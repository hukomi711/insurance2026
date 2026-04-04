<template>
  <div>
    <!-- عنوان الصفحة -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">محاولات تسجيل الدخول</h1>
      <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">مراقبة وتتبع جميع محاولات تسجيل الدخول إلى النظام</p>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">إجمالي المحاولات</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-text)' }">{{ formatNumber(totalAttempts) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: 'var(--admin-status-info-bg)' }">
            <i class="fa-solid fa-key w-5 h-5" :style="{ color: 'var(--admin-accent-blue)' }" aria-hidden="true"></i>
          </div>
        </div>
      </div>

      <div class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">محاولات ناجحة</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: 'var(--admin-accent-green)' }">{{ formatNumber(successCount) }}</p>
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
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">محاولات فاشلة</p>
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
          <input id="login-search" v-model="searchQuery" type="text" name="login-search"
            autocomplete="off" aria-label="بحث بالبريد الإلكتروني أو IP" placeholder="بحث بالبريد الإلكتروني أو عنوان IP..."
            class="w-full px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
            :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }" />
        </div>
        <select id="login-status-filter" v-model="statusFilter" name="login-status-filter" aria-label="تصفية حسب الحالة"
          class="px-4 py-2.5 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all"
          :style="{ backgroundColor: 'var(--admin-input-bg)', borderWidth: '1px', borderColor: 'var(--admin-input-border)', color: 'var(--admin-input-text)' }">
          <option value="all">جميع الحالات</option>
          <option value="success">ناجح</option>
          <option value="failed">فاشل</option>
        </select>
      </div>
    </div>

    <!-- جدول البيانات -->
    <div class="rounded-2xl overflow-hidden transition-colors duration-200"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr :style="{ backgroundColor: 'var(--admin-surface-2)' }">
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">#</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">البريد الإلكتروني</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">عنوان IP</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">المتصفح</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">الموقع</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">الحالة</th>
              <th class="text-right py-3 px-4 text-xs font-semibold" :style="{ color: 'var(--admin-text-muted)' }">التاريخ</th>
            </tr>
          </thead>
          <tbody class="divide-y" :style="{ borderColor: 'var(--admin-border)' }">
            <tr v-for="(attempt, index) in filteredAttempts" :key="attempt.id"
              class="transition-colors hover:brightness-95">
              <td class="py-3 px-4 ltr-nums" :style="{ color: 'var(--admin-text-dim)' }">{{ index + 1 }}</td>
              <td class="py-3 px-4">
                <span class="font-medium" :style="{ color: 'var(--admin-text-secondary)' }" dir="ltr">{{ attempt.email }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="ltr-nums" :style="{ color: 'var(--admin-text-secondary)' }" dir="ltr">{{ attempt.ip }}</span>
              </td>
              <td class="py-3 px-4">
                <span class="text-xs" :style="{ color: 'var(--admin-text-secondary)' }" dir="ltr">{{ attempt.userAgent }}</span>
              </td>
              <td class="py-3 px-4" :style="{ color: 'var(--admin-text-secondary)' }">{{ attempt.location }}</td>
              <td class="py-3 px-4">
                <span :class="getStatusColor(attempt.status)" class="text-[10px] font-medium px-2 py-1 rounded-full">
                  {{ getStatusLabel(attempt.status) }}
                </span>
              </td>
              <td class="py-3 px-4 text-xs ltr-nums" :style="{ color: 'var(--admin-text-muted)' }" dir="ltr">{{ attempt.date }}</td>
            </tr>
            <tr v-if="filteredAttempts.length === 0">
              <td colspan="7" class="py-12 text-center" :style="{ color: 'var(--admin-text-dim)' }">
                <i class="fa-solid fa-magnifying-glass w-12 h-12 mx-auto mb-3" :style="{ color: 'var(--admin-text-dim)' }" aria-hidden="true"></i>
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
import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
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

onMounted( () => {
  loadAttempts();
  registerPollingCallback( 'loginAttempts', loadAttempts );
} );

onUnmounted( () => {
  clearTimeout( searchTimeout );
  unregisterPollingCallback( 'loginAttempts' );
} );
</script>
