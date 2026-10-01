import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@inertiajs/vue3': fileURLToPath(new URL('./resources/js/inertia.js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
