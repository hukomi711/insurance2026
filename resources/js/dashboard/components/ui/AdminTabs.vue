<template>
  <div class="min-w-0">
    <!-- Tab bar -->
    <div
      :class="['admin-tabs-bar flex min-w-full max-w-full gap-1 overflow-x-auto whitespace-nowrap overscroll-x-contain', barClass]"
      :dir="dir"
      role="tablist"
      :aria-label="ariaLabel"
    >
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        role="tab"
        :aria-selected="tab.id === model"
        class="flex min-h-11 shrink-0 touch-manipulation items-center gap-1.5 whitespace-nowrap px-3 py-2.5 text-xs font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-inset sm:px-4"
        :class="tabClass(tab.id)"
        @click="model = tab.id"
      >
        <span v-if="tab.icon">{{ tab.icon }}</span>
        <span>{{ tab.label }}</span>
      </button>
    </div>
    <!-- Active tab content -->
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';

/** Two-way v-model via defineModel */
const model = defineModel( { type: String, required: true } );

const props = defineProps({
  /** Array of { id, label, icon? } */
  tabs: { type: Array, required: true },
  /** Theme */
  theme: { type: String, default: 'dark', validator: (v) => ['dark', 'light'].includes(v) },
  /** Direction */
  dir: { type: String, default: 'rtl' },
  /** Accessible label for the tab list */
  ariaLabel: { type: String, default: 'Tabs' },
  /** Visual style: underline tabs or bordered */
  style: { type: String, default: 'bordered', validator: (v) => ['bordered', 'pills'].includes(v) },
});

const isDark = computed(() => props.theme === 'dark');

const barClass = computed(() => {
  if (isDark.value) return 'border-b border-gray-700 bg-gray-800/50 px-2 sm:px-4';
  return 'border-b border-gray-200 bg-white px-2 sm:px-6';
});

const tabClass = (id) => {
  const isActive = id === model.value;
  if (isDark.value) {
    return isActive
      ? 'rounded-t-lg bg-gray-900 text-white border-t-2 border-x border-t-blue-500 border-x-gray-700 -mb-px'
      : 'text-gray-400 hover:text-gray-200 hover:bg-gray-700/50';
  }
  return isActive
    ? 'rounded border border-blue-400 bg-blue-50 text-blue-700'
    : 'border border-gray-300 bg-white text-gray-600 hover:bg-gray-50';
};
</script>

<style scoped>
.admin-tabs-bar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.admin-tabs-bar::-webkit-scrollbar {
  display: none;
}
</style>
