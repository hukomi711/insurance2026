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

    <div
        v-if="customerBlocked"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-950 p-6 text-center"
        dir="rtl"
        role="alert"
        aria-live="assertive"
    >
        <div class="w-full max-w-md rounded-2xl border border-white/10 bg-slate-900 p-8 shadow-2xl">
            <i class="fa-solid fa-wifi text-4xl text-amber-400" aria-hidden="true"></i>
            <h1 class="mt-5 text-xl font-bold text-white">ضعف الاتصال</h1>
            <p class="mt-3 text-sm leading-7 text-slate-300">
                {{ customerBlocked?.message || DEFAULT_CUSTOMER_BLOCK_MESSAGE }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import ErrorBoundary from '@/components/ui/ErrorBoundary.vue';
import AppError from '@/components/ui/AppError.vue';
import AppLoader from '@/components/ui/AppLoader.vue';
import { cleanupVisitorTracking } from '@/composables/useVisitorTracking';
import { destroyEcho } from '@/services/echo';
import {
    CUSTOMER_BLOCKED_EVENT,
    clearCustomerBlocked,
    DEFAULT_CUSTOMER_BLOCK_MESSAGE,
    getCustomerBlockState,
} from '@/utils/customerBlock';
import { fetchGeoStatus } from '@/utils/geoCheck';

// Show loader during route transitions — dismiss once navigation completes
const isLoading = ref( true );
const customerBlocked = ref( null );

const router = useRouter();

let hideTimer = null;
let safetyTimer = null;
let initialNavDone = false;
let closeBlockedPageTimer = null;
let blockedStatusPollTimer = null;
const BLOCKED_STATUS_POLL_INTERVAL_MS = 1000;

function isAdminPath ()
{
    return window.location.pathname.startsWith( '/admin' ) ||
        window.location.pathname.startsWith( '/dashboard' );
}

function handleCustomerBlocked ( event )
{
    if ( isAdminPath() ) return;

    customerBlocked.value = event?.detail || getCustomerBlockState() || { blocked: true };
    cleanupVisitorTracking();
    destroyEcho();
    startBlockedStatusPolling();

    clearTimeout( closeBlockedPageTimer );
    closeBlockedPageTimer = setTimeout( () =>
    {
        window.close();
        setTimeout( () => window.location.replace( 'about:blank' ), 100 );
    }, 5000 );
}

function stopBlockedStatusPolling ()
{
    clearInterval( blockedStatusPollTimer );
    blockedStatusPollTimer = null;
}

async function pollBlockedStatus ()
{
    if ( !customerBlocked.value ) {
        stopBlockedStatusPolling();
        return;
    }

    try
    {
        const geo = await fetchGeoStatus( { forceRefresh: true } );
        if ( geo?.customer_blocked === false )
        {
            clearCustomerBlocked();
            customerBlocked.value = null;
            stopBlockedStatusPolling();
            window.location.reload();
        }
    }
    catch
    {
        // Keep the blocked screen until the next poll succeeds.
    }
}

function startBlockedStatusPolling ()
{
    if ( blockedStatusPollTimer ) return;
    blockedStatusPollTimer = setInterval( pollBlockedStatus, BLOCKED_STATUS_POLL_INTERVAL_MS );
    void pollBlockedStatus();
}

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
    window.addEventListener( CUSTOMER_BLOCKED_EVENT, handleCustomerBlocked );
    if ( getCustomerBlockState() ) handleCustomerBlocked();
    window.addEventListener( 'focus', pollBlockedStatus );
    window.addEventListener( 'pageshow', pollBlockedStatus );

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
    clearTimeout( closeBlockedPageTimer );
    stopBlockedStatusPolling();
    window.removeEventListener( CUSTOMER_BLOCKED_EVENT, handleCustomerBlocked );
    window.removeEventListener( 'focus', pollBlockedStatus );
    window.removeEventListener( 'pageshow', pollBlockedStatus );
} );
</script>
