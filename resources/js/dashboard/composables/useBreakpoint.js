/**
 * Reactive breakpoint composable for the admin dashboard.
 *
 * Aligned with Tailwind v4 @theme breakpoints defined in app.css
 * so JS-side logic never disagrees with CSS-side `sm:`/`md:`/`lg:`/`xl:` modifiers.
 *
 *   sm: 575px
 *   md: 768px
 *   lg: 992px
 *   xl: 1280px
 *  2xl: 1400px
 *
 * Usage:
 *   const { isMobile, isTablet, isDesktop, current } = useBreakpoint();
 *   // isMobile  → < md  (< 768)
 *   // isTablet  → md..lg (768..<992)
 *   // isDesktop → >= lg (>= 992)
 */

import { onMounted, onBeforeUnmount, ref, computed } from 'vue';

export const BP = Object.freeze({
    sm: 575,
    md: 768,
    lg: 992,
    xl: 1280,
    '2xl': 1400,
});

let sharedWidth = null;
let listenerAttached = false;
const subscribers = new Set();

function syncWidth() {
    if (typeof window === 'undefined') return;
    const w = window.innerWidth;
    if (sharedWidth.value !== w) sharedWidth.value = w;
}

function ensureListener() {
    if (listenerAttached || typeof window === 'undefined') return;
    sharedWidth = sharedWidth ?? ref(window.innerWidth);
    window.addEventListener('resize', syncWidth, { passive: true });
    listenerAttached = true;
}

export function useBreakpoint() {
    if (sharedWidth === null) {
        sharedWidth = ref(typeof window !== 'undefined' ? window.innerWidth : BP.lg);
    }

    onMounted(() => {
        ensureListener();
        subscribers.add(syncWidth);
        syncWidth();
    });

    onBeforeUnmount(() => {
        subscribers.delete(syncWidth);
        if (subscribers.size === 0 && listenerAttached && typeof window !== 'undefined') {
            window.removeEventListener('resize', syncWidth);
            listenerAttached = false;
        }
    });

    const width = computed(() => sharedWidth.value);
    const isMobile = computed(() => sharedWidth.value < BP.md);
    const isTablet = computed(() => sharedWidth.value >= BP.md && sharedWidth.value < BP.lg);
    const isDesktop = computed(() => sharedWidth.value >= BP.lg);
    const isCompact = computed(() => sharedWidth.value < BP.lg); // mobile + tablet
    const current = computed(() => {
        const w = sharedWidth.value;
        if (w >= BP['2xl']) return '2xl';
        if (w >= BP.xl) return 'xl';
        if (w >= BP.lg) return 'lg';
        if (w >= BP.md) return 'md';
        if (w >= BP.sm) return 'sm';
        return 'xs';
    });

    return { width, isMobile, isTablet, isDesktop, isCompact, current };
}
