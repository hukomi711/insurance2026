import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

export default defineConfig( {
    plugins: [
        laravel( {
            input: [ 'resources/css/app.css', 'resources/js/app.js' ],
            refresh: [ 'resources/views/**' ],
        } ),
        tailwindcss(),
        vue( {
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        } ),
        VueI18nPlugin( {
            include: resolve( dirname( fileURLToPath( import.meta.url ) ), './resources/js/i18n/locales/**' ),
        } ),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue-i18n': 'vue-i18n/dist/vue-i18n.runtime.esm-bundler.js',
        },
    },
    define: {
        __VUE_I18N_FULL_INSTALL__: true,
        __VUE_I18N_LEGACY_API__: false,
        __INTLIFY_PROD_DEVTOOLS__: false,
    },
    build: {
        // ── Manual chunk splitting ──────────────────────────────
        // Breaks the monolithic vendor bundle into smaller, cacheable pieces.
        // Reduces main‑thread blocking and enables parallel HTTP/2 downloads.
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-vue': [ 'vue', 'vue-router', 'pinia', 'vue-i18n' ],
                    'vendor-echo': [ 'pusher-js', 'laravel-echo' ],
                    'vendor-ui': [ 'radix-vue' ],
                    'vendor-utils': [ 'axios', 'dompurify', 'vue-toastification' ],
                },
            },
        },
    },
    server: {
        host: 'localhost',
        proxy: {
            '/api': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '/broadcasting': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
            '/sanctum': {
                target: 'http://localhost:8000',
                changeOrigin: true,
                secure: false,
            },
        },
        watch: {
            ignored: [ '**/storage/framework/views/**' ],
        },
        hmr: {
            // Reduce HMR overlay noise — CSS updates are silent
            overlay: false,
        },
    },
} );
