<template>
  <ModalShell
    :open="open"
    title="بيانات التأمين"
    :subtitle="customer?.ip"
    emoji="📋"
    size="lg"
    accent="#a78bfa"
    dir="rtl"
    @close="$emit('close')"
  >
    <!-- البيانات الشخصية -->
    <SectionCard title="البيانات الشخصية" emoji="👤" color="blue">
      <InfoGrid :cols="2">
        <DataField label="المنطقة" :value="customer?.region" />
        <DataField label="المدينة" :value="customer?.city" />
        <DataField label="تاريخ بدء الوثيقة" :value="customer?.policyStartDate || customer?.policy_start_date || customer?.custom_data?.policy_start_date" />
        <DataField class="sm:col-span-2" label="نوع التأمين المطلوب" :value="getInsuranceType(customer?.insuranceType || customer?.insurance_type)" color="text-emerald-400" />
      </InfoGrid>
    </SectionCard>

    <!-- بيانات التواصل -->
    <SectionCard title="بيانات التواصل" emoji="📞" color="cyan">
      <InfoGrid :cols="2">
        <DataField
          class="sm:col-span-2"
          label="الاسم"
          :value="getCustomerName(customer)"
        />
        <DataField
          label="رقم الجوال"
          :value="customer?.phoneNumber || customer?.phone"
          mono
        />
        <DataField
          label="البريد الإلكتروني"
          :value="customer?.email"
        />
      </InfoGrid>
    </SectionCard>

    <!-- بيانات المركبة -->
    <SectionCard title="بيانات المركبة" emoji="🚗" color="green">
      <InfoGrid :cols="2">
        <DataField label="الغرض من الاستخدام" :value="getUsagePurpose(customer?.usagePurpose || customer?.usage_purpose || customer?.custom_data?.usage_purpose)" />
        <DataField label="سعر المركبة" :value="formatPrice(customer?.price || customer?.vehiclePrice || customer?.vehicle_value)" mono :bold="true" color="text-emerald-400" />
        <DataField
          label="طريقة الإصلاح"
          :value="repairMethodLabel(customer)"
        />
        <DataField label="نوع المركبة" :value="customer?.vehicleType || customer?.vehicle_type || customer?.custom_data?.vehicle_type" />
      </InfoGrid>
    </SectionCard>

    <!-- السائق الإضافي -->
    <SectionCard
      v-if="customer?.hasAdditionalDriver || customer?.custom_data?.has_additional_driver"
      title="السائق الإضافي"
      emoji="👥"
      color="cyan"
    >
      <InfoGrid :cols="2">
        <DataField class="sm:col-span-2" label="اسم السائق الإضافي" :value="customer?.additionalDriverName || customer?.custom_data?.additional_driver?.name" />
        <DataField label="رقم الهوية (السائق الإضافي)" :value="customer?.additionalDriverNationalId || customer?.custom_data?.additional_driver?.national_id" mono :bold="true" color="text-yellow-400" />
        <DataField label="تاريخ الميلاد" :value="customer?.additionalDriverBirthDate || customer?.custom_data?.additional_driver?.birth_date" />
      </InfoGrid>
    </SectionCard>

    <!-- تفاصيل أخرى -->
    <SectionCard
      v-if="customer?.extraData || customer?.extra_data"
      title="تفاصيل أخرى"
      emoji="📝"
      color="orange"
    >
      <InfoGrid :cols="3">
        <DataField label="مكان إيقاف المركبة ليلاً" :value="getNightParking(getExtra('night_parking'))" />
        <DataField label="الكيلومترات السنوية" :value="getExpectedKM(getExtra('expected_km'))" />
        <DataField label="ناقل الحركة" :value="getTransmission(getExtra('transmission_type'))" />
        <DataField label="عدد الحوادث" :value="getExtra('accident_counts')" mono />
        <DataField label="التعليم" :value="getEducation(getExtra('education'))" />
        <DataField label="أطفال دون 16" :value="getExtra('children_under_16')" mono />
        <DataField class="lg:col-span-3" label="جهة العمل" :value="getExtra('work_location')" />
      </InfoGrid>
      <!-- Yes/No flags -->
      <div class="mt-3 flex flex-wrap gap-2">
        <StatusPill
          :variant="getExtra('car_modification') === 'yes' ? 'error' : 'success'"
          :label="getExtra('car_modification') === 'yes' ? '✗ تعديلات مركبة' : '✓ بدون تعديلات'"
        />
        <StatusPill
          :variant="getExtra('has_trailer') === 'yes' ? 'warning' : 'success'"
          :label="getExtra('has_trailer') === 'yes' ? '✓ مقطورة' : '✗ بدون مقطورة'"
        />
        <StatusPill
          :variant="getExtra('foreign_license') === 'yes' ? 'info' : 'neutral'"
          :label="getExtra('foreign_license') === 'yes' ? '✓ رخصة أجنبية' : '✗ بدون رخصة أجنبية'"
        />
        <StatusPill
          :variant="getExtra('health_conditions') === 'yes' ? 'error' : 'success'"
          :label="getExtra('health_conditions') === 'yes' ? '✗ ظروف صحية' : '✓ بدون ظروف صحية'"
        />
        <StatusPill
          :variant="getExtra('traffic_violations') === 'yes' ? 'error' : 'success'"
          :label="getExtra('traffic_violations') === 'yes' ? '✗ مخالفات مرورية' : '✓ بدون مخالفات'"
        />
      </div>
      <!-- Modification / Trailer details -->
      <DataField v-if="getExtra('modification_desc')" class="mt-2" label="وصف التعديلات" :value="getExtra('modification_desc')" />
      <DataField v-if="getExtra('trailer_value')" class="mt-2" label="قيمة المقطورة" :value="formatPrice(getExtra('trailer_value'))" mono color="text-emerald-400" />
    </SectionCard>

    <!-- السائقون الإضافيون -->
    <div v-if="getExtra('drivers') && getExtra('drivers').length > 0" class="pb-2">
      <h4 class="mb-3 text-sm font-bold text-pink-400">🚘 السائقون الإضافيون</h4>
      <div v-for="(drv, idx) in getExtra('drivers')" :key="idx" class="admin-info-field mb-2 rounded-lg p-3">
        <div class="flex items-center justify-between">
          <span class="text-xs" style="color: var(--admin-text-dim)">السائق {{ idx + 1 }}</span>
        </div>
        <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
          <div><span style="color: var(--admin-text-dim)">الاسم: </span><span style="color: var(--admin-text)">{{ drv.name || drv.fullName || '—' }}</span></div>
          <div><span style="color: var(--admin-text-dim)">الهوية: </span><span class="font-mono text-yellow-400">{{ drv.nationalId || drv.id_number || '—' }}</span></div>
        </div>
      </div>
    </div>

    <template #footer>
      <AdminButton variant="purple" label="إغلاق" class="w-full" @click="$emit('close')" />
    </template>
  </ModalShell>
</template>

<script setup>
import { ModalShell, DataField, StatusPill, AdminButton, SectionCard, InfoGrid } from '../ui';
import { useCustomerFormatters } from '../../utils/customerFormatters';

const {
  formatPrice, getInsuranceType, getUsagePurpose, getRepairMethod,
  getNightParking, getExpectedKM, getTransmission, getEducation,
} = useCustomerFormatters();

const props = defineProps({
  open: { type: Boolean, default: false },
  customer: { type: Object, default: null },
});

defineEmits(['close']);

const getCustomerName = (customer) => {
  if (!customer) return '';

  const name =
    customer.fullName ||
    customer.full_name ||
    customer.customer_name ||
    customer.name ||
    customer.card_holder ||
    '';

  if (
    name === 'عميل' ||
    name === 'غير متوفر' ||
    name === 'غير معروف' ||
    name.startsWith('هوية:')
  ) {
    return '';
  }

  return name;
};

const repairMethodLabel = (customer) => {
  const method =
    customer?.repairMethod ||
    customer?.repairRegion ||
    customer?.repair_method ||
    customer?.custom_data?.repair_method ||
    customer?.custom_data?.repair_region;

  if (method) return getRepairMethod(method);

  const insuranceType = customer?.insuranceType || customer?.insurance_type;

  return ['tpl', 'thirdParty', 'thirdparty', 'third_party'].includes(insuranceType)
    ? 'غير مطبق على تأمين ضد الغير'
    : '';
};

const getExtra = (key) => {
  const ed = props.customer?.extraData || props.customer?.extra_data;
  if (!ed) return null;
  return ed[key] ?? null;
};
</script>
