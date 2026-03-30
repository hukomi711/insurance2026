import { onMounted } from "vue";
import request from "@/api/request";
import { getEcho } from "@/services/echo";
import { getSessionToken } from "@/utils/sessionToken";
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
const HEARTBEAT_INTERVAL = 10_000; // 10 seconds — keeps is_active fresh (must be < inactivity timeout)
const THROTTLE_MS = 5_000; // minimum 5s between /customer/page calls
const MAX_ERRORS = 3; // pause heartbeat after this many consecutive errors
const BACKOFF_429_MS = 120_000; // 2 min backoff on rate-limit
const BACKOFF_500_MS = 60_000; // 1 min backoff on server error

// ── Global State (singleton) ───────────────────────────────
let heartbeatTimer = null;
let currentPage = null;
let globalActive = false;
let lastSentAt = 0;
let pendingTimer = null;
let consecutiveErrors = 0;
let isPaused = false;
let inFlightController = null; // AbortController for the current in-flight request

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
        logger.warn(
            `[Tracking] ⚠️ Error ${ reason }, consecutive: ${ consecutiveErrors }`,
        );

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

/** Pause heartbeat (error backoff or tab hidden). */
function pauseHeartbeat ()
{
    if ( heartbeatTimer )
    {
        clearInterval( heartbeatTimer );
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
async function sendPageUpdate ( page, force = false )
{
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

    // Abort any previous in-flight request to prevent connection pileup
    if ( inFlightController )
    {
        inFlightController.abort();
        inFlightController = null;
    }

    lastSentAt = Date.now();

    const controller = new AbortController();
    inFlightController = controller;

    try
    {
        await request.post( "/customer/page", {
            current_page: page,
        }, { signal: controller.signal, silent: true, timeout: 8000 } );
        consecutiveErrors = 0; // Reset on success
    } catch ( error )
    {
        // Ignore aborted requests (we aborted them intentionally)
        if ( error?.code === 'ERR_CANCELED' ) return;
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
    heartbeatTimer = setInterval( () =>
    {
        if ( !currentPage || document.hidden || isPaused ) return;

        sendPageUpdate( currentPage );
    }, HEARTBEAT_INTERVAL );
}

/** Stop the heartbeat interval. */
function stopHeartbeat ()
{
    if ( heartbeatTimer )
    {
        clearInterval( heartbeatTimer );
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

    // Admin dashboard must never run customer tracking or redirect listeners.
    // Both share the same SPA entry and same IP in dev (127.0.0.1), so without
    // this guard the admin's own browser would be redirected by its own actions.
    if ( window.location.pathname.startsWith( "/dashboard" ) ) return;

    globalActive = true;

    // Track every navigation automatically
    router.afterEach( ( to ) =>
    {
        // Skip dashboard routes — admin doesn't need to be tracked
        if ( to.path.startsWith( "/dashboard" ) ) return;

        // Skip browser-internal / garbage paths that aren't real app routes
        if ( /^\/(\.|api\/|favicon|robots|sitemap|images\/)/.test( to.path ) ) return;

        // Skip static asset paths (images, fonts, etc.)
        if ( /\.(png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|css|js|map)$/i.test( to.path ) ) return;

        const newPage = to.fullPath;
        const pageChanged = newPage !== currentPage;
        currentPage = newPage;

        // Force-send only when the page actually changed; heartbeat handles the rest
        sendPageUpdate( currentPage, pageChanged );
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
 * Subscribes to the customer's IP-based channel and navigates on redirect events.
 *
 * @param {import('vue-router').Router} router — The Vue Router instance
 */
async function setupRedirectListener ( router )
{
    try
    {
        // Admin dashboard must never listen for customer redirects —
        // otherwise the admin's own browser gets redirected too.
        if ( window.location.pathname.startsWith( "/dashboard" ) ) return;

        // Get customer IP from a lightweight endpoint (avoids duplicate /customer/page call)
        const res = await request.get( "/customer/ip" );
        const ip = res?.data?.customer_ip;
        if ( !ip ) return;

        const echo = await getEcho();
        if ( !echo ) return;

        const { safeRedirect } = await import( '@/utils/safeRedirect' );

        const ch = echo.channel( `customer.${ ip }` );

        const onRedirect = ( e ) =>
        {
            if ( e.redirect_url )
            {
                currentPage = e.redirect_url;
                sessionStorage.setItem( 'adminRedirectTarget', e.redirect_url );
                safeRedirect( e.redirect_url, '/', router, { replace: true } );
            }
        };

        ch.listen( ".CustomerRedirected", onRedirect );

        logger.info( "[Tracking] 📡 Redirect listener active on customer." + ip );
    } catch ( err )
    {
        logger.warn(
            "[Tracking] ❌ Failed to setup redirect listener:",
            err.message,
        );
    }
}

// ── Manual Cleanup ─────────────────────────────────────────

/**
 * Full cleanup — call on app shutdown if needed.
 * Stops heartbeat, removes event listeners, sends last beacon.
 */
export function cleanupVisitorTracking ()
{
    stopHeartbeat();
    isPaused = true;
    globalActive = false;
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

    onMounted( async () =>
    {
        try
        {
            await request.post( "/customer/page", {
                current_page: `/insurance/${ pageName }`,
            } );
        } catch ( err )
        {
            logger.warn( "[Tracking] ❌ Per-page tracking failed:", err.message );
        }
    } );
}
