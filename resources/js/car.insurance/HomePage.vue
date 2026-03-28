<template>
    <div>
        <HeroSection />
        <HomePartners />
        <HomePlans />
        <HomeWhy />
        <StickyMobileCta />
    </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue';
import HeroSection from '@/components/home/HeroSection.vue';
import HomePartners from '@/components/home/HomePartners.vue';
import HomePlans from '@/components/home/HomePlans.vue';
import HomeWhy from '@/components/home/HomeWhy.vue';
import StickyMobileCta from '@/components/home/StickyMobileCta.vue';

/** Track dynamically created meta/script elements for cleanup */
const _createdElements = [];

onMounted( () => {
    document.title = 'تأمينكم - مقارنة أسعار التأمين | منصة تأمين في السعودية';

    setMeta( 'description', 'قارن أسعار تأمين السيارات من أكثر من 18 شركة تأمين معتمدة في السعودية. احصل على أفضل عرض تأمين شامل أو ضد الغير في دقائق.' );
    setMeta( 'keywords', 'تأمين سيارات, مقارنة تأمين, تأمين شامل, تأمين ضد الغير, تأمينكم, تأمين السعودية' );

    setMeta( 'og:title', 'تأمينكم - مقارنة أسعار التأمين', 'property' );
    setMeta( 'og:description', 'قارن أسعار تأمين السيارات من أكثر من 18 شركة تأمين معتمدة في السعودية.', 'property' );
    setMeta( 'og:type', 'website', 'property' );
    setMeta( 'og:locale', 'ar_SA', 'property' );

    injectJsonLd();
} );

function setMeta( name, content, attr = 'name' ) {
    let el = document.querySelector( `meta[${ attr }="${ name }"]` );
    if ( !el ) {
        el = document.createElement( 'meta' );
        el.setAttribute( attr, name );
        document.head.appendChild( el );
        _createdElements.push( el );
    }
    el.setAttribute( 'content', content );
}

function injectJsonLd() {
    if ( document.querySelector( 'script[data-home-jsonld]' ) ) return;
    const script = document.createElement( 'script' );
    script.type = 'application/ld+json';
    script.setAttribute( 'data-home-jsonld', '' );
    script.textContent = JSON.stringify( {
        '@context': 'https://schema.org',
        '@type': 'WebApplication',
        name: 'تأمينكم',
        url: 'https://tamicomz.store',
        applicationCategory: 'FinanceApplication',
        operatingSystem: 'Web, iOS, Android',
        description: 'منصة مقارنة أسعار تأمين السيارات في السعودية',
        aggregateRating: {
            '@type': 'AggregateRating',
            ratingValue: '4.7',
            ratingCount: '200000',
        },
    } );
    document.head.appendChild( script );
    _createdElements.push( script );
}

onUnmounted( () => {
    _createdElements.forEach( el => el.remove() );
    _createdElements.length = 0;
} );
</script>
