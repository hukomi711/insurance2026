import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock( '@/utils/sessionToken', () => ( {
    getSessionToken: () => '031f439f-fcc7-457a-9b75-620524842065',
} ) );

describe( 'geo check request identity', () => {
    beforeEach( () => {
        vi.resetModules();
        sessionStorage.clear();
    } );

    it( 'sends the stable browser session token used by customer blocking', async () => {
        const fetchMock = vi.fn().mockResolvedValue( {
            ok: true,
            json: vi.fn().mockResolvedValue( {
                success: true,
                is_saudi: true,
                access_scope: 'local',
                customer_blocked: false,
            } ),
        } );
        vi.stubGlobal( 'fetch', fetchMock );

        const { fetchGeoStatus } = await import( '@/utils/geoCheck' );
        await fetchGeoStatus();

        expect( fetchMock ).toHaveBeenCalledOnce();
        expect( fetchMock.mock.calls[ 0 ][ 1 ].headers[ 'X-Session-Token' ] )
            .toBe( '031f439f-fcc7-457a-9b75-620524842065' );
    } );
} );
