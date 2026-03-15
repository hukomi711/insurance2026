/**
 * Session Token Utility
 *
 * Generates a unique browser token (UUID v4) on first access and
 * persists it in localStorage. This token is sent with every API request
 * as `X-Session-Token` header to uniquely identify the browser,
 * preventing data collisions when multiple users share the same IP (e.g.
 * corporate NAT, mobile CGNAT, shared WiFi).
 *
 * The token persists across tabs and browser restarts so that returning
 * visitors are recognised as the same customer without creating duplicates.
 */

const STORAGE_KEY = "customer_session_token";

/**
 * Generate a UUID v4 using the crypto API.
 * Falls back to a Math.random()-based UUID if crypto is unavailable.
 */
function generateUUID ()
{
    if ( typeof crypto !== "undefined" && crypto.randomUUID )
    {
        return crypto.randomUUID();
    }

    // Fallback for older browsers
    return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace( /[xy]/g, ( c ) =>
    {
        const r = ( Math.random() * 16 ) | 0;
        const v = c === "x" ? r : ( r & 0x3 ) | 0x8;
        return v.toString( 16 );
    } );
}

/**
 * Get or create the session token for this browser session.
 * @returns {string} UUID session token
 */
export function getSessionToken ()
{
    // Use localStorage for persistence across tabs and browser restarts.
    // Prevents duplicate customer records when user opens a new tab or revisits.
    let token = localStorage.getItem( STORAGE_KEY );
    if ( !token )
    {
        // Migrate from sessionStorage if it exists (one-time transition)
        token = sessionStorage.getItem( STORAGE_KEY );
        if ( token )
        {
            localStorage.setItem( STORAGE_KEY, token );
            sessionStorage.removeItem( STORAGE_KEY );
        } else
        {
            token = generateUUID();
            localStorage.setItem( STORAGE_KEY, token );
        }
    }
    return token;
}
