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

// Shared state across components (singleton-like via module scope)
const sessionUUID = ref( sessionStorage.getItem( STORAGE_KEY ) || null );
const sessionId = ref( null );
const currentStep = ref( null );
let heartbeatTimer = null;
let startSessionInFlight = false; // prevent parallel startSession calls

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
                // Session might have been marked abandoned
                if ( err.response?.status === 404 )
                {
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
        if ( err?.response?.status === 404 )
        {
            sessionUUID.value = null;
            sessionId.value = null;
            currentStep.value = null;
            sessionStorage.removeItem( STORAGE_KEY );
        }
    }
}

