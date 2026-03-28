import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';

export default [
    /* ── Ignore build artifacts & vendor ────────────────────────────── */
    {
        ignores: [
            'public/build/**',
            'vendor/**',
            'node_modules/**',
            'bootstrap/cache/**',
            'storage/**',
            '_ide_helper*.php',
        ],
    },

    /* ── Base JS recommended rules ─────────────────────────────────── */
    js.configs.recommended,

    /* ── Vue 3 recommended (includes essential + strongly-recommended) */
    ...pluginVue.configs[ 'flat/recommended' ],

    /* ── Project overrides ─────────────────────────────────────────── */
    {
        files: [ '**/*.vue', '**/*.js' ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                /* Browser globals */
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                console: 'readonly',
                setTimeout: 'readonly',
                clearTimeout: 'readonly',
                setInterval: 'readonly',
                clearInterval: 'readonly',
                requestAnimationFrame: 'readonly',
                cancelAnimationFrame: 'readonly',
                fetch: 'readonly',
                URL: 'readonly',
                URLSearchParams: 'readonly',
                FormData: 'readonly',
                Blob: 'readonly',
                File: 'readonly',
                FileReader: 'readonly',
                HTMLElement: 'readonly',
                MutationObserver: 'readonly',
                IntersectionObserver: 'readonly',
                ResizeObserver: 'readonly',
                Element: 'readonly',
                Event: 'readonly',
                CustomEvent: 'readonly',
                DOMParser: 'readonly',
                XMLSerializer: 'readonly',
                btoa: 'readonly',
                atob: 'readonly',
                alert: 'readonly',
                confirm: 'readonly',
                location: 'readonly',
                history: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
                crypto: 'readonly',
                prompt: 'readonly',
                AbortController: 'readonly',
                AbortSignal: 'readonly',
                structuredClone: 'readonly',
                queueMicrotask: 'readonly',
                /* Vite */
                'import.meta': 'readonly',
            },
        },
        rules: {
            /* ── JS relaxations ─────────────────────────────────── */
            'no-unused-vars': [ 'warn', { argsIgnorePattern: '^_', varsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' } ],
            'no-console': 'off',

            /* ── Vue relaxations for existing code style ─────── */
            'vue/html-indent': 'off',                    // custom indent style
            'vue/html-self-closing': 'off',              // project mixes self-close
            'vue/max-attributes-per-line': 'off',        // project uses multi-attr lines
            'vue/singleline-html-element-content-newline': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/first-attribute-linebreak': 'off',
            'vue/multi-word-component-names': 'off',     // some components are single-word
            'vue/no-v-html': 'off',                      // v-html used with DOMPurify (audited safe)
            'vue/no-mutating-props': 'warn',             // project mutates props (needs defineModel migration)
            'vue/require-default-prop': 'warn',          // warn, don't error
            'vue/require-prop-types': 'warn',
            'vue/attribute-hyphenation': 'off',          // project uses both styles
            'vue/v-on-event-hyphenation': 'off',
        },
    },
];
