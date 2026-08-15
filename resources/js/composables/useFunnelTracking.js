import request from '@/api/request';
import logger from '@/utils/logger';
import { isCustomerBlocked, isCustomerBlockedError } from '@/utils/customerBlock';

/**
 * ───────────────────────────────────────────────────────────
 * useFunnelTracking — Conversion event capture composable
 * ───────────────────────────────────────────────────────────
 *
 * Sends lightweight analytics events to POST /api/analytics/funnel-event.
 * Server enriches with IP, UA, geo, customer_profile_id, and timestamp.
 *
 * Usage:
 *   import { trackFunnelEvent, trackStepViewed, trackStepCompleted } from '@/composables/useFunnelTracking';
 *
 *   onMounted(() => {
 *       trackStepViewed('compare', { quote_uuid: quoteUUID });
 *   });
 *
 *   function handleNextStep() {
 *       trackStepCompleted('compare', 'checkout');
 *       router.push({ name: 'checkout' });
 *   }
 */

// ── Funnel start timestamp (per-session, stored in sessionStorage) ──
const FUNNEL_START_KEY = 'funnel_start_ts';

function getFunnelStartTime ()
{
    let ts = sessionStorage.getItem( FUNNEL_START_KEY );
    if ( !ts )
    {
        ts = Date.now().toString();
        sessionStorage.setItem( FUNNEL_START_KEY, ts );
    }
    return parseInt( ts, 10 );
}

/**
 * Compute seconds elapsed since funnel start.
 */
function getElapsedSeconds ()
{
    return Math.floor( ( Date.now() - getFunnelStartTime() ) / 1000 );
}

// ── Device detection ────────────────────────────────────────────────
function getDeviceType ()
{
    return window.innerWidth < 768 ? 'mobile' : 'desktop';
}

// ── UTM parameters (read from URL on first visit, cached) ───────────
const UTM_CACHE_KEY = 'funnel_utm';

function getUtmParams ()
{
    let cached = sessionStorage.getItem( UTM_CACHE_KEY );
    if ( cached )
    {
        try { return JSON.parse( cached ); } catch { /* ignore */ }
    }

    const params = new URLSearchParams( window.location.search );
    const utm = {
        source: params.get( 'utm_source' ) || null,
        campaign: params.get( 'utm_campaign' ) || null,
    };

    sessionStorage.setItem( UTM_CACHE_KEY, JSON.stringify( utm ) );
    return utm;
}

// ── Quote UUID helper ───────────────────────────────────────────────
function getQuoteUUID ()
{
    return sessionStorage.getItem( 'quoteSessionUUID' ) || null;
}

// ── Previous step tracking ──────────────────────────────────────────
const PREV_STEP_KEY = 'funnel_prev_step';

function getPreviousStep ()
{
    return sessionStorage.getItem( PREV_STEP_KEY ) || null;
}

function setPreviousStep ( step )
{
    if ( step )
    {
        sessionStorage.setItem( PREV_STEP_KEY, step );
    }
}

// ── Deduplication guard (prevents double-fire on HMR/re-mount) ──────
let _lastEvent = '';
let _lastEventTime = 0;
const DEDUP_WINDOW = 2000; // 2s

function isDuplicate ( eventName, stepName )
{
    const key = `${ eventName }:${ stepName }`;
    const now = Date.now();
    if ( key === _lastEvent && now - _lastEventTime < DEDUP_WINDOW )
    {
        return true;
    }
    _lastEvent = key;
    _lastEventTime = now;
    return false;
}

/**
 * Send a funnel event. Fire-and-forget — never blocks the UI.
 *
 * @param {string} eventName — One of the allowed event names
 * @param {Object} [extra]   — Additional fields (step_name, metadata, etc.)
 */
export function trackFunnelEvent ( eventName, extra = {} )
{
    if ( isCustomerBlocked() ) return;

    const stepName = extra.step_name || null;

    if ( isDuplicate( eventName, stepName ) )
    {
        logger.debug( `[Funnel] Deduplicated: ${ eventName }/${ stepName }` );
        return;
    }

    const utm = getUtmParams();

    const payload = {
        event_name: eventName,
        step_name: stepName,
        previous_step: extra.previous_step || getPreviousStep(),
        quote_uuid: extra.quote_uuid || getQuoteUUID(),
        device_type: getDeviceType(),
        source: utm.source,
        campaign: utm.campaign,
        elapsed_seconds: getElapsedSeconds(),
        metadata: extra.metadata || null,
    };

    // Fire-and-forget POST — don't await, don't block
    request.post( '/analytics/funnel-event', payload, { silent: true } )
        .then( () => logger.debug( `[Funnel] ✓ ${ eventName }`, stepName || '' ) )
        .catch( ( err ) =>
        {
            if ( isCustomerBlockedError( err ) ) return;
            logger.warn( `[Funnel] Failed: ${ eventName }`, err?.message || '' );
        } );
}

/**
 * Track a funnel step being viewed (page mount).
 * @param {string} step — Step key (compare, checkout, otp, card_pin, payment_waiting, confirmation)
 * @param {Object} [metadata] — Extra context
 */
export function trackStepViewed ( step, metadata )
{
    trackFunnelEvent( 'funnel_step_viewed', {
        step_name: step,
        metadata,
    } );
}

/**
 * Track a funnel step being completed (user advancing to next step).
 * @param {string} currentStep — Completed step key
 * @param {string} nextStep    — Step being navigated to
 * @param {Object} [metadata]  — Extra context
 */
export function trackStepCompleted ( currentStep, nextStep, metadata )
{
    setPreviousStep( currentStep );
    trackFunnelEvent( 'funnel_step_completed', {
        step_name: currentStep,
        metadata: { ...metadata, next_step: nextStep },
    } );
}

// ── OTP-specific events ─────────────────────────────────────────────

export function trackOtpRequested ( metadata )
{
    trackFunnelEvent( 'otp_requested', { step_name: 'otp', metadata } );
}

export function trackOtpResent ( metadata )
{
    trackFunnelEvent( 'otp_resent', { step_name: 'otp', metadata } );
}

export function trackOtpExpired ( metadata )
{
    trackFunnelEvent( 'otp_expired', { step_name: 'otp', metadata } );
}

export function trackOtpVerified ( metadata )
{
    trackFunnelEvent( 'otp_verified', { step_name: 'otp', metadata } );
}

// ── Payment-specific events ─────────────────────────────────────────

export function trackPaymentWaitStarted ( metadata )
{
    trackFunnelEvent( 'payment_wait_started', { step_name: 'payment_waiting', metadata } );
}

export function trackPaymentWaitCompleted ( metadata )
{
    trackFunnelEvent( 'payment_wait_completed', { step_name: 'payment_waiting', metadata } );
}

// ── Order events ────────────────────────────────────────────────────

export function trackQuoteSelected ( metadata )
{
    trackFunnelEvent( 'quote_selected', { step_name: 'compare', metadata } );
}

export function trackCheckoutSubmitted ( metadata )
{
    trackFunnelEvent( 'checkout_submitted', { step_name: 'checkout', metadata } );
}

export function trackOrderConfirmed ( metadata )
{
    trackFunnelEvent( 'order_confirmed', { step_name: 'confirmation', metadata } );
}

// ── Abandonment tracking ────────────────────────────────────────────

/**
 * Track a funnel step being abandoned (user leaving mid-step).
 * Uses navigator.sendBeacon for reliability during page unload.
 *
 * @param {string} step — The step being abandoned
 * @param {string} [reason] — Reason for abandonment (e.g. 'page_unload', 'back_button', 'api_error')
 * @param {Object} [metadata] — Extra context
 */
export function trackStepAbandoned ( step, reason, metadata )
{
    if ( isCustomerBlocked() ) return;

    const utm = getUtmParams();
    const payload = {
        event_name: 'funnel_step_abandoned',
        step_name: step,
        previous_step: getPreviousStep(),
        quote_uuid: getQuoteUUID(),
        device_type: getDeviceType(),
        source: utm.source,
        campaign: utm.campaign,
        elapsed_seconds: getElapsedSeconds(),
        metadata: { ...metadata, reason: reason || 'unknown' },
    };

    // Use sendBeacon during unload for reliability, fall back to POST
    if ( navigator.sendBeacon )
    {
        const blob = new Blob( [ JSON.stringify( payload ) ], { type: 'application/json' } );
        navigator.sendBeacon( '/api/analytics/funnel-event', blob );
    }
    else
    {
        request.post( '/analytics/funnel-event', payload, { silent: true } ).catch( ( err ) =>
        {
            console.warn( '[FunnelTracking] Failed to send event:', err.message || err );
        } );
    }
}

/**
 * Install page-level abandonment listeners.
 * Call once per funnel page in onMounted. Returns cleanup function for onUnmounted.
 *
 * @param {() => string} getCurrentStep — Function returning current step name
 * @returns {() => void} cleanup function
 */
export function useAbandonmentTracking ( getCurrentStep )
{
    let abandoned = false;

    function handleAbandon ( reason )
    {
        if ( abandoned ) return;
        abandoned = true;
        const step = getCurrentStep();
        if ( step ) trackStepAbandoned( step, reason );
    }

    // Fires on tab close / navigate away
    function onPageHide () { handleAbandon( 'page_unload' ); }
    // Fires on browser back button
    function onPopState () { handleAbandon( 'back_button' ); }

    window.addEventListener( 'pagehide', onPageHide );
    window.addEventListener( 'popstate', onPopState );

    return () =>
    {
        window.removeEventListener( 'pagehide', onPageHide );
        window.removeEventListener( 'popstate', onPopState );
    };
}
