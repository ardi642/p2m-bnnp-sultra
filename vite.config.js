import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/filepond.js', 'resources/css/tom-select.css', 'resources/css/filepond.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
