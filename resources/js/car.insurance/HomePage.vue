<template>
    <div dir="rtl" class="national-day-home">
        <PricingBanner />
        <NationalDayHero />
        <HomePartners />
        <img
            :src="sectionPattern"
            alt=""
            class="national-day-home__pattern"
            width="1440"
            height="257"
            loading="lazy"
        />
        <div id="national-day-plans" class="national-day-home__plans">
            <HomePlans />
        </div>
        <HomeWhy />
        <StickyMobileCta />
    </div>
</template>

<script setup>
import PricingBanner from '@/components/home/PricingBanner.vue';
import HomePartners from '@/components/home/HomePartners.vue';
import HomePlans from '@/components/home/HomePlans.vue';
import HomeWhy from '@/components/home/HomeWhy.vue';
import StickyMobileCta from '@/components/home/StickyMobileCta.vue';
import NationalDayHero from '@/car.insurance/components/NationalDayHero.vue';
import sectionPattern from '../../images/national-day/section-pattern.svg';
</script>

<style scoped>
.national-day-home {
    --national-green: #1b8354;
    --national-green-dark: #092a1e;
    overflow: hidden;
    background: #fff;
}

.national-day-home__pattern {
    display: block;
    width: 100%;
    height: clamp(4.5rem, 12vw, 9rem);
    object-fit: cover;
    object-position: center;
    background: #fff;
}

.national-day-home__plans { scroll-margin-top: 5rem; }
.national-day-home :deep(.bg-yellow-400) { background-color: var(--national-green) !important; }
.national-day-home :deep(.bg-yellow-400 .text-slate-900) { color: #fff !important; }
.national-day-home :deep(.bg-primary) { background-color: var(--national-green) !important; }
.national-day-home :deep(.hover\:bg-primary-dark:hover) { background-color: var(--national-green-dark) !important; }
.national-day-home :deep(.text-primary) { color: var(--national-green) !important; }
.national-day-home :deep(.border-primary) { border-color: var(--national-green) !important; }
.national-day-home :deep(.ring-primary\/20) { --tw-ring-color: rgba(27, 131, 84, .2) !important; }
.national-day-home :deep(.shadow-primary\/20) { --tw-shadow-color: rgba(27, 131, 84, .2) !important; }
.national-day-home :deep(.text-secondary) { color: var(--national-green) !important; }

.national-day-home :deep(.why-card) {
    border: 1px solid rgba(27, 131, 84, .12);
    background: linear-gradient(145deg, #fff, #f0fdf6);
}

.national-day-home :deep(.why-card__icon) {
    background-image: linear-gradient(145deg, #dcfce7, #f0fdf4);
}
</style>
