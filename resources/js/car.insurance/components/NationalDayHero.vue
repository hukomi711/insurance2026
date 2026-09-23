<template>
    <section class="national-day-hero" aria-labelledby="national-day-title">
        <div class="national-day-hero__glow" aria-hidden="true"></div>
        <div class="national-day-hero__content">
            <div class="national-day-hero__copy">
                <img
                    :src="nationalDayLogo"
                    class="national-day-hero__eyebrow-logo"
                    alt="هوية اليوم الوطني السعودي"
                    width="206"
                    height="122"
                />
                <h1 id="national-day-title" class="national-day-hero__title">
                    نؤمّن طريقك<br /><span>ونحتفل بوطننا</span>
                </h1>
                <p class="national-day-hero__description">
                    قارن عروض تأمين المركبات من شركات موثوقة، واختر التغطية الأنسب لك
                    في تجربة رقمية سريعة وآمنة تليق بطموح الوطن.
                </p>
                <div class="national-day-hero__actions">
                    <router-link :to="{ name: 'motorapp' }" class="national-day-hero__primary-action">
                        ابدأ تأمين مركبتك
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
                    </router-link>
                </div>
            </div>
        </div>
        <div class="national-day-hero__bottom-line" aria-hidden="true"></div>
    </section>
</template>

<script setup>
import nationalDayLogo from '../../../images/national-day/national-day-logo-white.png';
</script>

<style scoped>
.national-day-hero {
    --nd-green: #1b8354;
    --nd-dark: #092a1e;
    position: relative;
    isolation: isolate;
    min-height: 520px;
    overflow: hidden;
    color: #fff;
    background-color: var(--nd-dark);
    background-image: linear-gradient(90deg, rgba(5, 34, 24, .94), rgba(9, 42, 30, .76) 54%, rgba(9, 42, 30, .35)), url('../../../images/national-day/hero-section.svg');
    background-position: center;
    background-size: cover;
}

.national-day-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: -1;
    background-image: radial-gradient(circle at 15% 25%, rgba(255, 255, 255, .1) 0 2px, transparent 3px);
    background-size: 30px 30px;
    mask-image: linear-gradient(to left, #000, transparent 65%);
    opacity: .45;
}

.national-day-hero__glow {
    position: absolute;
    inset-inline-end: -8rem;
    top: -12rem;
    z-index: -1;
    width: 38rem;
    height: 38rem;
    border-radius: 50%;
    background: rgba(62, 196, 122, .18);
    filter: blur(18px);
}

.national-day-hero__content {
    width: min(100% - 2rem, 80rem);
    min-height: 520px;
    margin-inline: auto;
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-items: center;
    gap: 3rem;
    padding-block: 4.25rem 4.75rem;
}

.national-day-hero__copy { position: relative; z-index: 2; max-width: 44rem; }
.national-day-hero__eyebrow-logo {
    display: block;
    width: clamp(10.5rem, 21vw, 13rem);
    height: auto;
    margin-bottom: 1.25rem;
    object-fit: contain;
    object-position: right center;
}

.national-day-hero__title {
    margin: 0;
    font-size: clamp(2.45rem, 5vw, 4.8rem);
    line-height: 1.14;
    font-weight: 900;
    letter-spacing: -.045em;
}

.national-day-hero__title span { color: #b8f2cf; }
.national-day-hero__description {
    max-width: 42rem;
    margin: 1.45rem 0 0;
    color: rgba(255, 255, 255, .82);
    font-size: clamp(1rem, 1.7vw, 1.22rem);
    line-height: 2;
}

.national-day-hero__actions { display: flex; flex-wrap: wrap; gap: .85rem; margin-top: 1.9rem; }
.national-day-hero__primary-action,
.national-day-hero__secondary-action {
    min-height: 3.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .7rem;
    padding: .8rem 1.35rem;
    border-radius: .75rem;
    font-weight: 800;
    transition: transform 180ms ease, background-color 180ms ease, box-shadow 180ms ease;
}

.national-day-hero__primary-action {
    background: #fff;
    color: var(--nd-dark);
    box-shadow: 0 12px 30px rgba(0, 0, 0, .2);
}

.national-day-hero__primary-action svg {
    width: 1.15rem;
    fill: none;
    stroke: currentColor;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-width: 2;
    transform: scaleX(-1);
}

.national-day-hero__secondary-action {
    border: 1px solid rgba(255, 255, 255, .48);
    background: rgba(255, 255, 255, .08);
    color: #fff;
    backdrop-filter: blur(8px);
}

.national-day-hero__primary-action:hover,
.national-day-hero__secondary-action:hover { transform: translateY(-2px); }
.national-day-hero__secondary-action:hover { background: rgba(255, 255, 255, .15); }
.national-day-hero__primary-action:focus-visible,
.national-day-hero__secondary-action:focus-visible { outline: 3px solid #f6c453; outline-offset: 3px; }

.national-day-hero__bottom-line {
    position: absolute;
    inset-inline: 0;
    bottom: 0;
    height: .45rem;
    background: linear-gradient(90deg, #d4af37 0 18%, #fff 18% 21%, var(--nd-green) 21%);
}

@media (max-width: 900px) {
    .national-day-hero__content { grid-template-columns: 1fr; gap: 1.4rem; padding-block: 3rem 4rem; }
}

@media (max-width: 560px) {
    .national-day-hero { min-height: auto; }
    .national-day-hero__content { width: min(100% - 1.5rem, 80rem); min-height: auto; padding-block: 2.4rem 3.4rem; }
    .national-day-hero__eyebrow-logo { width: min(68vw, 14rem); margin-bottom: 1rem; }
    .national-day-hero__title { font-size: clamp(2.1rem, 12vw, 3rem); }
    .national-day-hero__description { font-size: .95rem; line-height: 1.85; }
    .national-day-hero__actions { align-items: stretch; flex-direction: column; }
    .national-day-hero__primary-action, .national-day-hero__secondary-action { width: 100%; }
}

@media (prefers-reduced-motion: reduce) {
    .national-day-hero__primary-action, .national-day-hero__secondary-action { transition: none; }
}
</style>
