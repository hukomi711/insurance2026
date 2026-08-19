<template>
    <div class="space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">تتبع العروض</h1>
                <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-dim)' }">مراقبة مباشرة لجلسات العروض والتحليلات</p>
            </div>
            <div class="flex items-center gap-3">
                <button class="flex items-center gap-2 px-4 py-2 text-sm rounded-xl transition-colors"
                    :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-secondary)' }"
                    @click="refreshData">
                    <i class="fa-solid fa-arrows-rotate w-4 h-4" aria-hidden="true" :class="{ 'fa-spin': loading }"></i>
                    تحديث
                </button>
                <!-- Auto-refresh toggle -->
                <button class="flex items-center gap-2 px-4 py-2 text-sm rounded-xl transition-colors"
                    :class="autoRefresh ? 'bg-green-600 hover:bg-green-500 text-white' : ''"
                    :style="autoRefresh ? {} : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
                    @click="toggleAutoRefresh">
                    <span class="w-2 h-2 rounded-full"
                        :class="autoRefresh ? 'bg-green-300 animate-pulse' : ''"
                        :style="autoRefresh ? {} : { backgroundColor: 'var(--admin-text-dim)' }"></span>
                    {{ autoRefresh ? 'مباشر' : 'متوقف' }}
                </button>
            </div>
        </div>

        <!-- Overview Stats Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div v-for="stat in overviewStats" :key="stat.label"
                class="rounded-2xl p-4 transition-colors duration-200"
                :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                <p class="text-xs mb-1" :style="{ color: 'var(--admin-text-muted)' }">{{ stat.label }}</p>
                <p class="text-2xl font-bold font-heading ltr-nums" :class="stat.color">{{ stat.value }}</p>
                <p v-if="stat.sub" class="text-xs mt-1" :style="{ color: 'var(--admin-text-dim)' }">{{ stat.sub }}</p>
            </div>
        </div>

        <!-- Tabs: Live / All / Analytics -->
        <div class="flex gap-1 rounded-xl p-1 w-fit" :style="{ backgroundColor: 'var(--admin-surface-2)' }">
            <button v-for="tab in tabs" :key="tab.id" class="px-4 py-2 text-sm rounded-lg transition-colors"
                :class="activeTab === tab.id ? 'bg-(--color-primary) text-white' : ''"
                :style="activeTab !== tab.id ? { color: 'var(--admin-text-muted)' } : {}"
                @click="activeTab = tab.id">
                {{ tab.label }}
                <span v-if="tab.badge"
                    class="inline-flex items-center justify-center w-5 h-5 ms-1 text-xs font-bold rounded-full"
                    :class="activeTab === tab.id ? 'bg-blue-400 text-blue-900' : ''"
                    :style="activeTab !== tab.id ? { backgroundColor: 'var(--admin-surface-3)', color: 'var(--admin-text-muted)' } : {}">
                    {{ tab.badge }}
                </span>
            </button>
        </div>

        <!-- Tab: Live Sessions -->
        <div v-if="activeTab === 'live'" class="space-y-4">
            <div v-if="liveSessions.length === 0"
                class="rounded-2xl p-12 text-center"
                :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                <p :style="{ color: 'var(--admin-text-muted)' }">لا توجد جلسات نشطة حالياً</p>
            </div>
            <div v-else class="grid gap-4">
                <div v-for="session in liveSessions" :key="session.uuid"
                    class="rounded-2xl p-5 transition-colors duration-200"
                    :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <!-- Session Info -->
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center text-lg"
                                    :class="getStepBgColor(session.current_step)">
                                    <i :class="getStepIcon(session.current_step)"></i>
                                </div>
                                <span
                                    class="absolute -top-1 -inset-e-1 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-800 animate-pulse"></span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="font-medium text-sm ltr-nums" :style="{ color: 'var(--admin-text)' }">{{ session.customer_ip }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                        :class="getInsuranceTypeBadge(session.insurance_type)">
                                        {{ getInsuranceTypeLabel(session.insurance_type) }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ session.device_type }} · {{ session.device_browser }}
                                </p>
                            </div>
                        </div>

                        <!-- Step + Progress -->
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">الخطوة الحالية</p>
                                <p class="text-sm font-medium" :style="{ color: 'var(--admin-text)' }">{{ getStepLabel(session.current_step) }}</p>
                            </div>
                            <div class="text-center">
                                <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">الاكتمال</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-20 h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            :class="getProgressColor(session.completion_percentage)"
                                            :style="{ width: session.completion_percentage + '%' }"></div>
                                    </div>
                                    <span
                                        class="text-xs text-gray-300 ltr-nums">{{ session.completion_percentage }}%</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-xs text-gray-400">المدة</p>
                                <p class="text-sm text-white ltr-nums">{{ session.formatted_duration }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Step Timeline -->
                    <div class="flex items-center gap-1 mt-4 pt-4 border-t border-gray-700">
                        <div v-for="(log, idx) in session.step_logs" :key="idx" class="flex items-center gap-1">
                            <div class="flex items-center gap-1.5 px-2 py-1 rounded-lg text-xs"
                                :class="log.exited_at ? 'bg-gray-700 text-gray-300' : 'bg-blue-900/50 text-blue-300 border border-blue-700'">
                                <span>{{ getStepLabel(log.step_name) }}</span>
                                <span v-if="log.duration_seconds"
                                    class="text-gray-500 ltr-nums">{{ log.duration_seconds }}ث</span>
                            </div>
                            <i v-if="idx < session.step_logs.length - 1" class="fa-solid fa-chevron-right w-3 h-3 text-gray-600 rotate-180"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab: All Sessions -->
        <div v-if="activeTab === 'all'" class="space-y-4">
            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <select id="quote-status-filter" v-model="filters.status" name="quote-status-filter"
                    aria-label="تصفية حسب الحالة"
                    class="text-sm rounded-xl px-3 py-2 focus:border-blue-500 focus:outline-none transition-colors"
                    :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }">
                    <option value="">كل الحالات</option>
                    <option value="active">نشط</option>
                    <option value="completed">مكتمل</option>
                    <option value="abandoned">متروك</option>
                </select>
                <select id="quote-step-filter" v-model="filters.step" name="quote-step-filter"
                    aria-label="تصفية حسب الخطوة"
                    class="text-sm rounded-xl px-3 py-2 focus:border-blue-500 focus:outline-none transition-colors"
                    :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }">
                    <option value="">كل الخطوات</option>
                    <option value="motorapp">نوع التأمين</option>
                    <option value="vehicle">بيانات المركبة</option>
                    <option value="compare">المقارنة</option>
                    <option value="details">التفاصيل</option>
                </select>
            </div>

            <!-- Sessions Table -->
            <div class="rounded-2xl overflow-hidden" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-right text-gray-400 font-medium px-5 py-3">IP</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">النوع</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">الخطوة</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">الاكتمال</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">الحالة</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">الجهاز</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">المدة</th>
                                <th class="text-right text-gray-400 font-medium px-5 py-3">البدء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="session in filteredSessions" :key="session.uuid"
                                class="border-b border-gray-700/50 hover:bg-gray-750 transition-colors">
                                <td class="px-5 py-3 text-white ltr-nums">{{ session.customer_ip }}</td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                        :class="getInsuranceTypeBadge(session.insurance_type)">
                                        {{ getInsuranceTypeLabel(session.insurance_type) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-300">{{ getStepLabel(session.current_step) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 h-1.5 bg-gray-700 rounded-full overflow-hidden">
                                            <div class="h-full rounded-full"
                                                :class="getProgressColor(session.completion_percentage)"
                                                :style="{ width: session.completion_percentage + '%' }"></div>
                                        </div>
                                        <span
                                            class="text-xs text-gray-400 ltr-nums">{{ session.completion_percentage }}%</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                        :class="getStatusBadge(session.status)">
                                        {{ getStatusLabel(session.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-400 text-xs">{{ session.device_type }}</td>
                                <td class="px-5 py-3 text-gray-300 ltr-nums text-xs">{{ session.formatted_duration }}
                                </td>
                                <td class="px-5 py-3 text-gray-400 text-xs ltr-nums">
                                    {{ formatTime(session.started_at) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Tab: Analytics -->
        <div v-if="activeTab === 'analytics'" class="space-y-6">
            <!-- Step Funnel -->
            <div class="rounded-2xl p-6" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                <h3 class="font-bold mb-4 font-heading" :style="{ color: 'var(--admin-text)' }">قمع التحويل</h3>
                <div class="space-y-3">
                    <div v-for="(count, step) in analytics.step_funnel" :key="step" class="flex items-center gap-4">
                        <span class="text-sm text-gray-400 w-28 text-left">{{ getStepLabel(step) }}</span>
                        <div class="flex-1 h-8 bg-gray-700 rounded-lg overflow-hidden relative">
                            <div class="h-full rounded-lg transition-all duration-700 flex items-center pe-3"
                                :class="getFunnelColor(step)" :style="{ width: getFunnelWidth(count) + '%' }">
                                <span
                                    class="text-xs font-bold text-white ltr-nums ms-auto">{{ count.toLocaleString('ar-SA-u-nu-latn') }}</span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 w-12 text-left ltr-nums">
                            {{ getFunnelPercentage(count) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Abandonment by Step -->
            <div class="grid lg:grid-cols-2 gap-6">
                <div class="rounded-2xl p-6" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                    <h3 class="font-bold mb-4 font-heading" :style="{ color: 'var(--admin-text)' }">التخلي حسب الخطوة</h3>
                    <div class="space-y-3">
                        <div v-for="(count, step) in analytics.abandonment_by_step" :key="step"
                            class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">{{ getStepLabel(step) }}</span>
                            <div class="flex items-center gap-3">
                                <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-red-500 rounded-full"
                                        :style="{ width: (count / maxAbandonment * 100) + '%' }"></div>
                                </div>
                                <span class="text-sm text-red-400 ltr-nums w-8 text-left">{{ count }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl p-6" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                    <h3 class="font-bold mb-4 font-heading" :style="{ color: 'var(--admin-text)' }">الأجهزة</h3>
                    <div class="space-y-3">
                        <div v-for="(count, device) in analytics.device_breakdown" :key="device"
                            class="flex items-center justify-between">
                            <span class="text-sm text-gray-400">{{ getDeviceLabel(device) }}</span>
                            <div class="flex items-center gap-3">
                                <div class="w-32 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full bg-blue-500 rounded-full"
                                        :style="{ width: (count / maxDeviceCount * 100) + '%' }"></div>
                                </div>
                                <span
                                    class="text-sm text-blue-400 ltr-nums w-12 text-left">{{ count.toLocaleString('ar-SA-u-nu-latn') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Trend -->
            <div class="rounded-2xl p-6" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                <h3 class="font-bold mb-4 font-heading" :style="{ color: 'var(--admin-text)' }">الاتجاه اليومي (آخر 7 أيام)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-right text-gray-400 font-medium px-4 py-2">التاريخ</th>
                                <th class="text-right text-gray-400 font-medium px-4 py-2">الإجمالي</th>
                                <th class="text-right text-gray-400 font-medium px-4 py-2">مكتمل</th>
                                <th class="text-right text-gray-400 font-medium px-4 py-2">متروك</th>
                                <th class="text-right text-gray-400 font-medium px-4 py-2">معدل الإكمال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="day in analytics.daily_trend" :key="day.date"
                                class="border-b border-gray-700/50">
                                <td class="px-4 py-2 text-gray-300 ltr-nums">{{ day.date }}</td>
                                <td class="px-4 py-2 text-white ltr-nums">{{ day.total }}</td>
                                <td class="px-4 py-2 text-green-400 ltr-nums">{{ day.completed }}</td>
                                <td class="px-4 py-2 text-red-400 ltr-nums">{{ day.abandoned }}</td>
                                <td class="px-4 py-2 ltr-nums">
                                    <span class="text-xs px-2 py-0.5 rounded-full"
                                        :class="day.total > 0 && (day.completed / day.total * 100) > 25 ? 'bg-green-900/50 text-green-400' : 'bg-yellow-900/50 text-yellow-400'">
                                        {{ day.total > 0 ? Math.round(day.completed / day.total * 100) : 0 }}%
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';

defineOptions({ name: 'QuoteMonitorPage' });
import { getQuoteLiveSessions, getQuoteSessions, getQuoteAnalytics } from '@/api/dashboard';
import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
import logger from '@/utils/logger';

//
const loading = ref( false );
const activeTab = ref( 'live' );
const autoRefresh = ref( true );

const liveSessions = ref( [] );
const allSessions = ref( [] );
const analytics = ref( {
    overview: {},
    abandonment_by_step: {},
    daily_trend: [],
    device_breakdown: {},
    step_funnel: {},
} );

const filters = reactive( {
    status: '',
    step: '',
} );

//
const tabs = computed( () => [
    { id: 'live', label: 'مباشر', badge: liveSessions.value.length || null },
    { id: 'all', label: 'كل الجلسات', badge: null },
    { id: 'analytics', label: 'التحليلات', badge: null },
] );

//
const overviewStats = computed( () => {
    const ov = analytics.value.overview || {};
    return [
        { label: 'نشط الآن', value: ov.active_now || liveSessions.value.length, color: 'text-green-500', sub: 'جلسة مباشرة' },
        { label: 'إجمالي الجلسات', value: ( ov.total || 0 ).toLocaleString( 'ar-SA-u-nu-latn' ), color: '' },
        { label: 'مكتمل', value: ( ov.completed || 0 ).toLocaleString( 'ar-SA-u-nu-latn' ), color: 'text-green-500' },
        { label: 'متروك', value: ( ov.abandoned || 0 ).toLocaleString( 'ar-SA-u-nu-latn' ), color: 'text-red-500' },
        { label: 'معدل الإكمال', value: ( ov.completion_rate || 0 ) + '%', color: 'text-blue-500', sub: 'متوسط ' + Math.round( ( ov.avg_duration_seconds || 0 ) / 60 ) + ' دقيقة' },
    ];
} );

//
const filteredSessions = computed( () => {
    let sessions = [ ...allSessions.value ];
    if ( filters.status ) sessions = sessions.filter( s => s.status === filters.status );
    if ( filters.step ) sessions = sessions.filter( s => s.current_step === filters.step );
    return sessions;
} );

//
const maxAbandonment = computed( () => {
    const vals = Object.values( analytics.value.abandonment_by_step || {} );
    return Math.max( ...vals, 1 );
} );

const maxDeviceCount = computed( () => {
    const vals = Object.values( analytics.value.device_breakdown || {} );
    return Math.max( ...vals, 1 );
} );

const funnelMax = computed( () => {
    const vals = Object.values( analytics.value.step_funnel || {} );
    return Math.max( ...vals, 1 );
} );

function getFunnelWidth( count ) {
    return Math.round( ( count / funnelMax.value ) * 100 );
}

function getFunnelPercentage( count ) {
    return Math.round( ( count / funnelMax.value ) * 100 );
}

function getFunnelColor( step ) {
    const map = {
        motorapp: 'bg-blue-600',
        vehicle: 'bg-blue-500',
        compare: 'bg-indigo-500',
        details: 'bg-purple-500',
        completed: 'bg-green-500',
    };
    return map[ step ] || 'bg-gray-600';
}

//
function getStepLabel( step ) {
    const map = {
        motorapp: 'نوع التأمين',
        vehicle: 'بيانات المركبة',
        compare: 'المقارنة',
        details: 'التفاصيل',
        completed: 'مكتمل',
    };
    return map[ step ] || step;
}

function getStepIcon( step ) {
    const map = { motorapp: 'fa-solid fa-car', vehicle: 'fa-solid fa-clipboard-list', compare: 'fa-solid fa-chart-bar', details: 'fa-solid fa-file-lines', completed: 'fa-solid fa-circle-check' };
    return map[ step ] || 'fa-solid fa-location-pin';
}

function getStepBgColor( step ) {
    const map = {
        motorapp: 'bg-blue-900/50 text-blue-300',
        vehicle: 'bg-amber-900/50 text-amber-300',
        compare: 'bg-indigo-900/50 text-indigo-300',
        details: 'bg-purple-900/50 text-purple-300',
    };
    return map[ step ] || 'bg-gray-700 text-gray-300';
}

function getInsuranceTypeLabel( type ) {
    const map = { renew: 'تجديد', buy: 'شراء', import: 'مستوردة' };
    return map[ type ] || type || '—';
}

function getInsuranceTypeBadge( type ) {
    const map = {
        renew: 'bg-blue-900/50 text-blue-300',
        buy: 'bg-amber-900/50 text-amber-300',
        import: 'bg-green-900/50 text-green-300',
    };
    return map[ type ] || 'bg-gray-700 text-gray-400';
}

function getStatusLabel( status ) {
    const map = { active: 'نشط', completed: 'مكتمل', abandoned: 'متروك', expired: 'منتهي' };
    return map[ status ] || status;
}

function getStatusBadge( status ) {
    const map = {
        active: 'bg-green-900/50 text-green-400',
        completed: 'bg-blue-900/50 text-blue-400',
        abandoned: 'bg-red-900/50 text-red-400',
        expired: 'bg-gray-700 text-gray-400',
    };
    return map[ status ] || 'bg-gray-700 text-gray-400';
}

function getProgressColor( pct ) {
    if ( pct >= 80 ) return 'bg-green-500';
    if ( pct >= 50 ) return 'bg-blue-500';
    if ( pct >= 25 ) return 'bg-amber-500';
    return 'bg-red-500';
}

function getDeviceLabel( device ) {
    const map = { desktop: 'سطح المكتب', mobile: 'جوال', tablet: 'لوحي' };
    return map[ device ] || device;
}

function formatTime( iso ) {
    if ( !iso ) return '—';
    const d = new Date( iso );
    return d.toLocaleTimeString( 'ar-SA-u-nu-latn', { hour: '2-digit', minute: '2-digit' } );
}

//
async function refreshData() {
    loading.value = true;
    try {
        const [ liveRes, allRes, analyticsRes ] = await Promise.all( [
            getQuoteLiveSessions(),
            getQuoteSessions(),
            getQuoteAnalytics(),
        ] );
        liveSessions.value = liveRes.data?.sessions || liveRes.sessions || [];
        allSessions.value = allRes.data?.data || allRes.data || [];
        analytics.value = analyticsRes.data || analyticsRes;
    } catch ( err ) {
        logger.warn( '[QuoteMonitor] Failed to refresh:', err.message );
    } finally {
        loading.value = false;
    }
}

function toggleAutoRefresh() {
    autoRefresh.value = !autoRefresh.value;
    if ( autoRefresh.value ) {
        registerPollingCallback( 'quoteMonitor', refreshData );
    } else {
        unregisterPollingCallback( 'quoteMonitor' );
    }
}

//
onMounted( () => {
    refreshData();
    registerPollingCallback( 'quoteMonitor', refreshData );
} );

onUnmounted( () => {
    unregisterPollingCallback( 'quoteMonitor' );
} );

// ── KeepAlive lifecycle: pause/resume polling when cached ──
onActivated( () => {
    refreshData();
    registerPollingCallback( 'quoteMonitor', refreshData );
} );

onDeactivated( () => {
    unregisterPollingCallback( 'quoteMonitor' );
} );
</script>
