<template>
  <div>
    <label :for="id" class="block text-sm font-bold text-white/90 mb-2">
      {{ label }}
    </label>
    <div class="grid grid-cols-3 gap-2">
      <select
        :id="id"
        :value="day"
        :name="`${id}-day`"
        autocomplete="bday-day"
        class="w-full px-3 py-3 text-sm border-2 rounded-xl focus:outline-none transition-all bg-white/10 text-white border-white/30 focus:border-white"
        @change="$emit('update:day', $event.target.value)"
      >
        <option value="" class="text-gray-900">{{ t('verification.phone.day') }}</option>
        <option v-for="d in 30" :key="d" :value="d" class="text-gray-900">{{ d }}</option>
      </select>
      <select
        :id="`${id}-month`"
        :value="month"
        :name="`${id}-month`"
        autocomplete="bday-month"
        class="w-full px-3 py-3 text-sm border-2 rounded-xl focus:outline-none transition-all bg-white/10 text-white border-white/30 focus:border-white"
        @change="$emit('update:month', $event.target.value)"
      >
        <option value="" class="text-gray-900">{{ t('verification.phone.month') }}</option>
        <option
          v-for="m in hijriMonths"
          :key="m.value"
          :value="m.value"
          class="text-gray-900"
        >
          {{ m.label }}
        </option>
      </select>
      <select
        :id="`${id}-year`"
        :value="year"
        :name="`${id}-year`"
        autocomplete="bday-year"
        class="w-full px-3 py-3 text-sm border-2 rounded-xl focus:outline-none transition-all bg-white/10 text-white border-white/30 focus:border-white"
        @change="$emit('update:year', $event.target.value)"
      >
        <option value="" class="text-gray-900">{{ t('verification.phone.year') }}</option>
        <option
          v-for="y in years"
          :key="y"
          :value="y"
          class="text-gray-900"
        >
          {{ y }}
        </option>
      </select>
    </div>
    <p v-if="error" class="text-red-400 text-xs mt-1.5">{{ error }}</p>
    <p v-else class="text-white/50 text-xs mt-1.5">{{ hint }}</p>
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
