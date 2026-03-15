<template>
    <div class="faq-page">
        <!-- Page Header with Search -->
        <div class="faq-header">
            <div class="faq-header__inner">
                <h1 class="faq-header__title">كيف نقدر نساعدك؟</h1>
                <p class="faq-header__subtitle">ابحث في الأسئلة الشائعة أو تصفّح حسب الفئة</p>

                <!-- Search Box -->
                <div class="faq-search">
                    <svg class="faq-search__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input id="faq-search" v-model="searchQuery" type="text" name="faq-search"
                        autocomplete="off" aria-label="بحث في الأسئلة الشائعة" placeholder="ابحث عن سؤالك هنا..."
                        class="faq-search__input" />
                    <button v-if="searchQuery" class="faq-search__clear" @click="searchQuery = ''">
                        <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <section class="faq-content">
            <div class="faq-content__inner">

                <!-- Search Results Mode -->
                <div v-if="searchQuery.trim()">
                    <p style="font-size: 14px; color: var(--color-muted); margin-bottom: 24px;">
                        <span v-if="searchResults.length">{{ searchResults.length }} نتيجة لـ "{{ searchQuery
                        }}"</span>
                        <span v-else>لا توجد نتائج لـ "{{ searchQuery }}"</span>
                    </p>

                    <!-- Search Results Accordion -->
                    <AccordionRoot v-if="searchResults.length" type="multiple" class="faq-accordion" dir="rtl">
                        <AccordionItem v-for="(item, i) in searchResults" :key="'s-' + i" :value="'s-' + i"
                            class="faq-accordion__item">
                            <AccordionHeader as-child>
                                <AccordionTrigger class="faq-accordion__trigger">
                                    <span class="faq-accordion__question">{{ item.q }}</span>
                                    <svg class="faq-accordion__chevron" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </AccordionTrigger>
                            </AccordionHeader>
                            <AccordionContent class="faq-accordion__content">
                                <div class="faq-accordion__answer">{{ item.a }}</div>
                            </AccordionContent>
                        </AccordionItem>
                    </AccordionRoot>

                    <!-- Empty State -->
                    <div v-else style="text-align: center; padding: 48px 0;">
                        <div
                            style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <svg style="width: 32px; height: 32px; color: #94a3b8;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p style="color: var(--color-muted); font-size: 14px;">جرّب بكلمات مختلفة أو تصفّح الفئات أدناه
                        </p>
                        <button style="margin-top: 16px; color: var(--color-primary); font-size: 14px; font-weight: 500; background: none; border: none; cursor: pointer;"
                            @click="searchQuery = ''">
                            عرض جميع الأسئلة
                        </button>
                    </div>
                </div>

                <!-- Category Browse Mode -->
                <div v-else>
                    <!-- Category Tabs -->
                    <div class="faq-tabs">
                        <button v-for="category in categories" :key="category" class="faq-tabs__btn"
                            :class="{ 'faq-tabs__btn--active': activeCategory === category }" @click="activeCategory = category">
                            {{ category }}
                        </button>
                    </div>

                    <!-- All Categories View -->
                    <div v-if="activeCategory === 'الكل'" class="faq-groups">
                        <div v-for="group in faqItems" :key="group.category" class="faq-group">
                            <!-- Category Header -->
                            <div class="faq-group__header">
                                <div class="faq-group__icon">
                                    <svg style="width: 18px; height: 18px; color: var(--color-primary);" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="group.category === 'عام'" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        <path v-else-if="group.category === 'تأمين المركبات'" stroke-linecap="round"
                                            stroke-linejoin="round" stroke-width="2"
                                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10 M17.5 16H13V8h2l3 3v5z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <h2 class="faq-group__title">{{ group.category }}</h2>
                            </div>

                            <AccordionRoot type="multiple" class="faq-accordion" dir="rtl">
                                <AccordionItem v-for="(item, i) in group.questions" :key="i"
                                    :value="group.category + '-' + i" class="faq-accordion__item">
                                    <AccordionHeader as-child>
                                        <AccordionTrigger class="faq-accordion__trigger">
                                            <span class="faq-accordion__question">{{ item.q }}</span>
                                            <svg class="faq-accordion__chevron" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            </svg>
                                        </AccordionTrigger>
                                    </AccordionHeader>
                                    <AccordionContent class="faq-accordion__content">
                                        <div class="faq-accordion__answer">{{ item.a }}</div>
                                    </AccordionContent>
                                </AccordionItem>
                            </AccordionRoot>
                        </div>
                    </div>

                    <!-- Single Category View -->
                    <div v-else>
                        <AccordionRoot type="multiple" class="faq-accordion" dir="rtl">
                            <AccordionItem v-for="(item, i) in filteredFaqs" :key="i" :value="'faq-' + i"
                                class="faq-accordion__item">
                                <AccordionHeader as-child>
                                    <AccordionTrigger class="faq-accordion__trigger">
                                        <span class="faq-accordion__question">{{ item.q }}</span>
                                        <svg class="faq-accordion__chevron" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </AccordionTrigger>
                                </AccordionHeader>
                                <AccordionContent class="faq-accordion__content">
                                    <div class="faq-accordion__answer">{{ item.a }}</div>
                                </AccordionContent>
                            </AccordionItem>
                        </AccordionRoot>
                    </div>
                </div>

                <!-- Contact CTA -->
                <div class="faq-cta">
                    <div class="faq-cta__icon-wrap">
                        <svg style="width: 28px; height: 28px; color: var(--color-primary);" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                    </div>
                    <h3 class="faq-cta__title">لم تجد إجابتك؟</h3>
                    <p class="faq-cta__desc">فريق خدمة العملاء جاهز لمساعدتك على مدار الساعة</p>
                    <div class="faq-cta__buttons">
                        <router-link to="/contact" class="faq-cta__btn faq-cta__btn--primary">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            تواصل معنا
                        </router-link>
                        <a href="tel:920000000" class="faq-cta__btn faq-cta__btn--secondary">
                            <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            <span style="direction: ltr; unicode-bidi: embed;">920000000</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { AccordionRoot, AccordionItem, AccordionHeader, AccordionTrigger, AccordionContent } from 'radix-vue';
import { faqItems } from '@/data';

const searchQuery = ref( '' );
const activeCategory = ref( 'الكل' );

const categories = computed( () => [ 'الكل', ...faqItems.map( c => c.category ) ] );

const allQuestions = computed( () => faqItems.flatMap( c => c.questions ) );

const searchResults = computed( () => {
    const q = searchQuery.value.trim().toLowerCase();
    if ( !q ) return [];
    return allQuestions.value.filter(
        item => item.q.toLowerCase().includes( q ) || item.a.toLowerCase().includes( q )
    );
} );

const filteredFaqs = computed( () => {
    const category = faqItems.find( c => c.category === activeCategory.value );
    return category ? category.questions : [];
} );
</script>

<style scoped>
/* ===== PAGE ===== */
.faq-page {
    background-color: var(--color-bg);
    min-height: 100vh;
}

/* ===== HEADER ===== */
.faq-header {
    background: linear-gradient(to bottom left, var(--color-primary), var(--color-primary-dark));
    color: white;
    padding: 64px 16px;
}

@media (min-width: 640px) {
    .faq-header {
        padding: 80px 24px;
    }
}

.faq-header__inner {
    max-width: 768px;
    margin: 0 auto;
    text-align: center;
    width: 100%;
}

.faq-header__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: 28px;
    font-weight: 800;
    line-height: 1.5;
    margin-bottom: 12px;
    color: white;
}

@media (min-width: 640px) {
    .faq-header__title {
        font-size: 36px;
    }
}

@media (min-width: 1024px) {
    .faq-header__title {
        font-size: 40px;
    }
}

.faq-header__subtitle {
    font-size: 14px;
    line-height: 1.6;
    color: #bfdbfe;
    margin-bottom: 32px;
}

@media (min-width: 640px) {
    .faq-header__subtitle {
        font-size: 16px;
    }
}

/* ===== SEARCH ===== */
.faq-search {
    position: relative;
    max-width: 512px;
    margin: 0 auto;
    width: 100%;
}

.faq-search__icon {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    color: #94a3b8;
    pointer-events: none;
}

.faq-search__input {
    display: block;
    width: 100%;
    padding: 14px 48px 14px 48px;
    border-radius: 16px;
    font-size: 14px;
    color: var(--color-foreground);
    background: white;
    border: none;
    outline: none;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
    text-align: right;
    box-sizing: border-box;
}

.faq-search__input::placeholder {
    color: #94a3b8;
}

.faq-search__input:focus {
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1), 0 0 0 3px rgba(255, 255, 255, 0.3);
}

.faq-search__clear {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    line-height: 0;
}

.faq-search__clear:hover {
    color: #475569;
}

/* ===== CONTENT ===== */
.faq-content {
    padding: 48px 16px;
}

@media (min-width: 640px) {
    .faq-content {
        padding: 64px 24px;
    }
}

.faq-content__inner {
    max-width: 768px;
    margin: 0 auto;
    width: 100%;
}

/* ===== CATEGORY TABS ===== */
.faq-tabs {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 40px;
    justify-content: center;
}

.faq-tabs__btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 20px;
    border-radius: 9999px;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
    color: var(--color-muted);
    border: 1px solid var(--color-border);
}

.faq-tabs__btn:hover {
    border-color: rgba(0, 136, 235, 0.3);
    color: var(--color-foreground);
}

.faq-tabs__btn--active {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
    box-shadow: 0 4px 6px -1px rgba(0, 136, 235, 0.25);
}

.faq-tabs__btn--active:hover {
    color: white;
    border-color: var(--color-primary);
}

/* ===== FAQ GROUPS ===== */
.faq-groups {
    display: flex;
    flex-direction: column;
    gap: 40px;
}

.faq-group__header {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.faq-group__icon {
    width: 36px;
    height: 36px;
    min-width: 36px;
    border-radius: 12px;
    background: rgba(0, 136, 235, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.faq-group__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: 18px;
    font-weight: 700;
    color: var(--color-foreground);
    line-height: 1.5;
}

@media (min-width: 640px) {
    .faq-group__title {
        font-size: 20px;
    }
}

/* ===== ACCORDION ===== */
.faq-accordion {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.faq-accordion__item {
    background: white;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    overflow: hidden;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.2s;
}

.faq-accordion__item:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
}

.faq-accordion__trigger {
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 16px 20px;
    text-align: right;
    background: transparent;
    border: none;
    cursor: pointer;
    gap: 12px;
    transition: background-color 0.15s;
}

.faq-accordion__trigger:hover {
    background-color: #f8fafc;
}

.faq-accordion__question {
    flex: 1;
    min-width: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--color-foreground);
    line-height: 1.6;
    text-align: right;
}

@media (min-width: 640px) {
    .faq-accordion__question {
        font-size: 15px;
    }
}

.faq-accordion__chevron {
    width: 20px;
    height: 20px;
    min-width: 20px;
    color: var(--color-muted);
    transition: transform 0.3s;
    flex-shrink: 0;
}

[data-state="open"]>.faq-accordion__trigger .faq-accordion__chevron {
    transform: rotate(180deg);
}

.faq-accordion__content {
    overflow: hidden;
}

.faq-accordion__content[data-state="open"] {
    animation: faqSlideDown 300ms cubic-bezier(0.87, 0, 0.13, 1);
}

.faq-accordion__content[data-state="closed"] {
    animation: faqSlideUp 300ms cubic-bezier(0.87, 0, 0.13, 1);
}

.faq-accordion__answer {
    padding: 0 20px 20px;
    font-size: 14px;
    color: var(--color-muted);
    line-height: 1.8;
    border-top: 1px solid var(--color-border);
    padding-top: 16px;
}

/* ===== CTA ===== */
.faq-cta {
    margin-top: 56px;
    background: white;
    border-radius: 16px;
    border: 1px solid var(--color-border);
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    padding: 32px 24px;
    text-align: center;
}

@media (min-width: 640px) {
    .faq-cta {
        padding: 40px 40px;
    }
}

.faq-cta__icon-wrap {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(0, 136, 235, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.faq-cta__title {
    font-family: 'Noto Kufi Arabic', 'Roboto', sans-serif;
    font-size: 20px;
    font-weight: 700;
    color: var(--color-foreground);
    margin-bottom: 8px;
}

.faq-cta__desc {
    font-size: 14px;
    color: var(--color-muted);
    margin-bottom: 24px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.faq-cta__buttons {
    display: flex;
    flex-direction: column;
    gap: 12px;
    justify-content: center;
    align-items: center;
}

@media (min-width: 640px) {
    .faq-cta__buttons {
        flex-direction: row;
    }
}

.faq-cta__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}

.faq-cta__btn--primary {
    background: var(--color-primary);
    color: white;
    border: 1px solid var(--color-primary);
}

.faq-cta__btn--primary:hover {
    background: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}

.faq-cta__btn--secondary {
    background: white;
    color: var(--color-foreground);
    border: 1px solid var(--color-border);
}

.faq-cta__btn--secondary:hover {
    background: #f8fafc;
}

/* ===== ANIMATIONS ===== */
@keyframes faqSlideDown {
    from {
        height: 0;
        opacity: 0;
    }

    to {
        height: var(--radix-accordion-content-height);
        opacity: 1;
    }
}

@keyframes faqSlideUp {
    from {
        height: var(--radix-accordion-content-height);
        opacity: 1;
    }

    to {
        height: 0;
        opacity: 0;
    }
}
</style>
