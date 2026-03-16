<template>
  <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
    <!-- Header -->
    <div class="bg-gradient-to-b from-[#e8f4fd] to-white px-4 sm:px-5 pt-4 sm:pt-5 pb-3">
      <h2 class="text-lg sm:text-xl text-primary font-bold border-b-2 border-primary pb-2">تفاصيل الملخص</h2>
    </div>

    <div class="px-4 sm:px-5 pb-4 sm:pb-5">
      <!-- سعر الوثيقة -->
      <div class="flex justify-between items-center mb-1">
        <span class="text-sm sm:text-base font-bold text-foreground">سعر الوثيقة</span>
        <span class="font-medium text-sm sm:text-base ltr-nums flex items-center gap-1">
          <span>{{ formatDecimal(subtotal) }}</span>
          <SarIcon className="size-3" />
        </span>
      </div>

      <!-- شامل كل ما بعد -->
      <div class="my-2 sm:my-3">
        <span class="font-medium text-sm sm:text-base text-foreground">شامل كل ما بعد</span>
      </div>

      <!-- المجموع الجزئي -->
      <div class="flex justify-between items-center pt-2">
        <span class="font-medium text-sm sm:text-base text-foreground">المجموع الجزئي</span>
        <span class="font-medium text-sm sm:text-base ltr-nums flex items-center gap-1">
          <span>{{ formatDecimal(subtotal) }}</span>
          <SarIcon className="size-3" />
        </span>
      </div>

      <!-- ضريبة القيمة المضافة -->
      <div class="flex justify-between items-center pb-3 border-b border-slate-200">
        <span class="font-medium text-sm sm:text-base text-foreground">ضريبة القيمة المضافة (15.00%)</span>
        <span class="font-medium text-sm sm:text-base ltr-nums flex items-center gap-1">
          <span>{{ formatDecimal(vatAmount) }}</span>
          <SarIcon className="size-3" />
        </span>
      </div>

      <!-- Addons breakdown -->
      <div v-if="addons.length > 0" class="py-2 border-b border-slate-200 space-y-1.5">
        <div
          v-for="addon in addons"
          :key="addon.name"
          class="flex justify-between items-center text-sm text-secondary"
        >
          <span class="truncate pe-2">{{ addon.name }}</span>
          <span class="font-medium ltr-nums whitespace-nowrap flex items-center gap-1">
            +{{ formatDecimal(addon.price) }}
            <SarIcon className="size-2.5" />
          </span>
        </div>
      </div>

      <!-- العروض والخصومات -->
      <div class="my-2 sm:my-3">
        <div
          class="relative bg-gradient-to-l from-sky-100 to-sky-50 rounded-lg px-3 sm:px-4 py-2.5 sm:py-3 flex items-center gap-2 overflow-hidden"
        >
          <div class="flex items-center gap-2 flex-1">
            <svg
              class="w-6 h-6 sm:w-8 sm:h-8 text-sky-500 shrink-0"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="1.5"
                d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"
              />
            </svg>
            <span class="text-xs sm:text-sm font-bold text-sky-700">نقدم لك باقة مجانية من العروض والخصومات</span>
          </div>
        </div>
      </div>

      <!-- إحسان -->
      <div class="mb-3 sm:mb-4">
        <div class="bg-blue-50 rounded-lg p-2.5 sm:p-3 flex items-center gap-2.5 sm:gap-3 border border-blue-200">
          <div class="w-12 h-12 sm:w-14 sm:h-14 shrink-0 flex items-center justify-center">
            <img :src="ehsanCharitySrc" alt="إحسان" class="w-8 h-8 sm:w-10 sm:h-10 object-contain" width="40" height="40" />
          </div>
          <div class="flex-1">
            <p class="text-blue-900 text-xs sm:text-sm font-medium mb-0.5">
              لأنك اخترت تأمينكم .. تأمينكم معك بالإحسان
            </p>
            <p class="text-blue-900 text-xs sm:text-sm font-medium mb-0">
              وثيقة تأمين واحدة تجمع بين الأمان والعطاء.
            </p>
          </div>
        </div>
      </div>

      <!-- المبلغ الإجمالي -->
      <div class="flex justify-between items-start mb-2">
        <span class="font-semibold text-lg sm:text-xl text-foreground">المبلغ الإجمالي</span>
        <div class="flex flex-col items-end">
          <span class="text-primary font-bold text-xl sm:text-2xl ltr-nums flex items-center gap-1">
            <span>{{ formatDecimal(totalPrice) }}</span>
            <SarIcon className="size-4 text-primary" />
          </span>
        </div>
      </div>

      <!-- عمولة الوسيط -->
      <div class="mb-3">
        <span class="font-medium text-xs text-muted">
          شامل جميع الرسوم والضرائب و 2.00% عمولة الوسيط
        </span>
      </div>

      <!-- Monthly option -->
      <p v-if="monthlyTotal" class="typ-c1 text-muted text-center mt-1">
        أو {{ formatDecimal(monthlyTotal) }} ر.س / شهرياً
      </p>
    </div>
  </div>
</template>

<script setup>
import SarIcon from '@/components/SarIcon.vue';
import ehsanCharitySrc from '@/../../resources/images/icons/ehsan-charity.webp';

defineProps({
  subtotal: { type: Number, default: 0 },
  vatAmount: { type: Number, default: 0 },
  totalPrice: { type: Number, default: 0 },
  monthlyTotal: { type: Number, default: 0 },
  addons: { type: Array, default: () => [] },
});

function formatDecimal(num) {
  return new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(num);
}
</script>
