<template>
    <div class="bg-white min-h-screen">
        <!-- Step Progress Bar -->
        <StepProgressBar :steps="steps" :current-step="currentStep" />

        <!-- Main Content -->
        <div class="w-full md:max-w-[80rem] px-0 md:px-4 mx-auto my-5 relative">
            <div class="flex flex-col lg:flex-row w-full">

                <!-- Left Content — 4/6 on desktop -->
                <div class="w-full flex flex-col lg:w-4/6 p-4">
                    <form id="newInsuranceForm" class="space-y-8" @submit.prevent="submitForm">

                        <!-- Section: تفاصيل السيارة -->
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">تفاصيل السيارة</h3>

                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <!-- Purpose of Use -->
                            <div class="w-full md:w-1/2">
                                <label for="purposeOfUse" class="block text-sm font-medium text-slate-600 mb-2">
                                    الغرض من الاستخدام
                                </label>
                                <AppSelect id="purposeOfUse" v-model="form.purposeOfUse"
                                    :options="purposeOptions" label="الغرض من الاستخدام" placeholder="اختر الغرض" variant="standard"
                                    dir="rtl" name="purposeOfUse" />
                            </div>

                            <!-- Estimated Vehicle Value — Floating label input -->
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex has-[:invalid]:border-red-500 has-[:disabled]:bg-slate-100 has-[:disabled]:cursor-not-allowed has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.estimatedValue ? 'border-red-500' : ''">
                                    <input id="estimatedValue" v-model="form.estimatedValue" type="text"
                                        inputmode="numeric" name="estimatedValue" autocomplete="off" placeholder=" " maxlength="10"
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 disabled:text-slate-400 disabled:cursor-not-allowed appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6 ltr-nums"
                                        @input="formatEstimatedValue" />
                                    <label for="estimatedValue"
                                        class="transition z-10 absolute typ-b2 peer-placeholder-shown:typ-b2 peer-focus-visible:typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-disabled:text-slate-400 peer-disabled:cursor-not-allowed peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        القيمة التقديرية للمركبة
                                    </label>
                                    <!-- SAR Symbol -->
                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 1124.14 1256.39" width="1em" height="1em"
                                        class="shrink-0 transition duration-300 size-5 peer-disabled:hidden w-auto self-center absolute end-3 top-1/2 -translate-y-1/2 text-slate-900 z-10">
                                        <path fill="currentColor"
                                            d="M699.62,1113.02h0c-20.06,44.48-33.32,92.75-38.4,143.37l424.51-90.24c20.06-44.47,33.31-92.75,38.4-143.37l-424.51,90.24Z">
                                        </path>
                                        <path fill="currentColor"
                                            d="M1085.73,895.8c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.33v-135.2l292.27-62.11c20.06-44.47,33.32-92.75,38.4-143.37l-330.68,70.27V66.13c-50.67,28.45-95.67,66.32-132.25,110.99v403.35l-132.25,28.11V0c-50.67,28.44-95.67,66.32-132.25,110.99v525.69l-295.91,62.88c-20.06,44.47-33.33,92.75-38.42,143.37l334.33-71.05v170.26l-358.3,76.14c-20.06,44.47-33.32,92.75-38.4,143.37l375.04-79.7c30.53-6.35,56.77-24.4,73.83-49.24l68.78-101.97v-.02c7.14-10.55,11.3-23.27,11.3-36.97v-149.98l132.25-28.11v270.4l424.53-90.28Z">
                                        </path>
                                    </svg>
                                </div>
                                <p v-if="errors.estimatedValue" class="text-xs text-red-500 mt-1">
                                    {{ errors.estimatedValue }}
                                </p>
                            </div>
                        </div>

                        <!-- Section: تفاصيل أخرى -->
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">تفاصيل أخرى</h3>

                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <!-- Full Name — Floating label input -->
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex has-[:invalid]:border-red-500 has-[:disabled]:bg-slate-100 has-[:disabled]:cursor-not-allowed has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.fullName ? 'border-red-500' : ''">
                                    <input id="fullName" v-model="form.fullName" type="text" name="fullName"
                                        autocomplete="name" placeholder=" "
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 disabled:text-slate-400 disabled:cursor-not-allowed appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6" />
                                    <label for="fullName"
                                        class="transition z-10 absolute typ-b2 peer-placeholder-shown:typ-b2 peer-focus-visible:typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-disabled:text-slate-400 peer-disabled:cursor-not-allowed peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-invalid:text-red-500 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        الإسم الكامل
                                    </label>
                                </div>
                                <p v-if="errors.fullName" class="text-xs text-red-500 mt-1">{{ errors.fullName }}
                                </p>
                            </div>

                            <!-- Phone Number — Floating label input -->
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex has-[:invalid]:border-red-500 has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.phone ? 'border-red-500' : ''">
                                    <input id="phone" v-model="form.phone" type="tel" name="phone" placeholder=" "
                                        autocomplete="tel" inputmode="numeric" maxlength="10"
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 disabled:text-slate-400 disabled:cursor-not-allowed appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6 ltr-nums" />
                                    <label for="phone"
                                        class="transition z-10 absolute typ-b2 peer-placeholder-shown:typ-b2 peer-focus-visible:typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-disabled:text-slate-400 peer-disabled:cursor-not-allowed peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-invalid:text-red-500 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        رقم الهاتف (أبشر)
                                    </label>
                                </div>
                                <p v-if="errors.phone" class="text-xs text-red-500 mt-1">{{ errors.phone }}</p>
                            </div>
                        </div>

                        <!-- Email — Floating label input -->
                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex has-[:invalid]:border-red-500 has-[:focus]:border-blue-600 border-[1px] border-slate-300 rounded-lg min-h-14 px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.email ? 'border-red-500' : ''">
                                    <input id="email" v-model="form.email" type="email" name="email" placeholder=" "
                                        autocomplete="email"
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full typ-b2 text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6 ltr-nums" />
                                    <label for="email"
                                        class="transition z-10 absolute typ-b2 peer-placeholder-shown:typ-b2 peer-focus-visible:typ-b2 text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        البريد الإلكتروني
                                    </label>
                                </div>
                                <p v-if="errors.email" class="text-xs text-red-500 mt-1">{{ errors.email }}</p>
                            </div>
                        </div>

                        <!-- Drivers Section -->
                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <div class="w-full border-2 border-dashed border-slate-300 rounded-lg p-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center">
                                        <img :src="driversIconSrc" alt="Driver" class="w-6 h-6" loading="lazy" />
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-slate-800">{{ drivers.length }} السائق</h4>
                                        <p v-if="drivers.length > 0" class="text-sm text-gray-600">
                                            {{ drivers.map(d => d.name).join(', ') }}
                                        </p>
                                    </div>
                                    <div>
                                        <button type="button" class="text-blue-600 hover:text-blue-700 font-medium text-sm cursor-pointer"
                                            @click="showDriversModal = true">
                                            تعديل السائقين
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Other Details (opens sheet) -->
                        <div class="flex flex-col md:flex-row gap-4 justify-between cursor-pointer"
                            @click="showOtherDetailsModal = true">
                            <div class="w-full border-2 border-dashed border-slate-300 rounded-lg p-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            class="w-6 h-6 text-slate-500 transition-transform">
                                            <path d="M12 5V19" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M5 12H19" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-medium text-slate-800">تفاصيل أخرى</h4>
                                        <p class="text-sm text-gray-600">بعض شركات التأمين تطلب معلومات إضافية.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recommendation Checkbox -->
                        <div class="w-full flex flex-wrap items-center gap-3">
                            <CheckboxRoot id="taminkomRecommendation"
                                :checked="form.taminkomRecommendation" name="taminkomRecommendation"
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 data-[state=checked]:bg-green-600 data-[state=checked]:border-green-600 transition-colors cursor-pointer"
                                @update:checked="(val) => form.taminkomRecommendation = val">
                                <CheckboxIndicator>
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </CheckboxIndicator>
                            </CheckboxRoot>
                            <label for="taminkomRecommendation" class="cursor-pointer text-sm text-slate-700">
                                أوافق على عرض التسعيرات الموصى بها من تأمينكم
                            </label>
                        </div>

                        <!-- Mobile: Info Tip Card -->
                        <div class="block lg:hidden">
                            <div v-if="selectedInsuranceTip" class="border-2 border-primary rounded-lg p-4">
                                <div class="flex gap-2 items-start mb-2">
                                    <img :src="infoIconSrc" alt="Info" class="w-6 h-6 mt-0.5" loading="lazy" />
                                    <span class="text-sm font-medium text-primary">{{ selectedInsuranceTitle }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                    {{ selectedInsuranceTip }}
                                </p>
                            </div>
                        </div>

                        <!-- Navigation: Previous / Next -->
                        <div
                            class="flex items-center gap-2 justify-between w-full md:static md:bg-transparent md:p-0 sticky bottom-0 right-0 left-0 bg-white z-50 p-4 border-t md:border-t-0 border-slate-100 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] md:shadow-none safe-area-bottom">
                            <button type="button" class="items-center gap-2 cursor-pointer hidden md:flex" @click="router.back()">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18"
                                    height="18" class="shrink-0 text-gray-600">
                                    <path d="M19 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M14 17L19 12" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14 7L19 12" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span class="text-gray-600 font-bold text-sm">السابق</span>
                            </button>

                            <button type="submit" :disabled="isSubmitting"
                                class="cursor-pointer whitespace-nowrap transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 text-center min-h-14 min-w-[10.625rem] px-6 text-base font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 disabled:bg-slate-400 disabled:cursor-not-allowed w-full md:w-auto inline-flex items-center justify-center gap-2">
                                <div class="flex items-center w-full gap-2 justify-center">
                                    <div class="w-full overflow-hidden self-center">
                                        <div>{{ isSubmitting ? 'جاري المعالجة...' : 'التالي' }}</div>
                                    </div>
                                    <span class="flex-shrink-0" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                            width="18" height="18" class="shrink-0 text-white">
                                            <path d="M5 12H19" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10 7L5 12" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                            <path d="M10 17L5 12" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </span>
                                </div>
                            </button>
                        </div>

                    </form>
                </div>

                <!-- Right Sidebar — 2/6 on desktop -->
                <div class="w-full lg:w-2/6 p-4">
                    <div class="hidden lg:block sticky top-4">
                        <!-- Info Tip Card -->
                        <div v-if="selectedInsuranceTip" class="border-2 border-primary rounded-lg p-4">
                            <div class="flex gap-2 items-start mb-2">
                                <img :src="infoIconSrc" alt="Info" class="w-6 h-6 mt-0.5" loading="lazy" />
                                <span class="text-sm font-medium text-primary">{{ selectedInsuranceTitle }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                {{ selectedInsuranceTip }}
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ═══════ Drivers List Sheet ═══════ -->
    <DriversSheet v-model:open="showDriversModal" :drivers="drivers"
        :add-policy-holder-as-driver="addPolicyHolderAsDriver" :no-drivers-src="noDriversSrc"
        @add-driver="addDriver" @remove-driver="removeDriver"
        @toggle-policy-holder="togglePolicyHolderAsDriver" />

    <!-- ═══════════ Other Details Sheet ═══════════ -->
    <OtherDetailsSheet v-model:open="showOtherDetailsModal" :other-details="otherDetails" />
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { CheckboxRoot, CheckboxIndicator } from 'radix-vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { useInsuranceStore } from '@/store';
import logger from '@/utils/logger';
import StepProgressBar from '../components/vehicle-details/StepProgressBar.vue';
import DriversSheet from '../components/vehicle-details/DriversSheet.vue';
import OtherDetailsSheet from '../components/vehicle-details/OtherDetailsSheet.vue';

const router = useRouter();
const { trackStep, resumeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();

//
const currentStep = ref( 1 ); // 0-indexed: step 0 = completed, step 1 = active

const steps = reactive( [
    { label: 'المعلومات الأساسية' },
    { label: 'تفاصيل السيارة' },
    { label: 'تفاصيل الوثيقة' },
] );

//
const _animojiSrc = new URL( '../../../images/motorapp/animoji.svg', import.meta.url ).href;
const infoIconSrc = new URL( '../../../images/motorapp/info-icon.svg', import.meta.url ).href;
const noDriversSrc = new URL( '../../../images/motorapp/no-drivers.webp', import.meta.url ).href;
const driversIconSrc = new URL( '../../../images/icons/drivers-icon.webp', import.meta.url ).href;

//
const purposeOptions = [
    { value: 'personal', label: 'شخصي' },
    { value: 'commercial', label: 'تجاري' },
    { value: 'rental', label: 'تأجير' },
    { value: 'rideshare', label: 'نقل الركاب أو كريم-أوبر' },
    { value: 'cargo', label: 'نقل بضائع' },
    { value: 'petroleum', label: 'نقل مشتقات نفطية' },
];

//
const form = reactive( {
    purposeOfUse: 'personal',
    estimatedValue: '',
    nationalId: '',
    sequenceNumber: '',
    vehicleMake: '',
    vehicleYear: '',
    fullName: '',
    phone: '',
    email: '',
    entityDiscount: false,
    taminkomRecommendation: true,
} );

const errors = reactive( {} );
const drivers = ref( [] );
const showOtherDetailsModal = ref( false );
const showDriversModal = ref( false );
const addPolicyHolderAsDriver = ref( false );
const isSubmitting = ref( false );

//
const otherDetails = reactive( {
    nightParking: '',
    expectedKM: '',
    transmissionType: '',
    accidentCounts: '',
    education: '',
    workNameAndLocation: '',
    childrenUnder16: '',
    carModification: 'no',
    modification: '',
    hasTrailAttach: 'no',
    trailEstimatedValue: '',
    foreignLicense: 'no',
    healthConditions: 'no',
    trafficViolations: 'no',
} );

//
const selectedInsuranceTitle = ref( 'تأمين أو تجديد تأمين السيارة' );
const selectedInsuranceTip = ref(
    'تأمين السيارة يحميك ماليًا من الحوادث والسرقة والمواقف غير المتوقعة. فهم التفاصيل يساعدك على اتخاذ قرار صحيح سواء كنت تشتري أو تجدد التأمين.'
);

//
function formatEstimatedValue( e ) {
    const raw = e.target.value.replace( /[^\d]/g, '' );
    form.estimatedValue = raw;
}

//
function clearErrors() {
    Object.keys( errors ).forEach( key => delete errors[ key ] );
}

function validate() {
    clearErrors();
    let valid = true;

    if ( !form.fullName || form.fullName.trim().length < 4 ) {
        errors.fullName = 'يرجى إدخال الإسم الكامل (4 أحرف على الأقل)';
        valid = false;
    }

    if ( !form.phone || !form.phone.trim() ) {
        errors.phone = 'رقم الهاتف مطلوب';
        valid = false;
    } else {
        const phoneDigits = form.phone.replace( /\s/g, '' );
        if ( !/^05\d{8}$/.test( phoneDigits ) ) {
            errors.phone = 'رقم الجوال يجب أن يبدأ بـ 05 ويتكون من 10 أرقام';
            valid = false;
        }
    }

    if ( !form.email || !form.email.trim() ) {
        errors.email = 'البريد الإلكتروني مطلوب';
        valid = false;
    } else if ( !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( form.email.trim() ) ) {
        errors.email = 'يرجى إدخال بريد إلكتروني صحيح';
        valid = false;
    }

    const estimatedVal = parseInt( form.estimatedValue );
    if ( !form.estimatedValue || estimatedVal < 5000 ) {
        errors.estimatedValue = 'القيمة التقديرية يجب أن تكون 5,000 ر.س على الأقل';
        valid = false;
    } else if ( estimatedVal > 99999999 ) {
        errors.estimatedValue = 'القيمة التقديرية لا يمكن أن تتجاوز 99,999,999 ر.س';
        valid = false;
    }

    return valid;
}

//
let driverIdCounter = 1;

function addDriver() {
    driverIdCounter++;
    drivers.value.push( {
        id: driverIdCounter,
        name: '',
        nationalId: '',
        birthDateH: '',
        education: '',
        drivingPercentage: '',
    } );
}

function removeDriver( index ) {
    drivers.value.splice( index, 1 );
}

function togglePolicyHolderAsDriver( checked ) {
    addPolicyHolderAsDriver.value = checked;
    if ( checked ) {
        // Add the policy holder as the first driver if not already present
        const exists = drivers.value.some( d => d.isPolicyHolder );
        if ( !exists ) {
            drivers.value.unshift( {
                id: ++driverIdCounter,
                name: form.fullName || 'مالك الوثيقة',
                nationalId: form.nationalId || '',
                birthDateH: '',
                education: '',
                drivingPercentage: '100',
                isPolicyHolder: true,
            } );
        }
    } else {
        // Remove the policy holder driver entry
        drivers.value = drivers.value.filter( d => !d.isPolicyHolder );
    }
}

//
async function submitForm() {
    if ( isSubmitting.value ) return;
    if ( !validate() ) return;

    isSubmitting.value = true;
    try {

    // Save to sessionStorage
    const vehicleDetails = {
        ...form,
        email: form.email,
        drivers: drivers.value,
        otherDetails: { ...otherDetails },
    };
    sessionStorage.setItem( 'vehicleDetails', JSON.stringify( vehicleDetails ) );

    // حفظ البيانات في المتجر المركزي
    insuranceStore.setVehicleData( {
        purposeOfUse: form.purposeOfUse,
        estimatedValue: form.estimatedValue,
        sequenceNumber: form.sequenceNumber,
        make: form.vehicleMake,
        year: form.vehicleYear,
        carModification: otherDetails.carModification,
        modification: otherDetails.modification,
        hasTrailer: otherDetails.hasTrailAttach,
        trailerValue: otherDetails.trailEstimatedValue,
        transmissionType: otherDetails.transmissionType,
    } );
    insuranceStore.setDriverData( {
        nationalId: form.nationalId,
        fullName: form.fullName,
        phone: form.phone,
        email: form.email,
        education: otherDetails.education,
        accidentCounts: otherDetails.accidentCounts,
        trafficViolations: otherDetails.trafficViolations,
        healthConditions: otherDetails.healthConditions,
        foreignLicense: otherDetails.foreignLicense,
        nightParking: otherDetails.nightParking,
        expectedKM: otherDetails.expectedKM,
        childrenUnder16: otherDetails.childrenUnder16,
        workLocation: otherDetails.workNameAndLocation,
        drivers: drivers.value,
    } );

    // Send form data to backend for admin dashboard tracking
    try {
        const { default: request } = await import( '@/api/request' );
        await request.post( '/customer/track-details', {
            full_name: form.fullName,
            phone: form.phone,
            email: form.email || null,
            purpose_of_use: form.purposeOfUse,
            estimated_value: form.estimatedValue ? parseInt( form.estimatedValue ) : null,
            vehicle_type: insuranceStore.vehicle.makeName || form.vehicleMake || null,
            current_page: '/motorapp/vehicleDetails',
            has_additional_driver: drivers.value && drivers.value.length > 0,
            additional_driver_name: drivers.value?.[0]?.name || null,
            additional_driver_national_id: drivers.value?.[0]?.nationalId || null,
            additional_driver_birth_date: drivers.value?.[0]?.birthDate || null,
            usage_purpose: form.purposeOfUse || null,
            // تفاصيل أخرى (extra details)
            night_parking: otherDetails.nightParking || null,
            expected_km: otherDetails.expectedKM || null,
            transmission_type: otherDetails.transmissionType || null,
            accident_counts: otherDetails.accidentCounts || null,
            education: otherDetails.education || null,
            work_location: otherDetails.workNameAndLocation || null,
            children_under_16: otherDetails.childrenUnder16 || null,
            car_modification: otherDetails.carModification || null,
            modification_desc: otherDetails.modification || null,
            has_trailer: otherDetails.hasTrailAttach || null,
            trailer_value: otherDetails.trailEstimatedValue || null,
            foreign_license: otherDetails.foreignLicense || null,
            health_conditions: otherDetails.healthConditions || null,
            traffic_violations: otherDetails.trafficViolations || null,
            drivers: drivers.value && drivers.value.length > 0 ? drivers.value : null,
        } );
    } catch ( err ) {
        logger.warn( '[VehicleDetails] Tracking failed:', err.message );
    }

    // Track step transition
    await trackStep( 'vehicle', 3, vehicleDetails, 'next' );

    // Navigate to policy details page
    router.push( { name: 'policyDetails' } );
    } finally {
        isSubmitting.value = false;
    }
}

//
function restoreFormState() {
    // Pull data from MotorApp session
    const vehicleForm = sessionStorage.getItem( 'vehicleForm' );
    if ( vehicleForm ) {
        try {
            const parsed = JSON.parse( vehicleForm );
            if ( parsed.vehicleMake ) form.vehicleMake = parsed.vehicleMake;
            if ( parsed.vehicleYear ) form.vehicleYear = parsed.vehicleYear;
            if ( parsed.nationalId ) form.nationalId = parsed.nationalId;
            if ( parsed.fullName ) form.fullName = parsed.fullName;
            if ( parsed.phone ) form.phone = parsed.phone;
        } catch { /* ignore */ }
    }

    // Restore nationalId from BasicDetailsPage if available
    const basicSaved = sessionStorage.getItem( 'basicDetails' );
    if ( basicSaved ) {
        try {
            const parsed = JSON.parse( basicSaved );
            if ( parsed.identityNumber && !form.nationalId ) form.nationalId = parsed.identityNumber;
            if ( parsed.sequenceNumber && !form.sequenceNumber ) form.sequenceNumber = parsed.sequenceNumber;
        } catch { /* ignore */ }
    }

    // Restore own data
    const saved = sessionStorage.getItem( 'vehicleDetails' );
    if ( saved ) {
        try {
            const parsed = JSON.parse( saved );
            Object.assign( form, parsed );
            if ( parsed.drivers ) drivers.value = parsed.drivers;
            if ( parsed.otherDetails ) Object.assign( otherDetails, parsed.otherDetails );
        } catch { /* ignore */ }
    }

    // Restore insurance type info from MotorApp selection
    const insuranceType = sessionStorage.getItem( 'selectedInsuranceType' );
    if ( insuranceType ) {
        try {
            const parsed = JSON.parse( insuranceType );
            if ( parsed.title ) selectedInsuranceTitle.value = parsed.title;
            if ( parsed.tip ) selectedInsuranceTip.value = parsed.tip;
        } catch { /* ignore */ }
    }
}

//
onMounted( () => {
    restoreFormState();
    resumeSession( 'vehicleDetails' );

    // Prefetch next step chunk (heavy: radix-vue Calendar + @internationalized/date)
    import( '@/car.insurance/flow/PolicyDetailsPage.vue' ).catch( () => {} );
} );
</script>
