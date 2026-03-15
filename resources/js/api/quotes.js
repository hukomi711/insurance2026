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
 * Local recalc: deductible/repair changes on ComparePage use calculateAllQuotes directly (no round-trip).
 */

const { calculateAllQuotes, calculatePremium } = usePricingEngine();

/**
 * Set repairLocation on plans based on user's repair method choice.
 */
function applyRepairLocation ( plans, repairMethod )
{
    const label = repairMethod === 'agency' ? 'الوكالة' : 'الورش المعتمدة';
    return plans.map( p => ( { ...p, repairLocation: label } ) );
}

/**
 * Fetch available insurance quotes for a vehicle.
 * @param {Object} formData — { vehicle, driver, policy } from useInsuranceStore().allFormData
 * @returns {Promise<{ plans: Array, companies: Array }>}
 */
export async function getQuotes ( formData = {} )
{
    const repairMethod = formData.policy?.repairMethod || 'workshop';

    // Attach company data to each plan
    const basePlans = vehiclePlans.map( plan => ( {
        ...plan,
        company: getCompany( plan.companyId ),
    } ) );

    const v = formData.vehicle || {};
    const hasRequiredData = v.year && v.make && v.estimatedValue;
    if ( !hasRequiredData )
    {
        // Not enough data for server pricing — use local engine
        const plans = applyRepairLocation( calculateAllQuotes( basePlans, formData ), repairMethod );
        return { plans, companies };
    }

    try
    {
        // Server-side batch pricing
        const payload = buildPricingPayload( formData, vehiclePlans );
        const { data } = await request.post( '/quotes/calculate', payload );

        // Merge server prices back onto full plan objects
        const quotesMap = new Map(
            ( data.quotes || [] ).map( q => [ `${ q.companyId }-${ q.subType }`, q ] )
        );

        const plans = basePlans.map( plan =>
        {
            const key = `${ plan.companyId }-${ plan.subType }`;
            const quote = quotesMap.get( key );
            if ( quote )
            {
                return {
                    ...plan,
                    annualPrice: quote.annualPrice,
                    monthlyPrice: quote.monthlyPrice,
                    basePrice: quote.basePrice,
                    pricingFactors: quote.pricingFactors,
                };
            }
            return plan;
        } );

        return { plans: applyRepairLocation( plans, repairMethod ), companies };
    } catch ( error )
    {
        // Fallback: local pricing engine
        console.warn( '[Quotes] API failed, falling back to local engine:', error.message );
        const plans = applyRepairLocation( calculateAllQuotes( basePlans, formData ), repairMethod );
        return { plans, companies };
    }
}

/**
 * Fetch a single quote/plan by ID with dynamic pricing.
 * @param {number|string} planId
 * @param {Object} [formData] — { vehicle, driver, policy } for dynamic pricing
 * @returns {Promise<object|null>}
 */
export function getQuoteById ( planId, formData = {} )
{
    // return request.get( `/quotes/${ planId }` );

    return new Promise( ( resolve ) =>
    {
        setTimeout( () =>
        {
            const plan = vehiclePlans.find( p => p.id === Number( planId ) );
            if ( !plan ) return resolve( null );

            const planWithCompany = { ...plan, company: getCompany( plan.companyId ) };

            // Apply dynamic pricing if form data is provided
            const hasFormData = formData.vehicle || formData.driver || formData.policy;
            if ( hasFormData )
            {
                const pricing = calculatePremium( planWithCompany, formData );
                resolve( {
                    ...planWithCompany,
                    annualPrice: pricing.annualPrice,
                    monthlyPrice: pricing.monthlyPrice,
                    pricingFactors: pricing.factors,
                } );
            } else
            {
                resolve( planWithCompany );
            }
        }, 300 );
    } );
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

/**
 * Fetch recommended/featured quotes with dynamic pricing.
 * @param {Object} [formData] — { vehicle, driver, policy } for dynamic pricing
 * @returns {Promise<{ recommended: object|null, cheapest: object|null }>}
 */
export function getFeaturedQuotes ( formData = {} )
{
    // return request.get( '/quotes/featured', { params } );

    return new Promise( ( resolve ) =>
    {
        setTimeout( () =>
        {
            const basePlans = vehiclePlans.map( p => ( { ...p, company: getCompany( p.companyId ) } ) );

            const hasFormData = formData.vehicle || formData.driver || formData.policy;
            const plans = hasFormData
                ? calculateAllQuotes( basePlans, formData )
                : basePlans;

            const recommended = plans.find( p => p.badgeType === 'recommended' ) || null;
            const cheapest = plans.find( p => p.badgeType === 'cheapest' )
                || [ ...plans ].sort( ( a, b ) => a.annualPrice - b.annualPrice )[ 0 ]
                || null;
            resolve( { recommended, cheapest } );
        }, 300 );
    } );
}
