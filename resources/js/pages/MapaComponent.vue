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
          <button @click="irADashboard()" class="flex items-center space-x-1 hover:text-blue-400">
            <i class="fas fa-chart-line"></i>
            <span>Dashboard</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="togglePanel">
            <i class="fas fa-globe"></i>
            <span>Segmentos</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="">
            <i class="fas fa-user"></i>
            <span>Usuarios</span>
          </button>
          <Button class="flex items-center space-x-1 text-blue-400" @click="irRutas()">
          <i class="fas fa-route"></i>
          <span>Rutas</span>
          </Button>
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
                  :style="{ backgroundColor: convertirColor(segmento.colorHex) || '#4d0000' }"></div>
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

              <!-- Tipo de geometría 
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
              </div>-->

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
import Button from "@/components/ui/button/Button.vue";


export default defineComponent({
  name: "MapaComponent",

  data() {
    return {
      mostrar: 'dashboard',
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
      poligonosPorId: {},   // { [id]: google.maps.Polygon|Polyline|Circle }
      etiquetasPorId: {},   // { [id]: OverlayView }
      poligonosMarcadores: [],
      puntosEditables: [],

      selectedTipo: 'poligono',
      tipos: [
        { value: 'circulo', label: 'Círculo' },
        { value: 'poligono', label: 'Polígono' },
        { value: 'linea', label: 'Línea' }
      ],
      loadingActualizar: false,
      error: null,
      itemId: 402037903,
      colorHex: "#4153B9",
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

    
 irADashboard() {
  window.location.href = '/dashboard';
},

 irRutas() {
  window.location.href = '/rutas';
},

    async inicializar() {
      await this.cargarGoogleMaps();
      this.inicializarMapa();
      this.limpiarPuntos();
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
        this.cargarSegmentos(); // cargar segmentos *una* vez, después de que se carguen tiles
      });
    },


    colorHexToGoogleMaps(colorHex) {
      if (!colorHex) return { fillColor: "#FF0000", fillOpacity: 1 };

      if (colorHex.startsWith("rgba")) {
        const [r, g, b, a] = colorHex.match(/[\d.]+/g).map(Number);
        const fillColor = `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`;
        return { fillColor, fillOpacity: a };
      }

      if (colorHex.startsWith("#")) {
        if (colorHex.length === 9) { // #AARRGGBB
          return {
            fillColor: `#${colorHex.slice(3)}`,
            fillOpacity: parseInt(colorHex.slice(1, 3), 16) / 255
          };
        }
        if (colorHex.length === 7) return { fillColor: colorHex, fillOpacity: 1 };
      }

      return { fillColor: "#FF0000", fillOpacity: 1 };
    },




    async cargarSegmentos() {
      try {
        const { data } = await axios.get("http://localhost:8000/api/segmentos");
        const raw = Array.isArray(data.segmentos) ? data.segmentos : [];

        this.segmentos = raw.map(seg => {
          const coords = this.normalizarCoordenadas(seg);
          const tipo = this.detectarTipoSegmento({ coordenadas: coords });
          let colorHex = seg.color ?? seg.colorHex ?? "#FF0000";
          if (!/^#([0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/.test(colorHex)) colorHex = "#FF0000";

          return {
            id: seg.codsegmento ?? seg.id,
            nombre: seg.nombre || "Sin nombre",
            coordenadas: coords,
            tipo,
            colorHex,
            color: this.colorHexToGoogleMaps(colorHex),
            bounds: seg.bounds ?? {}
          };
        });

        // ✅ Dibuja todos los segmentos inmediatamente después de cargarlos
        this.dibujarTodosSegmentos();

      } catch (err) {
        console.error("Error al cargar segmentos:", err);
      }
    },


    async actualizarSegmentos() {
      this.loadingActualizar = true;
      this.error = null;

      try {
        const sid = await this.obtenerSID();
        const { data } = await axios.post('http://localhost:8000/api/zone-data', {
          itemId: this.itemId,
          sid
        });

        if (!data.success || !Array.isArray(data.zones))
          throw new Error('No se recibieron zonas válidas');

        const nuevosSegmentos = data.zones.map(z => {
          const coordenadas = this.normalizarCoordenadas(z);

          let colorHex = '#FFFFFF';
          if (z.color) {
            colorHex = z.color;
          } else if (z.c !== undefined && z.c !== null) {
            colorHex = '#' + Number(z.c).toString(16).padStart(8, '0');
          }
          if (!/^#([0-9A-F]{6}|[0-9A-F]{8})$/i.test(colorHex)) colorHex = '#FFFFFF';

          const colorMapa = this.hexToRgba(colorHex); // ✅ ahora sí existe

          return {
            id: z.id ?? z.codsegmento,
            nombre: z.n ?? z.nombre ?? 'Sin nombre',
            colorHex,
            color: colorMapa,
            coordenadas,
            bounds: z.b ?? z.bounds ?? {},
            tipo: this.detectarTipoSegmento(z)
          };
        });

        const segmentosModificados = [];

        for (const ns of nuevosSegmentos) {
          const existente = this.segmentos.find(s => s.id === ns.id);

          if (existente) {
            const haCambiado =
              existente.nombre !== ns.nombre ||
              existente.colorHex !== ns.colorHex ||
              JSON.stringify(existente.coordenadas) !== JSON.stringify(ns.coordenadas) ||
              JSON.stringify(existente.bounds) !== JSON.stringify(ns.bounds);

            if (haCambiado) {
              if (existente._figura) {
                existente._figura.setMap(null);
                existente._figura = null;
              }

              const lbl = this.etiquetas.find(e => e.segmentoId === existente.id);
              if (lbl) {
                lbl.setMap(null);
                this.etiquetas = this.etiquetas.filter(e => e.segmentoId !== existente.id);
              }

              Object.assign(existente, ns);

              this.limpiarFiguraPorId(existente.id)
              this.dibujarSegmento(existente);
              this.crearEtiqueta(existente);
              segmentosModificados.push(ns);
            }
          } else {
            this.segmentos.push(ns);
            this.dibujarSegmento(ns);
            this.crearEtiqueta(ns);
            segmentosModificados.push(ns);
          }
        }

        this.segmentos = this.segmentos.filter(s => {
          if (!nuevosSegmentos.some(ns => ns.id === s.id)) {
            if (s._figura) s._figura.setMap(null);
            const lbl = this.etiquetas.find(e => e.segmentoId === s.id);
            if (lbl) lbl.setMap(null);
            return false;
          }
          return true;
        });

        if (segmentosModificados.length > 0) {
          await axios.post('http://localhost:8000/api/segmentos/sincronizar', {
            segmentos: segmentosModificados.map(s => ({
              id: s.id,
              nombre: s.nombre,
              colorHex: s.colorHex,
              cordenadas: s.coordenadas,
              bounds: s.bounds
            }))
          });
        }

        this.mostrarNotificacion('Segmentos sincronizados correctamente', 'exito');
      } catch (err) {
        console.error('Error al actualizar segmentos:', err);
        this.error = err.message || JSON.stringify(err);
        this.mostrarNotificacion('Error al sincronizar segmentos', 'error');
      } finally {
        this.loadingActualizar = false;
        this.inicializar()
      }
    },



    mostrarMas() {
      this.verMas = !this.verMas;
    },


    // --- Abrir propiedades: dibuja solo al abrir el formulario ---
    abrirPropiedades(segmento) {
      if (!segmento) return;

      // Normaliza coordenadas
      segmento.coordenadas = this.normalizarCoordenadas(segmento);

      // Guarda el segmento editado
      this.segmentoEditado = { ...segmento };
      this.selectedTipo = segmento.tipo ?? "poligono";

      // Abre el panel de propiedades
      this.propiedadesAbiertas = true;

      // Centra mapa y dibuja puntos
      this.centrarMapa(this.segmentoEditado);
      this.dibujarPuntos(this.segmentoEditado);

      // Guarda referencia del polígono actual
      this.figuraActual = this.poligonos.find(p => p.id === segmento.id);

      // Guarda el color original por si se cancela
      this.colorOriginal = { ...segmento.color };

      // Dibuja vista previa
      this.vistaPreviaTipo(this.selectedTipo);
    },




    actualizarColorPoligono() {
      if (this.figuraActual && this.segmentoEditado?.color?.fillColor) {
        this.figuraActual.setOptions({
          fillColor: this.segmentoEditado.color.fillColor,
          strokeColor: this.segmentoEditado.color.fillColor,
        });
      }
    },


    cancelarEdicion() {
      if (this.figuraActual && this.colorOriginal) {
        this.figuraActual.setOptions({
          fillColor: this.colorOriginal.fillColor,
          strokeColor: this.colorOriginal.fillColor,
        });
      }
      this.segmentoEditado = null;
      this.figuraActual = null;
      this.colorOriginal = null;
      this.propiedadesAbiertas = false;
    },

    convertirColor(color) {
      if (!color) return "#1E90FF";
      if (color.startsWith("#")) {
        if (color.length === 9) return `#${color.slice(3)}`;
        if (color.length === 7) return color;
      }
      return "#1E90FF";
    },

    vistaPreviaTipo(tipo) {
      if (this.previewShape) this.previewShape.setMap(null);
      if (this.previewMarkers) this.previewMarkers.forEach(m => m.setMap(null));
      this.previewMarkers = [];

      const coords = this.segmentoEditado?.coordenadas ?? [];
      if (!coords.length) return;

      const colorHex = this.segmentoEditado.colorHex ?? "#FFFFFF";
      const { fillColor, fillOpacity } = this.colorHexToGoogleMaps(colorHex);

      const opcionesBase = {
        map: this.map,
        strokeColor: fillColor,
        strokeOpacity: fillOpacity,
        strokeWeight: tipo === "linea" ? 3 : 2,
        fillColor: tipo !== "linea" ? fillColor : undefined,
        fillOpacity: tipo !== "linea" ? fillOpacity : 0
      };

      if (tipo === "poligono") this.previewShape = new google.maps.Polygon({ ...opcionesBase, paths: coords.map(c => ({ lat: c.y, lng: c.x })) });
      else if (tipo === "linea") this.previewShape = new google.maps.Polyline({ ...opcionesBase, path: coords.map(c => ({ lat: c.y, lng: c.x })) });
      else if (tipo === "circulo") {
        const center = { lat: coords[0].y, lng: coords[0].x };
        let maxDist = 50;
        if (coords.length > 1) {
          maxDist = Math.max(...coords.map(p => {
            const dLat = (p.y - center.lat) * 111000;
            const dLng = (p.x - center.lng) * 111000 * Math.cos(center.lat * Math.PI / 180);
            return Math.sqrt(dLat ** 2 + dLng ** 2);
          }));
        }
        this.previewShape = new google.maps.Circle({ ...opcionesBase, center, radius: maxDist });
      }
    },


    onTipoChange(nuevoTipo) {
      this.selectedTipo = nuevoTipo;
      this.vistaPreviaTipo(this.selectedTipo);
    },

    async guardarPropiedades() {
      try {
        const s = this.segmentoEditado;
        const colorHex = s.colorHex?.startsWith("#") ? s.colorHex : "#000000";
        const segmento = {
          id: s.id ?? null,
          nombre: s.nombre ?? "",
          colorHex,
          color: colorHex,
          coordenadas: JSON.stringify(s.coordenadas ?? []),
          bounds: JSON.stringify(s.bounds ?? {}),
          velocidadMaxima: s.velocidadMaxima ?? 0,
          tipo: s.tipo ?? "poligono"
        };
        const res = await axios.post("http://localhost:8000/api/segmentos/guardar", { zonas: [segmento] });

        if (res.data.success) {
          const index = this.segmentos.findIndex(seg => seg.id === segmento.id);
          if (index !== -1) this.segmentos.splice(index, 1, { ...this.segmentos[index], ...segmento });
          else this.segmentos.push(segmento);

          this.mostrarNotificacion("Propiedades guardadas correctamente", "exito");
          this.cerrarPropiedades();
          this.dibujarTodosSegmentos();
        } else {
          this.mostrarNotificacion("Error al guardar propiedades", "error");
        }
      } catch (err) {
        console.error(err);
        this.mostrarNotificacion("Error al guardar propiedades", "error");
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
      if (window.google && window.google.maps) return;
      return new Promise((resolve, reject) => {
        const script = document.createElement("script");
        script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyAwIMAPTeuBV2TJghm-1VTnOVl4yi4Y3rE";
        script.async = true;
        script.defer = true;
        script.onload = () => resolve();
        script.onerror = () => reject(new Error("No se pudo cargar Google Maps"));
        document.head.appendChild(script);
      });
    },


    cerrarPropiedades() {
      this.propiedadesAbiertas = false;
      this.limpiarPuntos()
      this.cancelarEdicion()

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

      this.segmentoEditado = null;
      this.selectedTipo = null;
    },


    limpiarPuntos() {
      this.puntosEditables?.forEach(p => p.setMap(null));
      this.puntosEditables = [];
    },



    limpiarMapa() {
      try {
        if (this.segmentos && Array.isArray(this.segmentos)) {
          this.segmentos.forEach(seg => {
            if (seg._figura) {
              seg._figura.setMap(null);
              seg._figura = null;
            }
          });
        }

        if (this.etiquetas && Array.isArray(this.etiquetas)) {
          this.etiquetas.forEach(lbl => {
            if (lbl && lbl.setMap) lbl.setMap(null);
          });
          this.etiquetas = [];
        }
        if (this.poligonos && Array.isArray(this.poligonos)) {
          this.poligonos.forEach(p => {
            if (p && p.setMap) p.setMap(null);
          });
          this.poligonos = [];
        }

      } catch (error) {
        console.error("Error al limpiar el mapa:", error);
      }
    },

    parseColor(color) {
      if (color.startsWith("rgba")) {
        const valores = color
          .replace(/rgba|\(|\)|\s/g, "")
          .split(",")
          .map(Number);
        const [r, g, b, a] = valores;
        const fillColor =
          "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        return { fillColor, fillOpacity: a };
      }

      if (color.startsWith("#") && color.length === 9) {
        const a = parseInt(color.slice(1, 3), 16) / 255;
        const fillColor = "#" + color.slice(3);
        return { fillColor, fillOpacity: a };
      }

      if (color.startsWith("#") && color.length === 7) {
        return { fillColor: color, fillOpacity: 1 };
      }

      return { fillColor: "#000000", fillOpacity: 1 };
    },

    dibujarPuntos(segmento) {
      if (!segmento || !segmento.coordenadas?.length) return;
      const colorSeg = this.colorHexToGoogleMaps(segmento.colorHex);
      const poly = new google.maps.Polygon({
        paths: segmento.coordenadas.map(c => ({ lat: c.y, lng: c.x })),
        strokeColor: colorSeg.fillColor,
        strokeOpacity: colorSeg.fillOpacity,
        strokeWeight: 2,
        fillColor: colorSeg.fillColor,
        fillOpacity: colorSeg.fillOpacity,
        map: this.map
      });
    },



    normalizarCoordenadas(seg) {
      let raw = seg.coordenadas ?? seg.cordenadas ?? seg.p ?? [];
      if (typeof raw === "string") {
        try { raw = JSON.parse(raw); } catch { raw = []; }
      }
      if (!Array.isArray(raw)) return [];

      const coords = raw.map(p => {
        if (p?.x !== undefined && p?.y !== undefined) return { x: Number(p.x), y: Number(p.y) };
        if (Array.isArray(p) && p.length >= 2) return { x: Number(p[0]), y: Number(p[1]) };
        if (p?.lon !== undefined || p?.lat !== undefined) return { x: Number(p.lon ?? p.x), y: Number(p.lat ?? p.y) };
        return null;
      }).filter(Boolean);

      const seen = new Set();
      return coords.filter(c => {
        const key = `${c.x}-${c.y}`;
        if (!seen.has(key)) { seen.add(key); return true; }
        return false;
      });
    },




    limpiarFiguraPorId(id) {
      const figura = this.poligonosPorId[id];
      if (figura) { figura.setMap(null); delete this.poligonosPorId[id]; }
      const label = this.etiquetasPorId[id];
      if (label) { label.setMap(null); delete this.etiquetasPorId[id]; }
    },
    calcularCentroidePolygon(coords) {
      let sumX = 0, sumY = 0;
      coords.forEach(p => { sumX += p[0]; sumY += p[1]; });
      return [sumX / coords.length, sumY / coords.length];
    },

    crearEtiqueta(seg) {
      if (!seg.coordenadas.length) return;
      const centro = seg.coordenadas.reduce((acc, c) => ({ x: acc.x + c.x, y: acc.y + c.y }), { x: 0, y: 0 });
      centro.x /= seg.coordenadas.length;
      centro.y /= seg.coordenadas.length;

      const label = new google.maps.OverlayView();
      const div = document.createElement("div");
      div.innerText = seg.nombre || '';
      div.style.cssText = "background: rgba(255,255,255,0.85); padding:2px 6px; border-radius:4px; font-size:12px; position:absolute; white-space:nowrap;";
      label.onAdd = function () { this.getPanes().overlayLayer.appendChild(div); };
      label.draw = function () {
        const proj = this.getProjection();
        if (!proj) return;
        const pos = proj.fromLatLngToDivPixel(new google.maps.LatLng(centro.y, centro.x));
        div.style.left = pos.x + "px";
        div.style.top = pos.y + "px";
        div.style.transform = "translate(-50%, -50%)";
      };
      label.onRemove = function () { div.remove(); };
      label.setMap(this.map);
      this.etiquetasPorId[seg.id] = label;
    },


    dibujarSegmento(seg) {
      this.limpiarFiguraPorId(seg.id);
      if (!seg.coordenadas?.length) return;

      const coords = seg.coordenadas.map(c => ({ lat: parseFloat(c.y), lng: parseFloat(c.x) }));
      const { fillColor, fillOpacity } = this.colorHexToGoogleMaps(seg.colorHex);
      let figura;

      switch (seg.tipo) {
        case "poligono":
          figura = new google.maps.Polygon({
            paths: coords,
            strokeColor: fillColor,
            strokeOpacity: fillOpacity,
            strokeWeight: 2,
            fillColor,
            fillOpacity,
            editable: false,
            map: this.map
          });
          break;
        case "linea":
          figura = new google.maps.Polyline({
            path: coords,
            strokeColor: fillColor,
            strokeOpacity: fillOpacity,
            strokeWeight: 3,
            editable: false,
            map: this.map
          });
          break;
        case "circulo":
          const center = coords[0];
          let maxDist = 50;
          if (coords.length > 1) {
            maxDist = Math.max(...coords.map(p => {
              const dLat = (p.lat - center.lat) * 111000;
              const dLng = (p.lng - center.lng) * 111000 * Math.cos(center.lat * Math.PI / 180);
              return Math.sqrt(dLat ** 2 + dLng ** 2);
            }));
          }
          figura = new google.maps.Circle({
            center,
            radius: maxDist,
            strokeColor: fillColor,
            strokeOpacity: fillOpacity,
            fillColor,
            fillOpacity,
            editable: false,
            map: this.map
          });
          break;
      }

      if (figura) {
        seg._figura = figura;
        this.poligonosPorId[seg.id] = figura;
        this.crearEtiqueta(seg);
      }
    },

    dibujarTodosSegmentos() {
      Object.keys(this.poligonosPorId).forEach(id => this.limpiarFiguraPorId(id));
      this.etiquetasPorId = {};
      this.segmentos.forEach(seg => {
        seg.coordenadas = this.normalizarCoordenadas(seg);
        if (seg.coordenadas.length) this.dibujarSegmento(seg);
      });
    },


    obtenerColorParaMapa(color) {
      if (!color) return { fillColor: "#FF0000", fillOpacity: 1 };

      if (color.startsWith("rgba")) {
        const [r, g, b, a] = color.match(/\d+(\.\d+)?/g).map(Number);
        return { fillColor: `#${((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1)}`, fillOpacity: a };
      }

      if (color.startsWith("#")) {
        if (color.length === 9) return { fillColor: `#${color.slice(3)}`, fillOpacity: parseInt(color.slice(1, 3), 16) / 255 };
        if (color.length === 7) return { fillColor: color, fillOpacity: 1 };
      }

      return { fillColor: "#FF0000", fillOpacity: 1 };
    },



    hexToRgba(hex) {
      if (!hex) return { r: 255, g: 255, b: 255, a: 1 };
      hex = hex.replace("#", "");
      if (hex.length === 6) hex += "FF";
      const r = parseInt(hex.slice(0, 2), 16);
      const g = parseInt(hex.slice(2, 4), 16);
      const b = parseInt(hex.slice(4, 6), 16);
      const a = parseInt(hex.slice(6, 8), 16) / 255;
      return { r, g, b, a };
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
      const coords = segmento.coordenadas ?? [];
      if (!coords.length) return;

      const bounds = new google.maps.LatLngBounds();
      coords.forEach(c => {
        const lat = parseFloat(c.y ?? c.lat ?? 0);
        const lng = parseFloat(c.x ?? c.lng ?? 0);
        if (!isNaN(lat) && !isNaN(lng)) bounds.extend({ lat, lng });
      });
      if (!bounds.isEmpty()) this.map.fitBounds(bounds);
    },


    async obtenerSID() {
      const { data } = await axios.get("http://localhost:8000/api/obtener-sid");
      if (data.success && data.sid) return data.sid;
      throw new Error(data.error || "No se pudo obtener el SID");
    },

    async guardarPropiedades() {
      try {
        const s = this.segmentoEditado;
        const colorHex = s.colorHex?.startsWith("#") ? s.colorHex : "#000000";
        const segmento = {
          id: s.id ?? null,
          nombre: s.nombre ?? "",
          colorHex,
          color: colorHex,
          coordenadas: JSON.stringify(s.coordenadas ?? []),
          bounds: JSON.stringify(s.bounds ?? {}),
          velocidadMaxima: s.velocidadMaxima ?? 0,
          tipo: s.tipo ?? "poligono"
        };
        const res = await axios.post("http://localhost:8000/api/segmentos/guardar", { zonas: [segmento] });

        if (res.data.success) {
          const index = this.segmentos.findIndex(seg => seg.id === segmento.id);
          if (index !== -1) this.segmentos.splice(index, 1, { ...this.segmentos[index], ...segmento });
          else this.segmentos.push(segmento);

          this.mostrarNotificacion("Propiedades guardadas correctamente", "exito");
          this.cerrarPropiedades();
          this.dibujarTodosSegmentos();
        } else {
          this.mostrarNotificacion("Error al guardar propiedades", "error");
        }
      } catch (err) {
        console.error(err);
        this.mostrarNotificacion("Error al guardar propiedades", "error");
      }
    },



    actualizarFigura(segmento) {
      const figura = segmento._figura || this.poligonosPorId[segmento.id];
      if (!figura) return;

      const { fillColor, fillOpacity } = this.colorHexToGoogleMaps(segmento.colorHex);
      if (segmento.tipo === "poligono") {
        figura.setOptions({ strokeColor: fillColor, fillColor, fillOpacity, strokeOpacity: fillOpacity });
        figura.setPaths(segmento.coordenadas.map(c => ({ lat: c.y, lng: c.x })));
      } else if (segmento.tipo === "linea") {
        figura.setOptions({ strokeColor: fillColor, strokeOpacity: fillOpacity });
        figura.setPath(segmento.coordenadas.map(c => ({ lat: c.y, lng: c.x })));
      } else if (segmento.tipo === "circulo") {
        figura.setOptions({ strokeColor: fillColor, fillColor, fillOpacity, strokeOpacity: fillOpacity });
        figura.setCenter({ lat: segmento.coordenadas[0].y, lng: segmento.coordenadas[0].x });
      }
    },


    actualizarEtiqueta(segmento) {
      const label = this.etiquetas.find(e => e.segmentoId === segmento.id);
      if (!label) return;
      if (label.div) label.div.innerText = segmento.nombre || '';
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
      } finally {
        this.inicializarMapa();
      }
    },

    detectarTipoSegmento(seg) {
      const coords = seg.coordenadas ?? [];
      if (coords.length === 1) return "circulo";
      if (coords.length === 2) return "linea";
      if (coords.length > 2) return "poligono";
      return "desconocido";
    },


    async cerrarSesion() {
      try {
        await axios.post("/logout");
        // Redirige y limpia el historial
        this.$inertia.visit("/login", { replace: true });
        window.location.reload(); // 🔄 fuerza recarga total
      } catch (error) {
        console.error("Error al cerrar sesión:", error);
        this.toast.error("No se pudo cerrar sesión correctamente.");
      }
    }





  }
}



);
</script>