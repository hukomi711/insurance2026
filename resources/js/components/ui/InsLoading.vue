<template>
    <!-- Full-screen modal loader -->
    <Teleport v-if="modal" to="body">
        <Transition enter-active-class="transition-opacity duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-show="modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
                role="status" aria-busy="true">
                <div
                    class="flex flex-col items-center justify-center bg-white rounded-2xl p-8 shadow-lg animate-pulse max-w-xs w-full text-center">
                    <div :class="spinnerClasses"></div>
                    <p v-if="text || $slots.default" class="mt-3 text-foreground font-medium text-base">
                        <slot>{{ text }}</slot>
                    </p>
                    <p v-if="subText" class="mt-1 text-sm text-muted">{{ subText }}</p>
                </div>
            </div>
        </Transition>
    </Teleport>

    <!-- Inline / Button loader -->
    <div v-else class="inline-flex items-center gap-2" :class="alignClasses" role="status" aria-busy="true">
        <!-- Spinner -->
        <div :class="[spinnerBaseClasses, spinnerSizeClass, spinnerColorClass]" :style="spinnerStyle"></div>

        <!-- Text -->
        <span v-if="text || $slots.default" class="text-sm font-medium text-muted">
            <slot>{{ text }}</slot>
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps( {
    text: { type: String, default: '' },
    subText: { type: String, default: '' },
    modal: { type: Boolean, default: false },
    color: {
        type: String,
        default: 'primary',
        validator: v => [ 'primary', 'secondary', 'amber', 'emerald', 'white' ].includes( v ),
    },
    size: {
        type: String,
        default: 'md',
        validator: v => [ 'xs', 'sm', 'md', 'lg', 'xl', 'auto' ].includes( v ),
    },
    align: {
        type: String,
        default: 'center',
        validator: v => [ 'center', 'start', 'end' ].includes( v ),
    },
} );

const colorMap = {
    primary: 'border-blue-200 border-t-primary',
    secondary: 'border-green-100 border-t-secondary',
    amber: 'border-amber-100 border-t-amber-500',
    emerald: 'border-emerald-100 border-t-emerald-500',
    white: 'border-white/30 border-t-white',
};

const sizeMap = {
    xs: 'w-4 h-4 border-2',
    sm: 'w-6 h-6 border-2',
    md: 'w-10 h-10 border-3',
    lg: 'w-14 h-14 border-3',
    xl: 'w-16 h-16 border-4',
};

const spinnerBaseClasses = 'animate-spin rounded-full border-solid';

const spinnerColorClass = computed( () => colorMap[ props.color ] );

const spinnerSizeClass = computed( () => ( props.size !== 'auto' ? sizeMap[ props.size ] : '' ) );

const spinnerStyle = computed( () => {
    if ( props.size !== 'auto' ) return null;
    return { width: '1em', height: '1em', borderWidth: '2px' };
} );

const spinnerClasses = computed( () => {
    if ( props.size === 'auto' ) return `${ spinnerBaseClasses } ${ spinnerColorClass.value }`;
    return `${ spinnerBaseClasses } ${ spinnerSizeClass.value } ${ spinnerColorClass.value }`;
} );

const alignClasses = computed( () => {
    switch ( props.align ) {
        case 'start': return 'justify-start';
        case 'end': return 'justify-end';
        default: return 'justify-center';
    }
} );
</script>
