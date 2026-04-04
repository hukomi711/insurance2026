import { describe, it, expect, vi, beforeEach } from 'vitest';
import { usePricingEngine } from '@/utils/pricingEngine';
import {
    BASE_PREMIUMS,
    PRICE_LIMITS,
    REPAIR_METHOD_FACTORS,
} from '@/data/pricingConstants';

// Stub logger to avoid side-effects
vi.mock( '@/utils/logger', () => ( { default: { error: vi.fn(), warn: vi.fn(), info: vi.fn() } } ) );

describe( 'usePricingEngine', () =>
{
    let engine;

    beforeEach( () =>
    {
        engine = usePricingEngine();
    } );

    // ── calculatePremium basic contract ──

    describe( 'calculatePremium', () =>
    {
        const minimalPlan = { companyId: 1, subType: 'thirdParty', deductible: 1500 };
        const emptyForm = { vehicle: {}, driver: {}, policy: {} };

        it( 'returns all expected fields', () =>
        {
            const result = engine.calculatePremium( minimalPlan, emptyForm );

            expect( result ).toHaveProperty( 'annualPrice' );
            expect( result ).toHaveProperty( 'monthlyPrice' );
            expect( result ).toHaveProperty( 'vatAmount' );
            expect( result ).toHaveProperty( 'totalWithVAT' );
            expect( result ).toHaveProperty( 'basePrice' );
            expect( result ).toHaveProperty( 'factors' );
            expect( result.factors ).toHaveProperty( 'vehicle' );
            expect( result.factors ).toHaveProperty( 'driver' );
            expect( result.factors ).toHaveProperty( 'lifestyle' );
            expect( result.factors ).toHaveProperty( 'policy' );
            expect( result.factors ).toHaveProperty( 'company' );
            expect( result.factors ).toHaveProperty( 'ncd' );
            expect( result.factors ).toHaveProperty( 'total' );
        } );

        it( 'uses BASE_PREMIUMS for base price', () =>
        {
            const result = engine.calculatePremium( minimalPlan, emptyForm );
            expect( result.basePrice ).toBe( BASE_PREMIUMS.thirdParty );
        } );

        it( 'rounds annualPrice to nearest 10', () =>
        {
            const result = engine.calculatePremium( minimalPlan, emptyForm );
            expect( result.annualPrice % 10 ).toBe( 0 );
        } );

        it( 'clamps price within PRICE_LIMITS', () =>
        {
            const limits = PRICE_LIMITS.thirdParty;
            const result = engine.calculatePremium( minimalPlan, emptyForm );
            expect( result.annualPrice ).toBeGreaterThanOrEqual( limits.min );
            expect( result.annualPrice ).toBeLessThanOrEqual( limits.max );
        } );

        it( 'calculates VAT at ~15%', () =>
        {
            const result = engine.calculatePremium( minimalPlan, emptyForm );
            // VAT may be ceil-rounded; just verify it's within 1 SAR of 15%
            const rough = result.annualPrice * 0.15;
            expect( Math.abs( result.vatAmount - rough ) ).toBeLessThanOrEqual( 1 );
        } );

        it( 'totalWithVAT equals annualPrice + vatAmount', () =>
        {
            const result = engine.calculatePremium( minimalPlan, emptyForm );
            expect( result.totalWithVAT ).toBeCloseTo( result.annualPrice + result.vatAmount, 1 );
        } );

        it( 'falls back on error', () =>
        {
            // plan with no subType or base — triggers catch in real usage if internals throw
            const brokenPlan = { annualPrice: 999 };
            // formData as null to trigger error path
            const result = engine.calculatePremium( brokenPlan, null );
            expect( result.annualPrice ).toBe( 999 );
            expect( result.factors.total ).toBe( 1 );
        } );
    } );

    // ── calculateAllQuotes ──

    describe( 'calculateAllQuotes', () =>
    {
        it( 'returns pricing for every plan', () =>
        {
            const plans = [
                { companyId: 1, subType: 'thirdParty', deductible: 1500 },
                { companyId: 2, subType: 'comprehensive', deductible: 2000 },
            ];
            const form = { vehicle: {}, driver: {}, policy: {} };
            const quotes = engine.calculateAllQuotes( plans, form );

            expect( quotes ).toHaveLength( 2 );
            expect( quotes[ 0 ] ).toHaveProperty( 'annualPrice' );
            expect( quotes[ 1 ] ).toHaveProperty( 'annualPrice' );
        } );

        it( 'preserves original plan fields', () =>
        {
            const plans = [ { companyId: 1, subType: 'thirdParty', deductible: 1500, myCustomField: 'keep' } ];
            const form = { vehicle: {}, driver: {}, policy: {} };
            const quotes = engine.calculateAllQuotes( plans, form );

            expect( quotes[ 0 ].myCustomField ).toBe( 'keep' );
        } );

        it( 'passes overrides through', () =>
        {
            const plans = [ { companyId: 1, subType: 'comprehensive', deductible: 1500 } ];
            const form = { vehicle: {}, driver: {}, policy: {} };
            const withOverride = engine.calculateAllQuotes( plans, form, { repairMethod: 'agency' } );
            const withoutOverride = engine.calculateAllQuotes( plans, form );

            // agency vs workshop should differ (assuming different factors)
            if ( REPAIR_METHOD_FACTORS.agency !== REPAIR_METHOD_FACTORS.workshop )
            {
                expect( withOverride[ 0 ].annualPrice ).not.toBe( withoutOverride[ 0 ].annualPrice );
            }
        } );
    } );

    // ── Factor sensitivity: changing inputs should move the price ──

    describe( 'factor sensitivity', () =>
    {
        const basePlan = { companyId: 1, subType: 'comprehensive', deductible: 1500 };

        function priceWith ( patch )
        {
            const form = {
                vehicle: { year: 2022, make: 1, estimatedValue: 80000, ...patch.vehicle },
                driver: { dateOfBirth: '1990-01-01', drivingExperience: '4', accidentCounts: '0', trafficViolations: 'no', city: 'الرياض', ncdYears: '3', ...patch.driver },
                policy: { repairMethod: 'workshop', ...patch.policy },
            };
            return engine.calculatePremium( basePlan, form ).annualPrice;
        }

        it( 'older vehicle costs more', () =>
        {
            const newer = priceWith( { vehicle: { year: new Date().getFullYear() } } );
            const older = priceWith( { vehicle: { year: 2010 } } );
            expect( older ).toBeGreaterThanOrEqual( newer );
        } );

        it( 'modification increases price', () =>
        {
            const stock = priceWith( { vehicle: { carModification: 'no' } } );
            const modded = priceWith( { vehicle: { carModification: 'yes' } } );
            expect( modded ).toBeGreaterThanOrEqual( stock );
        } );

        it( 'more accidents increase price', () =>
        {
            const clean = priceWith( { driver: { accidentCounts: '0' } } );
            const accidents = priceWith( { driver: { accidentCounts: '3' } } );
            expect( accidents ).toBeGreaterThanOrEqual( clean );
        } );

        it( 'NCD no longer reduces price', () =>
        {
            const noNcd = priceWith( { driver: { ncdYears: '0' } } );
            const maxNcd = priceWith( { driver: { ncdYears: '5' } } );
            expect( maxNcd ).toBe( noNcd );
        } );

        it( 'trailer raises price', () =>
        {
            const noTrailer = priceWith( { vehicle: { hasTrailer: 'no' } } );
            const withTrailer = priceWith( { vehicle: { hasTrailer: 'yes' } } );
            expect( withTrailer ).toBeGreaterThanOrEqual( noTrailer );
        } );

        it( 'additional drivers raise price', () =>
        {
            const noneExtra = priceWith( { driver: { additionalDrivers: [] } } );
            const twoExtra = priceWith( { driver: { additionalDrivers: [ {}, {} ] } } );
            expect( twoExtra ).toBeGreaterThanOrEqual( noneExtra );
        } );
    } );
} );
