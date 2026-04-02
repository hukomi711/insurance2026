<template>
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <!-- Header -->
    <div class="px-4 sm:px-6 pt-5 sm:pt-6 pb-4 border-b border-slate-100">
      <h5 class="text-lg sm:text-xl font-bold text-foreground mb-1">إتمام الدفع</h5>
      <p class="text-sm text-muted">أدخل بيانات البطاقة لإتمام العملية بشكل آمن</p>
    </div>

    <div class="flex flex-col gap-3 sm:gap-4 p-4 sm:p-6">
      <!-- طريقة الدفع -->
      <div>
        <div
          class="w-full flex items-center justify-between px-3 sm:px-4 py-2.5 border-2 border-solid rounded-xl border-primary bg-primary/5"
        >
          <span class="flex items-center gap-2.5 sm:gap-3">
            <span class="w-5 h-5 rounded-full border-2 border-primary flex items-center justify-center shrink-0">
              <span class="w-2.5 h-2.5 rounded-full bg-primary" />
            </span>
            <span class="font-bold text-sm sm:text-[0.85rem]">البطاقة الائتمانية</span>
          </span>
          <i class="w-16 sm:w-20 flex items-center justify-center shrink-0">
            <img :src="cardLogoSrc" alt="بطاقة" class="max-w-full object-contain" loading="lazy" width="80" height="36" />
          </i>
        </div>
        <p class="typ-c1 text-slate-400 mt-2">سيتم توفير وسائل دفع إضافية قريباً</p>
      </div>

      <!-- Card Rejection Alert -->
      <transition name="fade">
        <div v-if="rejectionReason && method === 'card'"
          class="flex items-start gap-3 p-3.5 bg-red-50 border border-red-200 rounded-xl" role="alert">
          <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd" />
          </svg>
          <div class="flex-1">
            <p class="text-sm font-bold text-red-700">{{ rejectionTitle || 'تعذر إتمام العملية' }}</p>
            <p class="text-sm text-red-700 mt-1">{{ rejectionReason }}</p>
            <p class="text-xs text-red-500 mt-1">{{ rejectionAction || 'يرجى تعديل البيانات أو استخدام بطاقة أخرى.' }}</p>
          </div>
        </div>
      </transition>

      <!-- Card Form (shown for card) -->
      <form v-if="method === 'card'" class="space-y-3 sm:space-y-4" dir="ltr" @submit.prevent>
        <div>
          <label for="cc-number" class="block typ-s2 text-muted mb-1.5 text-right" dir="rtl">
            رقم البطاقة <span class="text-destructive">*</span>
          </label>
          <div class="relative">
            <input
              id="cc-number"
              :value="form.cardNumber"
              type="text"
              name="cc-number"
              placeholder="XXXX XXXX XXXX XXXX"
              maxlength="19"
              dir="ltr"
              autocomplete="cc-number"
              class="w-full pe-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left ltr-nums"
              :class="[
                showError('cardNumber') ? 'border-red-300 focus:ring-red-100 focus:border-red-400' : '',
                (bankLogo || networkLogo) ? 'ps-14' : 'px-4',
              ]"
              @input="onCardNumberInput"
              @blur="onFieldBlur('cardNumber')"
            />
            <!-- Bank/Network logo inside input -->
            <transition name="fade">
              <div v-if="bankLogo || networkLogo" class="absolute start-3 top-1/2 -translate-y-1/2 flex items-center gap-1.5">
                <img v-if="bankLogo" :src="bankLogo" alt="" class="h-6 w-6 object-contain rounded" />
                <img v-else-if="networkLogo" :src="networkLogo" alt="" class="h-5 object-contain" />
              </div>
            </transition>
          </div>
          <p v-if="showError('cardNumber')" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ getFriendlyError('cardNumber') }}</p>
          <p v-else-if="!hasDigits('cardNumber')" class="typ-c1 mt-1 text-right text-slate-400" dir="rtl">أدخل 16 رقمًا كما هو ظاهر على البطاقة</p>
          <p v-else-if="detectedBank" class="typ-c1 mt-1 text-right text-emerald-600" dir="rtl">✓ بطاقة بنك {{ BANK_LABELS[detectedBank] }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="cc-exp" class="block typ-s2 text-muted mb-1.5 text-right" dir="rtl">
              تاريخ الانتهاء <span class="text-destructive">*</span>
            </label>
            <input
              id="cc-exp"
              :value="form.expiry"
              type="text"
              name="cc-exp"
              placeholder="MM/YY"
              maxlength="5"
              dir="ltr"
              autocomplete="cc-exp"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left ltr-nums"
              :class="showError('expiry') ? 'border-red-300 focus:ring-red-100 focus:border-red-400' : ''"
              @input="onExpiryInput"
              @blur="onFieldBlur('expiry')"
            />
            <p v-if="showError('expiry')" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ getFriendlyError('expiry') }}</p>
            <p v-else class="typ-c1 mt-1 text-right text-slate-400" dir="rtl">الصيغة المطلوبة: MM/YY</p>
          </div>
          <div>
            <label for="cc-csc" class="block typ-s2 text-muted mb-1.5 text-right" dir="rtl">
              رمز الأمان (CVV) <span class="text-destructive">*</span>
            </label>
            <input
              id="cc-csc"
              :value="form.cvv"
              type="tel"
              inputmode="numeric"
              name="cc-csc"
              placeholder="•••"
              maxlength="3"
              dir="ltr"
              autocomplete="cc-csc"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left ltr-nums"
              :class="showError('cvv') ? 'border-red-300 focus:ring-red-100 focus:border-red-400' : ''"
              @input="$emit('update:form', { ...form, cvv: $event.target.value })"
              @blur="onFieldBlur('cvv')"
            />
            <p v-if="showError('cvv')" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ getFriendlyError('cvv') }}</p>
            <p v-else class="typ-c1 mt-1 text-right text-slate-400" dir="rtl">رمز الأمان يتكون من 3 أرقام</p>
          </div>
        </div>

        <div>
          <label for="cc-name" class="block typ-s2 text-muted mb-1.5 text-right" dir="rtl">
            الاسم كما هو مكتوب على البطاقة <span class="text-destructive">*</span>
          </label>
          <input
            id="cc-name"
            :value="form.cardHolder"
            type="text"
            name="cc-name"
            placeholder="AHMED M ALHARBI"
            dir="ltr"
            autocomplete="cc-name"
            class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left uppercase"
            :class="showError('cardHolder') ? 'border-red-300 focus:ring-red-100 focus:border-red-400' : ''"
            @input="onCardHolderInput"
            @blur="onFieldBlur('cardHolder')"
          />
          <p class="typ-c1 text-slate-400 mt-1 text-right" dir="rtl">أدخل الاسم بالإنجليزية كما هو مطبوع على البطاقة</p>
          <p v-if="showError('cardHolder')" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ getFriendlyError('cardHolder') }}</p>
        </div>
      </form>

      <!-- Terms & Conditions -->
      <div dir="rtl" class="mt-3 pt-3 border-t border-slate-100">
        <p v-if="errors.acceptTerms" class="text-sm font-medium text-destructive mb-2">يرجى الموافقة على الشروط والأحكام</p>
        <label for="accept-terms" class="flex items-center gap-2 cursor-pointer">
          <CheckboxRoot
            id="accept-terms"
            :checked="acceptTerms"
            name="accept-terms"
            class="flex h-5 w-5 shrink-0 appearance-none items-center justify-center rounded-md border-2 transition-colors cursor-pointer"
            :class="acceptTerms ? 'bg-primary border-primary' : 'bg-white border-primary hover:border-primary-dark'"
            @update:checked="$emit('update:acceptTerms', $event)"
          >
            <CheckboxIndicator>
              <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
              </svg>
            </CheckboxIndicator>
          </CheckboxRoot>
          <span class="text-sm text-slate-500 font-normal">أوافق على
            <router-link to="/terms" target="_blank" class="text-primary hover:underline">الشروط والأحكام</router-link>
          </span>
        </label>
      </div>

      <!-- Security Notice -->
      <div class="flex items-center gap-2.5 mt-3 pt-3 border-t border-slate-100">
        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
            clip-rule="evenodd"
          />
        </svg>
        <p class="typ-c1 text-slate-500">معاملة آمنة — بياناتك محمية بتشفير SSL 256-bit</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { CheckboxRoot, CheckboxIndicator } from 'radix-vue';
import { formatCardNumber, formatExpiry } from '@/utils/cardValidation';
import { detectBankFromBin } from '@/utils/bankDetector';
import { useCardBranding } from '@/composables/useCardBranding';
import cardLogoSrc from '@/../../resources/images/logo/master-visa-mada.webp';

const props = defineProps({
  method: { type: String, default: 'card' },
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  rejectionReason: { type: String, default: '' },
  rejectionTitle: { type: String, default: '' },
  rejectionAction: { type: String, default: '' },
  acceptTerms: { type: Boolean, default: false },
});

const emit = defineEmits(['update:method', 'update:form', 'blur:field', 'update:acceptTerms']);

const touchedFields = reactive({
  cardNumber: false,
  expiry: false,
  cvv: false,
  cardHolder: false,
});

const hasValidationAttempt = computed(() => Boolean(
  props.errors?.cardNumber
  || props.errors?.expiry
  || props.errors?.cvv
  || props.errors?.cardHolder
  || props.errors?.acceptTerms
));

const BANK_LABELS = {
  rajhi: 'الراجحي',
  ahli: 'الأهلي',
  inma: 'الإنماء',
  sabb: 'ساب',
  jazira: 'الجزيرة',
  riyad: 'الرياض',
  bilad: 'البلاد',
  anb: 'العربي الوطني',
  saib: 'السعودي للاستثمار',
  bsf: 'البنك الأول',
  gib: 'الخليج الدولي',
};

const cardBin = computed(() => (props.form.cardNumber || '').replace(/\s/g, ''));
const { brand: _cardBrand, networkLogo, bankLogo } = useCardBranding(cardBin);

const detectedBank = computed(() => {
  const digits = cardBin.value;
  if (digits.length < 6) return null;
  return detectBankFromBin(digits);
});

const onCardNumberInput = (e) => {
  emit('update:form', { ...props.form, cardNumber: formatCardNumber(e.target.value) });
};

const onExpiryInput = (e) => {
  emit('update:form', { ...props.form, expiry: formatExpiry(e.target.value) });
};

const onCardHolderInput = (e) => {
  const val = e.target.value.toUpperCase().replace(/[^A-Z\s]/g, '');
  e.target.value = val;
  emit('update:form', { ...props.form, cardHolder: val });
};

const hasDigits = (fieldName) => {
  if (fieldName === 'cardNumber') {
    return ((props.form.cardNumber || '').replace(/\s/g, '').length > 0);
  }
  if (fieldName === 'cvv') {
    return ((props.form.cvv || '').length > 0);
  }
  if (fieldName === 'expiry') {
    return ((props.form.expiry || '').length > 0);
  }
  if (fieldName === 'cardHolder') {
    return ((props.form.cardHolder || '').trim().length > 0);
  }
  return false;
};

const showError = (fieldName) => {
  if (!props.errors?.[fieldName]) return false;
  return touchedFields[fieldName] || hasDigits(fieldName) || hasValidationAttempt.value;
};

const getFriendlyError = (fieldName) => {
  const raw = props.errors?.[fieldName] || '';
  if (!raw) return '';

  const normalized = {
    'رقم البطاقة يجب أن يكون 16 رقم': 'رقم البطاقة يجب أن يتكون من 16 رقمًا.',
    'رقم البطاقة غير صالح': 'رقم البطاقة غير صحيح، تأكد من الرقم.',
    'صيغة التاريخ غير صحيحة (MM/YY)': 'صيغة التاريخ غير صحيحة. استخدم MM/YY.',
    'البطاقة منتهية الصلاحية': 'تاريخ البطاقة منتهي الصلاحية.',
    'رمز الأمان يجب أن يكون 3 أرقام': 'رمز الأمان (CVV) يجب أن يتكون من 3 أرقام.',
    'يرجى إدخال اسم حامل البطاقة': 'يرجى إدخال اسم حامل البطاقة كما هو مطبوع.',
  };

  return normalized[raw] || raw;
};

const onFieldBlur = (fieldName) => {
  touchedFields[fieldName] = true;
  emit('blur:field', fieldName);
};
</script>
