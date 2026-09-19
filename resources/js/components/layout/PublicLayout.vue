<template>
    <div class="min-h-screen flex flex-col">
        <AppBanner v-if="!hideLayout" />
        <AppHeader v-if="!hideLayout" />
        <main class="flex-1">
            <router-view v-slot="{ Component, route: childRoute }">
                <Transition name="fade" mode="out-in">
                    <div :key="childRoute.path">
                        <component :is="Component" />
                    </div>
                </Transition>
            </router-view>

            <!-- Scoped error display for public content area -->
            <div v-if="contentError" class="flex flex-col items-center justify-center py-20 text-center">
                <p class="text-red-500 text-lg font-bold mb-2">حدث خطأ أثناء تحميل الصفحة</p>
                <p class="text-gray-500 text-sm mb-4">{{ contentError }}</p>
                <button class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition-colors" @click="contentError = null">
                    إعادة المحاولة
                </button>
            </div>
        </main>
        <AppFooter v-if="!hideLayout" />
        <BackToTop v-if="!hideLayout" />
    </div>
</template>

<script setup>
import { ref, computed, defineAsyncComponent, onErrorCaptured } from 'vue';
import { useRoute } from 'vue-router';
import AppBanner from '@/components/layout/AppBanner.vue';
import AppHeader from '@/components/layout/AppHeader.vue';
import logger from '@/utils/logger';

// Below-the-fold components — lazy loaded with error resilience
const AppFooter = defineAsyncComponent( {
    loader: () => import( '@/components/layout/AppFooter.vue' ),
    timeout: 10000,
} );
const BackToTop = defineAsyncComponent( {
    loader: () => import( '@/components/layout/BackToTop.vue' ),
    timeout: 10000,
} );

const contentError = ref( null );

const route = useRoute();
const hideLayout = computed( () => !!route.meta?.hideLayout );

onErrorCaptured( ( err, instance, info ) => {
    contentError.value = err?.message || 'خطأ غير متوقع';
    logger.error( '[PublicLayout] Error captured in content:', err, info );
    return false; // stop propagation — handle locally instead of unmounting entire layout
} );
</script>
