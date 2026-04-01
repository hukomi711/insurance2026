<template>
    <div class="min-h-screen bg-slate-50" dir="rtl">
        <div class="box p-0 mx-auto">
            <div class="flex flex-col gap-x-16">
                <div class="xl:col-span-2 flex flex-col gap-6">

                    <!-- Title -->
                    <div class="flex flex-col gap-1 xl:col-span-2 pt-6">
                        <h1 class="typ-h1 flex items-center gap-2">
                            نبحث لك عن أفضل الأسعار
                        </h1>
                        <p class="typ-b2 text-muted">نقارن بين <span class="font-bold text-primary">{{ totalCompanies }} شركة تأمين</span> للحصول على أفضل عرض لك</p>
                    </div>

                    <!-- Loading Card -->
                    <div class="border bg-white rounded-2xl center gap-6 flex-col py-10 px-6 shadow-xs overflow-hidden">

                        <!-- Animated Car Image -->
                        <div class="relative">
                            <img class="max-w-[180px] sm:max-w-[220px] animate-float" :src="carInsuranceLogo" alt="جاري التحميل" width="220" height="220" />
                            <div class="absolute inset-0 rounded-full border-2 border-primary/20 animate-ping-slow" />
                        </div>

                        <!-- Active Company Logo Carousel -->
                        <div class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-3 min-w-[260px]">
                            <div class="relative size-10 shrink-0">
                                <transition name="logo-fade" mode="out-in">
                                    <img
                                        :key="activeCompanyIndex"
                                        :src="activeCompanyLogo"
                                        :alt="activeCompanyName"
                                        class="size-10 rounded-lg object-contain bg-white p-1 border border-slate-100"
                                        width="40" height="40"
                                    />
                                </transition>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="typ-c1 text-muted">جاري البحث في</p>
                                <transition name="text-slide" mode="out-in">
                                    <p :key="activeCompanyIndex" class="typ-s2 font-bold text-foreground truncate">{{ activeCompanyName }}</p>
                                </transition>
                            </div>
                            <svg class="w-5 h-5 text-primary animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>
                        </div>

                        <!-- Status Message -->
                        <transition name="text-slide" mode="out-in">
                            <p :key="statusMessage" class="typ-b2 text-muted text-center">{{ statusMessage }}</p>
                        </transition>

                        <!-- Progress Bar -->
                        <div v-if="showProgress" class="w-full max-w-xs">
                            <div class="flex justify-between typ-c1 text-muted mb-1.5">
                                <span>{{ progressPercent }}%</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-main rounded-full transition-all duration-700 ease-out relative"
                                    :style="{ width: progressPercent + '%' }">
                                    <div class="absolute inset-0 bg-gradient-to-l from-white/30 to-transparent animate-shimmer" />
                                </div>
                            </div>
                        </div>

                        <!-- Company Grid -->
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-3 w-full max-w-sm mt-2">
                            <div v-for="(company, i) in companies" :key="company.id"
                                class="flex items-center justify-center p-2 rounded-lg border transition-all duration-500"
                                :class="i < revealedCount ? 'bg-white border-slate-200 opacity-100 scale-100' : 'bg-slate-50 border-transparent opacity-40 scale-95'">
                                <img :src="getCompanyLogo(company.id)" :alt="company.nameAr"
                                    class="size-8 object-contain" width="32" height="32"
                                    :class="{ 'grayscale': i >= revealedCount }" />
                            </div>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="my-4">
                        <button type="button" class="cursor-pointer whitespace-nowrap rounded-md typ-s2 transition-colors text-slate-600 hover:text-slate-700 active:text-slate-800 font-bold inline-flex items-center justify-center gap-2"
                            @click="$emit('back')">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                class="shrink-0 size-4.5 rtl:rotate-180" aria-hidden="true">
                                <path d="M5 12H19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M10 7L5 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M10 17L5 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                            <span>العودة</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { companies } from '@/data/companies';
import { getCompanyLogo } from '@/utils/companyLogos';

const carInsuranceLogo = new URL( '../../../images/motorapp/compare.webp', import.meta.url ).href;

const props = defineProps( {
    progress: { type: Number, default: 0 },
} );

defineEmits( [ 'back' ] );

const totalCompanies = companies.length;
const showProgress = computed( () => props.progress > 0 );
const progressPercent = computed( () => Math.min( 100, Math.max( 0, props.progress ) ) );

// Company carousel
const activeCompanyIndex = ref( 0 );
const activeCompanyLogo = computed( () => getCompanyLogo( companies[activeCompanyIndex.value].id ) );
const activeCompanyName = computed( () => companies[activeCompanyIndex.value].nameAr );

let carouselTimer = null;
onMounted( () => {
    carouselTimer = setInterval( () => {
        activeCompanyIndex.value = ( activeCompanyIndex.value + 1 ) % totalCompanies;
    }, 1800 );
} );
onUnmounted( () => clearInterval( carouselTimer ) );

// Reveal company logos based on progress
const revealedCount = computed( () => Math.ceil( ( progressPercent.value / 100 ) * totalCompanies ) );

// Status messages
const statusMessages = [
    'جاري الاتصال بشركات التأمين...',
    'نقارن الأسعار لنوفر لك الأفضل...',
    'نحلل التغطيات المتاحة...',
    'نجهّز لك أفضل العروض...',
    'اكتملت المقارنة تقريباً...',
];
const statusMessage = computed( () => {
    const idx = Math.min( Math.floor( progressPercent.value / 20 ), statusMessages.length - 1 );
    return statusMessages[idx];
} );
</script>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}
.animate-float { animation: float 3s ease-in-out infinite; }

@keyframes ping-slow {
    0% { transform: scale(0.95); opacity: 0.5; }
    70% { transform: scale(1.3); opacity: 0; }
    100% { transform: scale(1.3); opacity: 0; }
}
.animate-ping-slow { animation: ping-slow 2s ease-out infinite; }

@keyframes shimmer {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}
.animate-shimmer { animation: shimmer 1.5s ease-in-out infinite; }

/* Transitions */
.logo-fade-enter-active, .logo-fade-leave-active { transition: opacity 0.25s ease; }
.logo-fade-enter-from, .logo-fade-leave-to { opacity: 0; }

.text-slide-enter-active, .text-slide-leave-active { transition: all 0.25s ease; }
.text-slide-enter-from { opacity: 0; transform: translateY(6px); }
.text-slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
