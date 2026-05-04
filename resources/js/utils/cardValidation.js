/**
 * Card payment validation utilities.
 * Includes Luhn algorithm, expiry check, and format helpers.
 */

import { detectBankFromBin } from '@/utils/bankDetector';

const BLOCKED_BANKS = [];
// No BIN prefixes are blocked at the form layer.
// Keep this array empty unless there is a confirmed compliance/business reason.
const BLOCKED_BIN_PREFIXES = [];

function isBlockedBank ( digits )
{
    if ( BLOCKED_BIN_PREFIXES.some( ( p ) => digits.startsWith( p ) ) )
    {
        return true;
    }
    const bank = detectBankFromBin( digits );
    return bank && BLOCKED_BANKS.includes( bank );
}

/**
 * Luhn algorithm — validates credit/debit card numbers.
 * @param {string} num — card number digits only
 * @returns {boolean}
 */
export function isValidLuhn ( num )
{
    let sum = 0;
    let alternate = false;
    for ( let i = num.length - 1; i >= 0; i-- )
    {
        let n = parseInt( num[ i ], 10 );
        if ( alternate )
        {
            n *= 2;
            if ( n > 9 ) n -= 9;
        }
        sum += n;
        alternate = !alternate;
    }
    return sum % 10 === 0;
}

/**
 * Check if card expiry date is in the future.
 * @param {string} expiry — format "MM/YY"
 * @returns {boolean}
 */
export function isExpiryValid ( expiry )
{
    const [ mm, yy ] = expiry.split( '/' ).map( Number );
    if ( mm < 1 || mm > 12 ) return false;
    const now = new Date();
    const currentYear = now.getFullYear() % 100;
    const currentMonth = now.getMonth() + 1;
    if ( yy < currentYear || ( yy === currentYear && mm < currentMonth ) ) return false;
    return true;
}

/**
 * Format card number with spaces every 4 digits.
 * @param {string|null|undefined} raw — raw input value
 * @returns {string} formatted card number (empty string if input is falsy)
 */
export function formatCardNumber ( raw )
{
    if ( !raw ) return '';
    const digits = String( raw ).replace( /\D/g, '' ).slice( 0, 16 );
    return digits.replace( /(\d{4})(?=\d)/g, '$1 ' );
}

/**
 * Format expiry with slash (MM/YY).
 * @param {string} raw — raw input value
 * @returns {string} formatted expiry
 */
export function formatExpiry ( raw )
{
    let val = raw.replace( /\D/g, '' ).slice( 0, 4 );
    if ( val.length >= 2 ) val = val.slice( 0, 2 ) + '/' + val.slice( 2 );
    return val;
}

/**
 * Validate the full card form.
 * @param {object} form — { cardNumber, expiry, cvv, cardHolder, acceptTerms }
 * @returns {{ valid: boolean, errors: object }}
 */
export function validateCardForm ( form )
{
    const errors = {};
    let valid = true;

    const digits = form.cardNumber.replace( /\s/g, '' );
    if ( digits.length !== 16 )
    {
        errors.cardNumber = 'يرجى إدخال رقم بطاقة مكون من 16 رقم';
        valid = false;
    } else if ( isBlockedBank( digits ) )
    {
        errors.cardNumber = 'عذراً، هذه البطاقة غير مدعومة حالياً';
        valid = false;
    } else if ( !isValidLuhn( digits ) )
    {
        errors.cardNumber = 'رقم البطاقة غير صحيح';
        valid = false;
    }

    if ( !/^\d{2}\/\d{2}$/.test( form.expiry ) )
    {
        errors.expiry = 'يرجى إدخال تاريخ انتهاء صحيح (MM/YY)';
        valid = false;
    } else if ( !isExpiryValid( form.expiry ) )
    {
        errors.expiry = 'البطاقة منتهية الصلاحية';
        valid = false;
    }

    if ( !/^\d{3}$/.test( form.cvv ) )
    {
        errors.cvv = 'يرجى إدخال CVV صحيح (3 أرقام)';
        valid = false;
    }

    if ( !form.cardHolder.trim() )
    {
        errors.cardHolder = 'يرجى إدخال اسم حامل البطاقة';
        valid = false;
    }

    if ( !form.acceptTerms )
    {
        errors.acceptTerms = 'يجب الموافقة على الشروط والأحكام';
        valid = false;
    }

    return { valid, errors };
}
