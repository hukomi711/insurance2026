import { beforeEach, describe, expect, it, vi } from 'vitest';

vi.mock( '@/utils/logger', () => ( {
    default: { debug: vi.fn() },
} ) );

class MockAudio
{
    static instances = [];
    static failPlayback = false;

    constructor ( source )
    {
        this.source = source;
        this.currentTime = 0;
        this.muted = false;
        this.preload = '';
        this.volume = 1;
        this.play = vi.fn( () => MockAudio.failPlayback
            ? Promise.reject( new Error( 'autoplay blocked' ) )
            : Promise.resolve() );
        this.pause = vi.fn();
        MockAudio.instances.push( this );
    }
}

describe( 'shared admin notification sounds', () => {
    beforeEach( () => {
        vi.resetModules();
        MockAudio.instances = [];
        MockAudio.failPlayback = false;
        localStorage.clear();
        vi.stubGlobal( 'Audio', MockAudio );
        window.Audio = MockAudio;
    } );

    it( 'creates no audio elements before a user enables playback', async () => {
        await import( '@/dashboard/composables/useAdminSounds' );
        expect( MockAudio.instances ).toHaveLength( 0 );
    } );

    it( 'unlocks every sound and persists one shared preference', async () => {
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );

        await expect( sounds.enableSounds() ).resolves.toBe( true );

        expect( MockAudio.instances ).toHaveLength( 4 );
        expect( MockAudio.instances.every( audio => audio.play.mock.calls.length === 1 ) ).toBe( true );
        expect( localStorage.getItem( 'admin-notification-sounds-enabled' ) ).toBe( 'true' );
        expect( sounds.useAdminSounds().soundsReady.value ).toBe( true );
    } );

    it( 'disables playback across every dashboard consumer', async () => {
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );
        await sounds.enableSounds();
        sounds.disableSounds();

        await expect( sounds.playPayment() ).resolves.toBe( false );
        expect( localStorage.getItem( 'admin-notification-sounds-enabled' ) ).toBe( 'false' );
        expect( sounds.useAdminSounds().soundsReady.value ).toBe( false );
    } );

    it( 'suppresses duplicate alerts emitted by websocket and polling', async () => {
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );
        await sounds.enableSounds();
        const paymentAudio = MockAudio.instances.find( audio => audio.source.endsWith( 'payment.mp3' ) );

        await expect( sounds.playPayment() ).resolves.toBe( true );
        await expect( sounds.playPayment() ).resolves.toBe( false );

        expect( paymentAudio.play ).toHaveBeenCalledTimes( 2 );
    } );

    it( 'does not report audio as ready when the browser blocks playback', async () => {
        MockAudio.failPlayback = true;
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );

        await expect( sounds.enableSounds() ).resolves.toBe( false );
        expect( sounds.useAdminSounds().soundsReady.value ).toBe( false );
    } );

    it( 'provides playQuietNotification for visitor/reactivation alerts', async () => {
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );
        await sounds.enableSounds();

        const quietAudio = MockAudio.instances.find( audio => audio.source.endsWith( 'quiet-notification-sound.mp3' ) );
        expect( quietAudio ).toBeDefined();

        await expect( sounds.playQuietNotification() ).resolves.toBe( true );
        expect( quietAudio.play ).toHaveBeenCalled();
    } );

    it( 'supports all four notification sound types', async () => {
        const sounds = await import( '@/dashboard/composables/useAdminSounds' );
        await sounds.enableSounds();

        // Verify all 4 sound types are created with correct sources
        expect( MockAudio.instances ).toHaveLength( 4 );

        const soundSources = MockAudio.instances.map( audio => audio.source );
        expect( soundSources.some( src => src.endsWith( 'new-data.mp3' ) ) ).toBe( true );
        expect( soundSources.some( src => src.endsWith( 'payment.mp3' ) ) ).toBe( true );
        expect( soundSources.some( src => src.endsWith( 'otp.mp3' ) ) ).toBe( true );
        expect( soundSources.some( src => src.endsWith( 'quiet-notification-sound.mp3' ) ) ).toBe( true );
    } );
} );
