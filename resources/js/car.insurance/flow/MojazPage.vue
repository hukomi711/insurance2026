<template>
    <div class="bg-white min-h-screen">
        <!-- Step Progress Bar — Mobile (colored bars only) -->
        <div class="sm:hidden w-full">
            <div class="flex w-full">
                <div v-for="(step, index) in steps" :key="'m-' + index" class="flex-1 h-1" :class="index <= currentStep
                    ? 'bg-secondary'
                    : 'bg-slate-100'
                    "></div>
            </div>
        </div>

        <!-- Step Progress Bar — Desktop -->
        <div dir="rtl" class="overflow-hidden hidden sm:block w-full">
            <div class="flex items-center">
                <div v-for="(step, index) in steps" :key="'d-' + index"
                    class="flex-1 flex flex-col items-center min-w-32">
                    <div class="flex items-center gap-1 lg:gap-4 lg:text-sm text-xs lg:py-4 py-1 border-b-2 w-full justify-center cursor-pointer transition-colors"
                        :class="index <= currentStep
                            ? 'text-secondary border-b-secondary'
                            : 'text-slate-400 border-b-slate-100'
                            ">
                        <!-- Checkmark for completed steps -->
                        <i v-if="index < currentStep" class="fa-solid fa-check shrink-0 size-4 text-green-600"></i>
                        <!-- Step number -->
                        <span v-else
                            class="inline-flex justify-center items-center size-6 rounded-full text-xs font-bold border-2 shrink-0"
                            :class="index <= currentStep
                                ? 'border-secondary text-secondary bg-secondary/10'
                                : 'border-slate-300 text-slate-400'
                                ">
                            {{ index + 1 }}
                        </span>
                        <span class="whitespace-nowrap pe-4" :class="index <= currentStep
                            ? 'text-secondary'
                            : 'text-slate-400'
                            ">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- STEP 1 — البيانات الأساسية (Basic Data)                     -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <template v-if="currentStep === 0">
            <div class="w-full md:max-w-[80rem] px-0 md:px-4 mx-auto my-5 relative">
                <div class="flex flex-col items-center px-3 md:px-0">

                    <!-- Header Section -->
                    <div
                        class="w-full md:w-4/5 lg:w-2/3 mx-auto relative mb-4 pb-2 border-0 border-b border-solid border-gray-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <span class="font-bold text-lg sm:text-xl">معلومات المركبة من موجز</span>
                                <p class="w-10/12 text-sm text-gray-600 mt-2 leading-relaxed">
                                    خدمة متطورة تقدم المعلومات المتوفرة عن أي مركبة مستعملة منذ تاريخ دخولها إلى
                                    المملكة
                                    العربية السعودية،
                                    ويساعد «موجَز» الباحثين عن سيارات مستعملة على اتخاذ قرار الشراء بناءاً على معلومات
                                    موثوقة
                                    المصدر.
                                </p>
                            </div>
                            <img :src="mojazLogoSrc" alt="معلومات المركبة من موجز"
                                class="w-16 h-16 object-contain shrink-0" loading="lazy" />
                        </div>
                    </div>

                    <!-- Form Section -->
                    <form class="w-full md:w-4/5 lg:w-2/3 mx-auto mt-8" @submit.prevent="handleSubmit">
                        <div class="flex flex-wrap -mx-1">
                            <!-- الرقم التسلسلى -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="sequenceNo" class="block text-sm font-bold text-slate-700 mb-1.5">
                                    الرقم التسلسلى
                                </label>
                                <input id="sequenceNo" v-model="form.sequenceNo" type="tel" name="sequenceNo"
                                    placeholder="الرقم التسلسلى" maxlength="10" dir="rtl" autocomplete="off"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all bg-slate-50 hover:bg-white"
                                    :class="errors.sequenceNo ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="errors.sequenceNo" class="text-red-500 text-xs mt-1">{{ errors.sequenceNo }}
                                </p>
                            </div>

                            <!-- رقم الجوال -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="mobileNumber" class="block text-sm font-bold text-slate-700 mb-1.5">
                                    رقم الجوال
                                    <button type="button" class="inline-flex ms-1 align-middle"
                                        title="رقم الجوال المسجل">
                                        <i class="fa-solid fa-circle-info size-4 text-slate-400"></i>
                                    </button>
                                </label>
                                <input id="mobileNumber" v-model="form.mobileNumber" type="tel" name="mobileNumber"
                                    placeholder="05XXXXXXXX" maxlength="10" dir="rtl" inputmode="numeric"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all bg-slate-50 hover:bg-white ltr-nums"
                                    :class="errors.mobileNumber ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="errors.mobileNumber" class="text-red-500 text-xs mt-1">
                                    {{ errors.mobileNumber }}
                                </p>
                            </div>

                            <!-- سنة الصنع -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="manufactureYear"
                                    class="block text-sm font-bold text-slate-700 mb-1.5">سنة الصنع</label>
                                <AppSelect id="manufactureYear" v-model="form.manufactureYear"
                                    :options="manufactureYearOptions" placeholder="اختر" variant="standard" dir="rtl"
                                    :scroll-buttons="true" name="manufactureYear" item-extra-class="ltr-nums"
                                    :error="!!errors.manufactureYear" />
                                <p v-if="errors.manufactureYear" class="text-red-500 text-xs mt-1">
                                    {{ errors.manufactureYear }}
                                </p>
                            </div>

                            <!-- الشركة المصنعة -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="vehicleMake"
                                    class="block text-sm font-bold text-slate-700 mb-1.5">الشركة المصنعة</label>
                                <AppSelect id="vehicleMake" v-model="form.vehicleMake"
                                    :options="makeOptions" placeholder="اختر الشركة المصنعة" variant="standard"
                                    dir="rtl" name="vehicleMake"
                                    :error="!!errors.vehicleMake" />
                                <p v-if="errors.vehicleMake" class="text-red-500 text-xs mt-1">
                                    {{ errors.vehicleMake }}
                                </p>
                            </div>

                            <!-- الموديل -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="vehicleModel"
                                    class="block text-sm font-bold text-slate-700 mb-1.5">الموديل</label>
                                <AppSelect id="vehicleModel" v-model="form.vehicleModel"
                                    :options="modelOptions" placeholder="اختر الموديل" variant="standard"
                                    dir="rtl" name="vehicleModel"
                                    :disabled="!form.vehicleMake"
                                    :error="!!errors.vehicleModel" />
                                <p v-if="errors.vehicleModel" class="text-red-500 text-xs mt-1">
                                    {{ errors.vehicleModel }}
                                </p>
                            </div>

                            <!-- رقم اللوحة -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="plateNo" class="block text-sm font-bold text-slate-700 mb-1.5">
                                    رقم اللوحة
                                </label>
                                <input id="plateNo" v-model="form.plateNo" type="text" name="plateNo"
                                    placeholder="مثال: 1234 أ ب ج" dir="rtl" autocomplete="off"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all bg-slate-50 hover:bg-white"
                                    :class="errors.plateNo ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="errors.plateNo" class="text-red-500 text-xs mt-1">{{ errors.plateNo }}</p>
                            </div>

                            <!-- رقم الهيكل (VIN) -->
                            <div class="w-full md:w-6/12 px-1 mb-4">
                                <label for="vin" class="block text-sm font-bold text-slate-700 mb-1.5">
                                    رقم الهيكل (VIN)
                                </label>
                                <input id="vin" v-model="form.vin" type="text" name="vin"
                                    placeholder="رقم الهيكل المكون من 17 خانة" dir="ltr" maxlength="17"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all bg-slate-50 hover:bg-white ltr-nums text-left uppercase"
                                    :class="errors.vin ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="errors.vin" class="text-red-500 text-xs mt-1">{{ errors.vin }}</p>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Service Cost -->
                <div class="flex justify-center font-bold text-secondary mx-4 mb-3 mt-6">
                    <span>تكلفة الخدمة: </span>
                    <span class="mx-1 flex items-center gap-1 ltr-nums">
                        119
                        <SarIcon className="size-4 text-secondary" />
                    </span>
                </div>
            </div>

            <!-- Bottom Action Bar — Step 1 -->
            <div class="border-0 border-t border-solid border-slate-200 bg-slate-50 px-3">
                <div class="box">
                    <div class="flex items-center py-3 gap-2 justify-between">
                        <router-link to="/motorapp"
                            class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-800 transition-colors">
                            <i class="fa-solid fa-arrow-right shrink-0 text-lg"></i>
                            <span class="font-bold text-sm">السابق</span>
                        </router-link>
                        <button :disabled="isSubmitting" class="min-w-[150px] disabled:cursor-not-allowed disabled:opacity-60 bg-secondary hover:bg-secondary/90 text-white py-3 px-6 rounded-xl font-bold text-base transition-colors cursor-pointer"
                            @click="handleSubmit">
                            {{ isSubmitting ? 'جاري المعالجة...' : 'التالي' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- ═══════════════════════════════════════════════════════════ -->
        <!-- STEP 2 — الملخص والدفع (Summary & Payment)                 -->
        <!-- ═══════════════════════════════════════════════════════════ -->
        <template v-else-if="currentStep === 1">
            <div class="w-full md:max-w-[80rem] px-0 md:px-4 mx-auto my-5 relative">
                <div class="flex flex-col items-center px-3 md:px-0">
                    <div class="w-full mb-5">
                        <!-- Section Title -->
                        <span
                            class="font-bold text-lg sm:text-xl pb-4 border-0 border-b border-solid border-gray-300 block">فحص
                            تاريخ المركبة (موجز)</span>

                        <!-- Vehicle Info -->
                        <div class="flex flex-col">
                            <span class="font-bold text-lg sm:text-xl mt-4 mb-4">معلومات المركبة</span>

                            <!-- Vehicle Summary Card -->
                            <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                                <!-- Card Header — make logo + details -->
                                <div class="flex items-start gap-4 p-4 bg-slate-50/60">
                                    <div
                                        class="shrink-0 flex items-center justify-center w-14 h-14 rounded-xl bg-white border border-slate-100 p-2">
                                        <!-- Vehicle Make Icon -->
                                        <i class="fa-solid fa-truck text-4xl text-slate-500"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-base text-slate-800">
                                            {{ vehicleInfo.makeName }} {{ vehicleInfo.modelName }}
                                            <span class="ltr-nums">{{ vehicleInfo.year }}</span>
                                        </h3>
                                        <div class="flex flex-wrap gap-x-5 gap-y-1 mt-2 text-sm text-slate-600">
                                            <div class="flex flex-wrap gap-1">
                                                <span>الرقم التسلسلى:</span>
                                                <b class="ltr-nums">{{ vehicleInfo.sequenceNo }}</b>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <span>رقم اللوحة:</span>
                                                <b class="ltr-nums">{{ vehicleInfo.plateNo }}</b>
                                            </div>
                                            <div class="flex flex-wrap gap-1">
                                                <span>رقم الهيكل:</span>
                                                <b class="ltr-nums" dir="ltr">{{ vehicleInfo.vin }}</b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Mobile notice -->
                                <div class="px-4 py-3 border-0 border-t border-solid border-slate-100">
                                    <p class="text-sm font-bold text-secondary flex items-center gap-2" dir="ltr">
                                        <i class="fa-solid fa-mobile-screen-button size-4 shrink-0 text-secondary"></i>
                                        <span class="ltr-nums">{{ vehicleInfo.mobileNumber }}</span>
                                        <span>سيتم إرسال نسخة من التقرير الى رقم الهاتف المسجل</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Section -->
                        <div class="mt-6">
                            <h3 class="font-bold text-lg mb-4">اختر طريقة الدفع</h3>
                            <div class="flex flex-wrap gap-3">
                                <!-- Mada -->
                                <label class="payment-option" :class="{ 'payment-option--active': paymentMethod === 'mada' }">
                                    <input v-model="paymentMethod" type="radio" value="mada" class="sr-only" />
                                    <span class="inline-flex mt-0.5">مدى</span>
                                    <svg class="h-6 w-auto" viewBox="0 0 60 40" fill="none">
                                        <rect width="60" height="40" rx="6" fill="#fff"
                                            stroke="#e2e8f0" />
                                        <path d="M15 20h5l3-6 3 6h4l-5-10h-4l-3 5-3-5h-4l5 10z"
                                            fill="#00857c" />
                                        <path d="M35 14h3v12h-3z" fill="#00857c" />
                                        <circle cx="42" cy="20" r="4" fill="#00857c" />
                                    </svg>
                                </label>
                                <!-- Mastercard -->
                                <label class="payment-option"
                                    :class="{ 'payment-option--active': paymentMethod === 'mastercard' }">
                                    <input v-model="paymentMethod" type="radio" value="mastercard" class="sr-only" />
                                    <span class="inline-flex mt-0.5">ماستركارد</span>
                                    <svg class="h-6 w-auto" viewBox="0 0 60 40" fill="none">
                                        <rect width="60" height="40" rx="6" fill="#fff"
                                            stroke="#e2e8f0" />
                                        <circle cx="24" cy="20" r="9" fill="#eb001b"
                                            opacity=".8" />
                                        <circle cx="36" cy="20" r="9" fill="#f79e1b"
                                            opacity=".8" />
                                    </svg>
                                </label>
                                <!-- Visa -->
                                <label class="payment-option"
                                    :class="{ 'payment-option--active': paymentMethod === 'visa' }">
                                    <input v-model="paymentMethod" type="radio" value="visa" class="sr-only" />
                                    <span class="inline-flex mt-0.5">فيزا</span>
                                    <svg class="h-6 w-auto" viewBox="0 0 60 40" fill="none">
                                        <rect width="60" height="40" rx="6" fill="#fff"
                                            stroke="#e2e8f0" />
                                        <path d="M22 26l3-12h4l-3 12h-4z" fill="#1a1f71" />
                                        <path
                                            d="M36 14.2c-1-.4-2.2-.7-3.8-.7-4 0-6.8 2-6.8 4.9 0 2.2 2 3.3 3.6 4 1.6.7 2.1 1.2 2.1 1.8 0 1-1.3 1.4-2.4 1.4-1.6 0-2.5-.2-3.8-.8l-.6 3c.9.4 2.5.7 4.2.7 4.2 0 7-2 7-5.1 0-3.9-5.6-4.1-5.6-5.8 0-.5.5-1 2-1 1.2 0 2.2.2 3 .5l.6-2.9z"
                                            fill="#1a1f71" />
                                        <path d="M40 14l-3 12h3.5l3-12H40z" fill="#1a1f71" />
                                    </svg>
                                </label>
                            </div>

                            <!-- Service Cost -->
                            <div class="flex font-bold text-secondary mt-5 justify-end">
                                <span>تكلفة الخدمة: </span>
                                <span class="mx-1 flex items-center gap-1 ltr-nums">
                                    119
                                    <SarIcon className="size-4 text-secondary" />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar — Step 2 -->
            <div class="border-0 border-t border-solid border-slate-200 bg-slate-50 px-3">
                <div class="box">
                    <div class="flex items-center py-3 gap-2 justify-between">
                        <button type="button" class="flex items-center gap-2 cursor-pointer text-slate-600 hover:text-slate-800 transition-colors min-w-[150px] border border-slate-300 bg-white py-3 px-6 rounded-xl font-bold text-sm justify-center"
                            @click="goBackToStep1">
                            السابق
                        </button>
                        <button :disabled="isPaying" class="min-w-[150px] disabled:cursor-not-allowed disabled:opacity-60 bg-secondary hover:bg-secondary/90 text-white py-3 px-6 rounded-xl font-bold text-base transition-colors cursor-pointer"
                            @click="handlePayment">
                            {{ isPaying ? 'جاري الدفع...' : 'ادفع الآن' }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import AppSelect from '@/components/ui/AppSelect.vue';
import SarIcon from '@/components/SarIcon.vue';
import { vehicleMakes } from '@/data';
import { trackMojazData } from '@/api/paymentApi';
import logger from '@/utils/logger';

const router = useRouter();
const route = useRoute();

// Steps
const currentStep = ref( 0 );
const steps = [
    { label: 'البيانات الأساسية' },
    { label: 'الملخص والدفع' },
    { label: 'تحميل التقرير' },
];

// ──────────────────────────────────────────────
// Step 1 — Form State
// ──────────────────────────────────────────────
const form = reactive( {
    sequenceNo: '',
    mobileNumber: '',
    manufactureYear: '',
    vehicleMake: '',
    vehicleModel: '',
    plateNo: '',
    vin: '',
} );

const errors = reactive( {
    sequenceNo: '',
    mobileNumber: '',
    manufactureYear: '',
    vehicleMake: '',
    vehicleModel: '',
    plateNo: '',
    vin: '',
} );

// Vehicle make/model options
const makeOptions = vehicleMakes.map( m => ( { value: String( m.id ), label: m.nameAr } ) );

const modelOptions = computed( () => {
    if ( !form.vehicleMake ) return [];
    const make = vehicleMakes.find( m => String( m.id ) === form.vehicleMake );
    return make ? make.models.map( ( model ) => ( { value: model, label: model } ) ) : [];
} );

// Reset model when make changes
watch( () => form.vehicleMake, () => {
    form.vehicleModel = '';
} );

const isSubmitting = ref( false );

// Manufacture year options (current year down to 2000)
const currentGregorianYear = new Date().getFullYear();
const manufactureYearOptions = Array.from( { length: currentGregorianYear - 1999 }, ( _, i ) => {
    const year = currentGregorianYear - i;
    return { value: String( year ), label: String( year ) };
} );

// Mojaz logo
const mojazLogoSrc = new URL( '../../../images/motorapp/mojaz.webp', import.meta.url ).href;

// ──────────────────────────────────────────────
// Step 2 — Vehicle Info & Payment
// ──────────────────────────────────────────────
const vehicleInfo = reactive( {
    makeName: '',
    modelName: '',
    year: '',
    sequenceNo: '',
    plateNo: '',
    vin: '',
    mobileNumber: '',
} );

const paymentMethod = ref( 'mada' );
const isPaying = ref( false );

/** Load step 2 data from sessionStorage */
function loadStep2Data() {
    const raw = sessionStorage.getItem( 'mojazRequest' );
    if ( !raw ) {
        router.replace( { name: 'mojaz', query: { step: '1' } } );
        return;
    }
    const data = JSON.parse( raw );
    Object.assign( vehicleInfo, {
        makeName: data.makeName || '',
        modelName: data.modelName || '',
        year: data.manufactureYear || '',
        sequenceNo: data.sequenceNo || '',
        plateNo: data.plateNo || '',
        vin: data.vin || '',
        mobileNumber: data.mobileNumber || '',
    } );
}

// ──────────────────────────────────────────────
// Navigation & Lifecycle
// ──────────────────────────────────────────────

/** Sync currentStep from route query */
function syncStepFromRoute() {
    const stepParam = route.query.step;
    if ( stepParam === '2' ) {
        currentStep.value = 1;
        loadStep2Data();
    } else {
        currentStep.value = 0;
    }
}

onMounted( syncStepFromRoute );
watch( () => route.query.step, syncStepFromRoute );

// ──────────────────────────────────────────────
// Step 1 — Validation & Submit
// ──────────────────────────────────────────────
function validate() {
    let valid = true;
    errors.sequenceNo = '';
    errors.mobileNumber = '';
    errors.manufactureYear = '';
    errors.vehicleMake = '';
    errors.vehicleModel = '';
    errors.plateNo = '';
    errors.vin = '';

    if ( !form.sequenceNo.trim() ) {
        errors.sequenceNo = 'يرجى إدخال الرقم التسلسلي';
        valid = false;
    } else if ( !/^\d{1,10}$/.test( form.sequenceNo.trim() ) ) {
        errors.sequenceNo = 'الرقم التسلسلي يجب أن يكون أرقام فقط';
        valid = false;
    }

    if ( !form.mobileNumber.trim() ) {
        errors.mobileNumber = 'يرجى إدخال رقم الجوال';
        valid = false;
    } else if ( !/^05\d{8}$/.test( form.mobileNumber.trim() ) ) {
        errors.mobileNumber = 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام';
        valid = false;
    }

    if ( !form.manufactureYear ) {
        errors.manufactureYear = 'يرجى اختيار سنة الصنع';
        valid = false;
    }

    if ( !form.vehicleMake ) {
        errors.vehicleMake = 'يرجى اختيار الشركة المصنعة';
        valid = false;
    }

    if ( !form.vehicleModel ) {
        errors.vehicleModel = 'يرجى اختيار الموديل';
        valid = false;
    }

    if ( !form.plateNo.trim() ) {
        errors.plateNo = 'يرجى إدخال رقم اللوحة';
        valid = false;
    }

    if ( !form.vin.trim() ) {
        errors.vin = 'يرجى إدخال رقم الهيكل';
        valid = false;
    } else if ( form.vin.trim().length < 17 ) {
        errors.vin = 'رقم الهيكل يجب أن يتكون من 17 خانة';
        valid = false;
    }

    return valid;
}

function handleSubmit() {
    if ( !validate() ) return;

    isSubmitting.value = true;

    // Resolve make name from id
    const selectedMake = vehicleMakes.find( m => String( m.id ) === form.vehicleMake );
    const makeName = selectedMake ? selectedMake.nameAr : '';

    const mojazData = {
        sequenceNo: form.sequenceNo.trim(),
        mobileNumber: form.mobileNumber.trim(),
        manufactureYear: form.manufactureYear,
        makeName,
        modelName: form.vehicleModel,
        plateNo: form.plateNo.trim(),
        vin: form.vin.trim().toUpperCase(),
    };

    // Save form data to session for Step 2 display
    sessionStorage.setItem( 'mojazRequest', JSON.stringify( mojazData ) );

    // Send data to backend so admin dashboard sees it immediately
    trackMojazData( {
        sequence_number: mojazData.sequenceNo,
        mobile_number: mojazData.mobileNumber,
        manufacturing_year: mojazData.manufactureYear,
        vehicle_make: mojazData.makeName,
        vehicle_model: mojazData.modelName,
        plate_number: mojazData.plateNo,
        vin: mojazData.vin,
        current_page: '/motorapp/Home/Mojaz',
    } ).catch( ( e ) => {
        // Non-blocking — data is also in sessionStorage
        logger.warn( '[Mojaz] Failed to track data:', e.message );
    } ).finally( () => {
        isSubmitting.value = false;
        router.push( { name: 'mojaz', query: { step: '2' } } );
    } );
}

// ──────────────────────────────────────────────
// Step 2 — Payment & Back
// ──────────────────────────────────────────────
function goBackToStep1() {
    router.push( { name: 'mojaz', query: { step: '1' } } );
}

function handlePayment() {
    if ( !paymentMethod.value ) return;

    isPaying.value = true;

    // Save payment choice
    sessionStorage.setItem( 'mojazPayment', JSON.stringify( {
        method: paymentMethod.value,
        amount: 119,
    } ) );

    // Navigate to payment gateway page
    setTimeout( () => {
        isPaying.value = false;
        router.push( { name: 'mojaz-payment' } );
    }, 500 );
}
</script>

<style scoped>
.payment-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.75rem;
    border: 2px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
    background: #f8fafc;
    font-weight: 600;
    font-size: 0.875rem;
}

.payment-option:hover {
    border-color: #cbd5e1;
    background: #fff;
}

.payment-option--active {
    border-color: var(--color-secondary);
    background: #fff;
    box-shadow: 0 0 0 1px var(--color-secondary);
}
</style>
