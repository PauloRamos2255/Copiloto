<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="cerrar">
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

      <div
        class="bg-white max-w-[1200px] w-full rounded-2xl shadow-2xl min-h-[85vh] md:min-h-[90vh] max-h-[95vh] flex flex-col overflow-hidden relative z-50">

        <!-- Header -->
        <div
          class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center justify-between flex-shrink-0">
          <h2 class="text-xl font-bold text-white flex items-center gap-2">
            <i class="fas fa-user"></i>
            {{ conductorData ? 'Editar Rutas del Conductor' : 'Asignar Rutas al Conductor' }}
          </h2>
          <button @click="cerrar" class="text-white hover:bg-blue-500 p-2 rounded-lg transition-colors">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>

        <!-- Content -->
        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">

          <!-- Left Panel: Selection -->
          <div class="lg:w-1/2 flex flex-col p-6 overflow-hidden">

            <!-- Conductor Select -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-1">
                <i class="fas fa-user text-blue-600 mr-1"></i> Conductor
              </label>

              <div class="flex gap-2">
                <select v-if="!conductorData" v-model="conductorId"
                  class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none h-10">
                  <option value="" disabled>Seleccione un conductor</option>
                  <option v-for="cond in conductores" :key="cond.codusuario" :value="cond.codusuario">
                    {{ cond.nombre }}
                  </option>
                </select>

                <select v-else v-model="conductorId" disabled
                  class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none h-10 disabled:bg-gray-100 disabled:cursor-not-allowed">
                  <option :value="conductorId">{{ nombreConductor }}</option>
                </select>


              </div>
            </div>


            <!-- Routes Container -->
            <div class="flex flex-1 gap-3 overflow-hidden pt-3">
              <!-- Available Routes -->
              <div
                class="w-1/2 bg-green-50 rounded-xl border border-green-200 flex flex-col p-3 shadow-sm overflow-hidden relative">
                <h3 class="font-semibold mb-2 flex items-center text-sm text-green-700 flex-shrink-0">
                  <i class="fas fa-list mr-2 text-green-600"></i> Disponibles ({{ rutasDisponiblesFiltradas.length }})
                </h3>

                <input type="text" v-model="filtroRutasDisponibles" placeholder="Buscar..."
                  class="w-full px-2 py-1 border border-green-300 rounded-lg mb-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none flex-shrink-0" />

                <!-- Loading Spinner -->
                <div v-if="loadingRutas"
                  class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                  <div class="flex flex-col items-center">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                      viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span class="text-xs text-gray-600">Cargando rutas...</span>
                  </div>
                </div>

                <!-- Routes List -->
                <div
                  class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-green-300 scrollbar-track-transparent"
                  :class="dragOverDisponibles ? 'border-green-500 bg-green-50' : 'border-green-300'"
                  @dragover.prevent="dragOverDisponibles = true" @dragleave.prevent="dragOverDisponibles = false"
                  @drop.prevent="dropRutaEnDisponibles">
                  <div v-for="ruta in rutasDisponiblesFiltradas" :key="ruta.id" draggable="true"
                    @dragstart="iniciarDragRuta(ruta, 'disponibles')" @dragend="finalizarDrag"
                    @dblclick="asignarRuta(ruta)"
                    class="p-1 mb-1 bg-green-100 border border-green-300 rounded cursor-pointer hover:bg-green-200 hover:shadow transition-all text-xs flex items-center justify-between group">
                    <div class="flex items-center">
                      <i class="fas fa-grip-vertical text-green-600 mr-1 text-xs"></i>
                      <span>{{ ruta.nombre }}</span>
                    </div>
                    <i
                      class="fas fa-arrow-right text-green-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                  </div>
                  <div v-if="!loadingRutas && rutasDisponiblesFiltradas.length === 0"
                    class="text-xs text-gray-400 text-center py-4">
                    No hay rutas disponibles
                  </div>
                </div>
              </div>

              <!-- Assigned Routes -->
              <div
                class="w-1/2 bg-blue-50 rounded-xl border border-blue-200 flex flex-col p-3 shadow-sm overflow-hidden relative">
                <h3 class="font-semibold mb-2 flex items-center text-sm text-blue-700 flex-shrink-0">
                  <i class="fas fa-route mr-2 text-blue-600"></i> Asignadas ({{ rutasAsignadas.length }})
                </h3>

                <!-- Loading Spinner -->
                <div v-if="loadingAsignadas"
                  class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 rounded-xl">
                  <div class="flex flex-col items-center">
                    <svg class="animate-spin h-8 w-8 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                      viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                    </svg>
                    <span class="text-xs text-gray-600">Cargando asignadas...</span>
                  </div>
                </div>

                <!-- Assigned Routes List -->
                <div
                  class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-transparent"
                  :class="dragOverRuta ? 'border-blue-500 bg-blue-50' : 'border-blue-300'"
                  @dragover.prevent="dragOverRuta = true" @dragleave.prevent="dragOverRuta = false"
                  @drop.prevent="dropRutaEnRuta">
                  <div v-for="(ruta, idx) in rutasAsignadas" :key="ruta.id" draggable="true"
                    @dragstart="iniciarDragRuta(ruta, 'ruta')" @dragend="finalizarDrag" @click="seleccionarRuta(ruta)"
                    class="p-1 mb-1 rounded cursor-move transition-all border-l-4 text-xs flex items-center justify-between group"
                    :class="isSelectedRuta(ruta) ? 'bg-blue-200 border-blue-600 shadow-md' : 'bg-blue-100 border-blue-400 hover:shadow-sm'">
                    <div class="flex items-center">
                      <i class="fas fa-grip-vertical text-blue-600 mr-1 text-xs"></i>
                      <span class="font-semibold mr-1">{{ idx + 1 }}.</span>
                      <span>{{ ruta.nombre }}</span>
                    </div>
                    <button @click.stop="quitarRuta(ruta)"
                      class="text-red-500 hover:text-red-700 text-xs p-1 opacity-0 group-hover:opacity-100 transition-opacity">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <div v-if="!loadingAsignadas && rutasAsignadas.length === 0"
                    class="text-xs text-gray-400 text-center py-4">
                    Ninguna ruta asignada
                  </div>
                </div>

                <!-- Clear Button -->
                <div class="mt-2 flex gap-2">
                  <button @click="limpiarRutas" :disabled="rutasAsignadas.length === 0"
                    class="flex-1 py-2 rounded bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-semibold transition-all">
                    <i class="fas fa-broom mr-1"></i>Limpiar Todo
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Panel: Map -->
          <div class="lg:w-1/2 p-3 flex flex-col bg-gray-50 border-l border-gray-200 hidden lg:flex">
            <div class="flex flex-col items-center mb-3">
              <h3 class="font-semibold text-center text-base flex items-center justify-center text-gray-700 mb-1">
                <i class="fas fa-map-marked-alt mr-2 text-blue-600 text-lg"></i> Vista de las Rutas
              </h3>
              <div class="w-2/3 border-t-2 border-blue-400"></div>
            </div>

            <div
              style="height: calc(100% - 50px); width: 100%; border-radius: 12px; overflow: hidden; border: 2px solid #e5e7eb;">
              <LMap v-if="visible" ref="mapa" :zoom="zoom" :center="center" style="height: 100%; width: 100%;">
                <LTileLayer :url="'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'"
                  :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" :attribution="'© Google Maps'" :max-zoom="20" />
                <template v-for="ruta in rutasAsignadas" :key="ruta.id">
                  <template v-if="rutaSeleccionada && ruta.id === rutaSeleccionada.id">
                    <template v-for="seg in ruta.segmentos" :key="seg.id">
                      <LPolygon v-if="seg.cordenadas?.length" :lat-lngs="seg.cordenadas"
                        :color="convertirColorConAlpha(seg.color || '#ff0000', 1)"
                        :fill-color="convertirColorConAlpha(seg.color || '#ff0000', 0.4)" :fill-opacity="0.4"
                        :weight="3" />
                      <LMarker v-if="seg.cordenadas?.length" :lat-lng="calcularCentroPolygono(seg.cordenadas)"
                        :icon="crearCardIcon(seg.nombre)" />
                    </template>
                  </template>
                </template>
              </LMap>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="border-t bg-gray-50 px-6 py-4 flex flex-col md:flex-row justify-end gap-3">
          <button @click="cerrar" type="button"
            class="px-6 py-2 border rounded-lg hover:bg-gray-100 transition-all font-medium w-full md:w-auto text-sm">
            <i class="fas fa-times mr-1"></i>Cancelar
          </button>
          <button  v-if="!conductorData" @click="guardarAsignacion" type="button"
            :disabled="!conductorId || rutasAsignadas.length === 0 || procesando"
            class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2 text-sm">
            <svg v-if="procesando" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
              viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <i class="fas fa-save" :class="{ 'mr-1': !procesando }"></i> Guardar Asignación
          </button>


          <button v-else @click="guardarAsignacion" type="button"
            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2 text-sm">

            <svg v-if="procesando" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
              viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>

            <i class="fas fa-edit" :class="{ 'mr-1': !procesando }"></i> Editar Asignación
          </button>

        </div>

      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from "vue";
import { LMap, LTileLayer, LMarker, LPolygon } from "@vue-leaflet/vue-leaflet";
import "leaflet/dist/leaflet.css";
import axios from "axios";
import L from "leaflet";
import Swal from "sweetalert2";
import { useToast } from "vue-toastification";

const props = defineProps({
  visible: { type: Boolean, required: true },
  conductorData: { type: Object, default: null },
  rutas: { type: Object, default: null },
  conductores: { type: Array, default: () => [] }
});

const emit = defineEmits(["close", "saved"]);
const toast = useToast();

// State
const conductores = ref([]);
const rutas = ref([]);
const conductorId = ref("");
const rutasAsignadas = ref([]);
const rutaSeleccionada = ref(null);
const filtroRutasDisponibles = ref("");
const nombreConductor = ref("");

const dragOverDisponibles = ref(false);
const dragOverRuta = ref(false);
const dragSource = ref(null);

const mapa = ref(null);
const zoom = ref(13);
const center = ref([-12.0464, -77.0428]);

const loadingRutas = ref(true);
const loadingAsignadas = ref(false);
const procesando = ref(false);

// Computed properties
const rutasDisponiblesFiltradas = computed(() =>
  rutas.value.filter(
    r =>
      !rutasAsignadas.value.some(a => a.id === r.id) &&
      r.nombre?.toLowerCase().includes(filtroRutasDisponibles.value.toLowerCase())
  )
);



// Color utilities
const convertirColorConAlpha = (hex, alpha = 0.33) => {
  if (!hex) return `rgba(0,0,0,${alpha})`;
  let clean = hex.replace("#", "");
  if (clean.length === 8) clean = clean.substring(2);
  if (clean.length === 3) clean = clean.split("").map(c => c + c).join("");
  const bigint = parseInt(clean, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `rgba(${r},${g},${b},${alpha})`;
};

const calcularCentroPolygono = (coords) => {
  if (!coords || coords.length === 0) return [0, 0];
  const sum = coords.reduce(
    (acc, c) => [acc[0] + c[0], acc[1] + c[1]],
    [0, 0]
  );
  return [sum[0] / coords.length, sum[1] / coords.length];
};

const crearCardIcon = (nombre) =>
  L.divIcon({
    className: "segment-card",
    html: `
      <div style="
        background:white;
        border:1px solid #888;
        padding:2px 6px;
        border-radius:4px;
        font-size:12px;
        font-weight:bold;
        white-space:nowrap;
      ">
        ${nombre}
      </div>
    `,
    iconSize: [80, 30],
    iconAnchor: [40, 15]
  });

// Data processing
const procesarRutas = (data) => {
  if (!data) return [];
  const payload = data.rutas ? data.rutas : (Array.isArray(data) ? data : []);
  return payload.map(r => ({
    id: r.id,
    nombre: r.nombre,
    segmentos: (r.segmentos || []).map(seg => {
      let coordsParsed = [];
      try {
        const coords = typeof seg.cordenadas === "string" ? JSON.parse(seg.cordenadas) : seg.cordenadas;
        coordsParsed = (coords || []).map(c => [c.y, c.x]);
      } catch (e) {
        console.error(`Error parseando coordenadas segmento ${seg.nombre}:`, e);
      }
      return {
        id: seg.id,
        nombre: seg.nombre,
        color: seg.color,
        cordenadas: coordsParsed,
        bounds: seg.bounds ? JSON.parse(seg.bounds) : null
      };
    })
  }));
};

// API calls
const cargarDatos = async () => {
  loadingRutas.value = true;
  try {
    // SIEMPRE cargar los conductores sin rutas desde la API
    conductores.value = (await axios.get("/api/conductor_sin_ruta")).data;

    const rutasResp = props.rutas
      ? props.rutas
      : (await axios.get("/api/asignacion_segmen")).data;

    rutas.value = procesarRutas(rutasResp);
  } catch (e) {
    console.error("Error cargando datos:", e);
    toast.error("Error al cargar los datos");
  } finally {
    loadingRutas.value = false;
  }
};


const cargarRutasConductor = async (id) => {
  if (!id) return;
  loadingAsignadas.value = true;

  try {
    const resp = await axios.get(`/api/asignacion/${id}`);


    nombreConductor.value = resp.data.conductor;
    console.log(nombreConductor.value);

    const rutasResp = resp.data.rutas || [];

    rutasAsignadas.value = rutasResp.map(r => ({
      id: r.id,
      nombre: r.nombre,
      conductorId: id,
      conductorNombre: nombreConductor,
      segmentos: (r.segmentos || []).map(seg => {
        let coordsParsed = [];
        try {
          const coords = typeof seg.cordenadas === "string" ? JSON.parse(seg.cordenadas) : seg.cordenadas;
          coordsParsed = (coords || []).map(c => [c.y, c.x]);
        } catch (e) {
          console.error(`Error seg coords:`, e);
        }
        return {
          id: seg.id,
          nombre: seg.nombre,
          color: seg.color,
          cordenadas: coordsParsed,
          bounds: seg.bounds ? JSON.parse(seg.bounds) : null
        };
      })
    }));

    await nextTick();
    actualizarMapa();

  } catch (e) {
    console.error("Error rutas conductor:", e);
    toast.error("Error al cargar rutas del conductor");
    rutasAsignadas.value = [];
    rutaSeleccionada.value = null;
  } finally {
    loadingAsignadas.value = false;
  }
};


const actualizarMapa = () => {
  try {
    if (mapa.value && mapa.value.leafletObject) {
      mapa.value.leafletObject.invalidateSize();
      if (rutasAsignadas.value.length > 0) {
        rutaSeleccionada.value = rutasAsignadas.value[0];
        const seg = rutaSeleccionada.value.segmentos?.[0];
        if (seg?.cordenadas?.length) {
          const centro = calcularCentroPolygono(seg.cordenadas);
          mapa.value.leafletObject.setView(centro, zoom.value);
        }
      } else {
        rutaSeleccionada.value = null;
      }
    }
  } catch (err) {
    console.warn("No se pudo ajustar mapa:", err);
  }
};

// Drag and Drop
const iniciarDragRuta = (ruta, origen) => {
  dragSource.value = { ruta, origen };
};

const finalizarDrag = () => {
  dragSource.value = null;
  dragOverDisponibles.value = false;
  dragOverRuta.value = false;
};

const dropRutaEnDisponibles = () => {
  if (dragSource.value?.origen === "ruta") {
    quitarRuta(dragSource.value.ruta);
  }
  finalizarDrag();
};

const dropRutaEnRuta = () => {
  if (dragSource.value?.origen === "disponibles") {
    asignarRuta(dragSource.value.ruta);
  }
  finalizarDrag();
};

// Route operations
const asignarRuta = (ruta) => {
  if (!rutasAsignadas.value.some(r => r.id === ruta.id)) {
    rutasAsignadas.value.push({ ...ruta });
    rutaSeleccionada.value = ruta;
  }
};

const quitarRuta = (ruta) => {
  rutasAsignadas.value = rutasAsignadas.value.filter(r => r.id !== ruta.id);
  if (rutaSeleccionada.value?.id === ruta.id) {
    rutaSeleccionada.value = rutasAsignadas.value[0] || null;
  }
};

const seleccionarRuta = (ruta) => {
  rutaSeleccionada.value = ruta;
};

const limpiarRutas = () => {
  rutasAsignadas.value = [];
  rutaSeleccionada.value = null;
};

const isSelectedRuta = (ruta) => {
  if (!ruta) return false;
  return rutaSeleccionada.value?.id === ruta.id;
};

// Save operation
const guardarAsignacion = async () => {
  if (procesando.value) return;

  const usuarioId = conductorId.value;
  const rutasIds = rutasAsignadas.value.map(r => r.id);

  if (!usuarioId) {
    toast.error("Seleccione un conductor");
    return;
  }

  procesando.value = true;

  Swal.fire({
    title: rutasIds.length > 0 ? "Guardando..." : "Eliminando asignaciones...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  try {
    if (rutasIds.length > 0) {
      // Si hay rutas, hacemos POST o PUT según corresponda
      const totalRutas = props.conductorData?.total_rutas || 0;
      if (totalRutas > 0) {
        await axios.put("/api/asignacion_update", {
          usuario: usuarioId,
          rutas: rutasIds
        });
      } else {
        await axios.post("/api/asignacion_save", {
          usuario: usuarioId,
          rutas: rutasIds
        });
      }

      Swal.close();
      await Swal.fire({
        icon: "success",
        title: totalRutas > 0 ? "Actualizado" : "Asignado",
        text: totalRutas > 0 ? "Rutas actualizadas correctamente" : "Rutas asignadas correctamente",
        timer: 1400,
        showConfirmButton: false
      });

    } else {
      // Si no hay rutas, eliminamos todas las asignaciones del usuario
      await axios.delete(`/api/asignacion_delete/${usuarioId}`);

      Swal.close();
      await Swal.fire({
        icon: "success",
        title: "Asignaciones eliminadas",
        text: "Todas las rutas del usuario fueron eliminadas",
        timer: 1400,
        showConfirmButton: false
      });
    }

    emit("saved");
    cerrar();

  } catch (error) {
    Swal.close();
    console.error("Error en la asignación:", error);
    await Swal.fire({
      icon: "error",
      title: "Error",
      text: error.response?.data?.message || "Ocurrió un error al procesar la asignación"
    });
  } finally {
    procesando.value = false;
  }
};




// Close modal
const cerrar = async () => {
  procesando.value = false;

  // Limpiar el mapa antes de cerrar el modal
  try {
    if (mapa.value?.leafletObject) {
      const m = mapa.value.leafletObject;

      // Desactivar todas las interacciones
      try { m.dragging?.disable(); } catch { }
      try { m.touchZoom?.disable(); } catch { }
      try { m.doubleClickZoom?.disable(); } catch { }
      try { m.scrollWheelZoom?.disable(); } catch { }
      try { m.scrollWheelZoom?.disable(); } catch { }

      // Remover todos los eventos
      try { m.off(); } catch { }

      // Limpiar layers
      try { m.eachLayer(layer => m.removeLayer(layer)); } catch { }
    }
  } catch (e) {
    console.warn("Error limpiando mapa:", e);
  }

  // Resetear estado
  conductorId.value = "";
  rutasAsignadas.value = [];
  rutaSeleccionada.value = null;
  filtroRutasDisponibles.value = "";
  dragOverDisponibles.value = false;
  dragOverRuta.value = false;
  nombreConductor.value = ""

  // NO establecer mapa.value a null aquí, dejar que vue-leaflet lo maneje
  // mapa.value = null;

  emit("close");
};

// Watchers
watch(
  () => props.visible,
  async (value) => {
    if (value) {
      await cargarDatos();
      if (props.conductorData) {
        const id = typeof props.conductorData === "object" ? props.conductorData.codusuario : props.conductorData;
        conductorId.value = id;
        await cargarRutasConductor(id);
      } else {
        conductorId.value = "";
        rutasAsignadas.value = [];
        rutaSeleccionada.value = null;
      }
      await nextTick();
      actualizarMapa();
    }
    // No hacer nada cuando se cierra, vue-leaflet manejará el cleanup
  }
);

watch(
  () => conductorId.value,
  async (newId) => {
    if (newId && !props.conductorData) {
      await cargarRutasConductor(newId);
    }
  }
);

onMounted(() => {
  cargarDatos();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

:deep(.leaflet-control) {
  display: none !important;
}

:deep(.scrollbar-thin) {
  scrollbar-width: thin;
}

:deep(.scrollbar-thumb-green-300) {
  scrollbar-color: #86efac transparent;
}

:deep(.scrollbar-thumb-blue-300) {
  scrollbar-color: #93c5fd transparent;
}

:deep(.scrollbar-track-transparent) {
  scrollbar-color: auto transparent;
}
</style>