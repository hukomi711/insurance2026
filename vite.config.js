import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

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
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
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
