<template>
  <Loader v-if="loading" />
  <div v-else class="min-h-screen bg-gray-100 font-sans">

    <!-- Header -->
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-4 md:p-6">

      <!-- Título y botón crear usuario -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
        <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold bg-clip-text text-transparent 
           bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
          <i class="fas fa-users mr-2"></i> Gestión de Usuarios
        </h1>

        <button @click="abrirModalUsuario()" class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500 
                 hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl 
                 shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
          <i class="fas fa-plus"></i>
          <span>Crear Usuario</span>
        </button>
      </div>

      <!-- Filtro -->
      <!-- Filtros rediseñados -->
      <div class="bg-white/70 backdrop-blur-md border border-gray-200 shadow-xl rounded-2xl p-5 mb-6 
         max-w-4xl mx-auto transition-all">

        <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center">
          <i class="fas fa-filter mr-2"></i> Filtros de búsqueda
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

          <!-- Buscar por nombre -->
          <div class="relative flex items-center">
            <i class="fas fa-search absolute left-3 text-blue-300"></i>
            <input type="text" v-model="filtroNombre" placeholder="Buscar nombre..." class="w-full pl-10 pr-4 h-12 border border-blue-300 rounded-xl 
               focus:border-blue-400 focus:ring-4 focus:ring-blue-100 
               shadow-md hover:shadow-lg transition-all" />
          </div>

          <!-- Filtro por empresa -->
          <div class="relative flex items-center">
            <i class="fas fa-building absolute left-3 text-blue-300"></i>
            <select v-model="filtroEmpresa" class="w-full pl-10 pr-4 h-12 border border-blue-300 rounded-xl bg-white
               focus:border-blue-400 focus:ring-4 focus:ring-blue-100 
               shadow-md hover:shadow-lg transition-all">
              <option value="">Todas las empresas</option>
              <option v-for="emp in empresas" :key="emp.id" :value="emp.id">
                {{ emp.nombre }}
              </option>
            </select>
          </div>

          <!-- Filtro por tipo -->
          <div class="relative flex items-center">
            <i class="fas fa-user-tag absolute left-3 text-blue-300"></i>
            <select v-model="filtroTipo" class="w-full pl-10 pr-4 h-12 border border-blue-300 rounded-xl bg-white
               focus:border-blue-400 focus:ring-4 focus:ring-blue-100
               shadow-md hover:shadow-lg transition-all">
              <option value="">Todos los tipos</option>
              <option value="A">Administrador</option>
              <option value="U">Usuario</option>
              <option value="C">Conductor</option>
            </select>
          </div>

        </div>
      </div>


      <!-- Tabla -->
      <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-800">
            <thead
              class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 uppercase text-xs font-semibold tracking-wide">
              <tr>
                <th class="px-6 py-4 text-left">#</th>
                <th class="px-6 py-4 text-left">Nombre</th>
                <th class="px-6 py-4 text-left">Tipo</th>
                <th class="px-6 py-4 text-left">Empresa</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>

            <tbody v-if="cargando">
              <tr v-for="n in 5" :key="'empresa-skel-' + n" class="border-b border-gray-100 animate-pulse">
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

                <td class="px-6 py-4 text-center flex justify-center space-x-2">
                  <div class="h-4 w-6 bg-gray-300 rounded"></div>
                  <div class="h-4 w-6 bg-gray-300 rounded"></div>
                </td>
              </tr>
            </tbody>

            <tbody v-else>
              <tr v-for="(usuario, index) in usuariosPaginados" :key="usuario.codusuario"
                class="border-b border-gray-100 hover:bg-blue-50 transition-all">
                <td class="px-6 py-4 font-semibold">
                  {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                </td>

                <td class="px-6 py-4 font-medium">{{ usuario.nombre }}</td>

                <td class="px-6 py-4 font-medium">
                  <span :class="tipoBadge(usuario.tipo)">
                    {{ mostrarTipo(usuario.tipo) }}
                  </span>
                </td>

                <td class="px-6 py-4 font-medium">
                  {{ usuario.empresa_nombre ?? 'Sin empresa' }}
                </td>

                <td class="px-6 py-4 text-center space-x-2">
                  <!-- Editar -->
                  <button class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                    @click="abrirModalUsuario(usuario)" title="Editar usuario">
                    <i class="fas fa-edit"></i>
                  </button>

                  <!-- Eliminar -->
                  <button class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                    @click="confirmarEliminar(usuario)" title="Eliminar usuario">
                    <i class="fas fa-trash"></i>
                  </button>

                  <!-- Cerrar sesión -->
                  <button v-if="usuario.tipo === 'C'" class="p-2 rounded-full transition-colors" :class="usuario.estado === 'I'
                    ? 'bg-red-50 text-red-600 hover:bg-red-100'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'" :disabled="usuario.estado !== 'I'" :title="usuario.estado === 'I'
      ? 'Cerrar sesión'
      : 'El usuario no tiene sesión activa'" @click="cerrarSesion(usuario)">
                    <i class="fas fa-sign-out-alt"></i>
                  </button>

                </td>

              </tr>

              <tr v-if="usuariosFiltrados.length === 0">
                <td colspan="5" class="text-center py-10 text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                  <p>{{ filtroNombre || filtroTipo ? 'No se encontraron resultados' : 'No hay usuarios' }}</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="usuariosFiltrados.length > 0"
        class="mt-4 flex flex-col md:flex-row justify-between items-center space-y-2 md:space-y-0">
        <div class="text-sm text-gray-600">
          Mostrando
          {{ Math.min((paginaActual - 1) * registrosPorPagina + 1, usuariosFiltrados.length) }}
          -
          {{ Math.min(paginaActual * registrosPorPagina, usuariosFiltrados.length) }}
          de {{ usuariosFiltrados.length }} usuarios
        </div>

        <div class="flex space-x-2">
          <button @click="cambiarPagina(paginaActual - 1)" :disabled="paginaActual === 1"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100">
            Anterior
          </button>

          <span class="px-4 py-2 font-medium text-gray-700">
            Página {{ paginaActual }} de {{ totalPaginas }}
          </span>

          <button @click="cambiarPagina(paginaActual + 1)" :disabled="paginaActual >= totalPaginas"
            class="px-4 py-2 border rounded disabled:opacity-50 hover:bg-gray-100">
            Siguiente
          </button>
        </div>
      </div>

      <!-- Modal Usuario -->
      <ModalUsuario :visible="modalVisible" :usuario="usuarioSeleccionado" @close="modalVisible = false"
        @saved="actualizarLista" />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import Header from "@/pages/Header.vue";
import Loader from "@/pages/Loader.vue";
import ModalUsuario from "@/components/ModalUsuario.vue";
import axios from "axios";
import Swal from "sweetalert2";

// Removí las funciones no utilizadas
const loading = ref(true);
const nombreUsuario = "Paulo Ramos";
const usuarios = ref([]);
const empresas = ref([]);
const cargando = ref(true);

const filtroNombre = ref("");
const filtroTipo = ref("");
const filtroEmpresa = ref("");

const paginaActual = ref(1);
const registrosPorPagina = 10;

const modalVisible = ref(false);
const usuarioSeleccionado = ref(null);

/* ----------------------------- CACHÉ CON SESSIONSTORAGE ----------------------------- */

const CACHE_KEY = "usuarios_cache";

const guardarCacheUsuarios = (lista) => {
  const payload = {
    usuarios: lista,
    timestamp: Date.now()
  };
  sessionStorage.setItem(CACHE_KEY, JSON.stringify(payload));
};

const obtenerCacheUsuarios = () => {
  const raw = sessionStorage.getItem(CACHE_KEY);
  if (!raw) return null;

  try {
    const data = JSON.parse(raw);
    // Opcional: podrías añadir una validación de tiempo de caché aquí
    return data.usuarios || null;
  } catch {
    return null;
  }
};

const cargarUsuariosConCache = async () => {
  const cache = obtenerCacheUsuarios();

  if (cache) {
    usuarios.value = cache;
    cargando.value = false;
    // Verificar sesiones de usuarios en caché
    await Promise.all(cache.map(usuario => verificarSesion(usuario)));
    return;
  }

  await cargarUsuarios();
};

/* ----------------------------- USUARIOS ----------------------------- */

const cargarUsuarios = async () => {
  try {
    const { data } = await axios.get("/api/usuarios");

    // Inicializar usuarios con estado por defecto
    usuarios.value = data.map(u => ({
      ...u,
      estado: "F", // valor por defecto
      sesion_activa: false
    }));

    cargando.value = false;

    // Verificar sesión para cada usuario
    await Promise.all(
      usuarios.value
        .filter(usuario => usuario.tipo === 'C')
        .map(usuario => verificarSesion(usuario))
    );


    guardarCacheUsuarios(usuarios.value);

  } catch (error) {
    console.error("Error al cargar usuarios:", error);
    // Opcional: mostrar mensaje de error al usuario
  } finally {
    cargando.value = false;
  }
};

const cargarEmpresas = async () => {
  try {
    const { data } = await axios.get("/api/empresas");
    empresas.value = data.empresas || [];
  } catch (error) {
    console.error("Error al cargar empresas:", error);
  }
};

/* ----------------------------- VALIDACIÓN Y MODAL ----------------------------- */

const validarAsignacionActiva = async (usuarioId) => {
  if (!usuarioId) return false; // Si es usuario nuevo

  try {
    const response = await axios.get(`/api/asignacion_activo/${usuarioId}`);
    return response.data.activo || false;
  } catch (err) {
    console.error("Error validando asignación:", err);
    return false;
  }
};

const abrirModalUsuario = async (usuario = null) => {
  // Preparar usuario para el modal
  usuarioSeleccionado.value = usuario
    ? { ...usuario }
    : {
      codusuario: null,
      nombre: "",
      tipo: "U",
      empresa_codempresa: empresas.value[0]?.codempresa || 1
    };

  // Si es usuario existente y es conductor, validar asignación
  if (usuario && usuario.tipo === "C") {
    Swal.fire({
      title: "Verificando asignaciones activas...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    try {
      const activa = await validarAsignacionActiva(usuario.codusuario);
      Swal.close();

      if (activa) {
        await Swal.fire({
          icon: "warning",
          title: "Asignación activa",
          text: "Este conductor tiene asignaciones activas y no se puede editar.",
          confirmButtonText: "Entendido"
        });
        return; // No abrir modal
      }
    } catch (error) {
      Swal.close();
      console.error("Error en validación:", error);
      // Continuar con la apertura del modal aunque falle la validación
    }
  }

  // Si pasa todas las validaciones o no es conductor, abrir modal
  modalVisible.value = true;
};

/* ----------------------------- ACTUALIZAR LISTA ----------------------------- */

const actualizarLista = (respuestaApi) => {
  const usuario = {
    ...respuestaApi.usuario,
    codusuario: respuestaApi.usuario.codusuario,
    empresa_nombre: respuestaApi.empresa_nombre || "Sin empresa",
    estado: "F", // Por defecto
    sesion_activa: false
  };

  const index = usuarios.value.findIndex(
    u => u.codusuario === usuario.codusuario
  );

  if (index !== -1) {
    usuarios.value[index] = { ...usuario };
    // Verificar estado de sesión después de actualizar
    verificarSesion(usuarios.value[index]);
  } else {
    usuarios.value.push(usuario);
  }

  // Actualizar caché
  guardarCacheUsuarios(usuarios.value);
};

/* ----------------------------- ELIMINAR ----------------------------- */

const confirmarEliminar = async (usuario) => {
  // Si no es conductor, eliminar directamente
  if (usuario.tipo !== "C") {
    return eliminarDirecto(usuario);
  }

  Swal.fire({
    title: "Verificando usuario...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  try {
    const { data: verificar } = await axios.get(
      `/api/verificar_usuario/${usuario.codusuario}`
    );

    Swal.close();

    if (verificar.success) {
      await Swal.fire({
        icon: "warning",
        title: "No se puede eliminar",
        text: "El usuario tiene rutas asignadas.",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    // Si no tiene rutas, proceder con eliminación
    eliminarDirecto(usuario);

  } catch (error) {
    Swal.close();
    console.error("Error verificando usuario:", error);
    await Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo verificar al usuario.",
    });
  }
};

const eliminarDirecto = async (usuario) => {
  const result = await Swal.fire({
    title: "¿Estás seguro?",
    text: `Se eliminará a ${usuario.nombre}`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  });

  if (!result.isConfirmed) return;

  try {
    await axios.delete(`/api/usuarios/${usuario.codusuario}`);

    // Eliminar de la lista local
    usuarios.value = usuarios.value.filter(
      u => u.codusuario !== usuario.codusuario
    );

    // Actualizar caché
    guardarCacheUsuarios(usuarios.value);

    Swal.fire({
      title: "Eliminado",
      text: "El usuario ha sido eliminado correctamente.",
      icon: "success",
      timer: 1500,
      showConfirmButton: false,
    });

  } catch (error) {
    console.error(error);
    Swal.fire({
      title: "Error",
      text: "No se pudo eliminar el usuario.",
      icon: "error",
    });
  }
};

/* ----------------------------- FILTROS ----------------------------- */

const usuariosFiltrados = computed(() => {
  const buscar = filtroNombre.value.trim().toLowerCase();
  const tipo = filtroTipo.value;
  const empresa = filtroEmpresa.value;

  return usuarios.value.filter(u => {
    const nombre = (u.nombre || "").toLowerCase();
    const empresaUsuario = String(u.empresa_codempresa || "");

    const coincideNombre = buscar === "" || nombre.includes(buscar);
    const coincideTipo = tipo === "" || u.tipo === tipo;
    const coincideEmpresa = empresa === "" || empresaUsuario === String(empresa);

    return coincideNombre && coincideTipo && coincideEmpresa;
  });
});

/* ----------------------------- PAGINACIÓN ----------------------------- */

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(usuariosFiltrados.value.length / registrosPorPagina))
);

const usuariosPaginados = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina;
  return usuariosFiltrados.value.slice(start, start + registrosPorPagina);
});

const cambiarPagina = (n) => {
  if (n < 1 || n > totalPaginas.value) return;
  paginaActual.value = n;
};

/* ----------------------------- SESIONES ----------------------------- */

const verificarSesion = async (usuario) => {
  if (!usuario?.codusuario) return;

  try {
    const { data } = await axios.get(
      `/api/actualizacion/pendiente/${usuario.codusuario}`
    );

    usuario.sesion_activa = data.sesion_activa || false;
    usuario.estado = data.estado || "F";

  } catch (error) {
    console.error("Error verificando sesión:", error);
    usuario.sesion_activa = false;
    usuario.estado = "F";
  }
};

const cerrarSesion = async (usuario) => {
  if (usuario.estado !== "I") return;

  const confirm = await Swal.fire({
    title: "Cerrar sesión",
    text: `¿Deseas cerrar la sesión de ${usuario.nombre}?`,
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, cerrar",
    cancelButtonText: "Cancelar",
  });

  if (!confirm.isConfirmed) return;

  Swal.fire({
    title: "Cerrando sesión...",
    text: "Por favor espera",
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  try {
    await axios.post(`/api/logout/${usuario.codusuario}`);

    // Actualizar estado local
    usuario.estado = "F";
    usuario.sesion_activa = false;

    // Actualizar caché
    guardarCacheUsuarios(usuarios.value);

    Swal.fire({
      icon: "success",
      title: "Sesión cerrada",
      timer: 1200,
      showConfirmButton: false,
    });

  } catch (error) {
    console.error("Error cerrando sesión:", error);

    Swal.fire({
      icon: "error",
      title: "Error",
      text: "No se pudo cerrar la sesión",
    });
  }
};


/* ----------------------------- UTILIDADES ----------------------------- */

const tipoBadge = (tipo) => {
  const clases = {
    "A": "px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold",
    "U": "px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold",
    "C": "px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold"
  };
  return clases[tipo] || "";
};

const mostrarTipo = (tipo) => {
  const tipos = {
    "A": "Administrador",
    "U": "Usuario",
    "C": "Conductor"
  };
  return tipos[tipo] || "Desconocido";
};

const obtenerNombreEmpresa = (codempresa) => {
  const empresa = empresas.value.find(e => e.codempresa === codempresa);
  return empresa ? empresa.nombre : "Sin empresa";
};

// Observador para resetear página cuando cambian los filtros
watch(usuariosFiltrados, () => {
  if (paginaActual.value > totalPaginas.value) {
    paginaActual.value = 1;
  }
});

// Observador para resetear filtros
watch([filtroNombre, filtroTipo, filtroEmpresa], () => {
  paginaActual.value = 1;
});

/* ----------------------------- MOUNT ----------------------------- */

onMounted(async () => {
  loading.value = true;

  try {
    await Promise.all([
      cargarUsuariosConCache(),
      cargarEmpresas()
    ]);
  } catch (error) {
    console.error("Error inicializando:", error);
  } finally {
    loading.value = false;
  }
});
</script>
