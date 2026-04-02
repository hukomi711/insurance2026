<template>
    <!-- ═══ Fullscreen Payment Gateway — No header/footer, no back button ═══ -->
    <div class="min-h-screen relative" dir="rtl">
        <!-- Background -->
        <img :src="bannerBg" alt="" class="absolute inset-0 w-full h-full object-cover" loading="eager" width="1920" height="1080" />
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>

        <!-- Content -->
        <div class="relative z-10 min-h-screen flex flex-col justify-center px-4 sm:px-6 py-6 sm:py-8">
            <div class="w-full max-w-lg mx-auto">

                <!-- Header -->
                <header class="text-center mb-6">
                    <div class="flex justify-center mb-3">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-primary-dark flex items-center justify-center shadow-lg">
                            <svg class="size-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                    </div>
                    <h1 class="text-2xl md:text-3xl font-bold text-white drop-shadow-md">ادفع الآن</h1>
                    <p class="text-white/80 text-sm md:text-base mt-2">
                        أدخل بيانات بطاقتك لإتمام عملية الدفع بأمان
                    </p>
                    <!-- Price badge -->
                    <div v-if="totalPrice" class="mt-3 inline-flex items-center gap-2 bg-white/15 backdrop-blur-sm rounded-full px-5 py-2">
                        <span class="text-white/80 text-sm font-medium">المبلغ المطلوب:</span>
                        <span class="text-white text-lg font-extrabold ltr-nums">{{ formatDecimal( totalPrice ) }}</span>
                        <SarIcon className="size-3.5 text-white/90" />
                    </div>
                </header>

                <!-- Payment Error Alert -->
                <transition name="fade">
                    <div v-if="paymentAlert" ref="paymentAlertRef"
                        class="mb-4 flex items-start gap-3 p-4 rounded-xl"
                        :class="paymentAlert.type === 'warning' ? 'bg-amber-50 border border-amber-200' : 'bg-red-50 border border-red-200'"
                        role="alert">
                        <svg class="w-5 h-5 shrink-0 mt-0.5"
                            :class="paymentAlert.type === 'warning' ? 'text-amber-500' : 'text-red-500'"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                        <div class="flex-1 text-right">
                            <p class="text-sm font-bold"
                                :class="paymentAlert.type === 'warning' ? 'text-amber-800' : 'text-red-700'">
                                {{ paymentAlert.title }}</p>
                            <p class="text-sm mt-1"
                                :class="paymentAlert.type === 'warning' ? 'text-amber-700' : 'text-red-700'">
                                {{ paymentAlert.message }}</p>
                            <p v-if="paymentAlert.action" class="text-xs mt-1"
                                :class="paymentAlert.type === 'warning' ? 'text-amber-700' : 'text-red-600'">
                                {{ paymentAlert.action }}</p>
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

                <!-- Payment Form Card (Mojaz silver gradient style) -->
                <div v-if="plan" class="payment-card">
                    <form @submit.prevent="handleSubmit">

                        <!-- Card Type Selector -->
                        <div class="flex items-center justify-between mb-4">
                            <div class="shrink-0">
                                <svg v-if="form.paymentMethod === 'mada'" class="h-8 w-auto" viewBox="0 0 60 38"
                                    fill="none">
                                    <rect width="60" height="38" rx="6" fill="#1d6f37" />
                                    <text x="30" y="24" text-anchor="middle" font-size="12" fill="white"
                                        font-weight="bold">mada</text>
                                </svg>
                                <svg v-else-if="form.paymentMethod === 'mastercard'" class="h-8 w-auto"
                                    viewBox="0 0 60 38" fill="none">
                                    <rect width="60" height="38" rx="6" fill="#fff" stroke="#e2e8f0" />
                                    <circle cx="23" cy="19" r="10" fill="#eb001b" opacity=".85" />
                                    <circle cx="37" cy="19" r="10" fill="#f79e1b" opacity=".85" />
                                </svg>
                                <svg v-else class="h-8 w-auto" viewBox="0 0 60 38" fill="none">
                                    <rect width="60" height="38" rx="6" fill="#1a1f71" />
                                    <text x="30" y="25" text-anchor="middle" font-size="14" fill="white"
                                        font-weight="bold" font-style="italic">VISA</text>
                                </svg>
                            </div>
                            <div class="flex items-center gap-3">
                                <select id="payment-card-type" v-model="form.paymentMethod" name="card-type"
                                    class="border border-slate-300 rounded px-2 py-1 text-sm bg-white cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary">
                                    <option value="mada">مدى</option>
                                    <option value="mastercard">Mastercard</option>
                                    <option value="visa">Visa</option>
                                </select>
                                <span class="text-sm font-bold text-slate-700">نوع البطاقة</span>
                            </div>
                        </div>

                        <!-- Card form fields (2-col grid) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 mb-4">
                            <!-- Card Number -->
                            <div>
                                <label for="cardNumber"
                                    class="block text-sm font-bold text-slate-700 mb-1">رقم البطاقة</label>
                                <input id="cardNumber" v-model="form.cardNumber" name="cc-number" type="tel"
                                    placeholder="رقم البطاقة" maxlength="19" dir="ltr" inputmode="numeric"
                                    autocomplete="cc-number"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary text-left"
                                    :class="errors.cardNumber ? 'border-red-400' : ''"
                                    @input="formatCardNumber" />
                                <p v-if="errors.cardNumber" class="text-red-500 text-xs mt-1">{{ errors.cardNumber }}
                                </p>
                            </div>

                            <!-- Expiry -->
                            <div>
                                <label for="expiry"
                                    class="block text-sm font-bold text-slate-700 mb-1">تاريخ الانتهاء</label>
                                <input id="expiry" v-model="form.expiry" name="cc-exp" type="tel"
                                    placeholder="شهر / سنة" maxlength="7" dir="ltr" inputmode="numeric"
                                    autocomplete="cc-exp"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary text-left"
                                    :class="errors.expiry ? 'border-red-400' : ''"
                                    @input="formatExpiry" />
                                <p v-if="errors.expiry" class="text-red-500 text-xs mt-1">{{ errors.expiry }}</p>
                            </div>

                            <!-- Card Holder -->
                            <div>
                                <label for="cardHolder"
                                    class="block text-sm font-bold text-slate-700 mb-1">اسم حامل البطاقة</label>
                                <input id="cardHolder" v-model="form.cardHolder" name="cc-name" type="text"
                                    placeholder="اسم حامل البطاقة" dir="rtl" autocomplete="cc-name"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                    :class="errors.cardHolder ? 'border-red-400' : ''"
                                    @input="form.cardHolder = form.cardHolder.toUpperCase()" />
                                <p v-if="errors.cardHolder" class="text-red-500 text-xs mt-1">{{ errors.cardHolder }}
                                </p>
                            </div>

                            <!-- CVV -->
                            <div>
                                <label for="cvv"
                                    class="block text-sm font-bold text-slate-700 mb-1">رمز التحقق (CVV)</label>
                                <input id="cvv" v-model="form.cvv" name="cc-csc" type="tel"
                                    placeholder="رمز التحقق (CVV)" maxlength="4" dir="ltr" inputmode="numeric"
                                    autocomplete="cc-csc"
                                    class="w-full px-3 py-2.5 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary text-left"
                                    :class="errors.cvv ? 'border-red-400' : ''" />
                                <p v-if="errors.cvv" class="text-red-500 text-xs mt-1">{{ errors.cvv }}</p>
                            </div>
                        </div>

                        <!-- Terms checkbox -->
                        <label class="flex items-start gap-2.5 cursor-pointer mb-4">
                            <input v-model="form.acceptTerms" type="checkbox" name="acceptTerms"
                                class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary mt-0.5 shrink-0" />
                            <span class="text-xs text-slate-600 leading-relaxed">
                                أوافق على
                                <router-link :to="{ name: 'terms' }" target="_blank"
                                    class="text-primary font-semibold underline">الشروط والأحكام</router-link>
                                و
                                <router-link :to="{ name: 'privacy' }" target="_blank"
                                    class="text-primary font-semibold underline">سياسة الخصوصية</router-link>
                            </span>
                        </label>
                        <p v-if="errors.acceptTerms" class="text-red-500 text-xs mb-3 -mt-2">{{ errors.acceptTerms }}</p>

                        <!-- Submit Button -->
                        <button type="submit" :disabled="isSubmitting"
                            class="w-full bg-primary hover:bg-primary-dark text-white py-3 px-8 rounded-xl font-bold text-sm transition-all cursor-pointer disabled:cursor-not-allowed disabled:opacity-60 inline-flex items-center justify-center gap-2">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                            <span v-if="isSubmitting">جاري معالجة الدفع...</span>
                            <template v-else>
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                                </svg>
                                ادفع الآن — {{ formatDecimal( totalPrice ) }} ر.س
                            </template>
                        </button>
                    </form>
                </div>

                <!-- No plan fallback -->
                <div v-else class="text-center py-12">
                    <p class="text-white text-lg font-bold mb-4">لم يتم اختيار وثيقة</p>
                    <router-link to="/compare"
                        class="inline-flex items-center gap-2 bg-white text-primary px-6 py-3 rounded-xl font-medium hover:bg-slate-100 transition-colors">
                        العودة للمقارنة
                    </router-link>
                </div>

                <!-- Secure Badge -->
                <div class="text-center mt-4">
                    <div class="flex items-center justify-center gap-3 mb-2">
                        <img :src="acceptedCardsLogo" alt="Visa, Mastercard, مدى" class="h-6 object-contain opacity-90" />
                    </div>
                    <small class="text-white/90 text-sm flex items-center justify-center gap-1.5">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        هذه الصفحة مؤمنة بنسبة 100%
                    </small>
                </div>

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
import CashbackModal from '../components/checkout/CashbackModal.vue';
import { CASHBACK_SUMMARY_IMAGE } from '@/constants/cashbackImage';
import acceptedCardsLogo from '@/../../resources/images/logo/master-visa-mada.webp';

const bannerBg = CASHBACK_SUMMARY_IMAGE;


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

    // Show cashback modal once per session on page entry
    if ( !_cashbackModalShown.value ) {
        _cashbackModalShown.value = true;
        sessionStorage.setItem( 'cashbackModalShown', '1' );
        showCashbackModal.value = true;
    }
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

// التسعير — prefer discounted values from OrderReviewPage
const subtotal = computed( () => {
    // If OrderReviewPage saved discounted subtotal, use it
    if ( selectedPlanData.value?.subtotalAfterDiscount != null ) {
        return selectedPlanData.value.subtotalAfterDiscount;
    }
    const base = dynamicPrice.value.annualPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + a.price, 0 );
    return base + addonSum;
} );
const vatAmount = computed( () => {
    if ( selectedPlanData.value?.vatAmount != null ) return selectedPlanData.value.vatAmount;
    return pricingResult.value.vat;
} );
const totalPrice = computed( () => {
    if ( selectedPlanData.value?.totalPrice != null ) return selectedPlanData.value.totalPrice;
    return pricingResult.value.total;
} );
const pricingResult = computed( () => calculateTotalWithVAT( dynamicPrice.value.annualPrice || 0, selectedAddons.value.reduce( ( s, a ) => s + a.price, 0 ) ) );
const _monthlyTotal = computed( () => {
    const base = dynamicPrice.value.monthlyPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + Math.ceil( a.price / 12 ), 0 );
    return calculateTotalWithVAT( base + addonSum ).total;
} );

// Format with 2 decimal places + thousand separator
function formatDecimal( num ) {
    return new Intl.NumberFormat( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ).format( num );
}



//
const form = reactive( {
    paymentMethod: 'mada',
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

// Show rejection alert at top if redirected back from PaymentWaitingPage
watch( cardRejectionReasonKey, ( key ) => {
    if ( !key ) return;
    const digits = ( form.cardNumber || '' ).replace( /\s/g, '' );
    const detectedBank = digits.length >= 6 ? detectBankFromBin( digits ) : null;
    const alert = formatPaymentFailure( key, { detectedBank } );
    setPaymentAlert( alert );
}, { immediate: true } );

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

    }

    if ( !form.acceptTerms ) { errors.acceptTerms = 'يجب الموافقة على الشروط والأحكام'; return false; }

    return true;
}

//

//
// ── Card number formatting (1234 5678 ...) ──
function formatCardNumber() {
    const raw = form.cardNumber.replace( /\D/g, '' ).slice( 0, 16 );
    form.cardNumber = raw.replace( /(\d{4})(?=\d)/g, '$1 ' );
    // Clear errors on valid input
    if ( errors.cardNumber ) {
        const digits = raw;
        if ( digits.length === 16 && isValidLuhn( digits ) ) delete errors.cardNumber;
    }
}

// ── Expiry formatting (MM/YY) ──
function formatExpiry() {
    let raw = form.expiry.replace( /\D/g, '' ).slice( 0, 4 );
    if ( raw.length >= 3 ) raw = raw.slice( 0, 2 ) + '/' + raw.slice( 2 );
    form.expiry = raw;
    if ( errors.expiry && /^\d{2}\/\d{2}$/.test( raw ) && isExpiryValid( raw ) ) delete errors.expiry;
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

// ── Prevent browser back navigation ──
function preventBack() {
    window.history.pushState( null, '', window.location.href );
}
function onPopState() {
    preventBack();
}

// ── Abandonment tracking cleanup ──
let _cleanupAbandonment;
onMounted( () => {
    _cleanupAbandonment = useAbandonmentTracking( () => 'checkout' );

    // Block browser back on payment page
    preventBack();
    window.addEventListener( 'popstate', onPopState );
} );
onUnmounted( () => {
    if ( _cleanupAbandonment ) _cleanupAbandonment();
    window.removeEventListener( 'popstate', onPopState );
} );
</script>

<style scoped>
.payment-card {
    border-radius: 10px;
    background-image: linear-gradient(to bottom, #f8f9fa, #e9ecef);
    background-color: silver;
    padding: 20px;
    border: 2px solid #fff;
    box-shadow: 2px 3px 5px 1px #999;
}

.fade-enter-active, .fade-leave-active {
    transition: opacity .25s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}

.ltr-nums {
    font-variant-numeric: tabular-nums;
    direction: ltr;
    unicode-bidi: embed;
}
</style>
