<template>
  <div class="min-h-screen bg-gray-100 font-sans">
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-4 md:p-6">

      <!-- Título y botón -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
        <h1
          class="text-3xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
          <i class="fas fa-road mr-2"></i> Asignación de Rutas
        </h1>

        <button @click="abrirModalAsignarRuta()" class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500 
               hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl 
               shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
          <i class="fas fa-location-arrow"></i>
          <span>Asignar Ruta</span>
        </button>
      </div>

      <!-- Filtro -->
      <div class="bg-white/70 backdrop-blur-md border border-gray-200 shadow-xl rounded-2xl p-5 mb-6 max-w-3xl mx-auto">
        <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center">
          <i class="fas fa-filter mr-2"></i> Buscar
        </h3>
        <div class="relative flex items-center">
          <i class="fas fa-search absolute left-3 text-blue-300"></i>
          <input v-model="filtroBusqueda" type="text" placeholder="Buscar por placa o datos del conductor..." class="w-full pl-10 pr-4 h-12 border border-blue-300 rounded-xl 
                   focus:border-blue-400 focus:ring-4 focus:ring-blue-100 
                   shadow-md hover:shadow-lg transition-all" />
        </div>
      </div>

      <!-- Loading -->
      <div v-if="cargando" class="flex justify-center py-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Tabla de conductores -->
      <div v-else class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-800">
            <thead
              class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 uppercase text-xs font-semibold tracking-wide">
              <tr>
                <th class="px-6 py-4 text-left">#</th>
                <th class="px-6 py-4 text-left">Placa</th>
                <th class="px-6 py-4 text-left">Conductor</th>
                <th class="px-6 py-4 text-center">N° Rutas</th>
                <th class="px-6 py-4 text-center">N° Viajes</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(conductor, index) in conductoresPaginados" :key="conductor.id"
                class="border-b border-gray-100 hover:bg-blue-50 transition-all">
                <td class="px-6 py-4 font-semibold">
                  {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                </td>
                <td class="px-6 py-4 font-medium">{{ conductor.nombre ?? 'Sin placa' }}</td>
                <td class="px-6 py-4 font-medium">{{ conductor.identificador ?? 'Sin datos' }}</td>
                <td class="px-6 py-4 font-medium text-center">{{ conductor.total_rutas ?? 0 }}</td>
                <td class="px-6 py-4 font-medium text-center">{{ conductor.total_viajes ?? 0 }}</td>
                <td class="px-6 py-4 text-center space-x-2">
                  <button class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                    @click="abrirModalAsignarRuta(conductor)" title="Asignar ruta">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                    @click="confirmarEliminar(conductor)" title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>

              <tr v-if="conductoresFiltrados.length === 0">
                <td colspan="6" class="text-center py-10 text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                  <p>No se encontraron resultados</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="conductoresFiltrados.length > 0"
        class="mt-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
        <div class="text-sm text-gray-600">
          Mostrando
          {{ Math.min((paginaActual - 1) * registrosPorPagina + 1, conductoresFiltrados.length) }}
          -
          {{ Math.min(paginaActual * registrosPorPagina, conductoresFiltrados.length) }}
          de {{ conductoresFiltrados.length }} registros
        </div>
        <div class="flex space-x-2">
          <button @click="cambiarPagina(paginaActual - 1)" :disabled="paginaActual === 1"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100">Anterior</button>
          <span class="px-4 py-2 font-medium text-gray-700">Página {{ paginaActual }} de {{ totalPaginas }}</span>
          <button @click="cambiarPagina(paginaActual + 1)" :disabled="paginaActual >= totalPaginas"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100">Siguiente</button>
        </div>
      </div>

      <!-- Modal -->
      <ModalAsignarRuta
        :visible="modalVisible"
        :conductorData="conductorSeleccionado"
        @close="cerrarModal"
        @saved="actualizarTabla"
      />

    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import Header from "@/pages/Header.vue";
import axios from "axios";
import ModalAsignarRuta from "@/components/ModalAsignarRuta.vue";

const nombreUsuario = "Paulo Ramos";
const conductores = ref([]);
const cargando = ref(true);
const filtroBusqueda = ref("");


const modalVisible = ref(false);
const conductorSeleccionado = ref(null);


const paginaActual = ref(1);
const registrosPorPagina = 10;


const cargarConductores = async () => {
  try {
    cargando.value = true;
    const { data } = await axios.get("/api/asignacion");
    conductores.value = data;
  } catch (error) {
    console.error("Error al cargar conductores:", error);
  } finally {
    cargando.value = false;
  }
};

onMounted(() => {
  cargarConductores();
});

// Filtros
const conductoresFiltrados = computed(() =>
  conductores.value.filter(c => {
    const texto = filtroBusqueda.value.toLowerCase();
    return (c.placa ?? "").toLowerCase().includes(texto) ||
           (c.identificador ?? "").toLowerCase().includes(texto);
  })
);

const totalPaginas = computed(() =>
  Math.ceil(conductoresFiltrados.value.length / registrosPorPagina)
);

const conductoresPaginados = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina;
  return conductoresFiltrados.value.slice(start, start + registrosPorPagina);
});

const cambiarPagina = n => {
  if (n >= 1 && n <= totalPaginas.value) paginaActual.value = n;
};

// Reset paginación al filtrar
watch(filtroBusqueda, () => paginaActual.value = 1);

// -------- MODAL --------
const abrirModalAsignarRuta = (conductor = null) => {
  conductorSeleccionado.value = conductor ? { ...conductor } : null;
  modalVisible.value = true;
};

const cerrarModal = () => {
  modalVisible.value = false;
  conductorSeleccionado.value = null;
};

const actualizarTabla = async () => {
  await cargarConductores();
  cerrarModal();
};

// Eliminar
const confirmarEliminar = async conductor => {
  if (confirm(`¿Eliminar a ${conductor.identificador}?`)) {
    try {
      await axios.delete(`/api/conductores/${conductor.id}`);
      conductores.value = conductores.value.filter(c => c.id !== conductor.id);
    } catch (error) {
      alert("No se pudo eliminar.");
    }
  }
};
</script>