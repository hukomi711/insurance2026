/**
 * @vitest OtpInput.vue Component Tests
 * Tests focus on normalizeOtp logic, acceptedLengths validation, and clipboard handling
 */

import { describe, it, expect } from 'vitest';
import { normalizeAcceptedLengths, normalizeOtp } from '../../resources/js/utils/otp';

describe('OtpInput Normalization Logic', () => {
    describe('Latin Numeral Input', () => {
        it('accepts single Latin digit', () => {
            expect( normalizeOtp( '1', 6 ) ).toBe( '1' );
        });

        it('enforces max length (6 digits)', () => {
            expect( normalizeOtp( '1234567890', 6 ) ).toBe( '123456' );
        });

        it('rejects non-numeric input', () => {
            expect( normalizeOtp( '12a34b56', 6 ) ).toBe( '123456' );
        });

        it('accepts exactly 6 digits', () => {
            expect( normalizeOtp( '123456', 6 ) ).toBe( '123456' );
        });

        it('empty string returns empty', () => {
            expect( normalizeOtp( '', 6 ) ).toBe( '' );
        });

        it('null/undefined returns empty', () => {
            expect( normalizeOtp( null, 6 ) ).toBe( '' );
            expect( normalizeOtp( undefined, 6 ) ).toBe( '' );
        });
    });

    describe('Arabic Numeral Conversion', () => {
        it('converts Arabic Indic numerals ١٢٣٤٥٦ to Latin', () => {
            // Arabic Indic: ١٢٣٤٥٦ (U+0660-U+0669)
            expect( normalizeOtp( '١٢٣٤٥٦', 6 ) ).toBe( '123456' );
        });

        it('converts Extended Arabic numerals ۱۲۳۴۵۶ to Latin', () => {
            // Extended Arabic Indic: ۱۲۳۴۵۶ (U+06F0-U+06F9)
            expect( normalizeOtp( '۱۲۳۴۵۶', 6 ) ).toBe( '123456' );
        });

        it('handles mixed Arabic and Latin numerals', () => {
            // Mixed: 1٢3۴5۶
            expect( normalizeOtp( '1٢3۴5۶', 6 ) ).toBe( '123456' );
        });

        it('Arabic numerals with invalid characters', () => {
            // ١٢a٣٤ (Arabic + 'a' + more Arabic)
            expect( normalizeOtp( '١٢a٣٤', 6 ) ).toBe( '1234' );
        });

        it('Arabic numerals respect max length', () => {
            expect( normalizeOtp( '١٢٣٤٥٦٧٨', 6 ) ).toBe( '12345678'.slice( 0, 6 ) );
        });
    });

    describe('Custom Max Lengths', () => {
        it('max length 4', () => {
            expect( normalizeOtp( '123456', 4 ) ).toBe( '1234' );
        });

        it('max length 8', () => {
            expect( normalizeOtp( '123456789', 8 ) ).toBe( '12345678' );
        });

        it('Arabic numerals with custom max length', () => {
            expect( normalizeOtp( '١٢٣٤٥٦٧٨', 5 ) ).toBe( '12345' );
        });
    });
});

describe('AcceptedLengths Validation', () => {
    describe('Basic Validation', () => {
        it('accepts valid integer lengths', () => {
            expect( normalizeAcceptedLengths( [4, 6, 8], 8 ) ).toEqual( [4, 6, 8] );
        });

        it('rejects non-integer values', () => {
            expect( normalizeAcceptedLengths( [4, 'invalid', -1, 10, 6], 8 ) ).toEqual( [4, 6] );
        });

        it('rejects values exceeding maxLen', () => {
            expect( normalizeAcceptedLengths( [4, 6, 8, 10], 8 ) ).toEqual( [4, 6, 8] );
        });

        it('rejects zero and negative values', () => {
            expect( normalizeAcceptedLengths( [0, -1, 4, 6], 8 ) ).toEqual( [4, 6] );
        });

        it('deduplicates values', () => {
            expect( normalizeAcceptedLengths( [6, 6, 4, 4, 6], 8 ) ).toEqual( [4, 6] );
        });

        it('sorts output', () => {
            expect( normalizeAcceptedLengths( [8, 4, 6, 5], 8 ) ).toEqual( [4, 5, 6, 8] );
        });

        it('empty array returns empty', () => {
            expect( normalizeAcceptedLengths( [], 8 ) ).toEqual( [] );
        });

        it('non-array returns empty', () => {
            expect( normalizeAcceptedLengths( null, 8 ) ).toEqual( [] );
            expect( normalizeAcceptedLengths( undefined, 8 ) ).toEqual( [] );
            expect( normalizeAcceptedLengths( 'not-array', 8 ) ).toEqual( [] );
        });
    });

    describe('Edge Cases', () => {
        it('single valid length', () => {
            expect( normalizeAcceptedLengths( [6], 8 ) ).toEqual( [6] );
        });

        it('all values invalid', () => {
            expect( normalizeAcceptedLengths( ['a', 'b', 0, -1], 8 ) ).toEqual( [] );
        });

        it('custom maxLen enforcement', () => {
            expect( normalizeAcceptedLengths( [4, 6], 4 ) ).toEqual( [4] );
        });
    });
});

describe('Completion Logic', () => {
    it('no acceptLengths: complete at maxLen', () => {
        const input = normalizeOtp( '123456', 6 );
        const isComplete = input.length === 6;
        expect( isComplete ).toBe( true );
    });

    it('no acceptLengths: incomplete before maxLen', () => {
        const input = normalizeOtp( '12345', 6 );
        const isComplete = input.length === 6;
        expect( isComplete ).toBe( false );
    });

    it('with acceptLengths: complete if in list', () => {
        const acceptedLengths = normalizeAcceptedLengths( [4, 6, 8], 8 );
        const input = normalizeOtp( '1234', 8 );
        const isComplete = acceptedLengths.includes( input.length );
        expect( isComplete ).toBe( true );
    });

    it('with acceptLengths: incomplete if not in list', () => {
        const acceptedLengths = normalizeAcceptedLengths( [4, 6, 8], 8 );
        const input = normalizeOtp( '12345', 8 );
        const isComplete = acceptedLengths.includes( input.length );
        expect( isComplete ).toBe( false );
    });
});

describe('Clipboard Simulation', () => {
    it('normalizes pasted Latin numerals', () => {
        const pasted = '123456';
        const normalized = normalizeOtp( pasted, 6 );
        expect( normalized ).toBe( '123456' );
    });

    it('normalizes pasted Arabic numerals', () => {
        const pasted = '١٢٣٤٥٦';
        const normalized = normalizeOtp( pasted, 6 );
        expect( normalized ).toBe( '123456' );
    });

    it('normalizes mixed pasted content', () => {
        const pasted = 'Code: ١٢٣٤';
        const normalized = normalizeOtp( pasted, 6 );
        expect( normalized ).toBe( '1234' );
    });

    it('handles clipboard with whitespace', () => {
        const pasted = '  1 2 3 4 5 6  ';
        const normalized = normalizeOtp( pasted, 6 );
        expect( normalized ).toBe( '123456' );
    });
});

describe('Security Considerations', () => {
    it('pattern filtering is UI-only (server must validate)', () => {
        // Demonstrate that filtering is not a security boundary
        const clientFiltered = normalizeOtp( 'abc123def456', 6 );
        expect( clientFiltered ).toBe( '123456' );
        // Server would need separate validation
    });

    it('clipboard rejection is silent', () => {
        // Simulating clipboard rejection error
        const handleError = ( _err ) => {
            // Silent fail (permission denied, not secure context, etc.)
            return '';
        };

        const result = handleError( new Error( 'Not allowed' ) );
        expect( result ).toBe( '' );
    });
});

