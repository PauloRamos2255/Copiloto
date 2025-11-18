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

      <!-- Tabla de conductores -->
      <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
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

              <!-- SKELETON CUANDO CARGA -->
              <template v-if="cargando">
                <tr v-for="n in registrosPorPagina" :key="n">
                  <td colspan="6" class="px-6 py-4">
                    <div class="flex gap-2 items-center">
                      <div class="h-6 w-6 bg-gray-300 rounded-full relative overflow-hidden">
                        <div
                          class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                        </div>
                      </div>
                      <div class="flex-1 flex gap-2">
                        <div class="h-4 bg-gray-300 rounded w-1/6 relative overflow-hidden">
                          <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                          </div>
                        </div>
                        <div class="h-4 bg-gray-300 rounded w-1/4 relative overflow-hidden">
                          <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                          </div>
                        </div>
                        <div class="h-4 bg-gray-300 rounded w-1/4 relative overflow-hidden">
                          <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                          </div>
                        </div>
                        <div class="h-4 bg-gray-300 rounded w-1/6 relative overflow-hidden">
                          <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                          </div>
                        </div>
                        <div class="h-4 bg-gray-300 rounded w-1/6 relative overflow-hidden">
                          <div
                            class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                          </div>
                        </div>
                      </div>
                    </div>
                  </td>
                </tr>
              </template>

              <!-- FILAS REALES -->
              <template v-else v-for="(conductor, index) in conductoresPaginados" :key="conductor.codusuario">

                <!-- FILA PRINCIPAL -->
                <tr @click="toggleExpandirFila(conductor.codusuario)"
                  class="border-b border-gray-100 hover:bg-blue-50 transition-all cursor-pointer">
                  <td class="px-6 py-4 font-semibold">
                    {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                  </td>
                  <td class="px-6 py-4 font-medium">{{ conductor.nombre ?? 'Sin placa' }}</td>
                  <td class="px-6 py-4 font-medium">{{ conductor.identificador ?? 'Sin datos' }}</td>
                  <td class="px-6 py-4 font-medium text-center">{{ conductor.total_rutas ?? 0 }}</td>
                  <td class="px-6 py-4 font-medium text-center">{{ conductor.total_viajes ?? 0 }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-center">
                      <button
                        class="flex items-center justify-center px-4 py-2 bg-yellow-500 text-white rounded-lg shadow transition-colors duration-200 hover:bg-yellow-600"
                        @click.stop="abrirModalAsignarRuta(conductor)" title="Editar">
                        <i class="fas fa-edit mr-2"></i>
                        Editar
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- FILA EXPANDIDA CON SKELETON COINCIDENTE -->
                <tr v-if="filaExpandida === conductor.codusuario" :key="`detalle-${conductor.codusuario}`">
                  <td colspan="6" class="bg-blue-50">
                    <div class="p-3">

                      <!-- Skeleton loading state -->
                      <template v-if="rutasDetalle.length === 0">
                        <div class="flex flex-wrap gap-2">
                          <div v-for="n in 4" :key="n"
                            class="bg-white border border-gray-200 rounded-lg shadow overflow-hidden w-28">

                            <!-- Skeleton imagen -->
                            <div class="w-full h-20 bg-gray-300 relative overflow-hidden">
                              <div
                                class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                              </div>
                            </div>

                            <!-- Skeleton contenido -->
                            <div class="p-2 space-y-1">
                              <!-- Skeleton nombre y badge -->
                              <div class="flex items-start justify-between gap-1">
                                <div class="h-3 bg-gray-300 rounded w-16 relative overflow-hidden flex-1">
                                  <div
                                    class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                  </div>
                                </div>
                                <div class="w-4 h-4 rounded-full bg-gray-300 flex-shrink-0 relative overflow-hidden">
                                  <div
                                    class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                  </div>
                                </div>
                              </div>

                              <!-- Skeleton información -->
                              <div class="space-y-0.5">
                                <div class="h-2 bg-gray-300 rounded w-20 relative overflow-hidden">
                                  <div
                                    class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                  </div>
                                </div>
                                <div class="h-2 bg-gray-300 rounded w-16 relative overflow-hidden">
                                  <div
                                    class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </template>

                      <!-- Actual content -->
                      <template v-else>
                        <div class="flex flex-wrap gap-2">
                          <div v-for="ruta in rutasDetalle" :key="ruta.codruta"
                            class="bg-white border border-gray-200 rounded-lg shadow hover:shadow-md transition-shadow overflow-hidden w-28">

                            <!-- Imagen -->
                            <div class="w-full h-20 overflow-hidden bg-gray-100">
                              <img :src="`storage/${ruta.icono}`" class="w-full h-full object-cover"
                                :alt="ruta.nombre" />
                            </div>

                            <!-- Contenido -->
                            <div class="p-2 space-y-1">
                              <!-- Nombre y Badge -->
                              <div class="flex items-start justify-between gap-1">
                                <h3 class="font-semibold text-xs text-gray-800 line-clamp-1 flex-1">
                                  {{ ruta.nombre }}
                                </h3>
                                <div
                                  class="w-4 h-4 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                  :class="ruta.tipo === 'G' ? 'bg-blue-500' : ruta.tipo === 'V' ? 'bg-green-500' : 'bg-gray-500'">
                                  {{ ruta.tipo }}
                                </div>
                              </div>

                              <!-- Información compacta -->
                              <div class="space-y-0.5 text-xs">
                                <p class="text-gray-600"><strong>Límite:</strong> {{ ruta.limiteGeneral }} km/h</p>
                                <p class="text-gray-600"><strong>Seg:</strong> {{ ruta.cantidadSegmentos }}</p>
                              </div>
                            </div>
                          </div>
                        </div>
                      </template>

                    </div>
                  </td>
                </tr>

              </template>

              <!-- SIN RESULTADOS -->
              <tr v-if="!cargando && conductoresFiltrados.length === 0">
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

      <ModalAsignarRuta :visible="modalVisible" :conductorData="conductorSeleccionado" :rutas="rutasCache"
        :conductores="conductores" @close="cerrarModal" @saved="actualizarTabla" />

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
const rutasCache = ref(null);
const cargando = ref(true);
const filtroBusqueda = ref("");

const modalVisible = ref(false);
const conductorSeleccionado = ref(null);

const paginaActual = ref(1);
const registrosPorPagina = 10;

const filaExpandida = ref(null);
const rutasDetalle = ref([]);
const rutasDetalleCache = ref({});

const cargarDetalleRuta = async (id) => {
  try {
    if (rutasDetalleCache.value[id]) {
      rutasDetalle.value = rutasDetalleCache.value[id];
      return;
    }
    const { data } = await axios.get(`/api/asignacion_get/${id}`);
    rutasDetalleCache.value[id] = data;
    rutasDetalle.value = data;
  } catch (e) {
    console.error("Error detalle:", e);
  }
};

const toggleExpandirFila = async (id) => {
  if (filaExpandida.value === id) {
    filaExpandida.value = null;
    rutasDetalle.value = [];
  } else {
    filaExpandida.value = id;
    await cargarDetalleRuta(id);
  }
};

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

const cargarRutas = async () => {
  try {
    const { data } = await axios.get("/api/asignacion_segmen");
    rutasCache.value = data;
  } catch (error) {
    console.error("Error al cargar rutas:", error);
  }
};

onMounted(() => {
  Promise.all([
    cargarConductores(),
    cargarRutas()
  ]);
});

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

watch(filtroBusqueda, () => paginaActual.value = 1);

const abrirModalAsignarRuta = (conductor = null) => {
  conductorSeleccionado.value = conductor ? { ...conductor } : null;
  modalVisible.value = true;
};

const cerrarModal = () => {
  modalVisible.value = false;
  conductorSeleccionado.value = null;
};

const actualizarTabla = async () => {
  rutasDetalleCache.value = {};
  await cargarConductores();
  cerrarModal();
};

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
