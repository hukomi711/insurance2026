<template>
    <div class="bg-white rounded-2xl border overflow-hidden transition-all duration-300"
        :class="[
            compareSelected
                ? 'border-primary shadow-md ring-2 ring-primary/20'
                : plan.badgeType === 'recommended' ? 'border-primary/30 shadow-md ring-1 ring-primary/10' : 'border-slate-200',
            expanded ? 'shadow-md' : 'shadow-sm hover:shadow-md',
            shaking ? 'animate-card-shake' : '',
            pulsing ? 'animate-card-pulse' : '',
        ]">

        <!-- ═══ Top Badge Strip ═══ -->
        <div v-if="plan.badgeType" class="px-4 py-1.5 text-center text-xs font-bold"
            :class="{
                'bg-linear-to-r from-orange-500 to-amber-500 text-white': plan.badgeType === 'recommended',
                'bg-linear-to-r from-emerald-500 to-green-500 text-white': plan.badgeType === 'cheapest',
                'bg-linear-to-r from-blue-500 to-indigo-500 text-white': plan.badgeType === 'betterCoverage',
            }">
            <span v-if="plan.badgeType === 'recommended'">⭐ الأكثر طلباً — موصى به</span>
            <span v-else-if="plan.badgeType === 'cheapest'">💰 أقل سعر متاح</span>
            <span v-else-if="plan.badgeType === 'betterCoverage'">🛡️ تغطية شاملة أفضل</span>
        </div>

        <!-- ═══ Main Content (always visible — clickable to expand) ═══ -->
        <div class="px-3 sm:px-4 py-3 sm:py-4 cursor-pointer" role="button" tabindex="0"
            :aria-expanded="expanded"
            @click="emit( 'toggle-expand' )"
            @keydown.enter.prevent="emit( 'toggle-expand' )"
            @keydown.space.prevent="emit( 'toggle-expand' )">

            <!-- Row 1: Company identity -->
            <div class="flex items-center gap-3">
                <!-- Company Logo -->
                <div class="size-11 sm:size-12 shrink-0 rounded-xl border border-slate-100 overflow-hidden flex items-center justify-center bg-white p-1">
                    <img :src="getCompanyLogo( plan.companyId )" :alt="plan.company.nameAr" loading="lazy"
                        class="max-w-full w-full h-full object-contain" width="48" height="48" />
                </div>
                <!-- Company Name & Type -->
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm sm:text-base font-bold text-slate-900 leading-5 line-clamp-1">
                        {{ plan.company.nameAr }}
                    </h3>
                    <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] sm:text-[11px] font-semibold"
                            :class="plan.subType === 'comprehensive' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
                            {{ plan.typeAr }}
                        </span>
                        <div v-if="plan.company?.rating" class="flex items-center gap-0.5 text-[11px] text-amber-600 font-semibold">
                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                            {{ Number( plan.company.rating ).toFixed( 1 ) }}
                        </div>
                    </div>
                </div>
                <!-- Desktop: chevron -->
                <svg class="size-5 text-slate-300 transition-transform duration-200 shrink-0 hidden lg:block"
                    :class="{ 'rotate-180': expanded }" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>

            <!-- Row 2: Key Features Strip (always visible) -->
            <div class="mt-3 flex items-center gap-2 sm:gap-3 flex-wrap text-[11px] sm:text-xs text-slate-500">
                <span class="inline-flex items-center gap-1">
                    <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" /><path d="M12 12V7" /><path d="M12 12h4.5" />
                    </svg>
                    تفعيل {{ plan.activationTime }}
                </span>
                <span class="text-slate-200 hidden sm:inline">|</span>
                <span class="inline-flex items-center gap-1"
                    :class="plan.repairLocation === 'الوكالة' ? 'text-blue-600' : ''">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7h3a2 2 0 012 2v9a2 2 0 01-2 2H4a1 1 0 01-1-1V7z" />
                        <path d="M8 7h8l4 5v6a2 2 0 01-2 2h-1" />
                        <circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" /><path d="M10 18h4" />
                    </svg>
                    {{ plan.repairLocation }}
                </span>
                <span v-if="plan.coverageLimit" class="text-slate-200 hidden sm:inline">|</span>
                <span v-if="plan.coverageLimit" class="inline-flex items-center gap-1 ltr-nums">
                    <svg class="size-3.5 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 4v5c0 5-3.4 9.74-7 10-3.6-.26-7-5-7-10V7l7-4z" />
                    </svg>
                    حد {{ formatNumber( plan.coverageLimit ) }}
                    <SarIcon className="size-2" />
                </span>
                <span v-if="plan.heroIncluded" class="text-slate-200 hidden sm:inline">|</span>
                <button v-if="plan.heroIncluded"
                    class="inline-flex items-center gap-1 text-primary font-semibold cursor-pointer"
                    @click.stop="emit( 'show-hero' )">
                    تأمينكم هيرو
                </button>
            </div>

            <!-- Row 3: Price + CTA -->
            <div class="mt-3 rounded-xl bg-slate-50 border border-slate-100 p-3 price-highlight">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 sm:gap-3">
                    <!-- Price -->
                    <div class="min-w-0">
                        <!-- Original price (before discount) -->
                        <div v-if="plan.originalPrice && plan.originalPrice !== plan.annualPrice" class="flex items-center gap-1.5 mb-0.5">
                            <span class="text-sm line-through text-slate-400 ltr-nums">{{ formatNumber( plan.originalPrice ) }}</span>
                            <SarIcon className="size-2.5 text-slate-400" />
                            <span class="text-[10px] bg-red-100 text-red-600 font-bold px-1.5 py-0.5 rounded-full">خصم 20%</span>
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span class="text-xl sm:text-2xl font-extrabold ltr-nums text-primary">{{ formatNumber( plan.annualPrice ) }}</span>
                            <SarIcon className="size-3 sm:size-3.5 text-primary" />
                            <span class="text-[11px] sm:text-xs text-muted font-medium">/ سنوياً</span>
                        </div>
                        <p class="text-[11px] sm:text-xs text-slate-400 ltr-nums mt-0.5">
                            ≈ {{ formatNumber( plan.monthlyPrice || Math.ceil( plan.annualPrice / 12 ) ) }}
                            <SarIcon className="size-2 text-slate-400 inline-block align-middle" />
                            / شهرياً
                        </p>
                    </div>
                    <!-- CTA Buttons -->
                    <div class="flex items-center gap-2" @click.stop>
                        <button class="flex-1 sm:flex-none h-10 sm:h-11 px-4 sm:px-6 text-xs sm:text-sm font-bold rounded-xl bg-primary text-white hover:bg-primary-dark transition-colors inline-flex items-center justify-center cursor-pointer gap-1.5 shadow-sm"
                            @click="emit( 'quick-select' )">
                            <svg class="size-4 hidden sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                            اشترِ الآن ⚡
                        </button>
                        <button class="h-10 sm:h-11 w-10 sm:w-11 rounded-xl border border-slate-200 text-slate-400 inline-flex items-center justify-center shrink-0 cursor-pointer hover:bg-slate-100 hover:text-slate-600 transition-colors"
                            @click="emit( 'toggle-expand' )">
                            <svg class="size-5 transition-transform duration-200"
                                :class="{ 'rotate-180': expanded }" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ Expandable Details Section ═══ -->
        <Transition name="slide-down">
        <div v-if="expanded && !compactView" class="border-t border-slate-100">

            <!-- Compare Checkbox (inside expanded only) -->
            <div class="px-3 sm:px-4 pt-3 pb-1">
                <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-slate-500" @click.stop>
                    <input :id="`compare-${plan.id}`" type="checkbox"
                        :checked="compareSelected"
                        :name="`compare-${plan.id}`" :disabled="!canToggleCompare && !compareSelected"
                        class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary"
                        @change="emit( 'update:compareSelected', $event.target.checked )" />
                    أضف للمقارنة
                </label>
            </div>

            <!-- Coverage Details Grid -->
            <div class="px-3 sm:px-4 py-3">
                <p class="text-xs font-bold text-slate-500 mb-2.5">تفاصيل التغطية</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                    <!-- Repair Location -->
                    <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="size-8 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <svg class="size-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7h3a2 2 0 012 2v9a2 2 0 01-2 2H4a1 1 0 01-1-1V7z" />
                                <path d="M8 7h8l4 5v6a2 2 0 01-2 2h-1" />
                                <circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" /><path d="M10 18h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400">مكان الإصلاح</p>
                            <p class="text-xs font-bold text-slate-700">{{ plan.repairLocation }}</p>
                        </div>
                    </div>
                    <!-- Coverage Limit -->
                    <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="size-8 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                            <svg class="size-4 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l7 4v5c0 5-3.4 9.74-7 10-3.6-.26-7-5-7-10V7l7-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400">حد التغطية</p>
                            <p class="text-xs font-bold text-slate-700 ltr-nums">{{ formatNumber( plan.coverageLimit ) }} <SarIcon className="size-2.5 inline text-slate-400" /></p>
                        </div>
                    </div>
                    <!-- Deductible -->
                    <div class="flex items-center gap-2.5 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                        <div class="size-8 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                            <svg class="size-4 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-[10px] text-slate-400">قيمة التحمل</p>
                            <div v-if="DEDUCTIBLE_OPTIONS.length > 1" @click.stop>
                                <AppSelect
                                    :id="`deductible-${plan.id}`"
                                    :modelValue="String( normalizedDeductibleValue( plan ) )"
                                    :options="DEDUCTIBLE_OPTIONS.map( d => ( { value: String( d ), label: formatNumber( d ) } ) )"
                                    variant="standard" class="w-full [&_select]:py-0! [&_select]:min-h-0! [&_select]:text-xs [&_select]:font-bold" :name="`deductible-${plan.id}`"
                                    @update:modelValue="val => emit( 'deductible-change', plan.id, val )" />
                            </div>
                            <p v-else class="text-xs font-bold text-slate-700 ltr-nums">{{ formatNumber( normalizedDeductibleValue( plan ) ) + ' ريال' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Qitaf points -->
            <div v-if="plan.qitafPoints" class="mx-3 sm:mx-4 mb-3 flex items-center gap-2 px-3 py-2 rounded-lg bg-purple-50 border border-purple-100">
                <span class="text-lg">🎁</span>
                <span class="text-xs font-bold text-purple-700">احصل على {{ plan.qitafPoints }} نقطة قطاف</span>
            </div>

            <!-- Benefits Section -->
            <div class="px-3 sm:px-4 pb-3">
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-xs font-bold text-slate-500 mb-2">المزايا والتغطيات</p>
                    <!-- Hero included -->
                    <div v-if="plan.heroIncluded"
                        class="flex items-start gap-2 p-2.5 mb-2 rounded-lg bg-primary/5 border border-primary/10 cursor-pointer"
                        @click.stop="emit( 'show-hero' )">
                        <div>
                            <p class="text-xs font-bold text-primary">تأمينكم هيرو مشمول</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">دعم فوري بعد الحوادث — سحب مجاني، رحلة أوبر، وخدمة تقدير</p>
                        </div>
                    </div>
                    <!-- Benefits list -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                        <div v-for="( benefit, i ) in plan.benefits.slice( 0, benefitsExpanded ? undefined : 4 )"
                            :key="i" class="flex gap-2 items-start">
                            <svg class="size-4 shrink-0 text-emerald-500 mt-0.5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 13l4 4L19 7" />
                            </svg>
                            <p class="text-[11px] sm:text-xs text-slate-700">{{ benefit }}</p>
                        </div>
                    </div>
                    <button v-if="plan.benefits.length > 4"
                        class="mt-2 text-[11px] text-primary font-semibold cursor-pointer hover:underline"
                        @click.stop="emit( 'toggle-benefits' )">
                        {{ benefitsExpanded ? 'عرض أقل' : `+${plan.benefits.length - 4} مزايا إضافية` }}
                    </button>
                </div>
            </div>

            <!-- What's NOT Covered -->
            <div v-if="plan.exclusions?.length" class="px-3 sm:px-4 pb-3">
                <div class="border-t border-slate-100 pt-3">
                    <p class="text-xs font-bold text-slate-500 mb-2">غير مشمول</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="( ex, i ) in plan.exclusions" :key="i"
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-red-50 text-[10px] sm:text-[11px] text-red-600">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            {{ ex }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="px-3 sm:px-4 py-3 border-t border-slate-100 bg-slate-50 flex items-center gap-2 sm:gap-3 justify-between">
                <button class="cursor-pointer text-xs sm:text-sm font-semibold text-primary hover:text-primary-dark inline-flex items-center gap-1.5 transition-colors"
                    @click.stop="emit( 'show-details' )">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H8a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-5.5" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6v6" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14L21 3" />
                    </svg>
                    عرض كل التفاصيل
                </button>
                <button class="h-10 sm:h-11 px-5 sm:px-8 text-xs sm:text-sm font-bold rounded-xl bg-primary text-white hover:bg-primary-dark transition-colors inline-flex items-center justify-center cursor-pointer gap-2 shadow-sm"
                    @click.stop="emit( 'quick-select' )">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    أكمل الشراء
                </button>
            </div>
        </div>
        </Transition>
    </div>
</template>

<script setup>
import SarIcon from '@/components/SarIcon.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import { DEDUCTIBLE_OPTIONS } from '@/data/pricingConstants';
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';

const DEDUCTIBLE_SET = new Set( DEDUCTIBLE_OPTIONS );
const DEFAULT_DEDUCTIBLE = 1000;

function normalizedDeductibleValue( plan )
{
    const deductible = Number( plan?.deductible );
    return DEDUCTIBLE_SET.has( deductible ) ? deductible : DEFAULT_DEDUCTIBLE;
}

const _props = defineProps( {
    plan: { type: Object, required: true },
    expanded: { type: Boolean, default: false },
    compactView: { type: Boolean, default: false },
    benefitsExpanded: { type: Boolean, default: false },
    compareSelected: { type: Boolean, default: false },
    canToggleCompare: { type: Boolean, default: true },
    shaking: { type: Boolean, default: false },
    pulsing: { type: Boolean, default: false },
} );

const emit = defineEmits( [
    'toggle-expand', 'toggle-benefits', 'select',
    'show-details', 'update:compareSelected', 'show-hero',
    'deductible-change', 'quick-select',
] );
</script>
