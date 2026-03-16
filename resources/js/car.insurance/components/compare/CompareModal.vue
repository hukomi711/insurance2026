<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 animate-fade-in" />
            <DialogContent
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white rounded-2xl max-w-4xl w-[95vw] max-h-[90vh] overflow-y-auto p-6 shadow-xl animate-scale-in"
                dir="rtl">
                <div class="flex items-center justify-between mb-6">
                    <DialogTitle class="typ-h2 font-bold">مقارنة العروض</DialogTitle>
                    <DialogClose class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </DialogClose>
                </div>
                <DialogDescription class="sr-only">مقارنة تفصيلية بين خطط التأمين المختارة</DialogDescription>

                <div class="overflow-x-auto">
                    <table class="w-full typ-b3">
                        <thead>
                            <tr class="border-b border-slate-200">
                                <th class="py-3 px-4 text-right typ-s2 text-muted">المعيار</th>
                                <th v-for="plan in comparedPlans" :key="plan.id" class="py-3 px-4 text-center">
                                    <img :src="getCompanyLogo( plan.companyId )" :alt="plan.company.nameAr"
                                        class="w-10 h-10 mx-auto rounded-lg object-contain border border-slate-100 mb-1" width="40" height="40" />
                                    <span class="typ-t3 text-foreground font-bold block">{{ plan.company.nameAr }}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 px-4 typ-s2 text-foreground">نوع التغطية</td>
                                <td v-for="plan in comparedPlans" :key="plan.id" class="py-3 px-4 text-center">
                                    <span class="typ-c1 font-bold px-2 py-0.5 rounded-full"
                                        :class="getCoverageClass( plan.subType )">{{ plan.typeAr }}</span>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-100 bg-blue-50/50">
                                <td class="py-3 px-4 typ-s2 text-foreground font-bold">السعر السنوي</td>
                                <td v-for="plan in comparedPlans" :key="plan.id" class="py-3 px-4 text-center">
                                    <div class="flex items-baseline justify-center gap-1">
                                        <span
                                            class="font-extrabold text-primary ltr-nums typ-t1">{{ formatNumber( plan.annualPrice ) }}</span>
                                        <SarIcon className="size-3 text-primary" />
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 px-4 typ-s2 text-foreground">مبلغ التحمل</td>
                                <td v-for="plan in comparedPlans" :key="plan.id"
                                    class="py-3 px-4 text-center ltr-nums font-bold">
                                    {{ formatNumber( plan.deductible ) }}
                                    <SarIcon className="size-2.5 text-muted inline-block align-middle" />
                                </td>
                            </tr>
                            <tr class="border-b border-slate-100">
                                <td class="py-3 px-4 typ-s2 text-foreground">التقييم</td>
                                <td v-for="plan in comparedPlans" :key="plan.id"
                                    class="py-3 px-4 text-center ltr-nums">
                                    <span class="text-orange-400">⭐</span> {{ plan.company.rating }}
                                </td>
                            </tr>
                            <tr>
                                <td class="py-3 px-4 typ-s2 text-foreground align-top">المزايا</td>
                                <td v-for="plan in comparedPlans" :key="plan.id" class="py-3 px-4">
                                    <ul class="space-y-1">
                                        <li v-for="( benefit, i ) in plan.benefits" :key="i"
                                            class="typ-c1 flex items-start gap-1">
                                            <svg class="w-3.5 h-3.5 text-secondary shrink-0 mt-0.5" fill="currentColor"
                                                viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ benefit }}
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import {
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
} from 'radix-vue';
import SarIcon from '@/components/SarIcon.vue';
import { formatNumber } from '@/utils/formatters';
import { getCompanyLogo } from '@/utils/companyLogos';

defineProps( {
    comparedPlans: { type: Array, required: true },
} );

const open = defineModel( 'open', { type: Boolean, default: false } );

function getCoverageClass( subType ) {
    const map = {
        thirdParty: 'bg-green-100 text-green-700',
        thirdPartyPlus: 'bg-emerald-100 text-emerald-700',
        vehicleDamagePlus: 'bg-blue-100 text-blue-700',
        comprehensive: 'bg-purple-100 text-purple-700',
    };
    return map[ subType ] || 'bg-slate-100 text-slate-600';
}
</script>
