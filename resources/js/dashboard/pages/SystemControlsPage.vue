<template>
    <div class="mx-auto max-w-6xl space-y-6" dir="rtl">
        <header class="rounded-2xl border border-gray-200 bg-white px-5 py-5 shadow-sm sm:px-7">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="mb-1 text-xs font-bold tracking-wider text-blue-600">إدارة النظام</p>
                    <h1 class="text-2xl font-black text-gray-900">ضوابط الموقع والتشغيل</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-500">إعدادات الوصول والدفع والمحادثة. تُطبّق القيم المحفوظة مباشرة من الخادم.</p>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    إعدادات فعّالة
                </span>
            </div>
        </header>

        <nav class="grid gap-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm sm:grid-cols-3" aria-label="أقسام ضوابط النظام">
            <button
                v-for="section in sections"
                :key="section.key"
                type="button"
                class="flex items-center gap-3 rounded-xl px-4 py-3 text-right transition"
                :class="activeSection === section.key ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'"
                @click="selectSection(section.key)"
            >
                <i class="w-5 text-center" :class="section.icon" aria-hidden="true"></i>
                <span>
                    <span class="block text-sm font-bold">{{ section.label }}</span>
                    <span class="block text-[11px] opacity-75">{{ section.subtitle }}</span>
                </span>
            </button>
        </nav>

        <div v-if="loading" class="rounded-2xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500 shadow-sm" role="status">
            <i class="fa-solid fa-spinner fa-spin ml-2" aria-hidden="true"></i>
            جاري تحميل الإعدادات…
        </div>

        <section v-else-if="activeSection === 'access'" id="controls-access" class="grid gap-5 lg:grid-cols-2">
            <article id="allowed-countries" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon bg-emerald-50 text-emerald-600"><i class="fa-solid fa-earth-asia"></i></span>
                    <div><h2>الدول المسموحة</h2><p>نطاق الخدمة الجغرافي</p></div>
                </div>
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl" aria-hidden="true">🇸🇦</span>
                            <div><p class="font-bold text-gray-900">المملكة العربية السعودية</p><p class="text-xs text-gray-500">SA — النطاق الأساسي</p></div>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200">مسموح</span>
                    </div>
                </div>
                <p class="mt-3 text-xs leading-5 text-gray-500">تم تثبيت السعودية كدولة مسموحة حفاظًا على شرط عرض وخدمة العملاء داخل المملكة فقط.</p>
            </article>

            <article id="blocked-ips" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon bg-rose-50 text-rose-600"><i class="fa-solid fa-ban"></i></span>
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
                    <span class="control-icon bg-amber-50 text-amber-600"><i class="fa-solid fa-shield-card"></i></span>
                    <div><h2>الرفض الذكي للبطاقات</h2><p>قواعد BIN دفاعية فقط</p></div>
                    <SwitchControl v-model="form.smart_rejection_enabled" label="تفعيل الرفض الذكي" />
                </div>
                <label for="blockedBins" class="mb-2 block text-xs font-bold text-gray-700">أرقام BIN المحظورة</label>
                <textarea id="blockedBins" v-model="form.blockedBins" rows="7" class="control-input font-mono text-left" dir="ltr" placeholder="123456&#10;65432100"></textarea>
                <p class="mt-2 text-xs leading-5 text-gray-500">يُقبل BIN من 6 إلى 8 أرقام. لا تُخزّن أرقام بطاقات كاملة في قائمة الحظر.</p>
            </article>

            <article id="bank-transfer" class="control-card scroll-mt-24">
                <div class="control-heading">
                    <span class="control-icon bg-sky-50 text-sky-600"><i class="fa-solid fa-building-columns"></i></span>
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
                    <span class="control-icon bg-cyan-50 text-cyan-600"><i class="fa-regular fa-comments"></i></span>
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
                    <span class="control-icon bg-violet-50 text-violet-600"><i class="fa-solid fa-shield-halved"></i></span>
                    <div><h2>فلتر الكلمات غير اللائقة</h2><p>رفض الرسالة قبل تخزينها</p></div>
                    <SwitchControl v-model="form.profanity_filter_enabled" label="تفعيل فلتر الكلمات" />
                </div>
                <label for="blockedWords" class="control-label">الكلمات والعبارات المحظورة</label>
                <textarea id="blockedWords" v-model="form.profanityWords" rows="7" class="control-input" placeholder="كلمة أو عبارة في كل سطر"></textarea>
                <p class="mt-2 text-xs text-gray-500">الحد الأقصى 100 عبارة، ولا تُحفظ الرسالة المرفوضة.</p>
            </article>
        </section>

        <footer class="sticky bottom-4 z-10 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <p class="min-h-5 text-sm" :class="saveError ? 'text-rose-600' : 'text-emerald-600'" role="status" aria-live="polite">{{ saveMessage }}</p>
            <button type="button" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-blue-500 disabled:cursor-wait disabled:opacity-60" :disabled="saving" @click="save">
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
    blockedIps: '', blockedBins: '', profanityWords: '',
    smart_rejection_enabled: false, bank_transfer_enabled: false,
    bank_transfer_beneficiary: '', bank_transfer_iban: '',
    livechat_enabled: true, profanity_filter_enabled: false,
} );

function lines ( value ) {
    return [ ...new Set( value.split( /[\n,]+/ ).map( item => item.trim() ).filter( Boolean ) ) ];
}

function selectSection ( section ) {
    activeSection.value = section;
    router.replace( { query: { ...route.query, section } } );
}

function applyData ( data ) {
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
            allowed_countries: [ 'SA' ],
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
.control-card { @apply rounded-2xl border border-gray-200 bg-white p-5 shadow-sm; }
.control-heading { @apply mb-5 flex items-center gap-3; }
.control-heading h2 { @apply text-base font-black text-gray-900; }
.control-heading p { @apply mt-0.5 text-xs text-gray-500; }
.control-icon { @apply flex h-11 w-11 shrink-0 items-center justify-center rounded-xl; }
.control-label { @apply mb-2 block text-xs font-bold text-gray-700; }
.control-input { @apply w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20; }
</style>
