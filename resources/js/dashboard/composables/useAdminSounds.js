/**
 * useAdminSounds — WAV-based notification sounds for admin dashboard.
 *
 * Place audio files at:
 *   public/sounds/new-data.wav  — generic new customer data
 *   public/sounds/payment.wav   — new payment card submitted
 *   public/sounds/otp.wav       — OTP / verification code submitted
 *
 * Browsers block audio playback before any user gesture. The first call to
 * `play()` will throw a NotAllowedError and is caught silently. After the
 * admin has clicked anywhere on the page, subsequent calls work normally.
 * Call `enableSounds()` from a click handler to "warm up" the Audio objects.
 */

import logger from '@/utils/logger';

const sounds = {
    newData: new window.Audio( '/sounds/new-data.wav' ),
    payment: new window.Audio( '/sounds/payment.wav' ),
    otp:     new window.Audio( '/sounds/otp.wav' ),
};

Object.values( sounds ).forEach( ( s ) =>
{
    s.preload = 'none';
    s.volume  = 0.8;
} );

let unlocked = false;
let blockedLogged = false;
const BLOCKED_LOG_KEY = '__insuranceAdminSoundsBlockedLogged';

/**
 * Unlock audio on the first user gesture.
 * Idempotent — safe to attach to a one-shot click listener.
 */
export function enableSounds ()
{
    if ( unlocked ) return;
    unlocked = true;
    // Touch each Audio element with a muted play+pause to unblock autoplay.
    Object.values( sounds ).forEach( ( s ) =>
    {
        s.muted = true;
        s.play()
            .then( () =>
            {
                s.pause();
                s.currentTime = 0;
                s.muted = false;
            } )
            .catch( () =>
            {
                s.muted = false;
            } );
    } );
}

async function play ( type )
{
    if ( !unlocked )
    {
        if ( !blockedLogged && !window[ BLOCKED_LOG_KEY ] )
        {
            blockedLogged = true;
            window[ BLOCKED_LOG_KEY ] = true;
            logger.debug( '[AdminSounds] blocked until user interaction' );
        }
        return;
    }

    const sound = sounds[ type ] || sounds.newData;
    try
    {
        sound.currentTime = 0;
        await sound.play();
    } catch
    {
        logger.debug( `[AdminSounds] failed to play ${ type } sound` );
    }
}

export function playNewData () { return play( 'newData' ); }
export function playPayment () { return play( 'payment' ); }
export function playOtp ()     { return play( 'otp' ); }

export function useAdminSounds ()
{
    return { playNewData, playPayment, playOtp, enableSounds };
}
