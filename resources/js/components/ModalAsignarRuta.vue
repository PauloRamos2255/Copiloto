<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="cerrar">

      <!-- Fondo -->
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

      <!-- Contenedor principal -->
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

        <!-- Contenido -->
        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">
          <!-- Panel izquierdo -->
          <div class="lg:w-1/2 flex flex-col p-6 overflow-hidden">
            <!-- Seleccionar conductor -->
            <div class="mb-4">
              <label class="block text-sm font-semibold text-gray-700 mb-1">
                <i class="fas fa-user text-blue-600 mr-1"></i> Conductor
              </label>
              <select v-model="conductorId" :disabled="!!conductorData"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none h-10 disabled:bg-gray-100 disabled:cursor-not-allowed">
                <option value="" disabled>Seleccione un conductor</option>
                <option v-for="cond in conductoresConEstado" :key="cond.codusuario" :value="cond.codusuario"
                  :disabled="cond.tieneRutas">
                  {{ cond.nombre }} <span v-if="cond.tieneRutas">(Ya tiene rutas)</span>
                </option>
              </select>
            </div>

            <div class="flex flex-1 gap-3 overflow-hidden pt-3">
              <!-- Rutas disponibles -->
              <div
                class="w-1/2 bg-green-50 rounded-xl border border-green-200 flex flex-col p-3 shadow-sm overflow-hidden">
                <h3 class="font-semibold mb-2 flex items-center text-sm text-green-700 flex-shrink-0">
                  <i class="fas fa-list mr-2 text-green-600"></i> Disponibles ({{ rutasDisponiblesFiltradas.length }})
                </h3>
                <input type="text" v-model="filtroRutasDisponibles" placeholder="Buscar..."
                  class="w-full px-2 py-1 border border-green-300 rounded-lg mb-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none flex-shrink-0" />
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
                  <div v-if="rutasDisponiblesFiltradas.length === 0" class="text-xs text-gray-400 text-center py-4">
                    No hay rutas disponibles
                  </div>
                </div>
              </div>

              <!-- Rutas asignadas -->
              <div
                class="w-1/2 bg-blue-50 rounded-xl border border-blue-200 flex flex-col p-3 shadow-sm overflow-hidden">
                <h3 class="font-semibold mb-2 flex items-center text-sm text-blue-700 flex-shrink-0">
                  <i class="fas fa-route mr-2 text-blue-600"></i> Asignadas ({{ rutasAsignadas.length }})
                </h3>
                <div
                  class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-transparent"
                  :class="dragOverRuta ? 'border-blue-500 bg-blue-50' : 'border-blue-300'"
                  @dragover.prevent="dragOverRuta = true" @dragleave.prevent="dragOverRuta = false"
                  @drop.prevent="dropRutaEnRuta">
                  <div v-for="(ruta, idx) in rutasAsignadas" :key="ruta.id" draggable="true"
                    @dragstart="iniciarDragRuta(ruta, 'ruta')" @dragend="finalizarDrag" @click="seleccionarRuta(ruta)"
                    class="p-1 mb-1 rounded cursor-move transition-all border-l-4 text-xs flex items-center justify-between group"
                    :class="isSelectedRuta(ruta) ? 'bg-blue-200 border-blue-600 shadow-md' : 'bg-blue-100 border-blue-400 hover:bg-blue-150 hover:shadow-sm'">
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
                  <div v-if="rutasAsignadas.length === 0" class="text-xs text-gray-400 text-center py-4">
                    Ninguna ruta asignada
                  </div>
                </div>

                <div class="mt-2 flex gap-2">
                  <button @click="limpiarRutas" :disabled="rutasAsignadas.length === 0"
                    class="flex-1 py-2 rounded bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-xs font-semibold transition-all">
                    <i class="fas fa-broom mr-1"></i>Limpiar Todo
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Panel derecho: Mapa -->
          <div class="lg:w-1/2 p-3 flex flex-col bg-gray-50 border-l border-gray-200 hidden lg:flex">
            <div class="flex flex-col items-center mb-3">
              <h3 class="font-semibold text-center text-base flex items-center justify-center text-gray-700 mb-1">
                <i class="fas fa-map-marked-alt mr-2 text-blue-600 text-lg"></i> Vista de las Rutas
              </h3>
              <div class="w-2/3 border-t-2 border-blue-400"></div>
            </div>
            <div
              style="height: calc(100% - 50px); width: 100%; border-radius: 12px; overflow: hidden; border: 2px solid #e5e7eb;">
              <LMap :zoom="zoom" :center="center" style="height: 100%; width: 100%;">
                <LTileLayer :url="'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'"
                  :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" :attribution="'© Google'" />

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
          <button @click="guardarAsignacion" type="button" :disabled="!conductorId || rutasAsignadas.length === 0"
            class="px-6 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2 text-sm">
            <i class="fas fa-save mr-1"></i> Guardar Asignación
          </button>
        </div>

      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { LMap, LTileLayer, LMarker, LPolygon } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import axios from "axios";
import L from "leaflet";
import { useToast } from "vue-toastification";

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  conductorData: {
    type: Object,
    default: null
  },
  rutas: {
    type: Object,
    default: null
  },
  conductores: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(["close", "saved"]);
const toast = useToast();

const conductores = ref([]);
const rutas = ref([]);
const conductorId = ref("");
const rutasAsignadas = ref([]);
const rutaSeleccionada = ref(null);
const filtroRutasDisponibles = ref("");
const dragOverDisponibles = ref(false);
const dragOverRuta = ref(false);
const dragSource = ref(null);
const zoom = ref(13);
const center = ref([-12.0464, -77.0428]);

const rutasDisponiblesFiltradas = computed(() =>
  rutas.value.filter(
    r => !rutasAsignadas.value.some(a => a.id === r.id) &&
      r.nombre.toLowerCase().includes(filtroRutasDisponibles.value.toLowerCase())
  )
);

const conductoresConEstado = computed(() =>
  conductores.value.map(c => ({
    ...c,
    tieneRutas: rutasAsignadas.value.some(r => r.conductorId === c.codusuario)
  }))
);

const hexToRgb = (hex) => {
  if (!hex) return "rgb(100, 150, 200)";
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  if (result) {
    const r = parseInt(result[1], 16);
    const g = parseInt(result[2], 16);
    const b = parseInt(result[3], 16);
    return `rgb(${r}, ${g}, ${b})`;
  }
  return "rgb(100, 150, 200)";
};

const convertirColorConAlpha = (hex, alpha = 0.33) => {
  if (!hex) return `rgba(0,0,0,${alpha})`;

  let cleanHex = hex.replace("#", "");

  if (cleanHex.length === 8) {
    cleanHex = cleanHex.substring(2);
  }

  if (cleanHex.length === 3) {
    cleanHex = cleanHex.split("").map(h => h + h).join("");
  }

  const bigint = parseInt(cleanHex, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;

  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const calcularCentroPolygono = (cordenadas) => {
  if (!cordenadas || cordenadas.length === 0) return [0, 0];
  let sumLat = 0, sumLng = 0;
  cordenadas.forEach(coord => {
    sumLat += coord[0];
    sumLng += coord[1];
  });
  return [sumLat / cordenadas.length, sumLng / cordenadas.length];
};

const crearCardIcon = (nombre) => {
  return L.divIcon({
    className: 'segment-card',
    html: `
      <div style="
        background-color: white;
        border: 1px solid #888;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        white-space: nowrap;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
      ">
        ${nombre}
      </div>
    `,
    iconSize: [80, 30],
    iconAnchor: [40, 15],
  });
};

const procesarRutas = (rutasData) => {
  if (!rutasData || !rutasData.status || !rutasData.rutas) {
    console.error("Formato de respuesta inválido:", rutasData);
    return [];
  }

  return rutasData.rutas.map(ruta => ({
    id: ruta.id,
    nombre: ruta.nombre,
    segmentos: (ruta.segmentos || []).map(seg => {
      let coordenadasParsed = [];
      try {
        const coords = JSON.parse(seg.cordenadas);
        coordenadasParsed = coords.map(c => [c.y, c.x]);
      } catch (error) {
        console.error(`Error parseando coordenadas del segmento ${seg.nombre}:`, error);
      }

      return {
        id: seg.id,
        nombre: seg.nombre,
        color: seg.color,
        cordenadas: coordenadasParsed,
        colorHex: seg.color || "#3388ff",
        colorRGB: hexToRgb(seg.color),
        bounds: seg.bounds ? JSON.parse(seg.bounds) : null
      };
    })
  }));
};

const cargarDatos = async () => {
  try {
    if (props.conductores && props.conductores.length > 0) {
      conductores.value = props.conductores;
    } else {
      const respConductores = await axios.get("/api/conductores");
      conductores.value = respConductores.data;
    }

    if (props.rutas) {
      rutas.value = procesarRutas(props.rutas);
    } else {
      const respRutas = await axios.get("/api/asignacion_segmen");
      rutas.value = procesarRutas(respRutas.data);
    }
  } catch (error) {
    console.error("Error cargando datos:", error);
    toast.error("Error al cargar los datos iniciales");
  }
};

const cargarRutasConductor = async (codusuario) => {
  if (!codusuario) return;

  try {
    const resp = await axios.get(`/api/asignacion/${codusuario}`);

    if (!resp.data.rutas) {
      console.log("No se encontraron rutas para el conductor");
      rutasAsignadas.value = [];
      return;
    }

    rutasAsignadas.value = resp.data.rutas.map(ruta => ({
      id: ruta.id,
      nombre: ruta.nombre,
      segmentos: (ruta.segmentos || []).map(seg => {
        let coordenadasParsed = [];
        try {
          const coords = JSON.parse(seg.cordenadas);
          coordenadasParsed = coords.map(c => [c.y, c.x]);
        } catch (error) {
          console.error(`Error parseando coordenadas del segmento ${seg.nombre}:`, error);
        }

        return {
          id: seg.id,
          nombre: seg.nombre,
          color: seg.color,
          cordenadas: coordenadasParsed,
          colorHex: seg.color || "#3388ff",
          colorRGB: hexToRgb(seg.color),
          bounds: seg.bounds ? JSON.parse(seg.bounds) : null
        };
      })
    }));

    if (rutasAsignadas.value.length > 0) {
      seleccionarRuta(rutasAsignadas.value[0]);
    }
  } catch (error) {
    console.error("Error cargando rutas del conductor:", error);
    toast.error("Error al cargar las rutas del conductor");
    rutasAsignadas.value = [];
  }
};

const iniciarDragRuta = (ruta, origen) => {
  dragSource.value = { ruta, origen };
};

const finalizarDrag = () => {
  dragSource.value = null;
  dragOverDisponibles.value = false;
  dragOverRuta.value = false;
};

const dropRutaEnDisponibles = () => {
  if (dragSource.value && dragSource.value.origen === 'ruta') {
    quitarRuta(dragSource.value.ruta);
  }
  finalizarDrag();
};

const dropRutaEnRuta = () => {
  if (dragSource.value && dragSource.value.origen === 'disponibles') {
    asignarRuta(dragSource.value.ruta);
  }
  finalizarDrag();
};

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

const isSelectedRuta = (ruta) => {
  return rutaSeleccionada.value?.id === ruta.id;
};

const limpiarRutas = () => {
  rutasAsignadas.value = [];
  rutaSeleccionada.value = null;
};

const guardarAsignacion = async () => {
  if (!conductorId.value || rutasAsignadas.value.length === 0) {
    toast.error("Seleccione un conductor y al menos una ruta");
    return;
  }

  const totalRutas = props.conductorData?.total_rutas || 0;
  const rutasIds = rutasAsignadas.value.map(r => r.id);

  try {
    if (totalRutas > 0) {
      await axios.put("/api/asignacion_update", {
        usuario: conductorId.value,
        rutas: rutasIds
      });
      toast.success("Rutas actualizadas correctamente");
    } else {
      await axios.post("/api/asignacion_save", {
        usuario: conductorId.value,
        rutas: rutasIds
      });
      toast.success("Rutas asignadas correctamente");
    }
    
    emit("saved");
  } catch (error) {
    console.error("Error en la asignación:", error);
    toast.error(error.response?.data?.message || "Ocurrió un error al guardar la asignación");
  }
};

const cerrar = () => {
  conductorId.value = "";
  rutasAsignadas.value = [];
  rutaSeleccionada.value = null;
  filtroRutasDisponibles.value = "";
  dragOverDisponibles.value = false;
  dragOverRuta.value = false;
  emit("close");
};

watch(() => props.visible, (newVal) => {
  if (newVal) {
    if (props.conductorData) {
      const codUsuario = typeof props.conductorData === 'object'
        ? props.conductorData.codusuario
        : props.conductorData;

      conductorId.value = codUsuario;
      cargarRutasConductor(codUsuario);
    } else {
      conductorId.value = "";
      rutasAsignadas.value = [];
      rutaSeleccionada.value = null;
    }
  }
});

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

:deep(.leaflet-control-zoom) {
  display: none !important;
}

:deep(.leaflet-control-attribution) {
  display: none !important;
}

:deep(.leaflet-control-scale) {
  display: none !important;
}

@media (max-width: 768px) {
  :deep(.leaflet-control) {
    display: none !important;
  }
}
</style>