<template>
  <div class="admin-info-row rounded-lg p-3">
    <span class="mb-1 block text-xs" style="color: var(--admin-text-dim)" dir="rtl">{{ label }}</span>
    <span :class="[valueClass, mono ? 'font-mono' : '', bold ? 'font-bold' : 'font-medium']" :style="defaultValueStyle" :dir="valueDir">
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
  /** Whether this field spans the full width (col-span-2) */
  full: { type: Boolean, default: false },
});

const displayValue = computed(() => {
  if (props.value === null || props.value === undefined || props.value === '') return props.fallback;
  return props.value;
});

const valueClass = computed(() => props.color);
const defaultValueStyle = computed(() =>
  props.color === 'text-white' ? { color: 'var(--admin-text)' } : undefined
);
</script>

<style scoped>
.admin-info-row {
  background: var(--admin-surface-2);
  border: 1px solid var(--admin-border);
}
</style>
