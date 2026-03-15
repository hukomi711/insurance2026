<template>
  <div class="card-nafath">
    <div class="text-center p-6 pb-4">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.cstLogo')"
        class="h-16 w-auto mx-auto mb-4"
      />
      <h4 class="fw-bold text-white text-xl mb-2">
        {{ t('verification.phone.waitingForOtp') }}
      </h4>
      <p class="text-white/70 text-sm">{{ t('verification.phone.otpSent') }}</p>
    </div>

    <div class="p-6 pt-0">
      <!-- Phone Number Display -->
      <div class="bg-white/10 backdrop-blur rounded-xl p-4 mb-5 text-center border border-white/20">
        <p class="text-white/70 text-xs mb-1">{{ t('verification.otp.codeSentViaSms') }}</p>
        <p class="text-lg font-bold text-white font-mono" dir="ltr">{{ phoneNumber }}</p>
        <button
          class="text-white/60 hover:text-white text-xs mt-1 underline"
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
          variant="dark"
          @submit="emitVerify"
        />
      </form>

      <!-- Timer -->
      <div class="text-center mb-4">
        <p v-if="!isExpired" class="text-sm text-white/70">
          صلاحية الرمز: <strong class="text-white">{{ timerFormatted }}</strong>
        </p>
        <p v-else class="text-sm text-red-400 font-semibold">⚠️ انتهت صلاحية الرمز</p>
      </div>

      <!-- Messages -->
      <div
        v-if="error"
        class="flex items-center gap-2 p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-red-300 text-sm mb-4"
      >
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
        class="flex items-center gap-2 p-3 bg-emerald-500/20 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm mb-4"
      >
        <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
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
          class="w-full py-3.5 rounded-xl font-bold transition-all flex items-center justify-center gap-2"
          :class="
            isOtpComplete && !processing && !isExpired
              ? 'bg-white text-[#000062] hover:bg-white/90'
              : 'bg-white/20 text-white/50 cursor-not-allowed'
          "
          @click="emitVerify"
        >
          <img
            v-if="processing"
            src="/images/icons/loader_CST_white.svg"
            class="h-5 w-5"
            alt="جاري التحميل"
          />
          {{ processing ? t('verification.phone.verifying') : t('verification.phone.confirmCode') }}
        </button>

        <button
          :disabled="!canResend || processing"
          class="w-full py-3 rounded-xl font-medium transition-all"
          :class="
            canResend
              ? 'bg-emerald-500 hover:bg-emerald-600 text-white'
              : 'bg-white/10 text-white/40'
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
