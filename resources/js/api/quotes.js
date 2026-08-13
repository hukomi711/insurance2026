// import request from './request';
import request from './request';
import { vehiclePlans, companies, getCompany } from '@/data';
import { usePricingEngine } from '@/utils/pricingEngine';
import { buildPricingPayload } from '@/utils/buildPricingPayload';

/**
 * Quotes API service
 *
 * Primary flow: POST /api/quotes/calculate (server-side pricing).
 * Fallback: local pricing engine if API fails.
 * Selection-sensitive recalc should use this service so signatures stay in
 * sync with the displayed server price.
 */

const { calculateAllQuotes } = usePricingEngine();

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

function localPricedPlans ( plans, formData )
{
    return calculateAllQuotes( plans.map( clearSignatureFields ), formData )
        .map( unsignedPlan );
}

/**
 * Fetch available insurance quotes for a vehicle.
 * @param {Object} formData — { vehicle, driver, policy } from useInsuranceStore().allFormData
 * @param {Array} sourcePlans — plan objects to price; defaults to all plans
 * @returns {Promise<{ plans: Array, companies: Array }>}
 */
export async function getQuotes ( formData = {}, sourcePlans = vehiclePlans )
{
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
        // Not enough data for server pricing — use local engine
        const plans = applyRepairLocation( localPricedPlans( basePlans, formData ), repairMethod );
        return { plans, companies };
    }

    try
    {
        // Server-side batch pricing
        const payload = buildPricingPayload( formData, sourcePlans );
        const { data } = await request.post( '/quotes/calculate', payload );

        // Merge server prices back onto full plan objects
        const quotesMap = new Map(
            ( data.quotes || [] ).map( q => [
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
        if ( !shouldUseLocalPricingFallback( error ) ) throw error;

        // Fallback: local pricing engine
        console.warn( '[Quotes] API failed, falling back to local engine:', error.message );
        const plans = applyRepairLocation( localPricedPlans( basePlans, formData ), repairMethod );
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
