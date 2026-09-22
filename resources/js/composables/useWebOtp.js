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
 * ⚠️ Security note: This is suitable only for OTP codes issued by your system
 *    for phone number verification. Do NOT use this to capture banking OTP or
 *    3-D Secure codes and send them to an admin panel. Banking authentication
 *    must occur within the bank's interface or an authorized payment provider.
 *
 * Usage:
 *   import { useWebOtp } from '@/composables/useWebOtp';
 *   const { start, stop, state } = useWebOtp((code) => { otpCode.value = code; });
 *   onMounted(start);
 *   onUnmounted(stop);
 */

import { ref } from 'vue';
import logger from '@/utils/logger';

export function useWebOtp (onCode, {
    timeout = 120_000,
    pattern = /^\d{4,8}$/,
} = {}) {
    let activeController = null;
    let requestId = 0;

    const state = ref('idle');
    const error = ref('');

    function isSupported () {
        return typeof window !== 'undefined'
            && window.isSecureContext
            && 'OTPCredential' in window
            && typeof navigator !== 'undefined'
            && typeof navigator.credentials?.get === 'function'
            && typeof AbortController !== 'undefined';
    }

    function stop () {
        requestId += 1;

        const controller = activeController;
        activeController = null;

        if (controller && !controller.signal.aborted) {
            controller.abort();
        }

        if (state.value !== 'received') {
            state.value = 'idle';
        }
    }

    async function start () {
        if (activeController) return;

        state.value = 'idle';
        error.value = '';

        if (!isSupported()) {
            logger.debug('[WebOTP] unavailable');
            return;
        }

        const currentRequestId = ++requestId;
        const controller = new AbortController();
        activeController = controller;
        state.value = 'listening';

        const timeoutId = window.setTimeout(() => {
            controller.abort();
        }, timeout);

        try {
            const credential = await navigator.credentials.get({
                otp: { transport: ['sms'] },
                signal: controller.signal,
            });

            if (currentRequestId !== requestId) return;

            const code = credential?.code;

            if (typeof code !== 'string' || !pattern.test(code)) {
                state.value = 'error';
                error.value = 'Invalid OTP format';
                return;
            }

            state.value = 'received';

            if (typeof onCode === 'function') {
                try {
                    await onCode(code);
                } catch (callbackError) {
                    logger.warn(
                        '[WebOTP] callback failed:',
                        callbackError?.message || callbackError,
                    );
                }
            }
        } catch (err) {
            if (currentRequestId !== requestId) return;

            if (err?.name === 'AbortError') {
                state.value = 'idle';
                return;
            }

            logger.warn('[WebOTP] request failed:', err?.message || err);
            state.value = 'error';
            error.value = 'تعذر قراءة رمز التحقق تلقائيًا';
        } finally {
            window.clearTimeout(timeoutId);

            if (currentRequestId === requestId) {
                activeController = null;

                if (state.value === 'listening') {
                    state.value = 'idle';
                }
            }
        }
    }

    function reset () {
        stop();
        state.value = 'idle';
        error.value = '';
    }

    return {
        start,
        stop,
        reset,
        isSupported,
        state,
        error,
    };
}
