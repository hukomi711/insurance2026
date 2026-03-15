<template>
  <div class="card-nafath">
    <!-- Header -->
    <div class="text-center p-6 pb-4">
      <img
        src="/images/icons/gate_logo_indv_light.svg"
        :alt="t('verification.phone.cstLogo')"
        class="h-16 w-auto mx-auto mb-4"
      />
      <h4 class="fw-bold text-white text-xl mb-2">
        {{ t('verification.phone.verifyPhone') }}
      </h4>
      <p class="text-white/70 text-sm">
        {{ t('verification.phone.enterPhoneAndBirthdate') }}
      </p>
    </div>

    <!-- Form Body -->
    <div class="p-6 pt-0">
      <form class="space-y-5" @submit.prevent="$emit('submit')">
        <!-- Carrier Selection -->
        <div role="group" aria-labelledby="carrier-label">
          <span id="carrier-label" class="block text-sm font-bold text-white/90 mb-3">
            {{ t('verification.phone.carrier') }}
          </span>
          <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-labelledby="carrier-label">
            <button
              v-for="carrier in carriers"
              :key="carrier.id"
              type="button"
              class="relative p-3 rounded-xl border-2 transition-all duration-200 bg-white/10"
              :class="
                selectedCarrier === carrier.id
                  ? 'border-white bg-white/20'
                  : 'border-white/20 hover:border-white/40'
              "
              @click="$emit('update:selectedCarrier', carrier.id)"
            >
              <img
                :src="carrier.logo"
                :alt="carrier.name"
                class="h-7 mx-auto mb-1 object-contain"
              />
              <span class="text-xs font-medium text-white/80 block text-center">
                {{ carrier.name }}
              </span>
              <div
                v-if="selectedCarrier === carrier.id"
                class="absolute -top-1.5 -right-1.5 w-5 h-5 bg-white rounded-full flex items-center justify-center"
              >
                <svg class="w-3 h-3 text-[#000062]" fill="currentColor" viewBox="0 0 20 20">
                  <path
                    fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd"
                  />
                </svg>
              </div>
            </button>
          </div>
          <p v-if="errors.carrier" class="text-red-400 text-xs mt-2">{{ errors.carrier }}</p>
        </div>

        <!-- Phone Number Input -->
        <div>
          <label for="phoneNumber" class="block text-sm font-bold text-white/90 mb-2">
            {{ t('verification.phone.phoneNumber') }}
          </label>
          <div class="relative" dir="ltr">
            <div
              class="absolute left-3 top-1/2 -translate-y-1/2 flex flex-row items-center gap-1.5 text-white/60"
              dir="ltr"
              style="direction: ltr; unicode-bidi: embed"
            >
              <span class="text-lg leading-none">🇸🇦</span>
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
              class="w-full pl-24 pr-4 py-3.5 text-base border-2 rounded-xl focus:outline-none transition-all duration-200 bg-white/10 text-white placeholder-white/40"
              :class="
                errors.phone
                  ? 'border-red-400 bg-red-500/10'
                  : 'border-white/30 focus:border-white focus:bg-white/15'
              "
              @input="onPhoneInput"
            />
          </div>
          <p v-if="errors.phone" class="text-red-400 text-xs mt-1.5">{{ errors.phone }}</p>
          <p v-else class="text-white/50 text-xs mt-1.5">
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
          class="flex items-center gap-2 p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-red-300 text-sm"
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

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="processing || !isFormValid"
          class="w-full py-3.5 rounded-xl font-bold text-base transition-all duration-200 flex items-center justify-center gap-2"
          :class="
            !processing && isFormValid
              ? 'bg-white text-[#000062] hover:bg-white/90'
              : 'bg-white/20 text-white/50 cursor-not-allowed'
          "
        >
          <img
            v-if="processing"
            src="/images/icons/loader_CST_white.svg"
            class="h-5 w-5"
            alt="جاري التحميل"
          />
          <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
            />
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
