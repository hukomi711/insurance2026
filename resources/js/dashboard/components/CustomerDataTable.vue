<template>
  <div class="customer-data-table" dir="rtl">
    <div class="customer-table-scroll admin-table-surface overflow-x-auto overscroll-x-contain rounded-xl shadow-sm">
      <table class="min-w-280 w-full table-fixed text-sm md:min-w-345 xl:min-w-405">
        <thead class="admin-table-head border-b">
          <tr>
            <th class="w-12.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">حذف</th>
            <th class="w-27.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">المزيد</th>
            <th class="w-42.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">المسار الحالي</th>
            <th class="w-25 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">الدفع</th>
            <th class="w-27.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">بيانات التأمين</th>
            <th class="w-32.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              الاسم
            </th>
            <th class="w-27.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">البيانات الأساسية</th>
            <th class="w-27.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              رقم الهوية
            </th>
            <th class="w-22.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              الموقع
            </th>
            <th class="w-22.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              المنطقة
            </th>
            <th class="w-30 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              IP
            </th>
            <th class="w-27.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              آخر نشاط
            </th>
            <th class="w-12.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">
              الحالة
            </th>
            <th class="w-22.5 px-2 py-3 text-center font-semibold text-slate-600 whitespace-nowrap">#</th>
          </tr>
        </thead>
        <tbody class="admin-table-body divide-y">
          <tr
            v-for="(customer, index) in tableRows"
            :id="`customer-row-${customer.id}`"
            :key="customer.id"
            :class="[
              'admin-table-row transition-colors',
              { 'admin-row-focus': focusedCustomerId === customer.id },
              { 'bg-red-50 ring-1 ring-inset ring-red-200': customer.is_blocked },
            ]"
          >
            <!-- حذف -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                type="button"
                class="admin-icon-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2"
                title="حذف بيانات العميل من النظام"
                aria-label="حذف بيانات العميل من النظام بشكل دائم"
                @click="$emit('delete-card', customer.id)"
              >
                <i class="fa-solid fa-trash w-4 h-4" aria-hidden="true"></i>
              </button>
            </td>

            <!-- المزيد -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div class="flex items-center justify-center gap-2">
                <button
                  class="admin-icon-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200"
                  title="المزيد من التفاصيل"
                  aria-label="المزيد من التفاصيل"
                  @click="openInfoModal(customer)"
                >
                  <i class="fa-solid fa-circle-info w-4 h-4" aria-hidden="true"></i>
                </button>

                <button
                  v-if="!isViewer"
                  type="button"
                  :title="customer.is_blocked ? 'إلغاء حظر العميل' : 'حظر العميل'"
                  :class="[
                    'inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-[11px] font-bold text-white transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                    customer.is_blocked ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700',
                  ]"
                  :disabled="String(blockingCustomerId) === String(customer.id)"
                  @click.stop="$emit(customer.is_blocked ? 'unblock' : 'block', customer)"
                >
                  {{
                    String(blockingCustomerId) === String(customer.id)
                      ? ( customer.is_blocked ? 'جاري إلغاء الحظر...' : 'جاري الحظر...' )
                      : ( customer.is_blocked ? 'إلغاء الحظر' : 'حظر' )
                  }}
                </button>
              </div>
            </td>

            <!-- المسار الحالي -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div class="relative flex justify-center">
                <button
                  :ref="(el) => setButtonRef(customer.id, el)"
                  data-journey-trigger
                  class="admin-icon-btn inline-flex w-40 items-center justify-between rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 transition-colors"
                  @click="toggleJourneyDropdown(customer.id, $event)"
                >
                  <span class="truncate">{{ customer._ui.pageLabel || 'غير محدد' }}</span>
                  <i class="fa-solid fa-chevron-down w-4 h-4 ms-1 shrink-0" aria-hidden="true"></i>
                </button>
              </div>
            </td>

            <!-- الدفع -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-all duration-200"
                :class="
                  !customer._ui.payment.has
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : customer._ui.payment.isNew
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100 hover:bg-blue-100'
                "
                :title="
                  !customer._ui.payment.has
                    ? 'لا توجد بيانات'
                    : customer._ui.payment.isNew
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openPaymentModal(customer)"
              >
                <template v-if="customer._ui.payment.has">
                  <span v-if="!customer._ui.payment.isNew" class="text-blue-500">👁</span>
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
                  !customer._ui.insurance.has
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : customer._ui.insurance.isNew
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100 hover:bg-blue-100'
                "
                :title="
                  !customer._ui.insurance.has
                    ? 'لا توجد بيانات'
                    : customer._ui.insurance.isNew
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openInsuranceDataModal(customer)"
              >
                <template v-if="customer._ui.insurance.has">
                  <span v-if="!customer._ui.insurance.isNew" class="text-blue-500">👁</span>
                  <span v-else class="h-2 w-2 rounded-full bg-white animate-ping"></span>
                </template>
                <span v-else class="text-gray-400">—</span>
                <span>التأمين</span>
              </button>
            </td>

            <!-- الاسم -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div class="flex flex-col items-center gap-1">
                <span class="font-semibold text-gray-800">{{ getCustomerName(customer) || customer?._ui?.displayName || '\u2014' }}</span>
                <span
                  v-if="customer.is_blocked"
                  class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-inset ring-red-200"
                  title="هذا العميل محظور"
                >
                  <i class="fa-solid fa-ban text-[9px]" aria-hidden="true"></i>
                  محظور
                </span>
              </div>
            </td>

            <!-- البيانات الأساسية -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <button
                class="inline-flex items-center justify-center gap-1.5 rounded-lg px-3.5 py-2 text-xs font-medium transition-all duration-200"
                :class="
                  !customer._ui.vehicle.has
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                    : customer._ui.vehicle.isNew
                      ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30 animate-pulse hover:bg-emerald-600'
                      : 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-100 hover:bg-blue-100'
                "
                :title="
                  !customer._ui.vehicle.has
                    ? 'لا توجد بيانات'
                    : customer._ui.vehicle.isNew
                      ? 'بيانات جديدة - انقر للعرض'
                      : 'تم العرض'
                "
                @click="openVehicleQuoteModal(customer)"
              >
                <template v-if="customer._ui.vehicle.has">
                  <span v-if="!customer._ui.vehicle.isNew" class="text-blue-500">👁</span>
                  <span v-else class="h-2 w-2 rounded-full bg-white animate-ping"></span>
                </template>
                <span v-else class="text-gray-400">—</span>
                <span>المركبة</span>
              </button>
            </td>

            <!-- رقم الهوية -->
            <td class="px-3 py-2 font-mono text-xs text-gray-700 whitespace-nowrap">{{ customer.nationalId || '\u2014' }}</td>

            <!-- الموقع -->
            <td class="px-3 py-2 text-center whitespace-nowrap">
              <div
                v-if="customer._ui.displayCity || customer._ui.displayCountry"
                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-medium"
                :class="customer._ui.isSaudiCountry
                    ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-200'
                    : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200'
                "
                :title="(customer._ui.displayCity || '') + ', ' + (customer._ui.displayCountry || '')"
              >
                <span>{{ customer._ui.countryFlag }}</span>
                <span class="max-w-16 truncate">{{
                  customer._ui.displayCity || customer._ui.displayCountry || '—'
                }}</span>
              </div>
              <span v-else class="text-gray-400 text-xs">—</span>
            </td>

            <!-- المنطقة -->
            <td class="px-2 py-2 text-center text-xs text-gray-600 whitespace-nowrap">
              <span class="max-w-20 truncate inline-block" :title="customer._ui.displayRegion || ''">
                {{ customer._ui.displayRegion || '—' }}
              </span>
            </td>

            <!-- IP -->
            <td class="px-3 py-2 font-mono text-xs text-gray-700 whitespace-nowrap">{{ customer.ip }}</td>

            <!-- آخر نشاط -->
            <td class="px-2 py-2 text-center text-xs text-gray-500 whitespace-nowrap" :title="customer.last_activity_at">
              {{ customer._ui.relativeActivity }}
            </td>

            <!-- الحالة -->
            <td class="px-2 py-2 text-center whitespace-nowrap">
              <span
                class="inline-block h-3 w-3 rounded-full"
                :class="
                  customer._ui.online
                    ? 'animate-pulse bg-emerald-500 shadow-lg shadow-emerald-500/50'
                    : 'bg-gray-300'
                "
                :title="customer._ui.online ? 'نشط' : 'غير نشط'"
              >
              </span>
            </td>

            <!-- # -->
            <td class="px-2 py-2 text-center whitespace-nowrap">
              <div class="flex flex-col items-center gap-1">
                <span class="text-gray-500">{{ rowNumber(index) }}</span>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Journey Dropdown Teleport -->
    <Teleport to="body">
      <div
        v-if="activeJourneyDropdown && dropdownPosition"
        data-journey-dropdown
        class="fixed z-9999 w-64 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl"
        :style="{ top: dropdownPosition.top + 'px', left: dropdownPosition.left + 'px' }"
        @click.stop
      >
        <div
          class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-3 py-2"
        >
          <span class="text-sm font-semibold text-gray-800">
            <i class="fa-solid fa-route me-1.5 text-blue-600" aria-hidden="true"></i>تغيير المسار
          </span>
          <button class="rounded-md p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="إغلاق" @click="closeJourneyDropdown">
            <i class="fa-solid fa-xmark w-4 h-4" aria-hidden="true"></i>
          </button>
        </div>
        <div class="max-h-80 overflow-y-auto py-1">
          <template v-for="(pages, category) in pageCategories" :key="category">
            <div class="sticky top-0 border-b border-gray-100 bg-gray-50/95 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-500 backdrop-blur-sm">
              {{ category }}
            </div>
            <button
              v-for="page in pages"
              :key="page.value"
              class="flex min-h-10 w-full items-center gap-2 px-3 py-2 text-right text-sm transition-colors hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-transparent"
              :class="
                getActiveCustomerPage() === page.url ? 'bg-blue-600 text-white hover:bg-blue-700' : 'text-gray-700'
              "
              :disabled="isViewer"
              :title="isViewer ? 'لا تملك صلاحية تغيير المسار' : ''"
              @click="redirectCustomerToPage(activeJourneyDropdown, page.value)"
            >
              <i :class="'fa-solid ' + page.icon" class="w-4 h-4 text-center text-xs opacity-60 shrink-0"></i>
              <span class="flex-1">{{ page.label }}</span>
              <span
                v-if="getActiveCustomerPage() === page.url"
                class="text-[10px] bg-white/20 px-1.5 py-0.5 rounded-full"
                >الحالي</span
              >
              <span v-else-if="page.isWaiting" class="rounded-full bg-orange-50 px-1.5 py-0.5 text-[10px] text-orange-700">انتظار</span>
            </button>
          </template>
        </div>
      </div>
    </Teleport>

    <!-- Info Modal (Card Control) -->
    <InfoModal
      v-if="selectedCustomer"
      v-model:active-tab="activeTab"
      :open="showModal"
      :customer="selectedCustomer"
      :get-page-name="getPageName"
      @close="closeModal"
    />

    <!-- Vehicle Quote Modal -->
    <BasicDataModal
      v-if="selectedVQCustomer"
      v-model:active-tab="activeVQTab"
      :open="showVQModal"
      :customer="selectedVQCustomer"
      @close="closeVQModal"
    />

    <!-- Insurance Data Modal -->
    <InsuranceDataModal
      v-if="selectedInsuranceCustomer"
      :open="showInsuranceModal"
      :customer="selectedInsuranceCustomer"
      @close="closeInsuranceModal"
    />

    <!-- Payment Modal -->
    <PaymentModal
      v-if="selectedPaymentCustomer"
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
import { ref, toRef, computed, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
const InfoModal = defineAsyncComponent( () => import( './modals/InfoModal.vue' ) );
const BasicDataModal = defineAsyncComponent( () => import( './modals/BasicDataModal.vue' ) );
const InsuranceDataModal = defineAsyncComponent( () => import( './modals/InsuranceDataModal.vue' ) );
const PaymentModal = defineAsyncComponent( () => import( './modals/PaymentModal.vue' ) );
import { usePaymentModal } from '@/dashboard/composables/usePaymentModal';
import { useJourneyDropdown } from '@/dashboard/composables/useJourneyDropdown';
import { useUserStore } from '@/store/modules/user';

const userStore = useUserStore();
const isViewer = computed( () => userStore.role === 'viewer' );

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
  focusedCustomerId: {
    type: [Number, String],
    default: null,
  },
  currentPage: {
    type: Number,
    default: 1,
  },
  perPage: {
    type: Number,
    default: 80,
  },
  blockingCustomerId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['delete-card', 'show-details', 'action', 'redirect', 'block', 'unblock', 'modal-opened', 'modal-closed']);

function rowNumber ( index ) {
    return ( Math.max( props.currentPage, 1 ) - 1 ) * Math.max( props.perPage, 1 ) + index + 1;
}

function formatRelativeTime ( isoString ) {
    if ( !isoString ) return '—';
    const diff = Date.now() - new Date( isoString ).getTime();
    const seconds = Math.floor( diff / 1000 );
    if ( seconds < 60 ) return 'الآن';
    const minutes = Math.floor( seconds / 60 );
    if ( minutes < 60 ) return `${ minutes } د`;
    const hours = Math.floor( minutes / 60 );
    if ( hours < 24 ) return `${ hours } س`;
    const days = Math.floor( hours / 24 );
    return `${ days } ي`;
}

const ONLINE_WINDOW_MS = 3 * 60 * 1000;

function getDisplayCity ( customer ) {
    return customer.location?.city || customer.location_city || customer.city || customer.custom_data?.city || null;
}

function getDisplayCountry ( customer ) {
    return customer.location?.country || customer.location_country || customer.country || null;
}

function getDisplayRegion ( customer ) {
    return customer.location?.region || customer.location_region || customer.region || customer.custom_data?.region || null;
}

function isCustomerOnline ( customer ) {
    if ( typeof customer?.is_online === 'boolean' ) return customer.is_online;

    const lastActivity = customer?.last_activity_at || customer?.last_activity;
    if ( !lastActivity ) return Boolean( customer?.is_active );

    // Fallback path only (primary path above uses a skew-free backend-computed
    // value) — small buffer for minor client/server clock skew, not full drift.
    const CLOCK_SKEW_TOLERANCE_MS = 10_000;
    const diff = Date.now() - new Date( lastActivity ).getTime();
    return diff >= -CLOCK_SKEW_TOLERANCE_MS && diff <= ONLINE_WINDOW_MS;
}

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
  selectedVQCustomer.value = structuredClone(customer);
  requestAnimationFrame(() => {
    showVQModal.value = true;
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
  // ✅ Emit modal-closed BEFORE clearing reference — parent re-marks viewed
  if ( _closingId ) emit( 'modal-closed', { id: _closingId, ip: _closingIp, section: 'vehicle' } );
  vqCleanupTimer = setTimeout(() => { selectedVQCustomer.value = null; }, 200);
};

// --- Insurance Modal ---
const showInsuranceModal = ref(false);
const selectedInsuranceCustomer = ref(null);

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
  selectedInsuranceCustomer.value = structuredClone(customer);
  requestAnimationFrame(() => {
    showInsuranceModal.value = true;
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
  if (customer.ip) {
    // InfoModal's "home" tab shows vehicle+insurance+payment data at once —
    // mark all three viewed, matching the dedicated per-section modals.
    emit('modal-opened', { id: customer.id, ip: customer.ip, section: 'vehicle' });
    emit('modal-opened', { id: customer.id, ip: customer.ip, section: 'insurance' });
    emit('modal-opened', { id: customer.id, ip: customer.ip, section: 'payment' });
  }
};

const closeModal = () => {
  const _closingId = selectedCustomer.value?.id;
  const _closingIp = selectedCustomer.value?.ip;
  showModal.value = false;
  selectedCustomer.value = null;
  if (_closingId) {
    emit('modal-closed', { id: _closingId, ip: _closingIp, section: 'vehicle' });
    emit('modal-closed', { id: _closingId, ip: _closingIp, section: 'insurance' });
    emit('modal-closed', { id: _closingId, ip: _closingIp, section: 'payment' });
  }
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

// Precomputed per-row display/badge data — avoids recalculating the same
// values multiple times per row on every re-render (each was previously
// called directly from the template, some up to 3x per row).
// Namespaced under `_ui` so it can never collide with a real API field
// (e.g. the customer's own `payment` object holds card data).
const tableRows = computed(() => props.customers.map((c) => ({
  ...c,
  _ui: {
    displayName: getCustomerName(c),
    displayCity: getDisplayCity(c),
    displayCountry: getDisplayCountry(c),
    displayRegion: getDisplayRegion(c),
    countryFlag: getCountryFlag(getDisplayCountry(c)),
    isSaudiCountry: isSaudi(getDisplayCountry(c)),
    online: isCustomerOnline(c),
    relativeActivity: formatRelativeTime(c.last_activity_at),
    pageLabel: getPageName(c.journey?.current_page || c.current_page),
    payment: { has: hasPaymentData(c), isNew: hasNewPaymentData(c) },
    insurance: { has: hasInsuranceData(c), isNew: hasNewInsuranceData(c) },
    vehicle: { has: hasVehicleData(c), isNew: hasNewVehicleQuoteData(c) },
  },
})));

onMounted(() => {
  if (typeof document !== 'undefined') document.addEventListener('click', handleClickOutside);
});

let vqCleanupTimer = null;
let insCleanupTimer = null;

onUnmounted(() => {
  clearTimeout(vqCleanupTimer);
  clearTimeout(insCleanupTimer);
  // Also removes the scroll/resize listeners registered while a journey dropdown was open.
  closeJourneyDropdown();
  if (typeof document !== 'undefined') document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
@reference "../../../css/app.css";

.customer-data-table {
  direction: ltr;
}

.customer-table-scroll {
  -webkit-overflow-scrolling: touch;
  scrollbar-gutter: stable;
  background: var(--admin-card-bg, #ffffff);
  border-color: var(--admin-card-border, #e5e7eb);
}

.admin-table-surface {
  background: var(--admin-surface);
  border: 1px solid var(--admin-card-border);
}

.admin-table-head {
  background: var(--admin-surface-2);
  border-color: var(--admin-card-border);
  color: var(--admin-text-muted);
}

.admin-table-body {
  background: var(--admin-surface);
  border-color: var(--admin-card-border);
  color: var(--admin-text);
}

.admin-table-row {
  border-color: var(--admin-card-border);
}

.admin-table-row:hover {
  background: var(--admin-hover-accent);
}

.customer-data-table thead {
  border-color: var(--admin-card-border, #e5e7eb);
  background: var(--admin-surface-2, #f8fafc);
}

.customer-data-table thead th {
  color: var(--admin-text-secondary, #4b5563);
}

.customer-data-table tbody {
  --tw-divide-opacity: 1;
  border-color: var(--admin-card-border, #e5e7eb);
}

.customer-data-table tbody tr {
  background: var(--admin-card-bg, #ffffff);
}

.customer-data-table tbody tr:hover {
  background: var(--admin-surface-2, #f8fafc);
}

.customer-data-table tbody td,
.customer-data-table tbody td > span,
.customer-data-table tbody td .text-white,
.customer-data-table tbody td .text-gray-300,
.customer-data-table tbody td .text-gray-400 {
  color: var(--admin-text-secondary, #4b5563);
}

.customer-data-table tbody .bg-slate-700,
.customer-data-table tbody .bg-slate-600,
.customer-data-table tbody .bg-gray-700 {
  background: var(--admin-surface-2, #f3f4f6);
  color: var(--admin-text, #374151);
}

.customer-data-table tbody .bg-slate-700:hover,
.customer-data-table tbody .bg-slate-600:hover {
  background: var(--admin-surface-3, #e5e7eb);
  color: var(--admin-text, #111827);
}

.customer-data-table td .admin-icon-btn {
  background-color: var(--admin-card-bg, #ffffff);
  border-color: var(--admin-card-border, #e5e7eb);
  color: var(--admin-text-muted, #6b7280);
}

.customer-data-table table td,
.customer-data-table table th {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
