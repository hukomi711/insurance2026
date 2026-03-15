<template>
    <div class="relative">
        <SelectRoot v-bind="$attrs" v-model="model" :dir="direction" :disabled="disabled" :name="name">
            <SelectTrigger :id="id" :class="computedTriggerClass" :aria-label="label">
                <SelectValue :placeholder="placeholder" :class="computedValueClass" />
                <SelectIcon>
                    <!-- Filled arrow (floating default) -->
                    <svg v-if="computedIconVariant === 'taminkom'" height="20" width="20" viewBox="0 0 20 20"
                        class="text-slate-600 shrink-0">
                        <path
                            d="M4.516 7.548c0.436-0.446 1.043-0.481 1.576 0l3.908 3.747 3.908-3.747c0.533-0.481 1.141-0.446 1.574 0 0.436 0.445 0.408 1.197 0 1.615-0.406 0.418-4.695 4.502-4.695 4.502-0.217 0.223-0.502 0.335-0.787 0.335s-0.57-0.112-0.789-0.335c0 0-4.287-4.084-4.695-4.502s-0.436-1.17 0-1.615z"
                            fill="currentColor" />
                    </svg>
                    <!-- Stroke chevron (standard / card default) -->
                    <svg v-else class="w-4 h-4 shrink-0" :class="variantCfg.iconClass" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </SelectIcon>
            </SelectTrigger>
            <SelectPortal>
                <SelectContent :class="computedContentClass" position="popper" :side-offset="computedSideOffset"
                    :dir="direction">
                    <SelectScrollUpButton v-if="scrollButtons"
                        class="flex items-center justify-center h-6 bg-white cursor-default"
                        :class="variantCfg.scrollBtnClass">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                    </SelectScrollUpButton>
                    <SelectViewport :class="computedViewportClass">
                        <SelectItem v-for="opt in normalizedOptions" :key="opt.value" :value="opt.value"
                            :class="computedItemClass">
                            <SelectItemIndicator v-if="computedShowIndicator"
                                class="absolute left-2 inline-flex items-center">
                                <svg class="w-4 h-4 text-[var(--color-primary)]" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </SelectItemIndicator>
                            <SelectItemText>{{ opt.label }}</SelectItemText>
                        </SelectItem>
                    </SelectViewport>
                    <SelectScrollDownButton v-if="scrollButtons"
                        class="flex items-center justify-center h-6 bg-white cursor-default"
                        :class="variantCfg.scrollBtnClass">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </SelectScrollDownButton>
                </SelectContent>
            </SelectPortal>
        </SelectRoot>

        <!-- Floating label (only for variant="floating") -->
        <span v-if="variant === 'floating'"
            class="absolute top-px start-4 typ-b2 text-slate-500 transition-all capitalize pointer-events-none"
            :class="model ? 'opacity-100' : 'opacity-0'">
            {{ label }}
        </span>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    SelectRoot, SelectTrigger, SelectValue, SelectIcon, SelectPortal,
    SelectContent, SelectViewport, SelectItem, SelectItemText, SelectItemIndicator,
    SelectScrollUpButton, SelectScrollDownButton,
} from 'radix-vue';

defineOptions( { inheritAttrs: false } );

/**
 * @component AppSelect
 * Reusable dropdown wrapping Radix Vue Select with custom styling.
 *
 * Three built-in variants:
 *   "floating"  → Floating label inside trigger   (VehicleDetailsPage other-details)
 *   "standard"  → External label, focus ring       (VehicleDetailsPage purpose-of-use)
 *   "card"      → CSS-var colours, check indicator (VehiclePage)
 *
 * Every visual aspect can be overridden via *Class props.
 * Auto-detects RTL from <html lang="ar">.
 */

const props = defineProps( {
        /** v-model value (selected option value) */
        modelValue: { type: [String, Number, Object], default: undefined },
    /** Array of options — strings, numbers, or { value, label } objects */
    options: { type: Array, required: true },

    /** Display label (floating label text or aria-label) */
    label: { type: String, default: '' },

    /** Unique id for the trigger element (for label association & autofill) */
    id: { type: String, default: undefined },

    /** HTML name attribute (for autofill / form submission) */
    name: { type: String, default: undefined },

    /** Placeholder when nothing is selected */
    placeholder: { type: String, default: 'اختر' },

    /** "floating" | "standard" | "card" */
    variant: {
        type: String,
        default: 'floating',
        validator: ( v ) => [ 'standard', 'floating', 'card' ].includes( v ),
    },

    /** Force direction — auto-detects from html lang if unset */
    dir: { type: String, default: null },

    /** Disable the select */
    disabled: { type: Boolean, default: false },

    /** Show scroll up / down buttons */
    scrollButtons: { type: Boolean, default: false },

    /** Error state — switches border colour */
    error: { type: Boolean, default: false },

    /** Force show / hide check indicator (null = use variant default) */
    showIndicator: { type: Boolean, default: null },

    /** Force icon type (null = use variant default) */
    iconVariant: { type: String, default: null },

    /** Force side-offset (null = use variant default) */
    sideOffset: { type: Number, default: null },

    /** Extra class appended to each item (e.g. "ltr-nums") */
    itemExtraClass: { type: String, default: '' },

    /* ── Class overrides (full replace when provided) ── */
    triggerClass: { type: String, default: '' },
    contentClass: { type: String, default: '' },
    itemClass: { type: String, default: '' },
    viewportClass: { type: String, default: '' },
} );

/** Two-way v-model via defineModel */
const model = defineModel({ type: [String, Number, Object], default: undefined });

/* ── Variant configuration map ── */
const VARIANTS = {
    floating: {
        trigger: 'bg-white rounded-lg border px-4 min-h-14 cursor-pointer gap-2 flex items-center w-full',
        triggerBorder: 'border-slate-300',
        triggerErrorBorder: 'border-red-500',
        content: 'mt-[-2px] border border-slate-200 bg-white rounded-b-lg overflow-hidden z-[9999] w-[var(--radix-select-trigger-width)]',
        item: 'flex items-center cursor-pointer p-3 gap-2 text-sm text-slate-900 select-none outline-none hover:bg-slate-50 data-[state=checked]:bg-green-100',
        viewport: 'max-h-60 overflow-y-auto',
        icon: 'taminkom',
        indicator: false,
        sideOffset: 0,
        iconClass: '',
        scrollBtnClass: 'text-slate-400',
    },
    standard: {
        trigger: 'w-full border rounded-lg px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white flex items-center justify-between min-h-14',
        triggerBorder: 'border-slate-300',
        triggerErrorBorder: 'border-red-500',
        content: 'mt-[-2px] border border-slate-200 bg-white rounded-b-lg overflow-hidden z-[9999] w-[var(--radix-select-trigger-width)]',
        item: 'flex items-center cursor-pointer p-3 gap-2 text-sm text-slate-900 select-none outline-none hover:bg-slate-50 data-[state=checked]:bg-green-100',
        viewport: 'max-h-60 overflow-y-auto',
        icon: 'chevron',
        indicator: false,
        sideOffset: 0,
        iconClass: 'text-slate-500',
        scrollBtnClass: 'text-slate-400',
    },
    card: {
        trigger: 'w-full border rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-[var(--color-primary)] focus:border-[var(--color-primary)] outline-none transition-all bg-white flex items-center justify-between',
        triggerBorder: 'border-[var(--color-border)]',
        triggerErrorBorder: 'border-[var(--color-danger)]',
        content: 'bg-white rounded-xl shadow-xl border border-[var(--color-border)] overflow-hidden z-[100]',
        item: 'relative flex items-center px-4 py-2.5 text-sm rounded-lg cursor-pointer select-none outline-none data-[highlighted]:bg-[var(--color-primary-light)] data-[highlighted]:text-[var(--color-primary)] data-[state=checked]:font-bold data-[state=checked]:text-[var(--color-primary)]',
        viewport: 'p-1 max-h-60 overflow-y-auto',
        icon: 'chevron',
        indicator: true,
        sideOffset: 4,
        iconClass: 'text-[var(--color-text-muted)]',
        scrollBtnClass: 'text-[var(--color-text-muted)]',
    },
};

/** Current variant config */
const variantCfg = computed( () => VARIANTS[ props.variant ] );

/** Auto-detect text direction */
const direction = computed( () => {
    if ( props.dir ) return props.dir;
    return document.documentElement.lang === 'ar' ? 'rtl' : 'ltr';
} );

/** Normalize options to { value, label } */
const normalizedOptions = computed( () =>
    props.options.map( ( opt ) => {
        if ( typeof opt === 'string' || typeof opt === 'number' ) {
            return { value: String( opt ), label: String( opt ) };
        }
        return { value: String( opt.value ), label: opt.label || String( opt.value ) };
    } ),
);

/* ── Resolved props (prop override → variant default) ── */
const computedIconVariant = computed( () => props.iconVariant || variantCfg.value.icon );
const computedShowIndicator = computed( () => ( props.showIndicator !== null ? props.showIndicator : variantCfg.value.indicator ) );
const computedSideOffset = computed( () => ( props.sideOffset !== null ? props.sideOffset : variantCfg.value.sideOffset ) );

/** Trigger classes — error border + disabled state */
const computedTriggerClass = computed( () => {
    if ( props.triggerClass ) return props.triggerClass;
    const cfg = variantCfg.value;
    const border = props.error ? cfg.triggerErrorBorder : cfg.triggerBorder;
    const disabled = props.disabled ? 'opacity-50 cursor-not-allowed' : '';
    return `${ cfg.trigger } ${ border } ${ disabled }`.trim();
} );

const computedContentClass = computed( () => props.contentClass || variantCfg.value.content );
const computedViewportClass = computed( () => props.viewportClass || variantCfg.value.viewport );

/** Item classes — supports itemExtraClass for appending e.g. "ltr-nums" */
const computedItemClass = computed( () => {
    const base = props.itemClass || variantCfg.value.item;
    return props.itemExtraClass ? `${ base } ${ props.itemExtraClass }` : base;
} );

/** Value classes — floating variant uses conditional pt-4 for descriptive placeholders */
const computedValueClass = computed( () => {
    const base = 'h-fit typ-b2 flex-1';
    const textAlign = direction.value === 'rtl' ? 'text-right' : 'text-left';

    if ( props.variant === 'floating' ) {
        // Descriptive placeholder (not default 'اختر') → pt-4 only when value is set
        const isDescriptive = props.placeholder !== 'اختر';
        const needsPadding = isDescriptive ? !!model.value : true;
        return `${ base } ${ needsPadding ? 'pt-4' : '' } ${ textAlign }`;
    }
    return `${ base } ${ textAlign }`;
} );
</script>
