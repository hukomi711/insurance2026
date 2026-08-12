const APP_MANIFEST_KEY = 'resources/js/app.js';
const APP_ASSET_PATTERN = /\/build\/assets\/app-[^/]+\.js$/;

export function getLoadedAppAssetPath ( documentObject = document )
{
    for ( const script of documentObject.querySelectorAll( 'script[type="module"][src]' ) )
    {
        try
        {
            const path = new URL( script.src, window.location.origin ).pathname;
            if ( APP_ASSET_PATTERN.test( path ) ) return path;
        } catch
        {
            // Ignore malformed/non-URL script sources.
        }
    }

    return null;
}

export async function reloadIfBuildChangedAfterPageCacheRestore (
    event,
    {
        documentObject = document,
        fetchImpl = window.fetch.bind( window ),
        locationObject = window.location,
    } = {},
)
{
    if ( !event?.persisted ) return false;

    const loadedPath = getLoadedAppAssetPath( documentObject );
    if ( !loadedPath ) return false;

    const response = await fetchImpl( '/build/manifest.json', {
        cache: 'no-store',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    } );

    if ( !response.ok ) return false;

    const manifest = await response.json();
    const currentFile = manifest?.[ APP_MANIFEST_KEY ]?.file;
    if ( !currentFile ) return false;

    const currentPath = `/build/${ String( currentFile ).replace( /^\/+/, '' ) }`;
    if ( loadedPath === currentPath ) return false;

    locationObject.reload();
    return true;
}
