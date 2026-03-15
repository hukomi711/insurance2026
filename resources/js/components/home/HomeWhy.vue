<template>
    <section class="why-section">
        <div class="why-header">
            <div class="why-header__anim">
                <h1 class="why-header__title">لماذا تأمينكم؟</h1>
            </div>
            <div class="why-header__anim">
                <h2 class="why-header__subtitle">اختر شريك تأمينك بذكاء</h2>
            </div>
        </div>

        <div class="why-grid">
            <div v-for="(card, idx) in cards" :key="idx" class="why-grid__item">
                <div class="why-card">
                    <div class="why-card__inner">
                        <!-- Decorative SVG -->
                        <div class="why-card__decor">
                            <svg width="152" height="44" viewBox="0 0 152 44" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path opacity="0.1"
                                    d="M6.20703 32.3583C8.83424 32.048 11.2156 33.9262 11.5259 36.5534C11.8362 39.1806 9.95801 41.562 7.3308 41.8723C4.70358 42.1826 2.32224 40.3044 2.01192 37.6772C1.7016 35.05 3.57981 32.6686 6.20703 32.3583Z"
                                    fill="#0088EB" stroke="url(#paint0_linear_0_1)" stroke-width="2.61276" />
                                <path opacity="0.1"
                                    d="M59.4072 18.0325C57.2286 17.7713 56.8727 14.7584 58.9305 13.9968L65.8565 11.4338C66.5919 11.1616 67.1131 10.5008 67.2064 9.72224L68.0853 2.3897C68.3464 0.21106 71.3594 -0.14483 72.1209 1.91302L74.684 8.839C74.9561 9.57439 75.617 10.0956 76.3955 10.1889L83.7281 11.0678C85.9067 11.3289 86.2626 14.3419 84.2047 15.1034L77.2788 17.6665C76.5434 17.9386 76.0222 18.5995 75.9288 19.378L75.05 26.7106C74.7888 28.8892 71.7759 29.2451 71.0143 27.1872L68.4513 20.2613C68.1791 19.5259 67.5183 19.0047 66.7397 18.9113L59.4072 18.0325Z"
                                    fill="#1DB97D" :class="{ 'why-card__shape--hidden': idx % 2 !== 0 && idx !== 0 }" />
                                <path opacity="0.1"
                                    d="M139.807 11.3992C139.441 10.1092 140.609 8.90692 141.909 9.23506L150.685 11.45C151.985 11.7781 152.442 13.3906 151.508 14.3525L145.202 20.8449C144.268 21.8068 142.643 21.3966 142.277 20.1066L139.807 11.3992Z"
                                    fill="#1DB97D" :class="{ 'why-card__shape--hidden': idx % 2 === 0 && idx !== 0 }" />
                                <defs>
                                    <linearGradient id="paint0_linear_0_1" x1="3.86395" y1="45.004" x2="18.0264"
                                        y2="24.1474" gradientUnits="userSpaceOnUse">
                                        <stop stop-color="#94BFFF" />
                                        <stop offset="1" stop-color="#4C94FE" />
                                    </linearGradient>
                                </defs>
                            </svg>
                        </div>

                        <!-- Icon Circle -->
                        <div class="why-card__icon">
                            <img :src="card.iconSrc" :alt="card.title" width="36" height="36" class="w-9 h-9" loading="lazy" />
                        </div>

                        <!-- Text -->
                        <p class="why-card__title">{{ card.title }}</p>
                        <span class="why-card__desc">{{ card.description }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import iconFirst from '../../../images/icons/why-first.svg';
import iconDestination from '../../../images/icons/why-destination.svg';
import iconTransparency from '../../../images/icons/why-transparency.svg';
import iconExpertise from '../../../images/icons/why-expertise.svg';
import iconRange from '../../../images/icons/why-range.svg';
import iconPricing from '../../../images/icons/why-pricing.svg';

const cards = [
    {
        title: 'الأول في المملكة',
        description: 'كمنصة رائدة لجميع أنواع التأمين',
        iconSrc: iconFirst,
    },
    {
        title: 'وجهة واحدة للتأمين',
        description: 'نقدم لك خيارات متعددة من شركات التأمين وعملية مقارنة وشراء سلسلة وسريعة تلبي احتياجاتك',
        iconSrc: iconDestination,
    },
    {
        title: 'الشفافية والوضوح',
        description: 'نأخذ الوضوح والبساطة على محمل الجد، ولهذا نضمن استخدام مصطلحات مباشرة',
        iconSrc: iconTransparency,
    },
    {
        title: 'الخبرة التقنية والإستشارية',
        description: 'كفاءة تعتمد عليها وإجابة سريعة لجميع استفساراتك',
        iconSrc: iconExpertise,
    },
    {
        title: 'النطاق الأوسع لعروض التأمين',
        description: 'نوفر لك شبكة واسعة من خيارات شركات التأمين',
        iconSrc: iconRange,
    },
    {
        title: 'تسعير موحّد',
        description: 'نضمن لك أسعار مطابقة مع شركات التأمين إن لم تكن أقل من خلال عروضنا الحصرية لعملائنا',
        iconSrc: iconPricing,
    },
];
</script>

<style scoped>
/* ===== SECTION ===== */
.why-section {
    max-width: 80rem;
    margin-left: auto;
    margin-right: auto;
    width: 100%;
    padding: 24px 16px;
}

@media (min-width: 1024px) {
    .why-section {
        padding: 56px 16px;
    }
}

/* ===== HEADER ===== */
.why-header {
    max-width: 80rem;
    margin: 0 auto 32px;
    text-align: center;
}

.why-header__anim {
    animation: whySlideIn 500ms ease-out both;
}

.why-header__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: var(--h1-font-size);
    line-height: var(--h1-line-height);
    font-weight: 700;
    margin-bottom: 8px;
    white-space: pre-line;
}

@media (min-width: 1024px) {
    .why-header__title {
        font-size: var(--d2-font-size);
        line-height: var(--d2-line-height);
        letter-spacing: var(--d2-letter-spacing);
        font-weight: 800;
    }
}

.why-header__subtitle {
    font-size: var(--h4-font-size);
    line-height: var(--h4-line-height);
    font-weight: 400;
    color: #475569;
    margin-bottom: 32px;
    white-space: pre-line;
}

/* ===== GRID ===== */
.why-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

@media (min-width: 768px) {
    .why-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* ===== GRID ITEM ===== */
.why-grid__item {
    height: 100%;
    animation: whyFadeIn 500ms ease-out both;
}

/* ===== CARD ===== */
.why-card {
    border: 1px solid transparent;
    color: hsl(var(--card-foreground));
    display: flex;
    flex-direction: column;
    gap: 8px;
    overflow: hidden;
    border-radius: 6px;
    background: #f8fafc;
    padding: 16px;
    height: 100%;
    border: none;
}

@media (min-width: 768px) {
    .why-card {
        gap: 16px;
        padding: 24px;
    }
}

.why-card__inner {
    position: relative;
    display: flex;
    flex-direction: column;
    padding: 0;
}

@media (min-width: 1024px) {
    .why-card__inner {
        padding-left: 16px;
        padding-right: 16px;
    }
}

/* Decorative SVG */
.why-card__decor {
    position: absolute;
    inset-inline-end: 0;
    top: 0;
}

/* Icon circle */
.why-card__icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    border-radius: 50%;
    aspect-ratio: 1;
    background-image: linear-gradient(151.65deg, #E0F1FD 9.99%, #D6F3FF 51.15%, #E4F9F1 91.66%);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

/* Text */
.why-card__title {
    font-size: var(--s1-font-size);
    line-height: var(--s1-line-height);
    font-weight: var(--s1-font-weight, 600);
    margin-bottom: 8px;
    font-weight: 700;
}

.why-card__desc {
    font-size: var(--s2-font-size);
    line-height: var(--s2-line-height);
    font-weight: var(--s2-font-weight, 500);
    color: #475569;
}

/* Hide decorative shapes */
.why-card__shape--hidden {
    display: none;
}

/* ===== ANIMATIONS ===== */
@keyframes whySlideIn {
    from {
        opacity: 0;
        transform: translateX(30px);
    }

    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes whyFadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}
</style>
