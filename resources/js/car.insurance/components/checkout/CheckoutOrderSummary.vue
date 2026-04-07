<template>
    <div class="space-y-3">

        <!-- ═══ Policy + Company Card (compact) ═══ -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <!-- Company header strip -->
            <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
                <div v-if="companyLogo"
                    class="w-11 h-11 rounded-lg border border-slate-100 bg-white flex items-center justify-center shrink-0 overflow-hidden p-1">
                    <img :src="companyLogo" :alt="companyName" class="w-full h-full object-contain" />
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-bold text-sm text-foreground truncate">{{ companyName }}</h3>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-muted">{{ planName }}</span>
                        <span
                            class="inline-block px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                            :class="insuranceType === 'comprehensive' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
                            {{ insuranceTypeLabel }}
                        </span>
                    </div>
                </div>
                <!-- Collapse toggle -->
                <button type="button"
                    class="p-1.5 -m-1 text-slate-400 hover:text-slate-600 transition-colors cursor-pointer"
                    :aria-expanded="detailsOpen"
                    aria-controls="order-details-panel"
                    @click="detailsOpen = !detailsOpen">
                    <svg class="size-5 transition-transform duration-200" :class="{ 'rotate-180': detailsOpen }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <!-- Collapsible policy + vehicle details -->
            <transition name="slide">
                <div v-show="detailsOpen" id="order-details-panel">
                    <!-- Policy rows -->
                    <div class="px-4 py-3 space-y-2 border-b border-slate-100">
                        <p class="text-xs font-bold text-primary mb-1.5 flex items-center gap-1.5">
                            <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                            بيانات الوثيقة
                        </p>
                        <div v-for="item in policyRows" :key="item.label"
                            class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">{{ item.label }}</span>
                            <span class="font-semibold text-foreground ltr-nums">{{ item.value }}</span>
                        </div>
                    </div>
                    <!-- Vehicle rows -->
                    <div class="px-4 py-3 space-y-2">
                        <p class="text-xs font-bold text-foreground mb-1.5 flex items-center gap-1.5">
                            <svg class="size-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 17h.01M16 17h.01M3 11l1.5-5A2 2 0 016.4 4h11.2a2 2 0 011.9 1.4L21 11M3 11v6a1 1 0 001 1h1a2 2 0 004 0h6a2 2 0 004 0h1a1 1 0 001-1v-6M3 11h18" />
                            </svg>
                            بيانات المركبة
                        </p>
                        <div v-for="item in vehicleRows" :key="item.label"
                            class="flex items-center justify-between text-xs">
                            <span class="text-slate-500">{{ item.label }}</span>
                            <span class="font-semibold text-foreground">{{ item.value }}</span>
                        </div>
                    </div>
                </div>
            </transition>
        </div>

        <!-- ═══ Price Summary Card ═══ -->
        <div class="bg-white rounded-2xl border-2 border-primary/60 overflow-hidden">
            <div class="px-4 py-3 space-y-2.5">

                <!-- Original Price (before discount) -->
                <div v-if="hasDiscount" class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">سعر الوثيقة الأساسي</span>
                    <span class="font-semibold text-slate-400 line-through ltr-nums inline-flex items-center gap-1">
                        {{ formatDecimal( originalPrice ) }}
                        <SarIcon className="size-2.5 text-slate-300" />
                    </span>
                </div>

                <!-- Safe Driving Discount (10%) -->
                <div v-if="hasDiscount"
                    class="flex items-center justify-between text-xs bg-emerald-50 border border-emerald-100 rounded-lg px-2.5 py-2">
                    <span class="text-emerald-700 font-semibold flex items-center gap-1">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                        خصم القيادة الآمنة (10%)
                    </span>
                    <span class="font-bold text-emerald-700 ltr-nums inline-flex items-center gap-1">
                        -{{ formatDecimal( safeDrivingDiscount ) }}
                        <SarIcon className="size-2.5 text-emerald-600" />
                    </span>
                </div>

                <!-- تأميني Discount (20%) -->
                <div v-if="hasDiscount"
                    class="flex items-center justify-between text-xs bg-emerald-50 border border-emerald-100 rounded-lg px-2.5 py-2">
                    <span class="text-emerald-700 font-semibold flex items-center gap-1">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                        خصم تأميني (20%)
                    </span>
                    <span class="font-bold text-emerald-700 ltr-nums inline-flex items-center gap-1">
                        -{{ formatDecimal( taminiDiscount ) }}
                        <SarIcon className="size-2.5 text-emerald-600" />
                    </span>
                </div>

                <!-- Discounted price -->
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">سعر الوثيقة{{ hasDiscount ? ' بعد الخصم' : '' }}</span>
                    <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                        {{ formatDecimal( annualPrice ) }}
                        <SarIcon className="size-2.5 text-slate-400" />
                    </span>
                </div>

                <!-- Add-ons -->
                <template v-if="addons.length > 0">
                    <div v-for="( addon, idx ) in addons" :key="idx"
                        class="flex items-center justify-between text-xs bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-2">
                        <span class="text-blue-700 font-semibold flex items-center gap-1">
                            <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                            {{ addon.name }}
                        </span>
                        <span class="font-bold text-blue-700 ltr-nums inline-flex items-center gap-1 shrink-0">
                            +{{ formatDecimal( addon.price ) }}
                            <SarIcon className="size-2.5 text-blue-600" />
                        </span>
                    </div>
                </template>

                <!-- Subtotal before VAT -->
                <div v-if="addons.length > 0" class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">المجموع قبل الضريبة</span>
                    <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                        {{ formatDecimal( subtotalBeforeVAT ) }}
                        <SarIcon className="size-2.5 text-slate-400" />
                    </span>
                </div>

                <!-- VAT -->
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-500">ضريبة القيمة المضافة (15%)</span>
                    <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                        +{{ formatDecimal( vatAmount ) }}
                        <SarIcon className="size-2.5 text-slate-400" />
                    </span>
                </div>

                <!-- Divider -->
                <hr class="border-slate-200">

                <!-- Total -->
                <div class="flex items-center justify-between">
                    <span class="text-sm font-bold text-foreground">المبلغ الإجمالي</span>
                    <span class="text-lg font-extrabold text-primary ltr-nums inline-flex items-center gap-1">
                        {{ formatDecimal( totalPrice ) }}
                        <SarIcon className="size-3.5 text-primary" />
                    </span>
                </div>

                <!-- Savings badge -->
                <div v-if="hasDiscount" class="text-center">
                    <span
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-bold">
                        <svg class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        وفّرت {{ formatDecimal( discountAmount ) }} ريال مع تأميني!
                    </span>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useInsuranceStore } from '@/store/modules/insurance';
import { getPlanWithCompany } from '@/data';
import { getCompanyLogo } from '@/utils/companyLogos';
import { formatNumber } from '@/utils/formatters';
import SarIcon from '@/components/SarIcon.vue';

const props = defineProps( {
    selectedPlanData: { type: Object, required: true },
} );

const insuranceStore = useInsuranceStore();
const detailsOpen = ref( false );

// ── Plan & Company ──
const plan = computed( () => props.selectedPlanData?.id ? getPlanWithCompany( props.selectedPlanData.id ) : null );
const companyLogo = computed( () => plan.value ? getCompanyLogo( plan.value.companyId ) : '' );
const companyName = computed( () => plan.value?.company?.nameAr || props.selectedPlanData?.companyName || '' );
const planName = computed( () => plan.value?.name || '' );
const insuranceType = computed( () => plan.value?.type || props.selectedPlanData?.type || 'thirdParty' );
const insuranceTypeLabel = computed( () =>
    insuranceType.value === 'comprehensive' ? 'تأمين شامل' : 'تأمين ضد الغير' );

// ── Pricing (prefer pre-calculated from OrderReviewPage) ──
const annualPrice = computed( () => props.selectedPlanData?.annualPrice || plan.value?.annualPrice || 0 );
const originalPrice = computed( () => props.selectedPlanData?.originalPrice || annualPrice.value );
const hasDiscount = computed( () => originalPrice.value > annualPrice.value );
const discountAmount = computed( () => Math.round( ( originalPrice.value - annualPrice.value ) * 100 ) / 100 );
const safeDrivingDiscount = computed( () => Math.round( originalPrice.value * 0.10 * 100 ) / 100 );
const taminiDiscount = computed( () => Math.round( ( discountAmount.value - safeDrivingDiscount.value ) * 100 ) / 100 );
const addons = computed( () => props.selectedPlanData?.addons || [] );
const addonsTotal = computed( () => addons.value.reduce( ( sum, a ) => sum + Number( a?.price || 0 ), 0 ) );
const subtotalBeforeVAT = computed( () => {
    if ( props.selectedPlanData?.subtotalBeforeVAT != null ) return props.selectedPlanData.subtotalBeforeVAT;
    return annualPrice.value + addonsTotal.value;
} );
const vatAmount = computed( () => {
    if ( props.selectedPlanData?.vatAmount != null ) return props.selectedPlanData.vatAmount;
    return Math.round( subtotalBeforeVAT.value * 0.15 * 100 ) / 100;
} );
const totalPrice = computed( () => {
    if ( props.selectedPlanData?.totalPrice != null ) return props.selectedPlanData.totalPrice;
    return Math.round( ( subtotalBeforeVAT.value + vatAmount.value ) * 100 ) / 100;
} );

// ── Policy rows ──
const policyRows = computed( () => {
    const p = insuranceStore.policy;
    const rows = [
        { label: 'نوع التأمين', value: insuranceTypeLabel.value },
        { label: 'شركة التأمين', value: companyName.value },
    ];
    if ( plan.value?.repairLocation ) {
        rows.push( { label: 'مكان الإصلاح', value: plan.value.repairLocation } );
    }
    if ( plan.value?.deductible !== undefined ) {
        rows.push( { label: 'قيمة التحمل', value: plan.value.deductible === 0 ? 'بدون تحمل' : formatNumber( plan.value.deductible ) + ' ريال' } );
    }
    if ( plan.value?.coverageLimit ) {
        rows.push( { label: 'حد التغطية', value: formatNumber( plan.value.coverageLimit ) + ' ريال' } );
    }
    if ( p.policyStartDate ) {
        rows.push( { label: 'تاريخ بدء الوثيقة', value: p.policyStartDate } );
    }
    return rows;
} );

// ── Vehicle rows ──
const vehicleRows = computed( () => {
    const v = insuranceStore.vehicle;
    const rows = [];
    if ( v.makeName || v.make ) rows.push( { label: 'الشركة المصنعة', value: v.makeName || v.make } );
    if ( v.year ) rows.push( { label: 'سنة الصنع', value: v.year } );
    if ( v.plateNumber ) rows.push( { label: 'رقم اللوحة', value: v.plateNumber } );
    if ( v.sequenceNumber ) rows.push( { label: 'الرقم التسلسلي', value: v.sequenceNumber } );
    if ( v.estimatedValue ) rows.push( { label: 'القيمة التقديرية', value: formatNumber( v.estimatedValue ) + ' ريال' } );
    if ( v.purposeOfUse ) {
        const purposes = { personal: 'شخصي', commercial: 'تجاري', transport: 'نقل' };
        rows.push( { label: 'غرض الاستخدام', value: purposes[ v.purposeOfUse ] || v.purposeOfUse } );
    }
    return rows;
} );

function formatDecimal( num ) {
    return new Intl.NumberFormat( 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    } ).format( num );
}
</script>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}
.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
}
.slide-enter-to,
.slide-leave-from {
    max-height: 500px;
    opacity: 1;
}
.ltr-nums {
    font-variant-numeric: tabular-nums;
    direction: ltr;
    unicode-bidi: embed;
}
</style>
