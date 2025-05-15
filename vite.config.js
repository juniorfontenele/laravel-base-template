import { sentryVitePlugin } from "@sentry/vite-plugin";
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import i18n from 'laravel-react-i18n/vite';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [laravel({
        input: ['resources/css/app.css', 'resources/js/app.tsx'],
        ssr: 'resources/js/ssr.jsx',
        refresh: true,
    }), react(), tailwindcss(), i18n(), sentryVitePlugin({
        org: "jf-tecnologia",
        project: "jf-tecnologia"
    })],

    esbuild: {
        jsx: 'automatic',
    },

    server: {
        hmr: {
            host: 'localhost',
        },
    },

    build: {
        sourcemap: true
    }
});