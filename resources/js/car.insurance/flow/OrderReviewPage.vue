<template>
    <div class="min-h-screen bg-slate-50 pb-24 md:pb-0" dir="rtl">

        <!-- Header -->
        <div class="bg-white border-b border-slate-200 sticky top-16 z-30">
            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-3 py-3 sm:px-4">
                <router-link :to="{ name: 'compare' }"
                    class="justify-self-start inline-flex items-center gap-1 text-xs font-medium text-primary hover:text-primary-dark transition-colors">
                    <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    <span class="sm:hidden">العودة</span><span class="hidden sm:inline">العودة للعروض</span>
                </router-link>
                <h1 class="justify-self-center whitespace-nowrap text-sm sm:text-base font-bold text-foreground">مراجعة الطلب</h1>
                <div></div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="mx-auto w-full max-w-2xl px-3 py-3 sm:px-4 sm:py-6 md:py-8">
            <div class="space-y-4 sm:space-y-5 md:space-y-6">

                <!-- Signature Status - Interactive Button -->
                <button v-if="signatureStatus"
                    :disabled="!canProceedToPayment"
                    :aria-label="signatureStatus.valid ? 'تم التحقق من السعر - انقر للمتابعة للدفع' : 'تعذّر التحقق من السعر'"
                    @click="proceedToPayment"
                    class="w-full rounded-xl border px-3.5 py-3.5 text-sm transition-all hover:shadow-lg active:scale-[0.99] sm:px-4 sm:py-5 sm:text-base"
                    :class="signatureStatus.valid
                        ? 'bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 active:bg-emerald-150 cursor-pointer'
                        : 'bg-red-50 border-red-200 text-red-700 hover:bg-red-100 hover:border-red-300 opacity-50 cursor-not-allowed'">
                    <div class="flex items-center gap-2.5 font-bold">
                        <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ signatureStatusTitle }}</span>
                        <svg v-if="canProceedToPayment" class="size-5 shrink-0 ms-auto" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                    <p class="mt-1.5 text-[11px] leading-5 opacity-90 sm:text-sm">{{ signatureStatusMessage }}</p>
                </button>

                <!-- ═══ Policy Data Card ═══ -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow sm:rounded-2xl">
                    <div class="bg-primary/5 px-4 sm:px-5 py-3 sm:py-4 border-b border-primary/10">
                        <h2 class="text-sm sm:text-base font-bold text-primary flex items-center gap-2">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                            </svg>
                            بيانات وثيقة التأمين
                        </h2>
                    </div>
                    <div class="space-y-3 p-3.5 sm:p-5">
                        <!-- Company + Plan -->
                        <div class="flex items-center gap-3 pb-3 border-b border-slate-100">
                            <div v-if="companyLogo"
                                class="w-14 h-14 rounded-xl border border-slate-100 bg-white flex items-center justify-center shrink-0 overflow-hidden p-1.5">
                                <img :src="companyLogo" :alt="companyName" class="w-full h-full object-contain" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-base text-foreground">{{ companyName }}</h3>
                                <p class="text-xs text-muted mt-0.5">{{ planName }}</p>
                                <span
                                    class="inline-block mt-1 px-2 py-0.5 rounded-full text-[11px] font-semibold"
                                    :class="insuranceType === 'comprehensive' ? 'bg-emerald-50 text-emerald-700' : 'bg-blue-50 text-blue-700'">
                                    {{ insuranceTypeLabel }}
                                </span>
                            </div>
                        </div>
                        <!-- Policy details rows -->
                        <div v-if="policyRows.length" class="space-y-2.5">
                            <div v-for="item in policyRows" :key="item.label"
                                class="checkout-detail-row text-sm">
                                <span class="text-slate-500">{{ item.label }}</span>
                                <span class="checkout-detail-value font-semibold text-foreground ltr-nums">{{ item.value }}</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-500">لا توجد تفاصيل وثيقة إضافية متاحة حالياً.</p>
                    </div>
                </div>

                <!-- ═══ Vehicle Info Card ═══ -->
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-shadow sm:rounded-2xl">
                    <div class="bg-slate-50 px-4 sm:px-5 py-3 sm:py-4 border-b border-slate-100">
                        <h2 class="text-sm sm:text-base font-bold text-foreground flex items-center gap-2">
                            <svg class="size-5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 17h.01M16 17h.01M3 11l1.5-5A2 2 0 016.4 4h11.2a2 2 0 011.9 1.4L21 11M3 11v6a1 1 0 001 1h1a2 2 0 004 0h6a2 2 0 004 0h1a1 1 0 001-1v-6M3 11h18" />
                            </svg>
                            بيانات المركبة
                        </h2>
                    </div>
                    <div v-if="vehicleRows.length" class="space-y-3 p-3.5 sm:p-5">
                        <div v-for="item in vehicleRows" :key="item.label"
                            class="checkout-detail-row text-sm">
                            <span class="text-slate-500">{{ item.label }}</span>
                            <span class="checkout-detail-value font-semibold text-foreground">{{ item.value }}</span>
                        </div>
                    </div>
                    <div v-else class="p-4 sm:p-5">
                        <p class="text-sm text-slate-500">تعذّر تحميل بيانات المركبة. يمكنك العودة للعروض ثم المحاولة مرة أخرى.</p>
                    </div>
                </div>

                <!-- ═══ Price Summary Card ═══ -->
                <div class="bg-white rounded-2xl border-2 border-primary overflow-hidden shadow-md hover:shadow-lg transition-shadow">
                    <div class="bg-primary/5 px-4 sm:px-5 py-3 sm:py-4 border-b border-primary/10">
                        <h2 class="text-sm sm:text-base font-bold text-primary flex items-center gap-2">
                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            ملخص عرض السعر
                        </h2>
                    </div>
                    <div class="p-4 sm:p-5 space-y-3 sm:space-y-4">
                        <!-- Original Price (before 20% discount) -->
                        <div v-if="hasDiscount" class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">سعر الوثيقة الأساسي</span>
                            <span class="font-semibold text-slate-400 line-through ltr-nums inline-flex items-center gap-1">
                                {{ formatDecimal( originalPrice ) }}
                                <SarIcon className="size-3 text-slate-300" />
                            </span>
                        </div>

                        <!-- تأميني Discount (20%) -->
                        <div v-if="hasDiscount"
                            class="flex items-center justify-between text-sm bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2.5">
                            <span class="text-emerald-700 font-semibold flex items-center gap-1.5">
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                                </svg>
                                خصم تأميني
                            </span>
                            <span
                                class="font-bold text-emerald-700 ltr-nums inline-flex items-center gap-1">
                                -{{ formatDecimal( taminiDiscount ) }}
                                <SarIcon className="size-3 text-emerald-600" />
                            </span>
                        </div>

                        <!-- Discounted annual price -->
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">سعر الوثيقة{{ hasDiscount ? ' بعد الخصم' : '' }}</span>
                            <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                                {{ formatDecimal( annualPrice ) }}
                                <SarIcon className="size-3 text-slate-400" />
                            </span>
                        </div>

                        <!-- Add-ons (each as a line item) -->
                        <template v-if="addons.length > 0">
                            <div v-for="( addon, idx ) in addons" :key="idx"
                                class="flex items-center justify-between text-sm bg-blue-50 border border-blue-100 rounded-lg px-3 py-2.5">
                                <span class="text-blue-700 font-semibold flex items-center gap-1.5">
                                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                        stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                    {{ addon.name }}
                                </span>
                                <span class="font-bold text-blue-700 ltr-nums inline-flex items-center gap-1 shrink-0">
                                    +{{ formatDecimal( addon.price ) }}
                                    <SarIcon className="size-3 text-blue-600" />
                                </span>
                            </div>
                        </template>

                        <!-- Subtotal before VAT -->
                        <div v-if="addons.length > 0" class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">المجموع قبل الضريبة</span>
                            <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                                {{ formatDecimal( subtotalBeforeVAT ) }}
                                <SarIcon className="size-3 text-slate-400" />
                            </span>
                        </div>

                        <!-- VAT -->
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">ضريبة القيمة المضافة (15%)</span>
                            <span class="font-semibold text-foreground ltr-nums inline-flex items-center gap-1">
                                +{{ formatDecimal( vatAmount ) }}
                                <SarIcon className="size-3 text-slate-400" />
                            </span>
                        </div>

                        <!-- Divider -->
                        <hr class="border-slate-200">

                        <!-- Total -->
                        <div class="flex items-end justify-between gap-3">
                            <span class="text-base font-bold text-foreground">المبلغ الإجمالي</span>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 whitespace-nowrap text-xl font-extrabold text-primary ltr-nums sm:text-2xl">
                                {{ formatDecimal( totalPrice ) }}
                                <SarIcon className="size-4 text-primary" />
                            </span>
                        </div>

                        <!-- Savings badge -->
                        <div v-if="hasDiscount" class="text-center">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                وفّرت {{ formatDecimal( discountAmount ) }} ريال مع تأميني!
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ═══ Pay Button ═══ -->
                <button
                    :disabled="!canProceedToPayment || isProcessingPayment"
                    :aria-label="`الانتقال للدفع - ${formatDecimal(totalPrice)} ريال سعودي`"
                    :aria-busy="isProcessingPayment"
                    class="hidden md:inline-flex w-full h-16 rounded-2xl bg-primary text-white font-bold text-lg transition-all items-center justify-center gap-2.5 shadow-lg shadow-primary/20 active:scale-98"
                    :class="(canProceedToPayment && !isProcessingPayment) ? 'hover:bg-primary-dark active:bg-primary-darker cursor-pointer' : 'opacity-50 cursor-not-allowed'"
                    @click="proceedToPayment">
                    <svg v-if="!isProcessingPayment" class="size-5 sm:size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                    <svg v-else class="size-5 sm:size-6 shrink-0 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none" />
                        <path stroke="currentColor" stroke-width="2" d="M12 2a10 10 0 0 1 10 10" stroke-dasharray="15.7" stroke-dashoffset="15.7" />
                    </svg>
                    <span class="flex-1 text-center">{{ isProcessingPayment ? 'جاري معالجة الطلب...' : 'الانتقال للدفع' }}</span>
                    <span v-if="!isProcessingPayment" class="font-extrabold ltr-nums inline-flex items-center gap-1 whitespace-nowrap">{{ formatDecimal( totalPrice ) }} <SarIcon className="size-4" /></span>
                </button>

                <!-- Security note -->
                <p class="text-center text-xs text-muted flex items-center justify-center gap-1.5">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                    </svg>
                    {{ quoteExpiryMessage }} — الدفع مشفر وآمن بنسبة 100%
                </p>

            </div>
        </div>

        <div class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white/95 px-3 pt-2 backdrop-blur-md md:hidden" style="padding-bottom: max(0.75rem, env(safe-area-inset-bottom));">
            <button
                :disabled="!canProceedToPayment || isProcessingPayment"
                :aria-label="`الانتقال للدفع - ${formatDecimal(totalPrice)} ريال سعودي`"
                class="flex h-14 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 font-bold text-white shadow-lg active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                @click="proceedToPayment"
            >
                <span class="flex-1 text-center">{{ isProcessingPayment ? 'جاري معالجة الطلب...' : 'الانتقال للدفع' }}</span>
                <span v-if="!isProcessingPayment" class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap font-extrabold ltr-nums">{{ formatDecimal(totalPrice) }} <SarIcon className="size-4" /></span>
            </button>
        </div>
    </div>
</template>

<script setup>
// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 1 - IMPORTS & COMPONENTS
// ═══════════════════════════════════════════════════════════════════════════════════

import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useInsuranceStore } from '@/store/modules/insurance';
import { usePricingSignature } from '@/composables/usePricingSignature';
import { getPlanWithCompany } from '@/data';
import { getCompanyLogo } from '@/utils/companyLogos';
import { formatNumber } from '@/utils/formatters';
import SarIcon from '@/components/SarIcon.vue';

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 2 - ROUTER, STORE & COMPOSABLES
// ═══════════════════════════════════════════════════════════════════════════════════

const router = useRouter();
const insuranceStore = useInsuranceStore();
const { getQuote, verify: verifySignaturePacket, getSignaturePacket } = usePricingSignature();

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 3 - STATE: SIGNATURE & PAYMENT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Pricing signature status verification
 * @type {import('vue').Ref<{ valid: boolean; remainingSeconds: number } | null>}
 */
const signatureStatus = ref( null );

/**
 * Payment processing state (prevents double-clicks)
 * @type {import('vue').Ref<boolean>}
 */
const isProcessingPayment = ref( false );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 4 - COMPUTED: PLAN SELECTION
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get selected plan from store (source of truth)
 * @returns {Object} Selected plan data with pricing and company info
 */
const selectedPlanData = computed( () => insuranceStore.selectedPlan );

/**
 * Extract plan ID from selected plan (fallback chain)
 * @returns {string|null} Plan ID or null if not selected
 */
const planId = computed( () => selectedPlanData.value?.id || selectedPlanData.value?.planId || null );

/**
 * Fetch full plan details including company data by plan ID
 * @returns {Object|null} Plan with company details or null
 */
const plan = computed( () => planId.value ? getPlanWithCompany( planId.value ) : null );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 5 - COMPUTED: COMPANY & INSURANCE TYPE
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get company logo URL for display
 * @returns {string} Logo image URL or empty string
 */
const companyLogo = computed( () => plan.value ? getCompanyLogo( plan.value.companyId ) : '' );

/**
 * Get company name in Arabic
 * @returns {string} Company name (from plan or selected data)
 */
const companyName = computed( () => plan.value?.company?.nameAr || selectedPlanData.value?.companyName || '' );

/**
 * Get plan name/description
 * @returns {string} Plan name
 */
const planName = computed( () => plan.value?.name || selectedPlanData.value?.name || '' );

/**
 * Get insurance type (e.g. 'comprehensive', 'thirdParty')
 * @returns {string} Insurance type code
 */
const insuranceType = computed( () => plan.value?.type || selectedPlanData.value?.type || 'thirdParty' );

/**
 * Get localized insurance type label
 * @returns {string} Arabic label for insurance type
 */
const insuranceTypeLabel = computed( () =>
    insuranceType.value === 'comprehensive' ? 'تأمين شامل' : 'تأمين ضد الغير' );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 6 - COMPUTED: PRICING & DISCOUNTS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get annual premium price from selected plan or plan data
 * @returns {number} Annual price in SAR
 */
const annualPrice = computed( () => selectedPlanData.value?.annualPrice || plan.value?.annualPrice || 0 );

/**
 * Get original price before any discounts (baseline for comparison)
 * @returns {number} Original price in SAR
 */
const originalPrice = computed( () => selectedPlanData.value?.originalPrice || annualPrice.value );

/**
 * Check if selected plan has discount applied
 * @returns {boolean} True if original price > annual price
 */
const hasDiscount = computed( () => originalPrice.value > annualPrice.value );

/**
 * Calculate discount amount (original - annual)
 * @returns {number} Discount amount in SAR
 */
const discountAmount = computed( () => Math.round( ( originalPrice.value - annualPrice.value ) * 100 ) / 100 );

/**
 * Get tamini (Tamini app) specific discount
 * @returns {number} Discount amount in SAR
 */
const taminiDiscount = computed( () => discountAmount.value );

/**
 * Get array of selected add-ons
 * @returns {Array} Array of addon objects with { name, price }
 */
const addons = computed( () => selectedPlanData.value?.addons || [] );

/**
 * Calculate total cost of all add-ons
 * @returns {number} Sum of all addon prices in SAR
 */
const addonsTotal = computed( () => addons.value.reduce( ( sum, a ) => sum + Number( a?.price || 0 ), 0 ) );

/**
 * Calculate subtotal before VAT (premium + addons)
 * Priority: locked value from ComparePage > calculated from annual + addons
 * @returns {number} Subtotal in SAR
 */
const subtotalBeforeVAT = computed( () => {
    if ( selectedPlanData.value?.subtotalBeforeVAT != null ) return selectedPlanData.value.subtotalBeforeVAT;
    if ( selectedPlanData.value?.subtotal != null ) return selectedPlanData.value.subtotal;
    return annualPrice.value + addonsTotal.value;
} );

/**
 * Calculate VAT amount (15% of subtotal)
 * Priority: locked value from ComparePage > calculated 15% of subtotal
 * @returns {number} VAT amount in SAR
 */
const vatAmount = computed( () => {
    if ( selectedPlanData.value?.vatAmount != null ) return selectedPlanData.value.vatAmount;
    return Math.round( subtotalBeforeVAT.value * 0.15 * 100 ) / 100;
} );

/**
 * Calculate total price (subtotal + VAT)
 * Priority: locked value from ComparePage > calculated total
 * @returns {number} Total price in SAR
 */
const totalPrice = computed( () => {
    if ( selectedPlanData.value?.totalPrice != null ) return selectedPlanData.value.totalPrice;
    return Math.round( ( subtotalBeforeVAT.value + vatAmount.value ) * 100 ) / 100;
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 7 - COMPUTED: SIGNATURE & PAYMENT STATUS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Check if customer can proceed to payment
 * Requires: plan selected + valid signature + total price > 0
 * @returns {boolean} True if all conditions met
 */
const canProceedToPayment = computed( () =>
    Boolean( selectedPlanData.value && signatureStatus.value?.valid && totalPrice.value > 0 )
);

/**
 * Get localized title for signature status display
 * @returns {string} Status title in Arabic
 */
const signatureStatusTitle = computed( () =>
    signatureStatus.value?.valid ? 'تم التحقق من السعر' : 'تعذّر التحقق من السعر'
);

/**
 * Get localized message for signature status
 * Displays expiry time if valid, or error message if expired/invalid
 * @returns {string} Status message in Arabic
 */
const signatureStatusMessage = computed( () => {
    if ( !signatureStatus.value ) return '';
    if ( signatureStatus.value.valid ) {
        const mins = Math.max( 1, Math.ceil( ( signatureStatus.value.remainingSeconds || 0 ) / 60 ) );
        return `السعر محمي ومثبت — صالح لمدة ${ mins } دقيقة.`;
    }
    return 'انتهت صلاحية التوقيع أو تغيّر السعر. الرجاء العودة للعروض وإعادة الاختيار.';
} );

const quoteExpiryMessage = computed( () => {
    const seconds = signatureStatus.value?.remainingSeconds;
    if ( !Number.isFinite( seconds ) || seconds <= 0 ) return 'تحقق من السعر قبل الدفع';
    return `السعر محمي ومثبت — صالح لمدة ${ Math.max( 1, Math.ceil( seconds / 60 ) ) } دقيقة`;
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 8 - COMPUTED: POLICY DETAILS ROWS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Build policy details display rows
 * Includes insurance type, company, repair location, deductible, coverage limit, policy start date
 * @returns {Array} Array of { label, value } objects
 */
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

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 9 - COMPUTED: VEHICLE DETAILS ROWS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Build vehicle details display rows
 * Includes make, year, plate, sequence number, estimated value, purpose of use
 * @returns {Array} Array of { label, value } objects
 */
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

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 10 - LIFECYCLE HOOKS
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Initialize component: restore session, verify pricing signature, handle fallback navigation
 * @async
 */
onMounted( () => {
    // Restore insurance data from sessionStorage to Pinia store
    insuranceStore.hydrateFromSession();

    // Check if pricing signature is cached (from ComparePage selection)
    const signedQuote = getQuote();
    if ( signedQuote && !selectedPlanData.value ) {
        insuranceStore.setSelectedPlan( {
            ...signedQuote,
            id: signedQuote.planId,
            totalPrice: signedQuote.totalPrice,
        } );
    }

    // Verify pricing signature validity (expiry, tampering, etc.)
    signatureStatus.value = verifySignaturePacket();

    // Redirect to compare if no plan selected (user landed here directly)
    if ( !selectedPlanData.value ) {
        router.replace( { name: 'compare' } );
    }
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 11 - HELPER FUNCTIONS: FORMATTING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Format number with 2 decimal places for price display
 * @param {number} num - Number to format
 * @returns {string} Formatted number (e.g. "1234.56")
 */
function formatDecimal( num ) {
    return new Intl.NumberFormat( 'en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    } ).format( num );
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 12 - ACTION FUNCTIONS: PAYMENT FLOW
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Validate pricing signature and proceed to checkout page
 * Verifies signature validity and handles payment data persistence
 * On invalid signature: redirects to compare page
 * On valid signature: enriches plan with pricing data and navigates to checkout
 * Prevents double-clicks with isProcessingPayment flag
 *
 * @async
 * @returns {void}
 */
async function proceedToPayment() {
    // Prevent double-clicks/rapid submissions
    if ( isProcessingPayment.value ) return;

    // Set loading state
    isProcessingPayment.value = true;

    try {
        // Verify pricing signature (checks expiry and tampering)
        signatureStatus.value = verifySignaturePacket();

        // Redirect if signature invalid or expired
        if ( !signatureStatus.value?.valid ) {
            router.replace( { name: 'compare' } );
            return;
        }

        // Get signed pricing packet from composable memory
        const signaturePacket = getSignaturePacket();
        if ( !signaturePacket ) {
            router.replace( { name: 'compare' } );
            return;
        }

        // Enrich selected plan with calculated pricing for checkout page
        const paymentData = {
            ...selectedPlanData.value,
            originalPrice: originalPrice.value,
            discountAmount: discountAmount.value,
            addonsTotal: addonsTotal.value,
            subtotalBeforeVAT: subtotalBeforeVAT.value,
            vatAmount: vatAmount.value,
            totalPrice: totalPrice.value,
            pricingSignature: signaturePacket.signature,
            pricingTimestamp: signaturePacket.timestamp,
        };

        // Persist enriched plan to store for checkout page access
        insuranceStore.setSelectedPlan( paymentData );

        // Add small delay for UX feedback (ensures spinner visible)
        await new Promise( resolve => setTimeout( resolve, 300 ) );

        // Navigate to checkout page
        router.push( { name: 'checkout' } );
    } finally {
        // Reset loading state (in case of error or redirect)
        isProcessingPayment.value = false;
    }
}
</script>

<style scoped>
.checkout-detail-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 0.75rem;
}

.checkout-detail-row > :first-child {
    min-width: 0;
}

.checkout-detail-value {
    max-width: 58vw;
    overflow-wrap: anywhere;
    text-align: left;
}
</style>
