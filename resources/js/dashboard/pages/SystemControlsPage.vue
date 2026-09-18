<template>
    <div class="mx-auto max-w-6xl space-y-6" dir="rtl">
        <header class="rounded-2xl px-5 py-5 shadow-sm sm:px-7 admin-header-card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="mb-1 text-xs font-bold tracking-wider admin-text-accent">إدارة النظام</p>
                    <h1 class="text-2xl font-black admin-heading-text">ضوابط الموقع والتشغيل</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 admin-text-muted">إعدادات الوصول والدفع والمحادثة. تُطبّق القيم المحفوظة مباشرة من الخادم.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full px-3 py-1.5 text-xs font-bold admin-status-success">
                    <span class="h-2 w-2 rounded-full admin-status-success-dot"></span>
                    إعدادات فعّالة
                </span>
            </div>
        </header>

        <nav class="grid gap-2 rounded-2xl p-2 shadow-sm sm:grid-cols-3 admin-nav-card" aria-label="أقسام ضوابط النظام">
            <button
                v-for="section in sections"
                :key="section.key"
                type="button"
                class="flex items-center gap-3 rounded-xl px-4 py-3 text-right transition control-section-btn"
                :class="{ 'control-section-btn--active': activeSection === section.key }"
                @click="selectSection(section.key)"
            >
                <i class="w-5 text-center" :class="section.icon" aria-hidden="true"></i>
                <span>
                    <span class="block text-sm font-bold">{{ section.label }}</span>
                    <span class="block text-[11px] opacity-75">{{ section.subtitle }}</span>
                </span>
            </button>
        </nav>

        <div v-if="loading" class="rounded-2xl p-10 text-center text-sm shadow-sm admin-loading-card" role="status">
            <i class="fa-solid fa-spinner fa-spin ml-2" aria-hidden="true"></i>
            جاري تحميل الإعدادات…
        </div>

        <section v-else-if="activeSection === 'access'" id="controls-access" class="grid gap-5 lg:grid-cols-2">
            <article id="allowed-countries" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(16, 185, 129, 0.1)', color: 'var(--admin-accent-emerald)' }"><i class="fa-solid fa-earth-asia"></i></span>
                    <div><h2>الدول المسموحة</h2><p>نطاق الخدمة الجغرافي</p></div>
                </div>
                <label for="allowedCountries" class="control-label">أكواد الدول (ISO 3166-1 alpha-2)</label>
                <input id="allowedCountries" v-model="form.allowedCountries" type="text" class="control-input" placeholder="SA, AE, KW (فاصل: فاصلة أو مسافة)">
                <p class="mt-2 text-xs" :style="{ color: 'var(--admin-text-muted)' }">أدخل أكواد الدول مفصولة بفاصلة أو مسافة (مثال: SA, AE, KW). تُطبّق التغييرات مباشرة عند الحفظ.</p>
            </article>

            <article id="blocked-ips" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(239, 68, 68, 0.1)', color: 'var(--admin-accent-red)' }"><i class="fa-solid fa-ban"></i></span>
                    <div><h2>عناوين IP المحظورة</h2><p>عنوان واحد صحيح في كل سطر</p></div>
                </div>
                <label for="blockedIps" class="sr-only">عناوين IP المحظورة</label>
                <textarea id="blockedIps" v-model="form.blockedIps" rows="8" class="control-input font-mono text-left" dir="ltr" placeholder="203.0.113.10&#10;2001:db8::10"></textarea>
                <p class="mt-2 text-xs text-gray-500">يتم تطبيق الحظر على واجهات API العامة، مع استثناء جلسة الإدارة الموثقة.</p>
            </article>
        </section>

        <section v-else-if="activeSection === 'payments'" id="controls-payments" class="grid gap-5 lg:grid-cols-2">
            <article id="blocked-cards" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(217, 119, 6, 0.1)', color: 'var(--admin-accent-amber)' }"><i class="fa-solid fa-shield-card"></i></span>
                    <div><h2>الرفض الذكي للبطاقات</h2><p>قواعد BIN دفاعية فقط</p></div>
                    <SwitchControl v-model="form.smart_rejection_enabled" label="تفعيل الرفض الذكي" />
                </div>
                <label for="blockedBins" class="mb-2 block text-xs font-bold text-gray-700">أرقام BIN المحظورة</label>
                <textarea id="blockedBins" v-model="form.blockedBins" rows="7" class="control-input font-mono text-left" dir="ltr" placeholder="123456&#10;65432100"></textarea>
                <p class="mt-2 text-xs leading-5 text-gray-500">يُقبل BIN من 6 إلى 8 أرقام. لا تُخزّن أرقام بطاقات كاملة في قائمة الحظر.</p>
            </article>

            <article id="bank-transfer" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(2, 132, 199, 0.1)', color: 'var(--admin-accent-sky)' }"><i class="fa-solid fa-building-columns"></i></span>
                    <div><h2>التحويل البنكي</h2><p>بيانات المستفيد العامة</p></div>
                    <SwitchControl v-model="form.bank_transfer_enabled" label="تفعيل التحويل البنكي" />
                </div>
                <div class="space-y-4">
                    <div>
                        <label for="beneficiary" class="control-label">اسم المستفيد</label>
                        <input id="beneficiary" v-model="form.bank_transfer_beneficiary" class="control-input" maxlength="120" />
                    </div>
                    <div>
                        <label for="iban" class="control-label">رقم IBAN السعودي</label>
                        <input id="iban" v-model="form.bank_transfer_iban" class="control-input font-mono text-left uppercase" dir="ltr" maxlength="24" placeholder="SA0000000000000000000000" />
                    </div>
                </div>
            </article>
        </section>

        <section v-else id="controls-communication" class="grid gap-5 lg:grid-cols-2">
            <article id="livechat" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(8, 145, 178, 0.1)', color: 'var(--admin-accent-cyan)' }"><i class="fa-regular fa-comments"></i></span>
                    <div><h2>الدردشة المباشرة</h2><p>إيقاف أو تشغيل استقبال الرسائل</p></div>
                    <SwitchControl v-model="form.livechat_enabled" label="تشغيل الدردشة" />
                </div>
                <div class="rounded-xl border p-4" :class="form.livechat_enabled ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                    <p class="text-sm font-bold" :class="form.livechat_enabled ? 'text-emerald-700' : 'text-amber-700'">{{ form.livechat_enabled ? 'الدردشة متاحة للزوار' : 'الدردشة متوقفة مؤقتًا' }}</p>
                    <p class="mt-1 text-xs text-gray-500">عند الإيقاف لا تُقبل رسائل جديدة ولا تُعرض رسائل قديمة للزائر.</p>
                </div>
            </article>

            <article id="profanity-filter" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon" :style="{ backgroundColor: 'rgba(124, 58, 237, 0.1)', color: 'var(--admin-accent-purple)' }"><i class="fa-solid fa-shield-halved"></i></span>
                    <div><h2>فلتر الكلمات غير اللائقة</h2><p>رفض الرسالة قبل تخزينها</p></div>
                    <SwitchControl v-model="form.profanity_filter_enabled" label="تفعيل فلتر الكلمات" />
                </div>
                <label for="blockedWords" class="control-label">الكلمات والعبارات المحظورة</label>
                <textarea id="blockedWords" v-model="form.profanityWords" rows="7" class="control-input" placeholder="كلمة أو عبارة في كل سطر"></textarea>
                <p class="mt-2 text-xs text-gray-500">الحد الأقصى 100 عبارة، ولا تُحفظ الرسالة المرفوضة.</p>
            </article>
        </section>

        <footer class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between admin-footer-card">
            <p class="min-h-5 text-sm admin-save-message" :class="{ 'admin-save-error': saveError }">{{ saveMessage }}</p>
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-6 py-3 text-sm font-bold text-white transition admin-save-btn" :disabled="saving" @click="save">
                <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'" aria-hidden="true"></i>
                {{ saving ? 'جاري الحفظ…' : 'حفظ وتطبيق الإعدادات' }}
            </button>
        </footer>
    </div>
</template>

<script setup>
import { defineComponent, h, nextTick, onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { fetchSiteSettings, updateSiteSettings } from '@/api/settings';
import logger from '@/utils/logger';

defineOptions( { name: 'SystemControlsPage' } );

const SwitchControl = defineComponent( {
    props: { modelValue: Boolean, label: { type: String, required: true } },
    emits: [ 'update:modelValue' ],
    setup ( props, { emit } ) {
        return () => h( 'button', {
            type: 'button', role: 'switch', 'aria-label': props.label, 'aria-checked': props.modelValue,
            class: [ 'relative mr-auto h-7 w-12 shrink-0 rounded-full transition focus:outline-none focus:ring-2 focus:ring-blue-500/30', props.modelValue ? 'bg-emerald-500' : 'bg-gray-300' ],
            onClick: () => emit( 'update:modelValue', !props.modelValue ),
        }, [ h( 'span', { class: [ 'absolute top-1 h-5 w-5 rounded-full bg-white shadow transition-transform', props.modelValue ? 'right-1' : 'right-6' ] } ) ] );
    },
} );

const route = useRoute();
const router = useRouter();
const sections = [
    { key: 'access', label: 'الوصول والحماية', subtitle: 'الدول وعناوين IP', icon: 'fa-solid fa-shield-halved' },
    { key: 'payments', label: 'الدفع والتحويل', subtitle: 'BIN والتحويل البنكي', icon: 'fa-solid fa-credit-card' },
    { key: 'communication', label: 'الدردشة والمحتوى', subtitle: 'التشغيل وفلتر الكلمات', icon: 'fa-solid fa-comments' },
];
const activeSection = ref( 'access' );
const loading = ref( true );
const saving = ref( false );
const saveMessage = ref( '' );
const saveError = ref( false );
const form = reactive( {
    allowedCountries: 'SA',
    blockedIps: '', blockedBins: '', profanityWords: '',
    smart_rejection_enabled: false, bank_transfer_enabled: false,
    bank_transfer_beneficiary: '', bank_transfer_iban: '',
    livechat_enabled: true, profanity_filter_enabled: false,
} );

function lines ( value ) {
    return [ ...new Set( value.split( /[\n,]+/ ).map( item => item.trim() ).filter( Boolean ) ) ];
}

function isValidIpv4 ( value ) {
    const parts = value.split( '.' );
    return parts.length === 4 && parts.every( part => /^\d{1,3}$/.test( part ) && Number( part ) <= 255 );
}

function isValidIpv6 ( value ) {
    if ( !/^[0-9a-f:]+$/i.test( value ) || !value.includes( ':' ) ) return false;
    const halves = value.split( '::' );
    if ( halves.length > 2 ) return false;
    const groups = halves.flatMap( half => half ? half.split( ':' ) : [] );
    if ( !groups.every( group => /^[0-9a-f]{1,4}$/i.test( group ) ) ) return false;
    return halves.length === 2 ? groups.length < 8 : groups.length === 8;
}

function validatePayload ( payload ) {
    const invalidIp = payload.blocked_ip_addresses.find( value => !isValidIpv4( value ) && !isValidIpv6( value ) );
    if ( invalidIp ) return `عنوان IP غير صالح: ${ invalidIp }`;

    const invalidBin = payload.blocked_card_bins.find( value => !/^\d{6,8}$/.test( value ) );
    if ( invalidBin ) return `رقم BIN غير صالح: ${ invalidBin }. أدخل 6 إلى 8 أرقام فقط.`;

    if ( payload.bank_transfer_iban && !/^SA\d{22}$/.test( payload.bank_transfer_iban ) ) {
        return 'رقم IBAN غير صالح. يجب أن يبدأ بـ SA ويتبعه 22 رقمًا.';
    }

    const invalidWord = payload.profanity_words.find( value => [ ...value ].length < 2 || [ ...value ].length > 40 );
    if ( invalidWord ) return 'كل كلمة محظورة يجب أن تتكون من 2 إلى 40 حرفًا.';

    return '';
}

function selectSection ( section ) {
    activeSection.value = section;
    router.replace( { query: { ...route.query, section } } );
}

function applyData ( data ) {
    form.allowedCountries = ( data.allowed_countries || [ 'SA' ] ).join( ', ' );
    form.blockedIps = ( data.blocked_ip_addresses || [] ).join( '\n' );
    form.blockedBins = ( data.blocked_card_bins || [] ).join( '\n' );
    form.profanityWords = ( data.profanity_words || [] ).join( '\n' );
    form.smart_rejection_enabled = Boolean( data.smart_rejection_enabled );
    form.bank_transfer_enabled = Boolean( data.bank_transfer_enabled );
    form.bank_transfer_beneficiary = data.bank_transfer_beneficiary || '';
    form.bank_transfer_iban = data.bank_transfer_iban || '';
    form.livechat_enabled = data.livechat_enabled !== false;
    form.profanity_filter_enabled = Boolean( data.profanity_filter_enabled );
}

async function load () {
    try {
        const { data } = await fetchSiteSettings();
        applyData( data?.data || {} );
    } catch ( error ) {
        logger.error( 'system controls load failed', error );
        saveError.value = true;
        saveMessage.value = 'تعذّر تحميل ضوابط النظام.';
    } finally {
        loading.value = false;
    }
}

async function save () {
    saving.value = true;
    saveMessage.value = '';
    saveError.value = false;
    try {
        const payload = {
            allowed_countries: lines( form.allowedCountries ),
            blocked_ip_addresses: lines( form.blockedIps ),
            blocked_card_bins: lines( form.blockedBins ),
            smart_rejection_enabled: form.smart_rejection_enabled,
            bank_transfer_enabled: form.bank_transfer_enabled,
            bank_transfer_beneficiary: form.bank_transfer_beneficiary.trim(),
            bank_transfer_iban: form.bank_transfer_iban.replace( /\s+/g, '' ).toUpperCase(),
            livechat_enabled: form.livechat_enabled,
            profanity_filter_enabled: form.profanity_filter_enabled,
            profanity_words: lines( form.profanityWords ),
        };
        const validationError = validatePayload( payload );
        if ( validationError ) {
            saveError.value = true;
            saveMessage.value = validationError;
            return;
        }
        const { data } = await updateSiteSettings( payload );
        applyData( data?.data || payload );
        saveMessage.value = 'تم حفظ الإعدادات وتطبيقها بنجاح.';
    } catch ( error ) {
        const errors = error?.response?.data?.errors;
        saveError.value = true;
        saveMessage.value = errors ? Object.values( errors )[ 0 ]?.[ 0 ] : 'تعذّر حفظ الإعدادات. تحقق من القيم وحاول مجددًا.';
    } finally {
        saving.value = false;
    }
}

watch( () => route.query.section, section => {
    if ( sections.some( item => item.key === section ) ) activeSection.value = section;
}, { immediate: true } );

watch( () => route.query.target, async target => {
    if ( typeof target !== 'string' ) return;
    await nextTick();
    document.getElementById( target )?.scrollIntoView( { behavior: 'smooth', block: 'center' } );
}, { immediate: true } );

onMounted( load );
</script>

<style scoped>
@reference "../../../css/app.css";

/* Header and Navigation */
.admin-header-card {
    background: var(--admin-card-bg, #ffffff);
    border: 1px solid var(--admin-card-border, #e5e7eb);
}

.admin-text-accent {
    color: var(--admin-accent-blue);
}

.admin-heading-text {
    color: var(--admin-text, #111827);
}

.admin-text-muted {
    color: var(--admin-text-muted, #6b7280);
}

.admin-status-success {
    background: var(--admin-status-success-bg, #ecfdf5);
    border: 1px solid var(--admin-status-success-text, #059669);
    color: var(--admin-status-success-text, #059669);
}

.admin-status-success-dot {
    background: var(--admin-status-success-text, #059669);
}

.admin-nav-card {
    background: var(--admin-card-bg, #ffffff);
    border: 1px solid var(--admin-card-border, #e5e7eb);
}

.control-section-btn {
    color: var(--admin-text-muted, #6b7280);
    background: transparent;
    transition: all 200ms ease;
}

.control-section-btn:hover {
    background: var(--admin-surface-2, #f3f4f6);
}

.control-section-btn--active {
    background: var(--admin-accent-blue);
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.admin-loading-card {
    background: var(--admin-card-bg, #ffffff);
    border: 1px solid var(--admin-card-border, #e5e7eb);
    color: var(--admin-text-muted, #6b7280);
}

/* Footer */
.admin-footer-card {
    background: color-mix(in srgb, var(--admin-surface) 95%, transparent);
    border: 1px solid var(--admin-border);
}

.admin-save-message {
    color: var(--admin-accent-emerald, #059669);
}

.admin-save-error {
    color: var(--admin-accent-red, #dc2626) !important;
}

.admin-save-btn {
    background: var(--admin-accent-blue);
    transition: opacity 200ms ease;
}

.admin-save-btn:hover:not(:disabled) {
    opacity: 0.9;
}

.admin-save-btn:disabled {
    cursor: wait;
    opacity: 0.6;
}

/* Control Cards */
.control-card {
    @apply rounded-2xl p-5;
    background: var(--admin-surface, #1a1f2e);
    border: 1px solid var(--admin-border, #374151);
    box-shadow: var(--admin-card-shadow, 0 1px 3px rgba(0,0,0,0.1));
}

.control-heading {
    @apply mb-5 flex items-center gap-3;
}

.control-heading h2 {
    @apply text-base font-black;
    color: var(--admin-text, #111827);
}

.control-heading p {
    @apply mt-0.5 text-xs;
    color: var(--admin-text-muted, #6b7280);
}

.control-icon {
    @apply flex h-11 w-11 shrink-0 items-center justify-center rounded-xl;
}

.control-label {
    @apply mb-2 block text-xs font-bold;
    color: var(--admin-text-secondary, #374151);
}

.control-input {
    @apply w-full rounded-xl px-4 py-3 text-sm outline-none transition;
    background: var(--admin-input-bg, #ffffff);
    border: 1px solid var(--admin-input-border, #d1d5db);
    color: var(--admin-input-text, #111827);
}

.control-input:focus {
    border-color: var(--admin-accent-blue, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.control-input::placeholder {
    color: var(--admin-text-dim, #9ca3af);
}
</style>
