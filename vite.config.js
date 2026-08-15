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
            runtimeOnly: true,
            compositionOnly: true,
            fullInstall: false,
        } ),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            'vue-i18n': 'vue-i18n/dist/vue-i18n.runtime.esm-bundler.js',
        },
    },
    define: {
        __VUE_I18N_FULL_INSTALL__: false,
        __VUE_I18N_LEGACY_API__: false,
        __VUE_PROD_DEVTOOLS__: false,
        __INTLIFY_PROD_DEVTOOLS__: false,
    },
    build: {
        // ── Manual chunk splitting ──────────────────────────────
        // Breaks the monolithic vendor bundle into smaller, cacheable pieces.
        // Reduces main‑thread blocking and enables parallel HTTP/2 downloads.
        rollupOptions: {
            checks: {
                pluginTimings: false,
            },
            output: {
                manualChunks( id ) {
                    if ( id.includes( 'node_modules/vue/' ) || id.includes( 'node_modules/@vue/' ) || id.includes( 'node_modules/ة vue-router/' ) || id.includes( 'node_modules/pinia/' ) || id.includes( 'node_modules/vue-i18n/' ) || id.includes( 'node_modules/@intlify/' ) ) {
                        return 'vendor-vue';
                    }
                    if ( id.includes( 'node_modules/pusher-js/' ) || id.includes( 'node_modules/laravel-echo/' ) ) {
                        return 'vendor-echo';
                    }
                    if ( id.includes( 'node_modules/radix-vue/' ) || id.includes( 'node_modules/@floating-ui/' ) || id.includes( 'node_modules/@internationalized/' ) || id.includes( 'node_modules/@swc/' ) ) {
                        return 'vendor-ui';
                    }
                    if ( id.includes( 'node_modules/axios/' ) || id.includes( 'node_modules/dompurify/' ) || id.includes( 'node_modules/vue-toastification/' ) ) {
                        return 'vendor-utils';
                    }
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
