/**
 * Saudi National ID / Iqama validation.
 *
 * Rules:
 *  - Exactly 10 digits
 *  - Starts with 1 (مواطن) or 2 (مقيم)
 *  - Passes Luhn checksum
 */

/**
 * Luhn checksum validation.
 * @param {string} digits — digits-only string
 * @returns {boolean}
 */
function luhnCheck ( digits )
{
    let sum = 0;
    let alternate = false;
    for ( let i = digits.length - 1; i >= 0; i-- )
    {
        let n = parseInt( digits[ i ], 10 );
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
 * Validate a Saudi National ID or Iqama number.
 * @param {string} id — the raw input
 * @param {{ context?: 'blur' | 'submit' }} options
 * @returns {{ valid: boolean, error: string }}
 */
export function validateNationalId ( id, { context = 'submit' } = {} )
{
    if ( !id )
    {
        return { valid: false, error: 'رقم الهوية / الإقامة مطلوب' };
    }

    if ( !/^\d+$/.test( id ) )
    {
        return { valid: false, error: 'رقم الهوية يجب أن يحتوي على أرقام فقط' };
    }

    if ( id.length !== 10 )
    {
        return { valid: false, error: 'رقم الهوية يجب أن يتكون من 10 أرقام' };
    }

    if ( !/^[12]/.test( id ) )
    {
        return { valid: false, error: 'رقم الهوية يجب أن يبدأ بالرقم 1 أو 2' };
    }

    if ( !luhnCheck( id ) )
    {
        const msg = context === 'blur'
            ? 'رقم الهوية غير صحيح'
            : 'يرجى إدخال رقم هوية صحيح لإكمال العملية';
        return { valid: false, error: msg };
    }

    return { valid: true, error: '' };
}
