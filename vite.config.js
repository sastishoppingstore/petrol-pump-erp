import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/sass/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                // Bootstrap 5.3 emits a lot of deprecation noise from Dart Sass.
                quietDeps: true,
                silenceDeprecations: ['color-functions', 'global-builtin', 'import'],
            },
        },
    },
    server: {
        host: '127.0.0.1',
        port: 5173,
    },
});
