<template>
    <div class="flex flex-col items-center justify-center py-16 px-6 text-center">
        <!-- Error Icon -->
        <div class="w-16 h-16 rounded-full bg-destructive-foreground center mb-5">
            <svg class="w-8 h-8 text-destructive" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
        </div>

        <!-- Title -->
        <h2 class="typ-h2 text-foreground mb-2">{{ title }}</h2>

        <!-- Message -->
        <p class="typ-b1 text-muted max-w-md mb-6">
            <slot>{{ message }}</slot>
        </p>

        <!-- Details (dev-only) -->
        <details v-if="details" class="max-w-lg w-full mb-6 text-start">
            <summary class="typ-s2 text-muted cursor-pointer hover:text-foreground transition-colors">
                التفاصيل التقنية
            </summary>
            <pre class="mt-2 p-3 bg-slate-100 rounded-lg text-xs text-slate-700 overflow-x-auto whitespace-pre-wrap break-words ltr-nums"
                dir="ltr">{{ details }}</pre>
        </details>

        <!-- Retry Button -->
        <button v-if="showRetry" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg typ-t3 hover:bg-primary-dark transition-colors duration-200 cursor-pointer"
            @click="$emit('retry')">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round">
                <polyline points="23 4 23 10 17 10" />
                <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
            </svg>
            {{ retryLabel }}
        </button>
    </div>
</template>

<script setup>
/**
 * AppError — Reusable error display component.
 *
 * Designed to work as the #error slot content for <ErrorBoundary>.
 *
 * @example
 * <AppError
 *   title="حدث خطأ"
 *   message="لم نتمكن من تحميل الصفحة"
 *   :details="error.message"
 *   @retry="reset"
 * />
 */
defineProps( {
    /** Error title */
    title: { type: String, default: 'حدث خطأ' },
    /** User-friendly error message */
    message: { type: String, default: 'عذراً، حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.' },
    /** Technical details (shown in collapsible) */
    details: { type: String, default: '' },
    /** Whether to show retry button */
    showRetry: { type: Boolean, default: true },
    /** Retry button label — override when the action isn't a literal retry */
    retryLabel: { type: String, default: 'إعادة المحاولة' },
} );

defineEmits( [ 'retry' ] );
</script>
