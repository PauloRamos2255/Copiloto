<template>
  <div class="min-h-screen w-full bg-gray-50 flex flex-col">
    <!-- Header fijo -->
    <header class="bg-white shadow-sm p-4 sticky top-0 z-10">
      <h1 class="text-2xl font-bold text-gray-800 text-center">🛣️ Segmentos Disponibles</h1>
    </header>

    <!-- Notificación -->
    <div
      v-if="mensaje"
      :class="[
        'fixed top-16 right-4 p-3 rounded-lg shadow-md text-white font-medium transition-all text-sm z-20',
        tipoMensaje === 'exito' ? 'bg-green-400' : 'bg-red-400'
      ]"
    >
      {{ mensaje }}
    </div>

    <!-- Tabla -->
    <main class="flex-1 overflow-auto p-4">
      <table class="w-full border border-gray-200 bg-white rounded-lg table-auto">
        <thead class="bg-blue-100 text-gray-700 text-sm sticky top-0">
          <tr>
            <th class="px-3 py-2">#</th>
            <th class="px-3 py-2">Código</th>
            <th class="px-3 py-2 w-70 truncate">Nombre</th>
            <th class="px-3 py-2">Color</th>
            <th class="px-3 py-2">Coordenadas</th>
            <th class="px-3 py-2">Bouns</th>
          </tr>
        </thead>
        <tbody class="text-gray-700 text-sm">
          <tr
            v-for="(s, i) in segmentos"
            :key="s.codsegmento"
            :class="i % 2 === 0 ? 'bg-gray-50' : 'bg-white'"
            class="hover:bg-gray-100 transition-colors duration-150"
          >
            <td class="px-3 py-2">{{ i + 1 }}</td>
            <td class="px-3 py-2 font-semibold">{{ s.codsegmento }}</td>
            <td class="px-3 py-2 max-w-[10rem] truncate" title="{{ s.nombre }}">
  {{ s.nombre }}
</td>
            <td class="px-3 py-2">
              <div
                class="w-6 h-6 rounded-full border border-gray-300 shadow-sm"
                :style="{ backgroundColor: s.color }"
                :title="s.color"
              ></div>
            </td>
            <td class="px-3 py-2 text-xs text-gray-600">
              <div v-if="s.cordenadas?.length">
                <div v-for="(p, j) in s.cordenadas.slice(0, 3)" :key="j">
                  ({{ p.x.toFixed(2) }}, {{ p.y.toFixed(2) }})
                </div>
                <div v-if="s.cordenadas.length > 3" class="text-gray-400">
                  ...{{ s.cordenadas.length - 3 }} más
                </div>
              </div>
              <span v-else class="italic text-gray-400">Sin coordenadas</span>
            </td>
            <!-- Bouns calculado a partir de bounds -->
            <td class="px-3 py-2">
              {{ s.bounds ? s.bounds.max_x +" "+s.bounds.min_x : '-' }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Mensaje si no hay datos -->
      <div v-if="!segmentos.length" class="text-center text-gray-500 mt-4 text-sm">
        🗂️ No hay segmentos cargados.
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const segmentos = ref([]);
const mensaje = ref("");
const tipoMensaje = ref("exito");

const cargarSegmentos = async () => {
  try {
    const { data } = await axios.get("http://localhost:8000/api/segmentos");
    if (data.success && Array.isArray(data.segmentos)) {
      // Calculamos bouns a partir de bounds al recibir los datos
      segmentos.value = data.segmentos.map(s => ({
        ...s,
        bouns: s.bounds ? (s.bounds.max_x - s.bounds.min_x).toFixed(2) : null
      }));
    } else {
      mensaje.value = "No se encontraron segmentos";
      tipoMensaje.value = "error";
    }
  } catch (err) {
    mensaje.value = err.message || "Error al cargar segmentos";
    tipoMensaje.value = "error";
  }

  setTimeout(() => {
    mensaje.value = "";
    tipoMensaje.value = "";
  }, 4000);
};

onMounted(cargarSegmentos);
</script>

<style scoped>
/* Notificación fija */
.fixed {
  z-index: 50;
  opacity: 0.95;
}
</style>
