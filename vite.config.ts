import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// Одна сборка IIFE с фиксированными именами: шаблон сайта подключает
// /local/js/dsefremov.shopclassic/dist/app.js и app.css через Bitrix\Main\Page\Asset.
export default defineConfig({
    plugins: [react(), tailwindcss()],
    define: { 'process.env.NODE_ENV': JSON.stringify('production') },
    build: {
        outDir: 'local/js/dsefremov.shopclassic/dist',
        emptyOutDir: true,
        sourcemap: false,
        lib: {
            entry: 'local/js/dsefremov.shopclassic/src/main.tsx',
            formats: ['iife'],
            name: 'BitrixShopClassic',
            fileName: () => 'app.js',
            cssFileName: 'app',
        },
    },
});
