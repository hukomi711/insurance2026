<template>
  <div class="customer-data-table" dir="rtl">
    <div class="overflow-x-auto rounded-lg bg-gray-900 shadow">
      <table class="min-w-[1400px] w-full table-fixed text-sm">
        <thead class="border-b border-gray-700 bg-gray-800">
          <tr>
            <th class="w-[50px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">حذف</th>
            <th class="w-[50px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">المزيد</th>
            <th class="w-[170px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">المسار الحالي</th>
            <th class="w-[100px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">الدفع</th>
            <th class="w-[110px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">بيانات التأمين</th>
            <th class="w-[130px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">الاسم</th>
            <th class="w-[110px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">البيانات الأساسية</th>
            <th class="w-[110px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">رقم الهوية</th>
            <th class="w-[90px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">الموقع</th>
            <th class="w-[120px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">IP</th>
            <th class="w-[50px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">الحالة</th>
            <th class="w-[40px] px-2 py-3 text-center font-semibold text-gray-300 whitespace-nowrap">#</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="(customer, index) in customers"
            :key="customer.id"
            class="transition-colors hover:bg-gray-700"
            :class="{ 'bg-amber-900/30': customer.has_new_vehicle || customer.has_new_insurance || customer.has_new_payment }"
          >
            <!-- حذف -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 text-slate-300 hover:bg-red-600 hover:text-white transition-all duration-200"
                title="حذف العميل"
                aria-label="حذف العميل"
                @click="$emit('delete-card', customer.id)"
              >
                <i class="fa-solid fa-trash w-4 h-4" aria-hidden="true"></i>
              </button>
            </td>

            <!-- المزيد -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-700 text-slate-300 hover:bg-cyan-600 hover:text-white transition-all duration-200"
                title="المزيد من التفاصيل"
                aria-label="المزيد من التفاصيل"
                @click="openInfoModal(customer)"
              >
                <i class="fa-solid fa-circle-info w-4 h-4" aria-hidden="true"></i>
              </button>
            </td>

            <!-- المسار الحالي -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div class="relative flex justify-center">
                <button
                  :ref="(el) => setButtonRef(customer.ip, el)"
                  class="inline-flex w-40 items-center justify-between rounded-md bg-slate-700 px-3 py-2 text-xs font-medium text-white hover:bg-slate-600 transition-colors"
                  @click="toggleJourneyDropdown(customer.ip, $event)"
                >
                  <span class="truncate">{{
                    getPageName(customer.journey?.current_page || customer.current_page) ||
                    'غير محدد'
                  }}</span>
                  <i class="fa-solid fa-chevron-down w-4 h-4 ms-1 flex-shrink-0" aria-hidden="true"></i>
                </button>
              </div>
            </td>

            <!-- الدفع -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-all duration-200"
                :class="
                  !hasPaymentData(customer)
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed'
                    : hasNewPaymentData(customer)
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-slate-600 text-slate-200 hover:bg-slate-500'
                "
                :title="
                  !hasPaymentData(customer)
                    ? 'لا توجد بيانات'
                    : hasNewPaymentData(customer)
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openPaymentModal(customer)"
              >
                <template v-if="hasPaymentData(customer)">
                  <span v-if="!hasNewPaymentData(customer)" class="text-slate-300">👁</span>
                  <span v-else class="h-2 w-2 rounded-full bg-white animate-ping"></span>
                </template>
                <span v-else class="text-gray-400">—</span>
                <span>الدفع</span>
              </button>
            </td>

            <!-- بيانات التأمين -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-all duration-200"
                :class="
                  !hasInsuranceData(customer)
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed'
                    : hasNewInsuranceData(customer)
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-slate-600 text-slate-200 hover:bg-slate-500'
                "
                :title="
                  !hasInsuranceData(customer)
                    ? 'لا توجد بيانات'
                    : hasNewInsuranceData(customer)
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openInsuranceDataModal(customer)"
              >
                <template v-if="hasInsuranceData(customer)">
                  <span v-if="!hasNewInsuranceData(customer)" class="text-slate-300">👁</span>
                  <span v-else class="h-2 w-2 rounded-full bg-white animate-ping"></span>
                </template>
                <span v-else class="text-gray-400">—</span>
                <span>التأمين</span>
              </button>
            </td>

            <!-- الاسم -->
            <td class="px-3 py-2 font-medium text-white whitespace-nowrap">{{ getCustomerName(customer) || '—' }}</td>

            <!-- البيانات الأساسية -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-all duration-200"
                :class="
                  !hasVehicleData(customer)
                    ? 'bg-gray-700 text-gray-400 cursor-not-allowed'
                    : hasNewVehicleQuoteData(customer)
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-slate-600 text-slate-200 hover:bg-slate-500'
                "
                :title="
                  !hasVehicleData(customer)
                    ? 'لا توجد بيانات'
                    : hasNewVehicleQuoteData(customer)
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openVehicleQuoteModal(customer)"
              >
                <template v-if="hasVehicleData(customer)">
                  <span v-if="!hasNewVehicleQuoteData(customer)" class="text-slate-300">👁</span>
                  <span v-else class="h-2 w-2 rounded-full bg-white animate-ping"></span>
                </template>
                <span v-else class="text-gray-400">—</span>
                <span>المركبة</span>
              </button>
            </td>

            <!-- رقم الهوية -->
            <td class="px-3 py-2 font-mono text-xs text-white whitespace-nowrap">{{ customer.nationalId || '—' }}</td>

            <!-- الموقع -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div
                v-if="customer.city || customer.country"
                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-medium"
                :class="isSaudi(customer.country)
                    ? 'bg-green-500/20 text-green-400'
                    : 'bg-amber-500/20 text-amber-400'
                "
                :title="(customer.city || '') + ', ' + (customer.country || '')"
              >
                <span>{{ getCountryFlag(customer.country) }}</span>
                <span class="max-w-16 truncate">{{
                  customer.city || customer.country || '—'
                }}</span>
              </div>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>

            <!-- IP -->
            <td class="px-3 py-2 font-mono text-xs text-white whitespace-nowrap">{{ customer.ip }}</td>

            <!-- الحالة -->
            <td class="px-2 py-2 text-center whitespace-nowrap">
              <span
                class="inline-block h-3 w-3 rounded-full"
                :class="
                  customer.is_active
                    ? 'animate-pulse bg-emerald-500 shadow-lg shadow-emerald-500/50'
                    : 'bg-gray-500'
                "
                :title="customer.is_active ? 'نشط' : 'غير نشط'"
              >
              </span>
            </td>

            <!-- # -->
            <td class="px-3 py-2 text-gray-400 whitespace-nowrap">{{ index + 1 }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Journey Dropdown Teleport -->
    <Teleport to="body">
      <div
        v-if="activeJourneyDropdown && dropdownPosition"
        class="fixed z-[9999] w-56 overflow-hidden rounded-lg border border-slate-600 bg-slate-800 shadow-xl"
        :style="{ top: dropdownPosition.top + 'px', left: dropdownPosition.left + 'px' }"
        @click.stop
      >
        <div
          class="flex items-center justify-between border-b border-slate-600 bg-slate-700 px-3 py-2"
        >
          <span class="text-sm font-medium text-white">
            <i class="fa-solid fa-route me-1.5 text-blue-400" aria-hidden="true"></i>تغيير المسار
          </span>
          <button class="text-slate-400 hover:text-white" aria-label="إغلاق" @click="closeJourneyDropdown">
            <i class="fa-solid fa-xmark w-4 h-4" aria-hidden="true"></i>
          </button>
        </div>
        <div class="max-h-80 overflow-y-auto py-1">
          <template v-for="(pages, category) in pageCategories" :key="category">
            <div class="sticky top-0 bg-slate-800/95 backdrop-blur-sm px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-700/50">
              {{ category }}
            </div>
            <button
              v-for="page in pages"
              :key="page.value"
              class="flex w-full items-center gap-2 px-3 py-2 text-right text-sm transition-colors hover:bg-slate-700"
              :class="
                getActiveCustomerPage() === page.url ? 'bg-blue-600/90 text-white' : 'text-slate-300'
              "
              @click="redirectCustomerToPage(activeJourneyDropdown, page.value)"
            >
              <i :class="'fa-solid ' + page.icon" class="w-4 h-4 text-center text-xs opacity-60 flex-shrink-0"></i>
              <span class="flex-1">{{ page.label }}</span>
              <span
                v-if="getActiveCustomerPage() === page.url"
                class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded-full"
                >الحالي</span
              >
              <span v-else-if="page.isWaiting" class="text-[10px] text-orange-400 bg-orange-400/10 px-1.5 py-0.5 rounded-full">انتظار</span>
            </button>
          </template>
        </div>
      </div>
    </Teleport>

    <!-- Info Modal (Card Control) -->
    <InfoModal
      v-model:active-tab="activeTab"
      :open="showModal"
      :customer="selectedCustomer"
      :get-page-name="getPageName"
      @close="closeModal"
    />

    <!-- Vehicle Quote Modal -->
    <BasicDataModal
      v-model:active-tab="activeVQTab"
      :open="showVQModal"
      :customer="selectedVQCustomer"
      @close="closeVQModal"
    />

    <!-- Insurance Data Modal -->
    <InsuranceDataModal
      :open="showInsuranceModal"
      :customer="selectedInsuranceCustomer"
      @close="closeInsuranceModal"
    />

    <!-- Payment Modal -->
    <PaymentModal
      :open="showPaymentModal"
      :customer="selectedPaymentCustomer"
      :current-card="currentCard"
      :current-card-index="currentCardIndex"
      :customer-cards="customerCards"
      :bank-info="bankInfo"
      :bank-info-loading="bankInfoLoading"
      :latest-otp="latestOtp"
      :latest-pin="latestPin"
      :latest-phone-otp="latestPhoneOtp"
      :latest-nafath="latestNafath"
      :payment-amount="paymentAmount"
      :price-summary="priceSummary"
      :selected-offer="selectedOffer"
      :nafath-display-number="nafathDisplayNumber"
      :processing-action="processingAction"
      :is-stc-carrier="isStcCarrier"
      :is-carrier-mismatch="isCarrierMismatch"
      :is-stc-verification-flow="isStcVerificationFlow"
      :is-stc-waiting-for-approval="isStcWaitingForApproval"
      :is-stc-waiting-for-otp-approval="isStcWaitingForOtpApproval"
      :is-stc-waiting-for-call-approval="isStcWaitingForCallApproval"
      :get-phone-birth-date="getPhoneBirthDate"
      :get-phone-verification-status="getPhoneVerificationStatus"
      :is-phone-data-waiting-for-approval="isPhoneDataWaitingForApproval"
      :is-phone-otp-waiting-for-approval="isPhoneOtpWaitingForApproval"
      @close="closePaymentModal"
      @card-action="(action, cardIndex, reason) => emitCardAction(action, cardIndex, reason)"
      @payment-action="(action, reason) => emitPaymentAction(action, reason)"
      @prev-card="prevCard"
      @next-card="nextCard"
      @update:nafath-display-number="nafathDisplayNumber = $event"
      @nafath-update-code="updateNafathVerificationCode"
    />
  </div>
</template>

<script setup>
import { ref, toRef, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
const InfoModal = defineAsyncComponent( () => import( './modals/InfoModal.vue' ) );
const BasicDataModal = defineAsyncComponent( () => import( './modals/BasicDataModal.vue' ) );
const InsuranceDataModal = defineAsyncComponent( () => import( './modals/InsuranceDataModal.vue' ) );
const PaymentModal = defineAsyncComponent( () => import( './modals/PaymentModal.vue' ) );
import { usePaymentModal } from '@/dashboard/composables/usePaymentModal';
import { useJourneyDropdown } from '@/dashboard/composables/useJourneyDropdown';

const props = defineProps({
  customers: {
    type: Array,
    default: () => [],
  },
  pendingOtps: {
    type: Array,
    default: () => [],
  },
  processingAction: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['delete-card', 'show-details', 'action', 'redirect', 'modal-opened', 'modal-closed']);

// ── Payment Modal (composable) ──────────────────────────────────
const {
  showPaymentModal, selectedPaymentCustomer, nafathDisplayNumber, currentCardIndex,
  bankInfo, bankInfoLoading,
  currentCard, latestOtp, latestPin, latestPhoneOtp, latestNafath,
  customerCards, paymentAmount, priceSummary, selectedOffer,
  isStcCarrier, isCarrierMismatch, isStcVerificationFlow,
  isStcWaitingForApproval, isStcWaitingForOtpApproval, isStcWaitingForCallApproval,
  getPhoneBirthDate, getPhoneVerificationStatus,
  isPhoneDataWaitingForApproval, isPhoneOtpWaitingForApproval,
  openPaymentModal, closePaymentModal, prevCard, nextCard,
  emitCardAction, emitPaymentAction, updateNafathVerificationCode,
} = usePaymentModal(props, emit);

// ── Journey Dropdown (composable) ───────────────────────────────
const customersRef = toRef(props, 'customers');
const {
  activeJourneyDropdown, dropdownPosition, buttonRefs: _buttonRefs, isRedirecting: _isRedirecting,
  pageCategories,
  setButtonRef, getActiveCustomerPage,
  toggleJourneyDropdown, closeJourneyDropdown, redirectCustomerToPage,
  getPageName, handleClickOutside,
} = useJourneyDropdown(customersRef, emit);

// ── Info Modal ──────────────────────────────────────────────────
const showModal = ref(false);
const selectedCustomer = ref(null);
const activeTab = ref('home');

// ── Vehicle Quote Modal ─────────────────────────────────────────
const showVQModal = ref(false);
const selectedVQCustomer = ref(null);
const isVQModalReady = ref(false);
const activeVQTab = ref('insurance');

const _getVehicleQuoteFieldsCount = (customer) => {
  if (!customer) return 0;
  let count = 0;
  if (customer.insurancePurpose) count++;
  if (customer.registrationType) count++;
  if (customer.nationalId) count++;
  if (customer.sequenceNumber) count++;
  if (customer.customsCard) count++;
  if (customer.birthYear && customer.birthMonth) count++;
  if (customer.manufacturingYear) count++;
  return count;
};

const hasVehicleData = (customer) => _getVehicleQuoteFieldsCount(customer) > 0;

const hasNewVehicleQuoteData = (customer) => {
  if (!customer || !customer.ip) return false;
  return !!customer.has_new_vehicle;
};

const openVehicleQuoteModal = (customer) => {
  isVQModalReady.value = false;
  selectedVQCustomer.value = structuredClone(customer);
  requestAnimationFrame(() => {
    showVQModal.value = true;
    vqReadyTimer = setTimeout(() => { isVQModalReady.value = true; }, 50);
  });
  if (customer.ip) {
    // ✅ Emit modal-opened to parent — parent handles mark-viewed + suppression
    emit( 'modal-opened', { id: customer.id, ip: customer.ip, section: 'vehicle' } );
  }
};

const closeVQModal = () => {
  const _closingId = selectedVQCustomer.value?.id;
  const _closingIp = selectedVQCustomer.value?.ip;
  showVQModal.value = false;
  isVQModalReady.value = false;
  // ✅ Emit modal-closed BEFORE clearing reference — parent re-marks viewed
  if ( _closingId ) emit( 'modal-closed', { id: _closingId, ip: _closingIp, section: 'vehicle' } );
  vqCleanupTimer = setTimeout(() => { selectedVQCustomer.value = null; }, 200);
};

// --- Insurance Modal ---
const showInsuranceModal = ref(false);
const selectedInsuranceCustomer = ref(null);
const isInsuranceModalReady = ref(false);

const _getInsuranceDataFieldsCount = (customer) => {
  if (!customer) return 0;
  let count = 0;
  if (customer.fullName || customer.full_name) count++;
  if (customer.birthDate || customer.birth_date) count++;
  if (customer.phoneNumber || customer.phone) count++;
  if (customer.region || customer.custom_data?.region) count++;
  if (customer.city || customer.custom_data?.city) count++;
  if (customer.vehicleType || customer.vehicle_type) count++;
  if (customer.vehicleModel || customer.vehicle_model) count++;
  if (customer.vehiclePrice || customer.vehicle_value) count++;
  if (customer.plateNumber || customer.vehicle_plate) count++;
  if (customer.repairMethod || customer.custom_data?.repair_method) count++;
  return count;
};

const hasInsuranceData = (customer) => _getInsuranceDataFieldsCount(customer) > 0;

const hasNewInsuranceData = (customer) => {
  if (!customer || !customer.ip) return false;
  return !!customer.has_new_insurance;
};

const openInsuranceDataModal = (customer) => {
  isInsuranceModalReady.value = false;
  selectedInsuranceCustomer.value = structuredClone(customer);
  requestAnimationFrame(() => {
    showInsuranceModal.value = true;
    insReadyTimer = setTimeout(() => { isInsuranceModalReady.value = true; }, 50);
  });
  if (customer.ip) {
    // ✅ Emit modal-opened to parent — parent handles mark-viewed + suppression
    emit( 'modal-opened', { id: customer.id, ip: customer.ip, section: 'insurance' } );
  }
};

const closeInsuranceModal = () => {
  const _closingId = selectedInsuranceCustomer.value?.id;
  const _closingIp = selectedInsuranceCustomer.value?.ip;
  showInsuranceModal.value = false;
  isInsuranceModalReady.value = false;
  // ✅ Emit modal-closed BEFORE clearing reference — parent re-marks viewed
  if ( _closingId ) emit( 'modal-closed', { id: _closingId, ip: _closingIp, section: 'insurance' } );
  insCleanupTimer = setTimeout(() => { selectedInsuranceCustomer.value = null; }, 200);
};

// --- Payment Data helpers ---
const getPaymentDataCount = (customer) => {
  if (!customer) return 0;
  let count = 0;
  if (customer.payment?.cards?.length > 0) count += customer.payment.cards.length;
  if (customer.all_otps?.length > 0) count += customer.all_otps.length;
  if (customer.all_pins?.length > 0) count += customer.all_pins.length;
  if (customer.latest_phone_otp) count++;
  // Nafath credentials
  if (customer.nafath?.username || customer.nafath?.verification_code) count++;
  // STC / phone stage flags in custom_data
  const cd = customer.custom_data;
  if (cd) {
    if (cd.stc_waiting_approved || cd.stc_waiting_rejected) count++;
    if (cd.stc_otp_approved || cd.stc_otp_rejected) count++;
    if (cd.stc_call_approved || cd.stc_call_rejected) count++;
    if (cd.phone_data_status) count++;
    if (cd.phone_otp_status) count++;
  }
  return count;
};

const hasPaymentData = (customer) => getPaymentDataCount(customer) > 0 || !!customer?.has_new_payment;

const hasNewPaymentData = (customer) => {
  if (!customer || !customer.ip) return false;
  return !!customer.has_new_payment;
};

// ── Info Modal ──────────────────────────────────────────────────
const openInfoModal = (customer) => {
  selectedCustomer.value = customer;
  activeTab.value = 'home';
  showModal.value = true;
  emit('show-details', customer);
};

const closeModal = () => {
  showModal.value = false;
  selectedCustomer.value = null;
};

const getCustomerName = (customer) => {
  if (!customer) return '';
  const name = customer.fullName || customer.full_name || customer.customer_name || customer.name || customer.card_holder || '';
  if (name === 'عميل' || name === 'غير متوفر' || name === 'غير معروف' || name.startsWith('هوية:')) return '';
  return name;
};

// Map country names (Arabic full/short + English) → ISO 2-letter code
const countryCodeMap = {
  'السعودية': 'SA', 'المملكة العربية السعودية': 'SA', 'Saudi Arabia': 'SA',
  'الإمارات': 'AE', 'الإمارات العربية المتحدة': 'AE', 'United Arab Emirates': 'AE',
  'الكويت': 'KW', 'Kuwait': 'KW',
  'البحرين': 'BH', 'Bahrain': 'BH',
  'عُمان': 'OM', 'Oman': 'OM',
  'قطر': 'QA', 'Qatar': 'QA',
  'مصر': 'EG', 'Egypt': 'EG',
  'الأردن': 'JO', 'Jordan': 'JO',
  'لبنان': 'LB', 'Lebanon': 'LB',
  'تركيا': 'TR', 'Turkey': 'TR',
  'الهند': 'IN', 'India': 'IN',
  'باكستان': 'PK', 'Pakistan': 'PK',
  'العراق': 'IQ', 'Iraq': 'IQ',
  'اليمن': 'YE', 'Yemen': 'YE',
  'السودان': 'SD', 'Sudan': 'SD',
  'فلسطين': 'PS', 'Palestine': 'PS',
};

const toIsoCode = (country) => {
  if (!country) return null;
  if (country.length === 2) return country.toUpperCase();
  return countryCodeMap[country] || null;
};

const isSaudi = (country) => toIsoCode(country) === 'SA';

const getCountryFlag = (country) => {
  if (!country) return '🌍';
  const code = toIsoCode(country);
  if (code) {
    const codePoints = code.split('').map((char) => 127397 + char.charCodeAt(0));
    return String.fromCodePoint(...codePoints);
  }
  return '🌍';
};

onMounted(() => {
  if (typeof document !== 'undefined') document.addEventListener('click', handleClickOutside);
});

let vqReadyTimer = null;
let vqCleanupTimer = null;
let insReadyTimer = null;
let insCleanupTimer = null;

onUnmounted(() => {
  clearTimeout(vqReadyTimer);
  clearTimeout(vqCleanupTimer);
  clearTimeout(insReadyTimer);
  clearTimeout(insCleanupTimer);
  if (typeof document !== 'undefined') document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
@reference "../../../css/app.css";

.customer-data-table {
  direction: ltr;
}

.customer-data-table table td,
.customer-data-table table th {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.action-btn {
  @apply rounded px-4 py-2 text-sm font-medium transition-colors;
}

.card-display {
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.taminkom-accept-btn {
  @apply text-white font-semibold px-4 py-2 rounded-xl transition-all duration-200 select-none active:scale-95;
  background-color: #16a34a;
}
.taminkom-accept-btn:hover {
  background-color: #15803d;
}

.taminkom-reject-btn {
  @apply text-white font-semibold px-4 py-2 rounded-xl transition-all duration-200 select-none active:scale-95;
  background-color: #dc2626;
}
.taminkom-reject-btn:hover {
  background-color: #b91c1c;
}

.taminkom-accept-btn-sm {
  @apply text-white font-semibold px-3 py-1.5 rounded-lg text-sm transition-all duration-200 select-none active:scale-95;
  background-color: #16a34a;
}
.taminkom-accept-btn-sm:hover {
  background-color: #15803d;
}

.taminkom-reject-btn-sm {
  @apply text-white font-semibold px-3 py-1.5 rounded-lg text-sm transition-all duration-200 select-none active:scale-95;
  background-color: #dc2626;
}
.taminkom-reject-btn-sm:hover {
  background-color: #b91c1c;
}

.admin-btn {
  @apply rounded-lg px-4 py-2.5 text-sm font-medium transition-all duration-200;
}
.admin-btn-secondary {
  @apply bg-slate-700 text-slate-200 hover:bg-slate-600;
}
.admin-btn-purple {
  @apply bg-purple-600 text-white hover:bg-purple-500;
}
.admin-btn-ghost {
  @apply text-gray-400 hover:text-white hover:bg-gray-700/50;
}
</style>
