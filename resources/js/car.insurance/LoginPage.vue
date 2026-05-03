<template>
    <div class="min-h-screen bg-bg flex items-center justify-center" dir="rtl">
        <div class="w-full max-w-md mx-4">
            <!-- Logo -->
            <div class="text-center mb-8">
                <h1 class="typ-h1 text-foreground">تسجيل الدخول</h1>
                <p class="text-muted mt-1">ادخل بياناتك للوصول إلى لوحة التحكم</p>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-border p-8" :class="{ 'animate-shake': shake }">
                <!-- Banner Error (auth / rate-limit / network / server) -->
                <div v-if="banner" role="alert" aria-live="assertive"
                    class="mb-5 flex items-start gap-3 rounded-xl border p-3.5"
                    :class="bannerClasses">
                    <svg class="w-5 h-5 mt-0.5 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <template v-if="banner.kind === 'lockout'">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </template>
                        <template v-else-if="banner.kind === 'network'">
                            <path d="M5 12.55a11 11 0 0 1 14.08 0" />
                            <path d="M1.42 9a16 16 0 0 1 21.16 0" />
                            <path d="M8.53 16.11a6 6 0 0 1 6.95 0" />
                            <line x1="12" y1="20" x2="12.01" y2="20" />
                        </template>
                        <template v-else>
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </template>
                    </svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium leading-relaxed">{{ banner.title }}</p>
                        <p v-if="banner.description" class="text-xs mt-1 opacity-90 leading-relaxed">
                            {{ banner.description }}
                        </p>
                    </div>
                </div>

                <form novalidate @submit.prevent="handleLogin">
                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="block typ-s2 text-foreground mb-2">البريد الإلكتروني</label>
                        <input id="email" v-model="form.email" type="email" autocomplete="username" name="email"
                            placeholder="example@email.com" :disabled="lockedOut"
                            :aria-invalid="!!fieldErrors.email"
                            :aria-describedby="fieldErrors.email ? 'email-error' : undefined"
                            class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:border-primary outline-none bg-slate-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                            :class="fieldErrors.email
                                ? 'border-red-400 focus:ring-red-200'
                                : 'border-border focus:ring-primary'"
                            @input="onFieldInput('email')" />
                        <p v-if="fieldErrors.email" id="email-error" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            {{ fieldErrors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <label for="password" class="block typ-s2 text-foreground mb-2">كلمة المرور</label>
                        <div class="relative">
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password" name="password" placeholder="••••••••" :disabled="lockedOut"
                                :aria-invalid="!!fieldErrors.password"
                                :aria-describedby="fieldErrors.password ? 'password-error' : undefined"
                                class="w-full px-4 py-3 pl-11 border rounded-xl text-sm focus:ring-2 focus:border-primary outline-none bg-slate-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                :class="fieldErrors.password
                                    ? 'border-red-400 focus:ring-red-200'
                                    : 'border-border focus:ring-primary'"
                                @input="onFieldInput('password')" />
                            <button type="button" :disabled="lockedOut"
                                :aria-label="showPassword ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور'"
                                class="absolute inset-y-0 left-3 flex items-center text-muted hover:text-foreground transition-colors disabled:opacity-50"
                                @click="showPassword = !showPassword">
                                <svg v-if="showPassword" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                    aria-hidden="true">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" />
                                    <line x1="1" y1="1" x2="23" y2="23" />
                                </svg>
                                <svg v-else class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <p v-if="fieldErrors.password" id="password-error" class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            {{ fieldErrors.password }}
                        </p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="loading || lockedOut"
                        class="w-full bg-primary hover:bg-primary-dark text-white py-3 rounded-xl font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="loading" class="inline-flex items-center gap-2">
                            <InsLoading size="auto" color="white" />
                            جاري تسجيل الدخول...
                        </span>
                        <span v-else-if="lockedOut">محظور — انتظر {{ lockoutRemaining }}</span>
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
import { ref, reactive, computed, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import InsLoading from '@/components/ui/InsLoading.vue';

const router = useRouter();
const userStore = useUserStore();

const form = reactive( { email: '', password: '' } );
const loading = ref( false );
const showPassword = ref( false );
const shake = ref( false );

// Per-field inline errors
const fieldErrors = reactive( { email: '', password: '' } );

// Top-level banner: { kind: 'auth' | 'lockout' | 'network' | 'server' | 'forbidden', title, description }
const banner = ref( null );

// Lockout state (countdown from 429)
const lockoutSecondsLeft = ref( 0 );
let lockoutTimer = null;
const lockedOut = computed( () => lockoutSecondsLeft.value > 0 );
const lockoutRemaining = computed( () =>
{
    const s = lockoutSecondsLeft.value;
    if ( s <= 0 ) return '';
    const m = Math.floor( s / 60 );
    const r = s % 60;
    return m > 0 ? `${ m }د ${ r.toString().padStart( 2, '0' ) }ث` : `${ r }ث`;
} );

function startLockoutCountdown ( seconds )
{
    lockoutSecondsLeft.value = Math.max( 0, Math.floor( seconds ) );
    if ( lockoutTimer ) clearInterval( lockoutTimer );
    lockoutTimer = setInterval( () =>
    {
        lockoutSecondsLeft.value -= 1;
        if ( lockoutSecondsLeft.value <= 0 )
        {
            clearInterval( lockoutTimer );
            lockoutTimer = null;
            banner.value = null;
        }
    }, 1000 );
}

onUnmounted( () => { if ( lockoutTimer ) clearInterval( lockoutTimer ); } );

const bannerClasses = computed( () =>
{
    if ( !banner.value ) return '';
    switch ( banner.value.kind )
    {
        case 'lockout':
            return 'border-amber-200 bg-amber-50 text-amber-800';
        case 'network':
            return 'border-slate-200 bg-slate-50 text-slate-700';
        case 'server':
            return 'border-orange-200 bg-orange-50 text-orange-800';
        default:
            return 'border-red-200 bg-red-50 text-red-700';
    }
} );

function triggerShake ()
{
    shake.value = false;
    requestAnimationFrame( () => { shake.value = true; } );
    setTimeout( () => { shake.value = false; }, 500 );
}

function clearErrors ()
{
    fieldErrors.email = '';
    fieldErrors.password = '';
    if ( banner.value && banner.value.kind !== 'lockout' ) banner.value = null;
}

function onFieldInput ( field )
{
    if ( fieldErrors[ field ] ) fieldErrors[ field ] = '';
    if ( banner.value && banner.value.kind === 'auth' ) banner.value = null;
}

function validateClient ()
{
    let ok = true;
    fieldErrors.email = '';
    fieldErrors.password = '';
    if ( !form.email.trim() )
    {
        fieldErrors.email = 'البريد الإلكتروني مطلوب';
        ok = false;
    } else if ( !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( form.email.trim() ) )
    {
        fieldErrors.email = 'البريد الإلكتروني غير صالح';
        ok = false;
    }
    if ( !form.password )
    {
        fieldErrors.password = 'كلمة المرور مطلوبة';
        ok = false;
    } else if ( form.password.length < 6 )
    {
        fieldErrors.password = 'كلمة المرور يجب ألا تقل عن 6 أحرف';
        ok = false;
    }
    return ok;
}

function parseLockoutSeconds ( msg )
{
    if ( !msg ) return 60;
    const m = msg.match( /(\d+)\s*دقيقة/ );
    if ( m ) return parseInt( m[ 1 ], 10 ) * 60;
    const s = msg.match( /(\d+)\s*ثانية/ );
    if ( s ) return parseInt( s[ 1 ], 10 );
    return 60;
}

function handleServerError ( err )
{
    // Network / no response
    if ( !err?.response )
    {
        banner.value = {
            kind: 'network',
            title: 'تعذّر الاتصال بالخادم',
            description: 'تحقّق من اتصالك بالإنترنت ثم حاول مرة أخرى.',
        };
        return;
    }

    const status = err.response.status;
    const data = err.response.data || {};

    // 429 — Rate limit / lockout
    if ( status === 429 )
    {
        const seconds = parseLockoutSeconds( data.message );
        startLockoutCountdown( seconds );
        banner.value = {
            kind: 'lockout',
            title: 'تم تجاوز عدد المحاولات المسموح',
            description: data.message || `لأسباب أمنية تم تعليق المحاولات مؤقتاً. حاول بعد ${ Math.ceil( seconds / 60 ) } دقيقة.`,
        };
        return;
    }

    // 422 — Validation
    if ( status === 422 )
    {
        const errs = data.errors || {};
        if ( errs.email?.length ) fieldErrors.email = errs.email[ 0 ];
        if ( errs.password?.length ) fieldErrors.password = errs.password[ 0 ];

        // If email error contains the auth-failure phrase, show banner instead
        const looksLikeAuth = errs.email?.[ 0 ] && /غير صحيحة|المتبقية|صلاحية/.test( errs.email[ 0 ] );
        if ( looksLikeAuth )
        {
            banner.value = {
                kind: 'auth',
                title: 'فشل تسجيل الدخول',
                description: errs.email[ 0 ],
            };
            fieldErrors.email = '';
            fieldErrors.password = '';
        } else if ( !Object.keys( errs ).length )
        {
            banner.value = {
                kind: 'auth',
                title: 'بيانات غير صالحة',
                description: data.message || 'تحقّق من الحقول وحاول مرة أخرى.',
            };
        }
        return;
    }

    // 401 / 403
    if ( status === 401 || status === 403 )
    {
        banner.value = {
            kind: 'forbidden',
            title: 'غير مسموح بالدخول',
            description: data.message || 'ليس لديك صلاحية الوصول إلى لوحة التحكم.',
        };
        return;
    }

    // 5xx
    if ( status >= 500 )
    {
        banner.value = {
            kind: 'server',
            title: 'خطأ في الخادم',
            description: 'حدثت مشكلة مؤقتة من جانبنا. حاول بعد قليل.',
        };
        return;
    }

    // Fallback
    banner.value = {
        kind: 'auth',
        title: 'تعذّر تسجيل الدخول',
        description: data.message || 'حدث خطأ غير متوقّع.',
    };
}

async function handleLogin ()
{
    if ( loading.value || lockedOut.value ) return;
    clearErrors();

    if ( !validateClient() )
    {
        triggerShake();
        return;
    }

    loading.value = true;
    try
    {
        const result = await userStore.login( form );
        if ( result.requires_2fa )
        {
            const redirect = router.currentRoute.value.query.redirect;
            const query = { pt: result.pending_token };
            if ( redirect ) query.redirect = redirect;
            router.push( { path: '/admin-verify', query } );
        } else
        {
            const redirect = router.currentRoute.value.query.redirect;
            router.push( redirect && typeof redirect === 'string' ? redirect : '/dashboard' );
        }
    } catch ( err )
    {
        handleServerError( err );
        triggerShake();
    } finally
    {
        loading.value = false;
    }
}
</script>

<style scoped>
@keyframes shake {
    10%, 90% { transform: translateX(-1px); }
    20%, 80% { transform: translateX(2px); }
    30%, 50%, 70% { transform: translateX(-4px); }
    40%, 60% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.45s cubic-bezier(.36, .07, .19, .97) both;
}
</style>
