<template>
  <div class="border-2 border-primary rounded-lg pt-4 overflow-hidden bg-white">
    <!-- Header -->
    <h2 class="px-3 text-foreground text-xl font-bold mb-2">تفاصيل الملخص</h2>

    <!-- مبلغ الوثيقة -->
    <div class="flex flex-wrap justify-between items-center gap-2 px-3 mb-4">
      <span class="text-black text-base font-bold">مبلغ الوثيقة</span>
      <span class="text-black text-base font-bold ms-auto ltr-nums flex items-center gap-1">
        <span>{{ formatDecimal(subtotal) }}</span>
        <SarIcon className="size-3 text-black" />
      </span>
    </div>

    <!-- Addons breakdown (collapsed on mobile) -->
    <template v-if="addons.length > 0">
      <button
        class="sm:hidden flex items-center gap-1.5 text-xs text-primary font-medium cursor-pointer py-1 px-3"
        @click="showDetails = !showDetails"
      >
        {{ showDetails ? 'إخفاء التفاصيل' : 'عرض التفاصيل' }}
        <svg class="w-3.5 h-3.5 transition-transform" :class="showDetails && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <div :class="showDetails ? 'block' : 'hidden sm:block'" class="space-y-1 px-3 mb-2">
        <div
          v-for="addon in addons"
          :key="addon.name"
          class="flex justify-between items-center"
        >
          <span class="text-slate-500 text-sm font-semibold truncate pe-2">{{ addon.name }}</span>
          <span class="text-slate-500 text-sm font-semibold ltr-nums whitespace-nowrap ms-auto flex items-center gap-1">
            +{{ formatDecimal(addon.price) }}
            <SarIcon className="size-2.5 text-slate-500" />
          </span>
        </div>
      </div>
    </template>

    <!-- المجموع (بدون ضريبة) -->
    <div class="flex flex-wrap justify-between items-center gap-2 px-3 mb-1">
      <span class="text-slate-500 text-base font-semibold">المجموع (بدون ضريبة):</span>
      <span class="text-slate-500 text-base font-semibold ms-auto ltr-nums flex items-center gap-1">
        <span>{{ formatDecimal(totalPrice - vatAmount) }}</span>
        <SarIcon className="size-3 text-slate-500" />
      </span>
    </div>

    <!-- ضريبة القيمة المضافة -->
    <div class="flex flex-wrap justify-between items-center gap-2 px-3 mb-2">
      <span class="text-slate-500 text-base font-semibold">ضريبة القيمة المضافة (15%):</span>
      <span class="text-slate-500 text-base font-semibold ms-auto ltr-nums flex items-center gap-1">
        <span>+{{ formatDecimal(vatAmount) }}</span>
        <SarIcon className="size-3 text-slate-500" />
      </span>
    </div>

    <!-- إحسان -->
    <div class="px-3 my-3">
      <div class="p-2 bg-blue-100 border border-blue-200 rounded flex items-start gap-2">
        <img :src="ehsanCharitySrc" alt="إحسان" class="w-8 h-8 object-contain shrink-0" width="32" height="32" />
        <div class="text-blue-900 text-xs leading-relaxed">
          <p class="mb-0">لانك اخترت تأميني .. تأميني معك بالاحسان</p>
          <p class="mb-0">وثيقة تأمين وحدة تجمع بين الأمان والعطاء.</p>
        </div>
      </div>
    </div>

    <!-- Total footer -->
    <div class="relative py-4 px-3">
      <!-- Wave background shape -->
      <div class="absolute top-0 start-[-2px] w-[calc(100%+4px)] h-[120%] z-[1]">
        <svg class="w-full h-full" viewBox="0 0 400 130" preserveAspectRatio="none">
          <path d="M0,25 C80,0 160,35 240,18 C300,6 360,22 400,12 L400,130 L0,130 Z" fill="var(--primary)" />
        </svg>
      </div>
      <div class="relative z-[2] text-white flex flex-wrap justify-between items-start gap-2">
        <span class="font-bold text-xl leading-6">المجموع</span>
        <div class="text-end ms-auto leading-6">
          <span class="font-bold text-xl ltr-nums flex items-center gap-1 justify-end">
            <span>{{ formatDecimal(totalPrice) }}</span>
            <SarIcon className="size-4 text-white" />
          </span>
          <span class="text-xs font-normal mt-1 block opacity-90">يشمل جميع الضرائب والرسوم</span>
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
