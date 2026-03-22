/**
 * Composable for injecting JSON-LD structured data into <head>.
 * Tracks injected scripts for cleanup on component unmount (SPA navigation).
 */
export function useJsonLd ()
{
    const injectedIds = [];

    function inject ( id, schema )
    {
        // Remove existing script with same id to prevent duplicates
        const existing = document.getElementById( id );
        if ( existing ) existing.remove();

        const script = document.createElement( 'script' );
        script.type = 'application/ld+json';
        script.id = id;
        script.textContent = JSON.stringify( schema );
        document.head.appendChild( script );
        injectedIds.push( id );
    }

    function cleanup ()
    {
        for ( const id of injectedIds )
        {
            const el = document.getElementById( id );
            if ( el ) el.remove();
        }
        injectedIds.length = 0;
    }

    return { inject, cleanup };
}
