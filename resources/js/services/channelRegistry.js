/**
 * channelRegistry.js — centralized Echo channel subscription manager.
 *
 * Prevents duplicate subscriptions, tracks active channels, and
 * provides bulk leave. Works with the singleton Echo from echo.js.
 *
 * Usage:
 *   import { subscribe, leave, leaveAll } from '@/services/channelRegistry';
 *
 *   // Subscribe (returns the channel object, warns on duplicate)
 *   const ch = await subscribe('dashboard');
 *
 *   // Leave a specific channel
 *   leave('dashboard');
 *
 *   // Leave all channels owned by a scope
 *   leaveAll('dashboardHome');
 */

import logger from '@/utils/logger';
import { getEcho } from '@/services/echo';

/** @type {Map<string, { channel: object, scope: string, ts: number }>} */
const _registry = new Map();

/**
 * Subscribe to a private Echo channel. Returns the channel object.
 * Warns and returns the existing channel if already subscribed.
 *
 * @param {string} name    — channel name (e.g. 'dashboard', 'admin.otp')
 * @param {string} [scope] — owner scope for bulk leaveAll (e.g. 'dashboardHome')
 * @returns {Promise<object|null>} the Echo channel, or null if Echo unavailable
 */
export async function subscribe ( name, scope = 'default' )
{
    if ( _registry.has( name ) )
    {
        logger.warn( `[ChannelRegistry] Already subscribed to "${ name }" — returning existing channel` );
        return _registry.get( name ).channel;
    }

    const echo = await getEcho();
    if ( !echo ) return null;

    const channel = echo.private( name );
    _registry.set( name, { channel, scope, ts: Date.now() } );
    logger.debug( `[ChannelRegistry] Subscribed: ${ name } (scope=${ scope }, total=${ _registry.size })` );
    return channel;
}

/**
 * Leave a single channel and remove from registry.
 * @param {string} name
 */
export function leave ( name )
{
    const entry = _registry.get( name );
    if ( !entry ) return;

    _registry.delete( name );

    getEcho().then( ( echo ) =>
    {
        if ( echo )
        {
            try { echo.leave( name ); } catch { /* safe */ }
        }
    } );

    logger.debug( `[ChannelRegistry] Left: ${ name } (total=${ _registry.size })` );
}

/**
 * Leave all channels that belong to a given scope.
 * If no scope is provided, leaves ALL channels.
 * @param {string} [scope]
 */
export function leaveAll ( scope )
{
    const toLeave = [];
    for ( const [ name, entry ] of _registry )
    {
        if ( !scope || entry.scope === scope )
        {
            toLeave.push( name );
        }
    }

    if ( toLeave.length === 0 ) return;

    getEcho().then( ( echo ) =>
    {
        for ( const name of toLeave )
        {
            _registry.delete( name );
            if ( echo )
            {
                try { echo.leave( name ); } catch { /* safe */ }
            }
        }
        logger.debug( `[ChannelRegistry] Left ${ toLeave.length } channels (scope=${ scope || 'all' }, remaining=${ _registry.size })` );
    } );
}
