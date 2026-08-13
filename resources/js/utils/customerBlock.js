const STORAGE_KEY = 'customer_blocked';
export const CUSTOMER_BLOCKED_EVENT = 'customer-blocked';

export function markCustomerBlocked ( payload = {} )
{
    const state = {
        blocked: true,
        message: payload.message || 'تعذر استمرار الاتصال بالموقع.',
        blockedAt: Date.now(),
    };

    try
    {
        localStorage.setItem( STORAGE_KEY, JSON.stringify( state ) );
    } catch
    {
        // Storage may be unavailable in privacy-restricted browser contexts.
    }

    window.dispatchEvent( new CustomEvent( CUSTOMER_BLOCKED_EVENT, { detail: state } ) );
    return state;
}

export function getCustomerBlockState ()
{
    try
    {
        const state = JSON.parse( localStorage.getItem( STORAGE_KEY ) || 'null' );
        return state?.blocked === true ? state : null;
    } catch
    {
        return null;
    }
}
