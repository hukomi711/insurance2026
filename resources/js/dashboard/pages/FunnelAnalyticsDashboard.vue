<template>
    <div class="funnel-page" dir="rtl">

        <!-- ── Header ── -->
        <div class="funnel-header">
            <div>
                <h1 class="funnel-title">تحليل التحويل</h1>
                <p class="funnel-subtitle">قمع المبيعات · مسار العميل من العروض حتى التأكيد</p>
            </div>
            <button class="btn-refresh" :disabled="loading" @click="load">
                <i class="fa-solid fa-rotate-right" :class="{ 'fa-spin': loading }"></i>
                تحديث
            </button>
        </div>

        <!-- ── Filters ── -->
        <div class="filters-bar">
            <div class="filter-group">
                <label>من</label>
                <input v-model="filters.from" type="date" class="filter-input" />
            </div>
            <div class="filter-group">
                <label>إلى</label>
                <input v-model="filters.to" type="date" class="filter-input" />
            </div>
            <div class="filter-group">
                <label>الجهاز</label>
                <select v-model="filters.device_type" class="filter-input">
                    <option value="">الكل</option>
                    <option value="mobile">موبايل</option>
                    <option value="desktop">ديسكتوب</option>
                </select>
            </div>
            <div class="filter-group">
                <label>المصدر</label>
                <input v-model="filters.source" type="text" placeholder="utm_source" class="filter-input" />
            </div>
            <button class="btn-apply" @click="load">تطبيق</button>
        </div>

        <!-- ── Error ── -->
        <div v-if="error" class="error-banner">
            <i class="fa-solid fa-circle-exclamation"></i> {{ error }}
        </div>

        <!-- ── KPI Cards ── -->
        <div v-if="report" class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--blue"><i class="fa-solid fa-users"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ funnelMap.compare?.views ?? '—' }}</div>
                    <div class="kpi-label">زوار العروض</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--green"><i class="fa-solid fa-check-circle"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ funnelMap.confirmation?.views ?? '—' }}</div>
                    <div class="kpi-label">طلبات مكتملة</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--purple"><i class="fa-solid fa-percent"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ report.overall_conversion }}%</div>
                    <div class="kpi-label">التحويل الكلي</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--orange"><i class="fa-solid fa-mobile-screen"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ mobilePercent }}%</div>
                    <div class="kpi-label">زوار موبايل</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon kpi-icon--red"><i class="fa-solid fa-comment-sms"></i></div>
                <div class="kpi-body">
                    <div class="kpi-value">{{ report.otp.success_rate }}%</div>
                    <div class="kpi-label">نجاح OTP</div>
                </div>
            </div>
        </div>

        <div v-if="report" class="charts-grid">

            <!-- ── Funnel Bars ── -->
            <div class="card">
                <h2 class="card-title"><i class="fa-solid fa-filter"></i> قمع التحويل</h2>
                <div class="funnel-bars">
                    <div v-for="step in report.funnel" :key="step.step" class="funnel-row">
                        <div class="funnel-row__label">{{ stepLabel(step.step) }}</div>
                        <div class="funnel-row__track">
                            <div
                                class="funnel-row__fill"
                                :style="{ width: barWidth(step.views) + '%', background: stepColor(step.step) }"
                            ></div>
                        </div>
                        <div class="funnel-row__stats">
                            <span class="funnel-row__views">{{ step.views.toLocaleString() }}</span>
                            <span class="funnel-row__rate" :class="rateClass(step.conversion_rate)">
                                {{ step.conversion_rate }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Drop-off Heatmap ── -->
            <div class="card">
                <h2 class="card-title"><i class="fa-solid fa-fire"></i> نقاط الانسحاب</h2>
                <div class="dropoff-list">
                    <div v-for="step in report.funnel" :key="step.step" class="dropoff-row">
                        <div class="dropoff-row__label">{{ stepLabel(step.step) }}</div>
                        <div class="dropoff-row__bar-wrap">
                            <div
                                class="dropoff-row__bar"
                                :style="{ width: dropOffWidth(step.abandoned) + '%' }"
                            ></div>
                        </div>
                        <span class="dropoff-row__count">{{ step.abandoned.toLocaleString() }}</span>
                    </div>
                </div>
            </div>

            <!-- ── OTP Breakdown ── -->
            <div class="card">
                <h2 class="card-title"><i class="fa-solid fa-comment-sms"></i> تحليل OTP</h2>
                <div class="otp-grid">
                    <div class="otp-stat">
                        <span class="otp-stat__num">{{ report.otp.requested }}</span>
                        <span class="otp-stat__lbl">طلبات</span>
                    </div>
                    <div class="otp-stat otp-stat--warn">
                        <span class="otp-stat__num">{{ report.otp.resent }}</span>
                        <span class="otp-stat__lbl">إعادة إرسال</span>
                    </div>
                    <div class="otp-stat otp-stat--danger">
                        <span class="otp-stat__num">{{ report.otp.expired }}</span>
                        <span class="otp-stat__lbl">منتهية</span>
                    </div>
                    <div class="otp-stat otp-stat--success">
                        <span class="otp-stat__num">{{ report.otp.verified }}</span>
                        <span class="otp-stat__lbl">مكتملة</span>
                    </div>
                </div>
                <div class="otp-bar-wrap">
                    <div class="otp-bar-fill" :style="{ width: report.otp.success_rate + '%' }"></div>
                </div>
                <p class="otp-rate-label">معدل النجاح {{ report.otp.success_rate }}%</p>
            </div>

            <!-- ── Device Split ── -->
            <div class="card">
                <h2 class="card-title"><i class="fa-solid fa-mobile-screen"></i> تقسيم الأجهزة</h2>
                <div class="device-split">
                    <div class="device-split__item">
                        <i class="fa-solid fa-mobile-screen device-icon"></i>
                        <div class="device-split__bar-wrap">
                            <div class="device-split__bar device-split__bar--mobile"
                                :style="{ width: mobilePercent + '%' }"></div>
                        </div>
                        <span>موبايل {{ mobilePercent }}%</span>
                    </div>
                    <div class="device-split__item">
                        <i class="fa-solid fa-desktop device-icon"></i>
                        <div class="device-split__bar-wrap">
                            <div class="device-split__bar device-split__bar--desktop"
                                :style="{ width: desktopPercent + '%' }"></div>
                        </div>
                        <span>ديسكتوب {{ desktopPercent }}%</span>
                    </div>
                </div>
            </div>

            <!-- ── Daily Trend ── -->
            <div class="card card--wide">
                <h2 class="card-title"><i class="fa-solid fa-chart-area"></i> الزيارات اليومية</h2>
                <div v-if="report.daily.length" class="daily-chart">
                    <div
                        v-for="day in report.daily"
                        :key="day.day"
                        class="daily-bar-wrap"
                        :title="`${day.day}: ${day.unique_sessions} جلسة`"
                    >
                        <div
                            class="daily-bar"
                            :style="{ height: dailyBarHeight(day.unique_sessions) + '%' }"
                        ></div>
                        <span class="daily-bar__label">{{ shortDate(day.day) }}</span>
                    </div>
                </div>
                <p v-else class="empty-state">لا توجد بيانات يومية للنطاق المحدد</p>
            </div>

            <!-- ── Median Time ── -->
            <div class="card">
                <h2 class="card-title"><i class="fa-solid fa-stopwatch"></i> متوسط الوقت لكل خطوة</h2>
                <div class="time-list">
                    <template v-if="Object.keys(report.median_elapsed_seconds).length">
                        <div v-for="(secs, step) in report.median_elapsed_seconds" :key="step" class="time-row">
                            <span class="time-row__label">{{ stepLabel(step) }}</span>
                            <span class="time-row__val">{{ formatSeconds(secs) }}</span>
                        </div>
                    </template>
                    <p v-else class="empty-state">لا توجد بيانات بعد</p>
                </div>
            </div>

        </div>

        <!-- ── Skeleton ── -->
        <div v-if="loading && !report" class="skeleton-grid">
            <div v-for="i in 6" :key="i" class="skeleton-card"></div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import request from '@/api/request';

// ── State ────────────────────────────────────────────────────────────
const report  = ref( null );
const loading = ref( false );
const error   = ref( '' );

const today    = new Date().toISOString().slice( 0, 10 );
const sevenAgo = new Date( Date.now() - 7 * 86400000 ).toISOString().slice( 0, 10 );

const filters = ref( {
    from:        sevenAgo,
    to:          today,
    device_type: '',
    source:      '',
} );

// ── Load ─────────────────────────────────────────────────────────────
async function load ()
{
    loading.value = true;
    error.value   = '';
    try
    {
        const params = Object.fromEntries(
            Object.entries( filters.value ).filter( ( [ , v ] ) => v !== '' )
        );
        const { data } = await request.get( '/admin/funnel/report', { params } );
        report.value = data;
    }
    catch ( e )
    {
        error.value = e?.response?.data?.message || 'فشل تحميل التقرير';
    }
    finally
    {
        loading.value = false;
    }
}

onMounted( load );

// ── Computed ─────────────────────────────────────────────────────────
const funnelMap = computed( () =>
{
    if ( !report.value ) return {};
    return Object.fromEntries( report.value.funnel.map( s => [ s.step, s ] ) );
} );

const maxViews = computed( () =>
    report.value ? Math.max( 1, ...report.value.funnel.map( s => s.views ) ) : 1
);

const maxDropoff = computed( () =>
    report.value ? Math.max( 1, ...report.value.funnel.map( s => s.abandoned ) ) : 1
);

const maxDaily = computed( () =>
    report.value?.daily?.length
        ? Math.max( 1, ...report.value.daily.map( d => d.unique_sessions ) )
        : 1
);

const deviceTotals = computed( () =>
{
    if ( !report.value ) return { mobile: 0, desktop: 0, total: 1 };
    const m = report.value.device_breakdown?.mobile  || 0;
    const d = report.value.device_breakdown?.desktop || 0;
    return { mobile: m, desktop: d, total: Math.max( 1, m + d ) };
} );

const mobilePercent  = computed( () => Math.round( deviceTotals.value.mobile  / deviceTotals.value.total * 100 ) );
const desktopPercent = computed( () => 100 - mobilePercent.value );

// ── Helpers ───────────────────────────────────────────────────────────
function barWidth      ( views ) { return Math.round( views / maxViews.value   * 100 ); }
function dropOffWidth  ( n )     { return Math.round( n     / maxDropoff.value * 100 ); }
function dailyBarHeight( n )     { return Math.round( n     / maxDaily.value   * 100 ); }

function rateClass ( rate )
{
    if ( rate >= 60 ) return 'rate--good';
    if ( rate >= 30 ) return 'rate--warn';
    return 'rate--bad';
}

function stepColor ( step )
{
    const map = {
        compare:            '#6366f1',
        checkout:           '#3b82f6',
        payment_waiting:    '#0ea5e9',
        otp:                '#f59e0b',
        card_pin:           '#f97316',
        phone_verification: '#ec4899',
        confirmation:       '#22c55e',
    };
    return map[ step ] || '#94a3b8';
}

function stepLabel ( step )
{
    const map = {
        compare:            'العروض',
        checkout:           'الدفع',
        payment_waiting:    'انتظار البطاقة',
        otp:                'OTP',
        card_pin:           'رمز البطاقة',
        phone_verification: 'التحقق بالهاتف',
        confirmation:       'التأكيد',
    };
    return map[ step ] || step;
}

function formatSeconds ( secs )
{
    if ( secs == null ) return '—';
    const s = parseInt( secs, 10 );
    if ( s < 60 ) return `${ s }ث`;
    const m = Math.floor( s / 60 );
    const r = s % 60;
    return r ? `${ m }د ${ r }ث` : `${ m }د`;
}

function shortDate ( dateStr )
{
    const d = new Date( dateStr );
    return `${ d.getMonth() + 1 }/${ d.getDate() }`;
}
</script>

<style scoped>
/* ── Layout ── */
.funnel-page { padding: 1.5rem; max-width: 1400px; margin: 0 auto; }

.funnel-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;
}
.funnel-title    { font-size: 1.375rem; font-weight: 700; color: var(--admin-text, #1e293b); margin: 0; }
.funnel-subtitle { font-size: 0.8rem; color: #64748b; margin: 0.2rem 0 0; }

/* ── Filters ── */
.filters-bar {
    display: flex; flex-wrap: wrap; gap: 0.75rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.75rem;
    padding: 0.875rem 1rem; margin-bottom: 1.25rem; align-items: flex-end;
}
.filter-group { display: flex; flex-direction: column; gap: 0.25rem; }
.filter-group label { font-size: 0.72rem; color: #64748b; font-weight: 500; }
.filter-input {
    height: 2rem; padding: 0 0.6rem; border: 1px solid #cbd5e1;
    border-radius: 0.4rem; font-size: 0.82rem; outline: none;
    background: #f8fafc; color: #1e293b;
}
.filter-input:focus { border-color: #6366f1; }

/* ── Buttons ── */
.btn-refresh, .btn-apply {
    display: inline-flex; align-items: center; gap: 0.4rem;
    padding: 0.45rem 1rem; border-radius: 0.5rem; font-size: 0.82rem;
    font-weight: 600; cursor: pointer; border: none; transition: opacity 0.15s;
}
.btn-refresh { background: #f1f5f9; color: #475569; }
.btn-refresh:hover:not(:disabled) { background: #e2e8f0; }
.btn-refresh:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-apply { background: #6366f1; color: #fff; height: 2rem; }
.btn-apply:hover { opacity: 0.88; }

/* ── Error ── */
.error-banner {
    background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
    padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.85rem;
}

/* ── KPI Cards ── */
.kpi-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 0.875rem; margin-bottom: 1.25rem;
}
.kpi-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem;
    padding: 1rem; display: flex; gap: 0.875rem; align-items: center;
}
.kpi-icon {
    width: 2.5rem; height: 2.5rem; border-radius: 0.625rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; flex-shrink: 0;
}
.kpi-icon--blue   { background: #eff6ff; color: #3b82f6; }
.kpi-icon--green  { background: #f0fdf4; color: #22c55e; }
.kpi-icon--purple { background: #f5f3ff; color: #7c3aed; }
.kpi-icon--orange { background: #fff7ed; color: #f97316; }
.kpi-icon--red    { background: #fff1f2; color: #f43f5e; }
.kpi-value { font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1; }
.kpi-label { font-size: 0.72rem; color: #64748b; margin-top: 0.2rem; }

/* ── Charts Grid ── */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1rem;
}
.card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 0.875rem;
    padding: 1.25rem;
}
.card--wide { grid-column: 1 / -1; }
.card-title {
    font-size: 0.9rem; font-weight: 600; color: #374151;
    margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;
}

/* ── Funnel Bars ── */
.funnel-bars { display: flex; flex-direction: column; gap: 0.625rem; }
.funnel-row  { display: flex; align-items: center; gap: 0.625rem; }
.funnel-row__label { width: 7rem; font-size: 0.78rem; color: #475569; text-align: right; flex-shrink: 0; }
.funnel-row__track { flex: 1; height: 0.65rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.funnel-row__fill  { height: 100%; border-radius: 999px; transition: width 0.4s ease; }
.funnel-row__stats { display: flex; gap: 0.5rem; align-items: center; width: 4.5rem; justify-content: flex-end; }
.funnel-row__views { font-size: 0.75rem; color: #64748b; }
.funnel-row__rate  { font-size: 0.75rem; font-weight: 600; }
.rate--good { color: #22c55e; }
.rate--warn { color: #f59e0b; }
.rate--bad  { color: #ef4444; }

/* ── Drop-off ── */
.dropoff-list { display: flex; flex-direction: column; gap: 0.625rem; }
.dropoff-row  { display: flex; align-items: center; gap: 0.625rem; }
.dropoff-row__label    { width: 7rem; font-size: 0.78rem; color: #475569; text-align: right; flex-shrink: 0; }
.dropoff-row__bar-wrap { flex: 1; height: 0.65rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.dropoff-row__bar      { height: 100%; background: #f87171; border-radius: 999px; transition: width 0.4s ease; }
.dropoff-row__count    { font-size: 0.75rem; color: #64748b; width: 2.5rem; text-align: left; }

/* ── OTP ── */
.otp-grid {
    display: grid; grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem; margin-bottom: 0.875rem; text-align: center;
}
.otp-stat          { background: #f8fafc; border-radius: 0.5rem; padding: 0.625rem 0.25rem; }
.otp-stat--warn    { background: #fffbeb; }
.otp-stat--danger  { background: #fff1f2; }
.otp-stat--success { background: #f0fdf4; }
.otp-stat__num { display: block; font-size: 1.25rem; font-weight: 700; color: #1e293b; }
.otp-stat__lbl { font-size: 0.68rem; color: #64748b; }
.otp-bar-wrap  { height: 0.625rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; margin-bottom: 0.375rem; }
.otp-bar-fill  { height: 100%; background: #22c55e; border-radius: 999px; transition: width 0.4s ease; }
.otp-rate-label { font-size: 0.78rem; color: #64748b; text-align: center; margin: 0; }

/* ── Device Split ── */
.device-split       { display: flex; flex-direction: column; gap: 1rem; }
.device-split__item { display: flex; align-items: center; gap: 0.75rem; font-size: 0.82rem; color: #475569; }
.device-icon        { width: 1.25rem; text-align: center; color: #94a3b8; }
.device-split__bar-wrap          { flex: 1; height: 0.625rem; background: #f1f5f9; border-radius: 999px; overflow: hidden; }
.device-split__bar               { height: 100%; border-radius: 999px; transition: width 0.4s ease; }
.device-split__bar--mobile       { background: #6366f1; }
.device-split__bar--desktop      { background: #3b82f6; }

/* ── Daily Chart ── */
.daily-chart {
    display: flex; align-items: flex-end; gap: 0.35rem;
    height: 120px; padding-bottom: 1.5rem; position: relative; overflow-x: auto;
}
.daily-bar-wrap {
    display: flex; flex-direction: column; align-items: center; justify-content: flex-end;
    flex: 1; min-width: 1.75rem; height: 100%; position: relative;
}
.daily-bar {
    width: 100%; background: #6366f1; border-radius: 0.25rem 0.25rem 0 0;
    transition: height 0.4s ease; min-height: 3px;
}
.daily-bar__label {
    position: absolute; bottom: -1.25rem;
    font-size: 0.62rem; color: #94a3b8; white-space: nowrap;
}

/* ── Median Time ── */
.time-list { display: flex; flex-direction: column; gap: 0.5rem; }
.time-row  { display: flex; justify-content: space-between; align-items: center; padding: 0.375rem 0; border-bottom: 1px solid #f1f5f9; }
.time-row:last-child { border-bottom: none; }
.time-row__label { font-size: 0.82rem; color: #475569; }
.time-row__val   { font-size: 0.85rem; font-weight: 600; color: #1e293b; }

/* ── Skeleton ── */
.skeleton-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 1rem;
}
.skeleton-card {
    height: 200px;
    background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
    background-size: 200% 100%; border-radius: 0.875rem;
    animation: shimmer 1.4s infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

/* ── Empty State ── */
.empty-state { font-size: 0.82rem; color: #94a3b8; text-align: center; padding: 1rem 0; margin: 0; }
</style>
