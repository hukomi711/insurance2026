<template>
  <button
    :class="[baseClass, computedSizeClass, computedVariantClass]"
    :disabled="disabled || loading"
  >
    <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
    <slot>{{ label }}</slot>
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: { type: String, default: '' },
  /**
   * accept    – green (confirm / approve)
   * reject    – red   (reject / deny)
   * secondary – slate-700 (neutral)
   * ghost     – transparent text-gray-400
   * purple    – purple-600
   */
  variant: {
    type: String,
    default: 'secondary',
    validator: (v) => ['accept', 'reject', 'secondary', 'ghost', 'purple'].includes(v),
  },
  /** sm | md */
  size: { type: String, default: 'md', validator: (v) => ['sm', 'md'].includes(v) },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
});

const baseClass =
  'inline-flex items-center justify-center font-semibold transition-all duration-200 select-none active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed';

const computedSizeClass = computed(() =>
  props.size === 'sm'
    ? 'px-3 py-1.5 rounded-lg text-sm'
    : 'px-4 py-2 rounded-xl text-sm'
);

const variantMap = {
  accept:    'bg-green-600 text-white hover:bg-green-700',
  reject:    'bg-red-600 text-white hover:bg-red-700',
  secondary: 'admin-button--secondary',
  ghost:     'admin-button--ghost',
  purple:    'bg-purple-600 text-white hover:bg-purple-500',
};

const computedVariantClass = computed(() => variantMap[props.variant] || variantMap.secondary);
</script>

<style scoped>
.admin-button--secondary {
  color: var(--admin-text);
  background: var(--admin-surface-3);
  border: 1px solid var(--admin-card-border);
}

.admin-button--secondary:hover,
.admin-button--ghost:hover {
  background: var(--admin-hover-accent);
  color: var(--admin-text);
}

.admin-button--ghost {
  color: var(--admin-text-muted);
}
</style>
