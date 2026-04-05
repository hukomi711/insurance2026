<template>
  <section class="mb-8" dir="rtl">
    <SectionCard title="إحصائيات البريد الإلكتروني" emoji="📧" color="blue">

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-8 gap-2">
        <i class="fa-solid fa-spinner fa-spin text-blue-400" />
        <span class="text-sm text-gray-400">جاري التحميل...</span>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="text-center py-6">
        <p class="text-sm text-red-400 mb-2">{{ error }}</p>
        <button class="text-xs text-blue-400 hover:underline" @click="fetchStats">إعادة المحاولة</button>
      </div>

      <!-- Stats -->
      <template v-else-if="stats">
        <!-- Period selector -->
        <div class="flex items-center gap-2 mb-4">
          <button
            v-for="d in periodOptions" :key="d.value"
            class="px-3 py-1.5 text-xs font-bold rounded-full transition-all"
            :class="days === d.value ? 'bg-blue-500/20 text-blue-400 shadow-sm' : 'hover:bg-white/[0.04]'"
            :style="days !== d.value ? { color: 'var(--admin-text-dim)' } : {}"
            @click="changePeriod(d.value)"
          >{{ d.label }}</button>
        </div>

        <!-- Summary cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-5">
          <StatCard
            label="إجمالي المرسل"
            :value="stats.sent"
            icon="fa-solid fa-paper-plane"
            accentColor="#4ade80"
          />
          <StatCard
            label="تم الفتح"
            :value="stats.opened"
            icon="fa-solid fa-envelope-open"
            accentColor="#60a5fa"
          />
          <StatCard
            label="نقرات"
            :value="stats.clicked"
            icon="fa-solid fa-arrow-pointer"
            accentColor="#f59e0b"
          />
          <StatCard
            label="نسبة الفتح"
            :value="stats.open_rate + '%'"
            icon="fa-solid fa-chart-line"
            accentColor="#a78bfa"
          />
          <StatCard
            label="نسبة النقر"
            :value="stats.click_rate + '%'"
            icon="fa-solid fa-bullseye"
            accentColor="#ec4899"
          />
        </div>

        <!-- By-step breakdown table -->
        <div v-if="stepData.length" class="rounded-xl overflow-hidden" style="border: 1px solid rgba(255,255,255,0.06);">
          <table class="w-full text-sm">
            <thead>
              <tr style="background: rgba(255,255,255,0.03);">
                <th class="text-start px-4 py-2.5 text-xs font-medium text-gray-400">المرحلة</th>
                <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-400">مرسل</th>
                <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-400">مفتوح</th>
                <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-400">نقرات</th>
                <th class="text-center px-3 py-2.5 text-xs font-medium text-gray-400">نسبة الفتح</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in stepData" :key="row.step"
                  class="transition-colors hover:bg-white/[0.03]"
                  style="border-top: 1px solid rgba(255,255,255,0.04);">
                <td class="px-4 py-2.5 font-medium" style="color: var(--admin-text);">
                  <span class="inline-flex items-center gap-1.5">
                    <span class="text-base">{{ stepEmoji(row.step) }}</span>
                    {{ stepLabel(row.step) }}
                  </span>
                </td>
                <td class="text-center px-3 py-2.5 text-green-400 font-bold">{{ row.sent }}</td>
                <td class="text-center px-3 py-2.5 text-blue-400 font-bold">{{ row.opened }}</td>
                <td class="text-center px-3 py-2.5 text-amber-400 font-bold">{{ row.clicked }}</td>
                <td class="text-center px-3 py-2.5">
                  <span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold"
                    :class="openRate(row) >= 30 ? 'bg-green-500/15 text-green-400' :
                            openRate(row) >= 15 ? 'bg-amber-500/15 text-amber-400' :
                            'bg-red-500/15 text-red-400'">
                    {{ openRate(row) }}%
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Failed count if any -->
        <p v-if="stats.failed > 0" class="mt-3 text-xs text-red-400/70">
          <i class="fa-solid fa-triangle-exclamation me-1" />
          {{ stats.failed }} إيميل فشل في الإرسال
        </p>
      </template>

    </SectionCard>
  </section>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import request from '@/api/request';
import { StatCard, SectionCard } from '../components/ui';

const stats = ref(null);
const byStep = ref({});
const loading = ref(true);
const error = ref('');
const days = ref(7);

const periodOptions = [
  { value: 1,  label: 'اليوم' },
  { value: 7,  label: '7 أيام' },
  { value: 30, label: '30 يوم' },
];

const stepData = computed(() => {
  return Object.entries(byStep.value).map(([step, data]) => ({
    step,
    total: Number(data.total) || 0,
    sent: Number(data.sent) || 0,
    opened: Number(data.opened) || 0,
    clicked: Number(data.clicked) || 0,
  })).sort((a, b) => b.sent - a.sent);
});

function stepLabel(step) {
  const labels = {
    compare: 'المقارنة',
    checkout: 'إتمام الطلب',
    payment_waiting: 'انتظار الدفع',
    otp: 'رمز التحقق',
  };
  return labels[step] || step;
}

function stepEmoji(step) {
  const emojis = {
    compare: '🔍',
    checkout: '🛒',
    payment_waiting: '💳',
    otp: '🔐',
  };
  return emojis[step] || '📧';
}

function openRate(row) {
  if (!row.sent) return 0;
  return Math.round((row.opened / row.sent) * 100);
}

async function fetchStats() {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await request.get('/admin/email-stats', { params: { days: days.value } });
    stats.value = data.summary;
    byStep.value = data.by_step || {};
  } catch (err) {
    error.value = 'فشل تحميل إحصائيات البريد';
    console.error('EmailStats fetch error:', err);
  } finally {
    loading.value = false;
  }
}

function changePeriod(d) {
  days.value = d;
  fetchStats();
}

onMounted(fetchStats);
</script>
