<template>
    <component :is="tag" :key="resetKey">
        <slot v-if="!error" />
        <slot v-else name="error" :error="error" :reset="reset">
            <div class="flex flex-col items-center justify-center gap-4 p-8 text-center">
                <p class="text-red-600 font-medium">حدث خطأ غير متوقع</p>
                <p class="text-sm text-gray-500 max-w-md">{{ error?.message || 'خطأ غير معروف' }}</p>
                <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors" @click="reset">إعادة المحاولة</button>
            </div>
        </slot>
    </component>
</template>

<script setup>
/**
 * ErrorBoundary — Vue 3 equivalent of React's Error Boundary.
 *
 * Uses `onErrorCaptured` to catch errors from descendant components.
 * Provides two slots:
 *   - #default     → normal content (rendered when no error)
 *   - #error       → fallback UI with { error, reset } slot props
 *
 * @example
 * <ErrorBoundary>
 *   <template #default>
 *     <SomeComponent />
 *   </template>
 *   <template #error="{ error, reset }">
 *     <AppError :message="error.message" @retry="reset" />
 *   </template>
 * </ErrorBoundary>
 */
import { ref, onErrorCaptured } from 'vue';
import logger from '@/utils/logger';

const props = defineProps( {
    /** Wrapper element tag */
    tag: { type: String, default: 'div' },
    /** Optional callback when an error is captured */
    onError: { type: Function, default: null },
} );

const error = ref( null );
const resetKey = ref( 0 );

/**
 * Reset the boundary — clears error and re-mounts children
 * by changing the key, which forces Vue to recreate the subtree.
 */
function reset() {
    error.value = null;
    resetKey.value++;
}

onErrorCaptured( ( err, instance, info ) => {
    error.value = err;
    logger.error( `[ErrorBoundary] Caught in "${ info }":`, err );

    if ( props.onError ) {
        props.onError( err, instance, info );
    }

    // Return false to stop propagation to parent error handlers
    return false;
} );

defineExpose( { reset, error } );
</script>
