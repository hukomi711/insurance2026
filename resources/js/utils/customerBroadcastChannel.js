/**
 * Build the opaque customer channel name used by Laravel broadcast events.
 * The raw browser/session token must never be exposed as a channel name.
 */
export async function customerBroadcastChannel ( prefix, sessionId )
{
    const normalizedPrefix = String( prefix || '' ).trim();
    const normalizedSessionId = String( sessionId || '' ).trim();

    if ( !normalizedPrefix || !normalizedSessionId )
    {
        throw new Error( 'Customer broadcast channels require a prefix and session ID.' );
    }

    if ( !globalThis.crypto?.subtle || !globalThis.TextEncoder )
    {
        throw new Error( 'Secure customer channel hashing is unavailable.' );
    }

    const bytes = new globalThis.TextEncoder().encode( normalizedSessionId );
    const digest = await globalThis.crypto.subtle.digest( 'SHA-256', bytes );
    const hash = Array.from( new Uint8Array( digest ), byte => byte.toString( 16 ).padStart( 2, '0' ) ).join( '' );

    return `${ normalizedPrefix }.${ hash }`;
}
