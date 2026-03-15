<template>
    <section data-section="hero"
        class="flex justify-center items-center bg-blue-100 border-y border-slate-100 overflow-hidden py-6 lg:py-12">
        <div class="box flex flex-col-reverse justify-between lg:flex-row gap-3 lg:gap-6 lg:items-center">
            <div class="pt-4 pb-8 lg:py-12 lg:w-[56%]">
                <h1 class="text-2xl lg:text-4xl font-bold text-slate-900 mb-2 !leading-snug">
                    أول منصة لتأمين السيارات في السعودية
                </h1>
                <p class="text-slate-600 text-base lg:text-xl mb-6 leading-relaxed">
                    جميع وأفضل شركات التأمين… في مكان واحد، لمجموعة واسعة من الخيارات وإصدار فوري لوثائق التأمين
                </p>
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <router-link to="/motorapp"
                        class="w-full max-w-56 px-8 py-4 text-center font-black bg-primary hover:bg-primary-dark text-white rounded-md cursor-pointer transition-all duration-200 active:scale-[0.97]">
                        ابدأ الآن
                    </router-link>
                </div>
                <span class="flex items-center text-slate-600 text-sm flex-wrap mt-4">
                    هل تود شراء سيارة وترغب في معرفة كل شيء عنها قبل الشراء؟
                    <router-link to="/motorapp" class="flex items-center underline mx-1 text-blue-600">
                        تقرير موجز
                        <img :src="mojazLogoSrc" alt="موجز" loading="lazy" width="30" height="30" decoding="async"
                            class="w-[30px] mx-2" />
                    </router-link>
                </span>
            </div>
            <div class="w-full lg:w-[44%]">
                <div class="relative flex justify-center items-center mx-auto">
                    <img :src="heroImgSrc" loading="eager" fetchpriority="high" decoding="async"
                        alt="أول منصة لتأمين السيارات في السعودية"
                        class="relative w-full h-auto rtl:scale-x-[-1] max-w-[280px] sm:max-w-[360px] md:max-w-[420px] lg:max-w-full" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import mojazLogoSrc from '@/../../resources/images/motorapp/mojaz.webp';
import heroImgSrc from '@/../../resources/images/banners/motor-hero-en.webp';
</script>
