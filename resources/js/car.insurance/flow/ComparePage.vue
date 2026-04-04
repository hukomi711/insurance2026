<template>
    <!-- ═══ Loading State ═══ -->
    <QuotesLoading v-if="isLoadingQuotes" :progress="loadingProgress" @back="router.push({ name: 'policyDetails' })" />

    <!-- ═══ Error State ═══ -->
    <div v-else-if="quotesError" class="min-h-screen bg-slate-50 center" dir="rtl">
        <AppError title="تعذّر تحميل العروض"
            message="عذراً، لم نتمكن من جلب عروض التأمين. تحقق من اتصالك بالإنترنت وأعد المحاولة."
            :details="quotesError?.message" @retry="retryLoadQuotes" />
    </div>

    <!-- ═══ Quotes Loaded ═══ -->
    <div v-else class="min-h-screen bg-slate-50" dir="rtl" role="main">

        <!-- ── Top Header: Back + Timer ── -->
        <div class="bg-white border-b border-slate-200">
            <div class="box py-3 flex items-center justify-between">
                <router-link :to="{ name: 'policyDetails' }"
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
                                class="inline-flex items-center justify-center whitespace-nowrap transition-all focus-visible:outline-none typ-s2 font-bold text-slate-500 py-2 sm:py-2.5 px-2.5 sm:px-3 flex-col flex-none sm:flex-1 min-w-fit sm:min-w-0 data-[state=active]:bg-white data-[state=active]:shadow-sm data-[state=active]:rounded-lg data-[state=active]:text-primary">
                                <span class="flex flex-col items-center gap-0.5">
                                    <span>{{ tab.label }}</span>
                                    <span class="typ-c1 !text-slate-400 ltr-nums">{{ tab.priceLabel }}</span>
                                </span>
                            </TabsTrigger>
                        </TabsList>
                    </TabsRoot>

                    <!-- NCD Discount Banner -->
                    <div class="flex gap-2 items-center justify-between cursor-pointer rounded-lg p-4 mb-4 hover:opacity-80 transition-opacity bg-green-600 text-white">
                        <div class="flex gap-2 items-center">
                            <img :src="ncdBannerImg" alt="ncd-discount-clap" class="max-w-full w-5 h-5" loading="lazy" width="20" height="20" />
                            <span class="text-sm font-medium">مبروك عليك خصم يبدأ من 20% نتيجة قيادتك الآمنة</span>
                        </div>
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>

                    <!-- Repair Method & Coverage -->
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 mb-4">
                        <div :class="showCoverageLimit ? 'sm:col-span-4' : 'sm:col-span-9'">
                            <AppSelect id="repairMethod" v-model="quoteOptions.repairMethod"
                                label="طريقة الإصلاح" :options="repairMethodOptions" variant="standard"
                                name="repairMethod" />
                        </div>
                        <div v-if="showCoverageLimit" class="sm:col-span-5">
                            <div class="group relative flex border border-slate-300 rounded-lg min-h-[3.25rem] sm:min-h-[3.5rem] px-3 sm:px-4 py-2 items-center gap-1.5 sm:gap-2 w-full
                                        focus-within:border-primary transition">
                                <input id="coverageLimit" v-model.number="quoteOptions.coverageLimit" type="number" autocomplete="off"
                                    name="coverageLimit"
                                    class="bg-transparent block w-full text-sm text-foreground pt-5 pb-1 appearance-none focus:outline-none peer ltr-nums"
                                    placeholder=" " />
                                <SarIcon className="size-4 sm:size-5 shrink-0 text-muted self-center" />
                                <label for="coverageLimit"
                                    class="absolute text-sm text-slate-500 transition-all top-4 start-3 sm:start-4
                                              peer-focus:top-1.5 peer-focus:text-xs peer-focus:text-primary
                                              peer-[:not(:placeholder-shown)]:top-1.5 peer-[:not(:placeholder-shown)]:text-xs">
                                    حد التغطية لمركبتك
                                </label>
                            </div>
                        </div>
                        <div class="sm:col-span-3">
                            <button :disabled="isUpdatingQuotes" class="w-full min-h-[3.25rem] sm:min-h-[3.5rem] px-4 sm:px-6 typ-t3 font-bold rounded-lg bg-primary text-white
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
                                {{ sortOptions.find( o => o.value === sortBy )?.label || 'السعر: الأقل' }}
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
                                class="w-10 h-[22px] bg-slate-300 rounded-full relative data-[state=checked]:bg-primary transition-colors">
                                <SwitchThumb
                                    class="block w-[18px] h-[18px] bg-white rounded-full shadow transition-transform translate-x-[2px] data-[state=checked]:translate-x-[20px]" />
                            </SwitchRoot>
                        </label>
                    </div>

                    <!-- ═══ Quote Cards ═══ -->
                    <div class="space-y-3">
                        <QuoteCard v-for="plan in sortedPlans" :key="plan.id" :plan="plan"
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
                        <router-link :to="{ name: 'policyDetails' }"
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

        <!-- Fixed Mobile Bottom Bar -->
        <div class="fixed bottom-0 inset-x-0 bg-white border-t border-slate-200 p-3 xl:hidden z-40 safe-area-bottom">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2 typ-s2 text-muted">
                    <span>عرض مختصر</span>
                    <SwitchRoot id="compact-toggle-mobile" v-model:checked="compactView" name="compactViewMobile"
                        class="w-9 h-5 bg-slate-300 rounded-full relative data-[state=checked]:bg-primary transition-colors">
                        <SwitchThumb
                            class="block w-4 h-4 bg-white rounded-full shadow transition-transform translate-x-[2px] data-[state=checked]:translate-x-[18px]" />
                    </SwitchRoot>
                </div>

                <!-- Compare Selected -->
                <button v-if="selectedPlans.length >= 2" class="flex items-center gap-2 px-4 py-2 bg-secondary text-white typ-s2 font-bold rounded-xl"
                    @click="showCompareModal = true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    مقارنة ({{ selectedPlans.length }})
                </button>

                <!-- Mobile Filter Trigger -->
                <button class="flex items-center gap-1.5 px-4 py-2 border border-slate-200 typ-s2 text-foreground font-bold rounded-xl"
                    @click="showMobileFilters = true">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    تصفية
                </button>
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
import { ref, reactive, computed, watch, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { TabsRoot, TabsList, TabsTrigger } from 'radix-vue';
import { SwitchRoot, SwitchThumb } from 'radix-vue';
import { companies, getCompany } from '@/data';
import { getQuotes } from '@/api/quotes';
import request from '@/api/request';
import { useQuoteTracking } from '@/composables/useQuoteTracking';
import { trackStepViewed, trackQuoteSelected, trackStepCompleted } from '@/composables/useFunnelTracking';
import { useInsuranceStore } from '@/store/modules/insurance';
import { usePricingEngine } from '@/utils/pricingEngine';
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';
import SarIcon from '@/components/SarIcon.vue';
import AppSelect from '@/components/ui/AppSelect.vue';
import QuotesLoading from '@/components/ui/QuotesLoading.vue';
import AppError from '@/components/ui/AppError.vue';
import QuoteCard from '@/car.insurance/components/compare/QuoteCard.vue';
const OfferDetailsSheet = defineAsyncComponent( () => import( '@/components/OfferDetailsSheet.vue' ) );
const TaminkomHeroModal = defineAsyncComponent( () => import( '@/components/TaminkomHeroModal.vue' ) );
const CompareModal = defineAsyncComponent( () => import( '@/car.insurance/components/compare/CompareModal.vue' ) );
const MobileFiltersSheet = defineAsyncComponent( () => import( '@/car.insurance/components/compare/MobileFiltersSheet.vue' ) );
import CompareSidebar from '@/car.insurance/components/compare/CompareSidebar.vue';
import logger from '@/utils/logger';

const ncdBannerImg = new URL( '../../../images/motorapp/mabruk.webp', import.meta.url ).href;

const route = useRoute();
const router = useRouter();
const { trackStep, resumeSession } = useQuoteTracking();
const insuranceStore = useInsuranceStore();
const { calculateAllQuotes, recalculateSinglePlan } = usePricingEngine();

// ── Quotes data (loaded from API) ──
const quotesData = ref( [] );
const quotesError = ref( null );

// ── Loading state ──
const isLoadingQuotes = ref( true );
const loadingProgress = ref( 0 );
let loadingInterval;
let loadingAborted = false;

/**
 * Fetch quotes from API with progress indication.
 * Progress bar animates independently — completes when API responds.
 * Now uses dynamic pricing via the insurance store.
 */
async function startLoadingQuotes() {
    loadingProgress.value = 0;
    isLoadingQuotes.value = true;
    quotesError.value = null;
    loadingAborted = false;

    const MIN_LOADING_MS = 5000;
    const loadingStart = Date.now();

    // Animate progress bar independently of API
    loadingInterval = setInterval( () => {
        if ( loadingAborted ) return;
        // Cap at 90% until API responds
        if ( loadingProgress.value < 90 ) {
            loadingProgress.value = Math.min( 90, loadingProgress.value + Math.random() * 12 + 3 );
        }
    }, 400 );

    try {
        // استعادة بيانات التأمين من sessionStorage إلى المتجر
        insuranceStore.hydrateFromSession();

        // استدعاء API مع بيانات النموذج للتسعير الديناميكي
        const result = await getQuotes( insuranceStore.allFormData );
        quotesData.value = result.plans || [];

        // حفظ الأسعار المحسوبة في المتجر
        insuranceStore.setCalculatedQuotes( quotesData.value );

        // انتظار 5 ثوانٍ كحد أدنى قبل إظهار العروض
        const elapsed = Date.now() - loadingStart;
        const remaining = Math.max( 0, MIN_LOADING_MS - elapsed );
        await new Promise( resolve => setTimeout( resolve, remaining ) );

        // Complete progress bar
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

/** Retry fetching quotes after an error */
function retryLoadQuotes() {
    quotesError.value = null;
    startLoadingQuotes();
}

// Logo & format helpers use shared composables/utilities (imported above)

// Resume tracking
onMounted( () => {
    resumeSession( 'compare' );
    trackStepViewed( 'compare', { ui_variant: 'quotecard_v3_benefits3_details_unified' } );
    loadVehicleInfo();
    startLoadingQuotes();

    // مزامنة التبويب النشط مع نوع التغطية المختار
    // hydrateFromSession already called inside startLoadingQuotes before await
    const ct = insuranceStore.policy.coverageType;
    if ( ct === 'comprehensive' ) {
        activeTab.value = 'comprehensive';
    } else if ( ct === 'thirdParty' ) {
        activeTab.value = 'thirdParty';
    }

    // مزامنة طريقة الإصلاح من المتجر
    const storeRepair = insuranceStore.policy.repairMethod;
    if ( storeRepair === 'agency' ) {
        quoteOptions.repairMethod = 'agency';
    } else if ( storeRepair === 'workshop' ) {
        quoteOptions.repairMethod = 'authorized';
    }

    // مزامنة حد التغطية من قيمة المركبة أو المتجر
    const storeLimit = insuranceStore.policy.coverageLimit;
    const vehicleValue = Number( insuranceStore.vehicle.estimatedValue );
    if ( vehicleValue > 0 ) {
        quoteOptions.coverageLimit = vehicleValue;
    } else if ( storeLimit && storeLimit !== 55667 ) {
        quoteOptions.coverageLimit = storeLimit;
    }

    // Countdown timer
    countdownInterval = setInterval( updateCountdown, 1000 );

    // Pause timers when tab is hidden, resume when visible
    document.addEventListener( 'visibilitychange', handleVisibilityChange );
} );

// State
const showCompareModal = ref( false );
const showMobileFilters = ref( false );
const showOfferSheet = ref( false );
const showHeroModal = ref( false );
const offerSheetPlan = ref( null );
const offerSheetEntrySource = ref( 'offer_sheet' );
const selectedPlans = ref( [] );
const shakingCards = ref( [] );
const pulsingCards = ref( [] );
const sortBy = ref( 'price-asc' );
const compactView = ref( false );
const activeTab = ref( 'thirdParty' );
const showCoverageLimit = computed( () => [ 'comprehensive', 'vehicleDamagePlus', 'thirdPartyPlus' ].includes( activeTab.value ) );
const expandedCards = ref( [] );
const selectionError = ref( '' );

// Quote options (repair method & coverage)
const quoteOptions = reactive( {
    repairMethod: 'authorized',
    coverageLimit: 55667,
} );
const isUpdatingQuotes = ref( false );
const expandedBenefits = ref( [] );

function triggerAnimation( list, id, duration = 600 ) {
    list.value.push( id );
    setTimeout( () => {
        list.value = list.value.filter( x => x !== id );
    }, duration );
}

function onCompareToggle( planId, checked ) {
    if ( checked ) {
        if ( selectedPlans.value.length >= 3 ) {
            // Shake all selected cards + the attempted one
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

function toggleExpandedBenefits( planId ) {
    const idx = expandedBenefits.value.indexOf( planId );
    if ( idx === -1 ) {
        expandedBenefits.value.push( planId );
    } else {
        expandedBenefits.value.splice( idx, 1 );
    }
}

function toggleCardExpand( planId ) {
    const idx = expandedCards.value.indexOf( planId );
    if ( idx === -1 ) {
        expandedCards.value.push( planId );
    } else {
        expandedCards.value.splice( idx, 1 );
    }
}

const repairMethodOptions = [
    { value: 'authorized', label: 'الورش المعتمدة' },
    { value: 'agency', label: 'وكالة' },
];

// تحديث تلقائي عند تغيير طريقة الإصلاح أو حد التغطية
let _quoteDebounce = null;
watch( () => [ quoteOptions.repairMethod, quoteOptions.coverageLimit ], () => {
    if ( quotesData.value.length > 0 ) {
        clearTimeout( _quoteDebounce );
        _quoteDebounce = setTimeout( updateQuoteOptions, 200 );
    }
} );

function updateQuoteOptions() {
    clearTimeout( _quoteDebounce );
    isUpdatingQuotes.value = true;

    // تحديث بيانات الوثيقة في المتجر
    insuranceStore.setPolicyData( {
        repairMethod: quoteOptions.repairMethod,
        coverageLimit: quoteOptions.coverageLimit,
    } );

    // إعادة حساب الأسعار فورياً مع تمرير طريقة الإصلاح وحد التغطية
    const recalculated = calculateAllQuotes(
        quotesData.value,
        insuranceStore.allFormData,
        { repairMethod: quoteOptions.repairMethod, coverageLimit: quoteOptions.coverageLimit }
    );

    // تحديث مكان الإصلاح + حد التغطية على كل باقة
    const repairLabel = quoteOptions.repairMethod === 'agency' ? 'الوكالة' : 'الورش المعتمدة';
    const userLimit = quoteOptions.coverageLimit;
    const affectedSubTypes = [ 'comprehensive', 'vehicleDamagePlus', 'thirdPartyPlus' ];
    quotesData.value = recalculated.map( p => ( {
        ...p,
        repairLocation: repairLabel,
        // تحديث حد التغطية المعروض للباقات المتأثرة
        coverageLimit: affectedSubTypes.includes( p.subType ) && userLimit ? userLimit : p.coverageLimit,
    } ) );
    insuranceStore.setCalculatedQuotes( quotesData.value );

    updatingDoneTimer = setTimeout( () => {
        isUpdatingQuotes.value = false;
    }, 300 );
}

// Vehicle info from store / sessionStorage
const vehicleInfo = reactive( {
    makeName: 'غير محدد',
    year: '',
    color: '',
    bodyType: '',
    sequenceNumber: '',
    plateNumber: '',
} );

function loadVehicleInfo() {
    // أولاً: من المتجر المركزي
    if ( insuranceStore.vehicle.make ) {
        vehicleInfo.makeName = insuranceStore.vehicle.makeName || insuranceStore.vehicle.make;
        vehicleInfo.year = insuranceStore.vehicle.year;
        vehicleInfo.sequenceNumber = insuranceStore.vehicle.sequenceNumber;
        vehicleInfo.plateNumber = insuranceStore.vehicle.plateNumber;
        return;
    }
    // التراجع: sessionStorage
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

/**
 * إعادة حساب سعر خطة واحدة عند تغيير الخصم من القائمة المنسدلة في الكارت
 */
function onPlanDeductibleChange( planId, newDeductible ) {
    const idx = quotesData.value.findIndex( p => p.id === planId );
    if ( idx === -1 ) return;

    const updatedPlan = recalculateSinglePlan(
        quotesData.value[ idx ],
        insuranceStore.allFormData,
        Number( newDeductible )
    );
    // تحديث الخطة في القائمة
    quotesData.value[ idx ] = { ...updatedPlan, deductible: Number( newDeductible ) };
}

const filters = reactive( {
    type: route.query.type || 'all',
    maxPrice: 8000,
    maxDeductible: 5000,
    companies: [],
} );

// Sort options
const sortOptions = [
    { value: 'price-asc', label: 'السعر: الأقل' },
    { value: 'price-desc', label: 'السعر: الأعلى' },
    { value: 'rating', label: 'التقييم' },
    { value: 'deductible', label: 'التحمل: الأقل' },
];

// Category tabs
const categoryTabs = computed( () => {
    const types = [
        { value: 'thirdParty', label: 'ضد الغير' },
        { value: 'thirdPartyPlus', label: 'ضد الغير بلس' },
        { value: 'vehicleDamagePlus', label: 'أضرار المركبة بلس' },
        { value: 'comprehensive', label: 'الشامل' },
    ];
    // Single pass: bucket plans by subType and track min price per bucket
    const buckets = {};
    for ( const p of plansWithCompany.value ) {
        if ( !buckets[ p.subType ] ) buckets[ p.subType ] = { count: 0, minPrice: Infinity };
        buckets[ p.subType ].count++;
        if ( p.annualPrice < buckets[ p.subType ].minPrice ) buckets[ p.subType ].minPrice = p.annualPrice;
    }
    return types.map( t => {
        const b = buckets[ t.value ];
        return {
            ...t,
            count: b ? b.count : 0,
            priceLabel: b ? formatNumber( Math.round( b.minPrice ) ) : 'لا يوجد تسعيرات',
        };
    } );
} );

// Plans with company data (from API response)
const plansWithCompany = computed( () =>
    quotesData.value.map( plan => ( {
        ...plan,
        company: plan.company || getCompany( plan.companyId ),
    } ) )
);


// Filtered plans
const filteredPlans = computed( () => {
    return plansWithCompany.value.filter( plan => {
        // Tab filter
        if ( plan.subType !== activeTab.value ) return false;
        // Price filter
        if ( plan.annualPrice > filters.maxPrice ) return false;
        // Deductible filter
        if ( plan.deductible > filters.maxDeductible ) return false;
        // Company filter
        if ( filters.companies.length > 0 && !filters.companies.includes( plan.companyId ) ) return false;
        return true;
    } );
} );

// Sorted plans
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

// Compared plans
const comparedPlans = computed( () =>
    plansWithCompany.value.filter( p => selectedPlans.value.includes( p.id ) )
);

// Countdown timer
const countdown = reactive( { total: 15 * 60, formatted: '15:00' } );
let countdownInterval;

function updateCountdown() {
    if ( countdown.total <= 0 ) {
        clearInterval( countdownInterval );
        // Auto-refresh quotes when timer expires
        countdown.total = 15 * 60;
        countdown.formatted = '15:00';
        startLoadingQuotes();
        countdownInterval = setInterval( updateCountdown, 1000 );
        return;
    }
    countdown.total--;
    const min = Math.floor( countdown.total / 60 );
    const sec = countdown.total % 60;
    countdown.formatted = `${ String( min ).padStart( 2, '0' ) }:${ String( sec ).padStart( 2, '0' ) }`;
}

let loadingDoneTimer = null;
let updatingDoneTimer = null;

function handleVisibilityChange() {
    if ( document.hidden ) {
        clearInterval( countdownInterval );
    } else {
        countdownInterval = setInterval( updateCountdown, 1000 );
    }
}

onUnmounted( () => {
    clearInterval( countdownInterval );
    clearInterval( loadingInterval );
    clearTimeout( loadingDoneTimer );
    clearTimeout( updatingDoneTimer );
    document.removeEventListener( 'visibilitychange', handleVisibilityChange );
} );

// Actions
function resetFilters() {
    filters.maxPrice = 8000;
    filters.maxDeductible = 5000;
    filters.companies = [];
    activeTab.value = 'thirdParty';
}

async function issueQuoteLock ( selection ) {
    const subtotal = Number( selection.annualPrice || 0 ) + Number( selection.addonsTotal || 0 );
    const vat = Math.round( subtotal * 0.15 );
    const total = subtotal + vat;

    const payload = {
        plan_id: selection.id,
        plan_name: selection.name,
        insurance_company: selection.companyName || '',
        insurance_type: selection.type === 'thirdParty' ? 'third_party' : 'comprehensive',
        plan_type: selection.subType || selection.type,
        subtotal,
        vat_amount: vat,
        total,
        deductible: Number( selection.deductible || 0 ),
        addons: selection.addons || [],
        session_id: sessionStorage.getItem( 'sessionToken' ) || null,
    };

    const { data } = await request.post( '/quotes/lock', payload );
    return {
        quoteLockToken: data.quote_lock_token,
        quoteLockExpiresAt: data.expires_at,
        subtotal,
        vatAmount: vat,
        totalPrice: total,
    };
}

async function selectPlan( plan, source = 'card_expanded' ) {
    selectionError.value = '';
    trackStep( 'select_plan', 4, { selected_plan_id: plan.id, source }, 'next' );
    let lock;
    try {
        lock = await issueQuoteLock( {
            id: plan.id,
            name: plan.name,
            companyName: plan.company?.nameAr,
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

    sessionStorage.setItem( 'selectedPlan', JSON.stringify( {
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
        vatAmount: lock.vatAmount,
        totalPrice: lock.totalPrice,
    } ) );
    trackQuoteSelected( { plan_id: plan.id, source } );
    trackStepCompleted( 'compare', 'orderReview' );
    router.push( { name: 'orderReview' } );
}

function openOfferSheet( plan, source = 'offer_sheet' ) {
    offerSheetPlan.value = plan;
    offerSheetEntrySource.value = source;
    showOfferSheet.value = true;
    trackStep( 'view_offer_details', 4, { selected_plan_id: plan.id, source }, 'next' );
}

async function handleOfferSelect( selection ) {
    selectionError.value = '';
    showOfferSheet.value = false;
    const p = selection.plan;
    const source = offerSheetEntrySource.value || 'offer_sheet';
    const addons = selection.addons || [];
    const addonsTotal = addons.reduce( ( sum, a ) => sum + Number( a?.price || 0 ), 0 );

    let lock;
    try {
        lock = await issueQuoteLock( {
            id: p.id,
            name: p.name,
            companyName: p.company?.nameAr,
            type: p.type,
            subType: p.subType,
            annualPrice: Number( selection.annualPrice || p.annualPrice || 0 ),
            deductible: selection.deductible ?? p.deductible,
            addons,
            addonsTotal,
        } );
    } catch ( err ) {
        logger.error( '[ComparePage] Failed to issue quote lock (offer select):', err );
        selectionError.value = 'تعذّر تثبيت السعر. تحقق من اتصالك بالإنترنت وأعد المحاولة.';
        return;
    }

    sessionStorage.setItem( 'selectedPlan', JSON.stringify( {
        id: p.id,
        name: p.name,
        companyName: p.company?.nameAr,
        annualPrice: Number( selection.annualPrice || p.annualPrice || 0 ),
        originalPrice: Number( p.originalPrice || selection.annualPrice || p.annualPrice || 0 ),
        monthlyPrice: selection.monthlyPrice || p.monthlyPrice || Math.ceil( Number( selection.annualPrice || p.annualPrice || 0 ) / 12 ),
        type: p.type,
        deductible: selection.deductible ?? p.deductible,
        addons,
        totalPrice: lock.totalPrice,
        subtotal: lock.subtotal,
        vatAmount: lock.vatAmount,
        quoteLockToken: lock.quoteLockToken,
        quoteLockExpiresAt: lock.quoteLockExpiresAt,
    } ) );
    trackStep( 'select_plan', 4, { selected_plan_id: p.id, source }, 'next' );
    trackQuoteSelected( { plan_id: p.id, source } );
    trackStepCompleted( 'compare', 'orderReview' );
    offerSheetEntrySource.value = 'offer_sheet';
    router.push( { name: 'orderReview' } );
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
