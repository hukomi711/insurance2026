/**
 * Pricing Payload Mapper
 *
 * Transforms Pinia store's allFormData + plans array into the canonical
 * pricing payload for POST /api/quotes/calculate.
 *
 * Field names match the JS pricing engine exactly (camelCase, string codes).
 * Only pricing-relevant fields are included — no PII, no metadata.
 */

import { DEDUCTIBLE_OPTIONS } from '@/data/pricingConstants';

const DEFAULT_DEDUCTIBLE = 1000;
const DEDUCTIBLE_SET = new Set( DEDUCTIBLE_OPTIONS );
// The pricing API accepts manufacturer identifiers from 1 through 95.
// Keep the UI-only "other" selection compatible with that contract.
const OTHER_VEHICLE_MAKE_ID = 95;

function normalizeDeductible( value )
{
    const deductible = Number( value );
    return DEDUCTIBLE_SET.has( deductible ) ? deductible : DEFAULT_DEDUCTIBLE;
}

function normalizeVehicleMake( value )
{
    if ( value === 'other' ) return OTHER_VEHICLE_MAKE_ID;

    return Number( value ) || 0;
}

/**
 * Build the canonical pricing payload.
 *
 * @param {Object} formData — { vehicle, driver, policy } from useInsuranceStore().allFormData
 * @param {Array}  plans    — plan objects from plans.js (need companyId, subType, deductible)
 * @returns {Object} payload ready for POST /api/quotes/calculate
 */
export function buildPricingPayload ( formData, plans = [] )
{
    const { vehicle: v = {}, driver: d = {}, policy: p = {} } = formData;

    return {
        plans: plans.map( plan => ( {
            id: Number( plan.id ) || 0,
            companyId: Number( plan.companyId ),
            subType: plan.subType,
            deductible: normalizeDeductible( plan.deductible ),
        } ) ),

        vehicle: {
            year: Number( v.year ) || 0,
            make: normalizeVehicleMake( v.make ),
            estimatedValue: Number( v.estimatedValue ) || 0,
            purposeOfUse: v.purposeOfUse || 'personal',
            carModification: v.carModification || 'no',
            hasTrailer: v.hasTrailer || 'no',
            transmissionType: String( v.transmissionType || '1' ),
        },

        driver: {
            dateOfBirth: d.dateOfBirth || null,
            drivingExperience: d.drivingExperience || null,
            accidentCounts: String( d.accidentCounts ?? '0' ),
            trafficViolations: d.trafficViolations || 'no',
            education: String( d.education || '' ),
            foreignLicense: d.foreignLicense || 'no',
            healthConditions: d.healthConditions || 'no',
            ncdYears: d.ncdYears || null,
            city: d.city || '',
            nightParking: String( d.nightParking || '' ),
            expectedKM: String( d.expectedKM || '' ),
            additionalDrivers: Array.isArray( d.additionalDrivers )
                ? d.additionalDrivers.map( () => ( {} ) )
                : [],
        },

        policy: {
            repairMethod: p.repairMethod || 'workshop',
            ...(
                p.coverageLimit !== undefined && p.coverageLimit !== null && p.coverageLimit !== ''
                    ? { coverageLimit: Number( p.coverageLimit ) }
                    : {}
            ),
            ...(
                p.deductible !== undefined && p.deductible !== null && p.deductible !== ''
                    ? { deductible: normalizeDeductible( p.deductible ) }
                    : {}
            ),
        },
    };
}
