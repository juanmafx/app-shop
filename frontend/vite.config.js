import { defineConfig } from 'vitest/config';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: 'src/app.js',
            publicDirectory: '../backend/public',
            refresh: ['../backend/resources/views/**', '../backend/routes/**'],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/**/*.test.js'],
    },
});
