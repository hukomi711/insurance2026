import { describe, it, expect } from 'vitest';
import { validateNationalId } from '../../resources/js/utils/nationalIdValidation';

describe( 'validateNationalId', () =>
{
    // ─── Valid IDs ────────────────────────────────────────────────
    it( 'accepts a valid Saudi National ID (starts with 1)', () =>
    {
        // 1000000008 — Luhn checksum: valid
        const result = validateNationalId( '1000000008' );
        expect( result.valid ).toBe( true );
        expect( result.error ).toBe( '' );
    } );

    it( 'accepts a valid Iqama number (starts with 2)', () =>
    {
        // 2000000006 — Luhn checksum: valid
        const result = validateNationalId( '2000000006' );
        expect( result.valid ).toBe( true );
        expect( result.error ).toBe( '' );
    } );

    // ─── Empty / Missing ─────────────────────────────────────────
    it( 'rejects empty string', () =>
    {
        const result = validateNationalId( '' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( 'مطلوب' );
    } );

    it( 'rejects null / undefined', () =>
    {
        expect( validateNationalId( null ).valid ).toBe( false );
        expect( validateNationalId( undefined ).valid ).toBe( false );
    } );

    // ─── Non-numeric ─────────────────────────────────────────────
    it( 'rejects letters', () =>
    {
        const result = validateNationalId( 'abcdefghij' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( 'أرقام فقط' );
    } );

    it( 'rejects mixed digits and letters', () =>
    {
        const result = validateNationalId( '12345abcde' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( 'أرقام فقط' );
    } );

    // ─── Wrong length ────────────────────────────────────────────
    it( 'rejects too short (9 digits)', () =>
    {
        const result = validateNationalId( '100000000' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( '10 أرقام' );
    } );

    it( 'rejects too long (11 digits)', () =>
    {
        const result = validateNationalId( '10000000080' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( '10 أرقام' );
    } );

    // ─── Wrong prefix ────────────────────────────────────────────
    it( 'rejects IDs starting with 3', () =>
    {
        const result = validateNationalId( '3000000000' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( '1 أو 2' );
    } );

    it( 'rejects IDs starting with 0', () =>
    {
        const result = validateNationalId( '0123456789' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( '1 أو 2' );
    } );

    // ─── Bad checksum ────────────────────────────────────────────
    it( 'rejects valid format but wrong checksum (submit context)', () =>
    {
        const result = validateNationalId( '1000000001' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toBe( 'يرجى إدخال رقم هوية صحيح لإكمال العملية' );
    } );

    it( 'rejects wrong checksum with blur context', () =>
    {
        const result = validateNationalId( '1000000001', { context: 'blur' } );
        expect( result.valid ).toBe( false );
        expect( result.error ).toBe( 'رقم الهوية غير صحيح' );
    } );

    it( 'rejects another invalid checksum', () =>
    {
        const result = validateNationalId( '2000000009' );
        expect( result.valid ).toBe( false );
        expect( result.error ).toContain( 'صحيح' );
    } );
} );
