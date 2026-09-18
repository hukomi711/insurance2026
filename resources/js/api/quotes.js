// import request from './request';
import request from './request';
import { vehiclePlans, companies, getCompany } from '@/data';
import { BASE_PREMIUMS } from '@/data/pricingConstants';
import { buildPricingPayload } from '@/utils/buildPricingPayload';
import { isCustomerBlocked, isCustomerBlockedError } from '@/utils/customerBlock';

/**
 * Quotes API service
 *
 * Primary flow: POST /api/quotes/calculate (server-side fixed pricing).
 * Fallback: local fixed pricing (same 399/499 SAR + 15% VAT) if API fails.
 * Selection-sensitive recalc should use this service so signatures stay in
 * sync with the displayed server price.
 */

const PRICING_API_BATCH_SIZE = 50;

const LOCAL_FALLBACK_ERROR_CODES = new Set( [
    'ECONNABORTED',
    'ETIMEDOUT',
    'ERR_NETWORK',
    'ECONNREFUSED',
    'ENETUNREACH',
    'EHOSTUNREACH',
] );

function shouldUseLocalPricingFallback ( error )
{
    const status = Number( error?.response?.status );
    if ( Number.isInteger( status ) )
    {
        return status >= 500 && status < 600;
    }

    const code = String( error?.code || '' ).toUpperCase();
    if ( LOCAL_FALLBACK_ERROR_CODES.has( code ) ) return true;

    const message = String( error?.message || '' );
    return !error?.response
        && /network error|failed to fetch|fetch failed|load failed/i.test( message );
}

/**
 * Set repairLocation on plans based on user's repair method choice.
 */
function applyRepairLocation ( plans, repairMethod )
{
    const label = repairMethod === 'agency' ? 'الوكالة' : 'الورش المعتمدة';
    return plans.map( p => ( { ...p, repairLocation: label } ) );
}

function clearSignatureFields ( plan )
{
    const {
        signature,
        timestamp,
        expiresAt,
        totalWithVATHalalas,
        priceUnit,
        ...rest
    } = plan;
    return rest;
}

function unsignedPlan ( plan )
{
    return {
        ...clearSignatureFields( plan ),
        signature: null,
        timestamp: null,
        expiresAt: null,
        totalWithVATHalalas: null,
        priceUnit: null,
    };
}

function localPricedPlans ( plans )
{
    const VAT_RATE = 0.15;
    return plans.map( clearSignatureFields ).map( plan =>
    {
        const basePrice = BASE_PREMIUMS[ plan.subType ] ?? 1000;
        const vatAmount = Math.round( basePrice * VAT_RATE * 100 ) / 100;
        return unsignedPlan( {
            ...plan,
            annualPrice: basePrice,
            originalPrice: basePrice,
            monthlyPrice: Math.round( basePrice / 12 ),
            vatAmount,
            basePrice,
            totalWithVAT: Math.round( ( basePrice + vatAmount ) * 100 ) / 100,
            pricingFactors: null,
        } );
    } );
}

/**
 * Fetch available insurance quotes for a vehicle.
 * @param {Object} formData — { vehicle, driver, policy } from useInsuranceStore().allFormData
 * @param {Array} sourcePlans — plan objects to price; defaults to all plans
 * @returns {Promise<{ plans: Array, companies: Array }>}
 */
export async function getQuotes ( formData = {}, sourcePlans = vehiclePlans )
{
    if ( isCustomerBlocked() )
    {
        return { plans: [], companies };
    }

    const repairMethod = formData.policy?.repairMethod || 'workshop';

    // Attach company data to each plan
    const basePlans = sourcePlans.map( plan => ( {
        ...plan,
        company: getCompany( plan.companyId ),
    } ) );

    const v = formData.vehicle || {};
    const hasRequiredData = v.year && v.make && v.estimatedValue;
    if ( !hasRequiredData )
    {
        // Not enough data for server validation — use fixed local pricing (same values as the server)
        const plans = applyRepairLocation( localPricedPlans( basePlans ), repairMethod );
        return { plans, companies };
    }

    try
    {
        // Keep each request within CalculateQuoteRequest's anti-amplification
        // limit while still pricing every configured plan.
        const serverQuotes = [];
        for ( let offset = 0; offset < sourcePlans.length; offset += PRICING_API_BATCH_SIZE )
        {
            const planBatch = sourcePlans.slice( offset, offset + PRICING_API_BATCH_SIZE );
            const payload = buildPricingPayload( formData, planBatch );
            const { data } = await request.post( '/quotes/calculate', payload );
            serverQuotes.push( ...( data.quotes || [] ) );
        }

        // Merge server prices back onto full plan objects
        const quotesMap = new Map(
            serverQuotes.map( q => [
                q.id ? `id:${ q.id }` : `type:${ q.companyId }-${ q.subType }`,
                q,
            ] )
        );

        const plans = basePlans.map( plan =>
        {
            const quote = quotesMap.get( `id:${ plan.id }` )
                || quotesMap.get( `type:${ plan.companyId }-${ plan.subType }` );
            if ( quote )
            {
                return {
                    ...plan,
                    annualPrice: quote.annualPrice,
                    originalPrice: quote.originalPrice,
                    monthlyPrice: quote.monthlyPrice,
                    vatAmount: quote.vatAmount,
                    basePrice: quote.basePrice,
                    pricingFactors: quote.pricingFactors,
                    signature: quote.signature,
                    timestamp: quote.timestamp,
                    expiresAt: quote.expiresAt,
                    totalWithVAT: quote.totalWithVAT,
                    totalWithVATHalalas: quote.totalWithVATHalalas,
                    priceUnit: quote.priceUnit,
                };
            }
            return plan;
        } );

        return { plans: applyRepairLocation( plans, repairMethod ), companies };
    } catch ( error )
    {
        if ( isCustomerBlockedError( error ) )
        {
            return { plans: [], companies };
        }

        if ( !shouldUseLocalPricingFallback( error ) ) throw error;

        // Fallback: fixed local pricing (same values as the server)
        console.warn( '[Quotes] API failed, falling back to fixed local pricing:', error.message );
        const plans = applyRepairLocation( localPricedPlans( basePlans ), repairMethod );
        return { plans, companies };
    }
}

/**
 * Submit a selected quote for checkout — creates a real order via API.
 * @param {{ planId: number, deductible?: number, additionalCoverages?: number[], [key: string]: any }} payload
 * @returns {Promise<{ success: boolean, order_number: string, policy_number: string, order_id: number }>}
 */
export async function submitQuote ( payload )
{
    const { data } = await request.post( '/orders', payload );
    return data;
}
