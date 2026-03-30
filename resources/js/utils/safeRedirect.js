/**
 * Safe redirect utility — prevents open-redirect vulnerabilities.
 *
 * Only allows:
 *  • Relative paths starting with "/"
 *  • Absolute URLs whose origin matches the current page
 *
 * @param {string}  url             – The URL to redirect to
 * @param {string}  [fallbackPath]  – Route name (vue-router) used when the URL is unsafe
 * @param {import('vue-router').Router} [router] – Vue Router instance for fallback navigation
 * @param {object}  [options]
 * @param {boolean} [options.replace=false] – Use history-replacing navigation (no back button)
 */
export function safeRedirect ( url, fallbackPath, router, { replace = false } = {} )
{
    if ( typeof url !== 'string' || !url.trim() )
    {
        navigateFallback( fallbackPath, router );
        return;
    }

    const trimmed = url.trim();

    // 1. Relative path — always safe
    if ( trimmed.startsWith( '/' ) && !trimmed.startsWith( '//' ) )
    {
        if ( replace ) window.location.replace( trimmed );
        else window.location.href = trimmed;
        return;
    }

    // 2. Absolute URL — must share origin with current page
    try
    {
        const parsed = new URL( trimmed );
        if ( parsed.origin === window.location.origin )
        {
            if ( replace ) window.location.replace( trimmed );
            else window.location.href = trimmed;
            return;
        }
    } catch
    {
        // Not a valid URL — fall through to fallback
    }

    // 3. Unsafe or unparseable — use fallback
    if ( import.meta.env.DEV )
    {
        console.warn( '[safeRedirect] Blocked unsafe redirect:', trimmed );
    }
    navigateFallback( fallbackPath, router );
}

/** @private */
function navigateFallback ( name, router )
{
    if ( name && router )
    {
        router.push( { name } );
    }
}
