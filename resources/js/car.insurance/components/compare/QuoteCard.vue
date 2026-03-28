<template>
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden transition-all"
        :class="{ 'shadow-sm ring-1 ring-primary/10': expanded }">

        <!-- Badges Row -->
        <div v-if="plan.heroIncluded || plan.badgeType" class="px-4 pt-3 flex items-center gap-2 flex-wrap">
            <div v-if="plan.heroIncluded" class="inline-flex items-center rounded-full py-1 px-2.5 typ-c1 font-medium bg-gradient-light-blue-green gap-1.5 cursor-pointer"
                @click="emit( 'show-hero' )">
                <svg class="size-3.5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-primary">يشمل تأمينكم هيرو</span>
            </div>
            <div v-if="plan.badgeType === 'recommended'"
                class="inline-flex items-center rounded-full py-1 px-2.5 typ-c1 font-bold bg-orange-100 text-orange-800 gap-1">
                ⭐ موصى به
            </div>
            <div v-else-if="plan.badgeType === 'cheapest'"
                class="inline-flex items-center rounded-full py-1 px-2.5 typ-c1 font-bold bg-green-100 text-green-800 gap-1">
                💰 الأوفر
            </div>
            <div v-else-if="plan.badgeType === 'betterCoverage'"
                class="inline-flex items-center rounded-full py-1 px-2.5 typ-c1 font-bold bg-blue-100 text-blue-800 gap-1">
                🛡️ تغطية أفضل
            </div>
        </div>

        <!-- Company + Price Row (always visible — clickable to expand) -->
        <div class="flex items-center justify-between px-4 py-3 cursor-pointer" role="button" tabindex="0"
            :aria-expanded="expanded"
            @click="emit( 'toggle-expand' )"
            @keydown.enter.prevent="emit( 'toggle-expand' )"
            @keydown.space.prevent="emit( 'toggle-expand' )">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Compare Checkbox -->
                <label class="flex items-center cursor-pointer shrink-0" @click.stop>
                    <input :id="`compare-${plan.id}`" type="checkbox"
                        :checked="compareSelected"
                        :name="`compare-${plan.id}`" :disabled="!canToggleCompare && !compareSelected"
                        class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary"
                        @change="emit( 'update:compareSelected', $event.target.checked )" />
                    <span class="sr-only">مقارنة {{ plan.company.nameAr }}</span>
                </label>
                <!-- Company Logo -->
                <div
                    class="size-12 shrink-0 rounded-lg border border-slate-200 overflow-hidden flex items-center justify-center bg-white">
                    <img :src="getCompanyLogo( plan.companyId )" :alt="plan.company.nameAr" loading="lazy"
                        class="max-w-full w-full h-full object-contain" width="48" height="48" />
                </div>
                <!-- Company Name & Type -->
                <div class="min-w-0">
                    <h3 class="typ-t3 truncate max-w-[180px] sm:max-w-[280px] lg:max-w-[400px]">
                        {{ plan.company.nameAr }}
                    </h3>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <p class="typ-c1 text-muted">{{ plan.typeAr }}</p>
                        <span v-if="plan.repairLocation"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 typ-c2 font-medium"
                            :class="plan.repairLocation === 'الوكالة' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700'">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 7h3a2 2 0 012 2v9a2 2 0 01-2 2H4a1 1 0 01-1-1V7z" />
                                <path d="M8 7h8l4 5v6a2 2 0 01-2 2h-1" />
                                <circle cx="7" cy="18" r="2" /><circle cx="17" cy="18" r="2" />
                                <path d="M10 18h4" />
                            </svg>
                            {{ plan.repairLocation }}
                        </span>
                    </div>
                </div>
            </div>
            <!-- Price + Chevron -->
            <div class="flex items-center gap-3 shrink-0">
                <div class="flex flex-col items-end gap-0.5">
                    <div v-if="discount.hasDiscount" class="flex items-center gap-1.5">
                        <span class="inline-flex items-center bg-red-100 text-red-700 typ-c2 font-bold px-1.5 py-0.5 rounded-full ltr-nums">وفّر {{ discount.discountPercent }}%</span>
                        <span class="text-slate-400 typ-c1 line-through ltr-nums">{{ formatNumber( discount.originalPrice ) }}</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="typ-t2 lg:typ-h2 font-extrabold ltr-nums text-primary">{{ formatNumber( plan.annualPrice ) }}</span>
                        <SarIcon className="size-3" />
                    </div>
                </div>
                <svg class="size-5 text-slate-400 transition-transform duration-200 shrink-0"
                    :class="{ 'rotate-180': expanded }" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                </svg>
            </div>
        </div>

        <!-- Expandable Details Section -->
        <div v-if="expanded && !compactView" class="border-t border-slate-100">

            <!-- Activation Time + Qitaf -->
            <div class="px-4 py-3 bg-slate-50">
                <div class="flex items-center justify-between flex-wrap gap-2 typ-s2">
                    <div class="flex items-center gap-1.5">
                        <svg class="size-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 12V7" />
                            <path d="M12 12h4.5" />
                        </svg>
                        <span class="text-slate-500">متوسط وقت التفعيل:</span>
                        <span class="font-bold text-foreground">{{ plan.activationTime }}</span>
                    </div>
                    <span v-if="plan.qitafPoints" class="text-purple-700 typ-c1 font-bold">
                        نقاط قطاف: {{ plan.qitafPoints }}
                    </span>
                </div>
            </div>

            <!-- Plan Details Grid -->
            <div class="px-4 py-3">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Repair Location -->
                    <div
                        class="relative flex border border-slate-200 rounded-lg min-h-[3.25rem] px-4 py-2 items-center gap-2 bg-slate-50">
                        <input :id="`repair-${plan.id}`" type="text" disabled :value="plan.repairLocation"
                            :name="`repair-${plan.id}`"
                            class="bg-transparent block w-full typ-c1 text-foreground pt-5 pb-1 appearance-none focus:outline-none disabled:text-slate-600 disabled:cursor-not-allowed peer"
                            placeholder=" " />
                        <label :for="`repair-${plan.id}`"
                            class="absolute typ-c1 text-slate-400 top-1.5 start-4">الإصلاح في:</label>
                    </div>
                    <!-- Coverage Limit -->
                    <div
                        class="relative flex border border-slate-200 rounded-lg min-h-[3.25rem] px-4 py-2 items-center gap-2 bg-slate-50">
                        <input :id="`coverage-${plan.id}`" type="text" disabled
                            :value="formatNumber( plan.coverageLimit )" :name="`coverage-${plan.id}`"
                            class="bg-transparent block w-full typ-c1 text-foreground pt-5 pb-1 appearance-none focus:outline-none disabled:text-slate-600 disabled:cursor-not-allowed peer ltr-nums"
                            placeholder=" " />
                        <SarIcon className="size-4 shrink-0 text-muted self-center" />
                        <label :for="`coverage-${plan.id}`"
                            class="absolute typ-c1 text-slate-400 top-1.5 start-4">حد التغطية لمركبتك:</label>
                    </div>
                    <!-- Deductible -->
                    <AppSelect v-if="plan.deductibleOptions && plan.deductibleOptions.length > 1"
                        :id="`deductible-${plan.id}`"
                        :modelValue="String( plan.deductible )"
                        label="قيمة التحمل"
                        :options="plan.deductibleOptions.map( d => ( { value: String( d ), label: formatNumber( d ) } ) )"
                        variant="standard" class="w-full" :name="`deductible-${plan.id}`"
                        @update:modelValue="val => emit( 'deductible-change', plan.id, val )" />
                    <div v-else
                        class="relative flex border border-slate-200 rounded-lg min-h-[3.25rem] px-4 py-2 items-center gap-2 bg-slate-50">
                        <input :id="`deductible-display-${plan.id}`" type="text" disabled
                            :value="formatNumber( plan.deductible )" :name="`deductible-display-${plan.id}`"
                            class="bg-transparent block w-full typ-c1 text-foreground pt-5 pb-1 appearance-none focus:outline-none disabled:text-slate-600 disabled:cursor-not-allowed peer ltr-nums"
                            placeholder=" " />
                        <label :for="`deductible-display-${plan.id}`"
                            class="absolute typ-c1 text-slate-400 top-1.5 start-4">قيمة التحمل</label>
                    </div>
                </div>
            </div>

            <!-- Product Info / Benefits -->
            <div class="px-4 pb-3">
                <div class="border-t border-slate-100 pt-3 relative">
                    <button v-if="plan.benefits.length > 2" class="absolute end-0 -top-4 rounded-b-lg typ-c1 font-bold text-primary shadow-sm bg-white border border-slate-200 border-t-0 w-[90px] text-center py-0.5 cursor-pointer z-10"
                        @click.stop="emit( 'toggle-benefits' )">
                        <span v-if="benefitsExpanded" class="inline-flex items-center gap-1">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 15l-6-6-6 6" />
                            </svg>
                            إغلاق
                        </span>
                        <span v-else class="inline-flex items-center gap-1">
                            <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M6 9l6 6 6-6" />
                            </svg>
                            معاينة الكل
                        </span>
                    </button>

                    <p class="typ-c1 text-slate-500 mb-2">معلومات المنتج</p>
                    <p v-if="plan.heroIncluded" class="flex items-center typ-c1 text-slate-900 gap-1 mb-1.5">
                        <svg class="size-4 shrink-0 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        تأمينكم هيرو: خدمة مقدمة من تأمينكم :دعم فوري بعد الحوادث، من خدمات السحب إلى أوبر وخدمة تقدير
                    </p>
                    <div v-for="( benefit, i ) in ( benefitsExpanded ? plan.benefits : plan.benefits.slice( 0, 2 ) )"
                        :key="i" class="flex gap-1.5 w-full mb-1">
                        <svg class="size-4 shrink-0 text-green-500 mt-0.5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M8.44 12.34l2.17 2.17 4.89-4.91" />
                        </svg>
                        <p class="typ-c1 text-slate-900">{{ benefit }}</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="px-4 py-3 border-t border-slate-100 flex flex-col-reverse lg:flex-row lg:justify-end gap-2">
                <button class="cursor-pointer whitespace-nowrap transition-colors min-h-11 px-6 typ-s2 font-bold rounded-lg bg-transparent text-primary hover:text-primary-dark w-full lg:w-auto inline-flex items-center justify-center"
                    @click="emit( 'show-details' )">
                    أظهر التفاصيل
                </button>
                <button class="cursor-pointer whitespace-nowrap transition-colors min-h-11 px-6 typ-s2 font-bold rounded-lg bg-primary text-white hover:bg-primary-dark w-full lg:w-auto inline-flex items-center justify-center gap-2"
                    @click="emit( 'select' )">
                    <span>اختيار</span>
                    <svg class="size-5 shrink-0 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 12H5" />
                        <path d="M14 17l5-5" />
                        <path d="M14 7l5 5" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import SarIcon from '@/components/SarIcon.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import { formatNumber, getDiscountInfo } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';

const props = defineProps( {
    plan: { type: Object, required: true },
    expanded: { type: Boolean, default: false },
    compactView: { type: Boolean, default: false },
    benefitsExpanded: { type: Boolean, default: false },
    compareSelected: { type: Boolean, default: false },
    canToggleCompare: { type: Boolean, default: true },
} );

const discount = computed( () => getDiscountInfo( props.plan ) );

const emit = defineEmits( [
    'toggle-expand', 'toggle-benefits', 'select',
    'show-details', 'update:compareSelected', 'show-hero',
    'deductible-change',
] );
</script>
