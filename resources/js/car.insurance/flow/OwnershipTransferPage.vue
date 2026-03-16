<template>
    <div class="bg-white min-h-screen">
        <!-- Step Progress Bar — Mobile (colored bars only) -->
        <div class="sm:hidden w-full">
            <div class="flex w-full">
                <div v-for="(step, index) in steps" :key="'m-' + index" class="flex-1 h-1" :class="index <= currentStep
                    ? 'bg-blue-600'
                    : 'bg-slate-100'
                    "></div>
            </div>
        </div>

        <!-- Step Progress Bar — Desktop (labeled tabs) -->
        <div dir="rtl" class="overflow-hidden hidden sm:block w-full">
            <div class="flex items-center">
                <div v-for="(step, index) in steps" :key="'d-' + index"
                    class="flex-1 flex flex-col items-center min-w-32">
                    <div class="flex items-center gap-1 lg:gap-4 lg:text-sm text-xs lg:py-4 py-1 border-b-2 w-full justify-center cursor-pointer transition-colors"
                        :class="index <= currentStep
                            ? 'text-blue-600 border-b-blue-600'
                            : 'text-slate-400 border-b-slate-100'
                            ">
                        <svg v-if="index < currentStep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                            fill="none" width="1em" height="1em" class="shrink-0 size-4 text-green-600">
                            <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span class="whitespace-nowrap pe-4" :class="index <= currentStep
                            ? 'text-blue-600'
                            : 'text-slate-400'
                            ">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="w-full md:max-w-[80rem] px-0 md:px-4 mx-auto my-5 relative">
            <div class="flex flex-col lg:flex-row w-full">

                <!-- Mobile sidebar placeholder -->
                <div class="w-full lg:hidden md:p-4"></div>

                <!-- Left Content — 4/6 on desktop -->
                <div class="w-full flex flex-col lg:w-4/6 p-4">

                    <form class="space-y-8 mb-8" @submit.prevent="handleSubmit">
                        <h3 class="text-xl sm:text-2xl font-bold text-slate-800 font-heading">
                            أدخل تفاصيل السيارة لسيارة تشتريها:
                        </h3>

                        <!-- Row 1: Identity Number + Birth Month/Year -->
                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <!-- Identity Number — رقم الهوية -->
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex border rounded-lg min-h-[3.5rem] px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.identityNumber
                                        ? 'border-red-500'
                                        : 'border-slate-300 has-[:focus]:border-blue-600'
                                        ">
                                    <input id="identityNumber" v-model="form.identityNumber" type="text" name="identityNumber"
                                        inputmode="numeric" autocomplete="off" maxlength="10" dir="ltr"
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full text-sm text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6"
                                        placeholder=" " @input="onIdentityInput" @blur="validateIdentity" />
                                    <label for="identityNumber"
                                        class="transition z-10 absolute text-sm text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        رقم الهوية
                                    </label>
                                </div>
                                <p v-if="errors.identityNumber" class="text-red-500 text-xs mt-1">
                                    {{ errors.identityNumber }}
                                </p>
                            </div>

                            <!-- Birth Month + Year Selects -->
                            <div class="w-full md:w-1/2">
                                <div class="flex gap-4">
                                    <div class="w-1/2">
                                        <AppSelect id="birthMonth" v-model="form.birthMonth"
                                            :options="birthMonthOptions" label="اختر الشهر" placeholder="اختر الشهر" variant="standard"
                                            dir="rtl" name="birthMonth" :error="!!errors.birthMonth" />
                                    </div>
                                    <div class="w-1/2">
                                        <AppSelect id="birthYear" v-model="form.birthYear"
                                            :options="birthYearOptions" label="اختر السنة" placeholder="اختر السنة" variant="standard"
                                            dir="rtl" :scroll-buttons="true" name="birthYear"
                                            item-extra-class="ltr-nums" :error="!!errors.birthYear" />
                                    </div>
                                </div>
                                <p v-if="errors.birthMonth || errors.birthYear" class="text-red-500 text-xs mt-1">
                                    {{ errors.birthMonth || errors.birthYear }}
                                </p>
                            </div>
                        </div>

                        <!-- Row 2: Sequence Number + Manufacturing Year -->
                        <div class="flex flex-col md:flex-row gap-4 justify-between">
                            <!-- Sequence Number — الرقم التسلسلي -->
                            <div class="w-full md:w-1/2">
                                <div class="group transition duration-300 relative flex border rounded-lg min-h-[3.5rem] px-4 py-2 items-center gap-2 w-full"
                                    :class="errors.sequenceNumber
                                        ? 'border-red-500'
                                        : 'border-slate-300 has-[:focus]:border-blue-600'
                                        ">
                                    <input id="sequenceNumber" v-model="form.sequenceNumber" type="text" name="sequenceNumber"
                                        inputmode="numeric" autocomplete="off" maxlength="10" dir="ltr"
                                        class="transition bg-transparent duration-300 block cursor-text resize-none caret-blue-600 pb-2.5 size-full text-sm text-slate-900 appearance-none focus:outline-none focus:ring-0 peer z-10 pt-6"
                                        placeholder=" " @input="onSequenceInput" @blur="validateSequence" />
                                    <label for="sequenceNumber"
                                        class="transition z-10 absolute text-sm text-slate-500 duration-300 transform -translate-y-4 top-5 peer-placeholder-shown:cursor-text peer-focus:pointer-events-none peer-placeholder-shown:top-4 peer-focus:top-5 origin-[0] start-4 peer-placeholder-shown:translate-y-0 peer-focus:-translate-y-4">
                                        الرقم التسلسلى
                                    </label>
                                </div>
                                <p v-if="errors.sequenceNumber" class="text-red-500 text-xs mt-1">
                                    {{ errors.sequenceNumber }}
                                </p>
                            </div>

                            <!-- Manufacturing Year — سنة الصنع -->
                            <div class="w-full md:w-1/2">
                                <AppSelect id="manufacturingYear" v-model="form.manufacturingYear"
                                    :options="manufacturingYearOptions" label="سنة الصنع" placeholder="سنة الصنع" variant="standard"
                                    dir="rtl" :scroll-buttons="true" name="manufacturingYear"
                                    item-extra-class="ltr-nums" :error="!!errors.manufacturingYear" />
                                <p v-if="errors.manufacturingYear" class="text-red-500 text-xs mt-1">
                                    {{ errors.manufacturingYear }}
                                </p>
                            </div>
                        </div>

                        <!-- Consent Notice -->
                        <p
                            class="bg-slate-50 p-4 rounded-lg text-sm text-slate-700 leading-relaxed font-medium text-center">
                            بالضغط على التالي، أوافق على منح تأمينكم الحق في الاستعلام عن بياناتي
                            وبيانات مركبتي من الجهات المعنية لأجل اصدار التسعيرة
                        </p>

                        <!-- Sequence Number Help — Mobile only -->
                        <div class="block lg:hidden w-fit mx-auto">
                            <div class="border-2 border-primary rounded-lg p-4">
                                <div class="flex items-center justify-center gap-2">
                                    <img :src="infoIconSrc" alt="Info" class="w-6 h-6" loading="lazy" width="24" height="24" />
                                    <span class="text-sm font-medium text-primary">أين أجد الرقم التسلسلي؟</span>
                                </div>
                                <div class="flex justify-center">
                                    <img :src="sequenceNumberSrc" alt="الرقم التسلسلي" class="max-w-full mt-4"
                                        loading="lazy" width="600" height="400" />
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div
                            class="flex items-center gap-2 justify-between w-full sticky bottom-0 right-0 left-0 bg-white z-50 p-4 border-t border-slate-100 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] safe-area-bottom md:static md:bg-transparent md:p-0 md:mb-4 md:border-t-0 md:shadow-none">
                            <router-link :to="{ name: 'motorapp' }"
                                class="items-center gap-2 cursor-pointer hidden md:flex group">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18"
                                    height="18"
                                    class="shrink-0 text-gray-600 group-hover:text-primary transition-colors">
                                    <path d="M19 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                        stroke-linejoin="round" />
                                    <path d="M14 17L19 12" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M14 7L19 12" stroke="currentColor" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span
                                    class="text-gray-600 font-bold text-sm group-hover:text-primary transition-colors">السابق</span>
                            </router-link>

                            <button type="submit" :disabled="isSubmitting"
                                class="cursor-pointer whitespace-nowrap transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring text-center min-h-14 min-w-[10.625rem] px-6 text-base font-bold rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 disabled:bg-slate-400 disabled:cursor-not-allowed w-full md:w-auto inline-flex items-center justify-center">
                                <div class="flex items-center w-full gap-2 justify-center">
                                    <div class="w-full overflow-hidden self-center">
                                        <div>{{ isSubmitting ? 'جاري التحقق...' : 'التالي' }}</div>
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
                    <div class="hidden lg:block">

                        <!-- Sequence Number Help Card -->
                        <div class="border-2 border-primary rounded-lg p-4">
                            <div class="flex items-center justify-center gap-2">
                                <img :src="infoIconSrc" alt="Info" class="w-6 h-6" loading="lazy" width="24" height="24" />
                                <span class="text-sm font-medium text-primary">أين أجد الرقم التسلسلي؟</span>
                            </div>
                            <div class="flex justify-center">
                                <img :src="sequenceNumberSrc" alt="الرقم التسلسلي" class="max-w-full mt-4 rounded-lg"
                                    loading="lazy" width="600" height="400" />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import AppSelect from '@/components/ui/AppSelect.vue';
import request from '@/api/request';
import logger from '@/utils/logger';

const router = useRouter();

// Step progress
const currentStep = ref( 0 );
const steps = reactive( [
    { label: 'المعلومات الأساسية' },
    { label: 'تفاصيل السيارة' },
    { label: 'تفاصيل الوثيقة' },
] );

// Images
const infoIconSrc = new URL( '../../../images/icons/info-icon.webp', import.meta.url ).href;
const sequenceNumberSrc = new URL( '../../../images/banners/sequence-number.webp', import.meta.url ).href;

// Form state
const form = reactive( {
    identityNumber: '',
    birthMonth: '',
    birthYear: '',
    sequenceNumber: '',
    manufacturingYear: '',
} );

const errors = reactive( {
    identityNumber: '',
    birthMonth: '',
    birthYear: '',
    sequenceNumber: '',
    manufacturingYear: '',
} );

const isSubmitting = ref( false );

// Birth month options (Hijri months)
const birthMonthOptions = [
    { value: '1', label: 'محرم' },
    { value: '2', label: 'صفر' },
    { value: '3', label: 'ربيع الأول' },
    { value: '4', label: 'ربيع الثاني' },
    { value: '5', label: 'جمادى الأولى' },
    { value: '6', label: 'جمادى الآخرة' },
    { value: '7', label: 'رجب' },
    { value: '8', label: 'شعبان' },
    { value: '9', label: 'رمضان' },
    { value: '10', label: 'شوال' },
    { value: '11', label: 'ذو القعدة' },
    { value: '12', label: 'ذو الحجة' },
];

// Birth year options (Hijri years — 1380 to 1450)
const birthYearOptions = Array.from( { length: 71 }, ( _, i ) => {
    const year = 1450 - i;
    return { value: String( year ), label: String( year ) };
} );

// Manufacturing year options (Gregorian — current year down to 2000)
const currentGregorianYear = new Date().getFullYear();
const manufacturingYearOptions = Array.from( { length: currentGregorianYear - 1999 }, ( _, i ) => {
    const year = currentGregorianYear - i;
    return { value: String( year ), label: String( year ) };
} );

// Load saved data
onMounted( () => {
    const saved = sessionStorage.getItem( 'ownershipTransferDetails' );
    if ( saved ) {
        try {
            const data = JSON.parse( saved );
            form.identityNumber = data.identityNumber || '';
            form.birthMonth = data.birthMonth || '';
            form.birthYear = data.birthYear || '';
            form.sequenceNumber = data.sequenceNumber || '';
            form.manufacturingYear = data.manufacturingYear || '';
        } catch { /* ignore */ }
    }
} );

// Input handlers — digits only
function onIdentityInput( e ) {
    form.identityNumber = e.target.value.replace( /\D/g, '' ).slice( 0, 10 );
    if ( errors.identityNumber ) validateIdentity();
}

function onSequenceInput( e ) {
    form.sequenceNumber = e.target.value.replace( /\D/g, '' ).slice( 0, 10 );
    if ( errors.sequenceNumber ) validateSequence();
}

// Validators
function validateIdentity() {
    if ( !form.identityNumber ) {
        errors.identityNumber = 'يرجى ادخال رقم الهوية او الاقامة او الشركة';
        return false;
    }
    if ( form.identityNumber.length !== 10 ) {
        errors.identityNumber = 'رقم الهوية يجب أن يكون 10 أرقام';
        return false;
    }
    if ( !/^[12]/.test( form.identityNumber ) ) {
        errors.identityNumber = 'رقم الهوية يجب أن يبدأ بـ 1 أو 2';
        return false;
    }
    errors.identityNumber = '';
    return true;
}

function validateBirth() {
    let valid = true;
    if ( !form.birthMonth ) {
        errors.birthMonth = 'يرجى اختيار شهر الميلاد';
        valid = false;
    } else {
        errors.birthMonth = '';
    }
    if ( !form.birthYear ) {
        errors.birthYear = 'يرجى اختيار سنة الميلاد';
        valid = false;
    } else {
        const year = parseInt( form.birthYear );
        // السنة الهجرية الحالية (تقريبياً)
        const currentHijriYear = Math.round( ( new Date().getFullYear() - 622 ) * ( 33 / 32 ) );
        if ( year > currentHijriYear - 18 ) {
            errors.birthYear = 'يجب أن يكون العمر 18 سنة على الأقل';
            valid = false;
        } else if ( year < currentHijriYear - 80 ) {
            errors.birthYear = 'يرجى التحقق من سنة الميلاد';
            valid = false;
        } else {
            errors.birthYear = '';
        }
    }
    return valid;
}

function validateSequence() {
    if ( !form.sequenceNumber ) {
        errors.sequenceNumber = 'الرقم التسلسلي مطلوب';
        return false;
    }
    if ( form.sequenceNumber.length < 6 || form.sequenceNumber.length > 10 ) {
        errors.sequenceNumber = 'الرقم التسلسلي يجب أن يكون بين 6 و 10 أرقام';
        return false;
    }
    errors.sequenceNumber = '';
    return true;
}

function validateManufacturingYear() {
    if ( !form.manufacturingYear ) {
        errors.manufacturingYear = 'يرجى اختيار سنة الصنع';
        return false;
    }
    const year = parseInt( form.manufacturingYear );
    const currentYear = new Date().getFullYear();
    if ( year < currentYear - 15 || year > currentYear + 1 ) {
        errors.manufacturingYear = `سنة الصنع يجب أن تكون بين ${ currentYear - 15 } و ${ currentYear + 1 }`;
        return false;
    }
    errors.manufacturingYear = '';
    return true;
}

// Submit
async function handleSubmit() {
    const isIdentityValid = validateIdentity();
    const isBirthValid = validateBirth();
    const isSequenceValid = validateSequence();
    const isYearValid = validateManufacturingYear();

    if ( !isIdentityValid || !isBirthValid || !isSequenceValid || !isYearValid ) return;

    isSubmitting.value = true;

    // Save to session
    sessionStorage.setItem( 'ownershipTransferDetails', JSON.stringify( {
        identityNumber: form.identityNumber,
        birthMonth: form.birthMonth,
        birthYear: form.birthYear,
        sequenceNumber: form.sequenceNumber,
        manufacturingYear: form.manufacturingYear,
    } ) );

    sessionStorage.setItem( 'selectedInsuranceType', JSON.stringify( {
        title: 'سيارة أشتريها',
        tip: 'عند شراء سيارة مستعملة، تحتاج لتأمين ساري المفعول قبل إتمام نقل الملكية.',
    } ) );

    // Send data to backend for tracking
    try {
        await request.post( '/customer/track', {
            insurance_type: 'buy',
            national_id: form.identityNumber,
            birth_month: form.birthMonth,
            birth_year: form.birthYear,
            sequence_number: form.sequenceNumber,
            manufacturing_year: form.manufacturingYear,
            registration_type: 'sequence',
            current_page: '/insurance/ownership-transfer',
        } );
    } catch ( err ) {
        logger.warn( '[OwnershipTransfer] Tracking failed:', err.message );
    }

    isSubmitting.value = false;

    // Navigate to vehicle details
    router.push( { name: 'vehicleDetails' } );
}
</script>
