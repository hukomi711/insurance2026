<template>
  <div class="card-nafath">
    <div class="bg-[#000062] text-center px-6 py-5">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.cstLogo')"
        class="h-12 w-auto mx-auto mb-3"
      />
      <h4 class="text-white text-lg font-bold mb-1">
        {{ t('verification.phone.waitingForOtp') }}
      </h4>
      <p class="text-white/60 text-xs">{{ t('verification.phone.otpSent') }}</p>
    </div>

    <div class="p-5 sm:p-6">
      <!-- Phone Number Display -->
      <div class="bg-gray-50 rounded-lg p-4 mb-5 text-center border border-gray-200">
        <p class="text-gray-500 text-xs mb-1">{{ t('verification.otp.codeSentViaSms') }}</p>
        <p class="text-lg font-bold text-gray-900 font-mono" dir="ltr">{{ phoneNumber }}</p>
        <button
          class="text-[#000062] hover:text-[#00004d] text-xs mt-1 underline"
          @click="$emit('change-phone')"
        >
          {{ t('verification.otp.changePhone') }}
        </button>
      </div>

      <!-- OTP Input -->
      <form class="mb-5" @submit.prevent="emitVerify">
        <OtpInput
          ref="otpInputRef"
          v-model="otpCode"
          :length="6"
          :disabled="processing"
          :error="error"
          :auto-submit="true"
          @submit="emitVerify"
        />
      </form>

      <!-- Timer -->
      <div class="text-center mb-4">
        <p v-if="!isExpired" class="text-sm text-gray-500">
          صلاحية الرمز: <strong class="text-[#000062]">{{ timerFormatted }}</strong>
        </p>
        <p v-else class="text-sm text-red-500 font-semibold">⚠️ انتهت صلاحية الرمز</p>
      </div>

      <!-- Messages -->
      <div
        v-if="error"
        class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm mb-4"
      >
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ error }}</span>
      </div>

      <div
        v-if="success"
        class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-600 text-sm mb-4"
      >
        <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ success }}</span>
      </div>

      <!-- Buttons -->
      <div class="space-y-3">
        <button
          :disabled="!isOtpComplete || processing || isExpired"
          class="w-full py-3 rounded-lg font-bold text-sm transition-all flex items-center justify-center gap-2"
          :class="
            isOtpComplete && !processing && !isExpired
              ? 'bg-[#000062] text-white hover:bg-[#00004d] shadow-sm'
              : 'bg-gray-200 text-gray-400 cursor-not-allowed'
          "
          @click="emitVerify"
        >
          <svg v-if="processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          {{ processing ? t('verification.phone.verifying') : t('verification.phone.confirmCode') }}
        </button>

        <button
          :disabled="!canResend || processing"
          class="w-full py-2.5 rounded-lg font-medium text-sm transition-all border"
          :class="
            canResend
              ? 'border-[#000062] text-[#000062] hover:bg-[#000062]/5'
              : 'border-gray-200 text-gray-400'
          "
          @click="emitResend"
        >
          {{
            canResend
              ? t('verification.phone.resendCode')
              : t('verification.phone.resendAfter', { seconds: resendTimer })
          }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import OtpInput from '@/components/ui/OtpInput.vue';

defineProps({
  phoneNumber: { type: String, required: true },
  error: { type: String, default: '' },
  success: { type: String, default: '' },
  processing: { type: Boolean, default: false },
  timerFormatted: { type: String, required: true },
  isExpired: { type: Boolean, default: false },
  canResend: { type: Boolean, default: false },
  resendTimer: { type: Number, default: 0 },
});

const emit = defineEmits(['verify', 'resend', 'change-phone']);

const { t } = useI18n();
const otpInputRef = ref(null);
const otpCode = ref('');
const isOtpComplete = computed(() => otpCode.value.length === 6);

const emitVerify = () => {
  emit('verify', otpCode.value);
};

const emitResend = () => {
  otpCode.value = '';
  otpInputRef.value?.clear();
  emit('resend');
};

const resetOtp = () => {
  otpCode.value = '';
  otpInputRef.value?.clear();
};

const focusFirst = () => {
  otpInputRef.value?.focusFirstEmpty();
};

defineExpose({ resetOtp, focusFirst });
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
</style>
