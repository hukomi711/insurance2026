<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 animate-fade-in" />
            <DialogContent
                class="fixed inset-y-0 right-0 z-50 bg-white w-[85vw] max-w-sm shadow-xl animate-slide-in-left overflow-y-auto p-5"
                dir="rtl">
                <div class="flex items-center justify-between mb-6">
                    <DialogTitle class="typ-t1 font-bold">تصفية النتائج</DialogTitle>
                    <DialogClose class="p-2 hover:bg-slate-100 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </DialogClose>
                </div>
                <DialogDescription class="sr-only">تصفية وترتيب عروض التأمين</DialogDescription>

                <!-- Sort -->
                <div class="mb-5">
                    <span class="typ-s2 text-muted block mb-2">ترتيب حسب</span>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="option in sortOptions" :key="option.value"
                            class="px-3 py-1.5 typ-c1 font-bold rounded-lg border transition-colors"
                            :class="sortBy === option.value
                                ? 'bg-primary text-white border-primary'
                                : 'bg-white text-muted border-slate-200'" @click="emit( 'update:sortBy', option.value )">
                            {{ option.label }}
                        </button>
                    </div>
                </div>

                <!-- Price Range -->
                <div class="mb-5">
                    <label for="maxPrice" class="typ-s2 text-muted block mb-2">
                        الحد الأقصى للسعر:
                        <span class="text-primary font-bold ltr-nums">{{ formatNumber( localFilters.maxPrice ) }}</span>
                    </label>
                    <input id="maxPrice" v-model.number="localFilters.maxPrice" type="range" :min="500" :max="8000"
                        step="100" name="maxPrice" class="w-full accent-primary" />
                </div>

                <!-- Company Filter -->
                <div class="mb-5">
                    <span class="typ-s2 text-muted block mb-2">شركة التأمين</span>
                    <div class="space-y-1.5">
                        <label v-for="company in companies" :key="company.id" :for="`company-${company.id}`"
                            class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-slate-50">
                            <input :id="`company-${company.id}`" v-model="localFilters.companies" type="checkbox"
                                :value="company.id" :name="`company-${company.id}`"
                                class="w-3.5 h-3.5 text-primary rounded border-slate-300" />
                            <img :src="getCompanyLogo( company.id )" :alt="company.nameAr"
                                class="w-5 h-5 rounded object-contain" width="20" height="20" />
                            <span class="typ-c1 text-foreground">{{ company.nameAr }}</span>
                        </label>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 mt-6">
                    <button class="flex-1 typ-s2 text-primary font-bold py-2.5 border border-primary rounded-xl"
                        @click="resetLocal">
                        إعادة تعيين
                    </button>
                    <button class="flex-1 typ-s2 text-white font-bold py-2.5 bg-primary rounded-xl"
                        @click="applyFilters">
                        تطبيق
                    </button>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import { ref, watch } from 'vue';
import {
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
} from 'radix-vue';
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';

const props = defineProps( {
    sortOptions: { type: Array, required: true },
    companies: { type: Array, required: true },
    filters: { type: Object, required: true },
    sortBy: { type: String, required: true },
} );

const open = defineModel( 'open', { type: Boolean, default: false } );

const emit = defineEmits( [ 'update:sortBy', 'apply-filters', 'reset-filters' ] );

const localFilters = ref( structuredClone( props.filters ) );

watch( () => props.filters, ( v ) => {
    localFilters.value = structuredClone( v );
}, { deep: true } );

function applyFilters() {
    emit( 'apply-filters', structuredClone( localFilters.value ) );
    open.value = false;
}

function resetLocal() {
    localFilters.value = { type: 'all', maxPrice: 8000, maxDeductible: 5000, companies: [] };
}
</script>
