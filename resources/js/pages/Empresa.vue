<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import Header from '@/pages/Header.vue';
import ModalEmpresa from '@/components/ModalEmpresa.vue';

const nombreUsuario = 'Paulo';
const empresas = ref([]);
const cargando = ref(false);

const filtro = ref('');
const paginaActual = ref(1);
const registrosPorPagina = 5;

const modalVisible = ref(false);
const empresaSeleccionada = ref(null);

const modalUsuariosVisible = ref(false);
const empresaConUsuarios = ref(null);

// Cache para prefetch
const prefetchedUrls = new Set();

// Prefetch nativo
function prefetchNativo(url) {
  if (prefetchedUrls.has(url)) return;
  
  prefetchedUrls.add(url);
  
  fetch(url, {
    method: 'GET',
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
    },
  }).catch(error => {
    console.warn(`Prefetch fallido para ${url}:`, error);
    prefetchedUrls.delete(url);
  });
}

// Abrir formulario (crear o editar)
function abrirFormularioEmpresa(empresa = null) {
    if (empresa) {
        empresaSeleccionada.value = { ...empresa };
    } else {
        empresaSeleccionada.value = null;
    }
    modalVisible.value = true;
}

// Cerrar modal
function cerrarModal() {
    modalVisible.value = false;
    empresaSeleccionada.value = null;
}

// Editar empresa
function editarEmpresa(emp) {
    abrirFormularioEmpresa(emp);
}

// Ver usuarios de una empresa con prefetch
async function verUsuarios(idEmpresa) {
    try {
        const response = await axios.get(`/api/empresas/${idEmpresa}/usuarios`);
        
        if (response.data.ok) {
            empresaConUsuarios.value = response.data;
            modalUsuariosVisible.value = true;
        }
    } catch (error) {
        console.error('Error al obtener usuarios:', error);
        alert('Error al obtener los usuarios de la empresa');
    }
}

// Prefetch al hover en usuarios
function prefetchUsuarios(idEmpresa) {
    prefetchNativo(`/api/empresas/${idEmpresa}/usuarios`);
}

// Cerrar modal de usuarios
function cerrarModalUsuarios() {
    modalUsuariosVisible.value = false;
    empresaConUsuarios.value = null;
}

// Actualizar tabla después de guardar
async function actualizarTabla(empresaGuardada) {
    try {
        if (empresaGuardada.id) {
            const index = empresas.value.findIndex(e => e.id === empresaGuardada.id);
            if (index !== -1) {
                empresas.value[index] = empresaGuardada;
            } else {
                await cargarEmpresas();
                prefetchTodosLosUsuarios();
            }
        } else {
            await cargarEmpresas();
            prefetchTodosLosUsuarios();
        }
        
        cerrarModal();
    } catch (error) {
        console.error('Error al actualizar tabla:', error);
    }
}

// Cargar empresas desde la API
const cargarEmpresas = async () => {
    try {
        cargando.value = true;
        const response = await axios.get('/api/empresas');

        if (response.data && response.data.ok) {
            empresas.value = response.data.empresas || [];
        } else {
            empresas.value = [];
            console.warn('La respuesta no contiene el formato esperado:', response.data);
        }

        console.log('Empresas cargadas:', empresas.value.length);

    } catch (error) {
        console.error("Error al cargar empresas:", error);
        if (error.response) {
            alert(`Error del servidor: ${error.response.status} - ${error.response.data.msg || 'Error desconocido'}`);
        } else if (error.request) {
            alert('No se pudo conectar con el servidor. Verifica tu conexión.');
        } else {
            alert('Error al cargar las empresas.');
        }
        empresas.value = [];
    } finally {
        cargando.value = false;
    }
};

// Prefetch de todos los usuarios al cargar
async function prefetchTodosLosUsuarios() {
    for (const empresa of empresas.value) {
        if (empresa.usuarios_count > 0) {
            prefetchNativo(`/api/empresas/${empresa.id}/usuarios`);
        }
    }
}

// Cargar empresas al montar
onMounted(async () => {
    await cargarEmpresas();
    // Prefetch automático después de cargar empresas
    prefetchTodosLosUsuarios();
});

// Filtrar empresas
const empresasFiltradas = computed(() => {
    if (!filtro.value) return empresas.value;
    
    const textoFiltro = filtro.value.toLowerCase().trim();
    
    return empresas.value.filter(e => {
        const nombre = (e.nombre || '').toLowerCase();
        const telefono = (e.empresa_col || '').toLowerCase();
        const observacion = (e.observacion || '').toLowerCase();
        
        return nombre.includes(textoFiltro) || 
               telefono.includes(textoFiltro) || 
               observacion.includes(textoFiltro);
    });
});

// Total de páginas
const totalPaginas = computed(() => {
    return Math.ceil(empresasFiltradas.value.length / registrosPorPagina) || 1;
});

// Empresas paginadas
const empresasFiltradasPaginadas = computed(() => {
    const start = (paginaActual.value - 1) * registrosPorPagina;
    return empresasFiltradas.value.slice(start, start + registrosPorPagina);
});

// Cambiar página
const cambiarPagina = (num) => {
    if (num < 1 || num > totalPaginas.value) return;
    paginaActual.value = num;
};

// Watch para resetear página al filtrar
watch(filtro, () => {
    paginaActual.value = 1;
});

// Eliminar empresa
const eliminarEmpresa = async (empresa) => {
    if (empresa.usuarios_count > 0) {
        alert(`No se puede eliminar la empresa "${empresa.nombre}" porque tiene ${empresa.usuarios_count} usuario(s) vinculado(s).`);
        return;
    }

    console.log(empresa)

    if (!confirm(`¿Seguro que deseas eliminar la empresa "${empresa.nombre}"?`)) return;

    try {
        const response = await axios.delete(`/api/empresas/${empresa.id}`);
        
        if (response.data.ok) {
            empresas.value = empresas.value.filter(e => e.id !== empresa.id);
            
            if (empresasFiltradasPaginadas.value.length === 0 && paginaActual.value > 1) {
                paginaActual.value--;
            }
            
            alert(response.data.msg || 'Empresa eliminada exitosamente');
        } else {
            alert(response.data.msg || 'No se pudo eliminar la empresa');
        }
    } catch (error) {
        console.error('Error al eliminar empresa:', error);
        
        if (error.response?.status === 400) {
            alert(error.response.data.msg || 'Esta empresa tiene usuarios vinculados y no puede ser eliminada.');
        } else if (error.response?.status === 404) {
            alert('La empresa no existe o ya fue eliminada.');
            await cargarEmpresas();
        } else {
            alert(error.response?.data?.msg || "Error al eliminar la empresa.");
        }
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans">
        <!-- Header -->
        <Header :nombreUsuario="nombreUsuario" />

        <main class="p-4 md:p-6">

            <!-- Encabezado y botón crear empresa -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
                <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold bg-clip-text text-transparent 
                   bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
                    <i class="fas fa-building mr-2"></i>Gestión de Empresas
                </h1>

                <button @click="abrirFormularioEmpresa()" 
                    class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500 
                       hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl 
                       shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
                    <i class="fas fa-plus"></i>
                    <span>Crear Empresa</span>
                </button>
            </div>

            <!-- Filtro de empresas -->
            <div class="mb-6 max-w-md">
                <input type="text" v-model="filtro" placeholder="Filtrar por nombre o teléfono..."
                    class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:border-blue-600 
                    focus:ring-4 focus:ring-blue-100 shadow-lg hover:shadow-xl transition-all duration-300 
                    text-gray-800 placeholder-blue-300 font-medium" />
            </div>

            <!-- Loading state -->
            <div v-if="cargando" class="flex justify-center items-center py-10">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Tabla de empresas -->
            <div v-else class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-800">
                        <thead
                            class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 uppercase text-xs font-semibold tracking-wide">
                            <tr>
                                <th class="px-6 py-4 text-left">#</th>
                                <th class="px-6 py-4 text-left">Nombre</th>
                                <th class="px-6 py-4 text-left">Observación</th>
                                <th class="px-6 py-4 text-left">Teléfono</th>
                                <th class="px-6 py-4 text-center">Usuarios</th>
                                <th class="px-6 py-4 text-center">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(empresa, index) in empresasFiltradasPaginadas" :key="empresa.id"
                                class="border-b border-gray-100 hover:bg-blue-50 transition-all">

                                <td class="px-6 py-4 font-semibold">
                                    {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                                </td>

                                <td class="px-6 py-4 font-medium">{{ empresa.nombre }}</td>

                                <td class="px-6 py-4 text-gray-700">{{ empresa.observacion || '-' }}</td>

                                <td class="px-6 py-4 text-gray-700">{{ empresa.empresa_col || '-' }}</td>

                                <!-- Columna de Usuarios CON PREFETCH -->
                                <td class="px-6 py-4 text-center">
                                    <span v-if="empresa.usuarios_count > 0" 
                                          class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold cursor-pointer hover:bg-blue-200 transition-colors"
                                          @mouseenter="prefetchUsuarios(empresa.id)"
                                          @click="verUsuarios(empresa.id)"
                                          title="Click para ver usuarios">
                                        <i class="fas fa-users mr-1"></i>
                                        {{ empresa.usuarios_count }}
                                    </span>
                                    <span v-else class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-500 rounded-full text-xs">
                                        <i class="fas fa-user-slash mr-1"></i>
                                        Sin usuarios
                                    </span>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4 text-center space-x-2">
                                    <button
                                        class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                                        @click.stop="editarEmpresa(empresa)" 
                                        title="Editar empresa">
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button
                                        class="p-2 rounded-full transition-colors"
                                        :class="empresa.usuarios_count > 0 
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                                            : 'bg-red-50 text-red-600 hover:bg-red-100'"
                                        @click.stop="eliminarEmpresa(empresa)" 
                                        :title="empresa.usuarios_count > 0 
                                            ? 'No se puede eliminar (tiene usuarios vinculados)' 
                                            : 'Eliminar empresa'"
                                        :disabled="empresa.usuarios_count > 0">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="empresasFiltradas.length === 0">
                                <td colspan="6" class="text-center py-10 text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                                    <p>{{ filtro ? 'No se encontraron resultados' : 'No hay empresas disponibles' }}</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div v-if="!cargando && empresasFiltradas.length > 0" 
                class="mt-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
                
                <div class="text-sm text-gray-600">
                    Mostrando {{ Math.min((paginaActual - 1) * registrosPorPagina + 1, empresasFiltradas.length) }} 
                    - {{ Math.min(paginaActual * registrosPorPagina, empresasFiltradas.length) }} 
                    de {{ empresasFiltradas.length }} empresas
                </div>

                <div class="flex space-x-2">
                    <button @click="cambiarPagina(paginaActual - 1)" :disabled="paginaActual === 1"
                        class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed 
                        hover:bg-gray-100 transition-all">
                        Anterior
                    </button>

                    <span class="px-4 py-2 font-medium text-gray-700">
                        Página {{ paginaActual }} de {{ totalPaginas }}
                    </span>

                    <button @click="cambiarPagina(paginaActual + 1)" :disabled="paginaActual >= totalPaginas"
                        class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed 
                        hover:bg-gray-100 transition-all">
                        Siguiente
                    </button>
                </div>
            </div>

            <!-- Modal Crear/Editar Empresa -->
            <ModalEmpresa 
                :visible="modalVisible" 
                :empresaData="empresaSeleccionada" 
                @close="cerrarModal"
                @saved="actualizarTabla" 
            />

            <!-- Modal Ver Usuarios -->
            <ModalUsuarios
                :visible="modalUsuariosVisible"
                :empresa="empresaConUsuarios"
                @close="cerrarModalUsuarios"
            />
        </main>
    </div>
</template>

<style scoped>
button:disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
}
</style>