<template>
    <div class="bg-white min-h-screen">
        <!-- Step Progress Bar — Mobile (colored bars only) -->
        <div class="sm:hidden w-full">
            <div class="flex items-center w-full">
                <div v-for="(step, index) in steps" :key="'m-' + index" class="flex-1 flex flex-col items-center">
                    <div class="flex border-b-2 w-full" :class="index <= currentStep
                        ? 'text-blue-600 border-b-blue-600'
                        : 'text-slate-400 border-b-slate-100'
                        "></div>
                </div>
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

                    <!-- Section: ما نوع التأمين الذي تحتاجه؟ -->
                    <div class="flex justify-between my-3">
                        <h3 class="text-lg font-bold text-slate-800 font-heading">
                            ما نوع التأمين الذي تحتاجه؟
                        </h3>
                        <div class="flex items-center gap-2 cursor-pointer" @click="showHelpModal = true">
                            <span class="text-primary text-xs font-bold cursor-pointer">ماذا أختار؟</span>
                            <!-- Arrow icon RTL -->
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18"
                                height="18" class="shrink-0 text-primary cursor-pointer">
                                <path d="M5 12H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M10 7L5 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M10 17L5 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>

                    <!-- Insurance Type Cards -->
                    <div class="flex flex-col gap-4">
                        <a v-for="option in insuranceOptions" :key="option.id" class="block cursor-pointer"
                            role="button" tabindex="0"
                            @click="selectInsuranceType(option)"
                            @keydown.enter.prevent="selectInsuranceType(option)"
                            @keydown.space.prevent="selectInsuranceType(option)">
                            <div class="flex flex-col gap-2 border-2 rounded-lg p-4 transition-all duration-200" :class="selectedType?.id === option.id
                                ? 'border-blue-500 bg-blue-50/50 shadow-sm'
                                : 'border-slate-200 hover:bg-gray-50'
                                ">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <img :src="option.icon" :alt="option.title" class="w-12 h-12 object-contain"
                                            loading="lazy" width="48" height="48" />
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-800">{{ option.title }}</span>
                                            <span
                                                class="text-xs text-gray-600 leading-relaxed">{{ option.description }}</span>
                                        </div>
                                    </div>
                                    <!-- Chevron (RTL — rotated) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" width="18"
                                        height="18" class="shrink-0 text-slate-400 rotate-180">
                                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Section: خدمات أخرى -->
                    <div class="flex flex-col gap-4 my-6">
                        <h3 class="text-lg font-bold text-slate-800 font-heading">خدمات أخرى</h3>
                        <a v-for="service in otherServices" :key="service.id" class="block cursor-pointer"
                            role="button" tabindex="0"
                            @click="selectService(service)"
                            @keydown.enter.prevent="selectService(service)"
                            @keydown.space.prevent="selectService(service)">
                            <div class="flex flex-col gap-2 border-2 rounded-lg p-4 transition-all duration-200" :class="selectedService?.id === service.id
                                ? 'border-blue-500 bg-blue-50/50 shadow-sm'
                                : 'border-slate-200 hover:bg-gray-50'
                                ">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <img :src="service.icon" :alt="service.title" class="w-12 h-12 object-contain"
                                            loading="lazy" width="48" height="48" />
                                        <div class="flex flex-col gap-1">
                                            <span class="text-sm font-medium text-slate-800">{{ service.title }}</span>
                                            <span
                                                class="text-xs text-gray-600 leading-relaxed">{{ service.description }}</span>
                                        </div>
                                    </div>
                                    <!-- Chevron -->
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" width="18"
                                        height="18" class="shrink-0 text-slate-400 rotate-180">
                                        <path d="M9 18L15 12L9 6" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- "ماذا أختار" Help Modal -->
                    <Dialog :open="showHelpModal" size="xl" @update:open="showHelpModal = $event">
                        <div dir="rtl">
                            <!-- Option 1: أصدر أو جدد تأمينك -->
                            <div class="mb-4">
                                <div class="flex flex-col px-4 py-2 border-2 rounded-md transition-colors"
                                    :class="expandedHelp === 'renew' ? 'border-primary/30 bg-blue-50/50' : 'border-slate-200 bg-slate-50'">
                                    <div class="cursor-pointer flex items-center gap-2 pb-2 border-b border-slate-200"
                                        @click="toggleHelp('renew')">
                                        <div class="bg-sky-200 rounded-full shrink-0">
                                            <img :src="carInsuranceLogo" alt="أصدر أو جدد تأمينك"
                                                class="max-w-full p-2 w-10 h-auto" loading="lazy" width="40" height="40" />
                                        </div>
                                        <h3 class="text-sm font-medium flex-1">أصدر أو جدد تأمينك</h3>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            width="18" height="18"
                                            class="shrink-0 text-slate-400 transition-transform duration-200"
                                            :class="expandedHelp === 'renew' ? 'rotate-180' : ''">
                                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <Transition enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-40 opacity-100"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="max-h-40 opacity-100" leave-to-class="max-h-0 opacity-0">
                                        <div v-show="expandedHelp === 'renew'" class="overflow-hidden">
                                            <div class="px-4 py-2 mt-2">
                                                <p class="text-sm text-slate-500 mb-3 leading-relaxed">
                                                    اختر هذا الخيار إذا كنت تملك سيارة بالفعل وتوشك وثيقة التأمين
                                                    الحالية على الانتهاء أو انتهت مؤخرًا. هذا الخيار مخصص للسيارات التي
                                                    تقودها بالفعل وتحتاج إلى تجديد الوثيقة للحفاظ على التغطية.
                                                    <br /><br />
                                                    <strong>مثال:</strong> لقد قمت بتأمين سيارتي العام الماضي، وتأميني
                                                    سينتهي قريبًا. لذا، أحتاج إلى تجديده للحفاظ على التغطية التأمينية.
                                                </p>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </div>

                            <!-- Option 2: تأمين لنقل ملكية السيارة -->
                            <div class="mb-4">
                                <div class="flex flex-col px-4 py-2 border-2 rounded-md transition-colors"
                                    :class="expandedHelp === 'buy' ? 'border-primary/30 bg-blue-50/50' : 'border-slate-200 bg-slate-50'">
                                    <div class="cursor-pointer flex items-center gap-2 pb-2 border-b border-slate-200"
                                        @click="toggleHelp('buy')">
                                        <div class="bg-sky-200 rounded-full shrink-0">
                                            <img :src="carBuyingLogo" alt="تأمين لنقل ملكية السيارة"
                                                class="max-w-full py-2 px-3 w-10 h-auto" loading="lazy" width="40" height="40" />
                                        </div>
                                        <h3 class="text-sm font-medium flex-1">تأمين لنقل ملكية السيارة</h3>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            width="18" height="18"
                                            class="shrink-0 text-slate-400 transition-transform duration-200"
                                            :class="expandedHelp === 'buy' ? 'rotate-180' : ''">
                                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <Transition enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-40 opacity-100"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="max-h-40 opacity-100" leave-to-class="max-h-0 opacity-0">
                                        <div v-show="expandedHelp === 'buy'" class="overflow-hidden">
                                            <div class="px-4 py-2 mt-2">
                                                <p class="text-sm text-slate-500 mb-3 leading-relaxed">
                                                    اختر هذا الخيار إذا كنت تنوي شراء سيارة مستعملة وتحتاج إلى تأمين
                                                    ساري المفعول لإتمام عملية نقل الملكية. يجب أن يكون لديك تأمين قبل أن
                                                    تتمكن من نقل ملكية السيارة في المرور.
                                                    <br /><br />
                                                    <strong>مثال:</strong> وجدت سيارة مستعملة أعجبتني وأتفقت مع البائع.
                                                    الآن أحتاج لتأمين قبل إتمام نقل الملكية.
                                                </p>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </div>

                            <!-- Option 3: تأمين للبطاقات الجمركية -->
                            <div>
                                <div class="flex flex-col px-4 py-2 border-2 rounded-md transition-colors"
                                    :class="expandedHelp === 'import' ? 'border-primary/30 bg-blue-50/50' : 'border-slate-200 bg-slate-50'">
                                    <div class="cursor-pointer flex items-center gap-2 pb-2 border-b border-slate-200"
                                        @click="toggleHelp('import')">
                                        <div class="bg-sky-200 rounded-full shrink-0">
                                            <img :src="importedCarLogo" alt="تأمين للبطاقات الجمركية"
                                                class="max-w-full p-2 w-10 h-auto" loading="lazy" width="40" height="40" />
                                        </div>
                                        <h3 class="text-sm font-medium flex-1">تأمين للبطاقات الجمركية</h3>
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                            width="18" height="18"
                                            class="shrink-0 text-slate-400 transition-transform duration-200"
                                            :class="expandedHelp === 'import' ? 'rotate-180' : ''">
                                            <path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="1.5"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </div>
                                    <Transition enter-active-class="transition-all duration-300 ease-out"
                                        enter-from-class="max-h-0 opacity-0" enter-to-class="max-h-40 opacity-100"
                                        leave-active-class="transition-all duration-200 ease-in"
                                        leave-from-class="max-h-40 opacity-100" leave-to-class="max-h-0 opacity-0">
                                        <div v-show="expandedHelp === 'import'" class="overflow-hidden">
                                            <div class="px-4 py-2 mt-2">
                                                <p class="text-sm text-slate-500 mb-3 leading-relaxed">
                                                    اختر هذا الخيار إذا كانت السيارة مستوردة من خارج المملكة ولديك
                                                    بطاقة جمركية أو شهادة ملكية (حيازة). ستحتاج إلى تأمين لتسجيل
                                                    السيارة لدى المرور.
                                                    <br /><br />
                                                    <strong>مثال:</strong> استوردت سيارة جديدة من الخارج وحصلت على
                                                    البطاقة الجمركية. الآن أحتاج تأمين لتسجيلها.
                                                </p>
                                            </div>
                                        </div>
                                    </Transition>
                                </div>
                            </div>
                        </div>
                    </Dialog>

                    <!-- Back / السابق link (hidden on this first step, shown for navigation pattern) -->
                    <router-link to="/" class="items-center gap-2 cursor-pointer hidden md:flex mt-4">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                            class="shrink-0 text-gray-600">
                            <path d="M19 12H5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M14 17L19 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                            <path d="M14 7L19 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                        <span class="text-gray-600 font-bold text-sm">السابق</span>
                    </router-link>
                </div>

                <!-- Right Sidebar — 2/6 on desktop -->
                <div class="w-full lg:w-2/6 p-4">
                    <div class="hidden lg:block">

                        <!-- Info Tip Card (appears when insurance type is selected) -->
                        <Transition enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                            <div v-if="selectedType" class="border-2 border-primary rounded-lg p-4">
                                <div class="flex gap-2 items-start mb-2">
                                    <img :src="infoIconSrc" alt="Info" class="w-6 h-6 mt-0.5" loading="lazy" width="24" height="24" />
                                    <span class="text-sm font-medium text-primary">{{ selectedType.title }}</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">
                                    {{ selectedType.tip }}
                                </p>
                            </div>
                        </Transition>

                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, defineAsyncComponent } from 'vue';
import { useRouter } from 'vue-router';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
const Dialog = defineAsyncComponent( () => import( '@/components/ui/Dialog.vue' ) );

const router = useRouter();
const { startSession, trackStep, resumeSession } = useQuoteTracking();

//
onMounted( () =>
{
    resumeSession( 'motorapp' );
} );

//
const currentStep = ref( 0 ); // 0-indexed: 0 = المعلومات الأساسية

const steps = [
    { label: 'المعلومات الأساسية' },
    { label: 'تفاصيل السيارة' },
    { label: 'تفاصيل الوثيقة' },
];

//
const carInsuranceLogo = new URL( '../../../images/motorapp/car-insurance-logo.webp', import.meta.url ).href;
const carBuyingLogo = new URL( '../../../images/motorapp/car-insurance-logo.webp', import.meta.url ).href;
const importedCarLogo = new URL( '../../../images/motorapp/imported-car.webp', import.meta.url ).href;
const mojazLogo = new URL( '../../../images/motorapp/mojaz.webp', import.meta.url ).href;
const infoIconSrc = new URL( '../../../images/motorapp/info-icon.svg', import.meta.url ).href;

//
const insuranceOptions = [
    {
        id: 'renew',
        icon: carInsuranceLogo,
        title: 'تأمين أو تجديد تأمين السيارة',
        description: 'اختر هذا إذا كنت تملك المركبة وكانت وثيقة التأمين قد انتهت أو أوشكت على الانتهاء',
        tip: 'تأمين السيارة يحميك ماليًا من الحوادث والسرقة والمواقف غير المتوقعة. فهم التفاصيل يساعدك على اتخاذ قرار صحيح سواء كنت تشتري أو تجدد التأمين.',
    },
    {
        id: 'buy',
        icon: carBuyingLogo,
        title: 'سيارة أشتريها',
        description: 'اختر هذا إذا كانت السيارة مستخدمة وتريد نقل ملكيتها.',
        tip: 'عند شراء سيارة مستعملة، تحتاج لتأمين ساري المفعول قبل إتمام نقل الملكية. اختر هذا الخيار للحصول على تغطية فورية.',
    },
    {
        id: 'import',
        icon: importedCarLogo,
        title: 'سيارة مستوردة',
        description: 'اختر هذا إذا كانت السيارة جديدة أو لديك شهادة ملكية (حيازة).',
        tip: 'السيارات المستوردة تحتاج لتأمين خاص قبل تسجيلها. سنساعدك في الحصول على أفضل عرض يناسب سيارتك الجديدة.',
    },
];

//
const otherServices = [
    {
        id: 'mojaz',
        icon: mojazLogo,
        title: 'التحقق من تاريخ السيارة (موجز)',
        description: 'احصل على معلومات حول تاريخ السيارة منذ دخولها المملكة.',
    },
];

//
const selectedType = ref( null );
const selectedService = ref( null );

// "ماذا أختار" help modal
const showHelpModal = ref( false );
const expandedHelp = ref( 'renew' ); // first item open by default

function toggleHelp ( id )
{
    expandedHelp.value = expandedHelp.value === id ? null : id;
}

async function selectInsuranceType ( option )
{
    selectedService.value = null;
    selectedType.value = option;

    // Start a quote tracking session
    await startSession( option.id );
    await trackStep( 'vehicleDetails', 2, null, 'next' );

    // Store selected type info for the next page
    sessionStorage.setItem( 'selectedInsuranceType', JSON.stringify( {
        title: option.title,
        tip: option.tip,
    } ) );

    // Navigate based on insurance type
    setTimeout( () =>
    {
        const targetRoute = option.id === 'import'
            ? { name: 'importedCar' }
            : option.id === 'buy'
                ? { name: 'ownershipTransfer' }
                : { name: 'basicDetails' };
        router.push( targetRoute );
    }, 300 );
}

function selectService ( service )
{
    selectedType.value = null;
    selectedService.value = service;

    if ( service.id === 'mojaz' ) {
        setTimeout( () => {
            router.push( { name: 'mojaz', query: { step: '1' } } );
        }, 300 );
    }
}
</script>
