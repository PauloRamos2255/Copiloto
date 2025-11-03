import { createApp } from 'vue';
import ZonaComponent from './components/ZonaComponent.vue';
import MapaComponent from '../components/MapaComponent.vue'


const app = createApp({});
app.component('zona-component', ZonaComponent);
app.component('mapa-component', MapaComponent);
app.mount('#app');
