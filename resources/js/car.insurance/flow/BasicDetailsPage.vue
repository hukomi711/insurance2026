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
                        <!-- Checkmark for completed steps -->
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

                <!-- Left Content — 4/6 on desktop -->
                <div class="w-full flex flex-col lg:w-4/6 p-4">

                    <!-- Section: أدخل تفاصيل السيارة -->
                    <h3 class="text-lg font-bold text-slate-800 font-heading mb-6">
                        أدخل تفاصيل السيارة
                    </h3>

                    <form class="space-y-6" @submit.prevent="handleSubmit">
                        <!-- Identity Number — رقم الهوية -->
                        <div class="relative">
                            <input id="identityNumber" v-model="form.identityNumber" type="text" inputmode="numeric"
                                name="identityNumber" autocomplete="off" maxlength="10" dir="ltr"
                                class="peer w-full border rounded-lg px-4 pt-6 pb-2 text-sm text-left outline-none transition-all duration-200"
                                :class="errors.identityNumber
                                    ? 'border-red-500 focus:ring-2 focus:ring-red-200'
                                    : 'border-slate-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500'
                                    " placeholder=" " @input="onIdentityInput" @blur="validateIdentity" />
                            <label for="identityNumber"
                                class="absolute top-2 right-4 text-xs text-slate-500 transition-all duration-200 pointer-events-none peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs">
                                رقم الهوية / الإقامة
                            </label>
                            <p v-if="errors.identityNumber" class="text-red-500 text-xs mt-1">
                                {{ errors.identityNumber }}
                            </p>
                        </div>

                        <!-- Sequence Number — الرقم التسلسلي -->
                        <div class="relative">
                            <input id="sequenceNumber" v-model="form.sequenceNumber" type="text" inputmode="numeric"
                                name="sequenceNumber" autocomplete="off" maxlength="10" dir="ltr"
                                class="peer w-full border rounded-lg px-4 pt-6 pb-2 text-sm text-left outline-none transition-all duration-200"
                                :class="errors.sequenceNumber
                                    ? 'border-red-500 focus:ring-2 focus:ring-red-200'
                                    : 'border-slate-300 focus:ring-2 focus:ring-blue-200 focus:border-blue-500'
                                    " placeholder=" " @input="onSequenceInput" @blur="validateSequence" />
                            <label for="sequenceNumber"
                                class="absolute top-2 right-4 text-xs text-slate-500 transition-all duration-200 pointer-events-none peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-focus:top-2 peer-focus:text-xs">
                                الرقم التسلسلي
                            </label>
                            <p v-if="errors.sequenceNumber" class="text-red-500 text-xs mt-1">
                                {{ errors.sequenceNumber }}
                            </p>
                        </div>

                        <!-- Consent Notice -->
                        <div class="bg-slate-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-slate-700 leading-relaxed font-medium">
                                بالضغط على التالي، أوافق على منح تأمينكم الحق في الاستعلام عن بياناتي
                                وبيانات مركبتي من الجهات المعنية لأجل اصدار التسعيرة
                            </p>
                        </div>

                        <!-- Navigation — Desktop -->
                        <div class="hidden md:flex items-center justify-between mt-8">
                            <router-link :to="{ name: 'motorapp' }"
                                class="flex items-center gap-2 cursor-pointer group">
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
                                class="bg-primary hover:bg-primary-dark text-white font-bold text-sm px-10 py-3 rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ isSubmitting ? 'جاري التحقق...' : 'التالي' }}
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Right Sidebar — 2/6 on desktop, visible on mobile too -->
                <div class="w-full lg:w-2/6 p-4">
                    <div class="flex flex-col gap-4">

                        <!-- Sequence Number Help Card -->
                        <div class="border-2 border-primary rounded-xl p-4">
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

        <!-- Sticky Mobile Navigation -->
        <div class="sticky bottom-0 left-0 right-0 bg-white p-4 md:hidden z-50 border-t border-slate-100 shadow-[0_-2px_8px_rgba(0,0,0,0.06)] safe-area-bottom">
            <div class="relative">
                <button type="button" :disabled="isSubmitting" class="w-full bg-primary hover:bg-primary-dark text-white font-bold text-base py-4 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleSubmit">
                    {{ isSubmitting ? 'جاري التحقق...' : 'التالي' }}
                </button>
                <button type="button" class="absolute left-4 top-1/2 -translate-y-1/2 p-1"
                    aria-label="السابق"
                    @click="router.push( { name: 'motorapp' } )">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        class="text-white">
                        <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M10 7L5 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                        <path d="M10 17L5 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter } from 'vue-router';
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
    sequenceNumber: '',
} );

const errors = reactive( {
    identityNumber: '',
    sequenceNumber: '',
} );

const isSubmitting = ref( false );

// Load saved data
onMounted( () => {
    const saved = sessionStorage.getItem( 'basicDetails' );
    if ( saved ) {
        try {
            const data = JSON.parse( saved );
            form.identityNumber = data.identityNumber || '';
            form.sequenceNumber = data.sequenceNumber || '';
        } catch { /* ignore */ }
    }

    // Prefetch next step chunk so it's cached before user navigates
    import( '@/car.insurance/flow/VehicleDetailsPage.vue' ).catch( () => {} );
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
        errors.identityNumber = 'رقم الهوية مطلوب';
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

// Submit
async function handleSubmit() {
    const isIdentityValid = validateIdentity();
    const isSequenceValid = validateSequence();

    if ( !isIdentityValid || !isSequenceValid ) return;

    isSubmitting.value = true;

    // Save to session
    sessionStorage.setItem( 'basicDetails', JSON.stringify( {
        identityNumber: form.identityNumber,
        sequenceNumber: form.sequenceNumber,
    } ) );

    // Track customer in backend
    try {
        const { default: request } = await import( '@/api/request' );
        const res = await request.post( '/customer/track', {
            national_id: form.identityNumber,
            sequence_number: form.sequenceNumber,
            registration_type: 'sequence',
            current_page: '/insurance/basic-details',
        } );
        // Save city from backend (geo-detected) for summary display
        if ( res.data?.city ) {
            const saved = JSON.parse( sessionStorage.getItem( 'basicDetails' ) || '{}' );
            saved.city = res.data.city;
            sessionStorage.setItem( 'basicDetails', JSON.stringify( saved ) );
        }
    } catch ( e ) {
        // Tracking failure should not block the user flow
        logger.warn( 'Tracking failed:', e );
    }

    isSubmitting.value = false;

    // Navigate to vehicle details
    router.push( { name: 'vehicleDetails' } );
}
</script>
