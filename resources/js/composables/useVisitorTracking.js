import { onMounted } from "vue";
import request from "@/api/request";
import { getEcho } from "@/services/echo";
import { getSessionToken } from "@/utils/sessionToken";
import { customerBroadcastChannel } from "@/utils/customerBroadcastChannel";
import { isSaudiConfirmed } from "@/utils/geoCheck";
import { isCustomerBlocked, isCustomerBlockedError } from "@/utils/customerBlock";
import logger from "@/utils/logger";

/**
 * ───────────────────────────────────────────────────────────
 * Global Customer Activity Tracking (Optimized)
 * ───────────────────────────────────────────────────────────
 *
 * Tracks the customer's current page and activity for the admin dashboard.
 *
 * Two modes:
 *   1. Global mode (recommended): Call `initGlobalTracking(router)` once in app.js.
 *      - Auto-tracks ALL page navigations via router.afterEach
 *      - Sends heartbeat every 30s to keep is_active fresh
 *      - Handles tab visibility (pause heartbeat when hidden)
 *      - Uses sendBeacon on page unload for reliable last-chance tracking
 *      - Error backoff: pauses heartbeat after consecutive 429/500 errors
 *
 *   2. Per-page mode (legacy): Call `useVisitorTracking(pageName)` in a component.
 *      - Only fires once on mount. No heartbeat.
 *      - Auto-disabled when global tracking is active.
 */

// ── Configuration ──────────────────────────────────────────
const HEARTBEAT_INTERVAL = 30_000; // 30 seconds — keeps is_active fresh (must be < inactivity timeout)
const THROTTLE_MS = 5_000; // minimum 5s between /customer/page calls
const MAX_ERRORS = 3; // pause heartbeat after this many consecutive errors
const BACKOFF_429_MS = 120_000; // 2 min backoff on rate-limit
const BACKOFF_500_MS = 120_000; // 2 min backoff on server/timeout errors
const REDIRECT_RETRY_MIN_MS = 10_000;
const REDIRECT_RETRY_MAX_MS = 30_000;

// ── Global State (singleton) ───────────────────────────────
let heartbeatTimer = null;
let currentPage = null;
let globalActive = false;
let lastSentAt = 0;
let pendingTimer = null;
let consecutiveErrors = 0;
let isPaused = false;
let inFlightController = null; // AbortController for the current in-flight request
let _redirectRouter = null; // Router ref for polling-based redirects
let _redirectEcho = null;
let _redirectChannelName = null;
let _redirectRetryTimer = null;
let _redirectRetryAttempts = 0;
let _redirectListenerSetupInFlight = false;
let _lastTrackingErrorLogAt = 0;

// ── Error Backoff ──────────────────────────────────────────

/**
 * Handle API errors with progressive backoff.
 * Pauses heartbeat after MAX_ERRORS consecutive failures.
 */
function handleApiError ( error )
{
    const status = error?.response?.status;
    const isTimeout = !error.response && error.code === 'ECONNABORTED';
    const isNetworkError = !error.response && !isTimeout;

    if ( isTimeout || isNetworkError || status === 429 || status >= 500 )
    {
        consecutiveErrors++;
        const reason = isTimeout ? 'timeout' : isNetworkError ? 'network' : status;
        const now = Date.now();
        if ( consecutiveErrors === 1 || consecutiveErrors >= MAX_ERRORS || now - _lastTrackingErrorLogAt > 60_000 )
        {
            _lastTrackingErrorLogAt = now;
            logger.warn(
                `[Tracking] ⚠️ Error ${ reason }, consecutive: ${ consecutiveErrors }`,
            );
        }

        if ( consecutiveErrors >= MAX_ERRORS )
        {
            pauseHeartbeat();
            const backoff = status === 429 ? BACKOFF_429_MS : BACKOFF_500_MS;
            logger.warn(
                `[Tracking] ⏸️ Pausing heartbeat for ${ backoff / 1000 }s`,
            );
            setTimeout( resumeHeartbeat, backoff );
        }
    }
}

function isTransientNetworkError ( error )
{
    const message = String( error?.message ?? '' );
    return !error?.response && (
        error?.code === 'ECONNABORTED' ||
        error?.code === 'ERR_NETWORK' ||
        message.includes( 'timeout' ) ||
        message.includes( 'Network Error' ) ||
        message.includes( 'ERR_NETWORK_CHANGED' )
    );
}

function isAdminRoute ()
{
    return window.location.pathname.startsWith( "/dashboard" ) ||
        window.location.pathname.startsWith( "/admin" );
}

function scheduleRedirectListenerRetry ( router )
{
    if ( _redirectRetryTimer || !globalActive || isAdminRoute() ) return;

    const delay = Math.min(
        REDIRECT_RETRY_MAX_MS,
        REDIRECT_RETRY_MIN_MS * ( 2 ** _redirectRetryAttempts ),
    );
    _redirectRetryAttempts++;

    _redirectRetryTimer = setTimeout( () =>
    {
        _redirectRetryTimer = null;
        setupRedirectListener( router );
    }, delay );

    logger.debug(
        `[Tracking] redirect listener retry scheduled in ${ delay / 1000 }s`,
    );
}

/** Pause heartbeat (error backoff or tab hidden). */
function pauseHeartbeat ()
{
    if ( heartbeatTimer )
    {
        clearTimeout( heartbeatTimer );
        heartbeatTimer = null;
    }
    isPaused = true;
}

/** Resume heartbeat after backoff or tab visible again. */
function resumeHeartbeat ()
{
    consecutiveErrors = 0;
    isPaused = false;
    if ( globalActive && currentPage )
    {
        startHeartbeat();
        logger.info( "[Tracking] ▶️ Heartbeat resumed" );
    }
}

// ── Page Update (Throttled) ────────────────────────────────

/**
 * Send page update to backend — updates TrackedCustomer record.
 * Throttled: drops duplicate calls within THROTTLE_MS window.
 *
 * @param {string} page — Current page path
 * @param {boolean} force — Bypass throttle (e.g. page actually changed)
 */
/** Blog paths are accessible to all visitors and always trackable. */
const BLOG_PATH_RE = /^\/(ar\/|en\/)?blog(\/|$)/;

async function sendPageUpdate ( page, force = false )
{
    if ( isCustomerBlocked() ) return;

    // Don't track restricted (non-blog) pages when the visitor's Saudi status
    // is unconfirmed. Prevents foreign visitors who passed through Fail-Open
    // geo check from showing as "الصفحة الرئيسية" on the admin dashboard.
    // The heartbeat will pick up tracking once the background geo retry succeeds.
    if ( !BLOG_PATH_RE.test( page ) && !isSaudiConfirmed() ) return;

    // Skip if heartbeat is paused due to errors
    if ( isPaused && !force ) return;

    const now = Date.now();
    const elapsed = now - lastSentAt;

    // If same page was sent recently, skip or defer
    if ( !force && elapsed < THROTTLE_MS )
    {
        // Schedule a deferred send so the latest page is still tracked
        if ( !pendingTimer )
        {
            pendingTimer = setTimeout( () =>
            {
                pendingTimer = null;
                if ( currentPage && !document.hidden )
                {
                    sendPageUpdate( currentPage, true );
                }
            }, THROTTLE_MS - elapsed );
        }
        return;
    }

    // Do not stack heartbeat requests. Only a real navigation is allowed to
    // replace the current request because it carries newer page state.
    if ( inFlightController )
    {
        if ( !force ) return;
        inFlightController.abort();
        inFlightController = null;
    }

    lastSentAt = Date.now();

    const controller = new AbortController();
    inFlightController = controller;

    try
    {
        const res = await request.post( "/customer/page", {
            current_page: page,
        }, { signal: controller.signal, silent: true, timeout: 8000 } );
        consecutiveErrors = 0; // Reset on success

        // Polling fallback: admin-initiated redirect delivered via heartbeat response
        if ( res?.data?.redirect_to && res.data.redirect_to !== page )
        {
            currentPage = res.data.redirect_to;
            sessionStorage.setItem( 'adminRedirectTarget', res.data.redirect_to );
            const { safeRedirect } = await import( '@/utils/safeRedirect' );
            safeRedirect( res.data.redirect_to, '/', _redirectRouter, { replace: true } );
        }
    } catch ( error )
    {
        // Ignore aborted requests (we aborted them intentionally)
        if ( error?.code === 'ERR_CANCELED' ) return;
        if ( isCustomerBlockedError( error ) ) return;
        handleApiError( error );
    } finally
    {
        if ( inFlightController === controller )
        {
            inFlightController = null;
        }
    }
}

// ── Heartbeat Timer ────────────────────────────────────────

/**
 * Start the heartbeat interval.
 * Sends keep-alive unconditionally to keep is_active and last_activity_at
 * fresh on the server, even when the customer stays on the same page.
 */
function startHeartbeat ()
{
    stopHeartbeat();
    const tick = async () =>
    {
        if ( !heartbeatTimer ) return;

        if ( currentPage && !document.hidden && !isPaused )
        {
            await sendPageUpdate( currentPage );
        }

        if ( heartbeatTimer )
        {
            heartbeatTimer = setTimeout( tick, HEARTBEAT_INTERVAL );
        }
    };

    heartbeatTimer = setTimeout( tick, HEARTBEAT_INTERVAL );
}

/** Stop the heartbeat interval. */
function stopHeartbeat ()
{
    if ( heartbeatTimer )
    {
        clearTimeout( heartbeatTimer );
        heartbeatTimer = null;
    }
}

// ── Visibility & Unload ────────────────────────────────────

/** Handle tab visibility changes — pause heartbeat when hidden, resume when visible. */
function handleVisibilityChange ()
{
    if ( document.hidden )
    {
        stopHeartbeat();
    } else
    {
        // Tab became visible again — restart heartbeat (throttle handles dedup)
        if ( currentPage && !isPaused )
        {
            sendPageUpdate( currentPage );
            startHeartbeat();
        }
    }
}

/** Use sendBeacon on page unload for a reliable last-chance update. */
function handlePageUnload ()
{
    if ( !currentPage ) return;
    if ( isCustomerBlocked() ) return;
    if ( !BLOG_PATH_RE.test( currentPage ) && !isSaudiConfirmed() ) return;

    const csrfToken = document
        .querySelector( 'meta[name="csrf-token"]' )
        ?.getAttribute( "content" );
    const payload = JSON.stringify( {
        current_page: currentPage,
        session_id: getSessionToken(),
        _token: csrfToken || "",
    } );

    navigator.sendBeacon(
        "/api/customer/page",
        new Blob( [ payload ], { type: "application/json" } ),
    );
}

// ── Global Init ────────────────────────────────────────────

/**
 * Initialize global customer activity tracking.
 * Call once in app.js after creating the router.
 *
 * @param {import('vue-router').Router} router — The Vue Router instance
 */
export function initGlobalTracking ( router )
{
    if ( globalActive ) return;
    const isAdminPath = window.location.pathname.startsWith( "/admin" ) || window.location.pathname.startsWith( "/dashboard" );

    // Admin dashboard must never run customer tracking or redirect listeners.
    // Both share the same SPA entry and same IP in dev (127.0.0.1), so without
    // this guard the admin's own browser would be redirected by its own actions.
    if ( isAdminPath ) {
        logger.info( "[Tracking] disabled on admin route" );
        return;
    }

    _redirectRouter = router;

    globalActive = true;

    // Track every navigation automatically
    router.afterEach( ( to ) =>
    {
        // Skip dashboard routes — admin doesn't need to be tracked
        if ( to.path.startsWith( "/dashboard" ) || to.path.startsWith( "/admin" ) )
        {
            cleanupVisitorTracking();
            return;
        }

        // Skip browser-internal / garbage paths that aren't real app routes
        if ( /^\/(\.|api\/|favicon|robots|sitemap|images\/)/.test( to.path ) ) return;

        // Skip static asset paths (images, fonts, etc.)
        if ( /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|css|js|map)$/i.test( to.path ) ) return;

        if ( !globalActive )
        {
            globalActive = true;
            isPaused = false;
            _redirectRouter = router;
            document.addEventListener( "visibilitychange", handleVisibilityChange );
            window.addEventListener( "beforeunload", handlePageUnload );
            window.addEventListener( "pagehide", handlePageUnload );
            setupRedirectListener( router );
            logger.info( "[Tracking] resumed on public route" );
        }

        const newPage = to.fullPath;
        const pageChanged = newPage !== currentPage;
        currentPage = newPage;

        // Force-send only when the page actually changed; heartbeat handles the rest
        sendPageUpdate( currentPage, pageChanged );
        startHeartbeat();
    } );

    // Start heartbeat
    startHeartbeat();

    // Pause/resume on tab visibility change
    document.addEventListener( "visibilitychange", handleVisibilityChange );

    // Last-chance tracking on page unload (both events for browser compatibility)
    window.addEventListener( "beforeunload", handlePageUnload );
    window.addEventListener( "pagehide", handlePageUnload );

    // Listen for admin-initiated redirects via WebSocket
    setupRedirectListener( router );

    logger.info(
        `[Tracking] 🟢 Global tracking active (heartbeat: ${ HEARTBEAT_INTERVAL / 1000 }s)`,
    );
}

// ── WebSocket Redirect Channel ─────────────────────────────

/**
 * Set up WebSocket listener for admin-initiated customer redirects.
 * Subscribes to the customer's opaque session channel and navigates on redirects.
 *
 * Tracks `lastProcessedCommandId` to prevent processing duplicate redirects
 * (critical for successive redirects that arrive close together).
 *
 * @param {import('vue-router').Router} router — The Vue Router instance
 */
async function setupRedirectListener ( router )
{
    if ( _redirectListenerSetupInFlight || _redirectChannelName ) return;

    try
    {
        // Admin dashboard must never listen for customer redirects —
        // otherwise the admin's own browser gets redirected too.
        if ( isAdminRoute() ) return;

        _redirectListenerSetupInFlight = true;

        const echo = await getEcho();
        if ( !echo ) return;

        const { safeRedirect } = await import( '@/utils/safeRedirect' );
        const channelName = await customerBroadcastChannel( 'customer', getSessionToken() );

        const ch = echo.channel( channelName );
        _redirectEcho = echo;
        _redirectChannelName = channelName;
        _redirectRetryAttempts = 0;

        // Track the last processed command ID to prevent race conditions
        let lastProcessedCommandId = null;

        const onRedirect = ( e ) =>
        {
            // Ignore if no URL or missing command ID
            if ( !e.redirect_url || !e.command_id )
            {
                logger.warn( '[Tracking] Redirect event missing redirect_url or command_id', e );
                return;
            }

            // Ignore duplicate commands (same command_id processed twice)
            if ( e.command_id === lastProcessedCommandId )
            {
                logger.debug( '[Tracking] Ignoring duplicate redirect command:', e.command_id );
                return;
            }

            lastProcessedCommandId = e.command_id;

            // Clear only the temporary redirect keys, not the entire sessionStorage
            // This preserves customer session data while removing stale redirect markers
            const keysToClean = [
                'adminRedirectTarget',
                'adminRedirectInProgress',
            ];
            keysToClean.forEach( k => sessionStorage.removeItem( k ) );

            currentPage = e.redirect_url;
            sessionStorage.setItem( 'adminRedirectTarget', e.redirect_url );

            logger.info( '[Tracking] Processing redirect command:', {
                command_id: e.command_id,
                redirect_url: e.redirect_url,
            } );

            safeRedirect( e.redirect_url, '/', router, { replace: true } );
        };

        // Listener for force refresh commands from admin
        let lastProcessedRefreshId = null;
        const onForceRefresh = ( e ) =>
        {
            if ( !e.command_id )
            {
                logger.warn( '[Tracking] ForcePageRefresh event missing command_id', e );
                return;
            }

            // Ignore duplicate refresh commands
            if ( e.command_id === lastProcessedRefreshId )
            {
                logger.debug( '[Tracking] Ignoring duplicate refresh command:', e.command_id );
                return;
            }

            lastProcessedRefreshId = e.command_id;

            logger.info( '[Tracking] Processing force refresh command:', e.command_id );

            // Clean ONLY temporary redirect keys, not entire storage
            // This preserves session token and customer data
            const keysToClean = [
                'adminRedirectTarget',
                'adminRedirectInProgress',
                'adminRefreshInProgress',
            ];
            keysToClean.forEach( k => sessionStorage.removeItem( k ) );

            // Full page reload to reset customer view
            // Set a marker to indicate this is an admin-triggered reload
            sessionStorage.setItem( '_adminRefreshReload', '1' );
            window.location.reload();
        };

        ch.listen( ".CustomerRedirected", onRedirect );
        ch.listen( ".ForcePageRefresh", onForceRefresh );

        logger.info( "[Tracking] Redirect listener active" );
    } catch ( err )
    {
        if ( isTransientNetworkError( err ) )
        {
            logger.debug(
                "[Tracking] Redirect listener unavailable — will retry later",
                err.message,
            );
            scheduleRedirectListenerRetry( router );
            return;
        }

        logger.warn(
            "[Tracking] redirect listener setup failed — will retry later:",
            err.message,
        );
        scheduleRedirectListenerRetry( router );
    } finally
    {
        _redirectListenerSetupInFlight = false;
    }
}

// ── Manual Cleanup ─────────────────────────────────────────

/**
 * Full cleanup — call on app shutdown if needed.
 * Stops heartbeat, removes event listeners, sends last beacon.
 */
export function cleanupVisitorTracking ()
{
    if ( !globalActive && !heartbeatTimer && !_redirectChannelName ) return;

    stopHeartbeat();
    isPaused = true;
    globalActive = false;
    if ( pendingTimer )
    {
        clearTimeout( pendingTimer );
        pendingTimer = null;
    }
    if ( _redirectRetryTimer )
    {
        clearTimeout( _redirectRetryTimer );
        _redirectRetryTimer = null;
    }
    _redirectRetryAttempts = 0;
    if ( inFlightController )
    {
        inFlightController.abort();
        inFlightController = null;
    }
    if ( _redirectEcho && _redirectChannelName )
    {
        try
        {
            _redirectEcho.leave( _redirectChannelName );
        } catch
        {
            // safe to ignore
        }
    }
    _redirectEcho = null;
    _redirectChannelName = null;
    document.removeEventListener( "visibilitychange", handleVisibilityChange );
    window.removeEventListener( "beforeunload", handlePageUnload );
    window.removeEventListener( "pagehide", handlePageUnload );
    handlePageUnload();
    logger.info( "[Tracking] 🧹 Cleanup complete" );
}

// ── Per-Page Composable (Legacy) ───────────────────────────

/**
 * Per-page tracking composable (legacy).
 * Automatically becomes a no-op when global tracking is active.
 *
 * @param {string} pageName — Current page identifier (e.g., 'otp', 'card-pin')
 * @param {number} stepNumber — Step number in the flow (1-10)
 */
export function useVisitorTracking ( pageName, _stepNumber = 1 )
{
    // If global tracking is active, the router afterEach already handles this
    if ( globalActive ) return;
    if ( isCustomerBlocked() ) return;

    onMounted( async () =>
    {
        try
        {
            await request.post( "/customer/page", {
                current_page: `/insurance/${ pageName }`,
            }, { silent: true } );
        } catch ( err )
        {
            if ( isCustomerBlockedError( err ) ) return;
            logger.warn( "[Tracking] ❌ Per-page tracking failed:", err.message );
        }
    } );
}
