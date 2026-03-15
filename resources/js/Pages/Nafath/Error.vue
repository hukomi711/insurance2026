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
                <div class="nafath-error-icon">⚠️</div>
                <h2 class="nafath-error-title">حدث خطأ</h2>
                <p class="nafath-error-message">{{ errorMessage }}</p>

                <div class="nafath-error-actions">
                    <button class="nafath-btn nafath-btn--primary" @click="retry">
                        🔄 إعادة المحاولة
                    </button>
                    <button class="nafath-btn nafath-btn--outline" @click="goHome">
                        🏠 العودة للرئيسية
                    </button>
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { getReasonLabel } from '@/constants/rejectionReasons';

const router = useRouter();
const { t } = useI18n();

const errorMessage = ref('حدث خطأ غير متوقع. الرجاء المحاولة مرة أخرى.');

onMounted(() => {
    const stored = sessionStorage.getItem('nafathError');
    if (stored) {
        errorMessage.value = getReasonLabel(stored, t) || 'حدث خطأ غير متوقع. الرجاء المحاولة مرة أخرى.';
        sessionStorage.removeItem('nafathError');
    }
});

const retry = () => {
    sessionStorage.removeItem('nafathContext');
    router.push({ name: 'nafathRedirecting' });
};

const goHome = () => {
    sessionStorage.removeItem('nafathContext');
    sessionStorage.removeItem('nafathError');
    router.push({ name: 'home' });
};
</script>

<style scoped>
.o-page { background-color: #f4f6f9; min-height: 100vh; }
.nafath-header { background: white; box-shadow: 0 2px 4px rgba(0,0,0,.1); display: flex; align-items: center; padding: 12px 24px; margin-bottom: 32px; }
.nafath-container { max-width: 520px; margin: 0 auto; padding: 0 16px; }
.nafath-card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,.08); padding: 48px 40px; }
.nafath-card--center { text-align: center; }

.nafath-error-icon { font-size: 64px; margin-bottom: 16px; }
.nafath-error-title { color: #c33; font-size: 24px; font-weight: 700; margin-bottom: 16px; }
.nafath-error-message { color: #666; font-size: 16px; line-height: 1.8; margin-bottom: 32px; }
.nafath-error-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }

.nafath-btn { border-radius: 8px; font-weight: 600; padding: 14px 28px; cursor: pointer; transition: all .3s; border: 2px solid #11998e; font-size: 14px; display: inline-block; text-decoration: none; }
.nafath-btn--primary { background: #11998e; color: white; border-color: #11998e; }
.nafath-btn--primary:hover { background: #0d7a72; }
.nafath-btn--outline { background: transparent; color: #11998e; }
.nafath-btn--outline:hover { background: #11998e; color: white; }

.nafath-footer { background: #f8f9fa; border-top: 1px solid #e9ecef; padding: 30px 24px; margin-top: 60px; display: flex; align-items: center; gap: 16px; }
.nafath-footer__brand { display: flex; align-items: center; gap: 12px; }
.nafath-footer__brand p { margin: 0; font-size: 12px; color: #6c757d; }
.nafath-footer__brand h5 { margin: 4px 0; color: #495057; }

@media (max-width: 768px) {
    .nafath-card { border-radius: 0; padding: 32px 20px; }
    .nafath-error-actions { flex-direction: column; }
    .nafath-error-actions .nafath-btn { width: 100%; }
    .nafath-footer { flex-direction: column; text-align: center; }
}
</style>
