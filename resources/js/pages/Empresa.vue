<template>
    <div class="min-h-screen bg-gray-100 font-sans">
        <!-- Header -->
        <Header :nombreUsuario="nombreUsuario" />

        <main class="p-4 md:p-6">

            <!-- Encabezado -->
            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
                <h1
                    class="text-2xl md:text-3xl lg:text-4xl font-extrabold bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
                    <i class="fas fa-building mr-2"></i>
                    Gestión de Empresas
                </h1>

                <button
                    @click="abrirFormularioEmpresa()"
                    class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500
                           hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl
                           shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
                    <i class="fas fa-plus"></i>
                    <span>Crear Empresa</span>
                </button>
            </div>

            <!-- Filtro -->
            <div
                class="bg-white/70 backdrop-blur-md border border-gray-200 shadow-xl rounded-2xl p-5 mb-6 max-w-3xl mx-auto">
                <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center">
                    <i class="fas fa-filter mr-2"></i> Buscar
                </h3>
                <div class="relative flex items-center">
                    <i class="fas fa-search absolute left-3 text-blue-300"></i>
                    <input
                        v-model="filtro"
                        type="text"
                        placeholder="Filtrar por nombre o teléfono..."
                        class="w-full pl-10 pr-4 h-12 border border-blue-300 rounded-xl
                               focus:border-blue-400 focus:ring-4 focus:ring-blue-100
                               shadow-md hover:shadow-lg transition-all"
                    />
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-gray-800">

                        <!-- Encabezados -->
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

                        <!-- Loader Skeleton -->
                        <tbody v-if="cargando">
                            <tr
                                v-for="n in 5"
                                :key="'empresa-skel-' + n"
                                class="border-b border-gray-100 animate-pulse"
                            >
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidth() }"></div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                                </td>

                                <td class="px-6 py-4 text-center flex justify-center space-x-2">
                                    <div class="h-4 w-6 bg-gray-300 rounded"></div>
                                    <div class="h-4 w-6 bg-gray-300 rounded"></div>
                                </td>
                            </tr>
                        </tbody>

                        <!-- Datos principales -->
                        <tbody v-else>
                            <tr
                                v-for="(empresa, index) in empresasFiltradasPaginadas"
                                :key="empresa.id"
                                class="border-b border-gray-100 hover:bg-blue-50 transition-all"
                            >
                                <td class="px-6 py-4 font-semibold">
                                    {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                                </td>

                                <td class="px-6 py-4 font-medium">
                                    {{ empresa.nombre }}
                                </td>

                                <td class="px-6 py-4 text-gray-700">
                                    {{ empresa.observacion || '-' }}
                                </td>

                                <td class="px-6 py-4 text-gray-700">
                                    {{ formatearTelefonoTabla(empresa.empresa_col) || '-' }}
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <div
                                        v-if="empresa.usuarios_count > 0"
                                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-semibold text-white
                                               rounded-full bg-gradient-to-r from-emerald-500 to-emerald-700 shadow"
                                    >
                                        <i class="fas fa-users"></i>
                                        {{ empresa.usuarios_count }} usuarios
                                    </div>

                                    <div
                                        v-else
                                        class="inline-flex items-center gap-2 px-5 py-2 text-xs font-medium text-gray-700
                                               rounded-full bg-gradient-to-r from-gray-300 to-gray-400 shadow"
                                    >
                                        <i class="fas fa-user-slash"></i>
                                        Sin usuarios
                                    </div>
                                </td>

                                <!-- Acciones -->
                                <td class="px-6 py-4 text-center space-x-2">
                                    <button
                                        class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                                        @click.stop="editarEmpresa(empresa)"
                                        title="Editar empresa"
                                    >
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button
                                        class="p-2 rounded-full transition-colors"
                                        :class="empresa.usuarios_count > 0
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : 'bg-red-50 text-red-600 hover:bg-red-100'"
                                        @click.stop="eliminarEmpresa(empresa)"
                                        :disabled="empresa.usuarios_count > 0"
                                        :title="empresa.usuarios_count > 0
                                            ? 'No se puede eliminar (tiene usuarios vinculados)'
                                            : 'Eliminar empresa'"
                                    >
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="empresasFiltradas.length === 0">
                                <td colspan="6" class="text-center py-10 text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                                    <p>
                                        {{ filtro ? 'No se encontraron resultados' : 'No hay empresas disponibles' }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginación -->
            <div
                v-if="!cargando && empresasFiltradas.length > 0"
                class="mt-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0"
            >
                <div></div>

                <div class="flex space-x-2">
                    <button
                        @click="cambiarPagina(paginaActual - 1)"
                        :disabled="paginaActual === 1"
                        class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed
                               hover:bg-gray-100 transition-all"
                    >
                        Anterior
                    </button>

                    <span class="px-4 py-2 font-medium text-gray-700">
                        Página {{ paginaActual }} de {{ totalPaginas }}
                    </span>

                    <button
                        @click="cambiarPagina(paginaActual + 1)"
                        :disabled="paginaActual >= totalPaginas"
                        class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed
                               hover:bg-gray-100 transition-all"
                    >
                        Siguiente
                    </button>
                </div>
            </div>

            <!-- Modal -->
            <ModalEmpresa
                :visible="modalVisible"
                :empresaData="empresaSeleccionada"
                @close="cerrarModal"
                @saved="actualizarTabla"
            />

        </main>
    </div>
</template>


<script setup>

import { ref, reactive, computed, watch, onMounted, nextTick, onUnmounted } from "vue";
import axios from 'axios';
import Header from '@/pages/Header.vue';
import ModalEmpresa from '@/components/ModalEmpresa.vue';

const nombreUsuario = 'Paulo';
const empresas = ref([]);


const filtro = ref('');
const paginaActual = ref(1);
const registrosPorPagina = 5;

const modalVisible = ref(false);
const empresaSeleccionada = ref(null);

const modalUsuariosVisible = ref(false);
const empresaConUsuarios = ref(null);
const cargando = ref(true);


// ---------------------------
//  CRUD EMPRESAS
// ---------------------------
function abrirFormularioEmpresa(empresa = null) {
    empresaSeleccionada.value = empresa ? { ...empresa } : null;
    modalVisible.value = true;
}

function cerrarModal() {
    modalVisible.value = false;
    empresaSeleccionada.value = null;
}

function editarEmpresa(emp) {
    abrirFormularioEmpresa(emp);
}



function cerrarModalUsuarios() {
    modalUsuariosVisible.value = false;
    empresaConUsuarios.value = null;
}

const randomWidth = () => `${Math.floor(Math.random() * 12 + 20)}ch`;
const randomWidthSmall = () => `${Math.floor(Math.random() * 8 + 12)}ch`;

// ---------------------------
//  TABLA / LISTADO
// ---------------------------
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
    } catch (err) {
        console.error("Error actualizando tabla:", err);
    }
}

const cargarEmpresas = async () => {
    try {
        cargando.value = true;

        const response = await axios.get('/api/empresas');

        if (response.data.ok) {
            empresas.value = response.data.empresas || [];
        } else {
            empresas.value = [];
        }

    } catch (err) {
        console.error("Error al cargar empresas:", err);
        empresas.value = [];
    } finally {
        cargando.value = false;
    }
};

function formatearTelefonoTabla(valor) {
    if (!valor) return '';
    const numeros = valor.replace(/\D/g, '').slice(0, 9);
    let formatted = '';
    for (let i = 0; i < numeros.length; i++) {
        formatted += numeros[i];
        if (i === 2 || i === 5) formatted += '-';
    }
    return formatted;
}


onMounted(async () => {
    await cargarEmpresas();
});

// ---------------------------
//  FILTRO + PAGINACIÓN
// ---------------------------
const empresasFiltradas = computed(() => {
    if (!filtro.value) return empresas.value;

    const t = filtro.value.toLowerCase().trim();
    return empresas.value.filter(e =>
        (e.nombre || '').toLowerCase().includes(t) ||
        (e.empresa_col || '').toLowerCase().includes(t) ||
        (e.observacion || '').toLowerCase().includes(t)
    );
});

const totalPaginas = computed(() => {
    return Math.ceil(empresasFiltradas.value.length / registrosPorPagina) || 1;
});

const empresasFiltradasPaginadas = computed(() => {
    const start = (paginaActual.value - 1) * registrosPorPagina;
    return empresasFiltradas.value.slice(start, start + registrosPorPagina);
});

function cambiarPagina(p) {
    if (p < 1 || p > totalPaginas.value) return;
    paginaActual.value = p;
}

watch(filtro, () => paginaActual.value = 1);

// ---------------------------
//  ELIMINAR EMPRESA
// ---------------------------
const eliminarEmpresa = async (empresa) => {
    if (empresa.usuarios_count > 0) {
        alert(`No se puede eliminar la empresa "${empresa.nombre}" porque tiene usuarios.`);
        return;
    }

    if (!confirm(`¿Seguro que deseas eliminar "${empresa.nombre}"?`)) return;

    try {
        const response = await axios.delete(`/api/empresas/${empresa.id}`);

        if (response.data.ok) {
            empresas.value = empresas.value.filter(e => e.id !== empresa.id);

            if (empresasFiltradasPaginadas.value.length === 0 && paginaActual.value > 1) {
                paginaActual.value--;
            }

            alert(response.data.msg || 'Empresa eliminada correctamente');
        }

    } catch (err) {
        console.error("Error al eliminar:", err);
        alert("No se pudo eliminar la empresa");
    }
};
</script>


<style scoped>
button:disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
}
</style>