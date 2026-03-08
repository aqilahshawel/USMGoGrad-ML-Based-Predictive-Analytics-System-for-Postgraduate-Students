import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        port: 5173,
        strictPort: true,
    },
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    'import',
                    'global-builtin',
                    'color-functions',
                    'mixed-decls',
                ],
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
            ],
            refresh: [
                'resources/views/**/*.blade.php',
                'routes/**/*.php',
                'app/Http/Controllers/**/*.php',
            ],
        }),
    ],
});
