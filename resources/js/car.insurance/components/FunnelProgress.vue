<template>
    <div class="bg-white border-b border-slate-200" dir="rtl">
        <div class="box py-3">
            <div class="flex items-center justify-between gap-1">
                <template v-for="(step, i) in steps" :key="step.key">
                    <!-- Step -->
                    <div class="flex items-center gap-1.5 min-w-0"
                        :class="i === current ? 'text-primary' : i < current ? 'text-secondary' : 'text-slate-300'">
                        <!-- Circle -->
                        <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold transition-colors"
                            :class="i < current ? 'bg-secondary text-white' : i === current ? 'bg-primary text-white' : 'bg-slate-100 text-slate-400'">
                            <svg v-if="i < current" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <!-- Label (hidden on small screens for non-current steps) -->
                        <span class="text-xs font-bold whitespace-nowrap transition-colors"
                            :class="i === current ? 'inline text-primary' : i < current ? 'hidden sm:inline text-secondary' : 'hidden sm:inline text-slate-400'">
                            {{ step.label }}
                        </span>
                    </div>
                    <!-- Connector line -->
                    <div v-if="i < steps.length - 1"
                        class="flex-1 h-0.5 rounded-full mx-1 transition-colors"
                        :class="i < current ? 'bg-secondary' : 'bg-slate-200'" />
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
const steps = [
    { key: 'compare', label: 'العروض' },
    { key: 'checkout', label: 'الدفع' },
    { key: 'verify', label: 'التحقق' },
    { key: 'confirm', label: 'التأكيد' },
];

defineProps( {
    /** 0-based index of the current active step */
    current: { type: Number, required: true },
} );
</script>
