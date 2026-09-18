import { describe, it, expect } from 'vitest';
import { buildPricingPayload } from '@/utils/buildPricingPayload';

describe( 'buildPricingPayload', () =>
{
    const basePlans = [
        { companyId: 1, subType: 'comprehensive', deductible: 3000 },
        { companyId: 2, subType: 'thirdParty', deductible: 1000 },
    ];

    const baseFormData = {
        vehicle: {
            year: 2022,
            make: 1,
            estimatedValue: 85000,
            purposeOfUse: 'personal',
            carModification: 'no',
            hasTrailer: 'no',
            transmissionType: '1',
        },
        driver: {
            dateOfBirth: '1990-05-15',
            drivingExperience: '4',
            accidentCounts: '0',
            trafficViolations: 'no',
            education: '5',
            foreignLicense: 'no',
            healthConditions: 'no',
            ncdYears: '3',
            city: 'الرياض',
            nightParking: '3',
            expectedKM: '3',
            additionalDrivers: [],
        },
        policy: {
            repairMethod: 'workshop',
        },
    };

    it( 'produces correct structure', () =>
    {
        const payload = buildPricingPayload( baseFormData, basePlans );

        expect( payload ).toHaveProperty( 'plans' );
        expect( payload ).toHaveProperty( 'vehicle' );
        expect( payload ).toHaveProperty( 'driver' );
        expect( payload ).toHaveProperty( 'policy' );
        expect( payload.plans ).toHaveLength( 2 );
    } );

    it( 'coerces plan values to numbers', () =>
    {
        const plans = [ { companyId: '1', subType: 'thirdParty', deductible: '3000' } ];
        const payload = buildPricingPayload( baseFormData, plans );

        expect( payload.plans[ 0 ].companyId ).toBe( 1 );
        expect( payload.plans[ 0 ].deductible ).toBe( 3000 );
    } );

    it( 'normalizes unsupported deductible values to 1000', () =>
    {
        const plans = [ { companyId: 1, subType: 'thirdParty', deductible: 1500 } ];
        const payload = buildPricingPayload( baseFormData, plans );

        expect( payload.plans[ 0 ].deductible ).toBe( 1000 );
    } );

    it( 'coerces vehicle values to numbers', () =>
    {
        const formData = {
            ...baseFormData,
            vehicle: { ...baseFormData.vehicle, year: '2022', make: '1', estimatedValue: '85000' },
        };
        const payload = buildPricingPayload( formData, basePlans );

        expect( payload.vehicle.year ).toBe( 2022 );
        expect( payload.vehicle.make ).toBe( 1 );
        expect( payload.vehicle.estimatedValue ).toBe( 85000 );
    } );

    it( 'defaults missing vehicle fields', () =>
    {
        const payload = buildPricingPayload( { vehicle: {}, driver: {}, policy: {} }, basePlans );

        expect( payload.vehicle.purposeOfUse ).toBe( 'personal' );
        expect( payload.vehicle.carModification ).toBe( 'no' );
        expect( payload.vehicle.hasTrailer ).toBe( 'no' );
        expect( payload.vehicle.transmissionType ).toBe( '1' );
    } );

    it( 'defaults missing driver fields', () =>
    {
        const payload = buildPricingPayload( { vehicle: {}, driver: {}, policy: {} }, basePlans );

        expect( payload.driver.dateOfBirth ).toBeNull();
        expect( payload.driver.drivingExperience ).toBeNull();
        expect( payload.driver.ncdYears ).toBeNull();
        expect( payload.driver.trafficViolations ).toBe( 'no' );
        expect( payload.driver.accidentCounts ).toBe( '0' );
    } );

    it( 'handles empty formData gracefully', () =>
    {
        const payload = buildPricingPayload( {}, basePlans );

        expect( payload.vehicle.year ).toBe( 0 );
        expect( payload.policy.repairMethod ).toBe( 'workshop' );
    } );

    it( 'maps additional drivers to empty objects', () =>
    {
        const formData = {
            ...baseFormData,
            driver: { ...baseFormData.driver, additionalDrivers: [ { name: 'Ali' }, { name: 'Omar' } ] },
        };
        const payload = buildPricingPayload( formData, basePlans );

        expect( payload.driver.additionalDrivers ).toHaveLength( 2 );
        expect( payload.driver.additionalDrivers[ 0 ] ).toEqual( {} );
    } );

    it( 'includes policy deductible override when present', () =>
    {
        const formData = {
            ...baseFormData,
            policy: { repairMethod: 'agency', deductible: 3000 },
        };
        const payload = buildPricingPayload( formData, basePlans );

        expect( payload.policy.deductible ).toBe( 3000 );
    } );

    it( 'omits policy deductible when not set', () =>
    {
        const payload = buildPricingPayload( baseFormData, basePlans );

        expect( payload.policy ).not.toHaveProperty( 'deductible' );
    } );

    it( 'defaults plans to empty array', () =>
    {
        const payload = buildPricingPayload( baseFormData );

        expect( payload.plans ).toEqual( [] );
    } );
} );
