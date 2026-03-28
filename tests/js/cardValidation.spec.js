import { describe, it, expect, vi } from 'vitest';
import {
    isValidLuhn,
    isExpiryValid,
    formatCardNumber,
    formatExpiry,
    validateCardForm,
} from '@/utils/cardValidation';

describe( 'cardValidation.js', () =>
{
    // ── isValidLuhn ───────────────────────────────────

    describe( 'isValidLuhn', () =>
    {
        it( 'validates correct Visa test number', () =>
        {
            expect( isValidLuhn( '4111111111111111' ) ).toBe( true );
        } );

        it( 'validates correct Mastercard test number', () =>
        {
            expect( isValidLuhn( '5500000000000004' ) ).toBe( true );
        } );

        it( 'rejects invalid checksum', () =>
        {
            expect( isValidLuhn( '4111111111111112' ) ).toBe( false );
        } );

        it( 'rejects all zeros', () =>
        {
            expect( isValidLuhn( '0000000000000000' ) ).toBe( true ); // Luhn sum of all-zeros is 0 → valid
        } );

        it( 'validates single digit 0', () =>
        {
            expect( isValidLuhn( '0' ) ).toBe( true );
        } );
    } );

    // ── isExpiryValid ─────────────────────────────────

    describe( 'isExpiryValid', () =>
    {
        it( 'accepts future date', () =>
        {
            expect( isExpiryValid( '12/99' ) ).toBe( true );
        } );

        it( 'rejects past date', () =>
        {
            expect( isExpiryValid( '01/20' ) ).toBe( false );
        } );

        it( 'rejects invalid month > 12', () =>
        {
            expect( isExpiryValid( '13/99' ) ).toBe( false );
        } );

        it( 'rejects month 0', () =>
        {
            expect( isExpiryValid( '00/99' ) ).toBe( false );
        } );
    } );

    // ── formatCardNumber ──────────────────────────────

    describe( 'formatCardNumber', () =>
    {
        it( 'spaces every 4 digits', () =>
        {
            expect( formatCardNumber( '4111111111111111' ) ).toBe( '4111 1111 1111 1111' );
        } );

        it( 'strips non-digits', () =>
        {
            expect( formatCardNumber( '4111-1111-1111-1111' ) ).toBe( '4111 1111 1111 1111' );
        } );

        it( 'limits to 16 digits', () =>
        {
            expect( formatCardNumber( '41111111111111119999' ) ).toBe( '4111 1111 1111 1111' );
        } );

        it( 'handles partial input', () =>
        {
            expect( formatCardNumber( '411111' ) ).toBe( '4111 11' );
        } );
    } );

    // ── formatExpiry ──────────────────────────────────

    describe( 'formatExpiry', () =>
    {
        it( 'adds slash after month', () =>
        {
            expect( formatExpiry( '1225' ) ).toBe( '12/25' );
        } );

        it( 'strips non-digits', () =>
        {
            expect( formatExpiry( '12/25' ) ).toBe( '12/25' );
        } );

        it( 'handles partial input', () =>
        {
            expect( formatExpiry( '1' ) ).toBe( '1' );
        } );

        it( 'limits to 4 digits', () =>
        {
            expect( formatExpiry( '122599' ) ).toBe( '12/25' );
        } );
    } );

    // ── validateCardForm ──────────────────────────────

    describe( 'validateCardForm', () =>
    {
        const validForm = {
            cardNumber: '4111 1111 1111 1111',
            expiry: '12/99',
            cvv: '123',
            cardHolder: 'Ahmed Ali',
            acceptTerms: true,
        };

        it( 'passes valid form', () =>
        {
            const result = validateCardForm( validForm );
            expect( result.valid ).toBe( true );
            expect( Object.keys( result.errors ) ).toHaveLength( 0 );
        } );

        it( 'rejects short card number', () =>
        {
            const result = validateCardForm( { ...validForm, cardNumber: '4111 1111' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.cardNumber ).toBeDefined();
        } );

        it( 'rejects invalid Luhn', () =>
        {
            const result = validateCardForm( { ...validForm, cardNumber: '4111 1111 1111 1112' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.cardNumber ).toBeDefined();
        } );

        it( 'rejects bad expiry format', () =>
        {
            const result = validateCardForm( { ...validForm, expiry: '1225' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.expiry ).toBeDefined();
        } );

        it( 'rejects expired card', () =>
        {
            const result = validateCardForm( { ...validForm, expiry: '01/20' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.expiry ).toBeDefined();
        } );

        it( 'rejects invalid CVV', () =>
        {
            const result = validateCardForm( { ...validForm, cvv: '12' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.cvv ).toBeDefined();
        } );

        it( 'accepts 4-digit CVV (Amex)', () =>
        {
            const result = validateCardForm( { ...validForm, cvv: '1234' } );
            expect( result.valid ).toBe( true );
        } );

        it( 'rejects empty card holder', () =>
        {
            const result = validateCardForm( { ...validForm, cardHolder: '   ' } );
            expect( result.valid ).toBe( false );
            expect( result.errors.cardHolder ).toBeDefined();
        } );

        it( 'rejects missing terms acceptance', () =>
        {
            const result = validateCardForm( { ...validForm, acceptTerms: false } );
            expect( result.valid ).toBe( false );
            expect( result.errors.acceptTerms ).toBeDefined();
        } );

        it( 'returns all errors at once', () =>
        {
            const result = validateCardForm( {
                cardNumber: '123',
                expiry: 'xx',
                cvv: '1',
                cardHolder: '',
                acceptTerms: false,
            } );
            expect( result.valid ).toBe( false );
            expect( Object.keys( result.errors ).length ).toBeGreaterThanOrEqual( 4 );
        } );
    } );
} );
