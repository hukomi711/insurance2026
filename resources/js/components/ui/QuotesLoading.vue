<template>
    <div class="min-h-screen bg-slate-50" dir="rtl">
        <div class="box p-0 mx-auto">
            <div class="flex flex-col gap-x-16">
                <div class="xl:col-span-2 flex flex-col gap-8">

                    <!-- Title -->
                    <div class="flex flex-col gap-2 xl:col-span-2 pt-6">
                        <h1 class="typ-h1 flex items-center gap-2">
                            جاري تحميل العروض...
                        </h1>
                    </div>

                    <!-- Loading Card -->
                    <div
                        class="border bg-white rounded-lg center gap-8 flex-col my-4 min-h-[300px] max-h-[80vh] shadow-xs overflow-hidden @container">

                        <!-- Animated Car Image -->
                        <img class="animate-pulse max-w-[249px]" :src="carInsuranceLogo" alt="جاري التحميل" width="249" height="249" />

                        <!-- Waiting Text -->
                        <section class="text-center">
                            <div class="flex items-center typ-t2 font-bold center">
                                <span>يرجى الانتظار</span>
                                <div class="flex gap-0.5 mr-1">
                                    <span class="animate-pulse" style="animation-delay: 0ms;">.</span>
                                    <span class="animate-pulse" style="animation-delay: 400ms;">.</span>
                                    <span class="animate-pulse" style="animation-delay: 800ms;">.</span>
                                </div>
                            </div>
                            <p class="typ-b2 text-muted mt-1">
                                أفضل عروض التأمين على بُعد لحظات!
                            </p>
                        </section>

                        <!-- Progress Bar -->
                        <div v-if="showProgress" class="w-64 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-main rounded-full transition-all duration-500 ease-out"
                                :style="{ width: progressPercent + '%' }"></div>
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
/**
 * QuotesLoading — Full-page loading state shown while fetching insurance quotes.
 *
 * Displays an animated car image, pulsing dots, and optional progress bar.
 * Used as the initial state in ComparePage before quotes are loaded.
 *
 * @example
 * <QuotesLoading v-if="isLoadingQuotes" :progress="fetchProgress" @back="goBack" />
 */
import { computed } from 'vue';

const carInsuranceLogo = new URL( '../../../images/motorapp/car-insurance-logo.webp', import.meta.url ).href;

const props = defineProps( {
    /** Progress percentage (0–100). If > 0, progress bar is shown. */
    progress: { type: Number, default: 0 },
} );

defineEmits( [ 'back' ] );

const showProgress = computed( () => props.progress > 0 );
const progressPercent = computed( () => Math.min( 100, Math.max( 0, props.progress ) ) );
</script>
