<template>
    <div class="min-h-screen bg-bg flex items-center justify-center" dir="rtl">
        <div class="w-full max-w-md mx-4">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h1 class="typ-h1 text-foreground">تسجيل الدخول</h1>
                <p class="text-muted mt-1">ادخل بياناتك للوصول إلى لوحة التحكم</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-border p-8">
                <form @submit.prevent="handleLogin">
                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="block typ-s2 text-foreground mb-2">البريد
                            الإلكتروني</label>
                        <input id="email" v-model="form.email" type="email" required autocomplete="username"
                            name="email" placeholder="example@email.com"
                            class="w-full px-4 py-3 border border-border rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-slate-50" />
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block typ-s2 text-foreground mb-2">كلمة المرور</label>
                        <input id="password" v-model="form.password" type="password" required
                            autocomplete="current-password" name="password" placeholder="••••••••"
                            class="w-full px-4 py-3 border border-border rounded-xl text-sm focus:ring-2 focus:ring-primary focus:border-primary outline-none bg-slate-50" />
                    </div>

                    <!-- Error -->
                    <p v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</p>

                    <!-- Submit -->
                    <button type="submit" :disabled="loading"
                        class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="loading" class="inline-flex items-center gap-2">
                            <InsLoading size="auto" color="white" />
                            جاري تسجيل الدخول...
                        </span>
                        <span v-else>تسجيل الدخول</span>
                    </button>
                </form>
        </div>

            <!-- Back to site -->
            <div class="text-center mt-6">
                <router-link to="/" class="text-sm text-muted hover:text-primary transition-colors">
                    ← العودة إلى الموقع
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import InsLoading from '@/components/ui/InsLoading.vue';

const router = useRouter();
const userStore = useUserStore();

const form = reactive( { email: '', password: '' } );
const loading = ref( false );
const error = ref( '' );

async function handleLogin() {
    if ( loading.value ) return;
    loading.value = true;
    error.value = '';
    try {
        const result = await userStore.login( form );
        if ( result.requires_2fa ) {
            const redirect = router.currentRoute.value.query.redirect;
            const query = { uid: result.user_id };
            if ( redirect ) query.redirect = redirect;
            router.push( { path: '/admin-verify', query } );
        } else {
            const redirect = router.currentRoute.value.query.redirect;
            router.push( redirect && typeof redirect === 'string' ? redirect : '/dashboard' );
        }
    } catch {
        error.value = 'بيانات الدخول غير صحيحة';
    } finally {
        loading.value = false;
    }
}
</script>
