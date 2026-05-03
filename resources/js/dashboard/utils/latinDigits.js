/**
 * latinDigits — Force Western/Latin digits (0-9) inside the admin dashboard
 * regardless of UI language or locale.
 *
 * Use cases:
 *   - Convert any string containing Arabic-Indic (٠-٩) or Eastern-Arabic
 *     (۰-۹) digits to Latin (0-9).
 *   - Format numbers/dates with `Intl` while keeping the locale's wording
 *     (e.g. month names) but forcing digit shape to Latin via the
 *     Unicode extension `-u-nu-latn`.
 *   - Normalize user input on the fly (used as a global listener in
 *     DashboardLayout).
 */

const ARABIC_INDIC_DIGITS = '\u0660\u0661\u0662\u0663\u0664\u0665\u0666\u0667\u0668\u0669';
const EASTERN_ARABIC_DIGITS = '\u06f0\u06f1\u06f2\u06f3\u06f4\u06f5\u06f6\u06f7\u06f8\u06f9';

const ARABIC_INDIC_RE = /[\u0660-\u0669]/g;
const EASTERN_ARABIC_RE = /[\u06f0-\u06f9]/g;

/**
 * Convert any Arabic-Indic / Eastern-Arabic digits inside a value to Latin.
 * Returns '' for null/undefined. Coerces all other input to string.
 *
 * @param {*} value
 * @returns {string}
 */
export function toLatinDigits ( value )
{
    if ( value === null || value === undefined ) return '';
    return String( value )
        .replace( ARABIC_INDIC_RE, ( d ) => String( ARABIC_INDIC_DIGITS.indexOf( d ) ) )
        .replace( EASTERN_ARABIC_RE, ( d ) => String( EASTERN_ARABIC_DIGITS.indexOf( d ) ) );
}

/**
 * Format a number using Intl.NumberFormat with Latin digits forced.
 * Returns '—' for null/undefined/empty.
 *
 * @param {*} value
 * @param {Intl.NumberFormatOptions} [options]
 * @returns {string}
 */
export function formatNumberLatin ( value, options = {} )
{
    if ( value === null || value === undefined || value === '' ) return '—';
    const number = Number( value );
    if ( Number.isNaN( number ) ) return toLatinDigits( value );
    return new Intl.NumberFormat( 'en-US', { maximumFractionDigits: 2, ...options } ).format( number );
}

/**
 * Format a date with Latin digits while preserving locale (e.g. Arabic month names).
 * Pass any locale; the `-u-nu-latn` Unicode extension forces Latin numerals.
 *
 * @param {*} value Date | string | number
 * @param {string} [locale='ar-SA']
 * @param {Intl.DateTimeFormatOptions} [options]
 * @returns {string}
 */
export function formatDateLatin ( value, locale = 'ar-SA', options = {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
} )
{
    if ( !value ) return '—';
    const date = value instanceof Date ? value : new Date( value );
    if ( Number.isNaN( date.getTime() ) ) return toLatinDigits( value );
    try
    {
        return toLatinDigits(
            new Intl.DateTimeFormat( `${ locale }-u-nu-latn`, options ).format( date )
        );
    } catch
    {
        // Fallback if locale extension is unsupported
        return toLatinDigits( new Intl.DateTimeFormat( locale, options ).format( date ) );
    }
}

/**
 * Normalize an <input> / <textarea> value to Latin digits as the user types.
 * Preserves caret position when possible.
 *
 * @param {InputEvent} event
 */
export function normalizeInputDigits ( event )
{
    const input = event?.target;
    if ( !input || typeof input.value !== 'string' ) return;

    const original = input.value;
    if ( !ARABIC_INDIC_RE.test( original ) && !EASTERN_ARABIC_RE.test( original ) )
    {
        // Reset lastIndex on global regex test
        ARABIC_INDIC_RE.lastIndex = 0;
        EASTERN_ARABIC_RE.lastIndex = 0;
        return;
    }
    ARABIC_INDIC_RE.lastIndex = 0;
    EASTERN_ARABIC_RE.lastIndex = 0;

    const normalized = toLatinDigits( original );
    if ( normalized === original ) return;

    const start = input.selectionStart;
    const end = input.selectionEnd;
    input.value = normalized;

    // Notify v-model listeners (Vue uses 'input' event)
    input.dispatchEvent( new Event( 'input', { bubbles: true } ) );

    try
    {
        input.setSelectionRange( start, end );
    } catch
    {
        // setSelectionRange is unsupported on some input types (e.g. number, email)
    }
}
