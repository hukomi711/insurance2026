<script setup>
const props = defineProps( {
    steps: { type: Array, required: true },
    currentStep: { type: Number, required: true },
} );

function getStepMobileClass( index ) {
    if ( index < props.currentStep ) return 'text-green-600 border-b-green-600';
    if ( index === props.currentStep ) return 'text-blue-600 border-b-blue-600';
    return 'text-slate-400 border-b-slate-100';
}

function getStepDesktopClass( index ) {
    if ( index < props.currentStep ) return 'text-green-600 border-b-green-600';
    if ( index === props.currentStep ) return 'text-blue-600 border-b-blue-600';
    return 'text-slate-400 border-b-slate-100';
}

function getStepLabelClass( index ) {
    if ( index < props.currentStep ) return 'text-green-600';
    if ( index === props.currentStep ) return 'text-blue-600';
    return 'text-slate-400';
}
</script>

<template>
    <!-- Mobile (colored bars only) -->
    <div class="sm:hidden w-full">
        <div class="flex items-center w-full">
            <div v-for="( step, index ) in steps" :key="'m-' + index" class="flex-1 flex flex-col items-center">
                <div class="flex border-b-2 w-full" :class="getStepMobileClass( index )"></div>
            </div>
        </div>
    </div>

    <!-- Desktop (labeled tabs) -->
    <div dir="rtl" class="overflow-hidden hidden sm:block w-full">
        <div class="flex items-center">
            <div v-for="( step, index ) in steps" :key="'d-' + index"
                class="flex-1 flex flex-col items-center min-w-32">
                <div class="flex items-center gap-1 lg:gap-4 lg:text-sm text-xs lg:py-4 py-1 border-b-2 w-full justify-center cursor-pointer transition-colors"
                    :class="getStepDesktopClass( index )">
                    <!-- Checkmark for completed steps -->
                    <svg v-if="index < currentStep" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                        width="1em" height="1em" class="shrink-0 size-4 text-green-600">
                        <path d="M5 12L10 17L20 7" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    <span class="whitespace-nowrap pe-4" :class="getStepLabelClass( index )">
                        {{ step.label }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
