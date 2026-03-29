/* build:2026-03-26 */
import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import Toast from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import i18n, { i18nReady } from './i18n';
import App from './App.vue';
import router from './router/index';
import { initGlobalTracking, cleanupVisitorTracking } from './composables/useVisitorTracking';
import { fetchGeoStatus } from './utils/geoCheck';

// Start geo-check early so the result is ready by the time the router guard runs.
// fetchGeoStatus() deduplicates, so the router's await shares this same request.
fetchGeoStatus();

/** @see https://vue-toastification.maronato.dev */
const toastOptions = {
    position: 'top-right',
    timeout: 4000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: true,
    hideProgressBar: false,
    closeButton: 'button',
    icon: true,
    rtl: true,
    transition: 'Vue-Toastification__slideBlurred',
    maxToasts: 4,
    newestOnTop: true,
};

const app = createApp( App );
app.use( createPinia() );
app.use( i18n );
app.use( router );
app.use( Toast, toastOptions );

// ─── Global customer activity tracking (all pages) ─────
initGlobalTracking( router );

/**
 * Global error handler — safety net for unhandled Vue errors.
 * Component-level errors should be caught by <ErrorBoundary> first;
 * this catches anything that slips through.
 */
app.config.errorHandler = ( err, instance, info ) =>
{
    console.error( `[Vue Error] ${ info }:`, err );

    import( '@/store' ).then( ( { useNotificationsStore } ) =>
    {
        const notifications = useNotificationsStore();
        notifications.push( {
            type: 'error',
            message: 'حدث خطأ غير متوقع — يرجى تحديث الصفحة',
            priority: 10,
            persist: true,
        } );
    } ).catch( ( storeErr ) =>
    {
        console.warn( '[Vue Error] Could not load notifications store:', storeErr );
    } );
};

// Wait for lazy-loaded English locale (if user's saved locale is 'en')
// before mounting so the first render shows correct translations.
await i18nReady;

app.mount( '#app' );

// ─── Remove skeleton after Vue mounts ────────────────────────────
const appEl = document.getElementById( 'app' );
if ( appEl ) appEl.classList.add( 'vue-ready' );
requestAnimationFrame( () =>
{
    document.getElementById( 'app-skeleton' )?.remove();
} );

// ─── Auto-reload on stale Vite chunk errors (post-deployment) ───
// Catches dynamic imports that happen outside Vue Router (e.g. composables)
window.addEventListener( 'unhandledrejection', ( event ) =>
{
    const msg = String( event?.reason?.message || event?.reason || '' );
    if (
        msg.includes( 'Failed to fetch dynamically imported module' ) ||
        msg.includes( 'Importing a module script failed' )
    )
    {
        const key = 'chunk_reload';
        if ( !sessionStorage.getItem( key ) )
        {
            sessionStorage.setItem( key, '1' );
            console.warn( '[Insurance] Stale chunk detected — reloading', msg );
            window.location.reload();
        }
    }
} );

// Ensure global tracking cleanup on HMR / app teardown
if ( import.meta.hot )
{
    import.meta.hot.dispose( () => cleanupVisitorTracking() );
}
