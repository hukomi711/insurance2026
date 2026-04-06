<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps( {
    systemStatus: { type: String, required: true },
    wsEnabled: { type: Boolean, default: false },
    autoRefresh: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
} );

defineEmits( [ 'toggle-auto-refresh', 'manual-refresh', 'clear-cache' ] );

// ── Live clock: ticks every second so "آخر تحديث" stays accurate ──
const now = ref( Date.now() );
let _clockTimer = null;
onMounted( () => { _clockTimer = setInterval( () => { now.value = Date.now(); }, 1000 ); } );
onUnmounted( () => { clearInterval( _clockTimer ); } );

const lastRefreshed = ref( Date.now() );
const markRefreshed = () => { lastRefreshed.value = Date.now(); };
defineExpose( { markRefreshed } );

const lastUpdatedLabel = computed( () => {
    const seconds = Math.floor( ( now.value - lastRefreshed.value ) / 1000 );
    if ( seconds < 5 ) return 'الآن';
    if ( seconds < 60 ) return `قبل ${ seconds } ثانية`;
    const m = Math.floor( seconds / 60 );
    return `قبل ${ m } دقيقة`;
} );

const statusConfig = computed( () => {
    if ( props.systemStatus === 'healthy' ) {
        return { label: 'Stable', icon: 'fa-solid fa-circle-check', cls: 'bg-emerald-500/10 text-emerald-400' };
    }
    return { label: 'تنبيه', icon: 'fa-solid fa-triangle-exclamation', cls: 'bg-amber-500/10 text-amber-400' };
} );
</script>

<template>
    <header class="rounded-2xl mb-6 transition-colors duration-200"
        :style="{
            backgroundColor: 'var(--admin-card-bg)',
            borderWidth: '1px',
            borderColor: 'var(--admin-card-border)',
            boxShadow: 'var(--admin-card-shadow)',
        }">
        <div class="flex items-center justify-between px-5 py-4 flex-wrap gap-y-3">

            <!-- LEFT — Title + badges -->
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-lg font-bold font-heading flex items-center gap-2"
                    :style="{ color: 'var(--admin-text)' }">
                    <i class="fa-solid fa-chart-line text-[var(--color-primary)]" aria-hidden="true"></i>
                    لوحة التحكم
                </h1>

                <!-- Live indicator (WebSocket connected) -->
                <div v-if="wsEnabled"
                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold flex items-center gap-1.5 bg-blue-500/10 text-blue-400">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-60"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-400"></span>
                    </span>
                    Live
                </div>

                <!-- System Status -->
                <div class="rounded-full px-2.5 py-1 text-[11px] font-semibold flex items-center gap-1.5"
                    :class="statusConfig.cls">
                    <i :class="statusConfig.icon" class="text-[9px]" aria-hidden="true"></i>
                    {{ statusConfig.label }}
                </div>

                <!-- Last update timestamp -->
                <span class="text-[11px]" :style="{ color: 'var(--admin-text-dim)' }">
                    آخر تحديث: {{ lastUpdatedLabel }}
                </span>
            </div>

            <!-- RIGHT — Controls -->
            <div class="flex items-center gap-2">
                <!-- Auto Sync toggle -->
                <button
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors cursor-pointer flex items-center gap-1.5"
                    :class="autoRefresh ? 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25' : 'hover:opacity-80'"
                    :style="autoRefresh ? {} : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
                    @click="$emit('toggle-auto-refresh')">
                    <span class="relative flex h-2 w-2">
                        <span v-if="autoRefresh" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-50"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                            :class="autoRefresh ? 'bg-emerald-400' : 'bg-current opacity-40'"></span>
                    </span>
                    {{ autoRefresh ? 'تلقائي' : 'متوقف' }}
                </button>

                <!-- Manual Refresh -->
                <button :disabled="loading"
                    class="rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-50 cursor-pointer flex items-center gap-1.5"
                    style="background-color: var(--color-primary);"
                    @click="$emit('manual-refresh')">
                    <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{ 'fa-spin': loading }" aria-hidden="true"></i>
                    تحديث
                </button>
            </div>

        </div>
    </header>
</template>
