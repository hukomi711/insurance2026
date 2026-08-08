<template>
    <div class="bg-white min-h-screen" dir="rtl">
        <!-- Step Progress Bar — Mobile (colored bars only) -->
        <div class="sm:hidden w-full">
            <div class="flex items-center w-full">
                <div v-for="(step, index) in steps" :key="'m-' + index" class="flex-1 flex flex-col items-center">
                    <div class="flex border-b-2 w-full" :class="getStepMobileClass(index)"></div>
                </div>
            </div>
        </div>

        <!-- Step Progress Bar — Desktop (labeled tabs) -->
        <div dir="rtl" class="overflow-hidden hidden sm:block w-full">
            <div class="flex items-center">
                <div v-for="(step, index) in steps" :key="'d-' + index"
                    class="flex-1 flex flex-col items-center min-w-32">
                    <div class="flex items-center gap-1 lg:gap-4 lg:text-sm text-xs lg:py-4 py-1 border-b-2 w-full justify-center cursor-pointer transition-colors"
                        :class="getStepDesktopClass(index)">
                        <!-- Checkmark for completed steps -->
                        <svg v-if="index < currentStep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" width="1em" height="1em" class="shrink-0 size-4 text-green-600">
                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span class="whitespace-nowrap pe-4" :class="getStepLabelClass(index)">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="w-full md:max-w-[80rem] px-0 md:px-4 mx-auto my-5 relative">
            <div class="flex flex-col lg:flex-row w-full">

                <!-- Mobile: User + Vehicle card -->
                <div class="w-full lg:hidden md:p-4">
                    <div class="flex flex-col border-2 border-blue-100 md:rounded-lg bg-blue-50 p-4 mb-4">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-3 mb-4">
                                <!-- Back arrow (mobile only) -->
                                <div class="border border-slate-300 rounded-full flex md:hidden cursor-pointer items-center justify-center"
                                    @click="router.push({ name: 'vehicleDetails' })">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="shrink-0 w-6 h-6 m-2 text-slate-600">
                                        <path d="M19 12H5" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M14 17L19 12" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M14 7L19 12" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <!-- Avatar + Info -->
                                <div class="w-12 h-12">
                                    <img :src="animojiSrc" alt="User avatar"
                                        class="w-full h-full rounded-full object-cover" loading="lazy" width="48" height="48" />
                                </div>
                                <div>
                                    <h6 class="font-medium text-sm">
                                        <span dir="ltr">{{ userFullName }}</span>
                                    </h6>
                                    <p class="text-xs text-gray-600 mt-1">الهوية: <span dir="ltr">{{ userNationalId
                                    }}</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Left Content — 4/6 on desktop -->
                <div class="w-full flex flex-col lg:w-4/6 p-4">
                    <form ref="policyFormRef" class="space-y-8" @submit.prevent="submitForm">

                        <!-- Section: اختر تاريخ بدء الوثيقة -->
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">اختر تاريخ بدء الوثيقة</h3>

                        <PolicyDatePicker v-model="form.policyStartDate" :error="errors.policyStartDate" />

                        <!-- Section: اختر المنطقة والمدينة -->
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">اختر المنطقة والمدينة</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" dir="rtl">
                            <div class="flex flex-col gap-1">
                                <AppSelect
                                    id="region"
                                    v-model="form.region"
                                    :options="regionOptions"
                                    label="المنطقة"
                                    placeholder="اختر المنطقة"
                                    variant="standard"
                                    dir="rtl"
                                    name="region"
                                    autocomplete="address-level1"
                                />
                                <p v-if="errors.region" class="text-xs text-red-500 mt-1">
                                    {{ errors.region }}
                                </p>
                            </div>
                            <div class="flex flex-col gap-1">
                                <AppSelect
                                    id="city"
                                    v-model="form.city"
                                    :options="cityOptions"
                                    label="المدينة"
                                    placeholder="اختر المدينة"
                                    variant="standard"
                                    dir="rtl"
                                    name="city"
                                    autocomplete="address-level2"
                                    :disabled="!form.region"
                                />
                                <p v-if="errors.city" class="text-xs text-red-500 mt-1">
                                    {{ errors.city }}
                                </p>
                            </div>
                        </div>

                        <!-- Section: اختر نوع التأمين -->
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">اختر نوع التأمين</h3>

                        <div class="grid grid-cols-2 gap-2 sm:gap-3 w-full" dir="rtl" role="radiogroup" aria-label="نوع التأمين">
                            <!-- ضد الغير (Third Party) -->
                            <div class="relative flex flex-col items-center text-center rounded-xl p-3 sm:p-5 gap-2 sm:gap-3 border-2 cursor-pointer transition-all"
                                :class="form.insuranceType === 'tpl'
                                    ? 'border-blue-600 bg-blue-50 shadow-md shadow-blue-100'
                                    : 'border-slate-200 bg-white hover:border-blue-300 hover:shadow-sm'"
                                role="radio"
                                :aria-checked="form.insuranceType === 'tpl'"
                                tabindex="0"
                                @click="form.insuranceType = 'tpl'" @keydown.enter.prevent="form.insuranceType = 'tpl'" @keydown.space.prevent="form.insuranceType = 'tpl'">
                                <div v-if="form.insuranceType === 'tpl'" class="absolute top-1.5 start-1.5 sm:top-2 sm:start-2 w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-blue-600 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <img :src="tplIconSrc" alt="ضد الغير" class="w-10 h-10 sm:w-14 sm:h-14" loading="lazy" width="56" height="56" />
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs sm:text-sm font-bold text-slate-900">ضد الغير</span>
                                    <span class="text-[11px] sm:text-xs text-slate-500 leading-tight">تغطية أساسية للطرف الثالث</span>
                                </div>
                            </div>

                            <!-- شامل (Comprehensive) -->
                            <div class="relative flex flex-col items-center text-center rounded-xl p-3 sm:p-5 gap-2 sm:gap-3 border-2 cursor-pointer transition-all"
                                :class="form.insuranceType === 'comp'
                                    ? 'border-blue-600 bg-blue-50 shadow-md shadow-blue-100'
                                    : 'border-slate-200 bg-white hover:border-blue-300 hover:shadow-sm'"
                                role="radio"
                                :aria-checked="form.insuranceType === 'comp'"
                                tabindex="0"
                                @click="form.insuranceType = 'comp'" @keydown.enter.prevent="form.insuranceType = 'comp'" @keydown.space.prevent="form.insuranceType = 'comp'">
                                <div v-if="form.insuranceType === 'comp'" class="absolute top-1.5 start-1.5 sm:top-2 sm:start-2 w-4 h-4 sm:w-5 sm:h-5 rounded-full bg-blue-600 flex items-center justify-center">
                                    <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <img :src="compIconSrc" alt="شامل" class="w-10 h-10 sm:w-14 sm:h-14" loading="lazy" width="56" height="56" />
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-xs sm:text-sm font-bold text-slate-900">شامل</span>
                                    <span class="text-[11px] sm:text-xs text-slate-500 leading-tight">تأمينكم هيرو… يغطي سيارتك بالكامل</span>
                                </div>
                            </div>
                        </div>
                        <p v-if="errors.insuranceType" class="text-xs text-red-500 mt-1">
                            {{ errors.insuranceType }}
                        </p>

                        <!-- Repair Center -->
                        <div class="flex gap-2 items-center overflow-hidden"
                                dir="rtl">
                                <span class="flex items-center text-sm text-slate-500 gap-1">
                                    اختر مركز الإصلاح
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                        class="shrink-0 w-4.5 h-4.5 text-blue-600 cursor-help">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M12 3C16.971 3 21 7.029 21 12C21 16.971 16.971 21 12 21C7.029 21 3 16.971 3 12C3 7.029 7.029 3 12 3Z"
                                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                            stroke-linejoin="round" />
                                        <path d="M12 12.5V7.5" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                        <circle cx="12" cy="16.25" r="0.25" stroke="currentColor" stroke-width="1.5" />
                                    </svg>
                                    :
                                </span>
                                <div class="flex flex-row gap-4" role="radiogroup" aria-label="مركز الإصلاح">
                                    <div class="flex rounded-xl border gap-2 px-4 py-2 cursor-pointer transition-colors"
                                        :class="form.repairMethod === 'workshop' ? 'bg-green-200 border-green-300' : 'border-slate-300 hover:bg-green-100'"
                                        role="radio"
                                        :aria-checked="form.repairMethod === 'workshop'"
                                        tabindex="0"
                                        @click="form.repairMethod = 'workshop'" @keydown.enter.prevent="form.repairMethod = 'workshop'" @keydown.space.prevent="form.repairMethod = 'workshop'">
                                        <span class="text-xs text-slate-900">الورشة</span>
                                    </div>
                                    <div class="flex rounded-xl border gap-2 px-4 py-2 cursor-pointer transition-colors"
                                        :class="form.repairMethod === 'agency' ? 'bg-green-200 border-green-300' : 'border-slate-300 hover:bg-green-100'"
                                        role="radio"
                                        :aria-checked="form.repairMethod === 'agency'"
                                        tabindex="0"
                                        @click="form.repairMethod = 'agency'" @keydown.enter.prevent="form.repairMethod = 'agency'" @keydown.space.prevent="form.repairMethod = 'agency'">
                                        <span class="text-xs text-slate-900">الوكالة</span>
                                    </div>
                                </div>
                            </div>

                        <!-- Mobile: Info Tip Card -->
                        <div class="block lg:hidden">
                            <div class="border-2 border-primary rounded-lg p-4">
                                <div class="flex gap-2 items-start mb-2">
                                    <img :src="infoIconSrc" alt="Info" class="w-6 h-6 mt-0.5" loading="lazy" width="24" height="24" />
                                    <span class="text-sm font-medium text-primary">{{ selectedInsuranceTitle }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                    {{ selectedInsuranceTip }}
                                </p>
                            </div>
                        </div>

                        <!-- Navigation: Previous / Submit -->
                        <div
                            class="flex items-center gap-2 justify-between w-full md:static md:bg-transparent md:p-0 sticky bottom-0 right-0 left-0 bg-white z-50 p-4 border-t md:border-t-0 border-slate-100 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] md:shadow-none safe-area-bottom">
                            <router-link :to="{ name: 'vehicleDetails' }"
                                class="items-center gap-2 cursor-pointer hidden md:flex">
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
                            </router-link>

                            <button type="submit" :disabled="isSubmitting"
                                class="cursor-pointer whitespace-nowrap transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 text-center min-h-14 min-w-[10.625rem] px-6 text-base font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 disabled:bg-slate-400 disabled:cursor-not-allowed w-full md:w-auto inline-flex items-center justify-center gap-2">
                                <div class="flex items-center w-full gap-2 justify-center">
                                    <div class="w-full overflow-hidden self-center">
                                        <div>{{ isSubmitting ? 'جاري المعالجة...' : 'احصل على التسعيرة' }}</div>
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
                        <div class="border-2 border-primary rounded-lg p-4">
                            <div class="flex gap-2 items-start mb-2">
                                <img :src="infoIconSrc" alt="Info" class="w-6 h-6 mt-0.5" loading="lazy" width="24" height="24" />
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
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { useRouter } from 'vue-router';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { useInsuranceStore } from '@/store/modules/insurance';
import AppSelect from '@/components/ui/AppSelect.vue';
import { regionsData, citiesByRegion } from '@/data';
import logger from '@/utils/logger';
import { trackSnapchatQuoteStart } from '@/utils/snapchatPixel';
const PolicyDatePicker = defineAsyncComponent( () => import( '../components/PolicyDatePicker.vue' ) );

const router = useRouter();
const { trackStep, resumeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();

// Steps
const currentStep = ref( 2 ); // steps 0 & 1 completed, step 2 active

const steps = reactive( [
    { label: 'المعلومات الأساسية' },
    { label: 'تفاصيل السيارة' },
    { label: 'تفاصيل الوثيقة' },
] );

// Image Imports
const animojiSrc = new URL( '../../../images/motorapp/animoji.svg', import.meta.url ).href;
const infoIconSrc = new URL( '../../../images/motorapp/info-icon.svg', import.meta.url ).href;
const tplIconSrc = new URL( '../../../images/motorapp/tpl-icon.webp', import.meta.url ).href;
const compIconSrc = new URL( '../../../images/motorapp/comp-icon.webp', import.meta.url ).href;

// Form State
const form = reactive( {
    policyStartDate: '',
    insuranceType: 'tpl',   // 'tpl' = Third Party, 'comp' = Comprehensive
    repairMethod: 'workshop', // 'workshop' or 'agency'
    region: '',
    city: '',
} );

// Region / City options
const EMPTY_CITIES = [];
const regionOptions = regionsData.ar;
const cityOptions = computed( () => {
    if ( !form.region ) return EMPTY_CITIES;
    return citiesByRegion.ar[ form.region ] || EMPTY_CITIES;
} );

// Reset city when region changes
watch( () => form.region, () => {
    form.city = '';
} );

const errors = reactive( {} );
const isSubmitting = ref( false );
const policyFormRef = ref( null );
const hasTrackedQuoteStart = ref( false );

function createDedupId() {
    if ( typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function' ) {
        return crypto.randomUUID();
    }

    return `${ Date.now() }-${ Math.random().toString( 36 ).slice( 2, 12 ) }`;
}

function trackQuoteStartOnFirstInteraction() {
    if ( hasTrackedQuoteStart.value ) return;

    hasTrackedQuoteStart.value = true;
    const dedupId = createDedupId();
    sessionStorage.setItem( 'snapchat_start_quote_dedup_id', dedupId );
    trackSnapchatQuoteStart( {
        client_dedup_id: dedupId,
        event_id: dedupId,
    } );
}

function bindFirstInteractionTracking() {
    const formElement = policyFormRef.value;
    if ( !formElement ) return () => {};

    const interactionHandler = () => {
        trackQuoteStartOnFirstInteraction();
    };

    const events = [ 'input', 'change', 'click' ];
    events.forEach( eventName => formElement.addEventListener( eventName, interactionHandler, true ) );

    return () => {
        events.forEach( eventName => formElement.removeEventListener( eventName, interactionHandler, true ) );
    };
}

let unbindFirstInteractionTracking = () => {};

// User/Vehicle data from session
const userFullName = ref( '' );
const userNationalId = ref( '' );
// Insurance type info (from MotorApp selection)
const selectedInsuranceTitle = ref( 'تأمين أو تجديد تأمين السيارة' );
const selectedInsuranceTip = ref(
    'تأمين السيارة يحميك ماليًا من الحوادث والسرقة والمواقف غير المتوقعة. فهم التفاصيل يساعدك على اتخاذ قرار صحيح سواء كنت تشتري أو تجدد التأمين.'
);

// Step helpers
function getStepMobileClass( index ) {
    if ( index < currentStep.value ) return 'text-green-600 border-b-green-600';
    if ( index === currentStep.value ) return 'text-blue-600 border-b-blue-600';
    return 'text-slate-400 border-b-slate-100';
}

function getStepDesktopClass( index ) {
    if ( index < currentStep.value ) return 'text-green-600 border-b-green-600';
    if ( index === currentStep.value ) return 'text-blue-600 border-b-blue-600';
    return 'text-slate-400 border-b-slate-100';
}

function getStepLabelClass( index ) {
    if ( index < currentStep.value ) return 'text-green-600';
    if ( index === currentStep.value ) return 'text-blue-600';
    return 'text-slate-400';
}

// Validation
function clearErrors() {
    Object.keys( errors ).forEach( key => delete errors[ key ] );
}

function validate() {
    clearErrors();
    let valid = true;

    if ( !form.policyStartDate ) {
        errors.policyStartDate = 'يرجى اختيار تاريخ بدء الوثيقة';
        valid = false;
    }

    if ( !form.region ) {
        errors.region = 'يرجى اختيار المنطقة';
        valid = false;
    }

    if ( !form.city ) {
        errors.city = 'يرجى اختيار المدينة';
        valid = false;
    }

    if ( !form.insuranceType ) {
        errors.insuranceType = 'يرجى اختيار نوع التأمين';
        valid = false;
    }

    return valid;
}

// Submit
async function submitForm() {
    if ( isSubmitting.value ) return;
    if ( !validate() ) return;

    const quoteSubmitDedupId = createDedupId();
    sessionStorage.setItem( 'snapchat_quote_submit_pending_dedup_id', quoteSubmitDedupId );

    isSubmitting.value = true;
    try {

    // Save to sessionStorage
    const policyDetails = { ...form };
    sessionStorage.setItem( 'policyDetails', JSON.stringify( policyDetails ) );

    // حفظ البيانات في المتجر المركزي
    insuranceStore.setPolicyData( {
        policyStartDate: form.policyStartDate,
        insuranceType: form.insuranceType,
        repairMethod: form.insuranceType === 'comp' ? form.repairMethod : null,
    } );

    // حفظ المنطقة والمدينة في بيانات السائق
    insuranceStore.setDriverData( {
        region: form.region,
        city: form.city,
    } );

    // Send policy details to backend for admin dashboard tracking
    try {
        const { default: request } = await import( '@/api/request' );
        await request.post( '/customer/track-details', {
            policy_start_date: form.policyStartDate,
            insurance_type: form.insuranceType === 'comp' ? 'comprehensive' : 'thirdParty',
            repair_method: form.insuranceType === 'comp' ? form.repairMethod : null,
            region: form.region,
            city: form.city,
            current_page: '/motorapp/policyDetails',
        } );
    } catch ( err ) {
        logger.warn( '[PolicyDetails] Tracking failed:', err.message );
    }

    // Track step transition
    await trackStep( 'policyDetails', 3, policyDetails, 'next' );

    // Navigate to compare page
    router.push( {
        path: '/compare',
        query: {
            type: form.insuranceType === 'comp' ? 'comprehensive' : 'thirdParty',
        }
    } );
    } finally {
        isSubmitting.value = false;
    }
}

// Restore form state
function restoreFormState() {
    // Pull user/vehicle data from VehicleDetails session
    const vehicleDetails = sessionStorage.getItem( 'vehicleDetails' );
    if ( vehicleDetails ) {
        try {
            const parsed = JSON.parse( vehicleDetails );
            if ( parsed.fullName ) userFullName.value = parsed.fullName;
            if ( parsed.nationalId ) userNationalId.value = parsed.nationalId;
        } catch { /* ignore */ }
    }

    // Restore own saved data
    const saved = sessionStorage.getItem( 'policyDetails' );
    if ( saved ) {
        try {
            const parsed = JSON.parse( saved );
            Object.assign( form, parsed );
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

// Lifecycle
onMounted( () => {
    restoreFormState();
    resumeSession( 'policyDetails' );
    unbindFirstInteractionTracking = bindFirstInteractionTracking();

    // Prefetch next step chunk so it's cached before user navigates
    import( '@/car.insurance/flow/ComparePage.vue' ).catch( () => {} );
} );

onUnmounted( () => {
    unbindFirstInteractionTracking();
} );
</script>
