<template>
  <div class="card-nafath">
    <div class="bg-[#000062] text-center px-6 py-5">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.citLogo')"
        class="h-12 w-auto mx-auto mb-3"
      />
      <h4 class="text-white text-lg font-bold mb-1">
        {{ t('verification.phone.waitingForOtp') }}
      </h4>
      <p class="text-white/60 text-xs">{{ t('verification.phone.otpSent') }}</p>
    </div>

    <div class="p-5 sm:p-6 text-center">
      <div class="bg-gray-50 rounded-lg p-4 mb-5 border border-gray-200">
        <p class="text-gray-500 text-xs mb-1">
          {{ t('verification.phone.phoneNumberLabel') }}
        </p>
        <p class="text-lg font-bold text-gray-900 font-mono" dir="ltr">{{ phoneNumber }}</p>
      </div>

      <p class="text-gray-600 text-sm mb-5">
        {{ t('verification.phone.codeWillArrive') }}
        <strong class="text-[#000062] text-lg">{{ countdownFormatted }}</strong>
      </p>

      <!-- Loader -->
      <div class="flex items-center justify-center gap-3 mb-5">
        <svg class="h-6 w-6 animate-spin text-[#000062]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span class="text-gray-500 text-sm">{{ t('verification.phone.waitingForOtp') }}...</span>
      </div>

      <button
        type="button"
        class="w-full py-3 bg-[#000062] text-white font-bold text-sm rounded-lg hover:bg-[#00004d] transition-all"
        @click="$emit('enter-manually')"
      >
        {{ t('verification.otp.enterCode') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
  phoneNumber: { type: String, required: true },
  countdownFormatted: { type: String, required: true },
});

defineEmits(['enter-manually']);

const { t } = useI18n();
</script>
