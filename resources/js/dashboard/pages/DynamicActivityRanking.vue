<template>
  <div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-bold font-heading" :style="{ color: 'var(--admin-text)' }">
          <i class="fa-solid fa-ranking-star ml-2" :style="{ color: 'var(--admin-accent-blue)' }" aria-hidden="true"></i>
          ترتيب النشاطات الديناميكي
        </h1>
        <p class="text-sm mt-1" :style="{ color: 'var(--admin-text-muted)' }">
          آخر نشاط في الأعلى — مثل WhatsApp — مع تنبيه فوري عند حدوث أخطاء
        </p>
      </div>

      <div class="flex items-center gap-3">
        <!-- Error filter toggle -->
        <button
          class="rounded-xl px-4 py-2 text-sm font-semibold transition-all flex items-center gap-2"
          :class="errorsOnly
            ? 'bg-red-500/20 text-red-400 ring-1 ring-red-500/30'
            : ''"
          :style="!errorsOnly ? { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-secondary)' } : {}"
          @click="errorsOnly = !errorsOnly"
        >
          <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
          {{ errorsOnly ? 'الأخطاء فقط' : 'الكل' }}
          <span v-if="errorCount > 0" class="bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center ltr-nums">
            {{ errorCount > 99 ? '99+' : errorCount }}
          </span>
        </button>

        <!-- Audio toggle -->
        <button
          class="rounded-xl px-3 py-2 text-sm font-semibold transition-all"
          :class="audioEnabled
            ? 'bg-green-600/20 text-green-400'
            : ''"
          :style="!audioEnabled ? { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' } : {}"
          :title="audioEnabled ? 'إيقاف التنبيه الصوتي' : 'تفعيل التنبيه الصوتي'"
          @click="toggleAudio"
        >
          <i :class="audioEnabled ? 'fa-solid fa-volume-high' : 'fa-solid fa-volume-xmark'" aria-hidden="true"></i>
        </button>

        <!-- Manual refresh -->
        <button
          class="rounded-xl px-3 py-2 text-sm transition-colors"
          :style="{ backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
          :disabled="loading"
          @click="manualRefresh"
        >
          <i class="fa-solid fa-arrows-rotate text-xs" :class="{ 'fa-spin': loading }" aria-hidden="true"></i>
        </button>

        <!-- WS Status -->
        <span :class="wsConnected ? 'bg-green-500/15 text-green-400' : ''"
          :style="wsConnected ? {} : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }"
          class="text-xs font-medium px-3 py-1.5 rounded-full flex items-center gap-1.5">
          <span class="w-2 h-2 rounded-full" :class="wsConnected ? 'bg-green-500 animate-pulse' : ''"
            :style="wsConnected ? {} : { backgroundColor: 'var(--admin-text-dim)' }"></span>
          {{ wsConnected ? 'فوري' : 'استطلاع' }}
        </span>
      </div>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
      <div v-for="stat in statCards" :key="stat.key"
        class="rounded-xl p-4 transition-colors duration-200"
        :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs" :style="{ color: 'var(--admin-text-muted)' }">{{ stat.label }}</p>
            <p class="text-2xl font-bold mt-1 ltr-nums" :style="{ color: stat.color }">{{ formatNumber(stat.value) }}</p>
          </div>
          <div class="w-10 h-10 rounded-lg flex items-center justify-center" :style="{ backgroundColor: stat.bg }">
            <i :class="stat.icon" class="w-5 h-5" :style="{ color: stat.color }" aria-hidden="true"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Activity list -->
    <div class="rounded-2xl overflow-hidden transition-colors duration-200"
      :style="{ backgroundColor: 'var(--admin-card-bg)', borderWidth: '1px', borderColor: 'var(--admin-card-border)', boxShadow: 'var(--admin-card-shadow)' }">

      <!-- List header -->
      <div class="px-5 py-3 flex items-center justify-between"
        :style="{ borderBottomWidth: '1px', borderColor: 'var(--admin-border)' }">
        <h2 class="text-sm font-semibold font-heading" :style="{ color: 'var(--admin-text-secondary)' }">
          {{ errorsOnly ? 'الأخطاء الفعلية — 422 / فشل التحقق / فشل الطلب' : 'ترتيب حسب آخر نشاط' }}
        </h2>
        <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }">{{ displayActivities.length }} نشاط</span>
      </div>

      <!-- Animated list -->
      <TransitionGroup name="rank-list" tag="div" class="divide-y" :style="{ borderColor: 'var(--admin-border)' }">
        <div
          v-for="(activity, index) in displayActivities"
          :key="activity.id"
          class="flex items-center gap-4 px-5 py-4 transition-all duration-300"
          :class="[
            pulsingId === activity.id ? 'ring-1 ring-inset ring-green-500/50 bg-green-500/5' : 'hover:brightness-95',
            isErrorActivity(activity) ? 'border-r-[3px] border-red-500' : '',
          ]"
        >
          <!-- Rank badge -->
          <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold"
            :style="index < 3
              ? { backgroundColor: 'var(--admin-status-info-bg)', color: 'var(--admin-accent-blue)' }
              : { backgroundColor: 'var(--admin-surface-2)', color: 'var(--admin-text-muted)' }">
            <span class="ltr-nums">{{ index + 1 }}</span>
          </div>

          <!-- Activity icon -->
          <div class="flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center"
            :style="{ backgroundColor: getActivityIconBg(activity) }">
            <i :class="getActivityIcon(activity)" class="text-sm" :style="{ color: getActivityIconColor(activity) }" aria-hidden="true"></i>
          </div>

          <!-- Customer info -->
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
              <span class="font-semibold text-sm" :style="{ color: 'var(--admin-text)' }">
                {{ activity.customer_name || 'زائر' }}
              </span>
              <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }" dir="ltr">
                {{ activity.phone }}
              </span>
              <span v-if="activity.status === 'active'" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            </div>
            <p class="text-xs truncate" :style="{ color: 'var(--admin-text-muted)' }">
              {{ activity.description || activity.activity_type }}
            </p>
          </div>

          <!-- Stage badge -->
          <div class="flex-shrink-0">
            <span :class="getStageColor(activity.stage)" class="text-[10px] font-medium px-2 py-1 rounded-full">
              {{ getStageBadge(activity.stage) }}
            </span>
          </div>

          <!-- Status badge -->
          <div class="flex-shrink-0">
            <span :class="getStatusBadgeColor(activity.status)" class="text-[10px] font-medium px-2 py-1 rounded-full">
              {{ getStatusBadgeLabel(activity.status) }}
            </span>
          </div>

          <!-- Relative time -->
          <div class="flex-shrink-0 text-left w-20">
            <span class="text-xs ltr-nums" :style="{ color: 'var(--admin-text-dim)' }">
              {{ getRelativeTime(activity.created_at) }}
            </span>
          </div>
        </div>
      </TransitionGroup>

      <!-- Empty state -->
      <div v-if="displayActivities.length === 0" class="py-12 text-center" :style="{ color: 'var(--admin-text-dim)' }">
        <i class="fa-solid fa-inbox text-3xl mb-3" aria-hidden="true"></i>
        <p v-if="errorsOnly">لا توجد أخطاء حالياً</p>
        <p v-else>لا توجد نشاطات مطابقة</p>
        <p class="text-xs mt-2" :style="{ color: 'var(--admin-text-dim)' }">
          سيظهر هنا تحديث فوري عند قيام أي عميل بإدخال بيانات
        </p>
      </div>
    </div>

    <!-- Footer note -->
    <div class="mt-4 text-center">
      <p class="text-xs" :style="{ color: 'var(--admin-text-dim)' }">
        {{ wsConnected ? 'التحديثات فورية عبر WebSocket' : 'التحديثات تلقائية كل 5 ثوانٍ' }}
        — الترتيب حسب آخر نشاط
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, onActivated, onDeactivated } from 'vue';
import { fetchCustomerActivities } from '@/api/customerActivities';
import { formatNumber } from '@/utils/formatters';
import { registerPollingCallback, unregisterPollingCallback } from '@/services/adminPolling';
import { getEcho } from '@/services/echo';
import { enableSounds, playPayment } from '@/dashboard/composables/useAdminSounds';
import logger from '@/utils/logger';

defineOptions( { name: 'DynamicActivityRanking' } );

// ── State ──
const activities = ref( [] );
const stats = ref( { total: 0, active: 0, completed: 0, failed: 0 } );
const loading = ref( false );
const errorsOnly = ref( false );
const wsConnected = ref( false );
const pulsingId = ref( null );
const audioEnabled = ref( localStorage.getItem( 'ranking-audio-enabled' ) !== 'false' );

// ── Throttle ──
const NOTIFICATION_THROTTLE = 2500;
let lastNotificationTime = 0;
let pulseTimer = null;

// ── Error detection ──
const ERROR_PATTERN = /422|validation|fail|rejected|error|فشل/i;

function isErrorActivity ( activity ) {
    return ERROR_PATTERN.test( activity.activity_type ) ||
        ERROR_PATTERN.test( activity.description ) ||
        activity.status === 'failed';
}

// ── Computed ──
const errorCount = computed( () => activities.value.filter( isErrorActivity ).length );

const sortedActivities = computed( () => {
    return [ ...activities.value ].sort( ( a, b ) => {
        const timeA = new Date( a.created_at ).getTime();
        const timeB = new Date( b.created_at ).getTime();
        return timeB - timeA;
    } );
} );

const displayActivities = computed( () => {
    if ( errorsOnly.value ) {
        return sortedActivities.value.filter( isErrorActivity );
    }
    return sortedActivities.value;
} );

const statCards = computed( () => [
    {
        key: 'total', label: 'إجمالي النشاطات', value: stats.value.total,
        color: 'var(--admin-text)', bg: 'var(--admin-status-info-bg)',
        icon: 'fa-solid fa-chart-bar',
    },
    {
        key: 'active', label: 'نشط الآن', value: stats.value.active,
        color: 'var(--admin-accent-blue)', bg: 'var(--admin-status-info-bg)',
        icon: 'fa-solid fa-bolt',
    },
    {
        key: 'completed', label: 'مكتمل', value: stats.value.completed,
        color: 'var(--admin-accent-green)', bg: 'var(--admin-status-success-bg)',
        icon: 'fa-solid fa-circle-check',
    },
    {
        key: 'failed', label: 'أخطاء / فشل', value: stats.value.failed,
        color: 'var(--admin-accent-red)', bg: 'var(--admin-status-error-bg)',
        icon: 'fa-solid fa-triangle-exclamation',
    },
] );

// ── Audio ──
function toggleAudio () {
    audioEnabled.value = !audioEnabled.value;
    localStorage.setItem( 'ranking-audio-enabled', audioEnabled.value );
    if ( audioEnabled.value ) enableSounds();
}

function playErrorAlert () {
    if ( !audioEnabled.value ) return;
    const now = Date.now();
    if ( now - lastNotificationTime < NOTIFICATION_THROTTLE ) return;
    lastNotificationTime = now;
    enableSounds();
    playPayment();
    if ( navigator.vibrate ) navigator.vibrate( [ 100, 50, 100 ] );
}

// ── Visual pulse ──
function pulseActivity ( id ) {
    pulsingId.value = id;
    clearTimeout( pulseTimer );
    pulseTimer = setTimeout( () => { pulsingId.value = null; }, 2000 );
}

// ── API ──
async function loadActivities () {
    loading.value = true;
    try {
        const { data } = await fetchCustomerActivities( { per_page: 50 } );
        activities.value = data.activities || [];
        stats.value = data.stats || stats.value;
    } catch ( e ) {
        logger.error( '[ActivityRanking] فشل تحميل الأنشطة:', e );
    } finally {
        loading.value = false;
    }
}

function manualRefresh () {
    loadActivities();
}

// ── WebSocket ──
let _dashboardChannel = null;
let _adminOtpChannel = null;
let _adminPaymentChannel = null;
let _adminPhoneChannel = null;
let _pusherBindings = [];

function handleWsEvent ( event ) {
    logger.debug( '[ActivityRanking WS] Event:', event.activity_type || event.type, event.ip_address || event.customer_ip );

    // Refresh from API to get fresh sorted data
    loadActivities();

    // Check if this is an error event
    const type = event.activity_type || event.type || '';
    if ( ERROR_PATTERN.test( type ) || /rejected|failed/i.test( type ) ) {
        playErrorAlert();
        // Pulse the first matching activity after refresh
        setTimeout( () => {
            const match = activities.value.find( a =>
                a.activity_type === type ||
                ( event.ip_address && a.phone && a.phone.includes( event.ip_address ) )
            );
            if ( match ) pulseActivity( match.id );
        }, 500 );
    } else {
        // Non-error: still pulse the latest
        setTimeout( () => {
            if ( sortedActivities.value.length ) {
                pulseActivity( sortedActivities.value[ 0 ].id );
            }
        }, 500 );
    }
}

async function connectWebSocket () {
    try {
        const token = localStorage.getItem( 'auth_token' );
        if ( !token ) return;

        const echo = await getEcho();
        if ( !echo ) return;

        // Track Pusher connection state
        const pusher = echo.connector?.pusher;
        if ( pusher ) {
            if ( pusher.connection?.state === 'connected' ) {
                wsConnected.value = true;
            }
            const bind = ( event, handler ) => {
                pusher.connection.bind( event, handler );
                _pusherBindings.push( { connection: pusher.connection, event, handler } );
            };
            bind( 'connected', () => { wsConnected.value = true; } );
            bind( 'disconnected', () => { wsConnected.value = false; } );
        }

        // Dashboard channel — main activity stream
        _dashboardChannel = echo.private( 'dashboard' )
            .listen( '.customer.activity.updated', ( event ) => {
                handleWsEvent( { ...event, type: event.activity_type } );
            } )
            .listen( '.window.read.updated', () => { /* ignore for ranking */ } )
            .error( ( error ) => logger.warn( '[ActivityRanking WS] dashboard error:', error ) );

        // OTP channel — captures validation errors
        _adminOtpChannel = echo.private( 'admin.otp' )
            .listen( '.OtpApproved', ( e ) => handleWsEvent( { ...e, activity_type: 'otp_approved' } ) )
            .listen( '.OtpRejected', ( e ) => handleWsEvent( { ...e, activity_type: 'otp_rejected' } ) )
            .listen( '.PinApproved', ( e ) => handleWsEvent( { ...e, activity_type: 'pin_approved' } ) )
            .listen( '.PinRejected', ( e ) => handleWsEvent( { ...e, activity_type: 'pin_rejected' } ) )
            .error( ( error ) => logger.warn( '[ActivityRanking WS] admin.otp error:', error ) );

        // Payment channel — captures payment failures
        _adminPaymentChannel = echo.private( 'admin.payment' )
            .listen( '.PaymentApproved', ( e ) => handleWsEvent( { ...e, activity_type: 'payment_approved' } ) )
            .listen( '.PaymentRejected', ( e ) => handleWsEvent( { ...e, activity_type: 'payment_rejected' } ) )
            .error( ( error ) => logger.warn( '[ActivityRanking WS] admin.payment error:', error ) );

        // Phone channel — captures phone verification failures
        _adminPhoneChannel = echo.private( 'admin.phone' )
            .listen( '.PhoneOtpApproved', ( e ) => handleWsEvent( { ...e, activity_type: 'phone_approved' } ) )
            .listen( '.PhoneOtpRejected', ( e ) => handleWsEvent( { ...e, activity_type: 'phone_rejected' } ) )
            .error( ( error ) => logger.warn( '[ActivityRanking WS] admin.phone error:', error ) );

        logger.info( '[ActivityRanking WS] Subscribed to all channels' );
    } catch ( err ) {
        logger.error( '[ActivityRanking WS] Connection failed:', err.message );
        wsConnected.value = false;
    }
}

function disconnectWebSocket () {
    for ( const { connection, event, handler } of _pusherBindings ) {
        try { connection.unbind( event, handler ); } catch { /* safe */ }
    }
    _pusherBindings = [];
    _dashboardChannel = null;
    _adminOtpChannel = null;
    _adminPaymentChannel = null;
    _adminPhoneChannel = null;
}

// ── Helpers ──
function getRelativeTime ( isoDate ) {
    if ( !isoDate ) return 'الآن';
    const diffMs = Date.now() - new Date( isoDate ).getTime();
    const diffMin = Math.floor( diffMs / 60000 );
    if ( diffMin < 1 ) return 'الآن';
    if ( diffMin < 60 ) return `منذ ${ diffMin } د`;
    const diffHrs = Math.floor( diffMin / 60 );
    if ( diffHrs < 24 ) return `منذ ${ diffHrs } س`;
    return `منذ ${ Math.floor( diffHrs / 24 ) } يوم`;
}

function getStageColor ( stage ) {
    const map = {
        customer_info: 'bg-sky-100 text-sky-700',
        vehicle_info: 'bg-indigo-100 text-indigo-700',
        compare: 'bg-purple-100 text-purple-700',
        checkout: 'bg-amber-100 text-amber-700',
        payment: 'bg-teal-100 text-teal-700',
    };
    // Handle URL-based stages from the database
    if ( !stage ) return 'bg-gray-100 text-gray-700';
    if ( stage.startsWith( '/compare' ) || stage.startsWith( '/insurance/compare' ) ) return map.compare;
    if ( stage.startsWith( '/checkout' ) ) return map.checkout;
    if ( stage.startsWith( '/insurance/payment' ) || stage.startsWith( '/insurance/otp' ) || stage.startsWith( '/insurance/card' ) || stage.startsWith( '/insurance/stc' ) ) return map.payment;
    if ( stage.startsWith( '/motorapp' ) || stage.startsWith( '/insurance/vehicle' ) ) return map.vehicle_info;
    return map[ stage ] || 'bg-gray-100 text-gray-700';
}

function getStageBadge ( stage ) {
    if ( !stage ) return 'غير محدد';
    const map = {
        customer_info: 'معلومات العميل',
        vehicle_info: 'معلومات المركبة',
        compare: 'مقارنة الأسعار',
        checkout: 'إتمام الشراء',
        payment: 'الدفع',
    };
    if ( stage.startsWith( '/compare' ) ) return map.compare;
    if ( stage.startsWith( '/checkout' ) ) return map.checkout;
    if ( stage.startsWith( '/insurance/payment' ) || stage.startsWith( '/insurance/otp' ) || stage.startsWith( '/insurance/card' ) || stage.startsWith( '/insurance/stc' ) ) return map.payment;
    if ( stage.startsWith( '/motorapp' ) || stage.startsWith( '/insurance/vehicle' ) ) return map.vehicle_info;
    return map[ stage ] || stage;
}

function getStatusBadgeLabel ( status ) {
    const map = { active: 'نشط', completed: 'مكتمل', failed: 'فشل' };
    return map[ status ] || status;
}

function getStatusBadgeColor ( status ) {
    const map = {
        active: 'bg-blue-100 text-blue-700',
        completed: 'bg-green-100 text-green-700',
        failed: 'bg-red-100 text-red-700',
    };
    return map[ status ] || 'bg-gray-100 text-gray-700';
}

function getActivityIcon ( activity ) {
    if ( isErrorActivity( activity ) ) return 'fa-solid fa-circle-exclamation';
    const map = {
        active: 'fa-solid fa-signal',
        completed: 'fa-solid fa-circle-check',
        failed: 'fa-solid fa-triangle-exclamation',
    };
    return map[ activity.status ] || 'fa-solid fa-circle-info';
}

function getActivityIconColor ( activity ) {
    if ( isErrorActivity( activity ) ) return 'var(--admin-accent-red)';
    const map = {
        active: 'var(--admin-accent-blue)',
        completed: 'var(--admin-accent-green)',
        failed: 'var(--admin-accent-red)',
    };
    return map[ activity.status ] || 'var(--admin-text-muted)';
}

function getActivityIconBg ( activity ) {
    if ( isErrorActivity( activity ) ) return 'var(--admin-status-error-bg)';
    const map = {
        active: 'var(--admin-status-info-bg)',
        completed: 'var(--admin-status-success-bg)',
        failed: 'var(--admin-status-error-bg)',
    };
    return map[ activity.status ] || 'var(--admin-surface-2)';
}

// ── Lifecycle ──
let _mounted = false;

onMounted( () => {
    _mounted = true;
    loadActivities();
    connectWebSocket();
    registerPollingCallback( 'activityRanking', loadActivities );
    if ( audioEnabled.value ) enableSounds();
} );

onUnmounted( () => {
    _mounted = false;
    clearTimeout( pulseTimer );
    disconnectWebSocket();
    unregisterPollingCallback( 'activityRanking' );
} );

onActivated( () => {
    if ( !_mounted ) return;
    loadActivities();
    registerPollingCallback( 'activityRanking', loadActivities );
} );

onDeactivated( () => {
    clearTimeout( pulseTimer );
    unregisterPollingCallback( 'activityRanking' );
} );
</script>

<style scoped>
/* Smooth rank-based reorder animations */
.rank-list-enter-active,
.rank-list-leave-active {
    transition: all 0.4s ease;
}

.rank-list-enter-from {
    opacity: 0;
    transform: translateX(20px);
}

.rank-list-leave-to {
    opacity: 0;
    transform: translateX(-20px);
}

.rank-list-move {
    transition: transform 0.4s ease;
}
</style>
