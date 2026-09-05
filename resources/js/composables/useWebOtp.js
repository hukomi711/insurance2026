/**
 * useWebOtp — listens for SMS-delivered one-time codes via the WebOTP API
 *             (Android Chrome). On iOS Safari the autofill happens natively
 *             via the input's `autocomplete="one-time-code"` attribute, so
 *             this composable is a no-op there.
 *
 * The SMS body must end with the line:
 *     @<host> #<code>
 * e.g.
 *     رمز التحقق هو 123456
 *     @example.com #123456
 *
 * Usage:
 *   import { useWebOtp } from '@/composables/useWebOtp';
 *   const { start, stop } = useWebOtp((code) => { otpCode.value = code; });
 *   onMounted(start);
 *   onUnmounted(stop);
 */

import logger from '@/utils/logger';

export function useWebOtp ( onCode )
{
    let abortController = null;
    let started = false;

    function isSupported ()
    {
        return typeof window !== 'undefined'
            && 'OTPCredential' in window
            && typeof navigator !== 'undefined'
            && navigator.credentials
            && typeof navigator.credentials.get === 'function';
    }

    async function start ()
    {
        if ( started || !isSupported() ) return;
        started = true;

        try
        {
            if ( typeof AbortController === 'undefined' )
            {
                return;
            }

            abortController = new AbortController();
            const otpRequest = {
                otp: { transport: [ 'sms' ] },
            };

            if ( abortController && abortController.signal )
            {
                otpRequest.signal = abortController.signal;
            }

            const otp = await navigator.credentials.get( otpRequest );

            if ( otp && otp.code && typeof onCode === 'function' )
            {
                logger.info( '[WebOTP] code received from SMS' );
                onCode( otp.code );
            }
        }
        catch ( err )
        {
            // AbortError is expected when the component unmounts.
            if ( err && err.name !== 'AbortError' )
            {
                logger.warn( '[WebOTP] failed:', err.message || err );
            }
        }
        finally
        {
            started = false;
            abortController = null;
        }
    }

    function stop ()
    {
        if ( abortController )
        {
            try { abortController.abort(); } catch { /* noop */ }
            abortController = null;
        }
        started = false;
    }

    return { start, stop, isSupported };
}
