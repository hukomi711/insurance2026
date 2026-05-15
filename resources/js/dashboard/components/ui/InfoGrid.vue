<template>
  <div class="grid gap-3" :class="colsClass">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Number of grid columns (2, 3, or 4) */
  cols: { type: Number, default: 2, validator: (v) => [2, 3, 4].includes(v) },
});

const colsClass = computed(() => {
  const map = {
    2: 'grid-cols-1 sm:grid-cols-2',
    3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
  };

  return map[props.cols] || map[2];
});
</script>
