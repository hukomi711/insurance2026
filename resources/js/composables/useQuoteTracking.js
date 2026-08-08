import { ref, onMounted, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import request from "@/api/request";
import logger from "@/utils/logger";

/**
 * Quote Tracking Composable
 *
 * Manages the full lifecycle of a quote session:
 * - Start session (generates UUID)
 * - Track step transitions
 * - Send heartbeats every 30s
 * - Mark session complete
 * - Persist UUID in sessionStorage for page refreshes
 *
 * Usage:
 *   const { startSession, trackStep, completeSession } = useQuoteTracking();
 */

const STORAGE_KEY = "quoteSessionUUID";
const HEARTBEAT_INTERVAL = 30_000; // 30 seconds
const QUOTE_DIAGNOSTICS_ENABLED = String( import.meta.env.VITE_QUOTE_DIAGNOSTICS || "false" ).toLowerCase() === "true";
const QUOTE_DIAGNOSTIC_REV = "qt-rev-2026-08-08-01";

// Shared state across components (singleton-like via module scope)
const sessionUUID = ref( sessionStorage.getItem( STORAGE_KEY ) || null );
const sessionId = ref( null );
const currentStep = ref( null );
let heartbeatTimer = null;
let startSessionInFlight = false; // prevent parallel startSession calls

function logClearOnForbiddenOnce ( uuid, source )
{
    if ( typeof window === "undefined" ) return;
    if ( !QUOTE_DIAGNOSTICS_ENABLED ) return;

    const normalizedUuid = typeof uuid === "string" && uuid.length > 0 ? uuid : "unknown";
    const diagnosticKey = `quote_403_cleanup_logged:${ normalizedUuid }`;
    if ( sessionStorage.getItem( diagnosticKey ) === "1" ) return;

    sessionStorage.setItem( diagnosticKey, "1" );
    const shortUuid = normalizedUuid === "unknown" ? normalizedUuid : normalizedUuid.slice( 0, 8 );

    // One-time diagnostic log to confirm the 403 cleanup path is working.
    console.info(
        `[QuoteTracking] Cleared stale quote session after 403 (${ source }). uuid=${ shortUuid }... rev=${ QUOTE_DIAGNOSTIC_REV }`,
    );
}

export function useQuoteTracking ()
{
    const route = useRoute();

    // ─── Start a new session ────────────────────────────────
    async function startSession ( insuranceType = null )
    {
        // Don't create duplicate sessions
        if ( sessionUUID.value ) return sessionUUID.value;

        // Prevent parallel startSession calls (e.g. during timeout)
        if ( startSessionInFlight ) return null;
        startSessionInFlight = true;

        try
        {
            const res = await request.post( "/quote/start", {
                insurance_type: insuranceType,
                referrer_url: document.referrer || null,
                utm_source: route.query.utm_source || null,
                utm_medium: route.query.utm_medium || null,
                utm_campaign: route.query.utm_campaign || null,
            } );

            sessionUUID.value = res.data.uuid;
            sessionId.value = res.data.session_id;
            sessionStorage.setItem( STORAGE_KEY, res.data.uuid );

            currentStep.value = "motorapp";
            startHeartbeat();

            return res.data.uuid;
        } catch ( err )
        {
            logger.warn(
                "[QuoteTracking] Failed to start session:",
                err.message,
            );
            return null;
        } finally
        {
            startSessionInFlight = false;
        }
    }

    // ─── Track a step change ────────────────────────────────
    async function trackStep (
        stepName,
        stepNumber,
        formData = null,
        exitReason = "next",
    )
    {
        if ( !sessionUUID.value ) return;

        currentStep.value = stepName;

        try
        {
            await request.post( `/quote/${ sessionUUID.value }/step`, {
                step: stepName,
                step_number: stepNumber,
                form_data: formData,
                exit_reason: exitReason,
            } );
        } catch ( err )
        {
            logger.warn( "[QuoteTracking] Failed to track step:", err.message );
        }
    }

    // ─── Heartbeat ──────────────────────────────────────────
    function startHeartbeat ()
    {
        stopHeartbeat(); // clear any existing

        heartbeatTimer = setInterval( async () =>
        {
            if ( !sessionUUID.value || !currentStep.value ) return;

            try
            {
                await request.post( `/quote/${ sessionUUID.value }/heartbeat`, {
                    step: currentStep.value,
                    tab_visible: !document.hidden,
                }, { silent: true } );
            } catch ( err )
            {
                // Session may be stale/inaccessible (not found or forbidden)
                // -> stop noisy retries and clear local session state.
                if ( err.response?.status === 404 || err.response?.status === 403 )
                {
                    if ( err.response?.status === 403 ) {
                        logClearOnForbiddenOnce( sessionUUID.value, "heartbeat" );
                    }

                    stopHeartbeat();
                    clearSession();
                }
            }
        }, HEARTBEAT_INTERVAL );
    }

    function stopHeartbeat ()
    {
        if ( heartbeatTimer )
        {
            clearInterval( heartbeatTimer );
            heartbeatTimer = null;
        }
    }

    // ─── Complete session ───────────────────────────────────
    async function completeSession ( formData = null )
    {
        if ( !sessionUUID.value ) return;

        try
        {
            await request.post( `/quote/${ sessionUUID.value }/complete`, {
                form_data: formData,
            } );
        } catch ( err )
        {
            logger.warn(
                "[QuoteTracking] Failed to complete session:",
                err.message,
            );
        } finally
        {
            stopHeartbeat();
            clearSession();
        }
    }

    // ─── Clear session ──────────────────────────────────────
    function clearSession ()
    {
        sessionUUID.value = null;
        sessionId.value = null;
        currentStep.value = null;
        sessionStorage.removeItem( STORAGE_KEY );
    }

    // ─── Resume existing session on mount ───────────────────
    async function resumeSession ( stepName )
    {
        if ( sessionUUID.value )
        {
            // Validate the session still exists on the server before starting heartbeat
            try
            {
                await request.get( `/quote/${ sessionUUID.value }` );
                currentStep.value = stepName;
                startHeartbeat();
            } catch ( _err )
            {
                if ( _err?.response?.status === 403 ) {
                    logClearOnForbiddenOnce( sessionUUID.value, "resumeSession" );
                }

                logger.warn( "[QuoteTracking] Stale session cleared:", sessionUUID.value );
                clearSession();
            }
        }
    }

    // ─── Visibility change handler ──────────────────────────
    function handleVisibility ()
    {
        if ( document.hidden )
        {
            // Tab switched away — heartbeat will report tab_visible=false
        } else if ( sessionUUID.value )
        {
            // Tab came back — ensure heartbeat is running
            startHeartbeat();
        }
    }

    // ─── beforeunload — send final beacon ───────────────────
    function handleBeforeUnload ()
    {
        if ( !sessionUUID.value || !currentStep.value ) return;

        // Use sendBeacon for reliability on page close
        const payload = JSON.stringify( {
            step: currentStep.value,
            tab_visible: false,
        } );

        navigator.sendBeacon(
            `/api/quote/${ sessionUUID.value }/heartbeat`,
            new Blob( [ payload ], { type: "application/json" } ),
        );
    }

    // ─── Lifecycle ──────────────────────────────────────────
    onMounted( () =>
    {
        document.addEventListener( "visibilitychange", handleVisibility );
        window.addEventListener( "beforeunload", handleBeforeUnload );
    } );

    onUnmounted( () =>
    {
        document.removeEventListener( "visibilitychange", handleVisibility );
        window.removeEventListener( "beforeunload", handleBeforeUnload );
        stopHeartbeat();
    } );

    return {
        // State
        sessionUUID,
        sessionId,
        currentStep,

        // Actions
        startSession,
        trackStep,
        completeSession,
        clearSession,
        resumeSession,
        startHeartbeat,
        stopHeartbeat,
    };
}

/**
 * Validate the persisted session UUID against the server once at app boot.
 * If the stored UUID no longer exists (e.g. server data was cleared),
 * remove it so subsequent sendBeacon/heartbeat calls don't 404.
 */
export async function validateStoredSession ()
{
    if ( typeof window !== 'undefined' ) {
        const path = window.location.pathname;
        const isPublicPage =
            path === '/' ||
            path.includes( '/blog' ) ||
            path.startsWith( '/login' ) ||
            path.startsWith( '/admin-verify' );

        if ( isPublicPage ) return;
    }

    const stored = sessionStorage.getItem( STORAGE_KEY );
    if ( !stored ) return;

    try
    {
        await request.get( `/quote/${ stored }`, { silent: true } );
    } catch ( err )
    {
        if ( err?.response?.status === 404 || err?.response?.status === 403 )
        {
            if ( err?.response?.status === 403 ) {
                logClearOnForbiddenOnce( stored, "validateStoredSession" );
            }

            sessionUUID.value = null;
            sessionId.value = null;
            currentStep.value = null;
            sessionStorage.removeItem( STORAGE_KEY );
        }
    }
}

