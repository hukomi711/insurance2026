<template>
    <DialogRoot :open="open" @update:open="$emit('update:open', $event)">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 animate-fade-in" />
            <DialogContent
                class="offer-sheet fixed inset-y-0 left-0 z-50 h-full w-[92vw] sm:w-3/5 flex flex-col gap-4 bg-background border-r border-border shadow-lg p-0 overflow-visible transition ease-in-out animate-slide-in-left"
                dir="rtl" @pointerDownOutside="$emit('update:open', false)">

                <!-- ── Header ── -->
                <div class="flex items-center justify-between px-5 py-4 bg-background border-b border-border shrink-0">
                    <DialogTitle class="typ-t1 font-bold text-foreground">تفاصيل العرض</DialogTitle>
                    <DialogClose
                        class="w-9 h-9 center rounded-xl bg-slate-100 hover:bg-slate-200 transition-colors cursor-pointer">
                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </DialogClose>
                </div>
                <DialogDescription class="sr-only">عرض تفاصيل خطة التأمين والتغطيات</DialogDescription>

                <!-- ── Scrollable Body ── -->
                <div class="flex-1 overflow-y-auto pb-24">
                    <div class="px-5 pb-5 space-y-4">

                        <!-- Badges Row -->
                        <div class="flex flex-wrap gap-2">
                            <span v-if="plan.heroIncluded"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 border border-green-200 rounded-full typ-c1 font-bold text-green-700">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                يشمل تأمينكم هيرو
                            </span>
                            <span v-if="plan.coverageLimit"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-full typ-c1 font-bold text-blue-700">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                حد التغطية لمركبتك {{ formatNumber(plan.coverageLimit) }} ر.س
                            </span>
                        </div>

                        <!-- Company Info Card -->
                        <div class="bg-background rounded-2xl border border-border p-4">
                            <div class="flex items-center gap-4">
                                <img :src="companyLogo" :alt="company.nameAr"
                                    class="w-14 h-14 rounded-xl object-contain bg-slate-50 p-2 border border-border shrink-0" width="56" height="56" />
                                <div class="flex-1 min-w-0">
                                    <p class="typ-t2 text-foreground font-bold">{{ company.nameAr }}</p>
                                    <p class="typ-c1 text-muted">{{ plan.typeAr }}</p>
                                </div>
                                <div class="text-left shrink-0">
                                    <div class="flex items-baseline gap-1">
                                        <span class="typ-h2 font-extrabold text-primary ltr-nums">
                                            {{ formatNumber(currentPrice) }}
                                        </span>
                                        <SarIcon className="size-3.5 text-primary" />
                                    </div>
                                    <p class="typ-c2 text-muted">سنوياً شامل الضريبة</p>
                                </div>
                            </div>
                        </div>

                        <!-- Policy Details Card -->
                        <div class="bg-background rounded-2xl border border-border p-4 space-y-4">
                            <!-- Quick Info Row -->
                            <div class="flex items-center gap-4">
                                <!-- Activation Time -->
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="w-8 h-8 bg-blue-50 rounded-lg center shrink-0">
                                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="typ-c2 text-muted">وقت التفعيل حتى</p>
                                        <p class="typ-s2 text-foreground font-bold">{{ plan.activationTime }}</p>
                                    </div>
                                </div>
                                <!-- Qitaf Points -->
                                <div class="flex items-center gap-2 flex-1">
                                    <div class="w-8 h-8 bg-purple-50 rounded-lg center shrink-0">
                                        <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="typ-c2 text-muted">نقاط قطاف</p>
                                        <p class="typ-s2 text-foreground font-bold ltr-nums">{{ plan.qitafPoints }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-slate-100"></div>

                            <!-- Responsive Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <!-- Repair Location -->
                                <div class="bg-slate-50 rounded-xl p-3 text-center">
                                    <div class="w-8 h-8 bg-white rounded-lg center mx-auto mb-2">
                                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                        </svg>
                                    </div>
                                    <p class="typ-c2 text-muted mb-0.5">الإصلاح في:</p>
                                    <p class="typ-c1 text-foreground font-bold">{{ plan.repairLocation }}</p>
                                </div>

                                <!-- Coverage Limit -->
                                <div class="bg-slate-50 rounded-xl p-3 text-center">
                                    <div class="w-8 h-8 bg-white rounded-lg center mx-auto mb-2">
                                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </div>
                                    <p class="typ-c2 text-muted mb-0.5">حد التغطية لمركبتك:</p>
                                    <p class="typ-c1 text-foreground font-bold ltr-nums">
                                        {{ formatNumber(plan.coverageLimit) }}
                                        <SarIcon className="size-2.5 text-foreground inline-block align-middle" />
                                    </p>
                                </div>

                                <!-- Deductible Selector -->
                                <div class="bg-slate-50 rounded-xl p-3 text-center">
                                    <div class="w-8 h-8 bg-white rounded-lg center mx-auto mb-2">
                                        <svg class="w-4 h-4 text-muted" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <p class="typ-c2 text-muted mb-1">قيمة التحمل:</p>
                                    <select v-if="plan.deductibleOptions && plan.deductibleOptions.length > 1"
                                        id="offer-deductible" v-model="selectedDeductible" name="offer-deductible"
                                        autocomplete="off" aria-label="قيمة التحمل"
                                        class="w-full typ-c1 font-bold text-foreground bg-white border border-slate-200 rounded-lg px-2 py-1.5 text-center appearance-none cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary ltr-nums">
                                        <option v-for="d in plan.deductibleOptions" :key="d" :value="d">
                                            {{ formatNumber(d) }} ر.س
                                        </option>
                                    </select>
                                    <p v-else class="typ-c1 text-foreground font-bold ltr-nums">
                                        {{ formatNumber(plan.deductible) }}
                                        <SarIcon className="size-2.5 text-foreground inline-block align-middle" />
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Benefits Section -->
                        <div class="bg-background rounded-2xl border border-border p-4">
                            <h3 class="typ-t3 text-foreground font-bold mb-3 flex items-center gap-2">
                                <svg class="w-4.5 h-4.5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                التأمين يشمل مجاناً
                            </h3>
                            <ul class="space-y-2.5">
                                <li v-for="(benefit, i) in plan.benefits" :key="i"
                                    class="flex items-start gap-2.5 typ-b3 text-foreground">
                                    <span class="w-5 h-5 bg-green-100 rounded-full center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-green-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    {{ benefit }}
                                </li>
                            </ul>
                        </div>

                        <!-- Additional Coverages Section -->
                        <div v-if="plan.additionalCoverages && plan.additionalCoverages.length > 0"
                            class="bg-background rounded-2xl border border-border p-4">
                            <h3 class="typ-t3 text-foreground font-bold mb-3 flex items-center gap-2">
                                <svg class="w-4.5 h-4.5 text-primary" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                تغطيات إضافية
                            </h3>
                            <div class="space-y-0">
                                <div v-for="(coverage, i) in plan.additionalCoverages" :key="i">
                                    <!-- Dashed separator (not before first item) -->
                                    <div v-if="i > 0" class="border-t border-dashed border-slate-200 my-0"></div>
                                    <label :for="`offer-addon-${i}`"
                                        class="flex items-center gap-3 py-3 cursor-pointer hover:bg-slate-50 rounded-lg transition-colors -mx-1 px-1">
                                        <input :id="`offer-addon-${i}`" v-model="selectedAddons" type="checkbox"
                                            :value="i" :name="`offer-addon-${i}`"
                                            class="w-4 h-4 text-primary rounded border-slate-300 focus:ring-primary shrink-0" />
                                        <span class="flex-1 typ-b3 text-foreground">{{ coverage.name }}</span>
                                        <span class="typ-c1 font-bold text-primary ltr-nums whitespace-nowrap">
                                            {{ formatNumber(coverage.price) }}
                                            <SarIcon className="size-2.5 text-primary inline-block align-middle" />
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Exclusions (collapsed by default) -->
                        <div v-if="plan.exclusions && plan.exclusions.length > 0"
                            class="bg-background rounded-2xl border border-border p-4">
                            <button class="flex items-center justify-between w-full typ-t3 text-foreground font-bold"
                                @click="showExclusions = !showExclusions">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4.5 h-4.5 text-red-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    التأمين لا يشمل
                                </span>
                                <svg class="w-4 h-4 text-muted transition-transform"
                                    :class="{ 'rotate-180': showExclusions }" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <ul v-if="showExclusions" class="mt-3 space-y-2">
                                <li v-for="(ex, i) in plan.exclusions" :key="i"
                                    class="flex items-start gap-2.5 typ-b3 text-muted">
                                    <span class="w-5 h-5 bg-red-100 rounded-full center shrink-0 mt-0.5">
                                        <svg class="w-3 h-3 text-red-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </span>
                                    {{ ex }}
                                </li>
                            </ul>
                        </div>

                    </div>
                </div>

                <!-- ── Fixed Bottom Bar ── -->
                <div
                    class="absolute bottom-0 inset-x-0 bg-background border-t border-border p-4 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
                    <div class="flex items-center gap-4">
                        <!-- Total Price -->
                        <div class="flex-1">
                            <p class="typ-c2 text-muted">الإجمالي شامل الضريبة</p>
                            <div class="flex items-baseline gap-1">
                                <span class="typ-h2 font-extrabold text-primary ltr-nums">
                                    {{ formatNumber(totalPrice) }}
                                </span>
                                <SarIcon className="size-3.5 text-primary" />
                                <span class="typ-c2 text-muted">/ سنوياً</span>
                            </div>
                        </div>
                        <!-- Select Button -->
                        <button class="px-8 py-3 bg-primary text-white typ-t3 font-bold rounded-xl hover:bg-primary-dark transition-colors shrink-0"
                            @click="handleSelect">
                            اختيار
                        </button>
                    </div>
                </div>

            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
} from 'radix-vue';
import { getCompany } from '@/data';
import { formatNumber } from '@/utils/formatters';
import { calculateTotalWithVAT } from '@/utils/pricing';
import { usePricingEngine } from '@/utils/pricingEngine';
import { useInsuranceStore } from '@/store';
import SarIcon from '@/components/SarIcon.vue';

const insuranceStore = useInsuranceStore();
const { recalculateSinglePlan } = usePricingEngine();

const props = defineProps( {
    open: { type: Boolean, default: false },
    plan: { type: Object, required: true },
    companyLogo: { type: String, default: '' },
} );

const emit = defineEmits( [ 'update:open', 'select' ] );

//
const company = computed( () => getCompany( props.plan.companyId ) || { nameAr: '', rating: 0 } );

//
const selectedDeductible = ref( props.plan.deductible );
const selectedAddons = ref( [] );
const showExclusions = ref( false );

// Hydrate store once at setup — never inside a computed getter
insuranceStore.hydrateFromSession();

// Reset state when plan changes
watch( () => props.plan.id, () => {
    selectedDeductible.value = props.plan.deductible;
    selectedAddons.value = [];
    showExclusions.value = false;
} );

// Recalculate price when deductible changes
const currentPrice = computed( () => {
    // If deductible changed from plan default, recalculate via pricing engine
    if ( selectedDeductible.value !== props.plan.deductible ) {
        const recalculated = recalculateSinglePlan(
            props.plan,
            insuranceStore.allFormData,
            Number( selectedDeductible.value )
        );
        return recalculated.annualPrice;
    }
    return props.plan.annualPrice;
} );

const addonsTotal = computed( () => {
    if ( !props.plan.additionalCoverages ) return 0;
    return selectedAddons.value.reduce( ( sum, idx ) => {
        return sum + ( props.plan.additionalCoverages[ idx ]?.price || 0 );
    }, 0 );
} );

const pricing = computed( () => calculateTotalWithVAT( currentPrice.value, addonsTotal.value ) );
const totalPrice = computed( () => pricing.value.total );

//
function handleSelect() {
    emit( 'select', {
        plan: props.plan,
        deductible: selectedDeductible.value,
        addons: selectedAddons.value.map( i => props.plan.additionalCoverages[ i ] ),
        totalPrice: totalPrice.value,
    } );
}
</script>

<style scoped>
.offer-sheet {
    scrollbar-width: thin;
    scrollbar-color: rgba(0, 0, 0, 0.15) transparent;
}

/* Card shadow */
.offer-sheet {
    box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.18) !important;
}

/* Custom select arrow (RTL) */
select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
    background-position: left 0.4rem center;
    background-repeat: no-repeat;
    background-size: 1.2em 1.2em;
    padding-left: 1.5rem;
}
</style>
