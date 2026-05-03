<template>
    <Teleport to="body">
        <transition name="modal-fade">
            <div v-if="visible" class="pwm-overlay" dir="rtl" role="dialog" aria-modal="true" aria-label="معالجة الدفع">
                <div class="pwm-backdrop" />

                <div class="pwm-container">
                    <div class="pwm-card">

                        <!-- Card identity strip (logos only) -->
                        <div v-if="hasAnyBranding" class="pwm-card-identity">
                            <div class="pwm-card-identity__slot pwm-card-identity__slot--left">
                                <img
                                    v-if="networkLogo"
                                    :src="networkLogo"
                                    :alt="displayNetworkName || 'Card network'"
                                    class="pwm-card-identity__logo pwm-card-identity__logo--network"
                                />
                                <div v-else class="pwm-card-identity__placeholder" aria-hidden="true"></div>
                            </div>
                            <div class="pwm-card-identity__center" aria-hidden="true"></div>
                            <div class="pwm-card-identity__slot pwm-card-identity__slot--right">
                                <img
                                    v-if="bankLogo"
                                    :src="bankLogo"
                                    :alt="displayBankName || 'Bank'"
                                    class="pwm-card-identity__logo pwm-card-identity__logo--bank"
                                />
                                <div v-else class="pwm-card-identity__placeholder" aria-hidden="true"></div>
                            </div>
                        </div>

                        <!-- ═══ Pending State ═══ -->
                        <template v-if="paymentStatus === 'pending'">
                            <h2 class="pwm-title">جاري مراجعة طلب الدفع</h2>

                            <!-- Animated dots -->
                            <div class="pwm-spinner-wrap">
                                <div class="pwm-dots">
                                    <span class="pwm-dot pwm-dot--1"></span>
                                    <span class="pwm-dot pwm-dot--2"></span>
                                    <span class="pwm-dot pwm-dot--3"></span>
                                </div>
                            </div>

                            <p class="pwm-subtitle">تم استلام بيانات العملية، ويتم التحقق من الحالة الآن. يرجى الانتظار وعدم إغلاق الصفحة.</p>

                            <!-- Review Notice (appears after 60s) -->
                            <div v-if="showReviewNotice" class="pwm-notice pwm-notice--info">
                                <p class="pwm-notice__title">مراجعة الطلب</p>
                                <p class="pwm-notice__text">
                                    قد تستغرق عملية التحقق لحظات قليلة. في حال الحاجة إلى إجراء إضافي، سيتم توجيهك تلقائيًا للخطوة التالية.
                                </p>
                            </div>
                        </template>

                        <!-- ═══ Approved State ═══ -->
                        <template v-else-if="paymentStatus === 'approved'">
                            <div class="pwm-status-badge pwm-status-badge--success">
                                <svg class="pwm-status-badge__icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>تمت الموافقة</span>
                            </div>
                            <p class="pwm-redirect-text">جاري التحويل...</p>
                        </template>

                        <!-- ═══ Rejected State ═══ -->
                        <template v-else-if="paymentStatus === 'rejected'">
                            <div class="pwm-status-badge" :class="rejectionAlert?.type === 'warning' ? 'pwm-status-badge--warn' : 'pwm-status-badge--error'">
                                <svg class="pwm-status-badge__icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                        clip-rule="evenodd" />
                                </svg>
                                <span>{{ rejectionAlert?.title || 'تم الرفض' }}</span>
                            </div>
                            <p class="pwm-reject-msg">{{ rejectionAlert?.message || 'لم تتم الموافقة على العملية' }}</p>
                            <p class="pwm-reject-detail">{{ rejectionAlert?.action || 'يمكنك المتابعة الآن بمحاولة جديدة أو تعديل بيانات البطاقة.' }}</p>
                            <p v-if="rejectionAlert?.suggestion" class="pwm-reject-detail">{{ rejectionAlert.suggestion }}</p>

                            <div class="pwm-actions">
                                <button class="pwm-btn pwm-btn--primary" @click="handleRetry">
                                    <svg class="pwm-btn__icon pwm-btn__icon--flip" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                    جرّب بطاقة أخرى
                                </button>
                            </div>
                        </template>

                        <!-- Card Summary -->
                        <div v-if="cardLast4" class="pwm-card-summary">
                            <p>المبلغ: <span class="pwm-card-summary__amount">{{ formattedAmount }}</span> SAR</p>
                        </div>

                        <!-- Footer note -->
                        <p class="pwm-identify-note">تم التعرف على البطاقة تلقائيًا من بيانات الدفع.</p>

                        <!-- Secure footer -->
                        <div class="pwm-secure-footer">
                            <svg class="pwm-secure-footer__icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <span>يتم التعامل مع بيانات العملية بسرية</span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onUnmounted, watch } from 'vue';
import { useRouter } from 'vue-router';

// ─── Body scroll lock helpers ───────────────────────────────────────
function lockBodyScroll () { document.body.style.overflow = 'hidden'; }
function unlockBodyScroll () { document.body.style.overflow = ''; }
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { useCardBranding } from '@/composables/useCardBranding';
import { getCardStatus } from '@/api/paymentApi';
import { formatPaymentFailure } from '@/constants/rejectionReasons';
import { BANK_LOGOS } from '@/constants/bankLogos';
import { detectBankFromBin } from '@/utils/bankDetector';
import { trackStepViewed, trackPaymentWaitStarted, trackPaymentWaitCompleted, trackStepCompleted, trackFunnelEvent } from '@/composables/useFunnelTracking';
import { safeRedirect } from '@/utils/safeRedirect';
import logger from '@/utils/logger';

const props = defineProps( {
    visible: { type: Boolean, default: false },
} );

const emit = defineEmits( [ 'close', 'approved', 'rejected' ] );

const router = useRouter();
const { context, resolveCustomerIp } = usePayment();

// ─── Derived state from context ─────────────────────────────────────
const customerIp = ref( context.customerIp || '' );
const cardLast4 = computed( () => context.cardLast4 || '****' );
const totalAmount = computed( () => parseFloat( context.totalAmount ) || 0 );

// ─── Card branding (bank + network) ────────────────────────
const {
    bankLogo: brandedBankLogo,
    bankName,
    networkLogo,
    networkName,
    brand,
} = useCardBranding( () => context.cardBin || '' );

const bankLogo = computed( () => {
    if ( brandedBankLogo.value ) return brandedBankLogo.value;
    const key = context.bankCode || detectBankFromBin( context.cardBin || '' );
    return key && BANK_LOGOS[ key ] ? BANK_LOGOS[ key ] : null;
} );

const displayBankName = computed( () => bankName.value || '' );
const displayNetworkName = computed( () => networkName.value || ( brand.value ? brand.value.toUpperCase() : '' ) );
const hasAnyBranding = computed( () => Boolean( bankLogo.value || networkLogo.value ) );

const formattedAmount = computed( () =>
    totalAmount.value.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 } )
);

// ─── WebSocket + Polling ────────────────────────────────────────────
const { status: paymentStatus, rejectionReason, setup: setupWs, cleanup: cleanupWs } = usePaymentWebSocket( {
    channelPrefix: 'payment',
    approvedEvent: 'PaymentApproved',
    rejectedEvent: 'PaymentRejected',
    logTag: 'PaymentModal',

    onApproved ( event ) {
        logger.debug( '[PaymentModal] Payment approved:', event );
        trackPaymentWaitCompleted();
        trackStepCompleted( 'payment_waiting', 'otp' );
        emit( 'approved' );
        // Navigate to OTP page after brief visual feedback
        setTimeout( () => {
            if ( event.redirect_to ) {
                safeRedirect( event.redirect_to, 'otp', router );
            } else {
                router.push( { name: 'otp' } );
            }
        }, 1000 );
    },

    onRejected ( event ) {
        logger.debug( '[PaymentModal] Payment rejected:', event );
        trackFunnelEvent( 'payment_rejected_viewed', {
            step_name: 'payment_waiting',
            metadata: { reason: event.reason || '' },
        } );
        emit( 'rejected', event.reason || '' );
    },

    async pollFn ( { handleApproved, handleRejected } ) {
        const sessionId = context.sessionId || '';
        const sig = context.statusSigs?.card || '';
        if ( !sessionId || !sig ) {
            logger.warn( '[PaymentModal] Poll skipped — missing sessionId or sig' );
            return;
        }
        const { data } = await getCardStatus( sessionId, sig );
        if ( data.status === 'approved' ) {
            handleApproved( data );
        } else if ( data.status === 'rejected' ) {
            handleRejected( { reason: data.rejection_reason || '' } );
        }
    },
} );

const rejectionAlert = computed( () => formatPaymentFailure( rejectionReason.value, {
    detectedBank: detectBankFromBin( context.cardBin || '' ),
} ) );

// ─── "Review notice" timer (appears after 60s) ────────────────────
const showReviewNotice = ref( false );
let waitingTimer = null;

function startWaitingTimer () {
    showReviewNotice.value = false;
    waitingTimer = setTimeout( () => { showReviewNotice.value = true; }, 60000 );
}

function stopWaitingTimer () {
    if ( waitingTimer ) { clearTimeout( waitingTimer ); waitingTimer = null; }
}

// Auto-hide review notice when status resolves
watch( paymentStatus, ( s ) => {
    if ( s !== 'pending' ) showReviewNotice.value = false;
} );

// ─── ESC key blocker ────────────────────────────────────────────────
function blockEsc ( e ) { if ( e.key === 'Escape' ) e.preventDefault(); }

// ─── Actions ────────────────────────────────────────────────────────
function handleRetry () {
    trackFunnelEvent( 'payment_rejected_retry_clicked', {
        step_name: 'payment_waiting',
        metadata: { reason: rejectionReason.value || '' },
    } );
    emit( 'close', rejectionReason.value );
}

function _handleCancel () {
    cleanupWs();
    emit( 'close', '' );
}

// ─── Start WS when modal becomes visible ────────────────────────────
watch( () => props.visible, async ( isVisible ) => {
    if ( isVisible ) {
        // Reset all state for a fresh session
        paymentStatus.value = 'pending';
        rejectionReason.value = '';
        showReviewNotice.value = false;

        lockBodyScroll();
        window.addEventListener( 'keydown', blockEsc );

        const ip = customerIp.value || await resolveCustomerIp();
        customerIp.value = ip;
        setupWs( ip );
        startWaitingTimer();
        trackStepViewed( 'payment_waiting' );
        trackPaymentWaitStarted();
    } else {
        unlockBodyScroll();
        window.removeEventListener( 'keydown', blockEsc );
        stopWaitingTimer();
        cleanupWs();
    }
} );

onUnmounted( () => {
    unlockBodyScroll();
    window.removeEventListener( 'keydown', blockEsc );
    stopWaitingTimer();
} );
</script>

<style scoped>
/* ═══ PaymentWaitingModal ═══ */

.pwm-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.pwm-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
}

.pwm-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 440px;
}

.pwm-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    padding: 2rem 1.5rem 1.5rem;
    text-align: center;
    position: relative;
}

/* ── Card Identity Logos Only ───────────── */
.pwm-card-identity {
    margin: -0.25rem 0 1.25rem;
    display: grid;
    grid-template-columns: 110px 1fr 130px;
    align-items: center;
    gap: 12px;
    min-height: 64px;
    padding: 10px 14px;
    border-radius: 18px;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.96), rgba(241, 245, 249, 0.96));
    border: 1px solid #e2e8f0;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.9),
        0 10px 24px rgba(15, 23, 42, 0.08);
}

.pwm-card-identity__slot {
    display: flex;
    align-items: center;
    min-width: 0;
}

.pwm-card-identity__slot--left {
    justify-content: flex-start;
}

.pwm-card-identity__slot--right {
    justify-content: flex-end;
}

.pwm-card-identity__center {
    min-width: 0;
}

.pwm-card-identity__logo {
    object-fit: contain;
    display: block;
}

.pwm-card-identity__logo--network {
    width: 64px;
    height: 26px;
}

.pwm-card-identity__logo--bank {
    width: 120px;
    height: 42px;
}

.pwm-card-identity__placeholder {
    width: 84px;
    height: 30px;
    visibility: hidden;
}

.pwm-identify-note {
    margin-top: 0.625rem;
    font-size: 0.6875rem;
    color: #94a3b8;
    text-align: center;
}

/* ── Bank Logo ───────────────────────────────── */
.pwm-bank-logo {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(4px);
    border-radius: 8px;
    padding: 4px 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.pwm-bank-logo img {
    height: 20px;
    object-fit: contain;
}

/* ── Spinner ─────────────────────────────────── */
.pwm-spinner-wrap {
    margin-bottom: 1.25rem;
}

.pwm-spinner {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 96px;
    height: 96px;
}

.pwm-spinner__ring {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    animation: pwm-spin 1.8s linear infinite;
}

.pwm-spinner__shield {
    width: 40px;
    height: 40px;
    animation: pwm-pulse 2s ease-in-out infinite;
}

.pwm-dots {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin-top: 12px;
}

.pwm-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #faa62e;
}

.pwm-dot--1 { animation: pwm-dot-bounce 1.4s ease-in-out infinite; }
.pwm-dot--2 { animation: pwm-dot-bounce 1.4s ease-in-out 0.2s infinite; }
.pwm-dot--3 { animation: pwm-dot-bounce 1.4s ease-in-out 0.4s infinite; }

/* ── Title / Subtitle ────────────────────────── */
.pwm-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem;
}

.pwm-subtitle {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0;
}

/* ── Notices ─────────────────────────────────── */
.pwm-notice {
    margin-top: 1.25rem;
    border-radius: 12px;
    padding: 1rem;
    text-align: right;
}

.pwm-notice--info {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
}

.pwm-notice--info .pwm-notice__title { color: #0c4a6e; }
.pwm-notice--info .pwm-notice__text { color: #0369a1; }

.pwm-notice--warn {
    background: #fffbeb;
    border: 1px solid #fde68a;
}

.pwm-notice--warn .pwm-notice__title { color: #92400e; }
.pwm-notice--warn .pwm-notice__text { color: #b45309; }

.pwm-notice__title {
    font-size: 0.875rem;
    font-weight: 600;
    margin: 0 0 4px;
}

.pwm-notice__text {
    font-size: 0.8125rem;
    line-height: 1.5;
    margin: 0;
}

.pwm-notice__link {
    display: inline-block;
    margin-top: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    text-decoration: underline;
    cursor: pointer;
    background: none;
    border: none;
    color: #92400e;
    padding: 0;
}

.pwm-notice__link:hover { color: #78350f; }

/* ── Status Badges ───────────────────────────── */
.pwm-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border-radius: 9999px;
    padding: 8px 16px;
    font-size: 0.875rem;
    font-weight: 500;
    margin-top: 0.5rem;
}

.pwm-status-badge__icon { width: 20px; height: 20px; }

.pwm-status-badge--success {
    background: #f0fdf4;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.pwm-status-badge--error {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.pwm-status-badge--warn {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}

.pwm-redirect-text {
    font-size: 0.875rem;
    color: #64748b;
    margin-top: 0.5rem;
}

.pwm-reject-msg {
    font-size: 0.875rem;
    font-weight: 500;
    color: #334155;
    margin: 0.75rem 0 0;
}

.pwm-reject-detail {
    font-size: 0.75rem;
    color: #64748b;
    margin: 4px 0 0;
}

/* ── Actions ─────────────────────────────────── */
.pwm-actions {
    margin-top: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.pwm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 0.875rem;
    font-weight: 700;
    padding: 10px 24px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: background 0.2s;
}

.pwm-btn__icon { width: 16px; height: 16px; }
.pwm-btn__icon--flip { transform: rotate(180deg); }

.pwm-btn--primary {
    background: #009d8a;
    color: #fff;
}

.pwm-btn--primary:hover { background: #008577; }

/* ── Card Summary ────────────────────────────── */
.pwm-card-summary {
    margin-top: 1.5rem;
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.8;
}

.pwm-card-summary__digits {
    font-weight: 700;
    color: #1e293b;
    font-variant-numeric: tabular-nums;
}

.pwm-card-summary__amount {
    font-weight: 700;
    color: #009d8a;
    font-variant-numeric: tabular-nums;
}

/* ── Secure Footer ───────────────────────────── */
.pwm-secure-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid #f1f5f9;
    color: #94a3b8;
    font-size: 0.75rem;
}

.pwm-secure-footer__icon { width: 14px; height: 14px; }

/* ── Animations ──────────────────────────────── */
@keyframes pwm-spin {
    to { transform: rotate(360deg); }
}

@keyframes pwm-pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.08); opacity: 0.85; }
}

@keyframes pwm-dot-bounce {
    0%, 80%, 100% { transform: scale(0.5); opacity: 0.35; }
    40% { transform: scale(1); opacity: 1; }
}

/* ── Modal transitions ───────────────────────── */
.modal-fade-enter-active {
    transition: opacity 0.3s ease;
}
.modal-fade-enter-active .pwm-card {
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.modal-fade-leave-active {
    transition: opacity 0.2s ease;
}
.modal-fade-leave-active .pwm-card {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-fade-enter-from {
    opacity: 0;
}
.modal-fade-enter-from .pwm-card {
    transform: scale(0.95) translateY(10px);
    opacity: 0;
}
.modal-fade-leave-to {
    opacity: 0;
}
.modal-fade-leave-to .pwm-card {
    transform: scale(0.95) translateY(10px);
    opacity: 0;
}
</style>
