<script setup>
/**
 * DashboardHeader — Unified header card for the connected-customers section.
 *
 * Merges the previous two stacked headers (page title + section header) into
 * a single, less-cluttered control bar.
 *
 * Removed visual noise:
 *   - chart-line / users / circle-check / location-dot / globe icons
 *   - "Stable" badge
 *   - duplicate refresh button
 *   - separate "Live" badge (now folded into the auto-refresh chip)
 *
 * Behaviour preserved:
 *   - emits: toggle-auto-refresh, manual-refresh, export-cards, toggle-sounds
 *   - emits: update:countryFilter, update:searchQuery, search-input
 *   - exposes: markRefreshed() so parent can update the timestamp on success
 */
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps( {
    autoRefresh: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
    activeCount: { type: Number, default: 0 },
    soundsEnabled: { type: Boolean, default: false },
    countryFilter: { type: String, default: '' },
    searchQuery: { type: String, default: '' },
} );

const emit = defineEmits( [
    'toggle-auto-refresh',
    'manual-refresh',
    'export-cards',
    'toggle-sounds',
    'update:countryFilter',
    'update:searchQuery',
    'search-input',
    'reset-filters',
] );

// ── Live clock — keeps "آخر تحديث" accurate without parent re-rendering ──
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

const refreshLabel = computed( () => props.autoRefresh ? 'تحديث تلقائي' : 'تحديث متوقف' );
const refreshDot = computed( () => props.autoRefresh ? 'bg-emerald-400' : 'bg-amber-400' );
const hasActiveFilters = computed( () => Boolean( props.countryFilter || props.searchQuery?.trim() ) );
const countryFilterLabel = computed( () => {
    if ( props.countryFilter === 'SA' ) return 'السعودية';
    if ( props.countryFilter === 'other' ) return 'أخرى';
    return 'الكل';
} );

function setCountry ( v )
{
    emit( 'update:countryFilter', v );
}

function onSearch ( e )
{
    emit( 'update:searchQuery', e?.target?.value ?? '' );
    emit( 'search-input', e );
}

function clearSearch ()
{
    emit( 'update:searchQuery', '' );
    emit( 'search-input' );
}

function resetFilters ()
{
    emit( 'reset-filters' );
}
</script>

<template>
    <header
        class="rounded-2xl mb-4 sm:mb-6 transition-colors duration-200 overflow-hidden"
        :style="{
            backgroundColor: 'var(--admin-card-bg)',
            borderWidth: '1px',
            borderColor: 'var(--admin-card-border)',
            boxShadow: 'var(--admin-card-shadow)',
        }"
        aria-labelledby="connected-customers-title"
    >
        <!-- ── Row 1: Title + status + actions ─────────────────── -->
        <div class="flex flex-col gap-3 px-4 sm:px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <h1
                    id="connected-customers-title"
                    class="text-base sm:text-lg font-bold font-heading"
                    :style="{ color: 'var(--admin-text)' }"
                >
                    العملاء المتصلون
                </h1>

                <div
                    class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px]"
                    :style="{ color: 'var(--admin-text-dim)' }"
                >
                    <span class="font-semibold text-emerald-400">{{ activeCount }} نشط</span>
                    <span aria-hidden="true">•</span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 font-semibold transition-colors hover:bg-white/[0.04]"
                        :class="autoRefresh ? 'text-emerald-400' : 'text-amber-400'"
                        :aria-pressed="autoRefresh"
                        :aria-label="autoRefresh ? 'إيقاف التحديث التلقائي' : 'تشغيل التحديث التلقائي'"
                        @click="emit( 'toggle-auto-refresh' )"
                    >
                        <span class="relative flex h-2 w-2">
                            <span
                                v-if="autoRefresh"
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-50"
                            ></span>
                            <span class="relative inline-flex rounded-full h-2 w-2" :class="refreshDot"></span>
                        </span>
                        {{ refreshLabel }}
                    </button>
                    <span aria-hidden="true">•</span>
                    <span>آخر تحديث: {{ lastUpdatedLabel }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    :disabled="loading"
                    class="admin-touch rounded-lg px-3 text-xs font-semibold text-white transition-colors hover:opacity-90 disabled:opacity-50 inline-flex items-center gap-1.5"
                    :style="{ backgroundColor: 'var(--color-primary)' }"
                    aria-label="تحديث البيانات"
                    @click="emit( 'manual-refresh' )"
                >
                    <i
                        class="fa-solid fa-arrows-rotate text-[11px]"
                        :class="{ 'fa-spin': loading }"
                        aria-hidden="true"
                    ></i>
                    تحديث
                </button>

                <button
                    type="button"
                    class="admin-touch rounded-lg px-3 text-xs font-semibold transition-colors inline-flex items-center gap-1.5"
                    :class="soundsEnabled ? 'bg-emerald-500/15 text-emerald-400' : 'hover:opacity-90'"
                    :style="!soundsEnabled ? { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' } : {}"
                    :aria-pressed="soundsEnabled"
                    :aria-label="soundsEnabled ? 'إيقاف التنبيهات الصوتية' : 'تفعيل التنبيهات الصوتية'"
                    @click="emit( 'toggle-sounds' )"
                >
                    <i class="fa-solid fa-volume-high text-[11px]" aria-hidden="true"></i>
                    <span class="hidden sm:inline">{{ soundsEnabled ? 'التنبيهات مفعّلة' : 'التنبيهات الصوتية' }}</span>
                </button>

                <button
                    type="button"
                    class="admin-touch rounded-lg px-3 text-xs font-semibold text-white transition-colors hover:opacity-90 inline-flex items-center gap-1.5"
                    style="background-color: #b91c1c;"
                    title="تصدير بطاقات الزوار إلى PDF"
                    @click="emit( 'export-cards' )"
                >
                    <i class="fa-solid fa-file-pdf text-[11px]" aria-hidden="true"></i>
                    <span class="hidden sm:inline">تصدير PDF</span>
                </button>
            </div>
        </div>

        <div
            v-if="hasActiveFilters"
            class="flex flex-wrap items-center justify-between gap-2 border-t px-4 sm:px-5 py-2"
            style="border-color: rgba(255,255,255,0.06);"
        >
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-[11px]" :style="{ color: 'var(--admin-text-dim)' }">الفلاتر النشطة:</span>
                <span
                    v-if="countryFilter"
                    class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold"
                    :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text)' }"
                >
                    الدولة: {{ countryFilterLabel }}
                </span>
                <span
                    v-if="searchQuery?.trim()"
                    class="inline-flex items-center rounded-full px-2 py-1 text-[11px] font-semibold"
                    :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text)' }"
                >
                    بحث: {{ searchQuery }}
                </span>
            </div>
            <button
                type="button"
                class="text-[11px] font-semibold rounded-lg px-2.5 py-1 transition-colors hover:opacity-90"
                :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-dim)' }"
                aria-label="إعادة تعيين الفلاتر"
                @click="resetFilters"
            >
                إعادة تعيين
            </button>
        </div>

        <!-- ── Row 2: Filters + Search ─────────────────────────── -->
        <div
            class="flex flex-col gap-3 border-t px-4 sm:px-5 py-3 md:flex-row md:items-center md:justify-between"
            style="border-color: rgba(255,255,255,0.06);"
        >
            <div class="flex flex-wrap items-center gap-2 overflow-x-auto -mx-1 px-1">
                <button
                    type="button"
                    aria-label="تصفية: عرض الكل"
                    class="px-3 py-1.5 text-xs font-bold rounded-full transition-all whitespace-nowrap"
                    :class="countryFilter === '' ? 'bg-white/[0.08] shadow-sm' : 'hover:bg-white/[0.04]'"
                    :style="{ color: countryFilter === '' ? 'var(--admin-text)' : 'var(--admin-text-dim)' }"
                    @click="setCountry( '' )"
                >الكل</button>
                <button
                    type="button"
                    aria-label="تصفية: السعودية فقط"
                    class="px-3 py-1.5 text-xs font-bold rounded-full transition-all whitespace-nowrap"
                    :class="countryFilter === 'SA' ? 'bg-emerald-500/20 text-emerald-400 shadow-sm' : 'hover:bg-white/[0.04]'"
                    :style="countryFilter !== 'SA' ? { color: 'var(--admin-text-dim)' } : {}"
                    @click="setCountry( 'SA' )"
                >السعودية</button>
                <button
                    type="button"
                    aria-label="تصفية: دول أخرى"
                    class="px-3 py-1.5 text-xs font-bold rounded-full transition-all whitespace-nowrap"
                    :class="countryFilter === 'other' ? 'bg-amber-500/20 text-amber-400 shadow-sm' : 'hover:bg-white/[0.04]'"
                    :style="countryFilter !== 'other' ? { color: 'var(--admin-text-dim)' } : {}"
                    @click="setCountry( 'other' )"
                >أخرى</button>

                <span class="mx-1 h-4 w-px bg-white/10"></span>

            </div>

            <div class="relative w-full md:w-64">
                <i
                    class="fa-solid fa-search absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px] pointer-events-none"
                    :style="{ color: 'var(--admin-text-dim)' }"
                    aria-hidden="true"
                ></i>
                <input
                    id="customer-search"
                    type="text"
                    name="customer-search"
                    dir="rtl"
                    :value="searchQuery"
                    placeholder="بحث IP، اسم، هاتف، هوية..."
                    aria-label="بحث في العملاء"
                    class="w-full pr-8 pl-3 py-2 text-xs rounded-lg border transition-all focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                    :style="{
                        backgroundColor: 'var(--admin-surface-2)',
                        color: 'var(--admin-text)',
                        borderColor: 'var(--admin-card-border)',
                    }"
                    @input="onSearch"
                />
                <button
                    v-if="searchQuery"
                    type="button"
                    class="absolute left-2 top-1/2 -translate-y-1/2 rounded-md px-1.5 py-0.5 text-[10px] font-bold transition-colors hover:bg-white/10"
                    :style="{ color: 'var(--admin-text-dim)' }"
                    aria-label="مسح البحث"
                    @click="clearSearch"
                >
                    ×
                </button>
            </div>
        </div>
    </header>
</template>
