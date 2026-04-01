/**
 * useNotificationSounds — Web Audio API notification tones for dashboard.
 *
 * Four distinct sounds:
 *   - basic:     البيانات الأساسية  (soft two-note chime)
 *   - insurance: بيانات التأمين     (three-note ascending)
 *   - payment:   الدفع              (bright four-note fanfare)
 *   - newCard:   بطاقة جديدة مسجلة  (distinctive double-pulse alert)
 */

let audioCtx = null;
let enabled  = false;

function ensureContext ()
{
    if ( audioCtx ) return;
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    audioCtx = new AudioCtx();
}

/**
 * Must be called from a user-gesture handler (click, keydown) to unlock the AudioContext.
 * Idempotent — safe to call multiple times.
 */
export function enableSounds ()
{
    if ( enabled ) return;
    try
    {
        ensureContext();
        if ( audioCtx.state === 'suspended' ) audioCtx.resume();
        enabled = true;
    } catch { /* browser blocked — silent fallback */ }
}

function isReady ()
{
    if ( !enabled || !audioCtx ) return false;
    if ( audioCtx.state === 'suspended' ) audioCtx.resume();
    return true;
}

// ── Tone helpers ──────────────────────────────────────────

function playNotes ( notes, waveform = 'sine', volume = 0.25 )
{
    if ( !isReady() ) return;
    let t = audioCtx.currentTime;
    notes.forEach( ( { freq, dur } ) =>
    {
        const osc  = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect( gain );
        gain.connect( audioCtx.destination );
        osc.frequency.value = freq;
        osc.type = waveform;
        gain.gain.setValueAtTime( 0, t );
        gain.gain.linearRampToValueAtTime( volume, t + 0.01 );
        gain.gain.exponentialRampToValueAtTime( 0.001, t + dur );
        osc.start( t );
        osc.stop( t + dur );
        t += dur + 0.02;
    } );
}

// ── Public sounds ─────────────────────────────────────────

/** البيانات الأساسية — soft two-note chime (D5 → F#5) */
export function playBasicSound ()
{
    playNotes( [
        { freq: 587, dur: 0.12 },
        { freq: 740, dur: 0.18 },
    ], 'sine', 0.2 );
}

/** بيانات التأمين — three-note ascending (E5 → G#5 → B5) */
export function playInsuranceSound ()
{
    playNotes( [
        { freq: 659, dur: 0.1 },
        { freq: 831, dur: 0.1 },
        { freq: 988, dur: 0.16 },
    ], 'sine', 0.22 );
}

/** الدفع — bright four-note fanfare (C5 → E5 → G5 → C6) */
export function playPaymentSound ()
{
    playNotes( [
        { freq: 523, dur: 0.09 },
        { freq: 659, dur: 0.09 },
        { freq: 784, dur: 0.09 },
        { freq: 1047, dur: 0.2 },
    ], 'triangle', 0.25 );
}

/** بطاقة جديدة مسجلة — distinctive double-pulse alert */
export function playNewCardSound ()
{
    if ( !isReady() ) return;
    const t = audioCtx.currentTime;

    // Pulse 1: sharp beep
    [ 880, 1100 ].forEach( ( freq, i ) =>
    {
        const osc  = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect( gain );
        gain.connect( audioCtx.destination );
        osc.frequency.value = freq;
        osc.type = 'square';
        const start = t + i * 0.08;
        gain.gain.setValueAtTime( 0, start );
        gain.gain.linearRampToValueAtTime( 0.15, start + 0.01 );
        gain.gain.exponentialRampToValueAtTime( 0.001, start + 0.07 );
        osc.start( start );
        osc.stop( start + 0.07 );
    } );

    // Gap then Pulse 2: higher confirmation
    [ 1175, 1397 ].forEach( ( freq, i ) =>
    {
        const osc  = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.connect( gain );
        gain.connect( audioCtx.destination );
        osc.frequency.value = freq;
        osc.type = 'square';
        const start = t + 0.25 + i * 0.08;
        gain.gain.setValueAtTime( 0, start );
        gain.gain.linearRampToValueAtTime( 0.18, start + 0.01 );
        gain.gain.exponentialRampToValueAtTime( 0.001, start + 0.1 );
        osc.start( start );
        osc.stop( start + 0.1 );
    } );
}
