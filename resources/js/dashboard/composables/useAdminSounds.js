/**
 * Shared notification-audio controller for the whole admin dashboard.
 *
 * Audio elements are created lazily after a user gesture, the preference is
 * shared across pages, and a short global cooldown prevents a WebSocket event
 * and its follow-up poll from playing the same alert twice.
 */

import { readonly, ref } from 'vue';
import logger from '@/utils/logger';

const STORAGE_KEY = 'admin-notification-sounds-enabled';
const SOUND_COOLDOWN_MS = 1_000;
const SOUND_SOURCES = {
    newData: '/sounds/new-data.mp3',
    payment: '/sounds/payment.mp3',
    otp: '/sounds/otp.mp3',
    quietNotification: '/sounds/quiet-notification-sound.mp3',
};

function loadPreference ()
{
    try
    {
        return window.localStorage.getItem( STORAGE_KEY ) !== 'false';
    }
    catch
    {
        return true;
    }
}

const soundsEnabled = ref( typeof window !== 'undefined' ? loadPreference() : false );
const soundsReady = ref( false );

let sounds = null;
let unlockPromise = null;
let lastPlayedAt = 0;

function persistPreference ()
{
    try
    {
        window.localStorage.setItem( STORAGE_KEY, String( soundsEnabled.value ) );
    }
    catch
    {
        // Storage may be unavailable in private browsing; in-memory state works.
    }
}

function ensureSounds ()
{
    if ( sounds || typeof window === 'undefined' || typeof window.Audio !== 'function' )
    {
        return sounds;
    }

    sounds = Object.fromEntries(
        Object.entries( SOUND_SOURCES ).map( ( [ type, source ] ) =>
        {
            const audio = new window.Audio( source );
            audio.preload = 'none';
            audio.volume = 0.8;
            return [ type, audio ];
        } )
    );

    return sounds;
}

async function warmAudioElement ( audio )
{
    audio.muted = true;
    try
    {
        await Promise.resolve( audio.play() );
        audio.pause();
        audio.currentTime = 0;
        return true;
    }
    catch
    {
        return false;
    }
    finally
    {
        audio.muted = false;
    }
}

/** Unlock the current preference from a real browser gesture. */
export async function unlockSounds ()
{
    if ( !soundsEnabled.value ) return false;
    if ( soundsReady.value ) return true;
    if ( unlockPromise ) return unlockPromise;

    const audioElements = ensureSounds();
    if ( !audioElements ) return false;

    unlockPromise = Promise.all( Object.values( audioElements ).map( warmAudioElement ) )
        .then( results =>
        {
            soundsReady.value = results.every( Boolean );
            if ( !soundsReady.value )
            {
                logger.debug( '[AdminSounds] browser did not unlock every notification sound' );
            }
            return soundsReady.value;
        } )
        .finally( () =>
        {
            unlockPromise = null;
        } );

    return unlockPromise;
}

/** Explicitly enable the preference and attempt browser audio unlock. */
export async function enableSounds ()
{
    soundsEnabled.value = true;
    persistPreference();
    return unlockSounds();
}

export function disableSounds ()
{
    soundsEnabled.value = false;
    soundsReady.value = false;
    persistPreference();

    if ( sounds )
    {
        Object.values( sounds ).forEach( audio =>
        {
            audio.pause();
            audio.currentTime = 0;
        } );
    }
}

/** Enable/unlock when inactive, otherwise disable. */
export async function toggleSounds ()
{
    if ( soundsReady.value )
    {
        disableSounds();
        return false;
    }

    return enableSounds();
}

async function play ( type )
{
    if ( !soundsEnabled.value || !soundsReady.value ) return false;

    const now = Date.now();
    if ( now - lastPlayedAt < SOUND_COOLDOWN_MS ) return false;

    const sound = ensureSounds()?.[ type ] || ensureSounds()?.newData;
    if ( !sound ) return false;

    try
    {
        sound.currentTime = 0;
        await Promise.resolve( sound.play() );
        lastPlayedAt = now;
        return true;
    }
    catch
    {
        logger.debug( `[AdminSounds] failed to play ${ type } sound` );
        return false;
    }
}

export function playNewData () { return play( 'newData' ); }
export function playPayment () { return play( 'payment' ); }
export function playOtp ()     { return play( 'otp' ); }
export function playQuietNotification () { return play( 'quietNotification' ); }

export function useAdminSounds ()
{
    return {
        soundsEnabled: readonly( soundsEnabled ),
        soundsReady: readonly( soundsReady ),
        enableSounds,
        unlockSounds,
        disableSounds,
        toggleSounds,
        playNewData,
        playPayment,
        playOtp,
        playQuietNotification,
    };
}
