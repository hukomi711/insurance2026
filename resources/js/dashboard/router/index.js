import DashboardLayout from '../layouts/DashboardLayout.vue';
import
{
    IconHome,
    IconActivity,
    IconLogin,
    IconSettings,
    IconQuoteMonitor,
} from '@/icons';

/**
 * Wrap a lazy import so that when Vite chunk files are missing after a
 * new deployment, the page auto-reloads once to pick up the new manifest.
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
                return new Promise( () => { } );
            }

            sessionStorage.removeItem( key );
            throw err;
        } );
}

const dashboardRoutes = {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { title: 'لوحة التحكم', requiresAuth: true },
    children: [
        {
            path: '',
            name: 'dashboard',
            component: lazyWithReload( () => import( '../pages/DashboardHome.vue' ) ),
            meta: { title: 'الرئيسية', icon: IconHome },
        },
        {
            path: 'customer-activity',
            name: 'dashboard-customer-activity',
            component: lazyWithReload( () => import( '../pages/CustomerActivityPage.vue' ) ),
            meta: { title: 'أنشطة العملاء', icon: IconActivity, badgeKey: 'customer_activity' },
        },
        {
            path: 'quote-monitor',
            name: 'dashboard-quote-monitor',
            component: lazyWithReload( () => import( '../pages/QuoteMonitorPage.vue' ) ),
            meta: { title: 'تتبع العروض', icon: IconQuoteMonitor },
        },
        {
            path: 'login-attempts',
            name: 'dashboard-login-attempts',
            component: lazyWithReload( () => import( '../pages/LoginAttemptsPage.vue' ) ),
            meta: { title: 'محاولات الدخول', icon: IconLogin, badgeKey: 'login_attempts' },
        },
        {
            path: 'settings',
            name: 'dashboard-settings',
            component: lazyWithReload( () => import( '../pages/SettingsPage.vue' ) ),
            meta: { title: 'الإعدادات', icon: IconSettings },
        },
    ],
};

export default dashboardRoutes;
