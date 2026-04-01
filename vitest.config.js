import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';
import { fileURLToPath } from 'url';

const __dirname = fileURLToPath( new URL( '.', import.meta.url ) );

export default defineConfig( {
    plugins: [ vue() ],
    resolve: {
        alias: {
            '@': resolve( __dirname, 'resources/js' ),
        },
    },
    test: {
        globals: true,
        environment: 'happy-dom',
        include: [ 'tests/js/**/*.{test,spec}.{js,ts}' ],
        coverage: {
            provider: 'v8',
            include: [ 'resources/js/**' ],
        },
    },
} );
