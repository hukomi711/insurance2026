import { ref, computed, onUnmounted } from 'vue';

/**
 * Composable for managing countdown timers.
 * Provides start/stop/reset and formatted display.
 *
 * @param {number} initialSeconds - Starting countdown value in seconds
 * @param {object} [options] - Optional configuration
 * @param {boolean} [options.autoStart=false] - Start timer on creation
 * @param {Function} [options.onComplete] - Callback when timer reaches 0
 * @returns {object} Timer state and controls
 */
export function useCountdownTimer ( initialSeconds, options = {} )
{
    const { autoStart = false, onComplete = null } = options;

    const remaining = ref( initialSeconds );
    const isRunning = ref( false );
    let intervalId = null;

    const formatted = computed( () =>
    {
        const mins = Math.floor( remaining.value / 60 );
        const secs = remaining.value % 60;
        if ( mins > 0 )
        {
            return `${ mins }:${ secs.toString().padStart( 2, '0' ) }`;
        }
        return `${ remaining.value } ثانية`;
    } );

    const isExpired = computed( () => remaining.value <= 0 );

    const start = ( seconds = null ) =>
    {
        if ( seconds !== null )
        {
            remaining.value = seconds;
        }
        stop();
        isRunning.value = true;
        intervalId = setInterval( () =>
        {
            if ( remaining.value > 0 )
            {
                remaining.value--;
            } else
            {
                stop();
                onComplete?.();
            }
        }, 1000 );
    };

    const stop = () =>
    {
        if ( intervalId )
        {
            clearInterval( intervalId );
            intervalId = null;
        }
        isRunning.value = false;
    };

    const reset = ( seconds = initialSeconds ) =>
    {
        stop();
        remaining.value = seconds;
    };

    const restart = ( seconds = initialSeconds ) =>
    {
        reset( seconds );
        start();
    };

    if ( autoStart )
    {
        start();
    }

    onUnmounted( () =>
    {
        stop();
    } );

    return {
        remaining,
        formatted,
        isRunning,
        isExpired,
        start,
        stop,
        reset,
        restart,
    };
}
