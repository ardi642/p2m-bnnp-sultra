import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',    // Semua CSS masuk sini
                'resources/js/app.js',      // Logic Layout & Alpine Core
                'resources/css/filepond.css',
                'resources/js/filepond.js', // Logic FilePond Terpisah
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});