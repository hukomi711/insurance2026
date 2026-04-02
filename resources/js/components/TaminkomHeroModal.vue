<template>
    <DialogRoot v-model:open="isOpen">
        <DialogPortal>
            <DialogOverlay class="fixed inset-0 bg-black/50 z-50 animate-fade-in" />
            <DialogContent
                class="fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white sm:rounded-2xl w-full max-w-lg lg:min-w-[800px] max-h-[calc(100vh-4rem)] shadow-xl animate-scale-in"
                dir="rtl">
                <!-- Scrollable area -->
                <div class="overflow-y-auto max-h-[calc(100vh-4rem)] p-6 no-scrollbar">
                    <!-- Close button -->
                    <DialogClose
                        class="absolute top-4 start-4 z-10 p-2 bg-white/80 backdrop-blur rounded-full hover:bg-slate-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </DialogClose>

                    <DialogTitle class="sr-only">تأمينكم هيرو</DialogTitle>
                    <DialogDescription class="sr-only">تفاصيل خدمة تأمينكم هيرو المجانية</DialogDescription>

                    <!-- Banner Image -->
                    <img :src="heroBanner" alt="تأمينكم هيرو" class="w-full rounded-xl" loading="lazy" width="2064" height="433" />

                    <!-- Content -->
                    <section class="text-start mt-8 md:px-4 flex flex-col gap-4">
                        <h2 class="w-full text-3xl lg:text-4xl leading-[3.5rem] font-bold">
                            تأمينكم هيرو: راحة بالك تبدأ هنا
                        </h2>

                        <p class="font-bold mb-0 typ-t3">وش يقدم لك تأمينكم هيرو؟</p>
                        <p class="typ-b2 text-muted leading-relaxed">
                            تأمينكم هيرو خدمة مجانية حصريًا لعملاء تأمينكم الشامل وأضرار المركبة بلس، صممت لتوفر دعم
                            إضافي وراحة للعملاء، تتيح لهم الوصول إلى العديد من الخدمات المميزة التي لا تغطيها خطط
                            التأمين
                            التقليدية.
                        </p>

                        <!-- Feature Cards Grid -->
                        <div class="flex flex-wrap items-stretch -mx-2">
                            <div v-for="feature in heroFeatures" :key="feature.title" class="w-full md:w-1/2 px-2 my-2">
                                <div
                                    class="w-full bg-white rounded-3xl py-6 px-4 flex gap-4 items-center border border-slate-200 h-full">
                                    <div
                                        class="size-12 shrink-0 rounded-xl bg-gradient-light-blue-green flex items-center justify-center">
                                        <component :is="feature.icon" />
                                    </div>
                                    <div class="flex flex-col gap-1">
                                        <span class="font-bold typ-t3">{{ feature.title }}</span>
                                        <span class="typ-c1 text-slate-500">{{ feature.description }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>

<script setup>
import { computed, h } from 'vue';
import {
    DialogRoot, DialogPortal, DialogOverlay, DialogContent,
    DialogTitle, DialogDescription, DialogClose,
} from 'radix-vue';

const heroBanner = new URL( '../../images/motorapp/compare.webp', import.meta.url ).href;

const props = defineProps( {
    open: { type: Boolean, default: false },
} );

const emit = defineEmits( [ 'update:open' ] );

const isOpen = computed( {
    get: () => props.open,
    set: ( val ) => emit( 'update:open', val ),
} );

// Shield-style SVG icons as render functions
const ShieldSupportIcon = {
    render() {
        return h( 'span' );
    },
};

const ShieldTowingIcon = {
    render() {
        return h( 'svg', { class: 'size-6 text-primary', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
            h( 'path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12' } ),
        ] );
    },
};

const ShieldTaxiIcon = {
    render() {
        return h( 'svg', { class: 'size-6 text-primary', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
            h( 'path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z' } ),
            h( 'path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z' } ),
        ] );
    },
};

const ShieldEstimateIcon = {
    render() {
        return h( 'svg', { class: 'size-6 text-primary', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '1.5' }, [
            h( 'path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', d: 'M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z' } ),
        ] );
    },
};

const heroFeatures = [
    {
        title: 'تقديم المطالبة',
        description: 'الدعم طوال فترة الحادث وعمليات المطالبة خطوة بخطوة',
        icon: ShieldSupportIcon,
    },
    {
        title: 'خدمة سحب المركبة مجانًا',
        description: 'من موقعك إلى الورشة لتسهيل تجربتك',
        icon: ShieldTowingIcon,
    },
    {
        title: 'رحلة أوبر',
        description: 'تنقلك لوجهتك بدون متاعب وذلك بحد أقصى 20 ريال للرحلة',
        icon: ShieldTaxiIcon,
    },
    {
        title: 'ذهابك وعودتك من مركز "تقدير"',
        description: 'ننسق لك رحلة الذهاب من وإلى مركز "تقدير" بالوقت المناسب لك',
        icon: ShieldEstimateIcon,
    },
];
</script>
