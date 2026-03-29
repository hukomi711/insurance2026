/**
 * Dev-only audit logger — ring-buffer event collector for WS, polling, store, timer, error, perf.
 *
 * Active when import.meta.env.DEV or VITE_AUDIT=true.
 * Exposes window.__audit (entries) and window.__auditDump() (JSON download).
 */

const ENABLED = import.meta.env.DEV || import.meta.env.VITE_AUDIT === 'true';
const MAX_ENTRIES = 500;
const PREFIX = '[Audit]';

const CATEGORIES = /** @type {const} */ ( ['ws', 'polling', 'store', 'timer', 'error', 'perf'] );

/** @typedef {'ws'|'polling'|'store'|'timer'|'error'|'perf'} AuditCategory */

/** @type {{ ts: number, cat: AuditCategory, level: string, msg: string, data?: any }[]} */
const entries = [];

/** Counters keyed by category for quick summary. */
const counters = Object.fromEntries( CATEGORIES.map( c => [c, 0] ) );

function _push ( level, cat, msg, data )
{
    if ( !ENABLED ) return;
    const entry = { ts: Date.now(), cat, level, msg, data };
    entries.push( entry );
    if ( entries.length > MAX_ENTRIES ) entries.shift();
    counters[cat] = ( counters[cat] || 0 ) + 1;

    const style = level === 'error' ? 'color:#ef4444'
        : level === 'warn'  ? 'color:#f59e0b'
            : 'color:#6b7280';
    console.debug( `%c${PREFIX} [${cat}] ${msg}`, style, data ?? '' );
}

/** Log an audit entry. */
export function auditLog ( cat, msg, data )  { _push( 'info', cat, msg, data ); }

/** Log an audit warning. */
export function auditWarn ( cat, msg, data ) { _push( 'warn', cat, msg, data ); }

/** Log an audit error. */
export function auditError ( cat, msg, data ) { _push( 'error', cat, msg, data ); }

/** Return current entries array (readonly reference). */
export function getEntries () { return entries; }

/** Return counters snapshot. */
export function getCounters () { return { ...counters }; }

/** Reset all entries and counters. */
export function resetAudit ()
{
    entries.length = 0;
    CATEGORIES.forEach( c => { counters[c] = 0; } );
}

/** Download entries as JSON file. */
function _dumpToFile ()
{
    const blob = new Blob( [JSON.stringify( entries, null, 2 )], { type: 'application/json' } );
    const url = URL.createObjectURL( blob );
    const a = document.createElement( 'a' );
    a.href = url;
    a.download = `audit-${new Date().toISOString().slice( 0, 19 ).replace( /:/g, '-' )}.json`;
    a.click();
    URL.revokeObjectURL( url );
}

/** Whether the audit system is active. */
export const isAuditEnabled = ENABLED;

// Expose on window in dev
if ( ENABLED && typeof window !== 'undefined' )
{
    window.__audit = entries;
    window.__auditCounters = counters;
    window.__auditDump = _dumpToFile;
    window.__auditReset = resetAudit;
}
