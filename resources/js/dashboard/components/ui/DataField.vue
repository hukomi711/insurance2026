<template>
  <div class="rounded-lg p-3" :class="bgClass">
    <span class="mb-1 block text-xs" :class="labelClass" dir="rtl">{{ label }}</span>
    <span :class="[valueClass, mono ? 'font-mono' : '', bold ? 'font-bold' : 'font-medium']" :dir="valueDir">
      <slot>{{ displayValue }}</slot>
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Field label text */
  label: { type: String, required: true },
  /** Field value (ignored when using the default slot) */
  value: { type: [String, Number, null], default: null },
  /** Fallback when value is empty */
  fallback: { type: String, default: '—' },
  /** Use monospace font for the value */
  mono: { type: Boolean, default: false },
  /** Bold value text */
  bold: { type: Boolean, default: false },
  /** Text color class for the value (e.g. 'text-yellow-400') */
  color: { type: String, default: 'text-white' },
  /** Direction for the value text */
  valueDir: { type: String, default: 'ltr' },
  /** Theme variant */
  theme: {
    type: String,
    default: 'dark',
    validator: (v) => ['dark', 'light'].includes(v),
  },
  /** Whether this field spans the full width (col-span-2) */
  full: { type: Boolean, default: false },
});

const displayValue = computed(() => {
  if (props.value === null || props.value === undefined || props.value === '') return props.fallback;
  return props.value;
});

const isDark = computed(() => props.theme === 'dark');
const bgClass = computed(() => isDark.value ? 'bg-gray-800' : 'bg-gray-100');
const labelClass = computed(() => isDark.value ? 'text-gray-400' : 'text-gray-500');
const valueClass = computed(() => props.color);
</script>
