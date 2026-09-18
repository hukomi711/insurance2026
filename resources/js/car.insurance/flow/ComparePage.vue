<template>
    <!-- ═══ Loading State ═══ -->
    <QuotesLoading v-if="isLoadingQuotes" :progress="loadingProgress" @back="router.push({ name: 'vehicleDetails' })" />

    <!-- ═══ Error State ═══ -->
    <div v-else-if="quotesError" class="min-h-screen bg-slate-50 center" dir="rtl">
        <AppError :title="quoteErrorTitle" :message="quoteErrorMessage" :details="quoteErrorDetails"
            :retry-label="quoteErrorRetryLabel" @retry="handleQuotesErrorAction" />
    </div>

    <!-- ═══ Quotes Loaded ═══ -->
    <div v-else class="min-h-screen bg-slate-50" dir="rtl" role="main">

        <!-- ── Top Header: Back + Timer ── -->
        <div class="bg-white border-b border-slate-200">
            <div class="box py-3 flex items-center justify-between">
                <router-link :to="{ name: 'vehicleDetails' }"
                    class="flex items-center gap-1.5 text-primary typ-s2 hover:text-primary-dark transition-colors">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    الرجوع
                </router-link>
                <div v-if="countdown.total > 0" class="flex items-center gap-2 typ-s2 text-muted">
                    <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>العروض تنتهي بعد</span>
                    <span class="font-bold text-primary ltr-nums">{{ countdown.formatted }}</span>
                </div>
            </div>
        </div>

        <!-- ── Main 3-Column Grid ── -->
        <div class="box py-4 sm:py-6">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

                <!-- ═══ Quotes Area (2 cols on xl) ═══ -->
                <div class="xl:col-span-2 min-w-0">

                    <!-- Selection Error Alert -->
                    <transition name="fade">
                        <div v-if="selectionError"
                            class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4" role="alert">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            <p class="flex-1 text-sm font-bold text-red-700">{{ selectionError }}</p>
                            <button class="text-red-400 hover:text-red-600 transition-colors cursor-pointer"
                                @click="selectionError = ''">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </transition>

                    <!-- Category Tabs (primary navigation — shown first on mobile) -->
                    <TabsRoot v-model="activeTab" class="mb-4">
                        <TabsList
                            class="flex w-full items-center p-1 overflow-x-auto no-scrollbar bg-slate-100 rounded-xl gap-1">
                            <TabsTrigger v-for="tab in categoryTabs" :key="tab.value" :value="tab.value"
                                class="inline-flex items-center justify-center whitespace-nowrap transition-all focus-visible:outline-none typ-s2 font-bold text-slate-500 py-2 sm:py-2.5 px-3 sm:px-4 flex-col flex-1 min-w-0 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:rounded-lg data-[state=active]:text-primary">
                                <span class="flex flex-col items-center gap-0.5 w-full">
                                    <span class="truncate">{{ tab.label }}</span>
                                    <span class="typ-c1 text-slate-400! ltr-nums">{{ tab.priceLabel }}</span>
                                </span>
                            </TabsTrigger>
                        </TabsList>
                    </TabsRoot>

                    <!-- NCD Discount Banner -->
                    <div class="flex gap-2 items-center justify-between cursor-pointer rounded-lg p-4 mb-4 hover:opacity-80 transition-opacity bg-green-600 text-white">
                        <div class="flex gap-2 items-center">
                            <img :src="IMAGES.ncdBannerImg" alt="ncd-discount-clap" class="max-w-full w-5 h-5" loading="lazy" width="20" height="20" />
                            <span class="text-sm font-medium">مبروك عليك خصم يبدأ من 10% نتيجة قيادتك الآمنة + خصم تأميني 20%</span>
                        </div>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <!-- Coverage Limit & Update Button -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 mb-4">
                        <div :class="showCoverageLimit ? 'sm:col-span-9' : 'hidden'">
                            <div v-if="showCoverageLimit" class="group relative flex border border-slate-300 rounded-lg min-h-13 sm:min-h-14 px-3 sm:px-4 py-2 items-center gap-1.5 sm:gap-2 w-full
                                        focus-within:border-primary transition">
                                <input id="coverageLimit" v-model.number="quoteOptions.coverageLimit" type="number" autocomplete="off"
                                    name="coverageLimit"
                                    class="bg-transparent block w-full text-sm text-foreground pt-5 pb-1 appearance-none focus:outline-none peer ltr-nums"
                                    placeholder=" " />
                                <SarIcon className="size-4 sm:size-5 shrink-0 text-muted self-center" />
                                <label for="coverageLimit"
                                    class="absolute text-sm text-slate-500 transition-all top-4 inset-s-3 sm:inset-s-4
                                              peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-primary
                                              peer-[:not(:placeholder-shown)]:top-1.5 peer-[:not(:placeholder-shown)]:text-xs">
                                    حد التغطية لمركبتك
                                </label>
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <button :disabled="isUpdatingQuotes" class="w-full min-h-13 sm:min-h-14 px-4 sm:px-6 typ-t3 font-bold rounded-lg bg-primary text-white
                                       hover:bg-primary-dark active:bg-primary-darker disabled:bg-slate-400
                                       disabled:cursor-not-allowed transition-colors inline-flex items-center justify-center cursor-pointer"
                                @click="updateQuoteOptions">
                                <svg v-if="isUpdatingQuotes" class="animate-spin size-5" viewBox="0 0 24 24"
                                    fill="none" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span v-else>تحديث</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mobile Results Header -->
                    <div class="xl:hidden flex items-center justify-between mb-3">
                        <p class="typ-t2 text-foreground">
                            <span class="text-primary font-extrabold ltr-nums">{{ sortedPlans.length }}</span>
                            عرض متاح
                        </p>
                        <div class="flex items-center gap-2">
                            <span class="typ-c1 text-muted bg-slate-100 rounded-full px-2.5 py-1">
                                {{ SORT_OPTIONS.find( o => o.value === sortBy )?.label || 'السعر: الأقل' }}
                            </span>
                            <button class="flex items-center gap-1.5 px-3 py-1.5 border border-slate-200 bg-white typ-s2 text-foreground font-bold rounded-full shadow-sm"
                                @click="showMobileFilters = true">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                تصفية
                            </button>
                        </div>
                    </div>

                    <!-- Offers Count + Compact Toggle (desktop only) -->
                    <div class="hidden xl:flex items-center justify-between mb-3 sm:mb-4">
                        <p class="typ-t2 sm:typ-t1 text-foreground">
                            <span class="text-primary font-extrabold ltr-nums">{{ sortedPlans.length }}</span>
                            عرض متاح
                        </p>
                        <label for="compact-toggle"
                            class="hidden md:flex items-center gap-2 cursor-pointer typ-s2 text-muted">
                            <span>عرض مختصر</span>
                            <SwitchRoot id="compact-toggle" v-model:checked="compactView" name="compactView"
                                class="w-10 h-5.5 bg-slate-300 rounded-full relative data-[state=checked]:bg-primary transition-colors">
                                <SwitchThumb
                                    class="block w-4.5 h-4.5 bg-white rounded-full shadow transition-transform translate-x-0.5 data-[state=checked]:translate-x-5" />
                            </SwitchRoot>
                        </label>
                    </div>

                    <!-- ═══ Quote Cards ═══ -->
                    <div class="space-y-3">
                        <QuoteCard v-for="plan in displayedPlans" :key="plan.id" :plan="plan"
                            :expanded="expandedCards.includes(plan.id)" :compact-view="compactView"
                            :benefits-expanded="expandedBenefits.includes(plan.id)"
                            :compare-selected="selectedPlans.includes(plan.id)"
                            :can-toggle-compare="selectedPlans.length < 3 || selectedPlans.includes(plan.id)"
                            :shaking="shakingCards.includes(plan.id)"
                            :pulsing="pulsingCards.includes(plan.id)"
                            @toggle-expand="toggleCardExpand(plan.id)"
                            @toggle-benefits="toggleExpandedBenefits(plan.id)"
                            @select="selectPlan(plan)" @show-details="openOfferSheet(plan, 'details_open')"
                            @quick-select="selectPlan(plan, 'card_quick')"
                            @show-hero="showHeroModal = true"
                            @deductible-change="val => onPlanDeductibleChange(plan.id, val)"
                            @update:compare-selected="checked => onCompareToggle(plan.id, checked)" />

                        <!-- Show More Button -->
                        <button v-if="sortedPlans.length > 5 && !showAllPlans"
                            class="w-full py-3 text-primary font-bold text-sm rounded-xl border border-primary/20 hover:bg-primary/5 transition-colors"
                            @click="showAllPlans = true">
                            عرض {{ sortedPlans.length - 5 }} عروض إضافية
                        </button>

                        <!-- No Results State -->
                        <div v-if="sortedPlans.length === 0"
                            class="text-center py-16 bg-white rounded-xl border border-slate-200">
                            <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="typ-h3 text-foreground mb-2">لا توجد نتائج</h3>
                            <p class="typ-b2 text-muted mb-4">حاول تغيير معايير البحث أو الفلاتر</p>
                            <button class="text-primary typ-s2 font-bold hover:underline" @click="resetFilters">إعادة
                                تعيين الفلاتر</button>
                        </div>
                    </div>

                    <!-- Back Button -->
                    <div class="mt-8 flex justify-center">
                        <router-link :to="{ name: 'vehicleDetails' }"
                            class="flex items-center gap-2 text-primary typ-s2 font-bold hover:text-primary-dark transition-colors">
                            <svg class="size-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                            الرجوع
                        </router-link>
                    </div>
                </div>

                <!-- ═══ Sidebar (desktop only) ═══ -->
                <CompareSidebar :vehicle-info="vehicleInfo" :sort-options="sortOptions" :sort-by="sortBy"
                    :filters="filters" :companies="companies" @update:sort-by="sortBy = $event"
                    @update:filters="Object.assign(filters, $event)"
                    @reset-filters="resetFilters" @show-hero="showHeroModal = true" />
            </div>
        </div>

        <!-- Compare Modal -->
        <CompareModal v-model:open="showCompareModal" :compared-plans="comparedPlans" />

        <!-- Mobile Filters Sheet -->
        <MobileFiltersSheet v-model:open="showMobileFilters" :sort-options="sortOptions" :companies="companies"
            :filters="filters" :sort-by="sortBy" @update:sort-by="sortBy = $event"
            @apply-filters="Object.assign(filters, $event)" @reset-filters="resetFilters" />

        <!-- Bottom padding for fixed bar on mobile -->
        <div class="h-16 xl:hidden"></div>

        <!-- Offer Details Sheet -->
        <OfferDetailsSheet v-if="offerSheetPlan" :open="showOfferSheet" :plan="offerSheetPlan"
            :companyLogo="getCompanyLogo(offerSheetPlan.companyId)" @update:open="showOfferSheet = $event"
            @select="handleOfferSelect" />

        <!-- Hero Info Modal -->
        <TaminkomHeroModal v-model:open="showHeroModal" />
    </div>

</template>

<script setup>
// ═══════════════════════════════════════════════════════════════════════════════════
// IMPORTS & ASYNC COMPONENTS
// ═══════════════════════════════════════════════════════════════════════════════════

import { ref, reactive, computed, watch, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { TabsRoot, TabsList, TabsTrigger } from 'radix-vue';
import { SwitchRoot, SwitchThumb } from 'radix-vue';
import { companies, getCompany } from '@/data';
import { getQuotes } from '@/api/quotes';
import request from '@/api/request';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackQuoteSelected, trackStepCompleted } from '@/composables/useFunnelTracking';
import { usePricingSignature } from '@/composables/usePricingSignature';
import { usePricingConstants } from '@/composables/usePricingConstants';
import { useInsuranceStore } from '@/store/modules/insurance';
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';
import { isCustomerBlocked } from '@/utils/customerBlock';
import SarIcon from '@/components/SarIcon.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import QuotesLoading from '@/components/ui/QuotesLoading.vue';
import AppError from '@/components/ui/AppError.vue';
import QuoteCard from '@/car.insurance/components/compare/QuoteCard.vue';
import CompareSidebar from '@/car.insurance/components/compare/CompareSidebar.vue';
import logger from '@/utils/logger';
import { getSessionToken } from '@/utils/sessionToken';

// Async Components (lazy-loaded for better performance)
const OfferDetailsSheet = defineAsyncComponent( () => import( '@/components/OfferDetailsSheet.vue' ) );
const TaminkomHeroModal = defineAsyncComponent( () => import( '@/components/TaminkomHeroModal.vue' ) );
const CompareModal = defineAsyncComponent( () => import( '@/car.insurance/components/compare/CompareModal.vue' ) );
const MobileFiltersSheet = defineAsyncComponent( () => import( '@/car.insurance/components/compare/MobileFiltersSheet.vue' ) );

// ═══════════════════════════════════════════════════════════════════════════════════
// ROUTER, STORE & COMPOSABLES
// ═══════════════════════════════════════════════════════════════════════════════════

const route = useRoute();
const router = useRouter();
const { trackStep, resumeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();
const { store: storePricingSignature } = usePricingSignature();
const { load: loadConstants } = usePricingConstants();

// ═══════════════════════════════════════════════════════════════════════════════════
// CONSTANTS & IMAGE ASSETS
// ═══════════════════════════════════════════════════════════════════════════════════

const IMAGES = {
    ncdBannerImg: new URL( '../../../images/motorapp/mabruk.webp', import.meta.url ).href,
};

const QUOTE_LOADING_CONFIG = {
    MIN_DURATION: 5000, // Minimum 5 seconds before showing results
    PROGRESS_INTERVAL: 400, // Progress bar update interval
    PROGRESS_INCREMENT: { min: 3, max: 12 }, // Random increment range
    PROGRESS_CAP: 90, // Cap at 90% until API responds
};

const REPAIR_METHOD_OPTIONS = [
    { value: 'authorized', label: 'الورش المعتمدة' },
    { value: 'agency', label: 'وكالة' },
];

const SORT_OPTIONS = [
    { value: 'price-asc', label: 'السعر: الأقل' },
    { value: 'price-desc', label: 'السعر: الأعلى' },
    { value: 'rating', label: 'التقييم' },
    { value: 'deductible', label: 'التحمل: الأقل' },
];

const CATEGORY_TYPES = [
    { value: 'thirdParty', label: 'ضد الغير' },
    { value: 'comprehensive', label: 'الشامل' },
];

const COVERAGE_AFFECTED_SUBTYPES = [ 'comprehensive' ];

const DEFAULT_FILTER_STATE = {
    type: 'all',
    maxPrice: 8000,
    maxDeductible: 5000,
    companies: [],
};

const DEFAULT_QUOTE_OPTIONS = {
    repairMethod: 'authorized',
    coverageLimit: 55667,
};

const DEFAULT_COUNTDOWN = {
    total: 15 * 60,
    formatted: '15:00',
};

const DEFAULT_VEHICLE_INFO = {
    makeName: 'غير محدد',
    year: '',
    color: '',
    bodyType: '',
    sequenceNumber: '',
    plateNumber: '',
};

const COMPARE_LIMIT = 3; // Maximum number of plans for comparison
const DISPLAY_LIMIT = 5; // Default number of plans to display

// ═══════════════════════════════════════════════════════════════════════════════════
// STATE: QUOTES & LOADING
// ═══════════════════════════════════════════════════════════════════════════════════

const quotesData = ref( [] );
const quotesError = ref( null );
const isLoadingQuotes = ref( true );
const loadingProgress = ref( 0 );
let loadingInterval;
let loadingAborted = false;
let loadingDoneTimer = null;
let updatingDoneTimer = null;

// الطلب يرجع 422 عند نقص بيانات المركبة/السائق — يحتاج إكمال البيانات لا إعادة محاولة.
const isIncompleteQuoteData = computed( () => quotesError.value?.response?.status === 422 );

const quoteErrorTitle = computed( () =>
    isIncompleteQuoteData.value ? 'بيانات المركبة غير مكتملة' : 'تعذّر تحميل العروض' );

const quoteErrorMessage = computed( () =>
    isIncompleteQuoteData.value
        ? 'يبدو أن بعض بيانات المركبة أو السائق لم تُكمّل بعد. يرجى العودة لإكمالها.'
        : 'عذراً، لم نتمكن من جلب عروض التأمين. تحقق من اتصالك بالإنترنت وأعد المحاولة.' );

// في الوضع الفني نعرض رسائل التحقق الفعلية من الخادم بدلاً من نص axios العام.
const quoteErrorDetails = computed( () => {
    const err = quotesError.value;
    if ( !err ) return '';
    const fieldErrors = err?.response?.data?.errors;
    if ( fieldErrors && typeof fieldErrors === 'object' ) {
        return Object.values( fieldErrors ).flat().join( '\n' );
    }
    return err?.response?.data?.message || err?.message || '';
} );

const quoteErrorRetryLabel = computed( () =>
    isIncompleteQuoteData.value ? 'إكمال البيانات' : 'إعادة المحاولة' );

function handleQuotesErrorAction() {
    if ( isIncompleteQuoteData.value ) {
        router.push( { name: 'vehicleDetails' } );
        return;
    }
    retryLoadQuotes();
}

// ═══════════════════════════════════════════════════════════════════════════════════
// STATE: UI & SELECTIONS
// ═══════════════════════════════════════════════════════════════════════════════════

const showCompareModal = ref( false );
const showMobileFilters = ref( false );
const showOfferSheet = ref( false );
const showHeroModal = ref( false );
const offerSheetPlan = ref( null );
const offerSheetEntrySource = ref( 'offer_sheet' );
const selectedPlans = ref( [] );
const shakingCards = ref( [] );
const pulsingCards = ref( [] );
const expandedCards = ref( [] );
const expandedBenefits = ref( [] );
const selectionError = ref( '' );

// ═══════════════════════════════════════════════════════════════════════════════════
// STATE: OPTIONS & FILTERS
// ═══════════════════════════════════════════════════════════════════════════════════

const activeTab = ref( 'thirdParty' );
const sortBy = ref( 'price-asc' );
const compactView = ref( false );
const showAllPlans = ref( false );
const isUpdatingQuotes = ref( false );

const quoteOptions = reactive( { ...DEFAULT_QUOTE_OPTIONS } );
const filters = reactive( { type: route.query.type || DEFAULT_FILTER_STATE.type, ...DEFAULT_FILTER_STATE } );
const vehicleInfo = reactive( { ...DEFAULT_VEHICLE_INFO } );
const countdown = reactive( { ...DEFAULT_COUNTDOWN } );

// ═══════════════════════════════════════════════════════════════════════════════════
// COMPUTED PROPERTIES
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Show coverage limit input for specific coverage types
 */
const showCoverageLimit = computed( () => COVERAGE_AFFECTED_SUBTYPES.includes( activeTab.value ) );

/**
 * Plans with company data enriched
 */
const plansWithCompany = computed( () =>
    quotesData.value.map( plan => ( {
        ...plan,
        company: plan.company || getCompany( plan.companyId ),
    } ) )
);

/**
 * Category tabs with counts and minimum prices
 */
const categoryTabs = computed( () => {
    const buckets = {};
    for ( const p of plansWithCompany.value ) {
        if ( !buckets[ p.subType ] ) buckets[ p.subType ] = { count: 0, minPrice: Infinity };
        buckets[ p.subType ].count++;
        if ( p.annualPrice < buckets[ p.subType ].minPrice ) buckets[ p.subType ].minPrice = p.annualPrice;
    }
    return CATEGORY_TYPES.map( t => {
        const b = buckets[ t.value ];
        return {
            ...t,
            count: b ? b.count : 0,
            priceLabel: b ? formatNumber( Math.round( b.minPrice ) ) : 'لا يوجد تسعيرات',
        };
    } );
} );

/**
 * Filtered plans by tab, price, deductible, and company
 */
const filteredPlans = computed( () => {
    return plansWithCompany.value.filter( plan => {
        if ( plan.subType !== activeTab.value ) return false;
        if ( plan.annualPrice > filters.maxPrice ) return false;
        if ( plan.deductible > filters.maxDeductible ) return false;
        if ( filters.companies.length > 0 && !filters.companies.includes( plan.companyId ) ) return false;
        return true;
    } );
} );

/**
 * Sorted plans based on selected sort option
 */
const sortedPlans = computed( () => {
    const plans = [ ...filteredPlans.value ];
    switch ( sortBy.value ) {
        case 'price-asc': return plans.sort( ( a, b ) => a.annualPrice - b.annualPrice );
        case 'price-desc': return plans.sort( ( a, b ) => b.annualPrice - a.annualPrice );
        case 'rating': return plans.sort( ( a, b ) => b.company.rating - a.company.rating );
        case 'deductible': return plans.sort( ( a, b ) => a.deductible - b.deductible );
        default: return plans;
    }
} );

/**
 * Display limited or all plans based on showAllPlans flag
 */
const displayedPlans = computed( () => {
    const plans = sortedPlans.value;
    return showAllPlans.value ? plans : plans.slice( 0, DISPLAY_LIMIT );
} );

/**
 * Plans selected for comparison
 */
const comparedPlans = computed( () =>
    plansWithCompany.value.filter( p => selectedPlans.value.includes( p.id ) )
);

// ═══════════════════════════════════════════════════════════════════════════════════
// WATCHERS & DEBOUNCING
// ═══════════════════════════════════════════════════════════════════════════════════

// Reset display limit when active tab changes
watch( activeTab, () => { showAllPlans.value = false; } );

// Quote options debouncer
let _quoteDebounce = null;
watch( () => quoteOptions.coverageLimit, () => {
    if ( quotesData.value.length > 0 ) {
        clearTimeout( _quoteDebounce );
        _quoteDebounce = setTimeout( updateQuoteOptions, 200 );
    }
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// LIFECYCLE HOOKS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Initialize component: load constants, restore session, sync state, and start loading quotes
 */
onMounted( async () => {
    try {
        await loadConstants();
    } catch ( err ) {
        logger.warn( '[ComparePage] Failed to load pricing constants:', err );
    }

    if ( isCustomerBlocked() ) {
        isLoadingQuotes.value = false;
        return;
    }

    resumeSession( 'compare' );
    trackStepViewed( 'compare', { ui_variant: 'quotecard_v3_benefits3_details_unified' } );
    insuranceStore.hydrateFromSession();
    loadVehicleInfo();

    // Sync active tab with coverage type
    const ct = insuranceStore.policy.coverageType;
    if ( ct === 'comprehensive' ) {
        activeTab.value = 'comprehensive';
    } else if ( ct === 'thirdParty' ) {
        activeTab.value = 'thirdParty';
    }

    // Sync repair method from store
    const storeRepair = insuranceStore.policy.repairMethod;
    if ( storeRepair === 'agency' ) {
        quoteOptions.repairMethod = 'agency';
    } else if ( storeRepair === 'workshop' ) {
        quoteOptions.repairMethod = 'authorized';
    }

    // Sync coverage limit
    const storeLimit = insuranceStore.policy.coverageLimit;
    const vehicleValue = Number( insuranceStore.vehicle.estimatedValue );
    if ( vehicleValue > 0 ) {
        quoteOptions.coverageLimit = vehicleValue;
    } else if ( storeLimit && storeLimit !== 55667 ) {
        quoteOptions.coverageLimit = storeLimit;
    }

    insuranceStore.setPolicyData( {
        repairMethod: quoteOptions.repairMethod,
        coverageLimit: quoteOptions.coverageLimit,
    } );

    startLoadingQuotes();
    countdownInterval = setInterval( updateCountdown, 1000 );
    document.addEventListener( 'visibilitychange', handleVisibilityChange );
} );

/**
 * Cleanup: clear timers and event listeners
 */
onUnmounted( () => {
    clearInterval( countdownInterval );
    clearInterval( loadingInterval );
    clearTimeout( loadingDoneTimer );
    clearTimeout( updatingDoneTimer );
    document.removeEventListener( 'visibilitychange', handleVisibilityChange );
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// QUOTES: LOADING & ERROR HANDLING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Fetch quotes from API with animated progress bar
 * @async
 */
async function startLoadingQuotes() {
    if ( isCustomerBlocked() ) {
        loadingAborted = true;
        isLoadingQuotes.value = false;
        return;
    }

    loadingProgress.value = 0;
    isLoadingQuotes.value = true;
    quotesError.value = null;
    loadingAborted = false;

    const loadingStart = Date.now();

    // Animate progress bar independently of API
    loadingInterval = setInterval( () => {
        if ( loadingAborted ) return;
        if ( loadingProgress.value < QUOTE_LOADING_CONFIG.PROGRESS_CAP ) {
            const increment = Math.random() * ( QUOTE_LOADING_CONFIG.PROGRESS_INCREMENT.max - QUOTE_LOADING_CONFIG.PROGRESS_INCREMENT.min ) + QUOTE_LOADING_CONFIG.PROGRESS_INCREMENT.min;
            loadingProgress.value = Math.min( QUOTE_LOADING_CONFIG.PROGRESS_CAP, loadingProgress.value + increment );
        }
    }, QUOTE_LOADING_CONFIG.PROGRESS_INTERVAL );

    try {
        insuranceStore.hydrateFromSession();
        const result = await getQuotes( insuranceStore.allFormData );
        quotesData.value = withDisplayCoverage( result.plans || [] );
        insuranceStore.setCalculatedQuotes( quotesData.value );

        // Enforce minimum loading duration for UX
        const elapsed = Date.now() - loadingStart;
        const remaining = Math.max( 0, QUOTE_LOADING_CONFIG.MIN_DURATION - elapsed );
        await new Promise( resolve => setTimeout( resolve, remaining ) );

        loadingProgress.value = 100;
        clearInterval( loadingInterval );

        loadingDoneTimer = setTimeout( () => {
            isLoadingQuotes.value = false;
        }, 400 );
    } catch ( err ) {
        clearInterval( loadingInterval );
        loadingAborted = true;
        quotesError.value = err;
        isLoadingQuotes.value = false;
        logger.error( '[ComparePage] Failed to fetch quotes:', err );
    }
}

/**
 * Retry fetching quotes after an error
 */
function retryLoadQuotes() {
    quotesError.value = null;
    startLoadingQuotes();
}

// ═══════════════════════════════════════════════════════════════════════════════════
// QUOTES: OPTION UPDATES & PRICING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Apply display coverage based on user-selected coverage limit
 * @param {Array} plans - Plans from API
 * @returns {Array} Plans with adjusted coverage display
 */
function withDisplayCoverage( plans ) {
    const userLimit = quoteOptions.coverageLimit;
    return plans.map( p => ( {
        ...p,
        coverageLimit: COVERAGE_AFFECTED_SUBTYPES.includes( p.subType ) && userLimit ? userLimit : p.coverageLimit,
    } ) );
}

/**
 * Build complete form data with policy overrides
 * @param {Object} overrides - Policy overrides
 * @returns {Object} Complete form data
 */
function formDataWithPolicyOverrides( overrides = {} ) {
    return {
        ...insuranceStore.allFormData,
        policy: {
            ...insuranceStore.allFormData.policy,
            repairMethod: quoteOptions.repairMethod,
            coverageLimit: quoteOptions.coverageLimit,
            ...overrides,
        },
    };
}

/**
 * Fetch repriced plans from server
 * @async
 * @param {Array} plans - Plans to reprice
 * @param {Object} overrides - Policy overrides
 * @returns {Array} Repriced plans
 */
async function pricePlansFromServer( plans, overrides = {} ) {
    const result = await getQuotes( formDataWithPolicyOverrides( overrides ), plans );
    return withDisplayCoverage( result.plans || [] );
}

/**
 * Update all quote options (repair method and coverage limit)
 * @async
 */
async function updateQuoteOptions() {
    clearTimeout( _quoteDebounce );
    isUpdatingQuotes.value = true;
    selectionError.value = '';

    insuranceStore.setPolicyData( {
        repairMethod: quoteOptions.repairMethod,
        coverageLimit: quoteOptions.coverageLimit,
    } );

    try {
        const repriced = await pricePlansFromServer( quotesData.value );
        quotesData.value = repriced;
        insuranceStore.setCalculatedQuotes( quotesData.value );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to reprice quote options:', err );
        selectionError.value = 'تعذّر تحديث الأسعار من السيرفر. تحقق من اتصالك وأعد المحاولة.';
    } finally {
        updatingDoneTimer = setTimeout( () => {
            isUpdatingQuotes.value = false;
        }, 300 );
    }
}

/**
 * Update pricing for a single plan deductible change
 * @async
 * @param {string} planId - Plan identifier
 * @param {number} newDeductible - New deductible value
 */
async function onPlanDeductibleChange( planId, newDeductible ) {
    const idx = quotesData.value.findIndex( p => p.id === planId );
    if ( idx === -1 ) return;

    const deductible = Number( newDeductible );
    isUpdatingQuotes.value = true;
    selectionError.value = '';

    try {
        const [ updatedPlan ] = await pricePlansFromServer(
            [ { ...quotesData.value[ idx ], deductible } ],
            { deductible },
        );

        if ( updatedPlan ) {
            quotesData.value[ idx ] = { ...quotesData.value[ idx ], ...updatedPlan, deductible };
            insuranceStore.setCalculatedQuotes( quotesData.value );
        }
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to reprice deductible change:', err );
        selectionError.value = 'تعذّر تحديث السعر حسب قيمة التحمل. الرجاء المحاولة مرة أخرى.';
    } finally {
        updatingDoneTimer = setTimeout( () => {
            isUpdatingQuotes.value = false;
        }, 300 );
    }
}

// ═══════════════════════════════════════════════════════════════════════════════════
// VEHICLE INFO & DATA LOADING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Load vehicle information from store or session storage
 */
function loadVehicleInfo() {
    // Primary: from central store
    if ( insuranceStore.vehicle.make ) {
        vehicleInfo.makeName = insuranceStore.vehicle.makeName || insuranceStore.vehicle.make;
        vehicleInfo.year = insuranceStore.vehicle.year;
        vehicleInfo.sequenceNumber = insuranceStore.vehicle.sequenceNumber;
        vehicleInfo.plateNumber = insuranceStore.vehicle.plateNumber;
        return;
    }
    // Fallback: sessionStorage
    const vehicleDetails = sessionStorage.getItem( 'vehicleDetails' );
    const vehicleForm = sessionStorage.getItem( 'vehicleForm' );
    if ( vehicleDetails ) {
        try {
            const parsed = JSON.parse( vehicleDetails );
            if ( parsed.sequenceNumber ) vehicleInfo.sequenceNumber = parsed.sequenceNumber;
        } catch { /* ignore */ }
    }
    if ( vehicleForm ) {
        try {
            const parsed = JSON.parse( vehicleForm );
            if ( parsed.vehicleMake ) vehicleInfo.makeName = parsed.vehicleMake;
            if ( parsed.vehicleYear ) vehicleInfo.year = parsed.vehicleYear;
        } catch { /* ignore */ }
    }
}

// ═══════════════════════════════════════════════════════════════════════════════════
// FILTERS & SORT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Reset all filters to default values
 */
function resetFilters() {
    filters.maxPrice = DEFAULT_FILTER_STATE.maxPrice;
    filters.maxDeductible = DEFAULT_FILTER_STATE.maxDeductible;
    filters.companies = [];
    activeTab.value = 'thirdParty';
    showAllPlans.value = false;
}

// ═══════════════════════════════════════════════════════════════════════════════════
// PLAN SELECTION & OFFER HANDLING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Issue a quote lock on the server to reserve pricing
 * @async
 * @param {Object} selection - Selected plan with details
 * @returns {Object} Quote lock token and pricing data
 */
async function issueQuoteLock( selection ) {
    const payload = {
        plan_id: selection.id,
        company_id: selection.companyId,
        plan_sub_type: selection.subType,
        plan_name: selection.name,
        insurance_company: selection.companyName || '',
        insurance_type: selection.type === 'thirdParty' ? 'third_party' : 'comprehensive',
        plan_type: selection.subType || selection.type,
        // Best-effort estimate; the server recomputes the authoritative amounts below.
        subtotal: Number( selection.annualPrice || 0 ) + Number( selection.addonsTotal || 0 ),
        vat_amount: Math.round( Number( selection.annualPrice || 0 ) * 0.15 ),
        total: Number( selection.annualPrice || 0 ) + Number( selection.addonsTotal || 0 ) + Math.round( Number( selection.annualPrice || 0 ) * 0.15 ),
        deductible: Number( selection.deductible || 0 ),
        addons: selection.addons || [],
        session_id: getSessionToken(),
    };

    const { data } = await request.post( '/quotes/lock', payload );
    return {
        quoteLockToken: data.quote_lock_token,
        quoteLockExpiresAt: data.expires_at,
        pricingSignature: data.pricing_signature,
        pricingTimestamp: data.pricing_timestamp,
        pricingExpiresAt: data.pricing_expires_at,
        // Authoritative amounts from the server — must match /api/quotes/calculate exactly.
        subtotal: data.subtotal,
        vatAmount: data.vat_amount,
        totalPrice: data.total,
    };
}

/**
 * Verify plan has pricing signature fields
 * @param {Object} quote - Plan to verify
 * @returns {boolean} True if signature fields present
 */
function ensureSignatureFields( quote ) {
    const hasSignature = Boolean( quote?.signature && quote?.timestamp && quote?.expiresAt );
    if ( hasSignature ) return true;

    selectionError.value = 'تعذّر التحقق من السعر حالياً. الرجاء تحديث العروض والمحاولة مرة أخرى.';
    logger.warn( '[ComparePage] Missing pricing signature packet on selected quote', {
        planId: quote?.id,
        companyId: quote?.companyId,
        subType: quote?.subType,
    } );
    return false;
}

/**
 * Select a plan and proceed to order review
 * @async
 * @param {Object} plan - Selected plan
 * @param {string} source - Source of selection (for tracking)
 */
async function selectPlan( plan, source = 'card_expanded' ) {
    selectionError.value = '';
    if ( !ensureSignatureFields( plan ) ) return;

    trackStep( 'select_plan', 4, { selected_plan_id: plan.id, source }, 'next' );
    let lock;
    try {
        lock = await issueQuoteLock( {
            id: plan.id,
            name: plan.name,
            companyName: plan.company?.nameAr,
            companyId: plan.companyId,
            type: plan.type,
            subType: plan.subType,
            annualPrice: plan.annualPrice,
            deductible: plan.deductible,
            addons: [],
            addonsTotal: 0,
        } );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to issue quote lock:', err );
        selectionError.value = 'تعذّر تثبيت السعر. تحقق من اتصالك بالإنترنت وأعد المحاولة.';
        return;
    }

    // Store signed quote packet in memory (secure flow)
    try {
        storePricingSignature( {
            planId: plan.id,
            totalPrice: lock.totalPrice,
            signature: lock.pricingSignature,
            timestamp: lock.pricingTimestamp,
            expiresAt: lock.pricingExpiresAt,
            companyId: plan.companyId,
            subType: plan.subType,
            annualPrice: plan.annualPrice,
            pricingFactors: plan.pricingFactors || null,
        } );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to store pricing signature packet:', err );
        selectionError.value = 'تعذّر حفظ توقيع حماية السعر. الرجاء المحاولة مرة أخرى.';
        return;
    }

    insuranceStore.setSelectedPlan( {
        id: plan.id,
        name: plan.name,
        companyName: plan.company?.nameAr,
        annualPrice: plan.annualPrice,
        originalPrice: plan.originalPrice || plan.annualPrice,
        monthlyPrice: plan.monthlyPrice || Math.ceil( plan.annualPrice / 12 ),
        type: plan.type,
        deductible: plan.deductible,
        addons: [],
        quoteLockToken: lock.quoteLockToken,
        quoteLockExpiresAt: lock.quoteLockExpiresAt,
        subtotal: lock.subtotal,
        subtotalBeforeVAT: lock.subtotal,
        vatAmount: lock.vatAmount,
        totalPrice: lock.totalPrice,
        pricingSignature: lock.pricingSignature,
        pricingTimestamp: lock.pricingTimestamp,
        pricingExpiresAt: lock.pricingExpiresAt,
    } );
    trackQuoteSelected( { plan_id: plan.id, source } );
    trackStepCompleted( 'compare', 'orderReview' );
    router.push( { name: 'orderReview' } );
}

/**
 * Open the offer details sheet to show full plan details
 * @param {Object} plan - Plan to display
 * @param {string} source - Source of opening (for tracking)
 */
function openOfferSheet( plan, source = 'offer_sheet' ) {
    offerSheetPlan.value = plan;
    offerSheetEntrySource.value = source;
    showOfferSheet.value = true;
    trackStep( 'view_offer_details', 4, { selected_plan_id: plan.id, source }, 'next' );
}

/**
 * Handle offer selection with deductible and addons
 * @async
 * @param {Object} selection - Selection with plan, deductible, and addons
 */
async function handleOfferSelect( selection ) {
    selectionError.value = '';
    showOfferSheet.value = false;
    const p = selection.plan;
    const source = offerSheetEntrySource.value || 'offer_sheet';
    const addons = selection.addons || [];
    const addonsTotal = addons.reduce( ( sum, a ) => sum + Number( a?.price || 0 ), 0 );
    const selectedDeductible = Number( selection.deductible ?? p.deductible ?? 0 );

    let signedPlan;
    try {
        [ signedPlan ] = await pricePlansFromServer(
            [ { ...p, deductible: selectedDeductible } ],
            { deductible: selectedDeductible },
        );
        signedPlan = signedPlan ? { ...p, ...signedPlan, deductible: selectedDeductible } : null;
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to reprice offer selection:', err );
        selectionError.value = 'تعذّر تحديث سعر العرض قبل الاختيار. الرجاء المحاولة مرة أخرى.';
        return;
    }

    if ( !ensureSignatureFields( signedPlan ) ) return;

    let lock;
    try {
        lock = await issueQuoteLock( {
            id: signedPlan.id,
            name: signedPlan.name,
            companyName: signedPlan.company?.nameAr,
            companyId: signedPlan.companyId,
            type: signedPlan.type,
            subType: signedPlan.subType,
            annualPrice: Number( signedPlan.annualPrice || 0 ),
            deductible: selectedDeductible,
            addons,
            addonsTotal,
        } );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to issue quote lock (offer select):', err );
        selectionError.value = 'تعذّر تثبيت السعر. تحقق من اتصالك بالإنترنت وأعد المحاولة.';
        return;
    }

    // Store signed quote packet in memory (secure flow)
    try {
        storePricingSignature( {
            planId: signedPlan.id,
            totalPrice: lock.totalPrice,
            signature: lock.pricingSignature,
            timestamp: lock.pricingTimestamp,
            expiresAt: lock.pricingExpiresAt,
            companyId: signedPlan.companyId,
            subType: signedPlan.subType,
            annualPrice: Number( signedPlan.annualPrice || 0 ),
            pricingFactors: signedPlan.pricingFactors || null,
        } );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to store pricing signature packet (offer select):', err );
        selectionError.value = 'تعذّر حفظ توقيع حماية السعر. الرجاء المحاولة مرة أخرى.';
        return;
    }

    insuranceStore.setSelectedPlan( {
        id: signedPlan.id,
        name: signedPlan.name,
        companyName: signedPlan.company?.nameAr,
        annualPrice: Number( signedPlan.annualPrice || 0 ),
        originalPrice: Number( signedPlan.originalPrice || signedPlan.annualPrice || 0 ),
        monthlyPrice: signedPlan.monthlyPrice || Math.ceil( Number( signedPlan.annualPrice || 0 ) / 12 ),
        type: signedPlan.type,
        deductible: selectedDeductible,
        addons,
        totalPrice: lock.totalPrice,
        subtotal: lock.subtotal,
        vatAmount: lock.vatAmount,
        quoteLockToken: lock.quoteLockToken,
        quoteLockExpiresAt: lock.quoteLockExpiresAt,
        pricingSignature: lock.pricingSignature,
        pricingTimestamp: lock.pricingTimestamp,
        pricingExpiresAt: lock.pricingExpiresAt,
    } );
    trackStep( 'select_plan', 4, { selected_plan_id: signedPlan.id, source }, 'next' );
    trackQuoteSelected( { plan_id: signedPlan.id, source } );
    trackStepCompleted( 'compare', 'orderReview' );
    offerSheetEntrySource.value = 'offer_sheet';
    router.push( { name: 'orderReview' } );
}

// ═══════════════════════════════════════════════════════════════════════════════════
// ANIMATION & UI INTERACTIONS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Trigger animation on a card by adding and removing from list
 * @param {Ref} list - Reactive list to track animation
 * @param {string} id - Card ID to animate
 * @param {number} duration - Animation duration in ms
 */
function triggerAnimation( list, id, duration = 600 ) {
    list.value.push( id );
    setTimeout( () => {
        list.value = list.value.filter( x => x !== id );
    }, duration );
}

/**
 * Toggle plan comparison checkbox
 * @param {string} planId - Plan to compare
 * @param {boolean} checked - Whether plan is selected
 */
function onCompareToggle( planId, checked ) {
    if ( checked ) {
        if ( selectedPlans.value.length >= COMPARE_LIMIT ) {
            [ ...selectedPlans.value, planId ].forEach( id => triggerAnimation( shakingCards, id, 500 ) );
            return;
        }
        if ( !selectedPlans.value.includes( planId ) ) {
            selectedPlans.value.push( planId );
            triggerAnimation( pulsingCards, planId, 600 );
        }
    } else {
        selectedPlans.value = selectedPlans.value.filter( id => id !== planId );
    }
}

/**
 * Toggle benefits expansion for a plan
 * @param {string} planId - Plan ID
 */
function toggleExpandedBenefits( planId ) {
    const idx = expandedBenefits.value.indexOf( planId );
    if ( idx === -1 ) {
        expandedBenefits.value.push( planId );
    } else {
        expandedBenefits.value.splice( idx, 1 );
    }
}

/**
 * Toggle card expansion for a plan
 * @param {string} planId - Plan ID
 */
function toggleCardExpand( planId ) {
    const idx = expandedCards.value.indexOf( planId );
    if ( idx === -1 ) {
        expandedCards.value.push( planId );
    } else {
        expandedCards.value.splice( idx, 1 );
    }
}

// ═══════════════════════════════════════════════════════════════════════════════════
// COUNTDOWN TIMER
// ═══════════════════════════════════════════════════════════════════════════════════

let countdownInterval;

/**
 * Update countdown timer display and auto-refresh quotes when expired
 */
function updateCountdown() {
    if ( countdown.total <= 0 ) {
        clearInterval( countdownInterval );
        countdown.total = DEFAULT_COUNTDOWN.total;
        countdown.formatted = DEFAULT_COUNTDOWN.formatted;
        startLoadingQuotes();
        countdownInterval = setInterval( updateCountdown, 1000 );
        return;
    }
    countdown.total--;
    const min = Math.floor( countdown.total / 60 );
    const sec = countdown.total % 60;
    countdown.formatted = `${ String( min ).padStart( 2, '0' ) }:${ String( sec ).padStart( 2, '0' ) }`;
}

/**
 * Handle document visibility changes (pause/resume countdown)
 */
function handleVisibilityChange() {
    if ( document.hidden ) {
        clearInterval( countdownInterval );
    } else {
        countdownInterval = setInterval( updateCountdown, 1000 );
    }
}
</script>

<style scoped>
@keyframes bounce-in {
    0% { transform: scale(0.8) translateY(20px); opacity: 0; }
    60% { transform: scale(1.03); opacity: 1; }
    100% { transform: scale(1) translateY(0); }
}
.animate-bounce-in {
    animation: bounce-in 0.4s ease-out;
}
/* Bottom bar safe area on iOS */
@supports (padding-bottom: env(safe-area-inset-bottom)) {
    .fixed.bottom-0 {
        padding-bottom: calc(0.75rem + env(safe-area-inset-bottom));
    }
}
</style>
