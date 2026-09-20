<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div v-if="isVisible" dir="rtl" class="flex justify-between gap-2 items-center py-2 px-4 bg-yellow-400 safe-area-top sticky top-0 z-40 shadow-md">
            <div class="flex gap-2 items-center flex-1">
                <div class="flex flex-col justify-center">
                    <span class="text-xs font-medium text-slate-900">اشتر وثيقتك واستمتع بأسعار تبدأ من <strong>399 ر.س</strong> على تأمينك</span>
                </div>
            </div>
            <div class="flex gap-2 items-center shrink-0">
                <button
                    class="flex cursor-pointer items-center justify-center min-w-11 min-h-11 rounded-lg hover:bg-black/10 active:bg-black/20 transition-all"
                    aria-label="إغلاق البانر"
                    @click="closeBanner"
                >
                    <span class="font-bold text-2xl leading-none text-slate-900">×</span>
                </button>
            </div>
        </div>
    </transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isVisible = ref( false );

const closeBanner = () => {
    isVisible.value = false;
    // Save banner state to localStorage so it doesn't reappear in this session
    sessionStorage.setItem( 'pricingBannerClosed', 'true' );
};

onMounted( () => {
    // Only show banner if it hasn't been closed in this session
    if ( !sessionStorage.getItem( 'pricingBannerClosed' ) ) {
        isVisible.value = true;
    }
} );
</script>
