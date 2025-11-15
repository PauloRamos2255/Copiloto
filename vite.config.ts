import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { existsSync } from 'fs';
import { resolve } from 'path';

// Verificar si las rutas API existen antes de usar Wayfinder
const apiRoutesExist = existsSync(resolve(__dirname, 'routes/api.php'));
const useWayfinder = apiRoutesExist && process.env.SKIP_WAYFINDER !== 'true';

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    laravel({
      input: ['resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),
    tailwindcss(),
    useWayfinder &&
      wayfinder({
        formVariants: false,
      }),
  ].filter(Boolean), // Filtra valores falsy (false, null, undefined)
});
