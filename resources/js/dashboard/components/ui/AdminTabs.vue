<template>
  <div class="min-w-0">
    <!-- Tab bar -->
    <div
      class="admin-tabs-bar flex min-w-full max-w-full gap-1 overflow-x-auto whitespace-nowrap overscroll-x-contain border-b px-2 sm:px-4"
      :style="{ backgroundColor: 'var(--admin-surface-2)', borderColor: 'var(--admin-card-border)' }"
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
        :class="tab.id === model ? 'admin-tab--active' : 'admin-tab--idle'"
        :style="tabStyle(tab.id)"
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
/** Two-way v-model via defineModel */
const model = defineModel( { type: String, required: true } );

defineProps({
  /** Array of { id, label, icon? } */
  tabs: { type: Array, required: true },
  /** Direction */
  dir: { type: String, default: 'rtl' },
  /** Accessible label for the tab list */
  ariaLabel: { type: String, default: 'Tabs' },
  /** Visual style: underline tabs or bordered */
  style: { type: String, default: 'bordered', validator: (v) => ['bordered', 'pills'].includes(v) },
});

const tabStyle = (id) => {
  const isActive = id === model.value;
  return {
    backgroundColor: isActive ? 'var(--admin-surface)' : 'transparent',
    color: isActive ? 'var(--admin-text)' : 'var(--admin-text-dim)',
    borderColor: 'var(--admin-card-border)',
  };
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

.admin-tab--active {
  margin-bottom: -1px;
  border-width: 2px 1px 1px;
  border-style: solid;
  border-top-color: var(--admin-accent-blue) !important;
  border-radius: 0.5rem 0.5rem 0 0;
}

.admin-tab--idle:hover {
  background: var(--admin-hover-accent) !important;
  color: var(--admin-text) !important;
}
</style>
