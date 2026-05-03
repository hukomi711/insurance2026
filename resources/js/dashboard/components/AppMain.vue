<template>
    <main class="min-w-0 overflow-x-hidden p-4 sm:p-6 lg:p-8">
        <router-view v-slot="{ Component }">
            <template v-if="Component">
                <transition name="fade" mode="out-in">
                    <KeepAlive :max="5" :include="['DashboardHome', 'PoliciesPage', 'ClaimsPage', 'CompaniesPage', 'CustomerActivityPage', 'QuoteMonitorPage', 'LoginAttemptsPage']">
                        <Suspense @fallback="onSuspenseFallback">
                            <component :is="Component" :key="$route.path" />
                            <template #fallback>
                                <div class="flex items-center justify-center py-20">
                                    <InsLoading text="جارٍ التحميل..." />
                                </div>
                            </template>
                        </Suspense>
                    </KeepAlive>
                </transition>
            </template>
        </router-view>

        <!-- Scoped error display for dashboard content area -->
        <div v-if="contentError" class="flex flex-col items-center justify-center py-20 text-center">
            <p class="text-red-500 text-lg font-bold mb-2">حدث خطأ أثناء تحميل الصفحة</p>
            <p class="text-gray-500 text-sm mb-4">{{ contentError }}</p>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm hover:bg-blue-700 transition-colors" @click="contentError = null">
                إعادة المحاولة
            </button>
        </div>
    </main>
</template>

<script setup>
import { ref, onErrorCaptured } from 'vue';
import InsLoading from '@/components/ui/InsLoading.vue';
import logger from '@/utils/logger';

const contentError = ref( null );

onErrorCaptured( ( err, instance, info ) => {
    contentError.value = err?.message || 'خطأ غير متوقع';
    logger.error( '[AppMain] Error captured in dashboard content:', err, info );
    return false; // stop propagation — handle locally instead of unmounting entire layout
} );

function onSuspenseFallback() {
    // Clear previous error when new content starts loading
    contentError.value = null;
}
</script>
