<template>
  <div class="min-h-screen bg-gray-100 py-10 px-6 flex flex-col items-center">
    <div class="w-full max-w-5xl bg-white shadow-lg rounded-2xl p-8">
      <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
        📍 Geocercas Wialon y Segmentos
      </h2>

      <!-- 🔹 Botones -->
      <div class="flex justify-center mb-8 gap-4">
        <button
          @click="cargarZonas(402037903)"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-xl shadow-md transition-all duration-200 flex items-center gap-2"
          :disabled="loadingZonas"
        >
          <span v-if="loadingZonas" class="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
          {{ loadingZonas ? 'Cargando...' : 'Cargar Geocercas' }}
        </button>

        <button
          @click="cargarSegmentos"
          class="bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-xl shadow-md transition-all duration-200 flex items-center gap-2"
          :disabled="loadingSegmentos"
        >
          <span v-if="loadingSegmentos" class="animate-spin border-2 border-white border-t-transparent rounded-full w-5 h-5"></span>
          {{ loadingSegmentos ? 'Cargando...' : 'Cargar Segmentos' }}
        </button>

        <!-- Botón Actualizar -->
         <button
          class="btn-actualizar"
          :disabled="loadingActualizar"
          @click="actualizarSegmentos"
          v-show="mostrarBtnActualizar"
        >
          <span class="btn-content">
            <svg v-if="loadingActualizar" class="spinner" viewBox="0 0 50 50" aria-hidden="true">
              <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="4" />
            </svg>
            <span class="label">{{ loadingActualizar ? 'Actualizando…' : 'Actualizar' }}</span>
          </span>
        </button>

       <button
        @click="verMapa"
        class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg shadow-md transition"
      >
        🔍 Ver mapa
      </button>


      </div>

      <!-- Notificación -->
      <div v-if="mensaje" 
           :class="['fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white font-medium transition-all', tipoMensaje === 'exito' ? 'bg-green-500' : 'bg-red-500']">
        {{ mensaje }}
      </div>


      <!-- 🔹 Tabla -->
      <div v-if="datosActuales.length" class="overflow-x-auto">
        <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
          <thead class="bg-indigo-600 text-white text-sm uppercase">
            <tr>
              <th class="px-4 py-3 text-left">#</th>
              <th class="px-4 py-3 text-left">ID</th>
              <th class="px-4 py-3 text-left">Nombre</th>
              <th class="px-4 py-3 text-left">Color</th>
              <th class="px-4 py-3 text-left">Coordenadas</th>
              <th class="px-4 py-3 text-left">Límites (Bounds)</th>
            </tr>
          </thead>

          <tbody class="divide-y divide-gray-100 bg-white">
            <tr
              v-for="(z, i) in datosActuales"
              :key="z.id || z.codsegmento"
              class="hover:bg-gray-50 transition-all duration-150"
            >
              <td class="px-4 py-3 text-gray-700">{{ i + 1 }}</td>
              <td class="px-4 py-3 font-semibold text-gray-800">{{ z.id || z.codsegmento }}</td>
              <td class="px-4 py-3">{{ z.n || z.nombre }}</td>
              <td class="px-4 py-3">
                <div
                  class="rounded-full border border-gray-500 w-10 h-10 shadow-sm"
                  :style="{ backgroundColor: tipoDatos === 'zonas' ? '#' + z.c.toString(16).padStart(6,'0') : z.color }"
                  :title="tipoDatos === 'zonas' ? '#' + z.c.toString(16).padStart(6,'0') : z.color"
                ></div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                <template v-if="tipoDatos === 'zonas' && z.p">
                  <div v-for="(p, j) in z.p" :key="j" class="text-xs text-gray-500">
                    ({{ p.x }}, {{ p.y }}, r={{ p.r }})
                  </div>
                </template>
                <template v-else-if="tipoDatos === 'segmentos' && z.cordenadas">
                  {{ z.cordenadas }}
                </template>
                <span v-else class="italic text-gray-400">Sin coordenadas</span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">
                <template v-if="tipoDatos === 'zonas' && z.b">
                  {{ `${z.b.min_x}, ${z.b.min_y}, ${z.b.max_x}, ${z.b.max_y}` }}
                </template>
                <template v-else-if="tipoDatos === 'segmentos' && z.bounds">
                  {{ z.bounds }}
                </template>
                <span v-else>—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mensajes -->
      <div v-else-if="!loadingZonas && !loadingSegmentos" class="text-center text-gray-500 mt-8">
        <p>🗺️ No hay datos disponibles aún.</p>
      </div>

      <div v-if="mensaje" 
     :class="[
       'fixed top-4 right-4 p-4 rounded-lg shadow-lg text-white font-medium transition-all',
       tipoMensaje === 'exito' ? 'bg-green-500' : 'bg-red-500'
     ]">
  {{ mensaje }}
</div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import MapaComponent from "./MapaComponent.vue";

const zonas = ref([])
const segmentos = ref([])
const tipoDatos = ref('')
const error = ref(null)
const loadingZonas = ref(false)
const loadingSegmentos = ref(false)
const loadingActualizar = ref(false)
const mensaje = ref('')
const tipoMensaje = ref('')
const mostrarBtnActualizar = ref(false)
loadingActualizar.value = false

const datosActuales = computed(() => (tipoDatos.value === 'zonas' ? zonas.value : segmentos.value))

const obtenerSID = async () => {
  const { data } = await axios.get('http://localhost:8000/api/obtener-sid')
  if (data.success && data.sid) return data.sid
  throw new Error(data.error || 'No se pudo obtener el SID')
}

const cargarZonas = async (itemId) => {
  mostrarBtnActualizar.value = true
  loadingZonas.value = true
  error.value = null
  tipoDatos.value = 'zonas'
  segmentos.value = []

  try {
    const sid = await obtenerSID()
    const { data } = await axios.post('http://localhost:8000/api/zone-data', { itemId, sid })
    if (data.success && Array.isArray(data.zones)) zonas.value = data.zones
    else error.value = data.error || 'No se recibieron zonas'
  } catch (err) {
    error.value = err.response?.data?.error || err.message
  } finally {
    loadingZonas.value = false
  }
}

const cargarSegmentos = async () => {
  mostrarBtnActualizar.value = false
  loadingSegmentos.value = true
  error.value = null
  tipoDatos.value = 'segmentos'
  zonas.value = []

  try {
    const { data } = await axios.get('http://localhost:8000/api/segmentos')
    if (data.success && Array.isArray(data.segmentos)) {
      segmentos.value = data.segmentos
    } else {
      error.value = 'No se encontraron segmentos'
    }
  } catch (err) {
    error.value = err.message
  } finally {
    loadingSegmentos.value = false
  }
}

const mostrarNotificacion = (msg, tipo = 'exito') => {
  mensaje.value = msg
  tipoMensaje.value = tipo
  setTimeout(() => {
    mensaje.value = ''
    tipoMensaje.value = ''
  }, 4000)
}

const actualizarSegmentos = async () => {
  if (!datosActuales.value || !datosActuales.value.length) return;

  loadingActualizar.value = true;
  error.value = null;

  try {
    const payload = datosActuales.value.map(z => {
      // 🔹 Depurar coordenadas únicas
      let cordenadas = [];
      if (Array.isArray(z.p)) {
        const uniquePoints = {};
        z.p.forEach(p => {
          // Solo conservar puntos válidos
          if (p.x !== undefined && p.y !== undefined) {
            const key = `${p.x}-${p.y}`;
            uniquePoints[key] = { ...p };
            // Eliminar r si es 0
            if (uniquePoints[key].r === 0) delete uniquePoints[key].r;
          }
        });
        cordenadas = Object.values(uniquePoints);
      } else if (Array.isArray(z.cordenadas)) {
        // Si vienen desde el backend, también eliminar r=0 y duplicados
        const uniquePoints = {};
        z.cordenadas.forEach(p => {
          if (p.x !== undefined && p.y !== undefined) {
            const key = `${p.x}-${p.y}`;
            uniquePoints[key] = { ...p };
            if (uniquePoints[key].r === 0) delete uniquePoints[key].r;
          }
        });
        cordenadas = Object.values(uniquePoints);
      }

      // 🔹 Depurar bounds
      let bounds = {};
      if (z.b && typeof z.b === 'object') bounds = z.b;
      else if (z.bounds && typeof z.bounds === 'object') bounds = z.bounds;

      // 🔹 Color en hexadecimal
      let color = '#FFFFFF';
      if (z.c !== undefined && z.c !== null) {
        // Convertir número a hex y asegurarse de que tenga 6 dígitos
        color = '#' + Number(z.c).toString(16).padStart(6, '0');
      } else if (z.color) {
        color = z.color;
      }

      return {
        id: z.id ?? z.codsegmento,
        n: z.n ?? z.nombre ?? '',
        c: color,       // string hexadecimal
        p: cordenadas,  // coordenadas depuradas
        b: bounds       // bounds
      };
    });

    const { data } = await axios.post(
      'http://localhost:8000/api/segmentos/guardar',
      { zonas: payload }
    );

    console.log('Resultado guardar:', data);
    mostrarNotificacion('✅ Segmentos actualizados correctamente', 'exito');

  } catch (err) {
    console.error('Error Axios:', err);
    if (err.response && err.response.data) {
      error.value = err.response.data.error || JSON.stringify(err.response.data);
    } else {
      error.value = err.message;
    }
    mostrarNotificacion('❌ Error al actualizar segmentos', 'error');

  } finally {
    loadingActualizar.value = false;
  }
};







</script>


<style scoped>
.btn-actualizar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.6rem;
  padding: 0.6rem 1.1rem;
  border: none;
  border-radius: 0.6rem;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: transform 120ms ease, box-shadow 120ms ease, opacity 120ms ease;
  user-select: none;
  background: linear-gradient(90deg, #2d8cff 0%, #4bb6ff 50%, #2dd4ff 100%);
  color: white;
  box-shadow: 0 6px 18px rgba(45, 140, 255, 0.18);
}
.btn-actualizar:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 26px rgba(45, 140, 255, 0.22);
}
.btn-actualizar:disabled {
  cursor: not-allowed;
  opacity: 0.6;
  transform: none;
  box-shadow: none;
}
.btn-content { display: inline-flex; align-items: center; gap: 0.6rem; }
.spinner { width: 1.1rem; height: 1.1rem; animation: spin 1s linear infinite; }
.path { stroke: rgba(255,255,255,0.9); stroke-linecap: round; stroke-dasharray: 90; stroke-dashoffset: 60; }
.label { display: inline-block; line-height: 1; }
@keyframes spin { 100% { transform: rotate(360deg); } }

.fixed {
  z-index: 50;
  opacity: 0.95;
}

</style>

<script>
export default {
  methods: {
    verMapa() {
      // Redirige a la ruta del mapa (Laravel)
      this.$inertia.visit("/mapa");
    }
  }
};
</script>