<template>
    <aside class="hidden xl:flex flex-col gap-4">

        <!-- Vehicle Info Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="typ-t3 font-bold text-foreground mb-3 flex items-center gap-2">
                <svg class="size-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                </svg>
                معلومات المركبة
            </h3>

            <!-- License Plate Display -->
            <div class="border-2 border-primary/30 rounded-lg overflow-hidden mb-4">
                <div class="bg-primary/5 px-3 py-2.5 flex items-center justify-between">
                    <span class="typ-c1 text-muted">رقم اللوحة</span>
                    <span
                        class="typ-t2 font-extrabold text-primary ltr-nums">{{ vehicleInfo.plateNumber || '---' }}</span>
                </div>
                <div class="px-3 py-2.5 flex items-center justify-between border-t border-primary/10">
                    <span class="typ-c1 text-muted">الرقم التسلسلي</span>
                    <span
                        class="typ-t3 font-bold text-foreground ltr-nums">{{ vehicleInfo.sequenceNumber || '---' }}</span>
                </div>
            </div>

            <div class="space-y-2.5 typ-c1">
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">الشركة المصنعة</span>
                    <span class="font-bold text-foreground">{{ vehicleInfo.makeName }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-500">سنة الصنع</span>
                    <span class="font-bold text-foreground ltr-nums">{{ vehicleInfo.year || '---' }}</span>
                </div>
            </div>
        </div>

        <!-- NCD Discount Banner -->
        <div v-if="hasNcdDiscount"
            class="bg-gradient-to-l from-green-50 to-emerald-50 rounded-xl border border-green-200 p-4 flex items-center gap-3">
            <div class="size-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <svg class="size-5 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <p class="typ-t3 font-bold text-green-900">خصم عدم وجود مطالبات</p>
                <p class="typ-c1 text-green-700">تم تطبيق خصم NCD بنسبة {{ ncdDiscountPercent }}% على أسعارك</p>
            </div>
        </div>

        <!-- Sort & Filter Panel -->
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="typ-t3 font-bold text-foreground mb-3 flex items-center gap-2">
                <svg class="size-5 text-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                </svg>
                الترتيب و التصنيف
            </h3>

            <!-- Sort Buttons -->
            <div class="mb-4">
                <span class="typ-c1 text-muted block mb-2">ترتيب حسب</span>
                <div class="flex flex-wrap gap-1.5">
                    <button v-for="option in sortOptions" :key="option.value"
                        class="px-3 py-1.5 typ-c1 font-bold rounded-lg border transition-colors"
                        :class="sortBy === option.value
                            ? 'bg-primary text-white border-primary'
                            : 'bg-white text-muted border-slate-200 hover:border-slate-300'" @click="$emit('update:sortBy', option.value)">
                        {{ option.label }}
                    </button>
                </div>
            </div>

            <!-- Price Range -->
            <div class="mb-4">
                <label for="maxPriceDesktop" class="typ-c1 text-muted block mb-1.5">
                    الحد الأقصى للسعر:
                    <span class="text-primary font-bold ltr-nums">{{ formatNumber(filters.maxPrice) }}</span>
                </label>
                <input id="maxPriceDesktop" :value="filters.maxPrice" type="range" :min="500" :max="8000"
                    step="100" name="maxPriceDesktop" class="w-full accent-primary"
                    @input="emit('update:filters', { ...filters, maxPrice: +$event.target.value })" />
            </div>

            <!-- Company Filter -->
            <div class="mb-3">
                <span class="typ-c1 text-muted block mb-1.5">شركة التأمين</span>
                <div class="space-y-1">
                    <label v-for="company in companies" :key="company.id"
                        :for="`company-desktop-${company.id}`"
                        class="flex items-center gap-2 cursor-pointer py-1 px-2 rounded-lg hover:bg-slate-50">
                        <input :id="`company-desktop-${company.id}`" type="checkbox"
                            :checked="filters.companies.includes(company.id)"
                            :name="`company-desktop-${company.id}`"
                            class="w-3.5 h-3.5 text-primary rounded border-slate-300"
                            @change="emit('update:filters', {
                                ...filters,
                                companies: $event.target.checked
                                    ? [...filters.companies, company.id]
                                    : filters.companies.filter(id => id !== company.id),
                            })" />
                        <img :src="getCompanyLogo(company.id)" :alt="company.nameAr"
                            class="size-5 rounded object-contain" width="20" height="20" />
                        <span class="typ-c1 text-foreground">{{ company.nameAr }}</span>
                    </label>
                </div>
            </div>

            <button class="w-full typ-c1 text-primary font-bold hover:underline py-1" @click="$emit('reset-filters')">
                إعادة تعيين الفلاتر
            </button>
        </div>

        <!-- Hero Promo -->
        <div class="bg-gradient-to-l from-blue-50 to-indigo-50 rounded-xl border border-blue-200 p-4 cursor-pointer hover:shadow-md transition-shadow"
            @click="$emit('show-hero')">
            <div class="flex items-center gap-3 mb-2">
                <div class="size-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                    <svg class="size-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <p class="typ-t3 font-bold text-blue-900">تأمينكم هيرو</p>
                    <p class="typ-c1 text-blue-700">دعم فوري بعد الحوادث</p>
                </div>
            </div>
            <p class="typ-c1 text-blue-600">تعرف على المزيد ←</p>
        </div>
    </aside>
</template>

<script setup>
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';

defineProps({
    vehicleInfo: { type: Object, required: true },
    hasNcdDiscount: { type: Boolean, default: false },
    ncdDiscountPercent: { type: [Number, String], default: 0 },
    sortOptions: { type: Array, required: true },
    sortBy: { type: String, required: true },
    filters: { type: Object, required: true },
    companies: { type: Array, required: true },
});

const emit = defineEmits(['update:sortBy', 'update:filters', 'reset-filters', 'show-hero']);
</script>
