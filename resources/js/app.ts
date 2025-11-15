import '../css/app.css';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, DefineComponent } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import Toast, { PluginOptions, POSITION } from 'vue-toastification';
import 'vue-toastification/dist/index.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Inicializa tema antes de crear la app
initializeTheme();

// Configuración de Toast
const toastOptions: PluginOptions = {
  position: POSITION.TOP_RIGHT,
  timeout: 3000,
  closeOnClick: true,
  pauseOnHover: true,
  draggable: true,
};

// Configuración global de Axios
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

// Cache de componentes para evitar doble render
const pages = import.meta.glob('./pages/**/*.vue');

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),

  resolve: (name: string) =>
    resolvePageComponent(`./pages/${name}.vue`, pages) as unknown as Promise<DefineComponent>,

  setup({ el, App, props, plugin }) {
    // Solo un mount para evitar duplicados
    if (!el.hasAttribute('data-inertia-mounted')) {
      const app = createApp({ render: () => h(App, props) });
      app.use(plugin);
      app.use(Toast, toastOptions);
      app.mount(el);
      el.setAttribute('data-inertia-mounted', 'true');
    }
  },

  progress: { color: '#4B5563' },
});
