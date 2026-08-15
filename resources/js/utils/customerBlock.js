import { getSessionToken } from '@/utils/sessionToken';

const STORAGE_KEY = 'customer_blocked';
export const CUSTOMER_BLOCKED_EVENT = 'customer-blocked';
export const DEFAULT_CUSTOMER_BLOCK_MESSAGE = 'تعذر استمرار الاتصال بالموقع. سيتم إغلاق الموقع تلقائيًا.';

export function markCustomerBlocked ( payload = {} )
{
    const state = {
        blocked: true,
        message: payload.message || DEFAULT_CUSTOMER_BLOCK_MESSAGE,
        sessionId: getSessionToken(),
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

export function clearCustomerBlocked ()
{
    try
    {
        localStorage.removeItem( STORAGE_KEY );
    } catch
    {
        // Storage may be unavailable in privacy-restricted browser contexts.
    }

    window.dispatchEvent( new CustomEvent( `${ CUSTOMER_BLOCKED_EVENT }:cleared` ) );
}

export function isCustomerBlocked ()
{
    return getCustomerBlockState() !== null;
}

export function isCustomerBlockedError ( error )
{
    return error?.response?.status === 423 && error.response?.data?.blocked === true;
}

export function getCustomerBlockState ()
{
    try
    {
        const state = JSON.parse( localStorage.getItem( STORAGE_KEY ) || 'null' );
        const isCurrentSession = state?.sessionId === getSessionToken();

        if ( state?.blocked === true && isCurrentSession ) return state;

        // Blocks from before session-scoped enforcement, or from a different
        // browser session, must not keep a visitor locked out locally.
        localStorage.removeItem( STORAGE_KEY );
        return null;
    } catch
    {
        return null;
    }
}
