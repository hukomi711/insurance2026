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

import logger from "@/utils/logger";

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
    try
    {
        const [ echoMod, pusherMod ] = await Promise.all( [
            import( "laravel-echo" ),
            import( "pusher-js" ),
        ] );

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
        try {
            localStorage.removeItem( 'pusherTransportTLS' );
            localStorage.removeItem( 'pusherTransportNonTLS' );
        } catch { /* ignored */ }

        const scheme = import.meta.env.VITE_REVERB_SCHEME || "http";
        const host = import.meta.env.VITE_REVERB_HOST || "localhost";
        const port = import.meta.env.VITE_REVERB_PORT || "8080";

        const config = {
            broadcaster: "reverb",
            key: import.meta.env.VITE_REVERB_APP_KEY,
            Pusher,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS: scheme === "https",
            enabledTransports: [ "ws" ],
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
    if ( echoInstance )
    {
        echoInstance.disconnect();
        echoInstance = null;
        window.Echo = undefined;
        logger.info( "[Echo] Disconnected" );
    }
}
