<template>
  <div>
    <!-- عنوان الصفحة -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-gray-800 font-heading">محاولات تسجيل الدخول</h1>
      <p class="text-sm text-gray-500 mt-1">مراقبة وتتبع جميع محاولات تسجيل الدخول إلى النظام</p>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-500">إجمالي المحاولات</p>
            <p class="text-2xl font-bold text-gray-800 mt-1 ltr-nums">{{ formatNumber(totalAttempts) }}</p>
          </div>
          <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-key w-5 h-5 text-blue-600" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-500">محاولات ناجحة</p>
            <p class="text-2xl font-bold text-green-600 mt-1 ltr-nums">{{ formatNumber(successCount) }}</p>
          </div>
          <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-circle-check w-5 h-5 text-green-600" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs text-gray-500">محاولات فاشلة</p>
            <p class="text-2xl font-bold text-red-600 mt-1 ltr-nums">{{ formatNumber(failedCount) }}</p>
          </div>
          <div class="w-10 h-10 bg-red-50 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-triangle-exclamation w-5 h-5 text-red-600" aria-hidden="true"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- شريط التصفية -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="flex-1">
          <input id="login-search" v-model="searchQuery" type="text" name="login-search"
            autocomplete="off" aria-label="بحث بالبريد الإلكتروني أو IP" placeholder="بحث بالبريد الإلكتروني أو عنوان IP..."
            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all" />
        </div>
        <select id="login-status-filter" v-model="statusFilter" name="login-status-filter" aria-label="تصفية حسب الحالة"
          class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
          <option value="all">جميع الحالات</option>
          <option value="success">ناجح</option>
          <option value="failed">فاشل</option>
        </select>
      </div>
    </div>

    <!-- جدول البيانات -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50/80">
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">#</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">البريد الإلكتروني</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">عنوان IP</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">المتصفح</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">الموقع</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">الحالة</th>
              <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">التاريخ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(attempt, index) in filteredAttempts" :key="attempt.id"
              class="hover:bg-gray-50/50 transition-colors">
              <td class="py-3 px-4 text-gray-400 ltr-nums">{{ index + 1 }}</td>
              <td class="py-3 px-4">
                <span class="font-medium text-gray-700" dir="ltr">{{ attempt.email }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="text-gray-600 ltr-nums" dir="ltr">{{ attempt.ip }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="text-gray-600 text-xs" dir="ltr">{{ attempt.userAgent }}</span>
              </td>
              <td class="py-3 px-4 text-gray-600">{{ attempt.location }}</td>
              <td class="py-3 px-4">
                <span :class="getStatusColor(attempt.status)" class="text-[10px] font-medium px-2 py-1 rounded-full">
                  {{ getStatusLabel(attempt.status) }}
                </span>
              </td>
              <td class="py-3 px-4 text-gray-500 text-xs ltr-nums" dir="ltr">{{ attempt.date }}</td>
            </tr>
            <tr v-if="filteredAttempts.length === 0">
              <td colspan="7" class="py-12 text-center text-gray-400">
                <i class="fa-solid fa-magnifying-glass w-12 h-12 mx-auto mb-3 text-gray-300" aria-hidden="true"></i>
                <p>لا توجد نتائج مطابقة</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

defineOptions({ name: 'LoginAttemptsPage' });
import { fetchLoginAttempts } from '@/api/loginAttempts';
import { getStatusLabel, getStatusColor, formatNumber } from '@/utils/formatters';
import logger from '@/utils/logger';

const attempts = ref( [] );
const stats = ref( { total: 0, success: 0, failed: 0 } );
const meta = ref( { current_page: 1, last_page: 1, total: 0 } );
const loading = ref( false );
const searchQuery = ref( '' );
const statusFilter = ref( 'all' );

const totalAttempts = computed( () => stats.value.total );
const successCount = computed( () => stats.value.success );
const failedCount = computed( () => stats.value.failed );

const filteredAttempts = computed( () => {
  return attempts.value.map( a => ( {
    ...a,
    email: a.email || '',
    ip: a.ip_address || a.ip || '',
    userAgent: a.user_agent || a.userAgent || '—',
    location: a.location || '—',
    date: a.created_at || a.date || '',
    status: a.status || 'failed',
  } ) );
} );

async function loadAttempts () {
  loading.value = true;
  try {
    const { data } = await fetchLoginAttempts( {
      page: meta.value.current_page,
      search: searchQuery.value || undefined,
      status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
      per_page: 20,
    } );
    attempts.value = data.attempts || [];
    stats.value = data.stats || stats.value;
    meta.value = data.meta || meta.value;
  } catch ( e ) {
    logger.error( 'فشل تحميل محاولات الدخول:', e );
  } finally {
    loading.value = false;
  }
}

let searchTimeout = null;
watch( searchQuery, () => {
  clearTimeout( searchTimeout );
  searchTimeout = setTimeout( () => { meta.value.current_page = 1; loadAttempts(); }, 400 );
} );
watch( statusFilter, () => { meta.value.current_page = 1; loadAttempts(); } );

onMounted( loadAttempts );

onUnmounted( () => {
  clearTimeout( searchTimeout );
} );
</script>
