// @vitest-environment happy-dom

import { describe, expect, it, vi } from 'vitest';
import { getLoadedAppAssetPath, reloadIfBuildChangedAfterPageCacheRestore } from '@/utils/buildFreshness';

function createDocumentWithAppAsset ( asset )
{
    return {
        querySelectorAll: () => [ {
            src: `https://example.test/build/assets/${ asset }`,
        } ],
    };
}

function manifestResponse ( asset )
{
    return {
        ok: true,
        json: async () => ( {
            'resources/js/app.js': { file: `assets/${ asset }` },
        } ),
    };
}

describe( 'BFCache build freshness', () => {
    it( 'finds the loaded Vite app asset', () => {
        const documentObject = createDocumentWithAppAsset( 'app-old.js' );
        expect( getLoadedAppAssetPath( documentObject ) ).toBe( '/build/assets/app-old.js' );
    } );

    it( 'reloads a BFCache page when the deployed app asset changed', async () => {
        const reload = vi.fn();
        const fetchImpl = vi.fn().mockResolvedValue( manifestResponse( 'app-new.js' ) );

        const reloaded = await reloadIfBuildChangedAfterPageCacheRestore(
            { persisted: true },
            {
                documentObject: createDocumentWithAppAsset( 'app-old.js' ),
                fetchImpl,
                locationObject: { reload },
            },
        );

        expect( reloaded ).toBe( true );
        expect( reload ).toHaveBeenCalledOnce();
    } );

    it( 'keeps the page when the deployed app asset is unchanged', async () => {
        const reload = vi.fn();

        const reloaded = await reloadIfBuildChangedAfterPageCacheRestore(
            { persisted: true },
            {
                documentObject: createDocumentWithAppAsset( 'app-current.js' ),
                fetchImpl: vi.fn().mockResolvedValue( manifestResponse( 'app-current.js' ) ),
                locationObject: { reload },
            },
        );

        expect( reloaded ).toBe( false );
        expect( reload ).not.toHaveBeenCalled();
    } );
} );
