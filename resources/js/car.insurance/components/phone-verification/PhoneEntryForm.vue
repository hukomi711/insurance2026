<template>
  <div class="card-nafath">
    <!-- Header -->
    <div class="bg-[#000062] text-center px-6 py-5">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.cstLogo')"
        class="h-12 w-auto mx-auto mb-3"
      />
      <h4 class="text-white text-lg font-bold mb-1">
        {{ t('verification.phone.verifyPhone') }}
      </h4>
      <p class="text-white/60 text-xs">
        {{ t('verification.phone.enterPhoneAndBirthdate') }}
      </p>
    </div>

    <!-- Form Body -->
    <div class="p-5 sm:p-6">
      <form class="space-y-4" @submit.prevent="$emit('submit')">
        <!-- Carrier Selection -->
        <div>
          <label for="carrierSelect" class="block text-sm font-semibold text-gray-700 mb-1.5">
            {{ t('verification.phone.carrier') }}
          </label>
          <select
            id="carrierSelect"
            :value="selectedCarrier"
            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#000062]/20 focus:border-[#000062] transition-all"
            @change="$emit('update:selectedCarrier', $event.target.value)"
          >
            <option value="" disabled>{{ t('verification.phone.selectCarrier') }}</option>
            <option
              v-for="carrier in carriers"
              :key="carrier.id"
              :value="carrier.id"
            >
              {{ carrier.name }}
            </option>
          </select>
          <p v-if="errors.carrier" class="text-red-500 text-xs mt-1">{{ errors.carrier }}</p>
        </div>

        <!-- Phone Number Input -->
        <div>
          <label for="phoneNumber" class="block text-sm font-semibold text-gray-700 mb-1.5">
            {{ t('verification.phone.phoneNumber') }}
          </label>
          <div class="relative" dir="ltr">
            <div
              class="absolute left-3 top-1/2 -translate-y-1/2 flex flex-row items-center gap-1.5 text-gray-400 border-r border-gray-200 pr-2.5"
              dir="ltr"
              style="direction: ltr; unicode-bidi: embed"
            >
              <span class="text-base leading-none">🇸🇦</span>
              <span class="text-sm font-medium" dir="ltr">+966</span>
            </div>
            <input
              id="phoneNumber"
              name="phoneNumber"
              :value="formData.phone"
              type="tel"
              inputmode="numeric"
              maxlength="9"
              placeholder="5xxxxxxxx"
              autocomplete="tel"
              dir="ltr"
              style="direction: ltr; text-align: left; unicode-bidi: embed"
              class="w-full pl-[6.5rem] pr-3 py-2.5 text-sm border rounded-lg bg-white text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#000062]/20 focus:border-[#000062] transition-all"
              :class="errors.phone ? 'border-red-400' : 'border-gray-300'"
              @input="onPhoneInput"
            />
          </div>
          <p v-if="errors.phone" class="text-red-500 text-xs mt-1">{{ errors.phone }}</p>
          <p v-else class="text-gray-400 text-xs mt-1">
            {{ t('verification.phone.phoneExample') }}
          </p>
        </div>

        <!-- Birth Date Input -->
        <HijriBirthDateInput
          :day="formData.birthDay"
          :month="formData.birthMonth"
          :year="formData.birthYear"
          :label="t('verification.phone.birthdateHijri')"
          :hint="t('verification.phone.birthdateExample')"
          :error="errors.birthdate"
          @update:day="$emit('update:formData', { ...formData, birthDay: $event })"
          @update:month="$emit('update:formData', { ...formData, birthMonth: $event })"
          @update:year="$emit('update:formData', { ...formData, birthYear: $event })"
        />

        <!-- Error Message -->
        <div
          v-if="error"
          class="flex items-center gap-2 p-3 bg-red-50 border border-red-200 rounded-lg text-red-600 text-sm"
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

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="processing || !isFormValid"
          class="w-full py-3 rounded-lg font-bold text-sm transition-all duration-200 flex items-center justify-center gap-2"
          :class="
            !processing && isFormValid
              ? 'bg-[#000062] text-white hover:bg-[#00004d] shadow-sm'
              : 'bg-gray-200 text-gray-400 cursor-not-allowed'
          "
        >
          <svg v-if="processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          {{ processing ? t('verification.phone.sending') : t('verification.phone.send') }}
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import HijriBirthDateInput from './HijriBirthDateInput.vue';

defineProps({
  carriers: { type: Array, required: true },
  selectedCarrier: { type: String, default: '' },
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({ carrier: '', phone: '', birthdate: '' }) },
  error: { type: String, default: '' },
  processing: { type: Boolean, default: false },
  isFormValid: { type: Boolean, default: false },
});

const emit = defineEmits([
  'submit',
  'update:selectedCarrier',
  'update:formData',
]);

const { t } = useI18n();

const onPhoneInput = (event) => {
  const cleaned = event.target.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 9);
  emit('update:formData', { phone: cleaned });
};
</script>
