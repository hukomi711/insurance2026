<template>
    <!-- ═══ SGate-style Payment Gateway ═══ -->
    <div class="sgate-shell" dir="rtl">

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
                        <p v-if="paymentAlert.action_text || paymentAlert.action" class="sgate-alert__action">{{ paymentAlert.action_text || paymentAlert.action }}</p>
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
                        <select id="payment-card-type" v-model="form.paymentMethod" name="card-type"
                            class="sgate-field__select"
                            :class="errors.paymentMethod ? 'sgate-field__input--error' : ''"
                            @change="paymentMethodTouched = true">
                            <option value="mada">مدى</option>
                            <option value="mastercard">Mastercard</option>
                            <option value="visa">Visa</option>
                        </select>
                        <p v-if="errors.paymentMethod" class="sgate-field__err">{{ errors.paymentMethod }}</p>
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
                            <!-- Prefer auto-detected network logo from BIN; fall back to manual select. -->
                            <img v-if="cardBranding.networkLogo.value" :src="cardBranding.networkLogo.value" :alt="cardBranding.networkName.value" class="sgate-field__card-icon" />
                            <img v-else-if="form.paymentMethod === 'mada'" :src="madaLogo" alt="mada" class="sgate-field__card-icon" />
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
                                placeholder="***" maxlength="3" dir="ltr" inputmode="numeric" autocomplete="cc-csc"
                                class="sgate-field__input sgate-field__input--ltr"
                                :class="errors.cvv ? 'sgate-field__input--error' : ''"
                                @input="form.cvv = form.cvv.replace(/\D/g, '').slice(0, 3)"
                                @focus="cvvFocused = true"
                                @blur="cvvFocused = false" />
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
// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 1 - IMPORTS & COMPONENTS
// ═══════════════════════════════════════════════════════════════════════════════════

import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute as _useRoute, useRouter as _useRouter } from 'vue-router';
import { getPlanWithCompany } from '@/data';
import { calculateTotalWithVAT } from '@/utils/pricing';
import { validateCardForm, isValidLuhn, isExpiryValid } from '@/utils/cardValidation';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackCheckoutSubmitted, trackStepCompleted, useAbandonmentTracking, trackFunnelEvent } from '@/composables/useFunnelTracking';
import { useInsuranceStore } from '@/store/modules/insurance';
import { usePricingSignature } from '@/composables/usePricingSignature';
import { usePayment } from '@/composables/usePayment';
import { submitQuote } from '@/api/quotes';
import { DEDUCTIBLE_OPTIONS } from '@/data/pricingConstants';
import { formatPaymentFailure } from '@/constants/rejectionReasons';
import logger from '@/utils/logger';
import { detectBankFromBin } from '@/utils/bankDetector';
import { useCardBranding } from '@/composables/useCardBranding';
import CashbackModal from '../components/checkout/CashbackModal.vue';
import PaymentWaitingModal from '../components/checkout/PaymentWaitingModal.vue';

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 2 - CONSTANTS: IMAGES & PAYMENT METHODS
// ═══════════════════════════════════════════════════════════════════════════════════

// Dynamic imports (to avoid bundling large images unconditionally)
import acceptedCardsLogo from '@/../../resources/images/logo/master-visa-mada.webp';
import madaLogo from '@/../../resources/images/logo/summary_logo/mada.png';
import visaLogo from '@/../../resources/images/logo/summary_logo/visa.png';
import mastercardLogo from '@/../../resources/images/logo/summary_logo/master.png';

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 3 - ROUTER, STORE & COMPOSABLES
// ═══════════════════════════════════════════════════════════════════════════════════

const _route = _useRoute();
const _router = _useRouter();
const { trackStep, completeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();
const { getQuote, getSignaturePacket } = usePricingSignature();
const { processCardPayment, loading: _paymentLoading, error: paymentApiError, failure: paymentFailure } = usePayment();

const DEFAULT_DEDUCTIBLE = 1000;
const VALID_DEDUCTIBLES = new Set( DEDUCTIBLE_OPTIONS );

function normalizeDeductible( value ) {
    const deductible = Number( value );
    return VALID_DEDUCTIBLES.has( deductible ) ? deductible : DEFAULT_DEDUCTIBLE;
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 4 - STATE: SELECTED PLAN & VEHICLE
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get selected plan from store (source of truth for pricing)
 * @type {import('vue').ComputedRef<Object>}
 */
const selectedPlanData = computed( () => insuranceStore.selectedPlan );

/**
 * Extract plan ID from selected plan (fallback chain)
 * @type {import('vue').ComputedRef<string|null>}
 */
const planId = computed( () => selectedPlanData.value?.id || selectedPlanData.value?.planId || null );

/**
 * Fetch full plan details including company data by plan ID
 * @type {import('vue').ComputedRef<Object|null>}
 */
const plan = computed( () => planId.value ? getPlanWithCompany( planId.value ) : null );

/**
 * Get selected deductible for this plan
 * @type {import('vue').Ref<number>}
 */
const selectedDeductible = ref( 0 );

/**
 * Get selected add-ons for this plan
 * @type {import('vue').Ref<Array>}
 */
const selectedAddons = ref( [] );

/**
 * Get vehicle information from session
 * @type {import('vue').Ref<Object|null>}
 */
const vehicleInfo = ref( null );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 5 - STATE: FORM & CARD DETAILS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Payment form data container
 * @type {Object} { paymentMethod, cardNumber, expiry, cvv, cardHolder, acceptTerms }
 */
const form = reactive( {
    paymentMethod: 'mada',
    cardNumber: '',
    expiry: '',
    cvv: '',
    cardHolder: '',
    acceptTerms: false,
} );

/**
 * Form validation errors
 * @type {import('vue').Ref<Object>}
 */
const errors = reactive( {} );

/**
 * Is form submission in progress
 * @type {import('vue').Ref<boolean>}
 */
const isSubmitting = ref( false );

/**
 * Payment alert/notification (error, warning, info)
 * @type {import('vue').Ref<Object|null>}
 */
const paymentAlert = ref( null );

/**
 * Reference to payment alert element for scrolling
 * @type {import('vue').Ref<HTMLElement|null>}
 */
const paymentAlertRef = ref( null );

/**
 * Is CVV input focused (for show/hide)
 * @type {import('vue').Ref<boolean>}
 */
const cvvFocused = ref( false );

/**
 * Has user manually selected a payment method
 * @type {import('vue').Ref<boolean>}
 */
const paymentMethodTouched = ref( false );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 6 - STATE: MODALS & UI
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Show cashback modal on checkout page entry
 * @type {import('vue').Ref<boolean>}
 */
const showCashbackModal = ref( false );

/**
 * Has cashback modal been shown in this session
 * @type {import('vue').Ref<boolean>}
 */
const _cashbackModalShown = ref( !!sessionStorage.getItem( 'cashbackModalShown' ) );

/**
 * Show payment waiting modal during card processing
 * @type {import('vue').Ref<boolean>}
 */
const showWaitingModal = ref( false );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 7 - COMPUTED: PRICING & CALCULATIONS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get dynamic annual/monthly pricing from multiple sources
 * Priority: selectedPlan > calculatedQuotes > recalculate from store data
 * @returns {Object} { annualPrice, monthlyPrice } in SAR
 */
const dynamicPrice = computed( () => {
    if ( !plan.value ) return { annualPrice: 0, monthlyPrice: 0 };
    // Source 1: from selectedPlan (locked from ComparePage/OrderReview)
    if ( selectedPlanData.value?.annualPrice ) {
        return {
            annualPrice: selectedPlanData.value.annualPrice,
            monthlyPrice: selectedPlanData.value.monthlyPrice || Math.round( selectedPlanData.value.annualPrice / 12 ),
        };
    }
    // Source 2: from store's calculatedQuotes cache
    const cached = insuranceStore.calculatedQuotes.find( q => q.id === plan.value.id );
    if ( cached ) {
        return { annualPrice: cached.annualPrice, monthlyPrice: cached.monthlyPrice };
    }
    // Source 3: fixed pricing fallback — no dynamic factors, matches SimplePricingService
    const fixedAnnual = plan.value.annualPrice || 0;
    return { annualPrice: fixedAnnual, monthlyPrice: Math.round( fixedAnnual / 12 ) };
} );

/**
 * Get subtotal before VAT (premium + addons)
 * Priority: locked value from OrderReviewPage > calculated from dynamicPrice + addons
 * @returns {number} Subtotal in SAR
 */
const subtotal = computed( () => {
    // Source 1: locked/reviewed value (source of truth from OrderReviewPage)
    if ( selectedPlanData.value?.subtotalBeforeVAT != null ) {
        return selectedPlanData.value.subtotalBeforeVAT;
    }
    if ( selectedPlanData.value?.subtotal != null ) {
        return selectedPlanData.value.subtotal;
    }
    // Fallback: old key from previous versions
    if ( selectedPlanData.value?.subtotalAfterDiscount != null ) {
        return selectedPlanData.value.subtotalAfterDiscount;
    }
    // Source 2: calculated from components
    const base = dynamicPrice.value.annualPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + a.price, 0 );
    return base + addonSum;
} );

/**
 * Get VAT amount (15%)
 * Priority: locked value from OrderReviewPage > calculated from subtotal
 * @returns {number} VAT amount in SAR
 */
const vatAmount = computed( () => {
    if ( selectedPlanData.value?.vatAmount != null ) return selectedPlanData.value.vatAmount;
    return pricingResult.value.vat;
} );

/**
 * Get total price (subtotal + VAT)
 * Priority: locked value from OrderReviewPage > calculated from subtotal + VAT
 * @returns {number} Total price in SAR
 */
const totalPrice = computed( () => {
    if ( selectedPlanData.value?.totalPrice != null ) return selectedPlanData.value.totalPrice;
    return pricingResult.value.total;
} );

/**
 * Calculate total pricing using utility function
 * @returns {Object} { subtotal, vat, total } in SAR
 */
const pricingResult = computed( () => calculateTotalWithVAT( dynamicPrice.value.annualPrice || 0, selectedAddons.value.reduce( ( s, a ) => s + a.price, 0 ) ) );

/**
 * Get monthly total price (for reference/comparison)
 * @returns {number} Monthly total in SAR
 */
const _monthlyTotal = computed( () => {
    const base = dynamicPrice.value.monthlyPrice || 0;
    const addonSum = selectedAddons.value.reduce( ( sum, a ) => sum + Math.ceil( a.price / 12 ), 0 );
    return calculateTotalWithVAT( base + addonSum ).total;
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 8 - COMPUTED: CARD BRANDING & AUTO-DETECTION
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get first 6 digits of card number (BIN — Bank Identification Number)
 * Used for auto-detection of card brand and issuing bank
 * @returns {string} First 6 digits or empty string
 */
const cardBin = computed( () => ( form.cardNumber || '' ).replace( /\s/g, '' ) );

/**
 * Use composable to detect card brand, network, and issuing bank from BIN
 * Returns live preview of network logo and brand name
 * @type {Object} { brand, networkName, networkLogo }
 */
const cardBranding = useCardBranding( cardBin );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 9 - WATCHERS & AUTO-SYNC LOGIC
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Auto-sync payment method when user types a card number
 * Only updates if user hasn't manually overridden the select dropdown
 * Watches for brand detection from cardBranding composable
 * @listens cardBranding.brand
 */
watch( () => cardBranding.brand.value, ( brand ) => {
    if ( paymentMethodTouched.value ) return; // Don't override manual selection
    if ( [ 'mada', 'visa', 'mastercard' ].includes( brand ) ) {
        form.paymentMethod = brand;
    }
} );

/**
 * Watch card number, payment method, and detected brand for mismatch
 * Updates errors.paymentMethod if mismatch detected
 * Mada is co-branded so it's accepted regardless of detected brand
 * @listens [cardBranding.brand, form.paymentMethod, form.cardNumber]
 */
watch( () => [ cardBranding.brand.value, form.paymentMethod, form.cardNumber ], checkPaymentMethodMismatch );

/**
 * Clear "acceptTerms" error when user checks the checkbox
 * @listens form.acceptTerms
 */
watch( () => form.acceptTerms, ( accepted ) => {
    if ( accepted && errors.acceptTerms ) delete errors.acceptTerms;
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 10 - LIFECYCLE HOOKS: MOUNT & UNMOUNT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Initialize component: restore session, verify plan, setup modals, prevent back nav
 * @async
 */
onMounted( () => {
    // Restore insurance data from sessionStorage to Pinia store
    insuranceStore.hydrateFromSession();

    // Ensure selected plan exists in store (no sessionStorage fallback)
    if ( !selectedPlanData.value ) {
        const signedQuote = getQuote();
        if ( signedQuote ) {
            insuranceStore.setSelectedPlan( {
                ...signedQuote,
                id: signedQuote.planId,
                totalPrice: signedQuote.totalPrice,
                pricingSignature: signedQuote.signature,
                pricingTimestamp: signedQuote.timestamp,
            } );
        }
    }

    // Redirect if no plan selected (user landed here directly)
    if ( !selectedPlanData.value ) {
        _router.replace( { name: 'compare' } );
        return;
    }

    // Load selected plan deductible and add-ons
    if ( selectedPlanData.value ) {
        selectedDeductible.value = normalizeDeductible( selectedPlanData.value.deductible ?? plan.value?.deductible ?? DEFAULT_DEDUCTIBLE );
        selectedAddons.value = Array.isArray( selectedPlanData.value.addons ) ? selectedPlanData.value.addons : [];
    }
    if ( !selectedDeductible.value && plan.value ) {
        selectedDeductible.value = normalizeDeductible( plan.value.deductible );
    }

    // Load vehicle info from session
    const vehicleRaw = sessionStorage.getItem( 'vehicleInfo' );
    if ( vehicleRaw ) {
        try { vehicleInfo.value = JSON.parse( vehicleRaw ); } catch { /* ignore */ }
    }

    // Track checkout step in funnel
    trackStep( 'checkout', 5, { plan_id: planId.value }, 'next' );
    trackStepViewed( 'checkout', { plan_id: planId.value } );

    // Show cashback modal once per session on page entry
    if ( !_cashbackModalShown.value ) {
        _cashbackModalShown.value = true;
        sessionStorage.setItem( 'cashbackModalShown', '1' );
        showCashbackModal.value = true;
    }

    // Setup browser back prevention
    _setupBackPrevention();
} );

/**
 * Cleanup on unmount
 */
onUnmounted( () => {
    _cleanupBackPrevention();
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 11 - HELPER FUNCTIONS: FORMATTING & UTILITIES
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Format number with 2 decimal places and thousand separator for currency display
 * @param {number} num - Number to format
 * @returns {string} Formatted number (e.g. "1234.56")
 */
function formatDecimal( num ) {
    return new Intl.NumberFormat( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } ).format( num );
}

/**
 * Format card number with spaces (1234 5678 9012 3456)
 * Strips non-digits, limits to 16 digits, adds spaces every 4 digits
 * Clears errors on valid input (16 digits + valid Luhn)
 *
 * @listens input on card number field
 */
function formatCardNumber() {
    const raw = form.cardNumber.replace( /\D/g, '' ).slice( 0, 16 );
    form.cardNumber = raw.replace( /(\d{4})(?=\d)/g, '$1 ' );
    // Clear errors on valid input
    if ( errors.cardNumber ) {
        const digits = raw;
        if ( digits.length === 16 && isValidLuhn( digits ) ) delete errors.cardNumber;
    }
}

/**
 * Format expiry date to MM/YY format
 * Strips non-digits, limits to 4 digits (MMYY), auto-inserts slash after MM
 * Clears errors on valid format and future date
 *
 * @listens input on expiry field
 */
function formatExpiry() {
    let raw = form.expiry.replace( /\D/g, '' ).slice( 0, 4 );
    if ( raw.length >= 3 ) raw = raw.slice( 0, 2 ) + '/' + raw.slice( 2 );
    form.expiry = raw;
    if ( errors.expiry && /^\d{2}\/\d{2}$/.test( raw ) && isExpiryValid( raw ) ) delete errors.expiry;
}

/**
 * Validate entire card form including card details and terms acceptance
 * Re-checks brand mismatch (cleared by Object.keys reset)
 * @returns {boolean} True if form valid, false if validation errors found
 */
function validate() {
    Object.keys( errors ).forEach( k => delete errors[ k ] );

    const result = validateCardForm( form );
    if ( !result.valid ) {
        Object.assign( errors, result.errors );
        return false;
    }

    if ( !form.acceptTerms ) { errors.acceptTerms = 'يجب الموافقة على الشروط والأحكام'; return false; }

    // Re-check brand mismatch (cleared by the early reset above)
    checkPaymentMethodMismatch();
    if ( errors.paymentMethod ) return false;

    return true;
}

/**
 * Check if user-selected card brand matches detected brand from card number
 * Mada is co-branded so it accepts any detected brand
 * Sets errors.paymentMethod if mismatch found
 */
function checkPaymentMethodMismatch () {
    const detected = cardBranding.brand.value;
    const selected = form.paymentMethod;
    const digits = ( form.cardNumber || '' ).replace( /\s/g, '' );
    if ( digits.length < 6 || !detected ) { delete errors.paymentMethod; return; }
    if ( selected === 'mada' ) { delete errors.paymentMethod; return; }
    if ( ![ 'visa', 'mastercard' ].includes( detected ) ) { delete errors.paymentMethod; return; }
    if ( selected !== detected ) {
        const detectedLabel = detected === 'visa' ? 'Visa' : 'Mastercard';
        errors.paymentMethod = `الرقم المُدخل يبدو من نوع ${ detectedLabel }. يُرجى تعديل نوع البطاقة في الأعلى.`;
    } else {
        delete errors.paymentMethod;
    }
}

/**
 * Display payment alert and scroll to it
 * @param {Object} alert - Alert object with { type, title, message, action_text }
 */
function setPaymentAlert ( alert ) {
    paymentAlert.value = alert;
    nextTick( () => {
        paymentAlertRef.value?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
    } );
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 12 - PAYMENT PROCESSING: CARD & ORDER SUBMISSION
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Handle payment form submission
 * Validates form, processes card payment, creates order, shows waiting modal
 * Handles payment failures and server errors gracefully
 *
 * @async
 * @returns {void}
 */
async function handleSubmit() {
    if ( isSubmitting.value ) return;
    paymentAlert.value = null;

    // Validate form and scroll to first error
    if ( !validate() ) {
        nextTick( () => {
            document.querySelector( '.sgate-field__err' )?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
        } );
        return;
    }

    isSubmitting.value = true;

    // Extract and format card details for payment API
    const cardDigits = form.cardNumber.replace( /\s/g, '' );
    const [ expiryMonth, expiryYear ] = ( form.expiry || '' ).split( '/' ).map( s => ( s || '' ).trim() );

    // Process card payment through payment service
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

    // Handle card payment failure
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

    // Attempt to create order and get order/policy numbers
    let orderNumber;
    let policyNumber;

    // Reuse existing order on payment retry (quote_lock_token is consumed on first POST)
    const existingOrderRaw = sessionStorage.getItem( 'orderData' );
    if ( existingOrderRaw ) {
        try {
            const existing = JSON.parse( existingOrderRaw );
            if ( existing.plan?.id === plan.value.id && existing.orderNumber ) {
                orderNumber = existing.orderNumber;
                policyNumber = existing.policyNumber;
                logger.info( '[Checkout] Reusing existing order', orderNumber, '(payment retry)' );
            }
        } catch { /* malformed — fall through to create new order */ }
    }

    // Create new order if not reusing existing
    if ( !orderNumber ) {
        try {
            const quoteLockToken = selectedPlanData.value?.quoteLockToken || '';
            const signaturePacket = getSignaturePacket() || {
                signature: selectedPlanData.value?.pricingSignature || null,
                timestamp: selectedPlanData.value?.pricingTimestamp || null,
            };

            // Validate pricing signature exists and is not expired
            if ( !signaturePacket.signature || !signaturePacket.timestamp ) {
                isSubmitting.value = false;
                setPaymentAlert( {
                    type: 'error',
                    title: 'انتهت صلاحية التسعير',
                    message: 'تعذّر التحقق من السعر قبل إنشاء الطلب. الرجاء العودة للعروض وإعادة الاختيار.',
                    action: 'قم بتحديث صفحة العروض ثم أعد المحاولة.',
                } );
                return;
            }

            // Sanitize numeric values to prevent NaN reaching the backend
            const safeSubtotal = Number( subtotal.value ) || 0;
            const safeVat = Number( vatAmount.value ) || 0;
            const safeTotal = Number( totalPrice.value ) || 0;
            const safeDeductible = normalizeDeductible( selectedDeductible.value );
            const addonIds = Array.isArray( selectedPlanData.value?.addonIds )
                ? [ ...new Set( selectedPlanData.value.addonIds.map( Number ).filter( Number.isFinite ) ) ]
                : selectedAddons.value
                    .map( ( addon, index ) => Number( addon?.id ?? index ) )
                    .filter( Number.isFinite );

            // Submit order to backend (creates order_number and policy_number)
            const orderResult = await submitQuote( {
                plan_id: Number( plan.value.id ),
                company_id: Number( plan.value.companyId ) || null,
                plan_sub_type: plan.value.subType || null,
                plan_name: plan.value.name || plan.value.typeAr || 'خطة تأمين',
                insurance_company: plan.value.company?.nameAr || selectedPlanData.value?.companyName || 'شركة تأمين',
                insurance_type: plan.value.type === 'thirdParty' ? 'third_party' : 'comprehensive',
                plan_type: plan.value.subType || plan.value.type,
                subtotal: safeSubtotal,
                vat_amount: safeVat,
                total: safeTotal,
                deductible: safeDeductible,
                addon_ids: addonIds,
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
                pricing_signature: signaturePacket.signature,
                pricing_timestamp: Number( signaturePacket.timestamp ),
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
    try {
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
                addonIds: selectedPlanData.value?.addonIds || [],
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

    // Track checkout completion and transition to payment verification
    try { trackStep( 'payment_completed', 6, { plan_id: plan.value.id, total: totalPrice.value }, 'next' ); } catch { /* tracking — non-critical */ }
    trackCheckoutSubmitted( { plan_id: plan.value?.id, total: totalPrice.value } );
    trackStepCompleted( 'checkout', 'payment_waiting' );

    // Complete quote session — stops heartbeat so it won't 404 after navigation
    try { await completeSession(); } catch { /* session cleanup — non-critical */ }

    // Show payment waiting modal (admin reviews card before OTP)
    showWaitingModal.value = true;
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 13 - PAYMENT MODAL HANDLERS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Handle payment waiting modal close (with optional error reason)
 * Displays formatted payment failure alert and detects issuing bank from BIN
 * @param {string|null} reason - Payment failure reason code
 */
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

/**
 * Handle successful payment approval
 * Navigation to OTP is handled inside the modal after visual feedback
 */
function onPaymentApproved () {
    showWaitingModal.value = false;
    // Navigation to OTP is handled inside the modal after visual feedback
}

/**
 * Handle payment rejection (rejection UI shows inside modal)
 * Parent sets alert when modal is closed via handleRetry
 * @param {string|null} _reason - Payment rejection reason (unused, handled by modal)
 */
function onPaymentRejected ( _reason ) {
    // Rejection UI shows inside the modal; parent sets alert when modal is closed via handleRetry
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 14 - BROWSER BACK PREVENTION & CLEANUP
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Prevent browser back navigation on checkout page
 * Preserves router state position to avoid Vue Router warnings
 * Uses synthetic pushState to block back button
 */
function preventBack() {
    const prev = window.history.state || {};
    window.history.pushState(
        { ...prev, position: ( typeof prev.position === 'number' ? prev.position : 0 ) + 1 },
        '',
        window.location.href
    );
}

/**
 * Handle popstate event (back/forward navigation)
 * Re-applies back prevention when user tries to navigate back
 */
function onPopState() {
    preventBack();
}

/**
 * Setup back prevention and track abandonment
 * Called during onMounted
 */
function _setupBackPrevention() {
    try { _cleanupAbandonment = useAbandonmentTracking( () => 'checkout' ); } catch { /* tracking — non-critical */ }
    preventBack();
    window.addEventListener( 'popstate', onPopState );
}

/**
 * Cleanup back prevention and abandonment tracking
 * Called during onUnmounted
 */
function _cleanupBackPrevention() {
    if ( _cleanupAbandonment ) _cleanupAbandonment();
    window.removeEventListener( 'popstate', onPopState );
}

// Abandonment tracking cleanup function holder
let _cleanupAbandonment;
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

/* ── Live preview host ───────────────────────── */
.sgate-preview-host {
    margin: 0.75rem 1rem 0.25rem;
    border: 0 !important;
    background: transparent !important;
    border-radius: 0 !important;
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
    display: flex;
    width: 100%;
    justify-content: center;
    padding: 1rem 0;
}

.sgate-footer__logos {
    display: block;
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
