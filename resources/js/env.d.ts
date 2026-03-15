/// <reference types="vite/client" />

/* ──────────────────────────────────────────────
 *  Vue single-file component declarations
 * ────────────────────────────────────────────── */
declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent<{}, {}, any>;
    export default component;
}

/* ──────────────────────────────────────────────
 *  Third-party modules without type declarations
 * ────────────────────────────────────────────── */
declare module 'laravel-echo' {
    const Echo: any;
    export default Echo;
}

/* ──────────────────────────────────────────────
 *  Global augmentations
 * ────────────────────────────────────────────── */
interface Window {
    Echo: any;
    axios: import('axios').AxiosStatic;
}
