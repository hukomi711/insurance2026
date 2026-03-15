/**
 * geoCheck.js — Geo-location access control for SPA
 *
 * Calls GET /api/geo/check on first load, caches result in sessionStorage
 * for 30 minutes to avoid excessive API calls during SPA navigation.
 *
 * Fail-Open: if the API call fails, assume Saudi (don't break the site).
 */

const CACHE_KEY = 'geo_status';
const CACHE_TTL = 30 * 60 * 1000; // 30 minutes in milliseconds

// Shared in-flight promise — deduplicates concurrent calls so the
// prefetch in app.js and the router guard share a single network request.
let inflightPromise = null;

/**
 * Fetch geo status from API (or cached value).
 * Returns { is_saudi: bool, access_scope: 'full'|'local'|'blog', country: string|null, country_ar: string|null }
 */
export async function fetchGeoStatus ()
{
    // Check sessionStorage cache first
    const cached = getCachedStatus();
    if ( cached ) return cached;

    // Deduplicate concurrent fetches (prefetch + router guard)
    if ( inflightPromise ) return inflightPromise;

    inflightPromise = doFetchGeoStatus();
    try
    {
        return await inflightPromise;
    }
    finally
    {
        inflightPromise = null;
    }
}

async function doFetchGeoStatus ()
{
    // AbortController — 5s timeout prevents hanging geo checks from blocking all navigation
    const controller = new AbortController();
    const timeoutId = setTimeout( () => controller.abort(), 5000 );

    try
    {
        const response = await fetch( '/api/geo/check', {
            method: 'GET',
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal: controller.signal,
        } );

        if ( !response.ok )
        {
            // Fail-Open: server error → assume Saudi
            return getFailOpenResult();
        }

        const data = await response.json();

        if ( !data.success )
        {
            return getFailOpenResult();
        }

        const result = {
            is_saudi: Boolean( data.is_saudi ),
            access_scope: data.access_scope || 'blog',
            country: data.country || null,
            country_code: data.country_code || null,
            country_ar: data.country_ar || null,
            fetched_at: Date.now(),
        };

        // Cache in sessionStorage
        try
        {
            sessionStorage.setItem( CACHE_KEY, JSON.stringify( result ) );
        }
        catch
        {
            // sessionStorage not available or full — ignore
        }

        return result;
    }
    catch
    {
        // Network error or timeout → Fail-Open
        return getFailOpenResult();
    }
    finally
    {
        clearTimeout( timeoutId );
    }
}

/**
 * Is the current visitor from Saudi Arabia? (synchronous, uses cache only)
 * Returns true if no data available (Fail-Open).
 */
export function isSaudiVisitor ()
{
    const cached = getCachedStatus();
    return cached ? cached.is_saudi : true;
}

/**
 * Does the current visitor have full (owner) access? (synchronous, uses cache only)
 * Returns false if no data available (Fail-Closed for admin).
 */
export function isAdminIp ()
{
    const cached = getCachedStatus();
    return cached ? cached.access_scope === 'full' : false;
}

/**
 * Force clear geo cache (useful after VPN change, for testing, etc.)
 */
export function clearGeoCache ()
{
    try
    {
        sessionStorage.removeItem( CACHE_KEY );
    }
    catch
    {
        // ignore
    }
}

// ─── Internal helpers ───────────────────────────────────────────────

function getCachedStatus ()
{
    try
    {
        const raw = sessionStorage.getItem( CACHE_KEY );
        if ( !raw ) return null;

        const data = JSON.parse( raw );

        // Check TTL
        if ( Date.now() - data.fetched_at > CACHE_TTL )
        {
            sessionStorage.removeItem( CACHE_KEY );
            return null;
        }

        return data;
    }
    catch
    {
        return null;
    }
}

function getFailOpenResult ()
{
    return {
        is_saudi: true,
        access_scope: 'blog',
        country: null,
        country_code: null,
        country_ar: null,
        fetched_at: Date.now(),
    };
}
