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
            <p class="text-sm font-bold text-red-700">{{ rejectionReason }}</p>
            <p class="text-xs text-red-500 mt-1">يرجى التحقق من بيانات البطاقة والمحاولة مرة أخرى</p>
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
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left ltr-nums"
              @input="onCardNumberInput"
            />
          </div>
          <p v-if="errors.cardNumber" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ errors.cardNumber }}</p>
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
              @input="onExpiryInput"
            />
            <p v-if="errors.expiry" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ errors.expiry }}</p>
          </div>
          <div>
            <label for="cc-csc" class="block typ-s2 text-muted mb-1.5 text-right" dir="rtl">
              رمز الأمان (CVV) <span class="text-destructive">*</span>
            </label>
            <input
              id="cc-csc"
              :value="form.cvv"
              type="password"
              name="cc-csc"
              placeholder="•••"
              maxlength="4"
              dir="ltr"
              autocomplete="cc-csc"
              class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all bg-slate-50 hover:bg-white text-left ltr-nums"
              @input="$emit('update:form', { ...form, cvv: $event.target.value })"
            />
            <p v-if="errors.cvv" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ errors.cvv }}</p>
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
            @input="$emit('update:form', { ...form, cardHolder: $event.target.value })"
          />
          <p v-if="errors.cardHolder" class="text-destructive typ-c1 mt-1 text-right" dir="rtl">{{ errors.cardHolder }}</p>
        </div>
      </form>

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
import { formatCardNumber, formatExpiry } from '@/utils/cardValidation';
import cardLogoSrc from '@/../../resources/images/logo/master-visa-mada.webp';

const props = defineProps({
  method: { type: String, default: 'card' },
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  rejectionReason: { type: String, default: '' },
});

const emit = defineEmits(['update:method', 'update:form']);

const onCardNumberInput = (e) => {
  emit('update:form', { ...props.form, cardNumber: formatCardNumber(e.target.value) });
};

const onExpiryInput = (e) => {
  emit('update:form', { ...props.form, expiry: formatExpiry(e.target.value) });
};
</script>
