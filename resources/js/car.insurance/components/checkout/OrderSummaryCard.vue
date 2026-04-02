<template>
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden" dir="rtl">
    <div class="flex items-center gap-3 p-4 sm:p-5">
      <!-- Company Logo -->
      <div v-if="companyLogo" class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-slate-100 bg-white flex items-center justify-center shrink-0 overflow-hidden p-1.5">
        <img :src="companyLogo" :alt="companyName" class="w-full h-full object-contain" />
      </div>
      <!-- Plan Info -->
      <div class="flex-1 min-w-0">
        <h3 class="font-bold text-sm sm:text-base text-foreground truncate">{{ companyName }}</h3>
        <p class="text-xs sm:text-sm text-muted mt-0.5">{{ planName }}</p>
        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
          :class="insuranceType === 'comprehensive' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
          {{ insuranceTypeLabel }}
        </span>
      </div>
    </div>
    <!-- Vehicle info strip -->
    <div v-if="vehicleMake || vehicleYear" class="px-4 sm:px-5 pb-4 sm:pb-5">
      <div class="flex items-center gap-2 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 17h.01M16 17h.01M3 11l1.5-5A2 2 0 016.4 4h11.2a2 2 0 011.9 1.4L21 11M3 11v6a1 1 0 001 1h1a2 2 0 004 0h6a2 2 0 004 0h1a1 1 0 001-1v-6M3 11h18" />
        </svg>
        <span class="text-xs sm:text-sm text-slate-600 font-medium">
          {{ vehicleMake }}<template v-if="vehicleYear"> · {{ vehicleYear }}</template><template v-if="plateNumber"> · {{ plateNumber }}</template>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  companyLogo: { type: String, default: '' },
  companyName: { type: String, default: '' },
  planName: { type: String, default: '' },
  insuranceType: { type: String, default: 'thirdParty' },
  insuranceTypeLabel: { type: String, default: '' },
  vehicleMake: { type: String, default: '' },
  vehicleYear: { type: String, default: '' },
  plateNumber: { type: String, default: '' },
});
</script>
