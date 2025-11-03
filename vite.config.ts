import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { existsSync } from 'fs';
import { resolve } from 'path';

// Verificar si las rutas API existen antes de usar Wayfinder
const apiRoutesExist = existsSync(resolve(__dirname, 'routes/api.php'));
const useWayfinder = apiRoutesExist && process.env.SKIP_WAYFINDER !== 'true';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        // Solo usar Wayfinder si las rutas existen
        useWayfinder && wayfinder({
            formVariants: false,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ].filter(Boolean), // Filtra valores falsy (false, null, undefined)
});