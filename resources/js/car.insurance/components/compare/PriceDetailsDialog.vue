<template>
    <Dialog :open="open" :title="t( 'insurance.priceBreakdown' )" @update:open="$emit( 'update:open', $event )">
        <!-- Base Price -->
        <div class="flex justify-between items-center py-3">
            <span class="text-sm text-slate-600">{{ t( 'insurance.basePrice' ) }}</span>
            <span class="text-base font-semibold text-slate-900">{{ formatPrice( basePrice ) }}</span>
        </div>

        <div class="h-px bg-slate-200" />

        <!-- Modifiers -->
        <template v-if="modifiers.length > 0">
            <h4 class="text-xs font-semibold uppercase text-slate-500 mt-3 mb-1">{{ t( 'insurance.priceModifiers' ) }}</h4>
            <div v-for="modifier in modifiers" :key="modifier.id" class="flex justify-between items-center py-2">
                <span class="text-sm text-slate-600">{{ modifier.label }}</span>
                <span class="text-sm font-semibold" :class="modifier.amount > 0 ? 'text-red-600' : 'text-green-600'">
                    {{ modifier.amount > 0 ? '+' : '' }}{{ formatPrice( modifier.amount ) }}
                </span>
            </div>
            <div class="h-px bg-slate-200" />
        </template>

        <!-- Discounts -->
        <template v-if="discounts.length > 0">
            <h4 class="text-xs font-semibold uppercase text-slate-500 mt-3 mb-1">{{ t( 'insurance.discounts' ) }}</h4>
            <div v-for="discount in discounts" :key="discount.id" class="flex justify-between items-center py-2">
                <span class="text-sm text-slate-600">{{ discount.label }}</span>
                <span class="text-sm font-semibold text-green-600">-{{ formatPrice( discount.amount ) }}</span>
            </div>
            <div class="h-[2px] bg-slate-300 my-3" />
        </template>

        <!-- Final Price -->
        <div class="flex justify-between items-center py-3">
            <span class="text-lg font-bold text-slate-900">{{ t( 'insurance.finalPrice' ) }}</span>
            <span class="text-xl font-bold text-primary">{{ formatPrice( finalPrice ) }}</span>
        </div>

        <template #footer>
            <button
                class="px-4 py-2 text-sm font-medium text-slate-700 bg-slate-100 rounded-lg hover:bg-slate-200 transition-colors"
                @click="$emit( 'update:open', false )">
                {{ t( 'common.close' ) }}
            </button>
            <button
                class="px-4 py-2 text-sm font-bold text-white bg-primary rounded-lg hover:bg-primary-dark transition-colors"
                @click="$emit( 'accept', finalPrice )">
                {{ t( 'insurance.acceptAndContinue' ) }}
            </button>
        </template>
    </Dialog>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
import Dialog from '@/components/ui/Dialog.vue';

defineProps( {
    open: { type: Boolean, required: true },
    basePrice: { type: Number, required: true },
    modifiers: { type: Array, default: () => [] },
    discounts: { type: Array, default: () => [] },
    finalPrice: { type: Number, required: true },
} );

defineEmits( [ 'update:open', 'accept' ] );

const { t, locale } = useI18n( { useScope: 'global' } );

const formatPrice = ( price ) => {
    return new Intl.NumberFormat( locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 2,
    } ).format( price );
};
</script>
