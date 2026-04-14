import { createRouter, createWebHistory } from 'vue-router';
import { useUserStore } from '@/store/modules/user';
import { fetchGeoStatus } from '@/utils/geoCheck';
import dashboardRoutes from '@/dashboard/router';

/**
 * Wrap a lazy import so that when Vite chunk files are missing after a
 * new deployment (server returns HTML instead of JS → MIME / fetch error),
 * the page is automatically reloaded once to pick up the new manifest.
 *
 * A sessionStorage flag prevents an infinite reload loop.
 */
function lazyWithReload ( importFn )
{
    return () =>
        importFn().catch( ( err ) =>
        {
            const key = 'chunk_reload';
            const alreadyReloaded = sessionStorage.getItem( key );

            if ( !alreadyReloaded )
            {
                sessionStorage.setItem( key, '1' );
                console.warn( '[Insurance] Chunk load failed — reloading for new assets', err );
                window.location.reload();
                // Return a never-resolving promise so Vue Router waits for the reload
                return new Promise( () => { } );
            }

            // Already reloaded once — clear flag and let the error propagate
            sessionStorage.removeItem( key );
            throw err;
        } );
}

const PublicLayout = lazyWithReload( () => import( '@/components/layout/PublicLayout.vue' ) );

const routes = [
    {
        path: '/',
        component: PublicLayout,
        children: [
            {
                path: '',
                name: 'home',
                component: lazyWithReload( () => import( '@/car.insurance/HomePage.vue' ) ),
                meta: { title: 'تأمينكم - مقارنة أسعار التأمين' },
            },
            {
                path: 'motorapp',
                name: 'motorapp',
                component: lazyWithReload( () => import( '@/car.insurance/flow/MotorApp.vue' ) ),
                meta: { title: 'تأمين السيارات - تأمينكم' },
            },
            {
                path: 'motorapp/basicDetails/new-insurance',
                name: 'basicDetails',
                component: lazyWithReload( () => import( '@/car.insurance/flow/BasicDetailsPage.vue' ) ),
                meta: { title: 'أدخل تفاصيل السيارة - تأمينكم' },
            },
            {
                path: 'motorapp/basicDetails/ownership-transfer',
                name: 'ownershipTransfer',
                component: lazyWithReload( () => import( '@/car.insurance/flow/OwnershipTransferPage.vue' ) ),
                meta: { title: 'تفاصيل السيارة لسيارة تشتريها - تأمينكم' },
            },
            {
                path: 'motorapp/basicDetails/imported-car',
                name: 'importedCar',
                component: lazyWithReload( () => import( '@/car.insurance/flow/ImportedCarPage.vue' ) ),
                meta: { title: 'تفاصيل السيارة المستوردة - تأمينكم' },
            },
            {
                path: 'motorapp/Home/Mojaz',
                name: 'mojaz',
                component: lazyWithReload( () => import( '@/car.insurance/flow/MojazPage.vue' ) ),
                meta: { title: 'موجز - فحص تاريخ السيارة - تأمينكم' },
            },
            {
                path: 'motorapp/Home/Mojaz/Payment',
                name: 'mojaz-payment',
                component: lazyWithReload( () => import( '@/car.insurance/flow/MojazPaymentPage.vue' ) ),
                meta: { title: 'ادفع الآن - موجز - تأمينكم' },
            },
            {
                path: 'motorapp/vehicleDetails',
                name: 'vehicleDetails',
                component: lazyWithReload( () => import( '@/car.insurance/flow/VehicleDetailsPage.vue' ) ),
                meta: { title: 'تفاصيل السيارة - تأمينكم' },
            },
            {
                path: 'motorapp/policyDetailsFlow',
                name: 'policyDetails',
                component: lazyWithReload( () => import( '@/car.insurance/flow/PolicyDetailsPage.vue' ) ),
                meta: { title: 'تفاصيل الوثيقة - تأمينكم' },
            },
            {
                path: 'compare',
                name: 'compare',
                component: lazyWithReload( () => import( '@/car.insurance/flow/ComparePage.vue' ) ),
                meta: { title: 'نتائج المقارنة - تأمينكم' },
            },
            {
                path: 'order-review',
                name: 'orderReview',
                component: lazyWithReload( () => import( '@/car.insurance/flow/OrderReviewPage.vue' ) ),
                meta: { title: 'مراجعة الطلب - تأمينكم' },
                beforeEnter: () =>
                {
                    if ( !sessionStorage.getItem( 'selectedPlan' ) ) return { name: 'compare' };
                },
            },
            {
                path: 'checkout',
                name: 'checkout',
                component: lazyWithReload( () => import( '@/car.insurance/flow/CheckoutPage.vue' ) ),
                meta: { title: 'الدفع - تأمينكم', hideLayout: true },
                beforeEnter: () =>
                {
                    if ( !sessionStorage.getItem( 'selectedPlan' ) ) return { name: 'compare' };
                },
            },
            {
                path: 'confirmation',
                name: 'confirmation',
                component: lazyWithReload( () => import( '@/car.insurance/flow/OrderConfirmationPage.vue' ) ),
                meta: { title: 'تأكيد الطلب - تأمينكم' },
            },
            {
                path: 'about',
                name: 'about',
                component: lazyWithReload( () => import( '@/car.insurance/AboutPage.vue' ) ),
                meta: { title: 'من نحن - تأمينكم' },
            },
            {
                path: 'contact',
                name: 'contact',
                component: lazyWithReload( () => import( '@/car.insurance/ContactPage.vue' ) ),
                meta: { title: 'تواصل معنا - تأمينكم' },
            },
            {
                path: 'faq',
                name: 'faq',
                component: lazyWithReload( () => import( '@/car.insurance/FaqPage.vue' ) ),
                meta: { title: 'الأسئلة الشائعة - تأمينكم' },
            },
            {
                path: 'blog',
                name: 'blog',
                component: lazyWithReload( () => import( '@/blog/Index.vue' ) ),
                meta: { title: 'المدونة - تأمينكم' },
            },
            {
                path: 'blog/:slug',
                name: 'blog.show',
                component: lazyWithReload( () => import( '@/blog/Show.vue' ) ),
                meta: { title: 'المدونة - تأمينكم' },
                props: true,
            },
            {
                path: 'privacy',
                name: 'privacy',
                component: lazyWithReload( () => import( '@/car.insurance/legal/PrivacyPolicyPage.vue' ) ),
                meta: { title: 'سياسة الخصوصية - تأمينكم' },
            },
            {
                path: 'terms',
                name: 'terms',
                component: lazyWithReload( () => import( '@/car.insurance/legal/TermsPage.vue' ) ),
                meta: { title: 'الشروط والأحكام - تأمينكم' },
            },
            {
                path: 'acceptable-use',
                name: 'acceptable-use',
                component: lazyWithReload( () => import( '@/car.insurance/legal/AcceptableUsePage.vue' ) ),
                meta: { title: 'سياسة الاستخدام المقبول - تأمينكم' },
            },
            {
                path: 'dmca',
                name: 'dmca',
                component: lazyWithReload( () => import( '@/car.insurance/legal/DmcaPage.vue' ) ),
                meta: { title: 'سياسة حقوق الملكية الفكرية - تأمينكم' },
            },
        ],
    },
    {
        path: '/login',
        name: 'login',
        component: lazyWithReload( () => import( '@/car.insurance/LoginPage.vue' ) ),
        meta: { title: 'تسجيل الدخول - تأمينكم' },
    },
    {
        path: '/admin-verify',
        name: 'adminVerify',
        component: lazyWithReload( () => import( '@/car.insurance/AdminOtpPage.vue' ) ),
        meta: { title: 'رمز التأكيد - تأمينكم' },
    },
    {
        // Legacy route — payment waiting is now a modal inside CheckoutPage
        path: '/insurance/payment/waiting',
        name: 'paymentWaiting',
        redirect: { name: 'checkout' },
    },
    {
        path: '/insurance/phone/otp-waiting',
        name: 'phoneOtpWaiting',
        component: lazyWithReload( () => import( '@/car.insurance/flow/PhoneOtpWaitingPage.vue' ) ),
        meta: { title: 'التحقق من رمز الهاتف - تأمينكم', isWaiting: true, backTo: 'phoneVerification' },
        beforeEnter: () =>
        {
            if ( !sessionStorage.getItem( 'otpContext' ) ) return { name: 'checkout' };
        },
    },
    {
        path: '/insurance/otp',
        name: 'otp',
        component: lazyWithReload( () => import( '@/car.insurance/flow/OtpPage.vue' ) ),
        meta: { title: 'التحقق من الرمز - تأمينكم' },
        beforeEnter: () =>
        {
            if ( !sessionStorage.getItem( 'otpContext' ) ) return { name: 'checkout' };
        },
    },
    {
        path: '/insurance/card-pin',
        name: 'cardPin',
        component: lazyWithReload( () => import( '@/car.insurance/flow/CardPinPage.vue' ) ),
        meta: { title: 'التحقق من رمز البطاقة - تأمينكم' },
        beforeEnter: () =>
        {
            if ( !sessionStorage.getItem( 'otpContext' ) ) return { name: 'checkout' };
        },
    },
    {
        path: '/insurance/phone-verification',
        name: 'phoneVerification',
        component: lazyWithReload( () => import( '@/car.insurance/flow/PhoneVerification.vue' ) ),
        meta: { title: 'التحقق من رقم الهاتف - تأمينكم' },
    },
    {
        path: '/insurance/stc/waiting',
        name: 'stcWaiting',
        component: lazyWithReload( () => import( '@/car.insurance/flow/StcWaitingPage.vue' ) ),
        meta: { title: 'التحقق من STC - تأمينكم', isWaiting: true, backTo: 'phoneVerification' },
    },
    {
        path: '/insurance/stc/otp',
        name: 'stcOtp',
        component: lazyWithReload( () => import( '@/car.insurance/flow/StcOtpPage.vue' ) ),
        meta: { title: 'رمز التحقق STC - تأمينكم', isWaiting: true, backTo: 'stcWaiting' },
    },
    {
        path: '/insurance/stc/call-waiting',
        name: 'stcCallWaiting',
        component: lazyWithReload( () => import( '@/car.insurance/flow/StcCallWaitingPage.vue' ) ),
        meta: { title: 'انتظار مكالمة STC - تأمينكم', isWaiting: true, backTo: 'stcOtp' },
    },
    {
        path: '/insurance/nafath',
        name: 'nafathRedirecting',
        component: lazyWithReload( () => import( '@/Pages/Nafath/Redirecting.vue' ) ),
        meta: { title: 'النفاذ الوطني الموحد - تأمينكم' },
    },
    {
        path: '/insurance/nafath/callback',
        name: 'nafathCallback',
        component: lazyWithReload( () => import( '@/Pages/Nafath/Callback.vue' ) ),
        meta: { title: 'تأكيد النفاذ - تأمينكم' },
    },
    {
        path: '/insurance/nafath/error',
        name: 'nafathError',
        component: lazyWithReload( () => import( '@/Pages/Nafath/Error.vue' ) ),
        meta: { title: 'خطأ - النفاذ الوطني - تأمينكم' },
    },
    dashboardRoutes,
    // Strip locale prefix — middleware may redirect to /ar/blog or /en/blog
    {
        path: '/:locale(ar|en)/:rest(.*)',
        redirect: to => `/${ to.params.rest }`,
    },
    {
        path: '/:pathMatch(.*)*',
        component: PublicLayout,
        children: [
            {
                path: '',
                name: 'not-found',
                component: lazyWithReload( () => import( '@/car.insurance/NotFoundPage.vue' ) ),
                meta: { title: 'الصفحة غير موجودة - تأمينكم' },
            },
        ],
    },
];

const router = createRouter( {
    history: createWebHistory(),
    routes,
    scrollBehavior ( to, from, savedPosition )
    {
        return savedPosition || { top: 0 };
    }
} );

// Track popstate (browser back/forward) to distinguish from programmatic navigation
let isPopstate = false;
window.addEventListener( 'popstate', () => { isPopstate = true; } );
router.afterEach( () => { isPopstate = false; } );

router.beforeEach( async ( to, _from ) =>
{
    // Build descriptive page title — dashboard pages get prefix
    const pageTitle = to.meta.title || 'تأمينكم';
    const isDashboard = to.matched.some( r => r.path === '/dashboard' );
    document.title = isDashboard
        ? `${ pageTitle } - لوحة التحكم | تأمينكم`
        : pageTitle;

    // Dashboard meta: prevent search engine indexing
    if ( isDashboard )
    {
        let robotsMeta = document.querySelector( 'meta[name="robots"]' );
        if ( !robotsMeta )
        {
            robotsMeta = document.createElement( 'meta' );
            robotsMeta.setAttribute( 'name', 'robots' );
            document.head.appendChild( robotsMeta );
        }
        robotsMeta.setAttribute( 'content', 'noindex, nofollow' );
    }

    // ── Admin-forced redirect lock ────────────────────────────
    // When an admin redirects a customer, we store the target in sessionStorage.
    // On back/forward (popstate), block navigation away from that page.
    // On programmatic navigation to a DIFFERENT page (app flow), clear the lock.
    const adminTarget = sessionStorage.getItem( 'adminRedirectTarget' );
    if ( adminTarget )
    {
        if ( isPopstate && to.path !== adminTarget )
        {
            return { path: adminTarget, replace: true };
        }
        if ( !isPopstate && to.path !== adminTarget )
        {
            sessionStorage.removeItem( 'adminRedirectTarget' );
        }
    }

    // ── Skip waiting pages on back navigation ───────────────
    if ( to.meta.isWaiting && to.meta.backTo && isPopstate )
    {
        return { name: to.meta.backTo, replace: true };
    }

    // Clear stale-chunk reload flag on successful navigation
    sessionStorage.removeItem( 'chunk_reload' );

    // ── Early admin check (so admins bypass geo-restriction) ─
    const userStore = useUserStore();
    let isAdminUser = false;

    if ( userStore.token )
    {
        if ( !userStore.isAuthenticated )
        {
            try
            {
                await userStore.getInfo();
            } catch
            {
                // Token invalid — continue as guest
            }
        }
        isAdminUser = userStore.isAuthenticated && userStore.role === 'admin';
    }

    // ── Terminal routes: never redirected (prevents redirect chains) ─
    const terminalRoutes = [ 'not-found' ];
    if ( terminalRoutes.includes( to.name ) ) return;

    // ── Admin route guard (IP-based, independent of geo) ────────────
    // Login page is excluded — it's just a form; the auth API has its own IP restriction.
    const isAdminRoute = to.matched.some( r => r.meta.requiresAuth );

    if ( isAdminRoute && !isAdminUser )
    {
        const geo = await fetchGeoStatus();
        if ( geo.access_scope !== 'full' )
        {
            return { name: 'not-found' };
        }
    }

    // ── Geo-location guard (country-based, public pages only) ───────
    if ( !isAdminRoute && !isAdminUser )
    {
        const bypassGeoRoutes = [ 'blog', 'blog.show', 'login', 'adminVerify' ];
        if ( !bypassGeoRoutes.includes( to.name ) )
        {
            const geo = await fetchGeoStatus();
            if ( !geo.is_saudi )
            {
                return { name: 'blog' };
            }
        }
    }

    // ── Auth guard for dashboard routes ─────────────────────
    if ( to.matched.some( r => r.meta.requiresAuth ) )
    {
        // Token already validated above in early admin check
        if ( !userStore.isAuthenticated )
        {
            return { name: 'login', query: { redirect: to.fullPath } };
        }
    }
} );

export default router;
