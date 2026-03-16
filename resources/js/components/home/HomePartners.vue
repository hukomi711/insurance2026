<template>
    <section class="border-b border-slate-100">
        <div class="py-4 border-b border-slate-100 flex justify-center">
            <div class="flex flex-col sm:flex-row items-center gap-3 text-center sm:text-right">
                <img :src="iaBadgeSrc" alt="هيئة التأمين" class="h-8 w-auto shrink-0" width="100" height="32" />
                <span class="text-sm font-medium text-muted leading-snug">
                    شركاء<br />
                    <span class="typ-t3 text-foreground font-bold">التأمين</span>
                </span>
            </div>
        </div>
        <div class="relative w-full py-4 overflow-hidden">
            <div
                class="absolute inset-y-0 right-0 w-16 sm:w-24 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none">
            </div>
            <div
                class="absolute inset-y-0 left-0 w-16 sm:w-24 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none">
            </div>
            <div class="flex animate-marquee whitespace-nowrap">
                <div v-for="(logo, i) in duplicatedLogos" :key="i"
                    class="inline-flex items-center justify-center mx-5 flex-shrink-0 grayscale hover:grayscale-0 opacity-50 hover:opacity-100 transition-all duration-300">
                    <img :src="logo.src" :alt="logo.name" loading="lazy" class="h-8 sm:h-10 w-auto object-contain" width="120" height="40" />
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import iaBadgeSrc from '@/../../resources/images/icons/Insurnace-comp.webp';

const logoFiles = import.meta.glob( '../../../images/logo/insurance_company/*.png', { eager: true, import: 'default' } );

function logoSrc( filename ) {
    const key = Object.keys( logoFiles ).find( k => k.endsWith( '/' + filename ) );
    return key ? logoFiles[ key ] : '';
}

const partnerLogos = [
    { name: 'التعاونية', src: logoSrc( 'tawuniya.png' ) },
    { name: 'الراجحي', src: logoSrc( 'alrajhi.png' ) },
    { name: 'ولاء', src: logoSrc( 'walaa.png' ) },
    { name: 'ميدغلف', src: logoSrc( 'medgulf.png' ) },
    { name: 'ملاذ', src: logoSrc( 'malath.png' ) },
    { name: 'GIG', src: logoSrc( 'GIG.png' ) },
    { name: 'سلامة', src: logoSrc( 'salama.png' ) },
    { name: 'الجزيرة', src: logoSrc( 'ALJAZIRA.png' ) },
    { name: 'أمانة', src: logoSrc( 'amana.png' ) },
    { name: 'الاتحاد', src: logoSrc( 'al_etihad.png' ) },
    { name: 'الصقر', src: logoSrc( 'Alsagr.png' ) },
    { name: 'ACIG', src: logoSrc( 'ACIG.png' ) },
    { name: 'الدرع العربي', src: logoSrc( 'arabian_shield.png' ) },
    { name: 'المتكاملة', src: logoSrc( 'Mutakamela.png' ) },
    { name: 'الخليج العام', src: logoSrc( 'gulf_general.png' ) },
    { name: 'ليفا', src: logoSrc( 'Liva.png' ) },
    { name: 'الإنماء طوكيو', src: logoSrc( 'Alinma_Tokio.png' ) },
    { name: 'الوطنية', src: logoSrc( 'WAataniya.png' ) },
];

const duplicatedLogos = [ ...partnerLogos, ...partnerLogos ];
</script>
