<template>
  <span
    class="inline-flex items-center gap-1 rounded-full font-medium"
    :class="[variantClass, sizeClass]"
  >
    <span v-if="iconText">{{ iconText }}</span>
    <slot>{{ label }}</slot>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Display label */
  label: { type: String, default: '' },
  /**
   * Visual variant:
   * success (green), error (red), warning (amber/yellow),
   * info (blue), neutral (gray), purple, cyan, orange, pink
   */
  variant: {
    type: String,
    default: 'neutral',
    validator: (v) =>
      ['success', 'error', 'warning', 'info', 'neutral', 'purple', 'cyan', 'orange', 'pink'].includes(v),
  },
  /** Optional leading icon text (e.g. '✓', '✗') */
  iconText: { type: String, default: '' },
  /** Size variant: 'sm' for inline micro badges, default for standard */
  size: {
    type: String,
    default: 'default',
    validator: (v) => ['sm', 'default'].includes(v),
  },
});

const variantMap = {
  success: 'bg-green-500/20 text-green-400',
  error:   'bg-red-500/20 text-red-400',
  warning: 'bg-amber-500/20 text-amber-400',
  info:    'bg-blue-500/20 text-blue-400',
  neutral: 'bg-gray-500/20 text-gray-400',
  purple:  'bg-purple-500/20 text-purple-400',
  cyan:    'bg-cyan-500/20 text-cyan-400',
  orange:  'bg-orange-500/20 text-orange-400',
  pink:    'bg-pink-500/20 text-pink-400',
};

const sizeMap = {
  sm:      'px-2 py-0.5 text-[9px]',
  default: 'px-3 py-1 text-xs',
};

const variantClass = computed(() => variantMap[props.variant] || variantMap.neutral);
const sizeClass = computed(() => sizeMap[props.size] || sizeMap.default);
</script>
