<template>
  <div class="stat-card group">
    <div class="stat-card__glow" :style="{ background: accentColor || 'var(--admin-accent-blue)' }" />
    <div class="flex items-start justify-between relative">
      <div class="flex-1 min-w-0">
        <p class="text-[11px] font-medium mb-1.5 uppercase tracking-wider truncate text-gray-400">
          {{ label }}
        </p>
        <p class="text-2xl font-bold font-heading ltr-nums" :style="{ color: accentColor || 'var(--admin-text)' }">
          <slot name="value">{{ displayValue }}</slot>
        </p>
        <p v-if="sub" class="text-[11px] mt-1.5 text-gray-500">{{ sub }}</p>
      </div>
      <div
        v-if="icon"
        class="stat-card__icon"
        :style="{ backgroundColor: iconBg, color: accentColor || 'var(--admin-accent-blue)' }"
      >
        <i :class="icon" class="text-base" />
      </div>
    </div>
    <slot name="footer" />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  /** Card label / title */
  label: { type: String, required: true },
  /** Numeric or text value */
  value: { type: [String, Number, null], default: null },
  /** Smaller text below value */
  sub: { type: String, default: '' },
  /** FontAwesome icon class (e.g. 'fa-solid fa-users') */
  icon: { type: String, default: '' },
  /** Accent color for value and icon (CSS color value) */
  accentColor: { type: String, default: '' },
});

const displayValue = computed(() => {
  if (props.value === null || props.value === undefined) return '—';
  return props.value;
});

const iconBg = computed(() => {
  if (!props.accentColor) return 'var(--admin-status-info-bg)';
  // Create a semi-transparent version of the accent color
  return props.accentColor + '1a'; // hex alpha ~10%
});
</script>

<style scoped>
@reference "../../../../css/app.css";

.stat-card {
  @apply relative overflow-hidden rounded-2xl p-5 transition-all duration-300;
  background: rgba(17, 24, 39, 0.45);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.06);
}
.stat-card:hover {
  border-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.stat-card__glow {
  @apply pointer-events-none absolute -top-12 -inset-e-12 h-24 w-24 rounded-full opacity-10 blur-2xl transition-opacity duration-300;
}
.stat-card:hover .stat-card__glow { opacity: 0.2; }

.stat-card__icon {
  @apply ms-3 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl transition-transform duration-300;
}
.stat-card:hover .stat-card__icon { transform: scale(1.1); }
</style>
