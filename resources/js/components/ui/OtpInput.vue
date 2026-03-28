<template>
    <div class="otp" :class="[rootClasses, variant === 'dark' && 'otp--dark']">
        <!-- Single conventional input field -->
        <div class="otp__field" :class="fieldClass">
            <input :id="inputId" ref="inputRef" type="text" :maxlength="maxLen" inputmode="numeric"
                autocomplete="one-time-code" name="otp-code" :disabled="disabled" :value="model"
                :placeholder="placeholder" :aria-label="`أدخل رمز التحقق المكون من ${maxLen} أرقام`"
                class="otp__input" dir="ltr" @input="handleInput" @keydown="handleKeydown"
                @focus="isFocused = true" @blur="isFocused = false" />
        </div>

        <!-- Error message -->
        <Transition name="otp-error">
            <p v-if="error" class="otp__error">
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
import { ref, computed, watch, nextTick, useId } from 'vue';

defineOptions( { inheritAttrs: false } );

const props = defineProps( {
    length: { type: Number, default: 6, validator: v => [4, 5, 6, 7, 8].includes( v ) },
    /** Optional array of accepted lengths — e.g. [4, 6] means auto-submit at 4 or 6 digits */
    acceptLengths: { type: Array, default: null },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: '' },
    autoSubmit: { type: Boolean, default: false },
    variant: { type: String, default: 'light', validator: v => ['light', 'dark'].includes( v ) },
} );

const emit = defineEmits( ['submit'] );

/** Effective max length — uses the largest acceptLength or falls back to `length` */
const maxLen = computed( () =>
    props.acceptLengths ? Math.max( ...props.acceptLengths ) : props.length
);

/** Set of accepted lengths for quick lookup */
const acceptSet = computed( () =>
    props.acceptLengths ? new Set( props.acceptLengths ) : null
);

/** Two-way v-model — strips non-digits */
const model = defineModel( {
    type: String,
    default: '',
    set ( value ) { return ( value || '' ).replace( /\D/g, '' ).slice( 0, maxLen.value ); },
} );

const inputId = `otp-${useId()}`;
const inputRef = ref( null );
const isFocused = ref( false );
let hasAutoSubmitted = false;

/** True when the current value matches any accepted length */
const isComplete = computed( () =>
    acceptSet.value
        ? acceptSet.value.has( model.value.length )
        : model.value.length === props.length
);

const placeholder = computed( () => '' );

const rootClasses = computed( () => ( {
    'otp--focused': isFocused.value && !isComplete.value,
    'otp--complete': isComplete.value && !props.error,
    'otp--error': !!props.error,
    'otp--disabled': props.disabled,
} ) );

const fieldClass = computed( () => ( {
    'otp__field--focused': isFocused.value && !isComplete.value,
    'otp__field--complete': isComplete.value && !props.error,
    'otp__field--error': !!props.error,
} ) );

// ── Auto-submit when an accepted length is reached ──
watch( model, ( val ) =>
{
    const isAccepted = acceptSet.value
        ? acceptSet.value.has( val.length )
        : val.length === props.length;

    if ( !isAccepted ) { hasAutoSubmitted = false; }
    if ( isAccepted && props.autoSubmit && !hasAutoSubmitted )
    {
        hasAutoSubmitted = true;
        nextTick( () => emit( 'submit' ) );
    }
} );

function handleInput ( e )
{
    model.value = e.target.value;
    e.target.value = model.value;
}

function handleKeydown ( e )
{
    if ( e.key === 'Enter' && isComplete.value ) emit( 'submit' );
}

function focusFirstEmpty ()
{
    nextTick( () => inputRef.value?.focus() );
}

function clear ()
{
    model.value = '';
    hasAutoSubmitted = false;
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
    gap: 0.375rem;
    width: 100%;
}

/* ── Field wrapper ── */
.otp__field {
    max-width: 14rem;
    width: 100%;
    border-radius: 0.25rem;
    border: 1.5px solid #d1d5db;
    background: #fff;
    transition: all 0.15s ease;
    overflow: hidden;
}

@media (min-width: 480px) {
    .otp__field {
        max-width: 15rem;
    }
}

@media (min-width: 640px) {
    .otp__field {
        max-width: 17rem;
    }
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
    animation: otp-field-shake 0.35s ease-in-out;
}

.otp--disabled .otp__field {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f1f5f9;
}

/* ── Input element ── */
.otp__input {
    width: 100%;
    padding: 0.3125rem 0.375rem;
    font-size: 1.0625rem;
    font-weight: 600;
    text-align: center;
    letter-spacing: 0.35em;
    color: #1f2937;
    background: transparent;
    border: none;
    outline: none;
    font-variant-numeric: tabular-nums;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    /* >= 16px prevents iOS Safari zoom */
}

@media (min-width: 480px) {
    .otp__input {
        padding: 0.375rem 0.5rem;
        font-size: 1.1875rem;
        letter-spacing: 0.4em;
    }
}

@media (min-width: 640px) {
    .otp__input {
        font-size: 1.375rem;
        padding: 0.5rem 0.625rem;
    }
}

.otp__input::placeholder {
    color: #cbd5e1;
    letter-spacing: 0.5em;
    font-weight: 400;
    font-size: 1.25rem;
}

.otp__input:disabled {
    cursor: not-allowed;
}

.otp--complete .otp__input {
    color: #1f2937;
}

.otp--error .otp__input {
    color: #b91c1c;
}

/* ── Error message ── */
.otp__error {
    display: inline-flex;
    align-items: center;
    gap: 0.1875rem;
    color: #b91c1c;
    font-size: 0.6875rem;
    font-weight: 600;
    text-align: center;
    margin: 0;
    padding: 0;
    background: transparent;
    border: none;
    border-radius: 0;
}

.otp__error-icon {
    width: 0.75rem;
    height: 0.75rem;
    flex-shrink: 0;
}

/* ── Error transition ── */
.otp-error-enter-active {
    transition: all 0.3s ease-out;
}
.otp-error-leave-active {
    transition: all 0.2s ease-in;
}
.otp-error-enter-from {
    opacity: 0;
    transform: translateY(-8px) scale(0.95);
}
.otp-error-leave-to {
    opacity: 0;
    transform: translateY(4px) scale(0.95);
}

@keyframes otp-field-shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-3px); }
    40% { transform: translateX(3px); }
    60% { transform: translateX(-2px); }
    80% { transform: translateX(1px); }
}

/* ══════════════════════════════════════════
   Dark variant (for dark backgrounds)
   ══════════════════════════════════════════ */
.otp--dark .otp__field {
    border-color: rgba(255, 255, 255, 0.2);
    background: rgba(255, 255, 255, 0.08);
}

.otp--dark .otp__field--focused {
    border-color: rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.15);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}

.otp--dark .otp__field--complete {
    border-color: #34d399;
    background: rgba(52, 211, 153, 0.15);
}

.otp--dark .otp__field--error {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.12);
}

.otp--dark .otp__input {
    color: #ffffff;
}

.otp--dark .otp__input::placeholder {
    color: rgba(255, 255, 255, 0.35);
}

.otp--dark.otp--complete .otp__input {
    color: #34d399;
}

.otp--dark.otp--error .otp__input {
    color: #fca5a5;
}

.otp--dark .otp__error {
    color: #fca5a5;
}

.otp--dark.otp--disabled .otp__field {
    background: rgba(255, 255, 255, 0.04);
}
</style>

