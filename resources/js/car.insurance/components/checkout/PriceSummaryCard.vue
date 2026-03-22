<template>
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
    <!-- Header -->
    <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-3 border-b border-slate-100">
      <h2 class="text-base sm:text-lg font-bold text-foreground">ملخص الطلب</h2>
    </div>

    <div class="px-4 sm:px-5 py-3 sm:py-4 space-y-2.5">
      <!-- سعر الوثيقة -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-muted">سعر الوثيقة</span>
        <span class="text-sm ltr-nums flex items-center gap-1">
          <span>{{ formatDecimal(subtotal) }}</span>
          <SarIcon className="size-2.5" />
        </span>
      </div>

      <!-- Addons breakdown (collapsed on mobile) -->
      <template v-if="addons.length > 0">
        <button
          class="sm:hidden flex items-center gap-1.5 text-xs text-primary font-medium cursor-pointer py-1"
          @click="showDetails = !showDetails"
        >
          {{ showDetails ? 'إخفاء تفاصيل السعر' : 'عرض تفاصيل السعر' }}
          <svg class="w-3.5 h-3.5 transition-transform" :class="showDetails && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div :class="showDetails ? 'block' : 'hidden sm:block'" class="space-y-1.5">
          <div
            v-for="addon in addons"
            :key="addon.name"
            class="flex justify-between items-center text-sm"
          >
            <span class="text-muted truncate pe-2">{{ addon.name }}</span>
            <span class="ltr-nums whitespace-nowrap flex items-center gap-1">
              +{{ formatDecimal(addon.price) }}
              <SarIcon className="size-2.5" />
            </span>
          </div>
        </div>
      </template>

      <!-- ضريبة القيمة المضافة -->
      <div class="flex justify-between items-center">
        <span class="text-sm text-muted">ضريبة القيمة المضافة (15%)</span>
        <span class="text-sm ltr-nums flex items-center gap-1">
          <span>{{ formatDecimal(vatAmount) }}</span>
          <SarIcon className="size-2.5" />
        </span>
      </div>

      <!-- Separator + Total -->
      <div class="border-t border-slate-200 pt-2.5">
        <div class="flex justify-between items-center">
          <span class="font-bold text-base sm:text-lg text-foreground">المبلغ الإجمالي</span>
          <span class="text-primary font-bold text-lg sm:text-xl ltr-nums flex items-center gap-1">
            <span>{{ formatDecimal(totalPrice) }}</span>
            <SarIcon className="size-3.5 text-primary" />
          </span>
        </div>
        <p class="typ-c1 text-slate-400 mt-1">شامل الضريبة والرسوم</p>
      </div>

      <!-- إحسان (desktop only) -->
      <div class="hidden sm:block pt-2">
        <div class="bg-blue-50/60 rounded-lg p-2.5 flex items-center gap-2.5 border border-blue-100">
          <img :src="ehsanCharitySrc" alt="إحسان" class="w-7 h-7 object-contain shrink-0" width="28" height="28" />
          <p class="text-blue-800 text-xs font-medium leading-relaxed">
            تأمينكم معك بالإحسان — وثيقة تجمع بين الأمان والعطاء
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import SarIcon from '@/components/SarIcon.vue';
import ehsanCharitySrc from '@/../../resources/images/icons/ehsan-charity.webp';

const showDetails = ref(false);

defineProps({
  subtotal: { type: Number, default: 0 },
  vatAmount: { type: Number, default: 0 },
  totalPrice: { type: Number, default: 0 },
  addons: { type: Array, default: () => [] },
});

function formatDecimal(num) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
}
</script>
