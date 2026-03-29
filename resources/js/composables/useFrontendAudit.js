/**
 * useFrontendAudit — dev-only composable that instruments WS, polling,
 * stores, timers, and global errors for real-time diagnostics.
 *
 * Usage (DashboardLayout.vue, dev-only dynamic import):
 *   if (import.meta.env.DEV) {
 *     const { useFrontendAudit } = await import('@/composables/useFrontendAudit');
 *     useFrontendAudit();
 *   }
 */

import { onUnmounted, getCurrentInstance } from 'vue';
import { auditLog, auditWarn, auditError, isAuditEnabled } from '@/utils/auditLogger';

// ── Shared state (singleton across component instances) ────────

/** @type {Map<string, number>} channel name → subscribe timestamp */
const _channels = new Map();

/** Active timer count (patched setTimeout / setInterval) */
let _activeTimers = 0;

/** Store mutation counter */
let _storeMutations = 0;

/** Polling tick counter observed by the audit */
let _pollingTicks = 0;

/** Error counter */
let _errorCount = 0;

/** Whether instrumentation was already installed (singleton guard) */
let _installed = false;

// ── Public read-only stats (for AuditOverlay) ──────────────────

export function getAuditStats ()
{
    return {
        channels: _channels.size,
        channelList: [ ..._channels.keys() ],
        activeTimers: _activeTimers,
        storeMutations: _storeMutations,
        pollingTicks: _pollingTicks,
        errorCount: _errorCount,
    };
}

// ── Module A: Error Tracking ───────────────────────────────────

function _installErrorTracking ( app )
{
    // window.onerror
    const prevOnError = window.onerror;
    window.onerror = ( msg, src, line, col, err ) =>
    {
        _errorCount++;
        auditError( 'error', `Uncaught: ${ msg }`, { src, line, col, stack: err?.stack } );
        if ( prevOnError ) prevOnError( msg, src, line, col, err );
    };

    // unhandledrejection
    const prevRejection = window.onunhandledrejection;
    window.onunhandledrejection = ( event ) =>
    {
        _errorCount++;
        auditError( 'error', `Unhandled rejection: ${ event.reason }`, { reason: event.reason } );
        if ( prevRejection ) prevRejection( event );
    };

    // Vue app error handler (chain existing)
    if ( app )
    {
        const prevHandler = app.config.errorHandler;
        app.config.errorHandler = ( err, vm, info ) =>
        {
            _errorCount++;
            auditError( 'error', `Vue: ${ err?.message || err }`, { info, stack: err?.stack } );
            if ( prevHandler ) prevHandler( err, vm, info );
        };
    }
}

// ── Module B: WebSocket / Echo Tracking ────────────────────────

function _installEchoTracking ()
{
    // Dynamically import echo to get the singleton
    import( '@/services/echo' ).then( ( { getEcho } ) =>
    {
        getEcho().then( ( echo ) =>
        {
            if ( !echo || !echo.connector ) return;

            const connector = echo.connector;

            // Proxy channel subscriptions
            const origSubscribe = connector.subscribe?.bind( connector );
            if ( origSubscribe )
            {
                connector.subscribe = ( name ) =>
                {
                    if ( _channels.has( name ) )
                    {
                        auditWarn( 'ws', `Duplicate subscribe: ${ name }` );
                    }
                    _channels.set( name, Date.now() );
                    auditLog( 'ws', `Subscribe: ${ name }`, { total: _channels.size } );
                    return origSubscribe( name );
                };
            }

            // Proxy channel leave
            const origLeave = connector.leave?.bind( connector );
            if ( origLeave )
            {
                connector.leave = ( name ) =>
                {
                    _channels.delete( name );
                    // Also remove presence/private variants
                    _channels.delete( `private-${ name }` );
                    _channels.delete( `presence-${ name }` );
                    auditLog( 'ws', `Leave: ${ name }`, { total: _channels.size } );
                    return origLeave( name );
                };
            }

            // Track connection state changes
            const pusher = echo.connector.pusher;
            if ( pusher?.connection )
            {
                pusher.connection.bind( 'state_change', ( { current, previous } ) =>
                {
                    auditLog( 'ws', `State: ${ previous } → ${ current }` );
                } );
            }

            auditLog( 'ws', 'Echo tracking installed' );
        } );
    } ).catch( () =>
    {
        auditWarn( 'ws', 'Echo not available — WS tracking skipped' );
    } );
}

// ── Module C: Polling Tracking ─────────────────────────────────

function _installPollingTracking ()
{
    const origDebug = console.debug;
    const POLL_TAG = '[AdminPolling]';

    // We intercept admin polling logs (it already logs tick info via logger.debug)
    // This is non-invasive: we just listen instead of monkeypatching the module.
    console.debug = ( ...args ) =>
    {
        const first = args[0];
        if ( typeof first === 'string' && first.includes( POLL_TAG ) )
        {
            _pollingTicks++;
            auditLog( 'polling', first.replace( POLL_TAG, '' ).trim(), args.slice( 1 ) );
        }
        return origDebug.apply( console, args );
    };

    auditLog( 'polling', 'Polling tracking installed (console.debug intercept)' );
}

// ── Module D: Store Tracking ───────────────────────────────────

function _installStoreTracking ()
{
    try
    {
        // Pinia stores are registered globally; we get them via the Pinia instance
        // attached to the app. We subscribe to each store's actions.
        const pinia = getCurrentInstance()?.appContext?.config?.globalProperties?.$pinia;
        if ( !pinia ) {
            auditWarn( 'store', 'Pinia instance not found — store tracking skipped' );
            return;
        }

        // pinia._s is the internal Map<string, StoreGeneric>
        const stores = pinia._s;
        if ( !stores || typeof stores.forEach !== 'function' )
        {
            auditWarn( 'store', 'Pinia store map not accessible' );
            return;
        }

        stores.forEach( ( store, id ) =>
        {
            store.$subscribe( ( mutation ) =>
            {
                _storeMutations++;
                auditLog( 'store', `${ id }.${ mutation.type }`, {
                    storeId: mutation.storeId,
                    events: mutation.events?.type ?? mutation.type,
                } );
            }, { detached: true } );
        } );

        auditLog( 'store', `Store tracking installed (${ stores.size } stores)` );
    }
    catch ( err )
    {
        auditWarn( 'store', `Store tracking failed: ${ err.message }` );
    }
}

// ── Module E: Timer / Leak Tracking ────────────────────────────

function _installTimerTracking ()
{
    const origSetTimeout = window.setTimeout;
    const origClearTimeout = window.clearTimeout;
    const origSetInterval = window.setInterval;
    const origClearInterval = window.clearInterval;

    /** @type {Set<number>} */
    const activeIds = new Set();

    window.setTimeout = ( fn, delay, ...args ) =>
    {
        const id = origSetTimeout( ( ...a ) =>
        {
            activeIds.delete( id );
            _activeTimers = activeIds.size;
            if ( typeof fn === 'function' ) fn( ...a );
        }, delay, ...args );
        activeIds.add( id );
        _activeTimers = activeIds.size;
        return id;
    };

    window.clearTimeout = ( id ) =>
    {
        activeIds.delete( id );
        _activeTimers = activeIds.size;
        origClearTimeout( id );
    };

    window.setInterval = ( fn, delay, ...args ) =>
    {
        const id = origSetInterval( fn, delay, ...args );
        activeIds.add( id );
        _activeTimers = activeIds.size;
        return id;
    };

    window.clearInterval = ( id ) =>
    {
        activeIds.delete( id );
        _activeTimers = activeIds.size;
        origClearInterval( id );
    };

    auditLog( 'timer', 'Timer tracking installed' );
}

// ── Main Composable ────────────────────────────────────────────

/**
 * Initialize the full audit system. Safe to call multiple times —
 * instrumentation is installed once (singleton).
 */
export function useFrontendAudit ()
{
    if ( !isAuditEnabled ) return;

    if ( !_installed )
    {
        _installed = true;

        const app = getCurrentInstance()?.appContext?.app;

        _installErrorTracking( app );
        _installEchoTracking();
        _installPollingTracking();
        _installStoreTracking();
        _installTimerTracking();

        auditLog( 'perf', 'Frontend audit system initialized' );

        // Expose stats getter on window
        window.__auditStats = getAuditStats;
    }

    onUnmounted( () =>
    {
        auditLog( 'perf', 'Audit host component unmounted' );
    } );
}
