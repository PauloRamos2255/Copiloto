import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, DefineComponent } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import Toast, { PluginOptions, POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import '@fortawesome/fontawesome-free/css/all.min.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Inicializa el tema antes de crear la app
initializeTheme();

const toastOptions: PluginOptions = {
  position: POSITION.TOP_RIGHT,
  timeout: 3000,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
};

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),

  // 🔥 Versión corregida con tipado correcto
  resolve: (name: string) =>
    resolvePageComponent(
      `./pages/${name}.vue`,
      import.meta.glob('./pages/**/*.vue')
    ) as unknown as Promise<DefineComponent>,

  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) });

    app.use(plugin);
    app.use(Toast, toastOptions);

    app.mount(el);
  },

  progress: {
    color: '#4B5563',
  },
});
