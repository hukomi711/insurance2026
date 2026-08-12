// @vitest-environment happy-dom

import { afterAll, beforeEach, describe, expect, it, vi } from 'vitest';

const pusher = vi.hoisted( () => ( {
    connection: { state: 'connected' },
    disconnect: vi.fn(),
    connect: vi.fn(),
} ) );

vi.mock( 'laravel-echo', () => ( {
    default: class MockEcho {
        constructor () {
            this.connector = { pusher };
        }

        disconnect () {}
    },
} ) );

vi.mock( 'pusher-js', () => ( { default: class MockPusher {} } ) );

function pageTransitionEvent ( type, persisted ) {
    const event = new Event( type );
    Object.defineProperty( event, 'persisted', { value: persisted } );
    return event;
}

describe( 'Echo BFCache lifecycle', () => {
    beforeEach( () => {
        pusher.disconnect.mockClear();
        pusher.connect.mockClear();
        pusher.connection.state = 'connected';
    } );

    afterAll( async () => {
        const { destroyEcho } = await import( '@/services/echo' );
        destroyEcho();
        window.history.replaceState( {}, '', '/' );
    } );

    it( 'disconnects on pageswap before pagehide and reconnects after restore', async () => {
        window.history.replaceState( {}, '', '/dashboard' );

        const {
            getEcho,
            isEchoPageLifecycleErrorExpected,
            isEchoSuspendedForPageCache,
        } = await import( '@/services/echo' );
        const echo = await getEcho();

        expect( echo ).not.toBeNull();

        window.dispatchEvent( new Event( 'pageswap' ) );
        window.dispatchEvent( pageTransitionEvent( 'pagehide', true ) );

        // pageswap + pagehide must remain idempotent.
        expect( pusher.disconnect ).toHaveBeenCalledOnce();
        expect( isEchoSuspendedForPageCache() ).toBe( true );

        pusher.connection.state = 'disconnected';
        window.dispatchEvent( pageTransitionEvent( 'pageshow', true ) );

        await vi.waitFor( () => expect( pusher.connect ).toHaveBeenCalledOnce() );
        expect( isEchoSuspendedForPageCache() ).toBe( false );
        expect( isEchoPageLifecycleErrorExpected() ).toBe( true );
    } );

    it( 'disconnects on pagehide even when the page is not persisted', async () => {
        const { isEchoSuspendedForPageCache } = await import( '@/services/echo' );

        window.dispatchEvent( pageTransitionEvent( 'pagehide', false ) );

        expect( pusher.disconnect ).toHaveBeenCalledOnce();
        expect( isEchoSuspendedForPageCache() ).toBe( true );
    } );
} );
