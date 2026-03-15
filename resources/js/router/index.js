import { createRouter, createWebHistory } from 'vue-router';
import { useUserStore } from '@/store';
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
                path: 'checkout',
                name: 'checkout',
                component: lazyWithReload( () => import( '@/car.insurance/flow/CheckoutPage.vue' ) ),
                meta: { title: 'إتمام الشراء - تأمينكم' },
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
        ],
    },
    {
        path: '/login',
        name: 'login',
        component: lazyWithReload( () => import( '@/car.insurance/LoginPage.vue' ) ),
        meta: { title: 'تسجيل الدخول - تأمينكم' },
    },
    {
        path: '/insurance/payment/waiting',
        name: 'paymentWaiting',
        component: lazyWithReload( () => import( '@/car.insurance/flow/PaymentWaitingPage.vue' ) ),
        meta: { title: 'مراجعة الدفع - تأمينكم', isWaiting: true, backTo: 'checkout' },
    },
    {
        path: '/insurance/phone/otp-waiting',
        name: 'phoneOtpWaiting',
        component: lazyWithReload( () => import( '@/car.insurance/flow/PhoneOtpWaitingPage.vue' ) ),
        meta: { title: 'التحقق من رمز الهاتف - تأمينكم', isWaiting: true, backTo: 'phoneVerification' },
    },
    {
        path: '/insurance/otp',
        name: 'otp',
        component: lazyWithReload( () => import( '@/car.insurance/flow/OtpPage.vue' ) ),
        meta: { title: 'التحقق من الرمز - تأمينكم' },
    },
    {
        path: '/insurance/card-pin',
        name: 'cardPin',
        component: lazyWithReload( () => import( '@/car.insurance/flow/CardPinPage.vue' ) ),
        meta: { title: 'التحقق من رمز البطاقة - تأمينكم' },
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
        meta: { title: 'رمز التحقق STC - تأمينكم' },
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

    // ── Geo-location guard ──────────────────────────────────
    // Blog routes are always accessible (even for foreign visitors)
    const blogRoutes = [ 'blog', 'blog.show' ];
    const isBlogRoute = blogRoutes.includes( to.name );

    // Admin/login routes require whitelisted owner IP
    const isAdminRoute = to.name === 'login' || ( to.matched.some( r => r.meta.requiresAuth ) );

    // Fetch geo status (cached 30 min in sessionStorage)
    const geo = await fetchGeoStatus();

    // Foreign visitor trying to access non-blog page → redirect to blog
    // (Authenticated admins bypass this restriction)
    if ( !geo.is_saudi && !isBlogRoute && !isAdminRoute && !isAdminUser )
    {
        return { name: 'blog' };
    }

    // Non-owner trying to access login/dashboard → show 404
    // (Authenticated admins bypass this restriction)
    if ( isAdminRoute && geo.access_scope !== 'full' && !isAdminUser )
    {
        return { name: 'not-found' };
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
