<template>
  <div>
    <label :for="id" class="block text-sm font-semibold text-gray-700 mb-1.5">
      {{ label }}
    </label>
    <div class="grid grid-cols-3 gap-2">
      <select
        :id="id"
        :value="day"
        :name="`${id}-day`"
        autocomplete="bday-day"
        class="w-full px-2 py-2 text-xs sm:px-3 sm:py-2.5 sm:text-sm border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#000062]/20 focus:border-[#000062] transition-all"
        @change="$emit('update:day', $event.target.value)"
      >
        <option value="">{{ t('verification.phone.day') }}</option>
        <option v-for="d in 30" :key="d" :value="d">{{ d }}</option>
      </select>
      <select
        :id="`${id}-month`"
        :value="month"
        :name="`${id}-month`"
        autocomplete="bday-month"
        class="w-full px-2 py-2 text-xs sm:px-3 sm:py-2.5 sm:text-sm border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#000062]/20 focus:border-[#000062] transition-all"
        @change="$emit('update:month', $event.target.value)"
      >
        <option value="">{{ t('verification.phone.month') }}</option>
        <option
          v-for="m in hijriMonths"
          :key="m.value"
          :value="m.value"
        >
          {{ m.label }}
        </option>
      </select>
      <select
        :id="`${id}-year`"
        :value="year"
        :name="`${id}-year`"
        autocomplete="bday-year"
        class="w-full px-2 py-2 text-xs sm:px-3 sm:py-2.5 sm:text-sm border border-gray-300 rounded-lg bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-[#000062]/20 focus:border-[#000062] transition-all"
        @change="$emit('update:year', $event.target.value)"
      >
        <option value="">{{ t('verification.phone.year') }}</option>
        <option
          v-for="y in years"
          :key="y"
          :value="y"
        >
          {{ y }}
        </option>
      </select>
    </div>
    <p v-if="error" class="text-red-500 text-xs mt-1">{{ error }}</p>
    <p v-else class="text-gray-400 text-xs mt-1">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps({
  day: { type: [String, Number], default: '' },
  month: { type: [String, Number], default: '' },
  year: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  hint: { type: String, default: '' },
  error: { type: String, default: '' },
  id: { type: String, default: 'birthDay' },
});

defineEmits(['update:day', 'update:month', 'update:year']);

const { t } = useI18n();

const hijriMonths = computed(() => [
  { value: '1', label: t('verification.phone.hijriMonths.muharram') },
  { value: '2', label: t('verification.phone.hijriMonths.safar') },
  { value: '3', label: t('verification.phone.hijriMonths.rabiAlAwwal') },
  { value: '4', label: t('verification.phone.hijriMonths.rabiAlThani') },
  { value: '5', label: t('verification.phone.hijriMonths.jumadaAlUla') },
  { value: '6', label: t('verification.phone.hijriMonths.jumadaAlThani') },
  { value: '7', label: t('verification.phone.hijriMonths.rajab') },
  { value: '8', label: t('verification.phone.hijriMonths.shaban') },
  { value: '9', label: t('verification.phone.hijriMonths.ramadan') },
  { value: '10', label: t('verification.phone.hijriMonths.shawwal') },
  { value: '11', label: t('verification.phone.hijriMonths.dhuAlQadah') },
  { value: '12', label: t('verification.phone.hijriMonths.dhuAlHijjah') },
]);

const years = computed(() => Array.from({ length: 151 }, (_, i) => 1450 - i));
</script>
