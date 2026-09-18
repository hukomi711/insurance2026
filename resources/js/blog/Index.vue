<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { switchLocale } from '@/i18n';
import request from '@/api/request';
import logger from '@/utils/logger';
import { getRecaptchaToken } from '@/composables/useRecaptcha';

const { locale, t } = useI18n( { useScope: 'global' } );

const _isRTL = computed( () => locale.value === 'ar' );
const _otherLang = computed( () => locale.value === 'ar' ? 'English' : 'العربية' );

function _toggleLanguage ()
{
    switchLocale();
}

// بيانات المقالات
const articles = ref( [
    {
        id: 1,
        slug: 'savings-insurance-financial-future',
        title: 'كيف تضمن مستقبلك المالي من خلال التأمين الادخاري؟',
        excerpt: 'في عالم تتسارع فيه التغيرات الاقتصادية وتزداد فيه الحاجة للتخطيط المالي السليم، أصبح التأمين الادخاري من الأدوات المهمة التي تساعد الأفراد والعائلات على تأمين مستقبلهم المالي.',
        category: 'تأمين',
        date: '2026-01-09',
        read_time: '6 دقائق',
        image: '/images/blog/2026/01/كيف-تضمن-مستقبلك-المالي-من-خلال-التأمين-الادخاري؟-1024x562.png',
    },
    {
        id: 2,
        slug: 'health-insurance-attract-talent',
        title: 'كيف يمكن للتأمين الصحي للشركات جذب المواهب في السعودية؟',
        excerpt: 'في سوق العمل السعودي التنافسي، تسعى الشركات إلى جذب أفضل المواهب والاحتفاظ بها. ومن بين أهم المزايا التي يبحث عنها الموظفون التأمين الصحي.',
        category: 'تأمين',
        date: '2026-01-09',
        read_time: '7 دقائق',
        image: '/images/blog/2026/01/كيف-يمكن-للتأمين-الصحي-للشركات-جذب-المواهب؟-1024x562.png',
    },
    {
        id: 3,
        slug: 'ai-hr-health-insurance-plans',
        title: 'إرشادات شاملة: كيف يمكن للذكاء الاصطناعي تحسين قرارات الموارد البشرية في اختيار خطط التأمين الصحي؟',
        excerpt: 'تُعدّ عملية اختيار خطط التأمين الصحي من أهم مسؤوليات إدارات الموارد البشرية، حيث يتطلب الأمر تحقيق توازن بين جودة الرعاية الصحية والتحكم في التكاليف.',
        category: 'تأمين',
        date: '2026-01-09',
        read_time: '5 دقائق',
        image: '/images/blog/2026/01/كيف-يمكن-للذكاء-الاصطناعي-تحسين-قرارات-الموارد-البشرية-في-اختيار-خطط-التأمين-الصحي؟-1024x562.png',
    },
    {
        id: 4,
        slug: 'insurance-surplus-saudi-rights',
        title: 'الفائض التأميني في السعودية: حقك وكيفية الاستفادة منه',
        excerpt: 'هل تعلم أن شركات التأمين التعاوني في المملكة العربية السعودية قد تحقق فائضًا ماليًا في نهاية السنة المالية بعد تسوية المطالبات ودفع التكاليف التشغيلية؟',
        category: 'مركبات',
        date: '2026-01-02',
        read_time: '8 دقائق',
        image: '/images/blog/2026/01/الفائض-التأميني-في-السعودية-حقك-وكيفية-الاستفادة-منه-1024x562.png',
    },
    {
        id: 5,
        slug: 'ev-charging-stations-insurance',
        title: 'محطات الشحن المنزلية للسيارات الكهربائية: هل يغطيها التأمين في السعودية؟',
        excerpt: 'مع تسارع انتشار السيارات الكهربائية في المملكة، باتت محطات الشحن المنزلية جزءًا من حياة المستخدم اليومية. ورغم هذا النمو، يظل السؤال الأهم: هل يغطي التأمين الأضرار؟',
        category: 'مركبات',
        date: '2026-01-02',
        read_time: '6 دقائق',
        image: '/images/blog/2026/01/محطات-الشحن-المنزلية-للسيارات-الكهربائية-هل-يغطيها-التأمين-في-السعودية؟-1024x562.png',
    },
    {
        id: 6,
        slug: 'vehicle-registration-renewal-absher',
        title: 'تجديد استمارة السيارة في السعودية عبر أبشر: دليلك الكامل بخطوات سهلة وسريعة',
        excerpt: 'تجديد الاستمارة أسهل مما تتخيل! فقط دقائق عبر منصة أبشر، دون أي أوراق أو زيارات مملة. تعرّف على الخطوات والرسوم والشروط.',
        category: 'مركبات',
        date: '2026-01-02',
        read_time: '7 دقائق',
        image: '/images/blog/2026/01/تجديد-استمارة-السيارة-في-السعودية-عبر-أبشر-دليلك-الكامل-بخطوات-سهلة-وسريعة-1024x562.png',
    },
] );

const categories = computed( () =>
{
    const cats = new Set( articles.value.map( a => a.category ) );
    return [ ...cats ].map( name => ( {
        name,
        count: articles.value.filter( article => article.category === name ).length,
    } ) );
} );

const featuredArticle = computed( () => articles.value[0] );

// البحث والفلترة
const searchQuery = ref( '' );
const selectedCategory = ref( '' );

const filteredArticles = computed( () =>
{
    let result = articles.value;

    if ( searchQuery.value )
    {
        const query = searchQuery.value.toLowerCase();
        result = result.filter( article =>
            article.title.toLowerCase().includes( query ) ||
            article.excerpt.toLowerCase().includes( query )
        );
    }

    if ( selectedCategory.value )
    {
        result = result.filter( article => article.category === selectedCategory.value );
    }

    return result;
} );

// النشرة البريدية
const newsletterEmail = ref( '' );
const newsletterSubmitted = ref( false );
const newsletterError = ref( '' );
const newsletterLoading = ref( false );

async function handleNewsletterSubmit ()
{
    if ( !newsletterEmail.value || newsletterLoading.value ) return;
    newsletterError.value = '';
    newsletterLoading.value = true;
    try {
        const recaptchaToken = await getRecaptchaToken( 'newsletter_submit' );
        await request.post( '/newsletter', {
            email: newsletterEmail.value,
            source: 'blog',
            recaptcha_token: recaptchaToken,
        } );
        newsletterSubmitted.value = true;
        newsletterEmail.value = '';
        newsletterTimer = setTimeout( () => { newsletterSubmitted.value = false; }, 4000 );
    } catch ( err ) {
        logger.warn( '[Newsletter] Subscription failed:', err );
        newsletterError.value = err.response?.data?.message || 'حدث خطأ — يرجى المحاولة لاحقًا';
    } finally {
        newsletterLoading.value = false;
    }
}

let newsletterTimer = null;

onMounted( () =>
{
    document.title = 'تأمين سيارات';
} );

onUnmounted( () =>
{
    clearTimeout( newsletterTimer );
} );
</script>

<template>
    <div class="py-8">
        <!-- Hero Section -->
        <section class="bg-linear-to-r from-blue-600 to-blue-800 text-white py-16 rounded-2xl mx-4 sm:mx-6 lg:mx-8 mb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-3xl md:text-5xl font-bold mb-4">
                    {{ t( 'blog.heroTitle' ) }}
                </h1>
                <p class="text-xl text-blue-100">
                    {{ t( 'blog.heroSubtitle' ) }}
                </p>
            </div>
        </section>

        <!-- Search & Filter -->
        <section class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <article v-if="featuredArticle && !searchQuery && !selectedCategory"
                    class="mb-10 grid gap-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[1.1fr_0.9fr]">
                    <div class="aspect-video bg-slate-100 lg:aspect-auto">
                        <img :src="featuredArticle.image" :alt="featuredArticle.title"
                            class="h-full w-full object-cover" loading="eager" decoding="async" width="1024"
                            height="562" fetchpriority="high" />
                    </div>
                    <div class="flex flex-col justify-center p-6 lg:p-8">
                        <div class="mb-4 flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <span class="rounded-full bg-blue-50 px-3 py-1 font-semibold text-blue-700">
                                {{ featuredArticle.category }}
                            </span>
                            <span>{{ featuredArticle.date }}</span>
                            <span>{{ featuredArticle.read_time }}</span>
                        </div>
                        <h2 class="text-2xl font-bold leading-snug text-slate-950 lg:text-3xl">
                            {{ featuredArticle.title }}
                        </h2>
                        <p class="mt-4 text-base leading-8 text-slate-600">
                            {{ featuredArticle.excerpt }}
                        </p>
                        <RouterLink :to="{ name: 'blog.show', params: { slug: featuredArticle.slug } }"
                            class="mt-6 inline-flex w-fit items-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-bold text-white transition-colors hover:bg-blue-700">
                            {{ t( 'blog.readMore' ) }}
                        </RouterLink>
                    </div>
                </article>

                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-md w-full">
                        <label for="blog-search" class="sr-only">{{ t( 'blog.searchPlaceholder' ) }}</label>
                        <input id="blog-search" v-model="searchQuery" name="blog-search" type="text"
                            :placeholder="t( 'blog.searchPlaceholder' )"
                            class="w-full ps-12 pe-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                        <svg class="absolute inset-s-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <!-- Category Filter -->
                    <div class="flex gap-2 flex-wrap justify-center">
                        <button :class="[
                            'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                            !selectedCategory
                                ? 'bg-blue-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]" @click="selectedCategory = ''">
                            {{ t( 'blog.allCategories' ) }}
                        </button>
                        <button v-for="category in categories" :key="category.name"
                            :class="[
                                'px-4 py-2 rounded-full text-sm font-medium transition-colors',
                                selectedCategory === category.name
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                            ]" @click="selectedCategory = category.name">
                            {{ category.name }}
                            <span class="ms-1 text-xs opacity-75">({{ category.count }})</span>
                        </button>
                    </div>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-3 text-sm text-slate-600 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        محتوى موجه للسوق السعودي ومراجع تشغيلية واضحة.
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        لا نعرض وعودًا مضللة أو روابط تحميل مشبوهة.
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-4 py-3">
                        روابط فهرسة واضحة عبر sitemap و robots.
                    </div>
                </div>
            </div>
        </section>

        <!-- Articles Grid -->
        <section class="py-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- No Results -->
                <div v-if="filteredArticles.length === 0" class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-700 mb-2">
                        {{ t( 'blog.noResults' ) }}
                    </h3>
                    <p class="text-gray-500">
                        {{ t( 'blog.tryDifferent' ) }}
                    </p>
                </div>

                <!-- Articles -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <article v-for="(article, index) in filteredArticles" :key="article.id"
                        class="bg-white rounded-2xl shadow-md hover:shadow-xl transition-shadow duration-300 overflow-hidden group">
                        <!-- Image -->
                        <div class="aspect-video bg-linear-to-br from-blue-400 to-blue-600 relative overflow-hidden">
                            <img v-if="article.image" :src="article.image" :alt="article.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                :loading="index === 0 ? 'eager' : 'lazy'" decoding="async" width="800" height="450"
                                :fetchpriority="index === 0 ? 'high' : undefined"
                                @error="$event.target.style.display = 'none'" />
                            <div class="absolute inset-0 bg-linear-to-t from-black/50 to-transparent"></div>
                            <span
                                class="absolute bottom-3 inset-e-3 bg-white/90 text-blue-600 px-3 py-1 rounded-full text-xs font-medium">
                                {{ article.category }}
                            </span>
                        </div>

                        <!-- Content -->
                        <div class="p-6">
                            <div class="flex items-center gap-4 text-sm text-gray-500 mb-3">
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ article.date }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ article.read_time }}
                                </span>
                            </div>

                            <h2
                                class="text-xl font-bold text-gray-800 mb-3 line-clamp-2 group-hover:text-blue-600 transition-colors">
                                {{ article.title }}
                            </h2>

                            <p class="text-gray-600 text-sm line-clamp-3 mb-4">
                                {{ article.excerpt }}
                            </p>

                            <!-- رابط المقال -->
                            <RouterLink :to="{ name: 'blog.show', params: { slug: article.slug } }"
                                class="inline-flex items-center gap-2 text-blue-600 font-medium hover:text-blue-800 transition-colors">
                                {{ t( 'blog.readMore' ) }}
                                <svg class="w-4 h-4 rtl:rotate-180" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </RouterLink>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="py-16 mt-8 bg-gray-100 rounded-2xl mx-4 sm:mx-6 lg:mx-8">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">
                    {{ t( 'blog.newsletter.title' ) }}
                </h2>
                <p class="text-gray-600 mb-8">
                    {{ t( 'blog.newsletter.subtitle' ) }}
                </p>
                <form class="flex flex-col sm:flex-row gap-4 max-w-md mx-auto" @submit.prevent="handleNewsletterSubmit">
                    <label for="newsletter-email" class="sr-only">{{ t( 'blog.newsletter.placeholder' ) }}</label>
                    <input id="newsletter-email" v-model="newsletterEmail" name="newsletter-email" type="email"
                        :placeholder="t( 'blog.newsletter.placeholder' )" autocomplete="email" required
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    <button type="submit" :disabled="newsletterLoading"
                        class="px-6 py-3 bg-blue-600 text-white font-medium rounded-xl hover:bg-blue-700 disabled:bg-slate-400 disabled:cursor-not-allowed transition-colors">
                        {{ newsletterLoading ? 'جاري الإرسال...' : t( 'blog.newsletter.button' ) }}
                    </button>
                </form>
                <p v-if="newsletterSubmitted" class="mt-4 text-green-600 font-medium">
                    {{ t( 'blog.newsletter.success' ) }}
                </p>
                <p v-if="newsletterError" class="mt-4 text-red-600 font-medium">
                    {{ newsletterError }}
                </p>
            </div>
        </section>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
