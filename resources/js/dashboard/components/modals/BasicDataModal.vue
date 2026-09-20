<template>
  <ModalShell
    :open="open"
    title="البيانات الأساسية"
    :subtitle="customer?.ip"
    emoji="🚗"
    size="md"
    accent="#facc15"
    dir="rtl"
    @close="$emit('close')"
  >
    <template #header-bottom>
      <AdminTabs
        v-model="activeTab"
        :tabs="tabs"
        dir="rtl"
        aria-label="أنواع البيانات الأساسية"
      />
    </template>

    <!-- Tab 1: تأمين / تجديد -->
    <template v-if="activeTab === 'insurance'">
      <SectionCard title="الغرض من التأمين" color="blue">
        <InfoGrid :cols="2">
          <DataField label="غرض التأمين" :value="getInsurancePurpose(customer?.insurancePurpose)" />
          <DataField label="نوع تسجيل المركبة" :value="getRegistrationType(customer?.registrationType)" />
        </InfoGrid>
      </SectionCard>
      <SectionCard title="بيانات الهوية" color="green">
        <InfoGrid :cols="2">
          <DataField label="رقم الهوية / الإقامة" :value="customer?.nationalId" mono :bold="true" color="text-yellow-400" />
          <DataField label="الرقم التسلسلي" :value="customer?.sequenceNumber" mono />
          <DataField label="الجنسية" :value="getNationality(customer)" />
        </InfoGrid>
      </SectionCard>
    </template>

    <!-- Tab 2: سيارة أشتريها (نقل ملكية) -->
    <template v-if="activeTab === 'ownership'">
      <SectionCard title="بيانات المالك" color="orange">
        <InfoGrid :cols="2">
          <DataField label="رقم الهوية" :value="customer?.nationalId" mono :bold="true" color="text-yellow-400" />
          <DataField label="تاريخ الميلاد (هجري)">
            <template #default>
              <span v-if="customer?.birthMonth || customer?.birthYear" class="text-white">
                {{ customer?.birthMonth || '—' }} / {{ customer?.birthYear || '—' }}
              </span>
              <span v-else class="text-gray-500">—</span>
            </template>
          </DataField>
        </InfoGrid>
      </SectionCard>
      <SectionCard title="بيانات المركبة" color="purple">
        <InfoGrid :cols="2">
          <DataField label="الرقم التسلسلي" :value="customer?.sequenceNumber" mono />
          <DataField label="سنة الصنع" :value="customer?.manufacturingYear" mono />
        </InfoGrid>
      </SectionCard>
      <SectionCard v-if="customer?.newOwnerNationalId" title="بيانات المالك الجديد" color="red">
        <InfoGrid :cols="2">
          <DataField label="رقم الهوية (المالك الجديد)" :value="customer?.newOwnerNationalId" mono :bold="true" color="text-yellow-400" />
          <DataField label="تاريخ الميلاد (المالك الجديد)">
            <template #default>
              <span class="text-white">
                {{ customer?.newOwnerBirthMonth || '—' }} / {{ customer?.newOwnerBirthYear || '—' }}
              </span>
            </template>
          </DataField>
        </InfoGrid>
      </SectionCard>
    </template>

    <!-- Tab 3: سيارة مستوردة -->
    <template v-if="activeTab === 'imported'">
      <SectionCard title="بيانات الهوية" color="green">
        <InfoGrid :cols="2">
          <DataField label="رقم الهوية" :value="customer?.nationalId" mono :bold="true" color="text-yellow-400" />
          <DataField label="تاريخ الميلاد (هجري)">
            <template #default>
              <span v-if="customer?.birthMonth || customer?.birthYear" class="text-white">
                {{ customer?.birthMonth || '—' }} / {{ customer?.birthYear || '—' }}
              </span>
              <span v-else class="text-gray-500">—</span>
            </template>
          </DataField>
        </InfoGrid>
      </SectionCard>
      <SectionCard title="البطاقة الجمركية" color="cyan">
        <InfoGrid :cols="2">
          <DataField label="رقم البطاقة الجمركية" :value="customer?.customsCard" mono />
          <DataField label="سنة الصنع" :value="customer?.importedManufacturingYear || customer?.manufacturingYear" mono />
        </InfoGrid>
      </SectionCard>
    </template>

    <!-- Tab 4: موجز -->
    <template v-if="activeTab === 'mojaz'">
      <SectionCard title="بيانات المركبة" color="indigo">
        <InfoGrid :cols="2">
          <DataField label="الرقم التسلسلي" :value="customer?.sequenceNumber" mono />
          <DataField label="رقم الجوال" :value="customer?.phoneNumber || customer?.phone_number" mono value-dir="ltr" />
          <DataField label="سنة الصنع" :value="customer?.manufacturingYear" mono />
          <DataField label="الشركة المصنعة" :value="customer?.vehicleMake" />
          <DataField label="الموديل" :value="customer?.vehicleModel" />
          <DataField label="رقم اللوحة" :value="customer?.plateNumber" mono />
          <DataField class="sm:col-span-2" label="رقم الهيكل (VIN)" mono value-dir="ltr">
            <template #default>
              <span class="font-mono uppercase text-white" dir="ltr">{{ customer?.vin || '—' }}</span>
            </template>
          </DataField>
        </InfoGrid>
      </SectionCard>
    </template>

    <template #footer>
      <AdminButton variant="secondary" label="إغلاق" class="w-full" @click="$emit('close')" />
    </template>
  </ModalShell>
</template>

<script setup>
import { ModalShell, DataField, AdminButton, SectionCard, InfoGrid, AdminTabs } from '../ui';
import { useCustomerFormatters } from '../../utils/customerFormatters';

const { getInsurancePurpose, getRegistrationType } = useCustomerFormatters();

const getNationality = ( customer ) =>
{
  const code = customer?.nationality || customer?.custom_data?.nationality;

  if ( !code ) return null;

  try {
    return new Intl.DisplayNames( [ 'ar' ], { type: 'region' } ).of( code ) || code;
  } catch {
    return code;
  }
};

const tabs = [
  { id: 'insurance', label: 'تأمين / تجديد', icon: '🛡️' },
  { id: 'ownership', label: 'سيارة أشتريها', icon: '🔄' },
  { id: 'imported', label: 'سيارة مستوردة', icon: '🚢' },
  { id: 'mojaz', label: 'موجز', icon: '📋' },
];

const activeTab = defineModel( 'activeTab', { type: String, default: 'insurance' } );

defineProps({
  open: { type: Boolean, default: false },
  customer: { type: Object, default: null },
});

defineEmits(['close']);
</script>
