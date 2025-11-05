<template>
  <div id="mapa-page" class="relative h-screen w-full overflow-hidden">
    <!-- Header -->
    <header class="bg-gray-700 text-white flex items-center justify-between px-4 py-2 shadow-md z-20">
      <div class="flex items-center space-x-6">
        <div class="flex items-center space-x-2">
          <img src="/logo.png" alt="Logo" class="h-6" />
          <span class="text-lg font-semibold">Copiloto</span>
        </div>
        <nav class="flex items-center space-x-5 text-sm">
          <button class="flex items-center space-x-1 hover:text-blue-400">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="togglePanel">
            <i class="fas fa-globe"></i>
            <span>Segmentos</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="verUsuarios">
            <i class="fas fa-user"></i>
            <span>Usuarios</span>
          </button>
        </nav>
      </div>
      <div class="flex items-center space-x-4 relative">
        <span class="text-sm text-gray-300">{{ nombreUsuario }}</span>
        <button @click="toggleMenuUsuario" class="focus:outline-none">
          <i class="fas fa-ellipsis-v"></i>
        </button>
        <transition name="fade">
          <ul v-if="menuUsuarioAbierto"
            class="absolute right-0 top-10 bg-white text-gray-800 rounded-lg shadow-lg w-40 z-50">
            <li @click="cerrarSesion" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Cerrar sesión</li>
          </ul>
        </transition>
      </div>
    </header>

    <!-- Panel lateral izquierdo -->
    <transition name="slide">
      <aside v-if="panelAbierto" class="fixed left-0 top-12 bg-white w-96 shadow-lg z-30 overflow-hidden"
        style="height: calc(100% - 3rem);">
        <!-- Lista de segmentos -->
        <div v-if="!propiedadesAbiertas" class="h-full flex flex-col">
          <div class="sticky top-0 bg-white z-10 border-b p-4">
            <div class="flex justify-between items-center mb-4">
              <h2 class="text-lg font-semibold text-gray-800">Segmentos</h2>
              <button @click="actualizarSegmentos" :disabled="loadingActualizar"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm flex items-center space-x-1">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loadingActualizar }"></i>
                <span>{{ loadingActualizar ? 'Actualizando...' : 'Actualizar' }}</span>
              </button>
            </div>
            <div class="flex items-center space-x-2">
              <i class="fas fa-search text-gray-400"></i>
              <input type="text" v-model="busqueda" placeholder="Buscar segmento..."
                class="w-full border rounded px-2 py-1 focus:outline-none focus:ring focus:ring-blue-300" />
            </div>
          </div>
          <div class="flex-1 overflow-y-auto p-4">
            <div v-for="segmento in segmentosFiltrados" :key="segmento.id"
              class="flex justify-between items-center p-2 hover:bg-gray-100 rounded cursor-pointer mb-2"
              @click="centrarMapa(segmento)">
              <div class="flex items-center space-x-2">
                <div class="w-4 h-4 rounded border border-gray-300"
                  :style="{ backgroundColor: segmento.color || '#4d0000' }"></div>
                <span class="text-sm">{{ segmento.nombre }}</span>
              </div>
              <div class="flex items-center space-x-3 text-gray-600">
                <i class="fas fa-wrench cursor-pointer hover:text-blue-600" @click.stop="abrirPropiedades(segmento)"
                  title="Editar propiedades"></i>
                <i class="fas fa-times text-red-500 hover:text-red-700 cursor-pointer" title="Eliminar geocerca"
                  @click.stop="eliminarGeocerca(segmento)"></i>
              </div>
            </div>
          </div>
        </div>

        <!-- Formulario de propiedades -->
        <div v-else class="h-full flex flex-col">
          <!-- Header del formulario -->
          <div class="bg-gray-50 border-b p-4">
            <div class="flex items-center justify-between mb-3">
              <button @click="cerrarPropiedades" class="text-gray-600 hover:text-gray-800 flex items-center space-x-2">
                <i class="fas fa-arrow-left"></i>
                <span class="text-sm">Volver</span>
              </button>
              <h3 class="text-lg font-semibold text-gray-800">Propiedades</h3>
              <button @click="cerrarPropiedades" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
              </button>
            </div>
          </div>

          <!-- Formulario con scroll -->
          <div class="flex-1 overflow-y-auto p-4">
            <form @submit.prevent="guardarPropiedades" class="space-y-4">
              <!-- ID -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">ID</label>
                <input :value="segmentoEditado.codsegmento || segmentoEditado.id" type="text" readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100 text-gray-600" />
              </div>

              <!-- Nombre y Color -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Nombre <span
                    class="text-red-500">*</span></label>
                <div class="flex gap-2">
                  <input v-model="segmentoEditado.nombre" type="text" required
                    class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  <input v-model="segmentoEditado.color" type="color"
                    class="w-14 h-10 cursor-pointer border border-gray-300 rounded"
                    @input="colorChange($event.target.value)" />
                </div>
              </div>

              <!-- Velocidad -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Velocidad permitida</label>
                <div class="flex gap-2 items-center">
                  <input v-model.number="segmentoEditado.velocidad" type="number" min="0" max="200" step="1"
                    placeholder="0"
                    class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                  <span class="text-sm text-gray-500">km/h</span>
                </div>
              </div>

              <!-- Mensaje -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Mensaje</label>
                <textarea v-model="segmentoEditado.mensaje" rows="3" maxlength="500"
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                  placeholder="Mensaje de notificación al entrar/salir"></textarea>
                <div class="text-xs text-gray-400 mt-1 text-right">{{ (segmentoEditado.mensaje || '').length }}/500
                </div>
              </div>

              <!-- Estado -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
                <div class="flex items-center gap-3">
                  <select v-model="segmentoEditado.estado"
                    class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                  <span
                    :class="['px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap', segmentoEditado.estado == 1 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                    {{ segmentoEditado.estado == 1 ? '● Activo' : '● Inactivo' }}
                  </span>
                </div>
              </div>

              <!-- Tipo de geometría -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-2">Tipo de geometría</label>
                <div class="flex space-x-2 items-center">
                  <label v-for="tipo in tipos" :key="tipo.value"
                    class="cursor-pointer p-2 border rounded-md flex items-center justify-center min-w-[80px]"
                    :class="{ 'border-blue-500 bg-blue-100': selectedTipo === tipo.value, 'border-gray-300': selectedTipo !== tipo.value }"
                    @click="selectedTipo = tipo.value, onTipoChange(tipo.value)">
                    <span class="text-sm">{{ tipo.label }}</span>
                  </label>
                </div>
              </div>

              <!-- Coordenadas -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Coordenadas ({{
                  segmentoEditado.coordenadas.length }} puntos)</label>
                <div class="bg-gray-50 border border-gray-300 rounded p-3 max-h-32 overflow-y-auto">
                  <div v-if="segmentoEditado.coordenadas.length > 0" class="space-y-1">
                    <div v-for="(coord, idx) in coordenadasVisibles" :key="idx" class="text-xs text-gray-600 font-mono">
                      {{ idx + 1 }}. {{ coord.y?.toFixed(6) }}, {{ coord.x?.toFixed(6) }}
                    </div>
                    <div v-if="segmentoEditado.coordenadas.length > 3"
                      class="text-xs text-blue-600 italic cursor-pointer mt-1 hover:underline" @click="mostrarMas">
                      <span v-if="!verMas">... y {{ segmentoEditado.coordenadas.length - 3 }} más</span>
                      <span v-else>ver menos</span>
                    </div>
                  </div>
                  <div v-else class="text-xs text-gray-400 italic">Sin coordenadas</div>
                </div>
              </div>

              <!-- Bounds -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Límites geográficos</label>
                <div class="bg-gray-50 border border-gray-300 rounded p-3">
                  <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                      <span class="font-medium text-gray-700">Min X:</span>
                      <span class="text-gray-600 font-mono block">{{ bounds?.min_x?.toFixed(6) || 'N/A' }}</span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-700">Max X:</span>
                      <span class="text-gray-600 font-mono block">{{ bounds?.max_x?.toFixed(6) || 'N/A' }}</span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-700">Min Y:</span>
                      <span class="text-gray-600 font-mono block">{{ bounds?.min_y?.toFixed(6) || 'N/A' }}</span>
                    </div>
                    <div>
                      <span class="font-medium text-gray-700">Max Y:</span>
                      <span class="text-gray-600 font-mono block">{{ bounds?.max_y?.toFixed(6) || 'N/A' }}</span>
                    </div>
                  </div>
                  <div class="mt-2 pt-2 border-t border-gray-200 text-xs">
                    <span class="font-medium text-gray-700">Centro:</span>
                    <span class="text-gray-600 font-mono block">{{ bounds?.cen_y?.toFixed(6) || 'N/A' }}, {{
                      bounds?.cen_x?.toFixed(6) || 'N/A' }}</span>
                  </div>
                </div>
              </div>

              <!-- Área y Perímetro -->
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Área</label>
                <input :value="calcularAreaReal()" type="text" readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50" />
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Perímetro</label>
                <input :value="calcularPerimetroReal()" type="text" readonly
                  class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50" />
              </div>

              <!-- Fechas -->
              <div class="space-y-2">
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Creado</label>
                  <input :value="formatearFecha(segmentoEditado.created_at)" type="text" readonly
                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs bg-gray-50 text-gray-600" />
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Actualizado</label>
                  <input :value="formatearFecha(segmentoEditado.updated_at)" type="text" readonly
                    class="w-full border border-gray-300 rounded px-3 py-2 text-xs bg-gray-50 text-gray-600" />
                </div>
              </div>
            </form>
          </div>

          <!-- Botones fijos al fondo -->
          <div class="border-t bg-gray-50 p-4">
            <div class="flex gap-2">
              <button type="button" @click="cerrarPropiedades()"
                class="flex-1 px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 text-sm font-medium transition-colors">
                Cancelar
              </button>
              <button type="button" @click="limpiarFormulario"
                class="flex-1 px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 text-sm font-medium transition-colors">
                Limpiar
              </button>
              <button type="submit" @click="guardarPropiedades"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-medium transition-colors">
                Guardar
              </button>
            </div>
          </div>
        </div>
      </aside>
    </transition>

    <!-- Mapa -->
    <div id="mapa" class="h-full w-full"></div>
  </div>
</template>
<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-enter-active {
  transition: transform 0.3s ease-out;
}

.slide-enter-from {
  transform: translateX(-100%);
}

.slide-leave-active {
  transition: transform 0.3s ease-in;
}

.slide-leave-to {
  transform: translateX(-100%);
}

.map-label {
  background: white;
  border: 1px solid #ccc;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
}
</style>

<script>
import { defineComponent, ref } from "vue"; // IMPORTANTE: ref aquí
import { useToast } from "vue-toastification";
import "vue-toastification/dist/index.css";
import axios from "axios";
import * as turf from "@turf/turf";

export default defineComponent({
  name: "MapaComponent",

  data() {
    return {
      figuraActual: null,
      propiedadesAbiertas: false,
      tabActiva: 'geofences',
      mostrarColorPicker: false,
      panelAbierto: true,
      menuUsuarioAbierto: false,
      nombreUsuario: "Paulo Ramos",
      busqueda: "",
      segmentos: [],
      poligonos: [],
      etiquetas: [],
      poligonosMarcadores: [],
      selectedTipo: 'poligono',
      tipos: [
        { value: 'circulo', label: 'Círculo' },
        { value: 'poligono', label: 'Polígono' },
        { value: 'linea', label: 'Línea' }
      ],
      loadingActualizar: false,
      error: null,
      itemId: 402037903,
      lastSegmentosHash: {},
      autoRefreshInterval: null,
      verMas: false,
      colorOriginal: null,
      segmentoEditado: {
        id: null,
        codsegmento: '',
        nombre: '',
        color: '#4d0000',
        coordenadas: [],
        velocidad: 0,
        mensaje: '',
        estado: 1,
        bounds: {
          min_x: null,
          max_x: null,
          min_y: null,
          max_y: null,
          cen_y: null
        },
        area: '',
        created_at: null,
        updated_at: null
      }
    };
  },

  setup() {
    const toast = useToast();

    return { toast };
  },

  computed: {
    bounds() {
      let boundsData = this.segmentoEditado?.bounds || {};
      if (typeof boundsData === 'string') {
        try {
          boundsData = JSON.parse(boundsData);
        } catch {
          boundsData = {};
        }
      }
      return {
        min_x: boundsData.min_x ?? null,
        max_x: boundsData.max_x ?? null,
        min_y: boundsData.min_y ?? null,
        max_y: boundsData.max_y ?? null,
        cen_x: boundsData.cen_x ?? null,
        cen_y: boundsData.cen_y ?? null
      };
    },

    coordenadasArray() {
      let coords = this.segmentoEditado?.coordenadas || [];
      if (typeof coords === 'string') {
        try {
          coords = JSON.parse(coords);
        } catch {
          return [];
        }
      }
      if (!Array.isArray(coords)) return [];
      return coords;
    },

    coordenadasVisibles() {
      if (!this.segmentoEditado || !this.segmentoEditado.coordenadas) return [];
      return !this.verMas ? this.segmentoEditado.coordenadas.slice(0, 3) : this.segmentoEditado.coordenadas;
    },

    segmentosFiltrados() {
      if (!this.busqueda) return this.segmentos;
      const texto = this.busqueda.toLowerCase();
      return this.segmentos.filter(s => s.nombre?.toLowerCase().includes(texto));
    }
  },
  mounted() {
 this.inicializar()
  },

  beforeUnmount() {
    window.removeEventListener("resize", this._resizeHandler);
    if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
  },

  methods: {
    editarYCentrar(segmento) {
      // Ejemplo: abrir propiedades y centrar mapa
      this.abrirPropiedades(segmento);
      this.centrarMapa(segmento);
    },
    mostrarMas() {
      this.verMas = !this.verMas;
    },
   abrirPropiedades(segmento) {
  if (!segmento || !segmento.cordenadas) {
    console.warn("Segmento inválido:", segmento);
    return;
  }

  // Guardamos los datos del segmento
  this.segmentoEditado = {
    ...segmento,
    coordenadas: segmento.cordenadas || [],
  };

  this.propiedadesAbiertas = true;
  this.verMas = false;
  this.selectedTipo = this.segmentoEditado.tipo || 'poligono';

  // Centramos el mapa solo si hay coordenadas
  this.centrarMapa(this.segmentoEditado);

  // Dibujar preview del segmento
  this.vistaPreviaTipo(this.selectedTipo);

  // Dibujar puntos de edición
  this.dibujarPuntos(this.segmentoEditado);
},

convertirColor(color) {
  if (!color) return "#1E90FF";
  if (color.startsWith("#")) {
    if (color.length === 9) return `#${color.slice(3)}`; // Ignora alpha
    if (color.length === 7) return color;
  }
  return "#1E90FF";
},

vistaPreviaTipo(tipo) {
  // Borrar vista previa anterior
  if (this.previewShape) {
    this.previewShape.setMap(null);
    this.previewShape = null;
  }

  // Borrar marcadores de puntos anteriores
  if (this.previewMarkers) {
    this.previewMarkers.forEach(m => m.setMap(null));
  }
  this.previewMarkers = [];

  if (!this.segmentoEditado || !this.segmentoEditado.cordenadas?.length) return;

  const coords = this.segmentoEditado.cordenadas.map(c => ({ lat: parseFloat(c.y), lng: parseFloat(c.x) }));
  const colorBase = this.convertirColor(this.segmentoEditado.color);

  if (tipo === "poligono") {
    this.previewShape = new google.maps.Polygon({
      paths: coords,
      map: this.map,
      strokeColor: colorBase,
      fillColor: colorBase,
      strokeWeight: 2,
      fillOpacity: 0.5,
      editable: true
    });

    
  } else if (tipo === "linea") {
    this.previewShape = new google.maps.Polyline({
      path: coords,
      map: this.map,
      strokeColor: colorBase,
      strokeWeight: 3,
      editable: true
    });
   
  } else if (tipo === "circulo") {
    let latSum = 0, lngSum = 0;
    coords.forEach(p => { latSum += p.lat; lngSum += p.lng; });
    const center = { lat: latSum / coords.length, lng: lngSum / coords.length };

    let maxDist = 0;
    coords.forEach(p => {
      const dLat = (p.lat - center.lat) * 111000;
      const dLng = (p.lng - center.lng) * 111000 * Math.cos(center.lat * Math.PI / 180);
      const dist = Math.sqrt(dLat*dLat + dLng*dLng);
      if (dist > maxDist) maxDist = dist;
    });

    this.previewShape = new google.maps.Circle({
      center,
      radius: maxDist || 50,
      map: this.map,
      strokeColor: colorBase,
      fillColor: colorBase,
      fillOpacity: 0.3,
      editable: true
    });
  }
},

onTipoChange(nuevoTipo) {
  this.selectedTipo = nuevoTipo;
  this.vistaPreviaTipo(this.selectedTipo); // esto dibuja la figura correcta
},

    async guardarPropiedades() {
      try {
        const segmento = { ...this.segmentoEditado };

        if (segmento.color.length > 7) {
          segmento.color = segmento.color.slice(0, 7);
        }

        segmento.cordenadas = JSON.stringify(segmento.coordenadas || []);
        segmento.bounds = JSON.stringify(segmento.bounds || {});

        const res = await axios.post('http://localhost:8000/api/segmentos/guardar', {
          zonas: [segmento]
        });

        if (res.data.success) {
          const index = this.segmentos.findIndex(s => s.id === segmento.id);
          if (index !== -1) {
            this.segmentos.splice(index, 1, { ...this.segmentos[index], ...this.segmentoEditado });
          }

          this.mostrarNotificacion('Propiedades guardadas correctamente', 'exito');
          this.cerrarPropiedades();
          this.dibujarTodosSegmentos();
        } else {
          this.mostrarNotificacion('Error al guardar propiedades', 'error');
        }
      } catch (err) {
        console.error('Error al guardar propiedades:', err);
        this.mostrarNotificacion('Error al guardar propiedades', 'error');
      }
    },

    limpiarFormulario() {
      this.segmentoEditado.nombre = '';
      this.segmentoEditado.velocidad = 0;
      this.segmentoEditado.mensaje = '';
    },

    calcularAreaReal() {
      const b = this.segmentoEditado?.bounds;
      if (!b || b.min_x == null || b.max_x == null || b.min_y == null || b.max_y == null) {
        return '0 km²';
      }

      const latDiff = Math.abs(b.max_y - b.min_y) * 111;
      const lngDiff = Math.abs(b.max_x - b.min_x) * 111 * Math.cos((b.cen_y || ((b.max_y + b.min_y) / 2)) * Math.PI / 180);
      const areaKm2 = latDiff * lngDiff;

      if (areaKm2 < 1) {
        return `${(areaKm2 * 1_000_000).toFixed(2)} m²`;
      }
      return `${areaKm2.toFixed(4)} km²`;
    },

    calcularPerimetroReal() {
      const b = this.segmentoEditado?.bounds;
      if (!b || b.min_x == null || b.max_x == null || b.min_y == null || b.max_y == null) {
        return '0 km';
      }

      const latDiff = Math.abs(b.max_y - b.min_y) * 111;
      const lngDiff = Math.abs(b.max_x - b.min_x) * 111 * Math.cos((b.cen_y || ((b.max_y + b.min_y) / 2)) * Math.PI / 180);
      const perimetroKm = 2 * (latDiff + lngDiff);

      return `${perimetroKm.toFixed(3)} km (${(perimetroKm * 1000).toFixed(0)} m)`;
    },

    formatearFecha(fecha) {
      if (!fecha) return 'N/A';
      try {
        const date = new Date(fecha);
        return date.toLocaleString('es-PE', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        });
      } catch {
        return fecha;
      }
    },

    verUsuarios() {
      console.log('Ver usuarios');
    },

    cerrarSesion() {
      console.log('Cerrar sesión');
    },

    async inicializar() {
      await this.cargarGoogleMaps();
      this.inicializarMapa();
      await this.cargarSegmentos();
    },

    togglePanel() {
      this.panelAbierto = !this.panelAbierto;
    },

    toggleMenuUsuario() {
      this.menuUsuarioAbierto = !this.menuUsuarioAbierto;
    },

    mostrarNotificacion(mensaje, tipo = "info") {
      if (!this.toast) return;
      if (tipo === "exito") this.toast.success(mensaje);
      else if (tipo === "error") this.toast.error(mensaje);
      else this.toast.info(mensaje);
    },


   async cargarGoogleMaps() {
  if (window.google && window.google.maps && window.google.maps.marker) return;

  return new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyAwIMAPTeuBV2TJghm-1VTnOVl4yi4Y3rE&map_ids=abcd1234efgh5678&libraries=marker";
    script.async = true;
    script.defer = true;
    script.onload = () => {
      const check = () => {
        if (window.google && window.google.maps && window.google.maps.marker) {
          resolve();
        } else {
          setTimeout(check, 50);
        }
      };
      check();
    };
    script.onerror = (e) => reject(new Error("No se pudo cargar Google Maps: " + e.message));
    document.head.appendChild(script);
  });

},



    cerrarPropiedades() {
  this.propiedadesAbiertas = false;

  // Borrar forma de vista previa
  if (this.previewShape) {
    this.previewShape.setMap(null);
    this.previewShape = null;
  }

  // Borrar markers de puntos
  if (this.previewMarkers) {
    this.previewMarkers.forEach(marker => marker.setMap(null));
  }
  this.previewMarkers = [];

  // Limpiar segmento editado
  this.segmentoEditado = null;
  this.selectedTipo = null;
},


    limpiarMapa() {
      this.limpiarPuntos();
      this.limpiarDibujos();
    },

    normalizarCoordenadas(seg) {
      let raw = seg.p ?? seg.cordenadas ?? seg.coordenadas ?? seg.points ?? [];
      if (typeof raw === "string") {
        try { raw = JSON.parse(raw); } catch { raw = []; }
      }
      if (!Array.isArray(raw)) return [];
      const coords = raw.map(p => {
        if (p?.x !== undefined && p?.y !== undefined) return { x: Number(p.x), y: Number(p.y) };
        if (Array.isArray(p) && p.length >= 2) return { x: Number(p[0]), y: Number(p[1]) };
        if (p?.lon !== undefined || p?.lng !== undefined) return { x: Number(p.lon ?? p.lng), y: Number(p.lat ?? p.latitude ?? p.y) };
        return null;
      }).filter(Boolean).filter(pt => !Number.isNaN(pt.x) && !Number.isNaN(pt.y));
      const uniq = [];
      const seen = new Set();
      coords.forEach(pt => {
        const key = `${pt.x}-${pt.y}`;
        if (!seen.has(key)) {
          seen.add(key);
          uniq.push(pt);
        }
      });
      return uniq;
    },

    dibujarPuntos(segmento) {
      // 🧹 Elimina los puntos anteriores
      if (this.puntosActuales?.length) {
        this.puntosActuales.forEach(p => {
          try {
            p.map = null;
          } catch { }
        });
      }
      this.puntosActuales = [];

      // ⚠️ Validar coordenadas
      if (!segmento?.coordenadas?.length) {
        console.warn("El segmento no tiene coordenadas válidas:", segmento);
        return;
      }

      // 🎨 Asegura que el color esté en formato hexadecimal válido
      let color = segmento.color || "#FF0000";
      if (!/^#[0-9A-Fa-f]{6}$/.test(color)) {
        try {
          // Si llega en formato ARGB (#4d000000), lo convertimos a RGB
          color = "#" + color.slice(-6);
        } catch {
          color = "#FF0000";
        }
      }

    
    },


    // ✅ Función auxiliar para personalizar el contenido visual del marcador
    crearEtiquetaPunto(numero, color) {
      const div = document.createElement("div");
      div.style.width = "16px";
      div.style.height = "16px";
      div.style.borderRadius = "50%";
      div.style.backgroundColor = color;
      div.style.border = "2px solid white";
      div.style.boxShadow = "0 0 3px rgba(0,0,0,0.5)";
      div.style.display = "flex";
      div.style.alignItems = "center";
      div.style.justifyContent = "center";
      div.style.fontSize = "10px";
      div.style.color = "#fff";
      div.style.fontWeight = "bold";
      div.innerText = numero;
      return div;
    },

    limpiarPuntosPorSegmento(idSegmento) {
      if (!idSegmento) return;

      this.poligonosMarcadores = this.poligonosMarcadores.filter(marker => {
        if (marker.segmentoId === idSegmento) {
          marker.setMap(null);
          return false;
        }
        return true;
      });
    },





    actualizarColorSegmento(segmentoId, nuevoColor) {
      if (!this.poligonosMarcadores) return;

      this.poligonosMarcadores
        .filter(marker => marker.segmentoId === segmentoId)
        .forEach(marker => {
          const icon = marker.getIcon();
          if (icon) {
            // Creamos un nuevo objeto icon para evitar problemas de referencia
            marker.setIcon({
              ...icon,
              fillColor: nuevoColor
            });
          }
        });
    },

    // Limpiar puntos
    limpiarPuntos() {
      // Recorremos todos los marcadores y los removemos del mapa
      this.poligonosMarcadores.forEach(marker => {
        marker.setMap(null);
      });

      // Vaciamos el array para no mantener referencias
      this.poligonosMarcadores = [];
    },

inicializarMapa() {
  const mapaElemento = document.getElementById("mapa");
  if (!mapaElemento) return console.error("No se encontró el elemento con id='mapa'");

  this.map = new google.maps.Map(mapaElemento, {
    center: { lat: -12.0464, lng: -77.0428 },
    zoom: 12,
    mapId: "74e66b37b4757a8ec908633b"
  });

  google.maps.event.addListenerOnce(this.map, 'tilesloaded', () => {
    // 3. Ahora sí cargar los segmentos
    this.cargarSegmentos();
  });

},

 

dibujarSegmento(segmento, editable = false) {
  let figura;

  if (segmento.tipo === "polygon") {
    figura = new google.maps.Polygon({
      paths: segmento.cordenadas.map(c => ({ lat: c.y, lng: c.x })),
      fillColor: segmento.color ?? "#90EE90",
      strokeColor: segmento.color ?? "#32CD32",
      strokeWeight: 2,
      editable: editable,
      map: this.map
    });

  } else if (segmento.tipo === "polyline") {
    figura = new google.maps.Polyline({
      path: segmento.cordenadas.map(c => ({ lat: c.y, lng: c.x })),
      strokeColor: segmento.color ?? "#FFD700",
      strokeWeight: 3,
      editable: editable,
      map: this.map
    });

  } else if (segmento.tipo === "circle") {
    figura = new google.maps.Circle({
      center: { lat: segmento.cordenadas[0].y, lng: segmento.cordenadas[0].x },
      radius: segmento.radio || 100,
      fillColor: segmento.color ?? "#00BFFF",
      strokeColor: segmento.color ?? "#00BFFF",
      strokeWeight: 2,
      map: this.map
    });

    // Si es editable, agregar marcador central para mover
    if (editable) {
      segmento._marcadorCentro = new google.maps.Marker({
        position: figura.getCenter(),
        map: this.map,
        draggable: true,
        visible: true
      });

      // actualizar centro del círculo en tiempo real
      segmento._marcadorCentro.addListener("position_changed", () => {
        figura.setCenter(segmento._marcadorCentro.getPosition());
      });
    }
  }

  segmento._figura = figura;
},

async cargarSegmentos() {
  try {
    const { data } = await axios.get("http://localhost:8000/api/segmentos");

    if (!Array.isArray(data.segmentos)) return;

    this.segmentos = data.segmentos.map(s => ({
      ...s,
      tipo: this.detectarTipoSegmento(s),
      cordenadas: this.normalizarCoordenadas(s)
    }));

    this.dibujarTodosSegmentos();
  } catch (err) {
    console.error("Error al cargar segmentos:", err);
  }
},

dibujarTodosSegmentos() {
        if (!this.map) return;
        this.limpiarMapa();

        const bounds = new google.maps.LatLngBounds();

        this.segmentos.forEach(seg => {
          const coords = seg.cordenadas || [];
          if (coords.length < 2) return;

          const puntos = coords.map(c => ({ lat: parseFloat(c.y), lng: parseFloat(c.x) }));
          if (puntos.some(p => Number.isNaN(p.lat) || Number.isNaN(p.lng))) return;

          const polygon = new google.maps.Polygon({
            paths: puntos,
            strokeColor: "#000000",
            strokeWeight: 2,
            fillColor: seg.color ?? "#FF0000",
            fillOpacity: 0.6,
            zIndex: 100,
            map: this.map,
          });
          polygon.segmentoId = seg.id;
          this.poligonos.push(polygon);

          const path = puntos.map(p => [p.lng, p.lat]);
          path.push(path[0]);
          const centro = turf.pointOnFeature(turf.polygon([path])).geometry.coordinates;

          const label = new google.maps.OverlayView();
          const div = document.createElement("div");
          div.className = "map-label";
          div.innerText = seg.nombre;
          div.style.cssText = "background: rgba(255,255,255,0.8); padding:2px 5px; border-radius:4px; font-size:12px; position:absolute; white-space:nowrap; z-index:200;";

          label.onAdd = function () { this.getPanes().overlayLayer.appendChild(div); };
          label.draw = function () {
            const pos = this.getProjection().fromLatLngToDivPixel(new google.maps.LatLng(centro[1], centro[0]));
            div.style.left = pos.x + "px";
            div.style.top = pos.y + "px";
            div.style.transform = "translate(-50%, -50%)";
          };
          label.onRemove = function () { div.remove(); };
          label.segmentoId = seg.id;
          label.setMap(this.map);
          this.etiquetas.push(label);

          puntos.forEach(p => bounds.extend(p));
        });

        if (!bounds.isEmpty()) this.map.fitBounds(bounds);
      
}
,



    limpiarDibujos() {
      if (this.figuraActual) { this.figuraActual.setMap(null); this.figuraActual = null; }
      if (this.puntosActuales) { this.puntosActuales.forEach(p=>p.map=null); this.puntosActuales=[]; }
    },

    colorChange(color) {
      this.segmentoEditado.color = color;
      this.poligonosMarcadores.forEach(c => {
        if (c.segmentoId === this.segmentoEditado.id) {
          c.setOptions({ strokeColor: color, fillColor: color });
        }
      });

      this.poligonos.forEach(p => {
        if (p.segmentoId === this.segmentoEditado.id) {
          p.setOptions({ fillColor: color });
        }
      });
    },

    centrarMapa(segmento) {
      const coords = segmento.cordenadas || [];
      if (!coords.length || !this.map) return;

      const bounds = new google.maps.LatLngBounds();
      coords.forEach(c => bounds.extend(new google.maps.LatLng(c.y, c.x)));
      this.map.fitBounds(bounds);
    },

    async obtenerSID() {
      const { data } = await axios.get("http://localhost:8000/api/obtener-sid");
      if (data.success && data.sid) return data.sid;
      throw new Error(data.error || "No se pudo obtener el SID");
    },

     async actualizarSegmentos() {
        this.loadingActualizar = true;
        this.error = null;
        try {
          const sid = await this.obtenerSID();
          const { data } = await axios.post('http://localhost:8000/api/zone-data', { itemId: this.itemId, sid });
          if (!data.success || !Array.isArray(data.zones)) throw new Error('No se recibieron zonas válidas');
          const nuevosSegmentos = data.zones.map(z => ({
            id: z.id ?? z.codsegmento,
            nombre: z.n ?? z.nombre ?? 'Sin nombre',
            color: z.color ?? (z.c !== undefined ? '#' + Number(z.c).toString(16).padStart(6, '0') : '#FFFFFF'),
            cordenadas: this.normalizarCoordenadas(z),
            bounds: z.b ?? z.bounds ?? {}
          }));
          const res = await axios.post('http://localhost:8000/api/segmentos/guardar', { zonas: nuevosSegmentos });
          if (res.data.success) {
            this.segmentos = nuevosSegmentos;
            this.lastSegmentosHash = {};
            this.limpiarDibujos();
            this.dibujarTodosSegmentos();
            this.mostrarNotificacion(res.data.mensaje || 'Segmentos actualizados y guardados', 'exito');
          } else {
            this.mostrarNotificacion('No se pudieron guardar los segmentos', 'error');
          }
        } catch (err) {
          console.error('Error al actualizar segmentos:', err);
          this.error = err.message || JSON.stringify(err);
          this.mostrarNotificacion('Error al actualizar segmentos', 'error');
        } finally {
          this.loadingActualizar = false;
        }
      },

    async eliminarGeocerca(segmento) {
      if (!confirm(`¿Eliminar ${segmento.nombre}?`)) return;
      try {
        const res = await axios.delete(`http://localhost:8000/api/segmentos/${segmento.id}`);
        if (res.data.success) {
          this.segmentos = this.segmentos.filter(s => s.id !== segmento.id);

          const polIndex = this.poligonos.findIndex(p => p.segmentoId === segmento.id);
          if (polIndex !== -1) {
            this.poligonos[polIndex].setMap(null);
            this.poligonos.splice(polIndex, 1);
          }

          const lblIndex = this.etiquetas.findIndex(e => e.segmentoId === segmento.id);
          if (lblIndex !== -1) {
            this.etiquetas[lblIndex].setMap(null);
            this.etiquetas.splice(lblIndex, 1);
          }

          this.mostrarNotificacion("Geocerca eliminada correctamente", "exito");
        } else {
          this.mostrarNotificacion("No se pudo eliminar la geocerca", "error");
        }
      } catch (err) {
        console.error(err);
        this.mostrarNotificacion("Error al eliminar la geocerca", "error");
      }
    },

    detectarTipoSegmento(seg) {
      const coords = seg.cordenadas ?? seg.coordenadas ?? [];

      if (seg.tipo) return seg.tipo;

      if (coords.length === 1) return "circulo";

      if (coords.length === 2) return "linea";

      if (coords.length > 2) {
        return "poligono";
      }
      return "desconocido";
    },

  }}
    
);
</script>