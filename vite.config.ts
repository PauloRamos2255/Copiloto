import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { existsSync } from 'fs';
import { resolve } from 'path';

// Detectar si existe el archivo de rutas API
const apiRoutesExist = existsSync(resolve(__dirname, 'routes/api.php'));

// Activar Wayfinder solo cuando existan rutas y no se haya desactivado por ENV
const useWayfinder =
  apiRoutesExist && process.env.SKIP_WAYFINDER !== 'true';

export default defineConfig({
  plugins: [
    // Plugin Vue
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),

    // Plugin Laravel
    laravel({
      input: ['resources/js/app.ts'],
      ssr: 'resources/js/ssr.ts',
      refresh: true,
    }),

    // TailwindCSS nativo para Vite
    tailwindcss(),

    // Wayfinder (solo si se cumple la condición)
    useWayfinder &&
      wayfinder({
        formVariants: false,
      }),
  ].filter(Boolean), // Evita agregar valores null/false
});
