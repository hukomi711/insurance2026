<template>
    <div class="min-h-screen bg-bg flex items-center justify-center" dir="rtl">
        <div class="w-full max-w-md mx-4">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="typ-h1 text-foreground">رمز التأكيد</h1>
                <p class="text-muted mt-1">تم إرسال رمز التأكيد إلى البريد الإلكتروني المعتمد</p>
            </div>

            <!-- Code Form -->
            <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                <form @submit.prevent="handleVerify">
                    <!-- Code Input -->
                    <div class="mb-6">
                        <label for="code" class="block typ-s2 text-foreground mb-2">رمز التأكيد</label>
                        <input id="code" v-model="code" type="text" required maxlength="6" minlength="6"
                            inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code" name="code"
                            placeholder="------" dir="ltr"
                            class="w-full px-4 py-4 border border-border rounded-xl text-center text-2xl font-bold tracking-[0.5em] focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-slate-50" />
                    </div>

                    <!-- Error -->
                    <p v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</p>

                    <!-- Success -->
                    <p v-if="resendSuccess" class="text-green-600 text-sm mb-4">{{ resendSuccess }}</p>

                    <!-- Submit -->
                    <button type="submit" :disabled="loading || code.length !== 6"
                        class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="loading" class="inline-flex items-center gap-2">
                            <InsLoading size="auto" color="white" />
                            جاري التحقق...
                        </span>
                        <span v-else>تأكيد الدخول</span>
                    </button>

                    <!-- Resend & Timer -->
                    <div class="mt-4 text-center">
                        <button v-if="canResend" type="button" :disabled="resending"
                            class="text-sm text-primary hover:underline disabled:opacity-50"
                            @click="handleResend">
                            {{ resending ? 'جاري الإرسال...' : 'إعادة إرسال الرمز' }}
                        </button>
                        <span v-else class="text-sm text-muted">
                            إعادة الإرسال بعد {{ countdown }} ثانية
                        </span>
                    </div>
                </form>
            </div>

            <!-- Back to login -->
            <div class="text-center mt-6">
                <router-link to="/login" class="text-sm text-muted hover:text-primary transition-colors">
                    ← العودة لتسجيل الدخول
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import InsLoading from '@/components/ui/InsLoading.vue';

const router = useRouter();
const route = useRoute();
const userStore = useUserStore();

const code = ref('');
const loading = ref(false);
const resending = ref(false);
const error = ref('');
const resendSuccess = ref('');
const countdown = ref(60);
const canResend = ref(false);

let timer = null;

const userId = route.query.uid;

// Redirect to login if no user_id
if (!userId) {
    router.replace('/login');
}

function startCountdown() {
    canResend.value = false;
    countdown.value = 60;
    timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            canResend.value = true;
        }
    }, 1000);
}

async function handleVerify() {
    if (loading.value || code.value.length !== 6) return;
    loading.value = true;
    error.value = '';
    resendSuccess.value = '';
    try {
        await userStore.verifyCode(userId, code.value);
        const redirect = route.query.redirect;
        router.push(redirect && typeof redirect === 'string' ? redirect : '/dashboard');
    } catch (e) {
        error.value = e.message || 'رمز التأكيد غير صحيح أو منتهي الصلاحية';
    } finally {
        loading.value = false;
    }
}

async function handleResend() {
    if (resending.value) return;
    resending.value = true;
    error.value = '';
    resendSuccess.value = '';
    try {
        await userStore.resendCode(userId);
        resendSuccess.value = 'تم إعادة إرسال الرمز بنجاح';
        code.value = '';
        startCountdown();
    } catch {
        error.value = 'فشل إعادة إرسال الرمز. حاول مرة أخرى.';
    } finally {
        resending.value = false;
    }
}

onMounted(() => {
    startCountdown();
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>
