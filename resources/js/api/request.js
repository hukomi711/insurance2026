import axios from "axios";
import { useNotificationsStore } from "@/store";
import logger from "@/utils/logger";
import { getSessionToken } from "@/utils/sessionToken";
import { markCustomerBlocked } from "@/utils/customerBlock";

/** Maximum number of automatic retries on 429 before giving up */
const MAX_429_RETRIES = 2;
const RETRY_COUNT_HEADER = "x-retry-count";

/**
 * Auth endpoints that should never be auto-retried on 429.
 * Retrying these burns through the security rate-limit window.
 */
const NO_RETRY_429_PATHS = [
    "/admin/login",
    "/admin/verify-code",
    "/admin/resend-code",
    "/otp/resend",
    "/phone-verification/resend",
];

/**
 * Auth form endpoints should manage their own 401/403 UI states
 * (e.g. login banner messages) and must not trigger global session-expired flow.
 */
const AUTH_FORM_PATHS = [
    "/admin/login",
    "/admin/verify-code",
    "/admin/resend-code",
];

/** Maximum number of automatic retries on 419 (CSRF mismatch) */
const MAX_419_RETRIES = 1;
const CSRF_RETRY_HEADER = "x-csrf-retry";

/** Whether CSRF cookie has already been initialized */
let csrfInitialized = false;

/**
 * Initialize Sanctum CSRF cookie.
 * Must be called once before the first state-changing request.
 * Coalesces concurrent calls to prevent duplicate fetches.
 * @returns {Promise<void>}
 */
let _csrfRefreshPromise = null;
export async function initCsrf ()
{
    if ( csrfInitialized ) return;
    if ( _csrfRefreshPromise ) return _csrfRefreshPromise;
    _csrfRefreshPromise = axios.get( "/sanctum/csrf-cookie" )
        .then( () => { csrfInitialized = true; } )
        .catch( ( err ) => { logger.warn( "[API] Failed to initialize CSRF cookie:", err.message ); } )
        .finally( () => { _csrfRefreshPromise = null; } );
    return _csrfRefreshPromise;
}

/**
 * Axios instance with pre-configured defaults
 * Ready for backend integration — just update baseURL
 */
const request = axios.create( {
    baseURL: "/api",
    timeout: 15000,
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
} );

/**
 * Request interceptor — inject auth token
 */
request.interceptors.request.use(
    ( config ) =>
    {
        // Inject Sanctum bearer token
        const token = localStorage.getItem( "auth_token" );
        if ( token )
        {
            config.headers.Authorization = `Bearer ${ token }`;
        }
        config._authToken = token || null;

        // Inject session token for customer identification
        config.headers[ "X-Session-Token" ] = getSessionToken();

        // Inject Echo socket ID so Laravel can exclude the sender with toOthers()
        const socketId = window.Echo?.socketId?.();
        if ( socketId )
        {
            config.headers[ "X-Socket-ID" ] = socketId;
        }

        // Laravel CSRF token
        const csrfToken = document.querySelector( 'meta[name="csrf-token"]' );
        if ( csrfToken )
        {
            config.headers[ "X-CSRF-TOKEN" ] = csrfToken.getAttribute( "content" );
        }

        return config;
    },
    ( error ) => Promise.reject( error ),
);

/**
 * Response interceptor — all errors route through Pinia notification store.
 * Queue + deduplication + priority are handled centrally.
 */
request.interceptors.response.use(
    ( response ) => response,
    async ( error ) =>
    {
        const { status } = error.response || {};
        const requestUrl = String( error.config?.url || "" );
        const isAdminMeRequest = requestUrl.includes( "/admin/me" );
        const silent = error.config?.silent === true || isAdminMeRequest;
        let notifications;
        try
        {
            notifications = useNotificationsStore();
        } catch
        {
            // Store not available (e.g. outside Vue app context)
            notifications = null;
        }

        // ── Network / Timeout errors (no response from server) ──
        if ( !error.response )
        {
            if ( error.code === "ECONNABORTED" )
            {
                if ( !silent ) logger.warn( "[API] Request timeout" );
                if ( !silent )
                {
                    notifications?.push( {
                        type: "warning",
                        message: "انتهت مهلة الطلب — يرجى المحاولة مرة أخرى",
                        priority: 5,
                        persist: false,
                    } );
                }
            } else
            {
                if ( !silent ) logger.warn( "[API] Network error — possibly offline" );
                if ( !silent )
                {
                    notifications?.push( {
                        type: "error",
                        message:
                            "لا يوجد اتصال بالإنترنت — تحقق من الشبكة وأعد المحاولة",
                        priority: 9,
                        persist: true,
                    } );
                }
            }
            return Promise.reject( error );
        }

        if ( status === 423 && error.response?.data?.blocked === true )
        {
            markCustomerBlocked( error.response.data );
            return Promise.reject( error );
        }

        if ( status === 401 || status === 403 )
        {
            const isAdminApiRequest = requestUrl.startsWith( "/admin/" );
            const isAuthFormEndpoint = AUTH_FORM_PATHS.some(
                ( p ) => requestUrl.includes( p ),
            );
            const currentToken = localStorage.getItem( "auth_token" );
            const requestToken = error.config?._authToken || null;
            const staleAuthFailure = currentToken && requestToken !== currentToken;

            const isAuthPage =
                window.location.pathname === "/login" ||
                window.location.pathname === "/admin-verify";

            if ( staleAuthFailure )
            {
                logger.warn( "[API] Ignoring stale admin auth failure from superseded token" );
                return Promise.reject( error );
            }

            if ( isAuthFormEndpoint || ( isAuthPage && !isAdminApiRequest ) )
            {
                return Promise.reject( error );
            }

            // Clear auth token only — preserve customer insurance form data
            localStorage.removeItem( "auth_token" );
            logger.warn(
                `[API] ${ status === 401 ? "Unauthorized" : "Forbidden" } — ${ status }`,
            );

            // Only redirect to login for admin/dashboard pages or protected admin API calls.
            // Public pages (blog, home, etc.) should not redirect visitors to /login
            const isAdminPage =
                window.location.pathname === "/login" ||
                window.location.pathname === "/admin-verify" ||
                window.location.pathname.startsWith( "/dashboard" );

            if ( isAdminPage || isAdminApiRequest )
            {
                notifications?.push( {
                    type: "error",
                    message: "انتهت الجلسة — يرجى تسجيل الدخول مرة أخرى",
                    priority: 10,
                    persist: true,
                } );

                if ( window.location.pathname !== "/login" )
                {
                    window.location.href =
                        "/login?redirect=" +
                        encodeURIComponent( window.location.pathname );
                }
            }
        }

        // ── CSRF token mismatch (session expired) ─────────────
        if ( status === 419 )
        {
            const csrfRetry = parseInt(
                error.config.headers?.[ CSRF_RETRY_HEADER ] || "0",
                10,
            );
            if ( csrfRetry >= MAX_419_RETRIES )
            {
                logger.warn( "[API] CSRF token mismatch — max retries reached" );
                notifications?.push( {
                    type: "error",
                    message: "انتهت صلاحية الجلسة — يرجى تحديث الصفحة",
                    priority: 8,
                    persist: true,
                } );
                return Promise.reject( error );
            }

            logger.warn( "[API] CSRF 419 — refreshing cookie and retrying" );
            // Reset the flag so initCsrf fetches a fresh cookie
            csrfInitialized = false;

            try
            {
                await initCsrf();
                error.config.headers = error.config.headers || {};
                error.config.headers[ CSRF_RETRY_HEADER ] = String( csrfRetry + 1 );
                // Update the CSRF token header from the freshly set cookie
                const freshToken = document.querySelector( 'meta[name="csrf-token"]' );
                if ( freshToken )
                {
                    error.config.headers[ "X-CSRF-TOKEN" ] = freshToken.getAttribute( "content" );
                }
                return request( error.config );
            } catch ( csrfErr )
            {
                logger.warn( "[API] Failed to refresh CSRF cookie:", csrfErr.message );
                notifications?.push( {
                    type: "error",
                    message: "انتهت صلاحية الجلسة — يرجى تحديث الصفحة",
                    priority: 8,
                    persist: true,
                } );
                return Promise.reject( error );
            }
        }

        if ( status === 429 )
        {
            if ( silent )
            {
                return Promise.reject( error );
            }

            // Rate limited — retry with limit to prevent infinite loops
            const retryCount = parseInt(
                error.config.headers?.[ RETRY_COUNT_HEADER ] || "0",
                10,
            );
            const isAuthPath = NO_RETRY_429_PATHS.some(
                ( p ) => error.config.url?.includes( p ),
            );
            if ( isAuthPath || retryCount >= MAX_429_RETRIES )
            {
                logger.warn(
                    isAuthPath
                        ? "[API] Rate limited on auth endpoint — not retrying"
                        : "[API] Rate limited — max retries reached, giving up",
                );
                notifications?.push( {
                    type: "error",
                    message:
                        "عدد كبير جداً من الطلبات — يرجى الانتظار قليلاً ثم إعادة المحاولة",
                    priority: 5,
                    persist: false,
                } );
                return Promise.reject( error );
            }

            const retryAfter = error.response.headers[ "retry-after" ] || 5;
            logger.warn(
                `[API] Rate limited — retry ${ retryCount + 1 }/${ MAX_429_RETRIES } after ${ retryAfter }s`,
            );
            notifications?.push( {
                type: "warning",
                message: "عدد كبير من الطلبات — يُعاد المحاولة تلقائيًا",
                priority: 3,
                persist: false,
            } );

            // Increment retry counter in config
            error.config.headers = error.config.headers || {};
            error.config.headers[ RETRY_COUNT_HEADER ] = String( retryCount + 1 );

            return new Promise( ( resolve ) =>
            {
                setTimeout(
                    () => resolve( request( error.config ) ),
                    retryAfter * 1000,
                );
            } );
        }

        if ( status === 422 )
        {
            if ( silent )
            {
                return Promise.reject( error );
            }

            // Validation error — pass through for form handling
            logger.warn( "[API] Validation error — 422", error.response.data );
            notifications?.push( {
                type: "warning",
                message: "يرجى التحقق من البيانات المدخلة",
                priority: 2,
                persist: false,
            } );
        }

        if ( status >= 500 )
        {
            if ( silent )
            {
                return Promise.reject( error );
            }

            logger.error( "[API] Server error", status );
            notifications?.push( {
                type: "error",
                message: "حدث خطأ في الخادم — يرجى المحاولة لاحقًا",
                priority: 8,
                persist: true,
            } );
        }

        return Promise.reject( error );
    },
);

export default request;
