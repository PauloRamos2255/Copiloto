<template>
  <div id="mapa-page" class="flex flex-col h-screen w-full">
    <Header :nombreUsuario="nombreUsuario" class="sticky top-0 z-30" />

    <div class="flex flex-1 overflow-hidden relative">

      <!-- PANEL -->
      <transition name="slide">
        <aside v-if="panelAbierto" class="bg-white shadow-lg z-20 overflow-hidden flex flex-col border-r"
          :style="{ width: anchoPanel + 'px', minWidth: '300px', maxWidth: '600px' }">

          <div
            class="absolute right-0 top-0 h-full w-1 bg-gray-300 hover:bg-blue-500 cursor-col-resize transition-colors"
            @mousedown="iniciarRedimensionamiento" @touchstart="iniciarRedimensionamiento"></div>

          <!-- PROPIEDADES -->
          <div v-if="propiedadesAbiertas" class="h-full flex flex-col">
            <!-- Header del formulario -->
            <div class="bg-gray-50 border-b p-4">
              <div class="flex items-center justify-between mb-3">
                <button @click="cerrarPropiedades"
                  class="text-gray-600 hover:text-gray-800 flex items-center space-x-2">
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
              <form @submit.prevent="cerrarPropiedades" class="space-y-4">
                <!-- ID -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">ID</label>
                  <input :value="segmentoEditado.codsegmento || segmentoEditado.id" type="text" readonly
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-100 text-gray-600" />
                </div>

                <!-- Nombre -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Nombre <span
                      class="text-red-500">*</span></label>
                  <input v-model="segmentoEditado.nombre" type="text" required
                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <!-- Coordenadas -->
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Coordenadas ({{
                    segmentoEditado.coordenadas.length }} puntos)</label>
                  <div class="bg-gray-50 border border-gray-300 rounded p-3 max-h-32 overflow-y-auto">
                    <div v-if="segmentoEditado.coordenadas.length > 0" class="space-y-1">
                      <div v-for="(coord, idx) in coordenadasVisibles" :key="idx"
                        class="text-xs text-gray-600 font-mono">
                        {{ idx + 1 }}. {{ coord[0]?.toFixed(6) }}, {{ coord[1]?.toFixed(6) }}
                      </div>
                      <div v-if="segmentoEditado.coordenadas.length > 3"
                        class="text-xs text-blue-600 italic cursor-pointer mt-1 hover:underline"
                        @click="verMas = !verMas">
                        <span v-if="!verMas">... y {{ segmentoEditado.coordenadas.length - 3 }} más</span>
                        <span v-else>ver menos</span>
                      </div>
                    </div>
                    <div v-else class="text-xs text-gray-400 italic">Sin coordenadas</div>
                  </div>
                </div>

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
                <div class="grid grid-cols-2 gap-2">
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

          <!-- LISTADO -->
          <div v-else class="h-full flex flex-col">
            <div v-if="loading" class="flex items-center justify-center h-full w-full flex-col gap-4">
              <!-- Dots animadas -->
              <div class="loader-dots">
                <div></div>
                <div></div>
                <div></div>
              </div>
              <p class="text-gray-500 text-sm">Cargando segmentos...</p>
            </div>

            <div v-else class="h-full flex flex-col">

              <div class="sticky top-0 bg-white border-b p-4 flex flex-col gap-3">

                <div class="flex justify-between">
                  <h2 class="text-lg font-semibold">Segmentos</h2>

                  <button @click="actualizarSegmentos()" class="bg-blue-600 text-white px-3 py-1 rounded-lg">
                    <i class="fas fa-sync-alt" :class="{ 'animate-spin': loadingActualizar }"></i>
                    {{ loadingActualizar ? "Actualizando..." : "Actualizar" }}
                  </button>
                </div>

                <div class="flex items-center gap-2">
                  <i class="fas fa-search text-gray-400"></i>
                  <input v-model="busqueda" placeholder="Buscar..."
                    class="w-full border px-2 py-1 rounded focus:ring" />
                </div>
              </div>

              <div class="flex-1 overflow-y-auto p-4">

                <div v-for="seg in segmentosFiltrados" :key="seg.id" @click="centrarEnSegmento(seg)"
                  class="p-2 hover:bg-gray-100 rounded cursor-pointer flex justify-between items-center">

                  <div class="flex items-center gap-2">
                    <div class="w-4 h-4 rounded border"
                      :style="{ backgroundColor: seg.color.fillColor, opacity: seg.color.fillOpacity }"></div>
                    {{ seg.nombre }}
                  </div>

                  <div class="flex items-center gap-3">
                    <i class="fas fa-wrench text-blue-600" @click.stop="abrirPropiedades(seg)"></i>
                    <i class="fas fa-times text-red-500" @click.stop="eliminarGeocerca(seg)"></i>
                  </div>

                </div>

              </div>

            </div>



            <div class="flex-1 overflow-y-auto p-4">
              <div v-for="seg in segmentosFiltrados" :key="seg.id" @click="centrarEnSegmento(seg)"
                class="p-2 hover:bg-gray-100 rounded cursor-pointer flex justify-between items-center">

                <div class="flex items-center gap-2">
                  <div class="w-4 h-4 rounded border"
                    :style="{ backgroundColor: seg.color.fillColor, opacity: seg.color.fillOpacity }"></div>
                  {{ seg.nombre }}
                </div>

                <div class="flex items-center gap-3">
                  <i class="fas fa-wrench text-blue-600" @click.stop="abrirPropiedades(seg)"></i>
                  <i class="fas fa-times text-red-500" @click.stop="eliminarGeocerca(seg)"></i>
                </div>

              </div>
            </div>
          </div>

        </aside>
      </transition>

      <!-- BOTÓN ABRIR PANEL -->
      <button v-if="!panelAbierto" @click="togglePanel"
        class="fixed left-0 top-24 bg-blue-600 text-white p-3 rounded-r-lg shadow-lg z-50">
        <i class="fas fa-chevron-right text-lg"></i>
      </button>

      <!-- MAPA -->
      <LMap ref="lmap" class="flex-1" :zoom="zoom" :center="center" :options="{ zoomControl: false }">
        <LTileLayer :url="tileLayerUrl" :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" />
        <template v-if="segmentos.length">
          <LPolygon v-for="seg in segmentos.filter(s => s.tipo === 'poligono')" :key="'pol-' + seg.id"
            :lat-lngs="seg.coordenadas" :color="seg.color.color" :fillColor="seg.color.fillColor"
            :fillOpacity="seg.color.fillOpacity" @l-ready="registrarCapa($event, seg.id)" />

          <LPolyline v-for="seg in segmentos.filter(s => s.tipo === 'linea')" :key="'line-' + seg.id"
            :lat-lngs="seg.coordenadas" :color="seg.color.color" @l-ready="registrarCapa($event, seg.id)" />


          <LCircle v-for="seg in segmentos.filter(s => s.tipo === 'circulo')" :key="'circ-' + seg.id"
            :lat-lng="seg.coordenadas[0]" :radius="seg.radio" :color="seg.color.color" :fillColor="seg.color.fillColor"
            :fillOpacity="seg.color.fillOpacity" @l-ready="registrarCapa($event, seg.id)" />

          <LMarker v-for="seg in segmentos" :key="'mark-' + seg.id" :lat-lng="calcularCentro(seg.coordenadas)"
            :icon="crearCardIcon(seg.nombre)" />

        </template>
      </LMap>
    </div>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import { ref, onBeforeMount, onMounted } from 'vue'
import { LMap, LTileLayer, LPolygon, LPolyline, LCircle, LMarker } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import Header from "@/pages/Header.vue";
import Loader from "@/pages/Loader.vue";
import axios from "axios";
import L from "leaflet";
import Swal from "sweetalert2";


export default defineComponent({
  name: "MapaComponent",
  components: { LMap, LTileLayer, LPolygon, LPolyline, LCircle, LMarker, Header, Loader },

  props: {
    toast: {
      type: Object,
      default: null
    }
  },

  data() {
    return {
      nombreUsuario: "Paulo Ramos",
      panelAbierto: true,
      anchoPanel: 400,
      segmentos: [],
      segmentoEditado: null,
      propiedadesAbiertas: false,
      busqueda: "",
      loadingActualizar: false,
      loading: true,
      error: null,
      etiquetas: [],
      itemId: 402037903,
      zoom: 12,
      center: [-12.05985755, -77.062111],
      tileLayerUrl: "https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}",
      capasMapa: new Map(),
      verMas: false,
      API_BASE: import.meta.env.VITE_API_BASE || 'http://localhost:8000/api'
    };
  },

  computed: {
    segmentosFiltrados() {
      return this.segmentos.filter(s =>
        !this.busqueda || s.nombre.toLowerCase().includes(this.busqueda.toLowerCase())
      );
    },

    coordenadasVisibles() {
      if (!this.segmentoEditado?.coordenadas) return [];
      return this.verMas
        ? this.segmentoEditado.coordenadas
        : this.segmentoEditado.coordenadas.slice(0, 3);
    },

    bounds() {
      if (!this.segmentoEditado?.coordenadas?.length) return null;
      const coords = this.segmentoEditado.coordenadas;
      const lats = coords.map(c => c[0]);
      const lngs = coords.map(c => c[1]);

      return {
        min_y: Math.min(...lats),
        max_y: Math.max(...lats),
        min_x: Math.min(...lngs),
        max_x: Math.max(...lngs),
        cen_y: (Math.min(...lats) + Math.max(...lats)) / 2,
        cen_x: (Math.min(...lngs) + Math.max(...lngs)) / 2
      };
    }
  },

  methods: {


    async obtenerSID() {
      try {
        const { data } = await axios.get(`${this.API_BASE}/obtener-sid`);
        if (data.success && data.sid) return data.sid;
        throw new Error(data.error || "No se pudo obtener el SID");
      } catch (err) {
        console.error("Error al obtener SID:", err);
        throw err;
      }
    },

    togglePanel() {
      this.panelAbierto = !this.panelAbierto;
    },

    convertirColorConAlpha(hex, alpha = 1) {
      if (!hex) return `rgba(0,70,255,${alpha})`;
      if (typeof hex !== 'string') return hex;
      let clean = hex.replace("#", "");

      if (clean.length === 8) {
        const a = parseInt(clean.substring(0, 2), 16) / 255;
        const r = parseInt(clean.substring(2, 4), 16);
        const g = parseInt(clean.substring(4, 6), 16);
        const b = parseInt(clean.substring(6, 8), 16);
        return `rgba(${r}, ${g}, ${b}, ${alpha !== 1 ? alpha : a})`;
      }

      if (clean.length === 6) {
        const r = parseInt(clean.substring(0, 2), 16);
        const g = parseInt(clean.substring(2, 4), 16);
        const b = parseInt(clean.substring(4, 6), 16);
        return `rgba(${r},${g},${b},${alpha})`;
      }

      return `rgba(0,70,255,${alpha})`;
    },

    hexToRgba(hex) {
      if (!hex) return { color: "rgb(0,0,255)", fillColor: "rgb(0,0,255)", fillOpacity: 1 };
      hex = hex.replace("#", "");

      if (hex.length === 8) {
        const a = parseInt(hex.slice(0, 2), 16) / 255;
        const r = parseInt(hex.slice(2, 4), 16);
        const g = parseInt(hex.slice(4, 6), 16);
        const b = parseInt(hex.slice(6, 8), 16);

        return { color: `rgb(${r},${g},${b})`, fillColor: `rgb(${r},${g},${b})`, fillOpacity: a };
      }

      if (hex.length === 6) {
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return { color: `rgb(${r},${g},${b})`, fillColor: `rgb(${r},${g},${b})`, fillOpacity: 1 };
      }

      return { color: "rgb(0,0,255)", fillColor: "rgb(0,0,255)", fillOpacity: 1 };
    },

    calcularCentro(coords) {
      if (!coords.length) return [0, 0];
      const lat = coords.reduce((a, c) => a + c[0], 0) / coords.length;
      const lng = coords.reduce((a, c) => a + c[1], 0) / coords.length;
      return [lat, lng];
    },

    crearCardIcon(nombre) {
      return L.divIcon({
        className: "segment-card",
        html: `
          <div style="
            background: white;
            border: 1px solid #888;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
            white-space: normal;
            word-break: break-word;
            line-height: 1.2;
            max-width: 90px;">
            ${nombre}
          </div>
        `,
        iconSize: [100, 50],
        iconAnchor: [50, 25]
      });
    },

    normalizarCoordenadas(zona) {
      if (!zona) return [];

      if (Array.isArray(zona)) {
        if (zona.length > 0) {
          if (typeof zona[0] === 'object' && (zona[0].x !== undefined || zona[0].y !== undefined)) {
            return zona.map(p => [Number(p.y || p.lat), Number(p.x || p.lng)]);
          }
          if (Array.isArray(zona[0])) {
            return zona.map(p => [Number(p[0]), Number(p[1])]);
          }
        }
        return [];
      }

      if (zona.coordenadas && Array.isArray(zona.coordenadas)) {
        return zona.coordenadas.map(p => [Number(p.y || p[0]), Number(p.x || p[1])]);
      }

      return [];
    },

    detectarTipoSegmento(zona) {
      const coords = this.normalizarCoordenadas(zona.coordenadas || zona);
      if (coords.length === 1) return "circulo";
      if (coords.length === 2) return "linea";
      if (coords.length > 2) return "poligono";
      return "poligono";
    },

    limpiarFiguraPorId(id) {
      const layer = this.capasMapa.get(id);
      if (layer) {
        layer.setMap?.(null);
        this.capasMapa.delete(id);
      }
    },

    mostrarNotificacion(mensaje, tipo = "info") {
      if (!this.toast) return;
      if (tipo === "exito") this.toast.success(mensaje);
      else if (tipo === "error") this.toast.error(mensaje);
      else this.toast.info(mensaje);
    },

    async cargarSegmentos() {
      try {
        this.loadingActualizar = true;
        const { data } = await axios.get(`${this.API_BASE}/segmentos`);


        this.segmentos = (data.segmentos || []).map(seg => {
          const coords = (seg.cordenadas || []).map(p => [Number(p.y), Number(p.x)]);
          const tipo = coords.length === 1 ? 'circulo' :
            coords.length === 2 ? 'linea' : 'poligono';
          const colorObj = this.hexToRgba(seg.color);


          return {
            id: seg.codsegmento || seg.id,
            codsegmento: seg.codsegmento || seg.id,
            nombre: seg.nombre,
            coordenadas: coords,
            cordenadas_originales: seg.cordenadas,
            tipo,
            radio: seg.radio || 60,
            color: colorObj,
            colorHex: seg.color,
            bounds: seg.bounds,
            created_at: seg.created_at,
            updated_at: seg.updated_at
          };
        });


      } catch (e) {
        console.error("Error al cargar segmentos:", e);
        this.mostrarNotificacion("Error al cargar segmentos", "error");
      } finally {
        this.loadingActualizar = false;
      }
    },

    registrarCapa(ev, id) {
      const layer = ev.leafletObject || ev.target;
      if (layer) {
        this.capasMapa.set(id, layer);
      }
    },

    resaltar(id) {
      this.capasMapa.forEach(layer => {
        const el = layer?.getElement?.() || layer?._path || layer?._container;
        if (el) el.classList.remove("segment-highlight", "segment-highlight-pulse");
      });

      const layer = this.capasMapa.get(id);
      if (!layer) return;

      const element = layer.getElement?.() || layer._path || layer._container;
      if (!element) return;

      element.classList.add("segment-highlight", "segment-highlight-pulse");

      setTimeout(() => {
        element.classList.remove("segment-highlight-pulse");
      }, 3000);
    },

    centrarEnSegmento(seg) {
      this.resaltar(seg.id);
      const map = this.$refs.lmap?.leafletObject;
      if (!map) return;

      if (seg.tipo === "circulo") {
        map.flyTo(seg.coordenadas[0], 17, { duration: 1.2 });
        return;
      }

      if (seg.bounds) {
        const bounds = L.latLngBounds([
          [seg.bounds.min_y, seg.bounds.min_x],
          [seg.bounds.max_y, seg.bounds.max_x]
        ]);
        map.flyToBounds(bounds, {
          padding: [80, 80],
          duration: 1.2,
          maxZoom: 18
        });
      } else if (seg.coordenadas?.length > 0) {
        const bounds = L.latLngBounds(seg.coordenadas);
        map.flyToBounds(bounds, {
          padding: [80, 80],
          duration: 1.2,
          maxZoom: 18
        });
      }
    },

    abrirPropiedades(seg) {
      this.segmentoEditado = {
        ...seg,
        coordenadas: seg.coordenadas || [],
        color: seg.color?.fillColor || seg.color,
        codsegmento: seg.codsegmento || seg.id,
        guardando: false
      };
      this.propiedadesAbiertas = true;
    },

    cerrarPropiedades() {
      this.propiedadesAbiertas = false;
      this.segmentoEditado = null;
    },

    async guardarSegmento() {
      if (!this.segmentoEditado) return;

      try {
        this.segmentoEditado.guardando = true;
        const idSegmento = this.segmentoEditado.id || this.segmentoEditado.codsegmento;

        if (!idSegmento) {
          this.mostrarNotificacion("Error: No se pudo obtener el ID del segmento", "error");
          return;
        }

        const cordenadas = this.segmentoEditado.coordenadas.map(c => ({
          x: Number(c[1]),
          y: Number(c[0])
        }));

        const payload = {
          nombre: this.segmentoEditado.nombre,
          color: this.segmentoEditado.colorHex || this.segmentoEditado.color,
          cordenadas,
          bounds: this.segmentoEditado.bounds
        };

        const { data } = await axios.put(`${this.API_BASE}/segmentos/${idSegmento}`, payload);

        const idx = this.segmentos.findIndex(s => s.id === idSegmento);
        if (idx !== -1) {
          const segActualizado = this.segmentos[idx];
          segActualizado.nombre = this.segmentoEditado.nombre;
          segActualizado.colorHex = payload.color;
          segActualizado.color = this.hexToRgba(payload.color);
        }

        this.mostrarNotificacion("Segmento guardado correctamente", "exito");
        this.cerrarPropiedades();

      } catch (err) {
        console.error("Error al guardar segmento:", err);
        const mensaje = err.response?.data?.error || "Error al guardar el segmento";
        this.mostrarNotificacion(mensaje, "error");
      } finally {
        if (this.segmentoEditado) {
          this.segmentoEditado.guardando = false;
        }
      }
    },

    mapearSegmentosApi(zones) {
      return zones.map(z => {
        const colorHex = z.c != null
          ? '#' + (z.c >>> 0).toString(16).padStart(6, '0')
          : '#0046FF';

        const coordenadas = (z.p || []).map(p => [Number(p.y), Number(p.x)]);

        const tipo = coordenadas.length === 1 ? 'circulo' :
          coordenadas.length === 2 ? 'linea' : 'poligono';

        const colorObj = this.hexToRgba(colorHex);

        return {
          id: z.id,
          nombre: z.n,
          colorHex,
          coordenadas,
          cordenadas_originales: z.p || [],
          bounds: z.b || null,
          style: colorObj,
          color: colorObj,
          tipo,
          radio: z.r || 60
        };
      });
    },

    detectarCambiosSegmento(segAnterior, segNuevo) {
      const cambios = [];
      if (segAnterior.nombre !== segNuevo.nombre) {
        cambios.push({
          campo: 'nombre',
          anterior: segAnterior.nombre,
          actual: segNuevo.nombre
        });
      }

      if (segAnterior.colorHex !== segNuevo.colorHex) {
        cambios.push({
          campo: 'colorHex',
          anterior: segAnterior.colorHex,
          actual: segNuevo.colorHex
        });
      }
      if (JSON.stringify(segAnterior.cordenadas_originales) !== JSON.stringify(segNuevo.cordenadas)) {
        cambios.push({
          campo: 'cordenadas',
          anterior: JSON.stringify(segAnterior.cordenadas_originales, null, 2), // formato legible
          actual: JSON.stringify(segNuevo.cordenadas, null, 2)
        });
      }
      if (JSON.stringify(segAnterior.bounds) !== JSON.stringify(segNuevo.bounds)) {
        cambios.push({
          campo: 'bounds',
          anterior: segAnterior.bounds,
          actual: segNuevo.bounds
        });
      }

      return cambios;
    },



    async actualizarSegmentos() {
      this.loadingActualizar = true;
      this.error = null;

      try {
        // 1. Obtener SID
        const sid = await this.obtenerSID();

        // 2. Obtener zonas desde Wialon
        const { data: wialonData } = await axios.post(`${this.API_BASE}/zone-data`, {
          itemId: this.itemId,
          sid
        });

        if (!wialonData.success || !Array.isArray(wialonData.zones)) {
          throw new Error('No se recibieron zonas válidas de Wialon');
        }

        // 3. Detectar eliminados
        const idsWialon = new Set(wialonData.zones.map(z => z.id));
        const segmentosEliminados = this.segmentos.filter(s => !idsWialon.has(s.id));

        if (segmentosEliminados.length > 0) {
          await this.procesarSegmentosEliminados(segmentosEliminados);
        }

        // 4. Preparar nuevos datos
        const segmentosWialon = wialonData.zones.map(z => ({
          id: z.id,
          nombre: z.n,
          colorHex: z.c != null
            ? '#' + (z.c >>> 0).toString(16).padStart(6, '0')
            : '#0046FF',
          cordenadas: z.p || [],
          bounds: z.b || null
        }));

        // 5. Detectar cambios entre los existentes
        for (const nuevo of segmentosWialon) {
          const anterior = this.segmentos.find(s => s.id === nuevo.id);
          if (!anterior) continue;

          const cambios = this.detectarCambiosSegmento(anterior, nuevo);

          if (cambios.length > 0) {
            // Detectar cambio de nombre
            const cambioNombre = cambios.find(c => c.campo === 'nombre');

            let htmlCambios = `
          Cambios detectados en el segmento <b>"${anterior.nombre}"</b>.
          <br><br>
        `;

            if (cambioNombre) {
              htmlCambios += `
            <b>Nombre anterior:</b> ${cambioNombre.anterior}<br>
            <b>Nombre actual:</b> ${cambioNombre.actual}
          `;
            } else {
              // Solo color u otros campos permitidos
              htmlCambios += cambios.map(c => `
            <b>${c.campo}</b>:<br>
            Antes: ${c.anterior}<br>
            Ahora: ${c.actual}<br><br>
          `).join('');
            }

            await Swal.fire({
              title: 'Cambios detectados',
              html: htmlCambios,
              icon: 'info',
              width: 500,
              confirmButtonText: 'Aceptar'
            });
          }
        }

        // 6. Sincronizar con backend
        await axios.post(`${this.API_BASE}/segmentos/sincronizar`, {
          segmentos: segmentosWialon
        });

        // 7. Notificación
        this.mostrarNotificacion(
          `${segmentosWialon.length} segmento(s) sincronizado(s)` +
          (segmentosEliminados.length ? ` | ${segmentosEliminados.length} eliminado(s)` : ''),
          'exito'
        );

        // 8. Recargar
        await this.cargarSegmentos();

      } catch (err) {
        console.error('Error al actualizar segmentos:', err);
        this.error = err?.message || 'Error desconocido';
        this.mostrarNotificacion('Error al actualizar desde Wialon', 'error');

      } finally {
        this.loadingActualizar = false;
      }
    },


    async procesarSegmentosEliminados(segmentosEliminados) {
      for (const seg of segmentosEliminados) {
        if (!seg.id) continue;

        try {
          const resp = await axios.get(`${this.API_BASE}/segmentos/${seg.id}/detalles-rutas`);

          if (resp.data.existe) {
            // Si tiene rutas, preguntar al usuario antes de eliminar
            const rutas = [...new Set(resp.data.detalles.map(r => r.ruta_nombre))];
            const { isConfirmed } = await Swal.fire({
              title: `Eliminar segmento "${seg.nombre}"?`,
              html: `Este segmento está asignado a <b>${rutas.length}</b> ruta(s):<br>${rutas.join(', ')}.<br>¿Deseas eliminarlo de todas formas?`,
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Sí, eliminar',
              cancelButtonText: 'No, cancelar',
            });

            if (isConfirmed) {
              await this.eliminarSegmentoConDetalles(seg.id);
              this.mostrarNotificacion(`Segmento "${seg.nombre}" eliminado`, 'exito');
            } else {
              this.mostrarNotificacion(`Segmento "${seg.nombre}" no se eliminó`, 'info');
            }

          } else {
            // Si no tiene rutas, eliminar directamente
            await this.eliminarSegmentoConDetalles(seg.id);
            this.mostrarNotificacion(`Segmento "${seg.nombre}" eliminado`, 'exito');
          }

        } catch (err) {
          console.error(`Error procesando segmento ${seg.id}:`, err);
          this.mostrarNotificacion(`Error al procesar "${seg.nombre}"`, 'error');
        }
      }
    },


    async ejecutarPromesasEnLotes(promesas, tamanioLote) {
      for (let i = 0; i < promesas.length; i += tamanioLote) {
        const lote = promesas.slice(i, i + tamanioLote);
        await Promise.all(lote);
      }
    },

    async eliminarSegmentoConDetalles(codsegmento) {
      if (!codsegmento) return;
      try {
        await axios.post(`${this.API_BASE}/segmentos/${codsegmento}/cascada`);
        this.segmentos = this.segmentos.filter(s => s.id !== codsegmento);
      } catch (err) {
        console.error('Error al eliminar segmento:', err);
        this.mostrarNotificacion(`No se pudo eliminar el segmento ${codsegmento}`, 'error');
        throw err;
      }
    },

    async eliminarGeocerca(seg) {
      const idSegmento = seg.id || seg.codsegmento;
      if (!idSegmento) {
        Swal.fire({
          icon: "error",
          title: "Error",
          text: "No se pudo obtener el ID del segmento",
        });
        return;
      }

      const { isConfirmed } = await Swal.fire({
        title: `Eliminar segmento "${seg.nombre}"?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
      });

      if (!isConfirmed) return;

      try {
        Swal.fire({
          title: 'Procesando...',
          html: 'Por favor, espera',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        const verificarRutas = await axios.get(`${this.API_BASE}/segmentos/${idSegmento}/detalles-rutas`);

        if (verificarRutas.data.existe && verificarRutas.data.detalles.length > 0) {
          Swal.close();
          const rutasUnicas = [...new Set(verificarRutas.data.detalles.map(d => d.ruta_nombre))];
          Swal.fire({
            icon: "error",
            title: "No se puede eliminar",
            html: `
          Este segmento está asignado a <b>${rutasUnicas.length}</b> ruta(s):<br>
          ${rutasUnicas.join("<br>")}
        `,
          });
          return;
        }

        const { data } = await axios.delete(`${this.API_BASE}/segmentos/${idSegmento}`);
        this.segmentos = this.segmentos.filter(s => s.id !== idSegmento);

        Swal.close();
        Swal.fire({
          icon: "success",
          title: "Eliminado",
          text: data.mensaje || "Segmento eliminado correctamente",
        });

      } catch (e) {
        Swal.close();
        console.error("Error al eliminar:", e);
        const mensaje = e.response?.data?.error || e.response?.data?.mensaje || "Error al eliminar el segmento";

        Swal.fire({
          icon: "error",
          title: "Error",
          text: mensaje,
        });
      }
    },

    iniciarRedimensionamiento(e) {
      const startX = e.clientX || e.touches?.[0]?.clientX;
      if (!startX) return;

      const startWidth = this.anchoPanel;

      const move = (ev) => {
        const currentX = ev.clientX || ev.touches?.[0]?.clientX;
        if (currentX) {
          this.anchoPanel = Math.min(600, Math.max(300, startWidth + (currentX - startX)));
        }
      };

      const stop = () => {
        window.removeEventListener("mousemove", move);
        window.removeEventListener("mouseup", stop);
        window.removeEventListener("touchmove", move);
        window.removeEventListener("touchend", stop);
      };

      window.addEventListener("mousemove", move);
      window.addEventListener("mouseup", stop);
      window.addEventListener("touchmove", move);
      window.addEventListener("touchend", stop);
    },

    calcularAreaReal() {
      const b = this.bounds;
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
      const b = this.bounds;
      if (!b || b.min_x == null || b.max_x == null || b.min_y == null || b.max_y == null) {
        return '0 km';
      }

      const latDiff = Math.abs(b.max_y - b.min_y) * 111;
      const lngDiff = Math.abs(b.max_x - b.min_x) * 111 * Math.cos((b.cen_y || ((b.max_y + b.min_y) / 2)) * Math.PI / 180);
      const perimetroKm = 2 * (latDiff + lngDiff);

      return `${perimetroKm.toFixed(3)} km`;
    },

    formatearFecha(fecha) {
      if (!fecha) return 'N/A';
      return new Date(fecha).toLocaleString('es-PE');
    },

    convertirSegmentoParaGuardar(seg) {
      return {
        codsegmento: seg.id,
        nombre: seg.nombre,
        color: seg.colorHex,
        cordenadas: seg.cordenadas_originales || seg.coordenadas.map(c => ({
          x: Number(c[1]),
          y: Number(c[0])
        })),
        bounds: seg.bounds
      };
    }
  },

  async created() {
    this.loading = true;

    await this.cargarSegmentos();

    this.loading = false;
  },



});

</script>

<style>
.segment-card {
  pointer-events: none;
}

.segment-highlight {
  filter: drop-shadow(0 0 6px rgba(255, 255, 0, 0.9));
}

@keyframes highlightPulse {
  0% {
    transform: scale(1);
  }

  50% {
    transform: scale(1.05);
  }

  100% {
    transform: scale(1);
  }
}

.segment-highlight-pulse {
  animation: highlightPulse 1s infinite;
}

/* Ocultar zoom buttons (+ y -) */
:deep(.leaflet-control-zoom) {
  display: none !important;
}

/* Ocultar atribución (OpenStreetMap) */
:deep(.leaflet-control-attribution) {
  display: none !important;
}

/* Ocultar escala */
:deep(.leaflet-control-scale) {
  display: none !important;
}

.loader-dots {
  display: flex;
  gap: 8px;
}

.loader-dots div {
  width: 12px;
  height: 12px;
  background-color: #3b82f6;
  border-radius: 50%;
  animation: bounce 0.6s infinite alternate;
}

.loader-dots div:nth-child(2) { animation-delay: 0.2s; }
.loader-dots div:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
  to { transform: translateY(-12px); }
}
</style>