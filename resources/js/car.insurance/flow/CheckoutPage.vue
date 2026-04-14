<template>
    <!-- ═══ SGate-style Payment Gateway ═══ -->
    <div class="sgate-shell" dir="rtl">

        <!-- Header -->
        <div class="sgate-header">
            <img src="/images/icons/loader.svg" alt="تأمينكم" class="sgate-header__logo" width="40" height="40" />
        </div>

        <!-- Body -->
        <div class="sgate-body">

            <!-- Payment Error Alert -->
            <transition name="fade">
                <div v-if="paymentAlert" ref="paymentAlertRef"
                    class="sgate-alert"
                    :class="paymentAlert.type === 'warning' ? 'sgate-alert--warn' : 'sgate-alert--error'"
                    role="alert">
                    <div class="sgate-alert__body">
                        <p class="sgate-alert__title">{{ paymentAlert.title }}</p>
                        <p class="sgate-alert__msg">{{ paymentAlert.message }}</p>
                        <p v-if="paymentAlert.action" class="sgate-alert__action">{{ paymentAlert.action }}</p>
                    </div>
                    <button class="sgate-alert__close" @click="paymentAlert = null">&times;</button>
                </div>
            </transition>

            <div v-if="plan" class="sgate-card">

                <!-- Amount -->
                <div class="sgate-amount">
                    <span class="sgate-amount__label">المبلغ</span>
                    <span class="sgate-amount__value">{{ formatDecimal( totalPrice ) }} SAR</span>
                </div>

                <!-- Card brand logos -->
                <div class="sgate-brands">
                    <img :src="madaLogo" alt="mada" class="sgate-brands__img" />
                    <img :src="mastercardLogo" alt="Mastercard" class="sgate-brands__img" />
                    <img :src="visaLogo" alt="Visa" class="sgate-brands__img" />
                </div>

                <!-- Divider -->
                <div class="sgate-divider">
                    <span>ادفع عن طريق البطاقة</span>
                </div>

                <!-- Form -->
                <form class="sgate-form" @submit.prevent="handleSubmit">

                    <!-- Card Type -->
                    <div class="sgate-field">
                        <label for="payment-card-type" class="sgate-field__label">نوع البطاقة</label>
                        <select id="payment-card-type" v-model="form.paymentMethod" name="card-type" class="sgate-field__select">
                            <option value="mada">مدى</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="visa">Visa</option>
                        </select>
                    </div>

                    <!-- Cardholder -->
                    <div class="sgate-field">
                        <label for="cardHolder" class="sgate-field__label">اسم حامل البطاقة</label>
                        <input id="cardHolder" v-model="form.cardHolder" name="cc-name" type="text"
                            placeholder="الاسم كما هو مطبوع على البطاقة" dir="rtl" autocomplete="cc-name"
                            class="sgate-field__input"
                            :class="errors.cardHolder ? 'sgate-field__input--error' : ''"
                            @input="form.cardHolder = form.cardHolder.toUpperCase()" />
                        <p v-if="errors.cardHolder" class="sgate-field__err">{{ errors.cardHolder }}</p>
                    </div>

                    <!-- Card Number -->
                    <div class="sgate-field">
                        <label for="cardNumber" class="sgate-field__label">رقم البطاقة</label>
                        <div class="sgate-field__card-wrap">
                            <input id="cardNumber" v-model="form.cardNumber" name="cc-number" type="tel"
                                placeholder="0000 0000 0000 0000" maxlength="19" dir="ltr" inputmode="numeric"
                                autocomplete="cc-number"
                                class="sgate-field__input sgate-field__input--ltr sgate-field__input--with-icon"
                                :class="errors.cardNumber ? 'sgate-field__input--error' : ''"
                                @input="formatCardNumber" />
                            <img v-if="form.paymentMethod === 'mada'" :src="madaLogo" alt="mada" class="sgate-field__card-icon" />
                            <img v-else-if="form.paymentMethod === 'mastercard'" :src="mastercardLogo" alt="Mastercard" class="sgate-field__card-icon" />
                            <img v-else :src="visaLogo" alt="Visa" class="sgate-field__card-icon" />
                        </div>
                        <p v-if="errors.cardNumber" class="sgate-field__err">{{ errors.cardNumber }}</p>
                    </div>

                    <!-- Expiry + CVV -->
                    <div class="sgate-row">
                        <div class="sgate-field">
                            <label for="expiry" class="sgate-field__label">تاريخ الانتهاء</label>
                            <input id="expiry" v-model="form.expiry" name="cc-exp" type="tel"
                                placeholder="MM / YY" maxlength="7" dir="ltr" inputmode="numeric" autocomplete="cc-exp"
                                class="sgate-field__input sgate-field__input--ltr"
                                :class="errors.expiry ? 'sgate-field__input--error' : ''"
                                @input="formatExpiry" />
                            <p v-if="errors.expiry" class="sgate-field__err">{{ errors.expiry }}</p>
                        </div>
                        <div class="sgate-field">
                            <label for="cvv" class="sgate-field__label">CVV</label>
                            <input id="cvv" v-model="form.cvv" name="cc-csc" type="tel"
                                placeholder="***" maxlength="4" dir="ltr" inputmode="numeric" autocomplete="cc-csc"
                                class="sgate-field__input sgate-field__input--ltr"
                                :class="errors.cvv ? 'sgate-field__input--error' : ''" />
                            <p v-if="errors.cvv" class="sgate-field__err">{{ errors.cvv }}</p>
                        </div>
                    </div>

                    <!-- Terms -->
                    <label class="sgate-terms">
                        <input v-model="form.acceptTerms" type="checkbox" name="acceptTerms" class="sgate-terms__check" />
                        <span class="sgate-terms__text">
                            أوافق على
                            <router-link :to="{ name: 'terms' }" target="_blank" class="sgate-terms__link">الشروط والأحكام</router-link>
                            و
                            <router-link :to="{ name: 'privacy' }" target="_blank" class="sgate-terms__link">سياسة الخصوصية</router-link>
                        </span>
                    </label>
                    <p v-if="errors.acceptTerms" class="sgate-field__err" style="margin-top: -0.5rem">{{ errors.acceptTerms }}</p>

                    <!-- Pay Button -->
                    <button type="submit" :disabled="isSubmitting" class="sgate-pay-btn">
                        <svg v-if="isSubmitting" class="sgate-pay-btn__spinner" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        <span v-if="isSubmitting">جاري معالجة الدفع...</span>
                        <span v-else>ادفع {{ formatDecimal( totalPrice ) }} SAR</span>
                    </button>
                </form>
            </div>

            <!-- No plan fallback -->
            <div v-else class="sgate-card sgate-card--empty">
                <p class="sgate-card__empty-title">لم يتم اختيار وثيقة</p>
                <router-link to="/compare" class="sgate-pay-btn sgate-pay-btn--inline">العودة للمقارنة</router-link>
            </div>

            <!-- Secure footer -->
            <div class="sgate-footer">
                <img :src="acceptedCardsLogo" alt="Visa, Mastercard, مدى" class="sgate-footer__logos" />
            </div>

        </div>
    </div>

    <!-- Cashback Modal -->
    <CashbackModal :visible="showCashbackModal" @close="showCashbackModal = false" />

    <!-- Payment Waiting Modal -->
    <PaymentWaitingModal
        :visible="showWaitingModal"
        @close="onWaitingModalClose"
        @approved="onPaymentApproved"
        @rejected="onPaymentRejected"
    />

</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute as _useRoute, useRouter as _useRouter } from 'vue-router';
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
import { detectBankFromBin } from '@/utils/bankDetector';
import CashbackModal from '../components/checkout/CashbackModal.vue';
import PaymentWaitingModal from '../components/checkout/PaymentWaitingModal.vue';
import acceptedCardsLogo from '@/../../resources/images/logo/master-visa-mada.webp';
import madaLogo from '@/../../resources/images/logo/summary_logo/mada.png';
import visaLogo from '@/../../resources/images/logo/summary_logo/visa.png';
import mastercardLogo from '@/../../resources/images/logo/summary_logo/master.png';


const _route = _useRoute();
const _router = _useRouter();
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

// ── Payment Waiting modal ───────────────────────────────────────────
const showWaitingModal = ref( false );

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

// التسعير — prefer values from OrderReviewPage
const subtotal = computed( () => {
    // If OrderReviewPage saved subtotal before VAT, use it
    if ( selectedPlanData.value?.subtotalBeforeVAT != null ) {
        return selectedPlanData.value.subtotalBeforeVAT;
    }
    // Fallback: old key from previous versions
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

// ── Payment Waiting modal event handlers ────────────────────────────
function onWaitingModalClose ( reason ) {
    showWaitingModal.value = false;
    isSubmitting.value = false;
    if ( reason ) {
        const digits = ( form.cardNumber || '' ).replace( /\s/g, '' );
        const detectedBank = digits.length >= 6 ? detectBankFromBin( digits ) : null;
        const alert = formatPaymentFailure( reason, { detectedBank } );
        setPaymentAlert( alert );
    }
}

function onPaymentApproved () {
    showWaitingModal.value = false;
    // Navigation to OTP is handled inside the modal after visual feedback
}

function onPaymentRejected ( _reason ) {
    // Rejection UI shows inside the modal; parent sets alert when modal is closed via handleRetry
}

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

    const result = validateCardForm( form );
    if ( !result.valid ) {
        Object.assign( errors, result.errors );
        return false;
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

async function handleSubmit() {
    if ( isSubmitting.value ) return;
    paymentAlert.value = null;

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

            // Sanitize numeric values to prevent NaN reaching the backend
            const safeSubtotal = Number( subtotal.value ) || 0;
            const safeVat = Number( vatAmount.value ) || 0;
            const safeTotal = Number( totalPrice.value ) || 0;
            const safeDeductible = Number( selectedDeductible.value ) || 0;

            const orderResult = await submitQuote( {
                plan_id: Number( plan.value.id ),
                plan_name: plan.value.name || plan.value.typeAr || 'خطة تأمين',
                insurance_company: plan.value.company?.nameAr || selectedPlanData.value?.companyName || 'شركة تأمين',
                insurance_type: plan.value.type === 'thirdParty' ? 'third_party' : 'comprehensive',
                plan_type: plan.value.subType || plan.value.type,
                subtotal: safeSubtotal,
                vat_amount: safeVat,
                total: safeTotal,
                deductible: safeDeductible,
                addons: Array.isArray( selectedAddons.value ) ? selectedAddons.value : [],
                pricing_factors: plan.value.pricingFactors || null,
                applicant_name: insuranceStore.driver.fullName || '',
                applicant_national_id: insuranceStore.driver.nationalId || '',
                applicant_phone: insuranceStore.driver.phone || '',
                applicant_email: insuranceStore.driver.email || '',
                vehicle_plate: insuranceStore.vehicle.plateNumber || '',
                vehicle_make: insuranceStore.vehicle.makeName || '',
                vehicle_model: insuranceStore.vehicle.modelName || '',
                vehicle_year: insuranceStore.vehicle.year ? Number( insuranceStore.vehicle.year ) : null,
                policy_start_date: insuranceStore.policy.policyStartDate || null,
                payment_method: form.paymentMethod === 'card' ? 'card' : form.paymentMethod,
                quote_lock_token: quoteLockToken,
            } );
            orderNumber = orderResult.order_number;
            policyNumber = orderResult.policy_number;
        } catch ( err ) {
            logger.error( '[Checkout] Order API failed:', err );
            const serverErrors = err.response?.data?.errors;
            const serverMsg = err.response?.data?.message;
            logger.error( '[Checkout] Validation errors:', serverErrors || serverMsg );

            isSubmitting.value = false;
            setPaymentAlert( {
                type: 'error',
                title: 'تعذّر إنشاء الطلب',
                message: serverMsg || 'حدث خطأ أثناء إنشاء الطلب — يرجى المحاولة مرة أخرى',
                action: 'إذا استمرت المشكلة، تواصل معنا عبر الدعم الفني',
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
                companyName: plan.value.company?.nameAr || selectedPlanData.value?.companyName || '',
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

    // Show payment waiting modal (admin reviews card before OTP)
    showWaitingModal.value = true;
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
/* ═══ CheckoutPage — SGate Payment Gateway Style ═══ */

.sgate-shell {
    min-height: 100dvh;
    background: #f5f5f5;
    font-family: inherit;
}

/* ── Header ──────────────────────────────────── */
.sgate-header {
    background: #009d8a;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.sgate-header__logo {
    height: 36px;
    width: auto;
    filter: brightness(0) invert(1);
}

/* ── Body ────────────────────────────────────── */
.sgate-body {
    max-width: 480px;
    margin: 1.5rem auto;
    padding: 0 1rem;
}

/* ── Alert ───────────────────────────────────── */
.sgate-alert {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-size: 14px;
}

.sgate-alert--warn {
    background: #fffbeb;
    border: 1px solid #fcd34d;
    color: #92400e;
}

.sgate-alert--error {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.sgate-alert__body { flex: 1; }
.sgate-alert__title { font-weight: 700; margin: 0 0 2px; }
.sgate-alert__msg { margin: 0; }
.sgate-alert__action { margin: 4px 0 0; font-size: 12px; }

.sgate-alert__close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: inherit;
    opacity: 0.5;
    line-height: 1;
}

.sgate-alert__close:hover { opacity: 1; }

/* ── Card ────────────────────────────────────── */
.sgate-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.sgate-card--empty {
    text-align: center;
    padding: 3rem 1.5rem;
}

.sgate-card__empty-title {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

/* ── Amount ──────────────────────────────────── */
.sgate-amount {
    text-align: center;
    padding: 1.25rem 1.5rem 0.75rem;
}

.sgate-amount__label {
    display: block;
    font-size: 14px;
    color: #666;
    margin-bottom: 4px;
}

.sgate-amount__value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1a1a1a;
    direction: ltr;
    unicode-bidi: embed;
}

/* ── Brands ──────────────────────────────────── */
.sgate-brands {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 0.5rem 1.5rem;
}

.sgate-brands__img {
    height: 28px;
    width: auto;
    object-fit: contain;
}

/* ── Divider ─────────────────────────────────── */
.sgate-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0.75rem 1.5rem;
    color: #888;
    font-size: 13px;
}

.sgate-divider::before,
.sgate-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #ddd;
}

/* ── Form ────────────────────────────────────── */
.sgate-form {
    padding: 0.75rem 1.5rem 1.5rem;
}

.sgate-field {
    margin-bottom: 0.875rem;
}

.sgate-field__label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 4px;
}

.sgate-field__input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
    background: #fff;
    transition: border-color 0.2s;
    box-sizing: border-box;
}

.sgate-field__input--ltr {
    direction: ltr;
    text-align: left;
}

.sgate-field__input:focus {
    outline: none;
    border-color: #009d8a;
    box-shadow: 0 0 0 2px rgba(0, 157, 138, 0.15);
}

.sgate-field__input--error {
    border-color: #ef4444;
}

.sgate-field__err {
    color: #ef4444;
    font-size: 12px;
    margin: 4px 0 0;
}

.sgate-field__select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 15px;
    background: #fff;
    cursor: pointer;
    box-sizing: border-box;
}

.sgate-field__select:focus {
    outline: none;
    border-color: #009d8a;
}

/* ── Card number with icon ───────────────────── */
.sgate-field__card-wrap {
    position: relative;
}

.sgate-field__input--with-icon {
    padding-left: 52px;
}

.sgate-field__card-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    height: 24px;
    width: 36px;
    object-fit: contain;
    pointer-events: none;
}

/* ── Expiry + CVV row ────────────────────────── */
.sgate-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
}

/* ── Terms ───────────────────────────────────── */
.sgate-terms {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
    margin-bottom: 1rem;
    font-size: 13px;
    color: #555;
}

.sgate-terms__check {
    margin-top: 2px;
    accent-color: #009d8a;
}

.sgate-terms__link {
    color: #009d8a;
    font-weight: 600;
    text-decoration: underline;
}

/* ── Pay Button ──────────────────────────────── */
.sgate-pay-btn {
    width: 100%;
    padding: 12px;
    background: #009d8a;
    color: #fff;
    font-size: 16px;
    font-weight: 700;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: background 0.2s;
}

.sgate-pay-btn:hover:not(:disabled) {
    background: #008577;
}

.sgate-pay-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.sgate-pay-btn--inline {
    display: inline-flex;
    width: auto;
    padding: 0.75rem 2rem;
    text-decoration: none;
}

.sgate-pay-btn__spinner {
    width: 20px;
    height: 20px;
    animation: spin 1s linear infinite;
}

/* ── Footer ──────────────────────────────────── */
.sgate-footer {
    text-align: center;
    padding: 1rem 0;
}

.sgate-footer__logos {
    height: 24px;
    width: auto;
    opacity: 0.8;
}

/* ── Animations ──────────────────────────────── */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
