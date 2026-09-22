<template>
    <div class="otp" :class="[rootClasses, variant === 'dark' && 'otp--dark']">
        <!-- Auto Fill Status Indicator -->
        <transition name="fade">
            <div v-if="showAutoFillStatus" class="otp__auto-fill-status" :class="`otp__auto-fill-status--${autoFillState}`">
                <svg v-if="autoFillState === 'listening'" class="otp__auto-fill-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <svg v-else-if="autoFillState === 'received'" class="otp__auto-fill-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <svg v-else-if="autoFillState === 'error'" class="otp__auto-fill-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <span class="otp__auto-fill-text">{{ autoFillMessage }}</span>
            </div>
        </transition>

        <!-- Single input field with paste button -->
        <div class="otp__input-container">
            <div class="otp__field" :class="fieldClass">
                <input
                    :id="inputId"
                    ref="inputRef"
                    type="text"
                    :maxlength="maxLen"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    autocomplete="one-time-code"
                    name="otp-code"
                    :disabled="disabled"
                    :value="model"
                    :placeholder="placeholder"
                    :aria-label="`أدخل رمز التحقق المكون من ${maxLen} أرقام`"
                    :aria-invalid="!!error"
                    :aria-describedby="error ? `${inputId}-error` : undefined"
                    aria-live="polite"
                    autocapitalize="off"
                    autocorrect="off"
                    spellcheck="false"
                    enterkeyhint="done"
                    class="otp__input"
                    dir="ltr"
                    @input="handleInput"
                    @keydown="handleKeydown"
                    @focus="isFocused = true"
                    @blur="isFocused = false"
                />
            </div>

            <!-- Paste Button -->
            <button
                v-if="showPasteButton"
                type="button"
                class="otp__paste-btn"
                :aria-label="`لصق رمز التحقق من الحافظة`"
                :disabled="disabled"
                @click="handlePaste"
            >
                <svg class="otp__paste-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="otp__paste-text">{{ t('verification.otp.paste') }}</span>
            </button>
        </div>

        <!-- Error message -->
        <Transition name="otp-error">
            <p v-if="error" :id="`${inputId}-error`" role="alert" aria-live="assertive" class="otp__error">
                <svg class="otp__error-icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                        clip-rule="evenodd" />
                </svg>
                {{ error }}
            </p>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, useId, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useWebOtp } from '@/composables/useWebOtp';
import { normalizeAcceptedLengths, normalizeOtp } from '@/utils/otp';

defineOptions( { inheritAttrs: false } );

const props = defineProps( {
    length: { type: Number, default: 6, validator: v => [4, 5, 6, 7, 8].includes( v ) },
    acceptLengths: { type: Array, default: null },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    autoSubmit: { type: Boolean, default: false },
    variant: { type: String, default: 'light', validator: v => ['light', 'dark'].includes( v ) },
    showAutoFillUI: { type: Boolean, default: true },
} );

const emit = defineEmits( ['submit'] );
const { t } = useI18n();
const inputId = useId();

const inputRef = ref( null );
const model = defineModel( { type: String, default: '' } );
const isFocused = ref( false );
const isSubmitting = ref( false );

// WebOTP integration with callback to populate OTP when received
const { state: webOtpState, start: startWebOtp, stop: stopWebOtp, reset: resetWebOtp } = useWebOtp(
    (code) => {
        model.value = normalizeOtp( code, maxLen.value );
    }
);

const maxLen = computed( () => props.length );

const acceptedLengths = computed( () => {
    return normalizeAcceptedLengths( props.acceptLengths, maxLen.value );
} );

// Watch disabled prop to stop/start WebOTP
watch(
    () => props.disabled,
    ( isDisabled ) => {
        if ( isDisabled ) {
            stopWebOtp();
        } else {
            startWebOtp();
        }
    }
);

const isValidLength = computed( () => {
    const length = model.value.length;
    return acceptedLengths.value.length
        ? acceptedLengths.value.includes( length )
        : length === maxLen.value;
} );

const isComplete = computed( () => isValidLength.value );

// Auto Fill state
const autoFillState = computed( () =>
{
    if ( webOtpState.value === 'received' ) return 'received';
    if ( isComplete.value ) return 'complete';
    return webOtpState.value;
} );

const showAutoFillStatus = computed( () =>
    props.showAutoFillUI && (autoFillState.value === 'listening' || autoFillState.value === 'received' || autoFillState.value === 'error')
);

const autoFillMessage = computed( () =>
{
    switch ( autoFillState.value )
    {
        case 'listening': return t( 'verification.otp.waitingForCode' );
        case 'received': return t( 'verification.otp.codeReceived' );
        case 'error': return t( 'verification.otp.autoFillError' );
        default: return '';
    }
} );

const showPasteButton = computed( () =>
    !model.value && !props.disabled && typeof navigator !== 'undefined' && typeof navigator.clipboard?.readText === 'function'
);

const placeholder = computed( () => `${ '0'.repeat( maxLen.value ) }` );

const rootClasses = computed( () => ({
    'otp--disabled': props.disabled,
}) );

const fieldClass = computed( () => ({
    'otp__field--focused': isFocused.value,
    'otp__field--complete': isComplete.value && !props.error,
    'otp__field--error': !!props.error,
}) );

let hasSubmitted = false;

watch( isComplete, ( val ) =>
{
    if ( val && props.autoSubmit ) {
        submitIfReady();
    }

    if ( !val ) {
        hasSubmitted = false;
    }
} );

function submitIfReady ()
{
    if ( props.disabled || !isComplete.value || isSubmitting.value || hasSubmitted ) return;

    hasSubmitted = true;
    isSubmitting.value = true;
    nextTick( () => {
        emit( 'submit' );
        isSubmitting.value = false;
    } );
}

function handleInput ( e )
{
    const input = e.currentTarget;
    const normalized = normalizeOtp( input.value, maxLen.value );

    // Only update DOM if value differs (prevents unnecessary cursor movement)
    if ( input.value !== normalized ) {
        input.value = normalized;
    }

    // Update model only if different (prevents redundant watchers)
    if ( model.value !== normalized ) {
        model.value = normalized;
    }
}

function handleKeydown ( e )
{
    if ( e.key === 'Enter' && isComplete.value )
    {
        e.preventDefault();
        submitIfReady();
    }
}

async function handlePaste ()
{
    try
    {
        if ( typeof navigator?.clipboard?.readText !== 'function' ) return;

        const text = await navigator.clipboard.readText();
        const digits = normalizeOtp( text, maxLen.value );
        if ( digits.length > 0 )
        {
            model.value = digits;
            nextTick( () => inputRef.value?.focus() );
        }
    }
    catch
    {
        // Clipboard access may be unavailable or denied.
    }
}

function focusFirstEmpty ()
{
    nextTick( () => inputRef.value?.focus() );
}

onMounted( () =>
{
    startWebOtp();
} );

onUnmounted( () =>
{
    stopWebOtp();
} );

function clear ()
{
    model.value = '';
    hasSubmitted = false;
    isSubmitting.value = false;
    resetWebOtp();
    if ( !props.disabled ) {
        startWebOtp();
    }
    nextTick( () => inputRef.value?.focus() );
}

defineExpose( { focusFirstEmpty, clear } );
</script>

<style scoped>
/* ── Container ── */
.otp {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
}

/* ── Auto Fill Status Indicator ── */
.otp__auto-fill-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.2s ease;
    animation: slideInDown 0.3s ease;
}

.otp__auto-fill-status--listening {
    background-color: #f0f9ff;
    color: #0369a1;
    border: 1px solid #0ea5e9;
}

.otp__auto-fill-status--received {
    background-color: #f0fdf4;
    color: #166534;
    border: 1px solid #22c55e;
}

.otp__auto-fill-status--error {
    background-color: #fef2f2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.otp__auto-fill-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.otp__auto-fill-status--received .otp__auto-fill-icon {
    animation: none;
}

.otp__auto-fill-text {
    line-height: 1.25;
}

/* ── Input Container (with paste button) ── */
.otp__input-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    max-width: 17rem;
}

/* ── Field wrapper ── */
.otp__field {
    flex: 1;
    border-radius: 0.25rem;
    border: 1.5px solid #d1d5db;
    background: #fff;
    transition: all 0.15s ease;
    overflow: hidden;
}

/* ── Field states ── */
.otp__field--focused {
    border-color: #1a5276;
    background: #fff;
    box-shadow: 0 0 0 1.5px rgba(26, 82, 118, 0.1);
}

.otp__field--complete {
    border-color: #1a5276;
    background: #f8fafe;
}

.otp__field--error {
    border-color: #dc2626;
    background: #fef2f2;
}

/* ── Input element ── */
.otp__input {
    width: 100%;
    padding: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    text-align: center;
    border: none;
    background: transparent;
    color: #111827;
    transition: all 0.15s ease;
}

.otp__input::placeholder {
    color: #d1d5db;
}

.otp__input:focus {
    outline: none;
}

.otp__input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.otp--dark .otp__input {
    color: #f3f4f6;
}

.otp--dark .otp__input::placeholder {
    color: #6b7280;
}

.otp--dark .otp__field {
    border-color: #4b5563;
    background: #1f2937;
}

.otp--dark .otp__field--focused {
    border-color: #60a5fa;
    box-shadow: 0 0 0 1.5px rgba(96, 165, 250, 0.1);
}

.otp--dark .otp__field--complete {
    border-color: #60a5fa;
    background: #111827;
}

/* ── Paste Button ── */
.otp__paste-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    min-height: 2.75rem;
    min-width: 2.75rem;
    border-radius: 0.25rem;
    border: 1.5px solid #e5e7eb;
    background: #fff;
    color: #1a5276;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
}

.otp__paste-btn:hover:not(:disabled) {
    background: #f3f4f6;
    border-color: #1a5276;
    box-shadow: 0 1px 2px rgba(26, 82, 118, 0.1);
}

.otp__paste-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.otp__paste-icon {
    width: 1rem;
    height: 1rem;
}

.otp__paste-text {
    display: none;
}

@media (min-width: 480px) {
    .otp__paste-text {
        display: inline;
    }
}

.otp--dark .otp__paste-btn {
    border-color: #4b5563;
    background: #1f2937;
    color: #60a5fa;
}

.otp--dark .otp__paste-btn:hover:not(:disabled) {
    background: #111827;
    border-color: #60a5fa;
    box-shadow: 0 1px 2px rgba(96, 165, 250, 0.1);
}

/* ── Error message ── */
.otp__error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #dc2626;
    font-size: 0.875rem;
    margin: 0;
}

.otp__error-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex-shrink: 0;
}

/* ── Animations ── */
@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(-4px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(4px);
}

.otp-error-enter-active,
.otp-error-leave-active {
    transition: opacity 0.15s ease, transform 0.15s ease;
}

.otp-error-enter-from,
.otp-error-leave-to {
    opacity: 0;
    transform: translateX(-4px);
}

/* Respect user's motion preferences */
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
