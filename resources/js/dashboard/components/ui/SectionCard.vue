<template>
  <div :class="['section-card', borderClass]">
    <h4 v-if="title" :class="['mb-3 flex items-center gap-2 text-sm font-bold', titleColorClass]">
      <span v-if="emoji" class="text-base">{{ emoji }}</span>{{ title }}
      <span v-if="$slots['header-actions']" class="ms-auto"><slot name="header-actions" /></span>
    </h4>
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Section heading */
  title: { type: String, default: '' },
  /** Emoji prefix */
  emoji: { type: String, default: '' },
  /** Heading color: blue, green, orange, purple, red, cyan, pink, indigo, gray */
  color: { type: String, default: 'blue' },
  /** Theme */
  theme: { type: String, default: 'dark', validator: (v) => ['dark', 'light'].includes(v) },
});

const isDark = computed(() => props.theme === 'dark');

const borderClass = computed(() =>
  isDark.value ? 'section-card--dark' : 'section-card--light'
);

const colorMap = {
  blue:    'text-blue-400',
  green:   'text-green-400',
  orange:  'text-orange-400',
  purple:  'text-purple-400',
  red:     'text-red-400',
  cyan:    'text-cyan-400',
  pink:    'text-pink-400',
  indigo:  'text-indigo-400',
  gray:    'text-gray-400',
  emerald: 'text-emerald-400',
  amber:   'text-amber-400',
};

const titleColorClass = computed(() => {
  if (isDark.value) return colorMap[props.color] || colorMap.blue;
  // Light theme uses darker shades
  const lightMap = {
    blue: 'text-blue-700', green: 'text-green-700', orange: 'text-orange-700',
    purple: 'text-purple-700', red: 'text-red-700', cyan: 'text-cyan-700',
    pink: 'text-pink-700', indigo: 'text-indigo-700', gray: 'text-gray-700',
    emerald: 'text-emerald-700', amber: 'text-amber-700',
  };
  return lightMap[props.color] || lightMap.blue;
});
</script>

<style scoped>
@reference "../../../../css/app.css";

.section-card {
  @apply rounded-xl p-4 mb-4;
  transition: border-color 0.3s ease;
}
.section-card--dark {
  background: rgba(17, 24, 39, 0.4);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.06);
}
.section-card--dark:hover { border-color: rgba(255, 255, 255, 0.1); }
.section-card--light {
  @apply border border-gray-200 bg-gray-50;
}
</style>
