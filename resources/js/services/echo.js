/**
 * Centralized Laravel Echo initialization via Reverb (WebSocket).
 *
 * Provides a singleton Echo instance shared across Vue pages.
 * Uses dynamic import so the bundle only loads laravel-echo when needed.
 *
 * Auth is handled automatically via `channelAuthorization.headersProvider`
 * (Pusher.js 8.x API) — the Bearer token is read from localStorage on
 * every auth request, so private-channel subscriptions always use the
 * latest token without needing to rebuild the Echo instance.
 *
 * Usage:
 *   import { getEcho } from '@/services/echo';
 *   const echo = await getEcho();
 *   echo.channel('otp.127.0.0.1').listen('.OtpApproved', handler);
 */

let echoInstance = null;
let echoPromise = null;
let echoSuspendedForPageCache = false;
let echoLifecycleErrorGraceUntil = 0;
let lifecycleHandlersRegistered = false;
const PUSHER_UNAVAILABLE_TIMEOUT_MS = 30_000;
const PAGE_CACHE_ERROR_GRACE_MS = 5_000;

import logger from "@/utils/logger";
import { reloadIfBuildChangedAfterPageCacheRestore } from "@/utils/buildFreshness";

function clearPusherTransportCache ()
{
    try
    {
        localStorage.removeItem( "pusherTransportTLS" );
        localStorage.removeItem( "pusherTransportNonTLS" );
    } catch
    {
        // Storage may be unavailable in privacy-restricted browser contexts.
    }
}

function getPusherInstance ()
{
    return echoInstance?.connector?.pusher ?? null;
}

function suspendEchoForPageLifecycle ( reason )
{
    if ( echoSuspendedForPageCache ) return;

    echoSuspendedForPageCache = true;
    echoLifecycleErrorGraceUntil = 0;

    try
    {
        // Disconnect while the document is still active. Chromium's pageswap
        // fires before pagehide, giving the socket time to close cleanly before
        // the document is frozen for the back-forward cache.
        const pusher = getPusherInstance();
        if ( pusher?.connection?.state !== "disconnected" ) pusher?.disconnect();
        logger.debug( `[Echo] WebSocket suspended for page lifecycle (${ reason })` );
    } catch ( err )
    {
        logger.debug( "[Echo] Page lifecycle suspend skipped:", err?.message ?? err );
    }
}

function registerPageCacheLifecycleHandlers ()
{
    if ( lifecycleHandlersRegistered || typeof window === "undefined" ) return;

    // pageswap is the earliest cross-document navigation signal in modern
    // Chromium. pagehide remains the interoperable fallback.
    window.addEventListener( "pageswap", () =>
    {
        suspendEchoForPageLifecycle( "pageswap" );
    } );

    window.addEventListener( "pagehide", () =>
    {
        // Close on every pagehide. If the document is discarded there is
        // nothing to resume; if it enters BFCache, pageshow reconnects it.
        suspendEchoForPageLifecycle( "pagehide" );
    } );

    window.addEventListener( "pageshow", async ( event ) =>
    {
        if ( !event.persisted ) return;

        try
        {
            if ( await reloadIfBuildChangedAfterPageCacheRestore( event ) ) return;
        } catch ( err )
        {
            // A failed freshness check must not prevent normal WS recovery.
            logger.debug( "[Echo] Build freshness check skipped:", err?.message ?? err );
        }

        clearPusherTransportCache();

        try
        {
            const pusher = getPusherInstance();
            if ( pusher && pusher.connection?.state !== "connected" )
            {
                pusher.connect();
                logger.debug( "[Echo] WebSocket resumed after back-forward cache restore" );
            }
        } catch ( err )
        {
            logger.warn( "[Echo] BFCache resume failed:", err?.message ?? err );
        } finally
        {
            echoSuspendedForPageCache = false;
            // A close/error task from the pre-BFCache socket can be delivered
            // just after pageshow. Treat it as an expected lifecycle event.
            echoLifecycleErrorGraceUntil = Date.now() + PAGE_CACHE_ERROR_GRACE_MS;
        }
    } );

    lifecycleHandlersRegistered = true;
}

export function isEchoSuspendedForPageCache ()
{
    return echoSuspendedForPageCache;
}

export function isEchoPageLifecycleErrorExpected ()
{
    return echoSuspendedForPageCache || Date.now() < echoLifecycleErrorGraceUntil;
}

/**
 * Return (or lazily create) the shared Echo instance configured for Reverb.
 *
 * Uses a promise lock to prevent race conditions when multiple callers
 * invoke getEcho() concurrently (e.g., onMounted + onActivated).
 *
 * @param {object} [_authOptions] — DEPRECATED. Kept for call-site compat;
 *   auth headers are now provided dynamically via headersProvider.
 * @returns {Promise<object|null>} Echo instance, or null if laravel-echo unavailable
 */
export async function getEcho ( _authOptions )
{
    if ( echoInstance ) return echoInstance;
    // Serialize concurrent callers — only the first creates the instance
    if ( echoPromise ) return echoPromise;

    echoPromise = _createEcho();
    try
    {
        return await echoPromise;
    } finally
    {
        echoPromise = null;
    }
}

async function _createEcho ()
{
    // On deployments without Reverb (e.g. shared hosting), skip WebSocket entirely.
    if ( !import.meta.env.VITE_REVERB_APP_KEY )
    {
        logger.info( "[Echo] VITE_REVERB_APP_KEY not set — WebSocket disabled" );
        return null;
    }

    // Skip Echo on public pages and when the browser is clearly offline.
    if ( typeof window === "undefined" || !window.navigator?.onLine )
    {
        logger.info( "[Echo] Browser offline or unavailable — WebSocket disabled" );
        return null;
    }

    registerPageCacheLifecycleHandlers();

    const path = window.location.pathname;
    const isPublicPage =
        path === "/" ||
        path.includes( "/blog" ) ||
        path.startsWith( "/login" ) ||
        path.startsWith( "/admin-verify" );
    if ( isPublicPage )
    {
        logger.info( "[Echo] Public page detected — WebSocket disabled" );
        return null;
    }

    try
    {
        const [ echoMod, pusherMod ] = await Promise.all( [
            import( "laravel-echo" ),
            import( "pusher-js" ),
        ] );

        // Navigation may have started while the dynamic imports were pending.
        // Do not create a fresh socket for a document that is being frozen.
        if ( echoSuspendedForPageCache ) return null;

        const Echo = echoMod.default ?? echoMod.Echo ?? echoMod;
        const Pusher = pusherMod.default ?? pusherMod;

        if ( !Echo || typeof Echo !== "function" )
        {
            logger.warn( "[Echo] laravel-echo loaded but Echo class not found", Object.keys( echoMod ) );
            return null;
        }

        window.Pusher = Pusher;

        // Clear Pusher transport cache — the cached strategy uses a tight
        // timeout (latency×2+1000ms, failFast:true) that aborts WS connections
        // prematurely on reconnect, producing "closed before established" errors.
        clearPusherTransportCache();

        const rawScheme = import.meta.env.VITE_REVERB_SCHEME;
        const rawHost = import.meta.env.VITE_REVERB_HOST;
        const rawPort = import.meta.env.VITE_REVERB_PORT;

        if ( import.meta.env.PROD && ( !rawScheme || !rawHost || !rawPort || rawHost === "localhost" ) )
        {
            logger.error( "[Echo] Invalid production Reverb VITE_* config", {
                scheme: rawScheme,
                host: rawHost,
                port: rawPort,
            } );

            return null;
        }

        const scheme = rawScheme || ( window.location.protocol === "https:" ? "https" : "http" );
        const host = rawHost || window.location.hostname;
        const port = Number( rawPort || ( scheme === "https" ? 443 : 80 ) );

        const config = {
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            Pusher,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === "https",
            enabledTransports: [ "ws", "wss" ],
            unavailableTimeout: PUSHER_UNAVAILABLE_TIMEOUT_MS,
            disableStats: true,
            authEndpoint: "/api/broadcasting/auth",
            // Custom authorizer — bypasses Pusher.js internal XHR auth
            // to guarantee the Bearer token is always sent correctly.
            // Retries up to 3 times with exponential backoff on network errors.
            channelAuthorization: {
                customHandler: ( { socketId, channelName }, callback ) =>
                {
                    const MAX_RETRIES = 3;

                    function attempt ( retry )
                    {
                        const token = localStorage.getItem( "auth_token" );
                        fetch( "/api/broadcasting/auth", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                                Accept: "application/json",
                                ...( token ? { Authorization: `Bearer ${ token }` } : {} ),
                            },
                            body: new URLSearchParams( {
                                socket_id: socketId,
                                channel_name: channelName,
                            } ),
                        } )
                            .then( ( r ) =>
                            {
                                if ( !r.ok )
                                {
                                    logger.error( `[Echo] Auth failed: ${ r.status } for ${ channelName }` );
                                    throw new Error( `Auth ${ r.status }` );
                                }
                                return r.json();
                            } )
                            .then( ( data ) => callback( null, data ) )
                            .catch( ( err ) =>
                            {
                                if ( retry < MAX_RETRIES )
                                {
                                    const delay = 1000 * Math.pow( 2, retry ); // 1s, 2s, 4s
                                    logger.warn( `[Echo] Auth retry ${ retry + 1 }/${ MAX_RETRIES } for ${ channelName } in ${ delay }ms` );
                                    setTimeout( () => attempt( retry + 1 ), delay );
                                } else
                                {
                                    logger.error( `[Echo] Auth failed after ${ MAX_RETRIES } retries for ${ channelName }` );
                                    callback( err, null );
                                }
                            } );
                    }

                    attempt( 0 );
                },
            },
        };

        echoInstance = new Echo( config );
        window.Echo = echoInstance;

        logger.info(
            "[Echo] Connected to Reverb at",
            `${ scheme }://${ host }:${ port }`,
        );

        return echoInstance;
    } catch ( err )
    {
        logger.warn( "[Echo] laravel-echo not available:", err?.message ?? err );
        return null;
    }
}

/**
 * Disconnect and destroy the Echo instance.
 */
export function destroyEcho ()
{
    echoPromise = null;
    echoSuspendedForPageCache = false;
    echoLifecycleErrorGraceUntil = 0;

    if ( echoInstance )
    {
        echoInstance.disconnect();
        echoInstance = null;
        window.Echo = undefined;
        logger.info( "[Echo] Disconnected" );
    }
}
