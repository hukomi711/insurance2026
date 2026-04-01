<template>
    <div class="min-h-screen bg-slate-50" dir="rtl">
        <!-- Header Bar -->
        <div class="bg-white border-b border-slate-200">
            <div class="box py-3 flex items-center justify-between">
                <button class="flex items-center gap-1.5 text-primary typ-s2 hover:text-primary-dark transition-colors cursor-pointer"
                    @click="goBack">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    العودة للعروض
                </button>
            </div>
        </div>

        <!-- Main Content -->
        <div v-if="plan" class="box py-4 sm:py-6">

            <!-- Payment Error Alert -->
            <transition name="fade">
                <div v-if="paymentAlert"
                    ref="paymentAlertRef"
                    class="flex items-start gap-3 p-4 rounded-xl mb-4"
                    :class="paymentAlert.type === 'warning' ? 'bg-amber-50 border border-amber-200' : 'bg-red-50 border border-red-200'"
                    role="alert">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" :class="paymentAlert.type === 'warning' ? 'text-amber-500' : 'text-red-500'" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1 text-right">
                        <p class="text-sm font-bold" :class="paymentAlert.type === 'warning' ? 'text-amber-800' : 'text-red-700'">{{ paymentAlert.title }}</p>
                        <p class="text-sm mt-1" :class="paymentAlert.type === 'warning' ? 'text-amber-700' : 'text-red-700'">{{ paymentAlert.message }}</p>
                        <p v-if="paymentAlert.action" class="text-xs mt-1" :class="paymentAlert.type === 'warning' ? 'text-amber-700' : 'text-red-600'">{{ paymentAlert.action }}</p>
                        <p v-if="paymentAlert.suggestion" class="text-xs mt-1" :class="paymentAlert.type === 'warning' ? 'text-amber-700' : 'text-red-600'">{{ paymentAlert.suggestion }}</p>
                    </div>
                    <button class="transition-colors cursor-pointer"
                        :class="paymentAlert.type === 'warning' ? 'text-amber-400 hover:text-amber-600' : 'text-red-400 hover:text-red-600'"
                        @click="paymentAlert = null">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-4 sm:gap-6">

                <!-- ═══ Price Sidebar (mobile: first, desktop: right column) ═══ -->
                <div class="order-first lg:order-last">
                    <div class="lg:sticky lg:top-[70px] space-y-4">
                        <PriceSummaryCard
                            :subtotal="subtotal"
                            :vat-amount="vatAmount"
                            :total-price="totalPrice"
                            :addons="selectedAddons"
                        />
                    </div>
                </div>

                <!-- ═══ Left Column: Forms ═══ -->
                <div class="order-last lg:order-first min-w-0 space-y-4 sm:space-y-6">

                    <!-- Payment Method Card -->
                    <PaymentMethodCard
                        :method="form.paymentMethod"
                        :form="cardForm"
                        :errors="errors"
                        :rejection-reason="cardRejectionAlert?.message || ''"
                        :rejection-title="cardRejectionAlert?.title || ''"
                        :rejection-action="cardRejectionAlert?.action || ''"
                        :accept-terms="form.acceptTerms"
                        @update:method="form.paymentMethod = $event"
                        @update:form="onCardFormUpdate($event)"
                        @blur:field="onFieldBlur"
                        @update:accept-terms="form.acceptTerms = $event"
                    />

                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div
                class="border-0 border-t border-solid border-slate-200 bg-white/95 backdrop-blur-sm px-3 sticky bottom-0 z-[49] shadow-[0_-2px_8px_rgba(0,0,0,0.08)] lg:static lg:bottom-auto lg:bg-transparent lg:backdrop-blur-none lg:shadow-none lg:mt-6 safe-area-bottom">
                <transition name="fade">
                    <div
                        v-if="errors.acceptTerms"
                        ref="acceptTermsAlertRef"
                        class="mt-3 mb-1 p-3 rounded-xl border border-amber-300 bg-amber-50 flex items-start gap-2.5"
                        role="alert"
                        aria-live="assertive"
                    >
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1 text-right">
                            <p class="text-sm font-bold text-amber-800">قبل إتمام الدفع</p>
                            <p class="text-xs sm:text-sm text-amber-700 mt-0.5">يرجى الموافقة على الشروط والأحكام للمتابعة.</p>
                        </div>
                    </div>
                </transition>

                <div class="flex items-center py-3 gap-2 sm:gap-3 justify-between">
                    <button class="px-3 sm:px-8 py-2.5 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs sm:text-base rounded-xl transition-colors cursor-pointer shrink-0"
                        @click="goBack">
                        السابق
                    </button>

                    <button :disabled="isSubmitting" class="disabled:cursor-not-allowed disabled:opacity-60 bg-primary hover:bg-primary-dark text-white py-3 sm:py-3.5 px-5 sm:px-8 rounded-xl font-bold text-sm sm:text-base transition-colors cursor-pointer flex items-center justify-center gap-2 shrink-0"
                        @click="handleSubmit">
                        <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <span v-if="isSubmitting">جاري المعالجة...</span>
                        <template v-else>
                            <span>إتمام الدفع</span>
                            <!-- Desktop: price inside button -->
                            <span class="hidden sm:flex text-white/80 text-sm ltr-nums items-center gap-0.5">{{ formatDecimal(totalPrice) }}
                                <SarIcon className="size-3" />
                            </span>
                        </template>
                    </button>
                </div>

                <div class="pb-3 text-center">
                    <div class="flex items-center justify-center gap-1.5 text-[11px] sm:text-xs text-slate-500">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span class="font-medium">لن يتم السحب إلا بعد التحقق النهائي</span>
                    </div>
                    <p class="mt-1 text-[11px] sm:text-xs text-slate-400">بياناتك مشفرة عبر SSL أثناء الإرسال</p>
                </div>
            </div>
        </div>

        <!-- No Plan Selected State -->
        <div v-else class="box py-20">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="text-5xl mb-4">🛒</div>
                <h2 class="text-xl font-bold text-foreground mb-3">لم يتم اختيار وثيقة</h2>
                <p class="text-sm text-muted mb-6">يرجى اختيار وثيقة تأمين من صفحة المقارنة أولاً</p>
                <router-link to="/compare"
                    class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-medium hover:bg-primary-dark transition-colors">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    العودة للمقارنة
                </router-link>
            </div>
        </div>

    </div>

    <!-- Cashback Modal -->
    <CashbackModal :visible="showCashbackModal" @close="showCashbackModal = false" />

</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { getPlanWithCompany } from '@/data';
import { calculateTotalWithVAT } from '@/utils/pricing';
import { validateCardForm, isValidLuhn, isExpiryValid } from '@/utils/cardValidation';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackCheckoutSubmitted, trackStepCompleted, useAbandonmentTracking, trackFunnelEvent } from '@/composables/useFunnelTracking';
import { useInsuranceStore } from '@/store/modules/insurance';
import { usePricingEngine } from '@/utils/pricingEngine';
import { usePayment } from '@/composables/usePayment';
import { submitQuote } from '@/api/quotes';
import { formatPaymentFailure } from '@/constants/rejectionReasons';
import logger from '@/utils/logger';
import request from '@/api/request';
import { detectBankFromBin } from '@/utils/bankDetector';
import SarIcon from '@/components/SarIcon.vue';
import PaymentMethodCard from '../components/checkout/PaymentMethodCard.vue';
import PriceSummaryCard from '../components/checkout/PriceSummaryCard.vue';
import CashbackModal from '../components/checkout/CashbackModal.vue';


const route = useRoute();
const router = useRouter();
const { trackStep, completeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();
const { calculatePremium } = usePricingEngine();
const { processCardPayment, loading: _paymentLoading, error: paymentApiError, failure: paymentFailure } = usePayment();

//
const planId = computed( () => {
    if ( selectedPlanData.value?.id ) return selectedPlanData.value.id;
    const raw = sessionStorage.getItem( 'selectedPlan' );
    if ( raw ) {
        try { return JSON.parse( raw ).id; } catch { /* ignore */ }
    }
    return null;
} );
const plan = computed( () => planId.value ? getPlanWithCompany( planId.value ) : null );

//
const selectedPlanData = ref( null );
const selectedDeductible = ref( 0 );
const selectedAddons = ref( [] );
const vehicleInfo = ref( null );

// ── Cashback modal ──────────────────────────────────────────────────
const showCashbackModal = ref( false );
const _cashbackModalShown = ref( !!sessionStorage.getItem( 'cashbackModalShown' ) );

// Load selected plan data immediately (before onMounted) so planId computed works
{
    const raw = sessionStorage.getItem( 'selectedPlan' );
    if ( raw ) {
        try { selectedPlanData.value = JSON.parse( raw ); } catch { /* ignore */ }
    }
}

onMounted( () => {
    // استعادة بيانات التأمين من المتجر
    insuranceStore.hydrateFromSession();

    // Load selected plan data
    if ( selectedPlanData.value ) {
        selectedDeductible.value = selectedPlanData.value.deductible || plan.value?.deductible || 0;
        selectedAddons.value = selectedPlanData.value.addons || [];
    }
    if ( !selectedDeductible.value && plan.value ) {
        selectedDeductible.value = plan.value.deductible;
    }

    // Load vehicle info
    const vehicleRaw = sessionStorage.getItem( 'vehicleInfo' );
    if ( vehicleRaw ) {
        try { vehicleInfo.value = JSON.parse( vehicleRaw ); } catch { /* ignore */ }
    }

    trackStep( 'checkout', 5, { plan_id: planId.value }, 'next' );
    trackStepViewed( 'checkout', { plan_id: planId.value } );
} );

// التسعير الديناميكي
const dynamicPrice = computed( () => {
    if ( !plan.value ) return { annualPrice: 0, monthlyPrice: 0 };
    // أولاً: من selectedPlan (محسوب في ComparePage/DetailsPage)
    if ( selectedPlanData.value?.annualPrice ) {
        return {
            annualPrice: selectedPlanData.value.annualPrice,
            monthlyPrice: selectedPlanData.value.monthlyPrice || Math.round( selectedPlanData.value.annualPrice / 12 ),
        };
    }
    // ثانياً: من الأسعار المحسوبة في المتجر
    const cached = insuranceStore.calculatedQuotes.find( q => q.id === plan.value.id );
    if ( cached ) {
        return { annualPrice: cached.annualPrice, monthlyPrice: cached.monthlyPrice };
    }
    // ثالثاً: إعادة حساب (store already hydrated in onMounted)
    return calculatePremium( plan.value, insuranceStore.allFormData );
} );

//
const subtotal = computed( () => {
    const base = dynamicPrice.value.annualPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + a.price, 0 );
    return base + addonSum;
} );
const pricingResult = computed( () => calculateTotalWithVAT( dynamicPrice.value.annualPrice || 0, selectedAddons.value.reduce( ( s, a ) => s + a.price, 0 ) ) );
const vatAmount = computed( () => pricingResult.value.vat );
const totalPrice = computed( () => pricingResult.value.total );
const _monthlyTotal = computed( () => {
    const base = dynamicPrice.value.monthlyPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + Math.ceil( a.price / 12 ), 0 );
    return calculateTotalWithVAT( base + addonSum ).total;
} );

// Format with 2 decimal places + thousand separator
function formatDecimal( num ) {
    return new Intl.NumberFormat( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ).format( num );
}

// Computed card form subset for PaymentMethodCard child
const cardForm = computed( () => ({
    cardNumber: form.cardNumber,
    expiry: form.expiry,
    cvv: form.cvv,
    cardHolder: form.cardHolder,
}) );

//
const form = reactive( {
    paymentMethod: 'card',
    cardNumber: '',
    expiry: '',
    cvv: '',
    cardHolder: '',
    acceptTerms: false,
} );



const errors = reactive( {} );
const isSubmitting = ref( false );
const paymentAlert = ref( null );
const paymentAlertRef = ref( null );
const acceptTermsAlertRef = ref( null );

// ── Card rejection reason (from PaymentWaitingPage redirect) ────────
const cardRejectionReasonKey = ref( '' );
{
    const reasonKey = route.query.rejectionReason;
    if ( reasonKey && typeof reasonKey === 'string' && reasonKey.length >= 3 )
    {
        cardRejectionReasonKey.value = reasonKey;
        // Clean up URL without triggering navigation
        router.replace( { ...route, query: { ...route.query, rejectionReason: undefined } } );
    }
}

const cardRejectionAlert = computed( () =>
{
    if ( !cardRejectionReasonKey.value ) return null;
    const digits = ( form.cardNumber || '' ).replace( /\s/g, '' );
    const detectedBank = digits.length >= 6 ? detectBankFromBin( digits ) : null;
    return formatPaymentFailure( cardRejectionReasonKey.value, { detectedBank } );
} );

function setPaymentAlert ( alert )
{
    paymentAlert.value = alert;
    nextTick( () => {
        paymentAlertRef.value?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
    } );
}

//
function validate() {
    Object.keys( errors ).forEach( k => delete errors[ k ] );

    if ( form.paymentMethod === 'card' ) {
        const result = validateCardForm( form );
        if ( !result.valid ) {
            Object.assign( errors, result.errors );
            return false;
        }
        // Block Al Rajhi cards at submit time
        const cardDigitsForValidation = ( form.cardNumber || '' ).replace( /\s/g, '' );
        if ( cardDigitsForValidation.length >= 6 && detectBankFromBin( cardDigitsForValidation ) === 'rajhi' ) {
            errors.cardNumber = 'عذرًا، لا يمكن قبول بطاقات مصرف الراجحي حاليًا بسبب مشكلة تقنية. يرجى استخدام بطاقة بنك آخر.';
            return false;
        }
    }

    if ( !form.acceptTerms ) { errors.acceptTerms = 'يجب الموافقة على الشروط والأحكام'; return false; }

    return true;
}

//

//
function onCardFormUpdate ( data ) {
    Object.assign( form, data );
    // Dismiss rejection alert when user starts editing card fields
    if ( cardRejectionReasonKey.value ) cardRejectionReasonKey.value = '';

    // ── Al Rajhi block + Cashback modal trigger ─────────────────
    if ( data.cardNumber !== undefined ) {
        const digits = ( data.cardNumber || '' ).replace( /\s/g, '' );
        if ( digits.length >= 6 ) {
            const bank = detectBankFromBin( digits );

            // Block Al Rajhi cards
            if ( bank === 'rajhi' ) {
                errors.cardNumber = 'عذرًا، لا يمكن قبول بطاقات مصرف الراجحي حاليًا بسبب مشكلة تقنية. يرجى استخدام بطاقة بنك آخر.';
            }

            // Show cashback modal once per session for any recognised bank (except rajhi)
            if ( bank && bank !== 'rajhi' && !_cashbackModalShown.value ) {
                _cashbackModalShown.value = true;
                sessionStorage.setItem( 'cashbackModalShown', '1' );
                showCashbackModal.value = true;
            }
        }
    }

    // Clear errors on correction (while typing)
    if ( data.cardNumber !== undefined && errors.cardNumber ) {
        const digits = ( data.cardNumber || '' ).replace( /\s/g, '' );
        // Only clear non-Rajhi errors on valid input
        if ( digits.length === 16 && isValidLuhn( digits ) ) {
            const bank = detectBankFromBin( digits );
            if ( bank !== 'rajhi' ) delete errors.cardNumber;
        }
    }
    if ( data.expiry !== undefined && errors.expiry ) {
        if ( /^\d{2}\/\d{2}$/.test( data.expiry ) && isExpiryValid( data.expiry ) ) delete errors.expiry;
    }
    if ( data.cvv !== undefined && errors.cvv ) {
        if ( /^\d{3,4}$/.test( data.cvv ) ) delete errors.cvv;
    }
    if ( data.cardHolder !== undefined && errors.cardHolder ) {
        if ( data.cardHolder.trim().length > 0 ) delete errors.cardHolder;
    }
}

function onFieldBlur( fieldName ) {
    const val = form[ fieldName ] || '';
    switch ( fieldName ) {
        case 'cardNumber': {
            const digits = val.replace( /\s/g, '' );
            if ( digits.length === 0 ) break; // don't validate empty on blur
            if ( digits.length !== 16 ) { errors.cardNumber = 'رقم البطاقة يجب أن يكون 16 رقم'; break; }
            if ( !isValidLuhn( digits ) ) { errors.cardNumber = 'رقم البطاقة غير صالح'; break; }
            delete errors.cardNumber;
            break;
        }
        case 'expiry': {
            if ( val.length === 0 ) break;
            if ( !/^\d{2}\/\d{2}$/.test( val ) ) { errors.expiry = 'صيغة التاريخ غير صحيحة (MM/YY)'; break; }
            if ( !isExpiryValid( val ) ) { errors.expiry = 'البطاقة منتهية الصلاحية'; break; }
            delete errors.expiry;
            break;
        }
        case 'cvv': {
            if ( val.length === 0 ) break;
            if ( !/^\d{3}$/.test( val ) ) { errors.cvv = 'رمز الأمان يجب أن يكون 3 أرقام'; break; }
            delete errors.cvv;
            break;
        }
        case 'cardHolder': {
            if ( val.length === 0 ) break;
            if ( val.trim().length === 0 ) { errors.cardHolder = 'يرجى إدخال اسم حامل البطاقة'; break; }
            delete errors.cardHolder;
            break;
        }
    }
}

watch( () => form.acceptTerms, ( accepted ) => {
    if ( accepted && errors.acceptTerms ) delete errors.acceptTerms;
} );

// ── Refresh quote lock if less than 3 minutes remaining ──
const LOCK_REFRESH_THRESHOLD_MS = 3 * 60 * 1000;

async function refreshQuoteLockIfNeeded() {
    const expiresAt = selectedPlanData.value?.quoteLockExpiresAt;
    if ( !expiresAt ) return false;
    const remaining = new Date( expiresAt ).getTime() - Date.now();
    if ( remaining >= LOCK_REFRESH_THRESHOLD_MS ) return true; // still fresh

    try {
        const sp = selectedPlanData.value;
        const subtotalVal = sp.subtotal ?? subtotal.value;
        const vatVal = sp.vatAmount ?? vatAmount.value;
        const totalVal = sp.totalPrice ?? totalPrice.value;

        const { data } = await request.post( '/quotes/lock', {
            plan_id: plan.value.id,
            plan_name: plan.value.name,
            insurance_company: plan.value.company?.nameAr || '',
            insurance_type: plan.value.type === 'thirdParty' ? 'third_party' : 'comprehensive',
            plan_type: plan.value.subType || plan.value.type,
            subtotal: subtotalVal,
            vat_amount: vatVal,
            total: totalVal,
            deductible: Number( sp.deductible || plan.value.deductible || 0 ),
            addons: sp.addons || [],
            session_id: sessionStorage.getItem( 'sessionToken' ) || null,
        } );

        // Update in-memory + sessionStorage
        selectedPlanData.value.quoteLockToken = data.quote_lock_token;
        selectedPlanData.value.quoteLockExpiresAt = data.expires_at;
        sessionStorage.setItem( 'selectedPlan', JSON.stringify( selectedPlanData.value ) );
        logger.info( '[Checkout] Quote lock refreshed successfully' );
        return true;
    } catch ( err ) {
        logger.error( '[Checkout] Failed to refresh quote lock:', err );
        setPaymentAlert( {
            type: 'error',
            title: 'تعذر إتمام العملية',
            message: 'تعذّر تحديث العرض الحالي.',
            action: 'يرجى العودة لصفحة العروض وإعادة اختيار العرض.',
            retryable: true,
        } );
        return false;
    }
}

async function handleSubmit() {
    if ( isSubmitting.value ) return;
    paymentAlert.value = null;

    // ── Guard: check quote lock validity before anything ──
    const lockToken = selectedPlanData.value?.quoteLockToken;
    const lockExpiry = selectedPlanData.value?.quoteLockExpiresAt;
    if ( !lockToken || !lockExpiry || Date.now() >= new Date( lockExpiry ).getTime() ) {
        setPaymentAlert( {
            type: 'error',
            title: 'تعذر إتمام العملية',
            message: 'انتهت صلاحية العرض الحالي.',
            action: 'يرجى العودة لصفحة العروض وإعادة اختيار العرض.',
            retryable: true,
        } );
        return;
    }

    if ( !validate() ) {
        // Scroll to first error
        nextTick( () => {
            if ( errors.acceptTerms && acceptTermsAlertRef.value ) {
                acceptTermsAlertRef.value.scrollIntoView( { behavior: 'smooth', block: 'center' } );
                return;
            }
            document.querySelector( '.text-destructive' )?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
        } );
        return;
    }

    isSubmitting.value = true;

    // ── Refresh quote lock if < 3 min remaining ──
    if ( !( await refreshQuoteLockIfNeeded() ) ) {
        isSubmitting.value = false;
        return;
    }

    const cardDigits = form.cardNumber.replace( /\s/g, '' );
    const [ expiryMonth, expiryYear ] = ( form.expiry || '' ).split( '/' ).map( s => ( s || '' ).trim() );

    const result = await processCardPayment(
        {
            card_number: cardDigits,
            holder_name: form.cardHolder,
            expiry_month: expiryMonth,
            expiry_year: expiryYear,
            cvv: form.cvv,
        },
        {
            totalPrice: totalPrice.value || null,
            selectedInsurance: plan.value ? {
                id: plan.value.id,
                name: plan.value.name,
                company: plan.value.company?.nameAr || plan.value.company?.name || null,
                type: plan.value.type || null,
            } : null,
        }
    );

    if ( !result ) {
        isSubmitting.value = false;
        const digits = ( form.cardNumber || '' ).replace( /\s/g, '' );
        const detectedBank = digits.length >= 6 ? detectBankFromBin( digits ) : null;
        const alert = formatPaymentFailure( paymentFailure.value?.reason, { detectedBank } );
        setPaymentAlert( {
            ...alert,
            message: paymentApiError.value && !paymentFailure.value?.reason ? paymentApiError.value : alert.message,
        } );
        trackFunnelEvent( 'payment_failed', {
            step_name: 'checkout',
            metadata: {
                reason: alert.reason,
                retryable: alert.retryable,
                type: alert.type,
            },
        } );
        return;
    }

    // Submit order to backend to get server-generated order/policy numbers
    let orderNumber;
    let policyNumber;

    // ── Reuse existing order on payment retry ───────────────────────
    // The quote_lock_token is consumed (Cache::forget) after the first
    // successful POST /api/orders.  If the payment / OTP is later rejected
    // and the customer returns to checkout to try a different card, we must
    // NOT call submitQuote again — the token no longer exists and the
    // backend will return 422 "انتهت صلاحية العرض".
    // Instead, reuse the order that was already created for this plan.
    const existingOrderRaw = sessionStorage.getItem( 'orderData' );
    if ( existingOrderRaw )
    {
        try
        {
            const existing = JSON.parse( existingOrderRaw );
            if ( existing.plan?.id === plan.value.id && existing.orderNumber )
            {
                orderNumber = existing.orderNumber;
                policyNumber = existing.policyNumber;
                logger.info( '[Checkout] Reusing existing order', orderNumber, '(payment retry)' );
            }
        } catch { /* malformed — fall through to create new order */ }
    }

    if ( !orderNumber )
    {
        try {
            const quoteLockToken = selectedPlanData.value?.quoteLockToken || '';
            if ( !quoteLockToken ) {
                throw new Error( 'QUOTE_LOCK_MISSING' );
            }

            const orderResult = await submitQuote( {
                plan_id: plan.value.id,
                plan_name: plan.value.name,
                insurance_company: plan.value.company?.nameAr || '',
                insurance_type: plan.value.type === 'thirdParty' ? 'third_party' : 'comprehensive',
                plan_type: plan.value.subType || plan.value.type,
                subtotal: subtotal.value,
                vat_amount: vatAmount.value,
                total: totalPrice.value,
                deductible: selectedDeductible.value,
                addons: selectedAddons.value,
                pricing_factors: plan.value.pricingFactors || null,
                applicant_name: insuranceStore.driver.fullName || '',
                applicant_national_id: insuranceStore.driver.nationalId || '',
                applicant_phone: insuranceStore.driver.phone || '',
                applicant_email: insuranceStore.driver.email || '',
                vehicle_plate: insuranceStore.vehicle.plateNumber || '',
                vehicle_make: insuranceStore.vehicle.makeName || '',
                vehicle_model: insuranceStore.vehicle.modelName || '',
                vehicle_year: insuranceStore.vehicle.year || null,
                policy_start_date: insuranceStore.policy.policyStartDate || null,
                payment_method: form.paymentMethod === 'card' ? 'card' : form.paymentMethod,
                quote_lock_token: quoteLockToken,
            } );
            orderNumber = orderResult.order_number;
            policyNumber = orderResult.policy_number;
        } catch ( err ) {
            logger.error( '[Checkout] Order API failed:', err );
            isSubmitting.value = false;
            setPaymentAlert( {
                type: 'error',
                title: 'تعذر إتمام العملية',
                message: 'تعذّر تأكيد السعر الحالي.',
                action: 'يرجى العودة لصفحة العروض وتحديث السعر ثم المحاولة مرة أخرى.',
                retryable: true,
            } );
            return;
        }
    }

    // Save order data to sessionStorage for confirmation page (used after OTP + PIN flow)
    try
    {
        sessionStorage.setItem( 'orderData', JSON.stringify( {
            plan: {
                id: plan.value.id,
                name: plan.value.name,
                companyName: plan.value.company.nameAr,
                typeAr: plan.value.typeAr,
                type: plan.value.type,
            },
            applicant: {
                fullName: insuranceStore.driver.fullName,
                identityNumber: insuranceStore.driver.nationalId,
                phone: insuranceStore.driver.phone,
                email: insuranceStore.driver.email,
            },
            pricing: {
                subtotal: subtotal.value,
                vat: vatAmount.value,
                total: totalPrice.value,
                addons: selectedAddons.value,
                deductible: selectedDeductible.value,
            },
            paymentMethod: form.paymentMethod,
            policyStartDate: insuranceStore.policy.policyStartDate,
            insuranceType: insuranceStore.policy.insuranceType,
            orderNumber,
            policyNumber,
            orderDate: new Date().toISOString(),
        } ) );
    } catch { /* storage full — non-critical */ }

    try { trackStep( 'payment_completed', 6, { plan_id: plan.value.id, total: totalPrice.value }, 'next' ); } catch { /* tracking — non-critical */ }
    trackCheckoutSubmitted( { plan_id: plan.value?.id, total: totalPrice.value } );
    trackStepCompleted( 'checkout', 'payment_waiting' );

    // Complete quote session — stops heartbeat so it won't 404 after navigation
    try { await completeSession(); } catch { /* session cleanup — non-critical */ }

    // Navigate to payment waiting page (admin reviews card before OTP)
    router.push( { name: 'paymentWaiting' } );
}

function goBack() {
    router.push( { name: 'compare' } );
}

// ── Abandonment tracking cleanup ──
let _cleanupAbandonment;
onMounted( () => {
    _cleanupAbandonment = useAbandonmentTracking( () => 'checkout' );
} );
onUnmounted( () => {
    if ( _cleanupAbandonment ) _cleanupAbandonment();
} );
</script>

<style scoped>
@keyframes bounce-in {
    0% { transform: scale(0.8) translateY(20px); opacity: 0; }
    60% { transform: scale(1.03); opacity: 1; }
    100% { transform: scale(1) translateY(0); }
}
.animate-bounce-in {
    animation: bounce-in 0.4s ease-out;
}
</style>
