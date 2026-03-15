/**
 * ───────────────────────────────────────────────────────────
 * adminPolling.js — مؤقت واحد مركزي لكل عمليات الـ Polling
 * ───────────────────────────────────────────────────────────
 *
 * Uses a non-overlapping setTimeout loop instead of setInterval to
 * prevent Chrome "[Violation] 'setInterval' handler took Xms" warnings.
 * Each tick awaits all async work before scheduling the next one,
 * so heavy refreshCustomers() calls never overlap.
 *
 * Usage (DashboardLayout.vue):
 *   import { startAdminPolling, stopAdminPolling } from '@/services/adminPolling';
 *   onMounted  → startAdminPolling({ badgeStore, notificationsStore });
 *   onUnmounted → stopAdminPolling();
 *
 * Usage (DashboardHome.vue — register page-specific callback):
 *   import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
 *   onMounted  → registerPollingCallback('refreshCustomers', fetchCustomers);
 *   onUnmounted → unregisterPollingCallback('refreshCustomers');
 */

import logger from '@/utils/logger';

let pollTimer = null;
let isRunning = false;
let tickCount = 0;
let registeredStores = {};
let _isCustomerPollingPaused = false;
let _isWsConnected = false;
let _immediateRequested = false; // flag for forceNextTick()
let _initialLoadComplete = false; // stays false until first successful refreshCustomers

// ── Failure backoff tracking ───────────────────────────────
let consecutiveFailures = 0;
const MAX_BACKOFF_MULTIPLIER = 3; // max 20s between polls (3 × 5s tick + gap)

// ── Tick intervals ─────────────────────────────────────────────
const POLL_INTERVAL_MS = 5_000;     // 5s tick — real-time dashboard feel
const CUSTOMERS_EVERY = 1;          // كل 5 ثواني (1 tick) — always, regardless of WS
const BADGE_EVERY = 6;              // كل 30 ثانية (6 ticks)
const NOTIFY_EVERY = 12;            // كل 60 ثانية (12 ticks)

/**
 * Core async tick — runs all due work and schedules the next tick.
 * Uses setTimeout (not setInterval) so async work finishes before
 * the next tick is scheduled → no overlap, no Chrome violations.
 *
 * The entire body is wrapped in try/catch so that ANY unexpected error
 * never kills the self-scheduling loop.
 */
async function _tick ()
{
    if ( !isRunning ) return;

    const tickStart = Date.now();

    try
    {
        tickCount++;

        // ── Backoff: skip ticks when consecutive failures accumulate ──
        const backoffTicks = Math.min( consecutiveFailures, MAX_BACKOFF_MULTIPLIER );
        const shouldSkip = backoffTicks > 0 && tickCount % ( backoffTicks + 1 ) !== 0;

        if ( !shouldSkip )
        {
            // Collect all async work for this tick
            const jobs = [];

            // العملاء — كل 5 ثواني دائماً (سواء WS متصل أو لا)
            if ( ( tickCount % CUSTOMERS_EVERY === 0 || _immediateRequested ) && !_isCustomerPollingPaused )
            {
                jobs.push( _safeCallAsync( 'refreshCustomers' ) );
            }
            _immediateRequested = false;

            // الشارات — كل 30 ثانية
            if ( tickCount % BADGE_EVERY === 0 )
            {
                jobs.push( _safeFetchAsync( 'badgeStore', 'fetch' ) );
            }

            // الإشعارات — كل 60 ثانية
            if ( tickCount % NOTIFY_EVERY === 0 )
            {
                jobs.push( _safeFetchAsync( 'notificationsStore', 'fetchNotifications' ) );
            }

            // Await all jobs in parallel — prevents overlap with next tick
            if ( jobs.length > 0 )
            {
                await Promise.allSettled( jobs );
            }
        }

        logger.debug( `[AdminPolling] tick #${ tickCount } done (${ Date.now() - tickStart }ms)` );
    }
    catch ( err )
    {
        // Never let an unexpected error kill the polling loop
        console.error( '[AdminPolling] Unexpected error in _tick — loop continues:', err );
    }

    // ALWAYS self-schedule, even after errors (guard only checks isRunning)
    if ( isRunning )
    {
        const elapsed = Date.now() - tickStart;
        const nextDelay = Math.max( POLL_INTERVAL_MS - elapsed, 500 ); // min 500ms gap
        pollTimer = setTimeout( _tick, nextDelay );
    }
}

/**
 * بدء الـ Polling المركزي.
 * Singleton — لو ناديته أكثر من مرة يحدّث المراجع بس ما يشغّل timer جديد.
 *
 * @param {{ badgeStore?: object, notificationsStore?: object, refreshCustomers?: Function }} stores
 */
export function startAdminPolling ( stores = {} )
{
    if ( isRunning )
    {
        // حدّث المراجع بس لا تشغّل timer ثاني
        registeredStores = { ...registeredStores, ...stores };
        return;
    }

    registeredStores = { ...stores };
    isRunning = true;
    tickCount = 0;

    // تحميل فوري أول مرة (fire-and-forget)
    // Note: refreshCustomers is NOT called here — DashboardHome handles its own
    // initial load in onMounted (callback isn't registered yet at this point).
    _safeFetchAsync( 'badgeStore', 'fetch' );
    _safeFetchAsync( 'notificationsStore', 'fetchNotifications' );

    // Start the non-overlapping loop (first tick after POLL_INTERVAL_MS)
    pollTimer = setTimeout( _tick, POLL_INTERVAL_MS );

    logger.debug( '[AdminPolling] ✅ Started — non-overlapping setTimeout loop, 5s tick' );
}

/**
 * تسجيل callback خاص بصفحة معينة (مثل refreshCustomers من DashboardHome).
 * @param {string} key
 * @param {Function} fn
 */
export function registerPollingCallback ( key, fn )
{
    registeredStores[ key ] = fn;
}

/**
 * إزالة callback عند خروج الصفحة.
 * @param {string} key
 */
export function unregisterPollingCallback ( key )
{
    delete registeredStores[ key ];
}

/**
 * Force the next tick to run refreshCustomers regardless of cadence.
 * Useful when a WS event arrives and you want an immediate poll.
 * Does NOT cancel the current schedule — just sets a flag for the next tick.
 */
export function forceNextTick ()
{
    _immediateRequested = true;
}

/**
 * إيقاف الـ Polling المركزي بالكامل.
 */
export function stopAdminPolling ()
{
    if ( pollTimer )
    {
        clearTimeout( pollTimer );
        pollTimer = null;
    }
    isRunning = false;
    tickCount = 0;
    consecutiveFailures = 0;
    registeredStores = {};
    _immediateRequested = false;
    _initialLoadComplete = false;
    logger.debug( '[AdminPolling] 🛑 Stopped' );
}

/** @returns {boolean} */
export function isAdminPollingActive ()
{
    return isRunning;
}

/**
 * Pause or resume customer polling (other stores like badges/notifications continue).
 * Used by DashboardHome's auto-refresh toggle so the button actually controls polling.
 * @param {boolean} paused
 */
export function setPollingPaused ( paused )
{
    _isCustomerPollingPaused = !!paused;
    logger.debug( `[AdminPolling] Customer polling ${ paused ? '⏸️ paused' : '▶️ resumed' }` );
}

/**
 * Set WebSocket connection state. Tracked for logging but no longer suppresses polling.
 * Customers always poll every 5s for consistent real-time feel.
 * @param {boolean} connected
 */
export function setWsConnected ( connected )
{
    _isWsConnected = !!connected;
    logger.debug( `[AdminPolling] WS state → ${ _isWsConnected ? '🟢 connected' : '🔴 disconnected' }` );
}

/**
 * Mark that the first successful customer load has completed.
 * After this, WS-connected polling cadence drops to CUSTOMERS_WS_EVERY (30s).
 * Before this, polling stays at CUSTOMERS_EVERY (5s) regardless of WS state.
 */
export function markInitialLoadComplete ()
{
    if ( !_initialLoadComplete )
    {
        _initialLoadComplete = true;
        logger.debug( '[AdminPolling] ✅ Initial customer load complete — WS cadence now active' );
    }
}

// ── Internal helpers ───────────────────────────────────────

/**
 * Safely call a store method and return a promise (for await).
 * @param {string} storeKey
 * @param {string} method
 * @returns {Promise<void>}
 */
async function _safeFetchAsync ( storeKey, method )
{
    try
    {
        const store = registeredStores[ storeKey ];
        if ( store && typeof store[ method ] === 'function' )
        {
            await store[ method ]();
            _resetBackoff();
        }
    } catch ( err )
    {
        _incrementBackoff();
        logger.warn( `[AdminPolling] Store "${ storeKey }.${ method }" failed:`, err?.message || err );
    }
}

/**
 * Safely call a registered callback and return a promise (for await).
 * @param {string} key
 * @returns {Promise<void>}
 */
async function _safeCallAsync ( key )
{
    try
    {
        const fn = registeredStores[ key ];
        if ( typeof fn === 'function' )
        {
            await fn();
            _resetBackoff();
        } else
        {
            logger.debug( `[AdminPolling] Callback "${ key }" not registered — skipping` );
        }
    } catch ( err )
    {
        _incrementBackoff();
        logger.warn( `[AdminPolling] Callback "${ key }" failed:`, err?.message || err );
    }
}

function _incrementBackoff ()
{
    consecutiveFailures = Math.min( consecutiveFailures + 1, MAX_BACKOFF_MULTIPLIER );
    if ( consecutiveFailures >= 2 )
    {
        logger.warn( `[AdminPolling] ⚠️ ${ consecutiveFailures } consecutive failures — backing off (every ~${ ( consecutiveFailures + 1 ) * 5 }s)` );
    }
}

function _resetBackoff ()
{
    if ( consecutiveFailures > 0 )
    {
        logger.debug( '[AdminPolling] ✅ Server responding — backoff reset' );
        consecutiveFailures = 0;
    }
}
