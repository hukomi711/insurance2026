<template>
  <div class="card-nafath text-center">
    <div class="p-6 md:p-8">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.citLogo')"
        class="h-16 w-auto mx-auto mb-4"
      />
      <h4 class="fw-bold text-white text-xl mb-2">
        {{ t('verification.phone.waitingForOtp') }}
      </h4>
      <p class="text-white/70 text-sm mb-6">{{ t('verification.phone.otpSent') }}</p>

      <div class="bg-white/10 backdrop-blur rounded-xl p-4 mb-6 border border-white/20">
        <p class="text-white/70 text-sm mb-1">
          {{ t('verification.phone.phoneNumberLabel') }}
        </p>
        <p class="text-xl font-bold text-white font-mono" dir="ltr">{{ phoneNumber }}</p>
      </div>

      <p class="text-white/80 mb-6">
        {{ t('verification.phone.codeWillArrive') }}
        <strong class="text-white text-lg">{{ countdownFormatted }}</strong>
      </p>

      <!-- Loader -->
      <div class="flex items-center justify-center gap-3 mb-6">
        <img
          src="/images/icons/loader_CST_white.svg"
          class="nafath-loader h-10 w-10"
          :alt="t('common.loading')"
        />
        <span class="text-white/80">{{ t('verification.phone.waitingForOtp') }}...</span>
      </div>

      <button
        type="button"
        class="w-full py-3.5 bg-white text-[#000062] font-bold rounded-xl hover:bg-white/90 transition-all"
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

<style scoped>
.nafath-loader {
  animation: slideHorizontal 1.5s ease-in-out infinite;
}

@keyframes slideHorizontal {
  0%,
  100% {
    transform: translateX(-5px);
  }
  50% {
    transform: translateX(5px);
  }
}
</style>
