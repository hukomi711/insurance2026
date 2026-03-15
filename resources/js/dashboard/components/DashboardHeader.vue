<script setup>
defineProps( {
    systemStatus: { type: String, required: true },
    wsEnabled: { type: Boolean, default: false },
    autoRefresh: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
} );

defineEmits( [ 'toggle-auto-refresh', 'manual-refresh', 'clear-cache' ] );
</script>

<template>
    <header class="rounded-2xl mb-6 transition-colors duration-200"
        :style="{
            backgroundColor: 'var(--admin-card-bg)',
            borderWidth: '1px',
            borderColor: 'var(--admin-card-border)',
            boxShadow: 'var(--admin-card-shadow)',
        }">
        <div class="flex items-center justify-between px-5 py-4">
            <!-- Title + Status -->
            <div class="flex items-center gap-3">
                <h1 class="text-lg font-bold font-heading flex items-center gap-2"
                    :style="{ color: 'var(--admin-text)' }">
                    <i class="fa-solid fa-chart-line text-[var(--color-primary)]" aria-hidden="true"></i>
                    لوحة التحكم
                </h1>
                <div class="rounded-full px-2.5 py-1 text-[11px] font-semibold flex items-center gap-1.5"
                    :class="systemStatus === 'healthy' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-amber-500/10 text-amber-400'">
                    <i :class="systemStatus === 'healthy' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'" class="text-[9px]" aria-hidden="true"></i>
                    <span v-if="systemStatus === 'healthy'">سليم</span>
                    <span v-else>تنبيه</span>
                </div>
                <div v-if="wsEnabled" class="rounded-full bg-blue-500/10 px-2.5 py-1 text-[11px] font-semibold text-blue-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-satellite-dish text-[9px]" aria-hidden="true"></i> فوري
                </div>
            </div>

            <!-- Controls -->
            <div class="flex items-center gap-2">
                <button :disabled="loading"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                    style="background-color: var(--color-primary);"
                    @click="$emit('manual-refresh')">
                    <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{ 'fa-spin': loading }" aria-hidden="true"></i> تحديث
                </button>
                <button
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                    :class="autoRefresh ? 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25' : 'hover:opacity-80'"
                    :style="autoRefresh ? {} : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
                    @click="$emit('toggle-auto-refresh')">
                    <i :class="autoRefresh ? 'fa-solid fa-pause' : 'fa-solid fa-play'" class="text-[11px]" aria-hidden="true"></i>
                    تلقائي
                </button>
            </div>
        </div>
    </header>
</template>
