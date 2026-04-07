<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">
          <i class="fa-solid fa-envelope-open-text ml-2" :style="{ color: 'var(--admin-accent-blue)' }" aria-hidden="true"></i>
          إحصائيات البريد الإلكتروني
        </h1>
        <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">
          تتبع حملات البريد — الفتح، النقر، ونسب التحويل
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Period selector -->
        <div class="flex items-center rounded-xl overflow-hidden"
          :style="{ backgroundColor: 'var(--admin-surface-2)' }">
          <button
            v-for="p in periodOptions" :key="p.value"
            class="px-4 py-2 text-xs font-bold transition-all"
            :class="days === p.value ? 'bg-blue-500/20 text-blue-400' : ''"
            :style="days !== p.value ? { color: 'var(--admin-text-dim)' } : {}"
            @click="changePeriod(p.value)"
          >{{ p.label }}</button>
        </div>

        <!-- Export CSV -->
        <button
          class="rounded-xl px-4 py-2 text-sm transition-colors flex items-center gap-2"
          :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-secondary)' }"
          :disabled="!stats"
          @click="exportCSV"
        >
          <i class="fa-solid fa-file-csv text-xs" aria-hidden="true"></i>
          تصدير
        </button>

        <!-- Refresh -->
        <button
          class="rounded-xl px-3 py-2 text-sm transition-colors"
          :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
          :disabled="loading"
          @click="fetchStats"
        >
          <i class="fa-solid fa-arrows-rotate text-xs" :class="{ 'fa-spin': loading }" aria-hidden="true"></i>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading && !stats" class="rounded-2xl p-12 text-center"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
      <i class="fa-solid fa-spinner fa-spin text-blue-400 text-3xl mb-3" aria-hidden="true"></i>
      <p class="text-sm" style="color: var(--admin-text-dim);">جاري تحميل إحصائيات البريد...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="rounded-2xl p-12 text-center"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
      <i class="fa-solid fa-circle-exclamation text-red-400 text-3xl mb-3" aria-hidden="true"></i>
      <p class="text-sm text-red-400 mb-3">{{ error }}</p>
      <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700 transition-colors"
        @click="fetchStats">إعادة المحاولة</button>
    </div>

    <!-- Content -->
    <template v-else-if="stats">
      <!-- Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <StatCard label="إجمالي المرسل" :value="stats.sent" icon="fa-solid fa-paper-plane" accentColor="#4ade80" />
        <StatCard label="فشل الإرسال" :value="stats.failed" icon="fa-solid fa-circle-xmark" accentColor="#ef4444" />
        <StatCard label="تم الفتح" :value="stats.opened" icon="fa-solid fa-envelope-open" accentColor="#60a5fa" />
        <StatCard label="نقرات" :value="stats.clicked" icon="fa-solid fa-arrow-pointer" accentColor="#f59e0b" />
        <StatCard label="نسبة الفتح" :value="stats.open_rate + '%'" icon="fa-solid fa-chart-line" accentColor="#a78bfa" />
        <StatCard label="نسبة النقر" :value="stats.click_rate + '%'" icon="fa-solid fa-bullseye" accentColor="#ec4899" />
      </div>

      <!-- Two-column: By-Step + Daily Chart -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- By-Step Breakdown -->
        <div class="rounded-2xl overflow-hidden transition-colors duration-200"
          :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
          <div class="px-5 py-3 flex items-center justify-between"
            :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-border)' }">
            <h2 class="text-sm font-semibold font-heading" :style="{ color: 'var(--admin-text-secondary)' }">
              <i class="fa-solid fa-layer-group ml-1.5 text-purple-400" aria-hidden="true"></i>
              تفصيل حسب المرحلة
            </h2>
          </div>

          <div v-if="stepData.length" class="divide-y" :style="{ borderColor: 'var(--admin-border)' }">
            <div v-for="row in stepData" :key="row.step"
              class="flex items-center gap-4 px-5 py-4 transition-colors hover:brightness-95">
              <!-- Step icon -->
              <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center text-base"
                :style="{ backgroundColor: 'var(--admin-surface-2)' }">
                {{ stepEmoji(row.step) }}
              </div>

              <!-- Step name + bar -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1.5">
                  <span class="font-semibold text-sm" :style="{ color: 'var(--admin-text)' }">{{ stepLabel(row.step) }}</span>
                  <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }">{{ row.sent }} مرسل</span>
                </div>
                <!-- Progress bar: open rate visual -->
                <div class="h-1.5 w-full rounded-full overflow-hidden" :style="{ backgroundColor: 'var(--admin-surface-2)' }">
                  <div class="h-full rounded-full transition-all duration-500"
                    :class="openRate(row) >= 30 ? 'bg-green-500' : openRate(row) >= 15 ? 'bg-amber-500' : 'bg-red-500'"
                    :style="{ width: openRate(row) + '%' }"></div>
                </div>
              </div>

              <!-- Stats -->
              <div class="flex-shrink-0 flex items-center gap-3 text-center">
                <div>
                  <p class="text-xs text-blue-400 font-bold ltr-nums">{{ row.opened }}</p>
                  <p class="text-[10px]" :style="{ color: 'var(--admin-text-dim)' }">فتح</p>
                </div>
                <div>
                  <p class="text-xs text-amber-400 font-bold ltr-nums">{{ row.clicked }}</p>
                  <p class="text-[10px]" :style="{ color: 'var(--admin-text-dim)' }">نقر</p>
                </div>
                <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold"
                  :class="openRate(row) >= 30 ? 'bg-green-500/15 text-green-400' :
                          openRate(row) >= 15 ? 'bg-amber-500/15 text-amber-400' :
                          'bg-red-500/15 text-red-400'">
                  {{ openRate(row) }}%
                </span>
              </div>
            </div>
          </div>

          <div v-else class="py-8 text-center" :style="{ color: 'var(--admin-text-dim)' }">
            <p class="text-sm">لا توجد بيانات حسب المرحلة</p>
          </div>
        </div>

        <!-- Daily Trend -->
        <div class="rounded-2xl overflow-hidden transition-colors duration-200"
          :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
          <div class="px-5 py-3 flex items-center justify-between"
            :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-border)' }">
            <h2 class="text-sm font-semibold font-heading" :style="{ color: 'var(--admin-text-secondary)' }">
              <i class="fa-solid fa-chart-area ml-1.5 text-cyan-400" aria-hidden="true"></i>
              الاتجاه اليومي
            </h2>
          </div>

          <div v-if="daily.length" class="p-5">
            <!-- Simple bar chart -->
            <div class="flex items-end gap-1 h-40">
              <div v-for="d in daily" :key="d.date" class="flex-1 flex flex-col items-center gap-1 group relative">
                <!-- Tooltip on hover -->
                <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity z-10 pointer-events-none">
                  <div class="rounded-lg px-2 py-1 text-[10px] whitespace-nowrap shadow-lg"
                    :style="{ backgroundColor: 'var(--admin-card-bg)', border: '1px solid var(--admin-card-border)', color: 'var(--admin-text)' }">
                    <span class="ltr-nums">{{ d.date }}</span> — {{ d.sent }} مرسل / {{ d.opened }} فتح
                  </div>
                </div>
                <!-- Sent bar -->
                <div class="w-full rounded-t transition-all duration-300 bg-blue-500/40 hover:bg-blue-500/60"
                  :style="{ height: barHeight(d.sent) }"></div>
                <!-- Opened bar overlay -->
                <div class="w-full rounded-t transition-all duration-300 bg-green-500/60 -mt-1"
                  :style="{ height: barHeight(d.opened), marginTop: '-' + barHeight(d.opened) }"></div>
                <!-- Date label -->
                <span class="text-[9px] ltr-nums mt-1 rotate-[-45deg] origin-top-right" :style="{ color: 'var(--admin-text-dim)' }">
                  {{ formatShortDate(d.date) }}
                </span>
              </div>
            </div>
            <!-- Legend -->
            <div class="flex items-center justify-center gap-4 mt-4 text-[10px]" :style="{ color: 'var(--admin-text-muted)' }">
              <span class="flex items-center gap-1"><span class="w-3 h-2 rounded bg-blue-500/40 inline-block"></span> مرسل</span>
              <span class="flex items-center gap-1"><span class="w-3 h-2 rounded bg-green-500/60 inline-block"></span> مفتوح</span>
            </div>
          </div>

          <div v-else class="py-8 text-center" :style="{ color: 'var(--admin-text-dim)' }">
            <p class="text-sm">لا توجد بيانات يومية</p>
          </div>
        </div>
      </div>

      <!-- Failed emails alert -->
      <div v-if="stats.failed > 0" class="rounded-2xl p-4 flex items-center gap-3 mb-6"
        :style="{ backgroundColor: 'rgba(239, 68, 68, 0.08)', borderWidth: '1px', borderColor: 'rgba(239, 68, 68, 0.2)' }">
        <i class="fa-solid fa-triangle-exclamation text-red-400 text-lg" aria-hidden="true"></i>
        <div>
          <p class="text-sm font-semibold text-red-400">{{ stats.failed }} إيميل فشل في الإرسال</p>
          <p class="text-xs text-red-400/60 mt-0.5">في آخر {{ days }} {{ days === 1 ? 'يوم' : 'أيام' }}</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import request from '@/api/request';
import { StatCard } from '../components/ui';

defineOptions( { name: 'EmailStatsPage' } );

const stats = ref( null );
const byStep = ref( {} );
const daily = ref( [] );
const loading = ref( true );
const error = ref( '' );
const days = ref( 7 );

const periodOptions = [
    { value: 1, label: 'اليوم' },
    { value: 7, label: '7 أيام' },
    { value: 30, label: '30 يوم' },
];

const stepData = computed( () => {
    return Object.entries( byStep.value ).map( ( [ step, data ] ) => ( {
        step,
        total: Number( data.total ) || 0,
        sent: Number( data.sent ) || 0,
        opened: Number( data.opened ) || 0,
        clicked: Number( data.clicked ) || 0,
    } ) ).sort( ( a, b ) => b.sent - a.sent );
} );

const maxDailySent = computed( () => {
    if ( !daily.value.length ) return 1;
    return Math.max( ...daily.value.map( d => d.sent ), 1 );
} );

function barHeight ( value ) {
    const pct = Math.max( ( value / maxDailySent.value ) * 100, 2 );
    return pct + '%';
}

function stepLabel ( step ) {
    const labels = {
        compare: 'المقارنة',
        checkout: 'إتمام الطلب',
        payment_waiting: 'انتظار الدفع',
        otp: 'رمز التحقق',
    };
    return labels[ step ] || step;
}

function stepEmoji ( step ) {
    const emojis = {
        compare: '🔍',
        checkout: '🛒',
        payment_waiting: '💳',
        otp: '🔐',
    };
    return emojis[ step ] || '📧';
}

function openRate ( row ) {
    if ( !row.sent ) return 0;
    return Math.round( ( row.opened / row.sent ) * 100 );
}

function formatShortDate ( dateStr ) {
    if ( !dateStr ) return '';
    const d = new Date( dateStr );
    return `${ d.getMonth() + 1 }/${ d.getDate() }`;
}

async function fetchStats () {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await request.get( '/admin/email-stats', { params: { days: days.value } } );
        stats.value = data.summary;
        byStep.value = data.by_step || {};
        daily.value = data.daily || [];
    } catch ( err ) {
        error.value = 'فشل تحميل إحصائيات البريد';
        console.error( 'EmailStats fetch error:', err );
    } finally {
        loading.value = false;
    }
}

function changePeriod ( d ) {
    days.value = d;
    fetchStats();
}

function exportCSV () {
    if ( !stepData.value.length ) return;
    const headers = [ 'المرحلة', 'مرسل', 'مفتوح', 'نقرات', 'نسبة الفتح %' ];
    const rows = stepData.value.map( r => [
        stepLabel( r.step ), r.sent, r.opened, r.clicked, openRate( r ),
    ] );
    // Add summary row
    if ( stats.value ) {
        rows.push( [ 'الإجمالي', stats.value.sent, stats.value.opened, stats.value.clicked, stats.value.open_rate ] );
    }
    const esc = c => `"${ String( c ?? '' ).replace( /"/g, '""' ) }"`;
    const csv = [ headers, ...rows ].map( r => r.map( esc ).join( ',' ) ).join( '\n' );
    const blob = new Blob( [ '\uFEFF' + csv ], { type: 'text/csv;charset=utf-8;' } );
    const url = URL.createObjectURL( blob );
    const a = document.createElement( 'a' );
    a.href = url;
    a.download = `email-stats-${ days.value }d-${ new Date().toISOString().slice( 0, 10 ) }.csv`;
    a.click();
    URL.revokeObjectURL( url );
}

onMounted( fetchStats );
</script>
