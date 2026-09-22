<template>
  <div class="section-card">
    <h4 v-if="title" class="mb-3 flex items-center gap-2 text-sm font-bold" :style="{ color: titleColor }">
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
});

const titleColor = computed(() => {
  const colors = {
    blue: 'var(--admin-accent-blue)', green: 'var(--admin-accent-green)',
    orange: 'var(--admin-accent-orange)', purple: 'var(--admin-accent-purple)',
    red: 'var(--admin-accent-red)', cyan: 'var(--admin-accent-cyan)',
    pink: 'var(--admin-accent-pink)', indigo: 'var(--admin-accent-indigo)',
    gray: 'var(--admin-text-muted)', emerald: 'var(--admin-accent-emerald)',
    amber: 'var(--admin-accent-amber)',
  };
  return colors[props.color] || colors.blue;
});
</script>

<style scoped>
@reference "../../../../css/app.css";

.section-card {
  margin-bottom: 1rem;
  padding: 1rem;
  border: 1px solid var(--admin-card-border);
  border-radius: 1rem;
  background: linear-gradient(180deg, var(--admin-surface-2) 0%, rgba(148, 163, 184, 0.04) 100%);
  color: var(--admin-text);
  box-shadow: var(--admin-card-shadow-soft);
  transition: background-color var(--admin-transition), border-color var(--admin-transition), box-shadow var(--admin-transition);
}
</style>
