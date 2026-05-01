/**
 * Contact / domain helpers (env-driven, no hardcoded domains).
 *
 * Public domain comes from VITE_APP_URL (build-time) or window.location.origin
 * (runtime fallback). Email domain prefers VITE_SUPPORT_EMAIL_DOMAIN, then
 * derives from the public host.
 */

function safeHost ( url )
{
    try { return new URL( url ).host; } catch { return ''; }
}

export function publicOrigin ()
{
    const fromEnv = import.meta.env.VITE_APP_URL;
    if ( fromEnv ) return fromEnv.replace( /\/+$/, '' );
    if ( typeof window !== 'undefined' && window.location?.origin ) return window.location.origin;
    return '';
}

export function publicHost ()
{
    return safeHost( publicOrigin() ) || ( typeof window !== 'undefined' ? window.location?.host : '' ) || 'localhost';
}

export function supportEmailDomain ()
{
    return import.meta.env.VITE_SUPPORT_EMAIL_DOMAIN || publicHost() || 'localhost';
}

export function supportEmail ( prefix )
{
    return `${prefix}@${supportEmailDomain()}`;
}
