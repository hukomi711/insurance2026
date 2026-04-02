<template>
  <div class="flex items-center justify-center gap-1.5 sm:gap-2 py-3" dir="rtl">
    <template v-for="(step, i) in steps" :key="i">
      <div class="flex items-center gap-1.5">
        <span
          class="w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
          :class="i < current ? 'bg-emerald-500 text-white' : i === current ? 'bg-primary text-white' : 'bg-slate-200 text-slate-400'"
        >
          <svg v-if="i < current" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
          </svg>
          <span v-else>{{ i + 1 }}</span>
        </span>
        <span class="text-[11px] sm:text-xs font-medium hidden sm:inline"
          :class="i <= current ? 'text-foreground' : 'text-slate-400'">{{ step }}</span>
      </div>
      <div v-if="i < steps.length - 1"
        class="flex-1 max-w-8 sm:max-w-12 h-0.5 rounded-full transition-colors"
        :class="i < current ? 'bg-emerald-500' : 'bg-slate-200'" />
    </template>
  </div>
</template>

<script setup>
defineProps({
  steps: { type: Array, default: () => ['البيانات', 'المقارنة', 'الدفع', 'التأكيد'] },
  current: { type: Number, default: 2 },
});
</script>
