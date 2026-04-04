<template>
  <div>
    <!-- عنوان الصفحة -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">أنشطة العملاء</h1>
        <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">تتبع فوري لآخر نشاطات العملاء مع ترتيب ديناميكي</p>
      </div>
      <div class="flex items-center gap-3">
        <button class="px-4 py-2 text-sm rounded-xl transition-colors"
          :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-secondary)' }"
          :disabled="!filteredActivities.length" @click="exportCSV">
          <i class="fa-solid fa-file-csv ml-1"></i>
          تصدير CSV
        </button>
        <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-xl hover:bg-blue-700 transition-colors"
          @click="manualRefresh">
          تحديث يدوي
        </button>
        <span :class="liveActive ? 'bg-green-500/15 text-green-400' : ''"
          :style="liveActive ? {} : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
          class="text-xs font-medium px-3 py-1.5 rounded-full flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full" :class="liveActive ? 'bg-green-500 animate-pulse' : ''"
            :style="liveActive ? {} : { backgroundColor: 'var(--admin-text-dim)' }"></span>
          {{ liveActive ? 'مباشر' : 'متوقف' }}
        </span>
      </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">إجمالي العملاء</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-text)' }">{{ formatNumber(totalCustomers) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-status-info-bg)' }">
            <i class="fa-solid fa-users w-5 h-5" :style="{ color: 'var(--admin-accent-blue)' }" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">نشط الآن</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-accent-blue)' }">{{ formatNumber(activeCount) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-status-info-bg)' }">
            <i class="fa-solid fa-chart-line w-5 h-5" :style="{ color: 'var(--admin-accent-blue)' }" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">مكتمل</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-accent-green)' }">{{ formatNumber(completedCount) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-status-success-bg)' }">
            <i class="fa-solid fa-circle-check w-5 h-5" :style="{ color: 'var(--admin-accent-green)' }" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">فشل</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-accent-red)' }">{{ formatNumber(failedCount) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-status-error-bg)' }">
            <i class="fa-solid fa-triangle-exclamation w-5 h-5" :style="{ color: 'var(--admin-accent-red)' }" aria-hidden="true"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- شريط التصفية -->
    <div class="rounded-2xl p-4 mb-6 transition-colors duration-200"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input id="activity-search" v-model="searchQuery" type="text" name="activity-search"
            autocomplete="off" aria-label="بحث باسم العميل" placeholder="بحث باسم العميل أو رقم الجوال..."
            class="w-full px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
            :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
        </div>
        <select id="activity-stage-filter" v-model="stageFilter" name="activity-stage-filter"
          aria-label="تصفية حسب المرحلة"
          class="px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
          :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }">
          <option value="all">جميع المراحل</option>
          <option value="customer_info">معلومات العميل</option>
          <option value="vehicle_info">معلومات المركبة</option>
          <option value="compare">مقارنة الأسعار</option>
          <option value="checkout">إتمام الشراء</option>
          <option value="payment">الدفع</option>
        </select>
        <select id="activity-status-filter" v-model="statusFilter" name="activity-status-filter"
          aria-label="تصفية حسب الحالة"
          class="px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
          :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }">
          <option value="all">جميع الحالات</option>
          <option value="active">نشط</option>
          <option value="completed">مكتمل</option>
          <option value="failed">فشل</option>
        </select>
      </div>
    </div>

    <!-- قائمة الأنشطة -->
    <div class="rounded-2xl overflow-hidden transition-colors duration-200"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
      <div class="px-5 py-3 flex items-center justify-between" :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-border)' }">
        <h2 class="text-sm font-semibold font-heading" :style="{ color: 'var(--admin-text-secondary)' }">ترتيب النشاط — آخر نشاط أولاً</h2>
        <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }">{{ filteredActivities.length }} عميل</span>
      </div>

      <div class="divide-y" :style="{ borderColor: 'var(--admin-border)' }">
        <div v-for="(activity, index) in filteredActivities" :key="activity.id"
          class="flex items-center gap-4 px-5 py-4 transition-colors hover:brightness-95">
          <!-- الترتيب -->
          <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
            :style="index < 3
              ? { backgroundColor: 'var(--admin-status-info-bg)', color: 'var(--admin-accent-blue)' }
              : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }">
            <span class="ltr-nums">{{ index + 1 }}</span>
          </div>

          <!-- معلومات العميل -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-semibold text-sm" :style="{ color: 'var(--admin-text)' }">{{ activity.customerName }}</span>
              <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }" dir="ltr">{{ activity.phone }}</span>
              <span v-if="activity.status === 'active'" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            </div>
            <p class="text-xs truncate" :style="{ color: 'var(--admin-text-muted)' }">{{ activity.description }}</p>
          </div>

          <!-- المرحلة -->
          <div class="flex-shrink-0">
            <span :class="getStatusColor(activity.stage)" class="text-[10px] font-medium px-2 py-1 rounded-full">
              {{ getStatusLabel(activity.stage) }}
            </span>
          </div>

          <!-- الحالة -->
          <div class="flex-shrink-0">
            <span :class="getActivityStatusColor(activity.status)"
              class="text-[10px] font-medium px-2 py-1 rounded-full">
              {{ getActivityStatusLabel(activity.status) }}
            </span>
          </div>

          <!-- الوقت النسبي -->
          <div class="flex-shrink-0 text-left w-24">
            <span class="text-xs" :style="{ color: 'var(--admin-text-dim)' }">{{ getRelativeTime(activity.lastActivityAt) }}</span>
          </div>
        </div>

        <!-- حالة فارغة -->
        <div v-if="filteredActivities.length === 0" class="py-12 text-center" :style="{ color: 'var(--admin-text-dim)' }">
          <i class="fa-solid fa-users w-12 h-12 mx-auto mb-3" :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
          <p>لا توجد أنشطة مطابقة</p>
        </div>
      </div>
    </div>

    <!-- ملاحظة -->
    <div class="mt-4 text-center">
      <p class="text-xs" :style="{ color: 'var(--admin-text-dim)' }">التحديثات تلقائية كل 5 ثوانٍ — الترتيب حسب آخر نشاط</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';

defineOptions({ name: 'CustomerActivityPage' });
import { fetchCustomerActivities } from '@/api/customerActivities';
import { getStatusLabel, getStatusColor, formatNumber } from '@/utils/formatters';
import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
import logger from '@/utils/logger';

const activities = ref( [] );
const stats = ref( { total: 0, active: 0, completed: 0, failed: 0 } );
const meta = ref( { current_page: 1, last_page: 1, total: 0 } );
const loading = ref( false );
const searchQuery = ref( '' );
const stageFilter = ref( 'all' );
const statusFilter = ref( 'all' );
const liveActive = ref( true );

// إحصائيات
const totalCustomers = computed( () => stats.value.total );
const activeCount = computed( () => stats.value.active );
const completedCount = computed( () => stats.value.completed );
const failedCount = computed( () => stats.value.failed );

// تسميات حالة النشاط
function getActivityStatusLabel( status ) {
  const map = { active: 'نشط', completed: 'مكتمل', failed: 'فشل' };
  return map[ status ] || status;
}

function getActivityStatusColor( status ) {
  const map = {
    active: 'bg-blue-100 text-blue-700',
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
  };
  return map[ status ] || 'bg-gray-100 text-gray-700';
}

// الوقت النسبي
function getRelativeTime( isoDate ) {
  const now = new Date();
  const then = new Date( isoDate );
  const diffMs = now - then;
  const diffMin = Math.floor( diffMs / 60000 );

  if ( diffMin < 1 ) return 'الآن';
  if ( diffMin < 60 ) return `منذ ${ diffMin } د`;
  const diffHrs = Math.floor( diffMin / 60 );
  if ( diffHrs < 24 ) return `منذ ${ diffHrs } س`;
  return `منذ ${ Math.floor( diffHrs / 24 ) } يوم`;
}

// القائمة المصفاة والمرتبة — الفلترة تتم من السيرفر
const filteredActivities = computed( () => {
  return activities.value
    .map( a => ( {
      ...a,
      customerName: a.customer_name || a.customerName || 'زائر',
      phone: a.phone || '',
      description: a.description || '',
      stage: a.stage || '',
      status: a.status || 'active',
      lastActivityAt: a.created_at || a.lastActivityAt || '',
    } ) );
} );

async function loadActivities () {
  loading.value = true;
  try {
    const { data } = await fetchCustomerActivities( {
      page: meta.value.current_page,
      search: searchQuery.value || undefined,
      stage: stageFilter.value !== 'all' ? stageFilter.value : undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      per_page: 30,
    } );
    activities.value = data.activities || [];
    stats.value = data.stats || stats.value;
    meta.value = data.meta || meta.value;
  } catch ( e ) {
    logger.error( 'فشل تحميل الأنشطة:', e );
  } finally {
    loading.value = false;
  }
}

import { watch } from 'vue';
let searchTimeout = null;
watch( searchQuery, () => {
  clearTimeout( searchTimeout );
  searchTimeout = setTimeout( () => { meta.value.current_page = 1; loadActivities(); }, 400 );
} );
watch( [ stageFilter, statusFilter ], () => { meta.value.current_page = 1; loadActivities(); } );

function manualRefresh () {
  meta.value.current_page = 1;
  loadActivities();
}

function exportCSV () {
  if ( !filteredActivities.value.length ) return;
  const headers = [ 'الاسم', 'الجوال', 'المرحلة', 'الحالة', 'الوصف', 'آخر نشاط' ];
  const rows = filteredActivities.value.map( a => [
    a.customerName,
    a.phone,
    getStatusLabel( a.stage ),
    getActivityStatusLabel( a.status ),
    a.description,
    a.lastActivityAt,
  ] );
  const esc = c => `"${ String( c ?? '' ).replace( /"/g, '""' ) }"`;
  const csv = [ headers, ...rows ].map( r => r.map( esc ).join( ',' ) ).join( '\n' );
  const blob = new Blob( [ '\uFEFF' + csv ], { type: 'text/csv;charset=utf-8;' } );
  const url  = URL.createObjectURL( blob );
  const a    = document.createElement( 'a' );
  a.href     = url;
  a.download = `customer-activities-${ new Date().toISOString().slice( 0, 10 ) }.csv`;
  a.click();
  URL.revokeObjectURL( url );
}

let _mounted = false;

onMounted( () => {
  _mounted = true;
  loadActivities();
  registerPollingCallback( 'customerActivities', loadActivities );
} );

onUnmounted( () => {
  _mounted = false;
  clearTimeout( searchTimeout );
  unregisterPollingCallback( 'customerActivities' );
} );

// ── KeepAlive lifecycle: pause/resume polling when cached ──
onActivated( () => {
  if ( !_mounted ) return; // onMounted already fired — skip duplicate
  loadActivities();
  registerPollingCallback( 'customerActivities', loadActivities );
} );

onDeactivated( () => {
  clearTimeout( searchTimeout );
  unregisterPollingCallback( 'customerActivities' );
} );
</script>
