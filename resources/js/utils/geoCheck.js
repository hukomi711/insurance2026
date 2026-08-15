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
 * Returns { is_saudi: bool, access_scope: 'full'|'local'|'blog', customer_blocked: bool, country: string|null, country_ar: string|null }
 */
export async function fetchGeoStatus ( { forceRefresh = false } = {} )
{
    // Check sessionStorage cache first
    const cached = getCachedStatus();
    if ( cached && !forceRefresh ) return cached;

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
            customer_blocked: data.customer_blocked === true,
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
 * Does the current visitor have full (owner) access? (synchronous, uses cache only)
 * Returns false if no data available (Fail-Closed for admin).
 */
export function isAdminIp ()
{
    const cached = getCachedStatus();
    return cached ? cached.access_scope === 'full' : false;
}

/**
 * Is this visitor a confirmed Saudi user? (synchronous, cache-only)
 * Returns true ONLY when a real API result with is_saudi=true is cached.
 * Returns false if no cache exists (geo API failed or never called).
 *
 * Used by tracking to avoid recording foreign visitors on restricted pages.
 */
export function isSaudiConfirmed ()
{
    const cached = getCachedStatus();
    return cached !== null && cached.is_saudi === true;
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
    scheduleBackgroundRetry();
    return {
        is_saudi: true,
        access_scope: 'blog',
        customer_blocked: null,
        country: null,
        country_code: null,
        country_ar: null,
        is_fallback: true,
        fetched_at: Date.now(),
    };
}

// ─── Background Retry ──────────────────────────────────────────────
// When the geo API fails, schedule ONE background retry with a longer
// timeout. If it succeeds the result is cached so subsequent tracking
// and guards are accurate.
let bgRetryDone = false;

function scheduleBackgroundRetry ()
{
    if ( bgRetryDone ) return;
    bgRetryDone = true;

    setTimeout( async () =>
    {
        // Skip if a successful result was cached meanwhile
        if ( getCachedStatus() ) return;

        const controller = new AbortController();
        const tid = setTimeout( () => controller.abort(), 8000 );

        try
        {
            const response = await fetch( '/api/geo/check', {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            } );

            if ( !response.ok ) return;

            const data = await response.json();
            if ( !data.success ) return;

            const result = {
                is_saudi: Boolean( data.is_saudi ),
                access_scope: data.access_scope || 'blog',
                customer_blocked: data.customer_blocked === true,
                country: data.country || null,
                country_code: data.country_code || null,
                country_ar: data.country_ar || null,
                fetched_at: Date.now(),
            };

            try { sessionStorage.setItem( CACHE_KEY, JSON.stringify( result ) ); }
            catch { /* sessionStorage unavailable */ }
        }
        catch { /* Still failing — give up silently */ }
        finally { clearTimeout( tid ); }
    }, 3000 );
}
