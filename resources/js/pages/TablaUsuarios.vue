<template>
  <div class="min-h-screen bg-gray-100 font-sans">

    <!-- Header -->
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-4 md:p-6">

      <!-- Título y botón crear usuario -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
        <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold bg-clip-text text-transparent 
           bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
          <i class="fas fa-users mr-2"></i> Gestión de Usuarios
        </h1>

        <button 
          @click="abrirModalUsuario()"
          class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500 
                 hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl 
                 shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
          <i class="fas fa-plus"></i>
          <span>Crear Usuario</span>
        </button>
      </div>

      <!-- Filtro -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 max-w-2xl">
        <input
          type="text"
          v-model="filtroNombre"
          placeholder="Filtrar por nombre..."
          class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl focus:border-blue-600 
                 focus:ring-4 focus:ring-blue-100 shadow-lg hover:shadow-xl transition-all"
        />

        <select
          v-model="filtroTipo"
          class="w-full px-4 py-3 border-2 border-blue-300 rounded-xl bg-white focus:border-blue-600 
                 focus:ring-4 focus:ring-blue-100 shadow-lg hover:shadow-xl transition-all"
        >
          <option value="">Todos los tipos</option>
          <option value="A">Administrador</option>
          <option value="U">Usuario</option>
        </select>
      </div>

      <!-- Loading -->
      <div v-if="cargando" class="flex justify-center py-10">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Tabla -->
      <div v-else class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-800">
            <thead
              class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 uppercase text-xs font-semibold tracking-wide"
            >
              <tr>
                <th class="px-6 py-4 text-left">#</th>
                <th class="px-6 py-4 text-left">Nombre</th>
                <th class="px-6 py-4 text-left">Tipo</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <tr
                v-for="(usuario, index) in usuariosPaginados"
                :key="usuario.codusuario"
                class="border-b border-gray-100 hover:bg-blue-50 transition-all"
              >
                <td class="px-6 py-4 font-semibold">
                  {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                </td>

                <td class="px-6 py-4 font-medium">{{ usuario.nombre }}</td>

                <td class="px-6 py-4 font-medium">
                  <span :class="tipoBadge(usuario.tipo)">
                    {{ mostrarTipo(usuario.tipo) }}
                  </span>
                </td>

                <td class="px-6 py-4 text-center space-x-2">
                  <button
                    class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                    @click="abrirModalUsuario(usuario)"
                    title="Editar usuario"
                  >
                    <i class="fas fa-edit"></i>
                  </button>

                  <button
                    class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                    @click="confirmarEliminar(usuario)"
                    title="Eliminar usuario"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>

              <tr v-if="usuariosFiltrados.length === 0">
                <td colspan="4" class="text-center py-10 text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                  <p>{{ filtroNombre || filtroTipo ? 'No se encontraron resultados' : 'No hay usuarios' }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="usuariosFiltrados.length > 0" class="mt-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
        <div class="text-sm text-gray-600">
          Mostrando 
          {{ Math.min((paginaActual - 1) * registrosPorPagina + 1, usuariosFiltrados.length) }}
          -
          {{ Math.min(paginaActual * registrosPorPagina, usuariosFiltrados.length) }}
          de {{ usuariosFiltrados.length }} usuarios
        </div>

        <div class="flex space-x-2">
          <button
            @click="cambiarPagina(paginaActual - 1)"
            :disabled="paginaActual === 1"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100"
          >
            Anterior
          </button>

          <span class="px-4 py-2 font-medium text-gray-700">
            Página {{ paginaActual }} de {{ totalPaginas }}
          </span>

          <button
            @click="cambiarPagina(paginaActual + 1)"
            :disabled="paginaActual >= totalPaginas"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100"
          >
            Siguiente
          </button>
        </div>
      </div>

      <!-- Modal Usuario -->
      <ModalUsuario
        :visible="modalVisible"
        :usuario="usuarioSeleccionado"
        @close="modalVisible = false"
        @saved="actualizarLista"
      />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Header from "@/pages/Header.vue";
import ModalUsuario from "@/components/ModalUsuario.vue";
import axios from "axios";

const nombreUsuario = "Paulo Ramos";
const usuarios = ref([]);
const cargando = ref(true);

const filtroNombre = ref("");
const filtroTipo = ref("");

const paginaActual = ref(1);
const registrosPorPagina = 10;

// Modal
const modalVisible = ref(false);
const usuarioSeleccionado = ref(null);

const abrirModalUsuario = (usuario = null) => {
  usuarioSeleccionado.value = usuario
    ? { ...usuario }
    : { codusuario: null, nombre: "", tipo: "U", empresa_codempresa: 1 };

  modalVisible.value = true;
};

// Precargar usuarios
onMounted(async () => {
  try {
    const { data } = await axios.get("http://localhost:8000/api/usuarios");
    usuarios.value = data;
  } catch (error) {
    console.error("Error al cargar usuarios:", error);
    alert("No se pudieron cargar los usuarios.");
  } finally {
    cargando.value = false;
  }
});

// Guardar usuario desde modal
const actualizarLista = (usuario) => {
  const index = usuarios.value.findIndex(u => u.codusuario === usuario.codusuario);
  if (index !== -1) {
    usuarios.value[index] = { ...usuario };
  } else {
    usuarios.value.push(usuario);
  }
};

// Eliminar usuario
const confirmarEliminar = async (usuario) => {
  if (confirm(`¿Eliminar a ${usuario.nombre}?`)) {
    try {
      await axios.delete(`http://localhost:8000/api/usuarios/${usuario.codusuario}`);
      usuarios.value = usuarios.value.filter(u => u.codusuario !== usuario.codusuario);
    } catch (error) {
      console.error(error);
      alert("No se pudo eliminar el usuario.");
    }
  }
};

// Filtros seguros
const usuariosFiltrados = computed(() => {
  return usuarios.value.filter(u => {
    const nombreSeguro = (u.nombre ?? "").toLowerCase();
    const filtroSeguro = filtroNombre.value.toLowerCase();

    const matchNombre = nombreSeguro.includes(filtroSeguro);
    const matchTipo = filtroTipo.value ? u.tipo === filtroTipo.value : true;

    return matchNombre && matchTipo;
  });
});

// Paginación
const totalPaginas = computed(() =>
  Math.ceil(usuariosFiltrados.value.length / registrosPorPagina)
);

const usuariosPaginados = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina;
  return usuariosFiltrados.value.slice(start, start + registrosPorPagina);
});

const cambiarPagina = (n) => {
  if (n >= 1 && n <= totalPaginas.value) paginaActual.value = n;
};

// Estilos dinámicos
const tipoBadge = (tipo) =>
  tipo === "A"
    ? "px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold"
    : "px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold";

const mostrarTipo = (tipo) => (tipo === "A" ? "Administrador" : "Usuario");
</script>
