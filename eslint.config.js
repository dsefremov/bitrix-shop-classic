import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';

export default [
    js.configs.recommended,
    ...typescript.configs.recommended,
    {
        ...react.configs.flat.recommended,
        ...react.configs.flat['jsx-runtime'],
        languageOptions: {
            globals: { ...globals.browser },
        },
        rules: {
            ...react.configs.flat.recommended.rules,
            ...react.configs.flat['jsx-runtime'].rules,
            'react/prop-types': 'off',
        },
        settings: { react: { version: 'detect' } },
    },
    {
        plugins: { 'react-hooks': reactHooks },
        rules: {
            'react-hooks/rules-of-hooks': 'error',
            'react-hooks/exhaustive-deps': 'warn',
        },
    },
    {
        files: ['vite.config.ts', 'eslint.config.js', 'e2e/**', 'playwright.config.ts'],
        languageOptions: { globals: { ...globals.node } },
    },
    {
        ignores: ['bitrix', 'upload', 'vendor', 'node_modules', 'local/js/dsefremov.shopclassic/dist', 'var', 'test-results', 'playwright-report'],
    },
    prettier,
];
