<template>
    <ErrorBoundary>
        <template #default>
            <router-view />
        </template>
        <template #error="{ error, reset }">
            <div class="min-h-screen flex items-center justify-center bg-bg">
                <AppError title="حدث خطأ في التطبيق" message="عذراً، حدث خطأ غير متوقع. يرجى إعادة المحاولة."
                    :details="error?.message" @retry="reset" />
            </div>
        </template>
    </ErrorBoundary>

    <!-- Global route‑transition loader -->
    <AppLoader :visible="isLoading" text="جاري التحميل..." />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import ErrorBoundary from '@/components/ui/ErrorBoundary.vue';
import AppError from '@/components/ui/AppError.vue';
import AppLoader from '@/components/ui/AppLoader.vue';

// Show loader during route transitions — dismiss once navigation completes
const isLoading = ref( true );

const router = useRouter();

let hideTimer = null;
let safetyTimer = null;
let initialNavDone = false;

// Safety timeout — force-hide loader after 10s to prevent permanent white screen
// (covers edge case: beforeEach hangs → afterEach never fires)
function scheduleSafetyTimeout ()
{
    clearTimeout( safetyTimer );
    safetyTimer = setTimeout( () =>
    {
        if ( isLoading.value )
        {
            console.warn( '[AppLoader] Safety timeout — force-hiding loader after 10s' );
            isLoading.value = false;
        }
    }, 10000 );
}

// Show loader on navigation start (skip during initial navigation — onMounted handles it)
const removeBeforeEach = router.beforeEach( () =>
{
    if ( !initialNavDone ) return;
    isLoading.value = true;
    scheduleSafetyTimeout();
} );

// Hide loader when navigation finishes (with a small min-display of 300ms for UX)
const removeAfterEach = router.afterEach( () =>
{
    initialNavDone = true;
    clearTimeout( hideTimer );
    clearTimeout( safetyTimer );
    hideTimer = setTimeout( () =>
    {
        isLoading.value = false;
    }, 300 );
} );

// Initial app load — show brief splash then hide
onMounted( () =>
{
    scheduleSafetyTimeout();
    hideTimer = setTimeout( () =>
    {
        isLoading.value = false;
    }, 800 );
} );

onUnmounted( () =>
{
    removeBeforeEach();
    removeAfterEach();
    clearTimeout( hideTimer );
    clearTimeout( safetyTimer );
} );
</script>
