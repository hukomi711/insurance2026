import { describe, it, expect } from 'vitest';
import { calculateVAT, calculateTotalWithVAT, calculateMonthlyPrice, VAT_RATE } from '@/utils/pricing';

describe( 'pricing.js', () =>
{
    // ── VAT_RATE constant ──────────────────────────────

    it( 'VAT rate is 15%', () =>
    {
        expect( VAT_RATE ).toBe( 0.15 );
    } );

    // ── calculateVAT ──────────────────────────────────

    it( 'calculates 15% VAT on 1000', () =>
    {
        expect( calculateVAT( 1000 ) ).toBe( 150 );
    } );

    it( 'rounds VAT to nearest integer', () =>
    {
        // 999 * 0.15 = 149.85 → 150
        expect( calculateVAT( 999 ) ).toBe( 150 );
    } );

    it( 'accepts custom rate', () =>
    {
        expect( calculateVAT( 1000, 0.10 ) ).toBe( 100 );
    } );

    it( 'returns 0 for zero subtotal', () =>
    {
        expect( calculateVAT( 0 ) ).toBe( 0 );
    } );

    // ── calculateTotalWithVAT ─────────────────────────

    it( 'returns correct subtotal, vat, total', () =>
    {
        const result = calculateTotalWithVAT( 1000, 200 );
        expect( result.subtotal ).toBe( 1200 );
        expect( result.vat ).toBe( 180 );
        expect( result.total ).toBe( 1380 );
    } );

    it( 'defaults addonsTotal to 0', () =>
    {
        const result = calculateTotalWithVAT( 500 );
        expect( result.subtotal ).toBe( 500 );
        expect( result.vat ).toBe( 75 );
        expect( result.total ).toBe( 575 );
    } );

    it( 'accepts custom VAT rate', () =>
    {
        const result = calculateTotalWithVAT( 1000, 0, 0.05 );
        expect( result.vat ).toBe( 50 );
        expect( result.total ).toBe( 1050 );
    } );

    // ── calculateMonthlyPrice ─────────────────────────

    it( 'divides annual by 12 and rounds up', () =>
    {
        expect( calculateMonthlyPrice( 1200 ) ).toBe( 100 );
    } );

    it( 'rounds up when not evenly divisible', () =>
    {
        // 1000 / 12 = 83.33 → 84
        expect( calculateMonthlyPrice( 1000 ) ).toBe( 84 );
    } );

    it( 'returns 0 for zero annual price', () =>
    {
        expect( calculateMonthlyPrice( 0 ) ).toBe( 0 );
    } );
} );
