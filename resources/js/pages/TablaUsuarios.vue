<template>
  <div class="min-h-screen w-full bg-gray-50 flex flex-col">
    <header class="bg-white shadow-sm p-4 sticky top-0 z-10">
      <h1 class="text-2xl font-bold text-gray-800 text-center">Usuarios Registrados</h1>
    </header>

    <div
      v-if="mensaje"
      :class="[ 
        'fixed top-16 right-4 p-3 rounded-lg shadow-md text-white font-medium transition-all text-sm z-20',
        tipoMensaje === 'exito' ? 'bg-green-400' : 'bg-red-400'
      ]"
    >
      {{ mensaje }}
    </div>

    <main class="flex-1 overflow-auto p-4">
     <table class="w-full border border-gray-200 bg-white rounded-lg table-auto">
  <thead class="bg-blue-100 text-gray-700 text-sm sticky top-0">
    <tr>
      <th class="px-3 py-2">ID</th>
      <th class="px-3 py-2">Cod_Empresa</th>
      <th class="px-3 py-2">Nombre</th>
      <th class="px-3 py-2">Tipo</th>
      <th class="px-3 py-2">Acciones</th> <!-- columna para botones -->
    </tr>
  </thead>

  <tbody class="text-gray-700 text-sm">
    <tr
      v-for="(u, i) in usuarios"
      :key="u.codusuario"
      :class="i % 2 === 0 ? 'bg-gray-50' : 'bg-white'"
      class="hover:bg-gray-100 transition-colors duration-150"
    >
      <td class="px-3 py-2 font-semibold">{{ u.codusuario }}</td>
      <td class="px-3 py-2 max-w-[10rem] truncate" :title="u.nombre">{{ u.empresa_codempresa }}</td>
      <td class="px-3 py-2">{{ u.nombre }}</td>
      <td class="px-3 py-2">{{ u.tipo === 'A' ? 'Administrador' : 'Usuario' }}</td>
      <td class="px-3 py-2 flex gap-2">
        <button
          @click="editarUsuario(u)"
          class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded"
        >
          Editar
        </button>
        <button
          @click="eliminarUsuario(u.codusuario)"
          class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
        >
          Eliminar
        </button>
      </td>
    </tr>
  </tbody>
</table>

<div v-if="modalVisible" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
      <div class="bg-white rounded-lg shadow-lg w-96 p-6 relative">
        <h2 class="text-xl font-bold mb-4">Editar Usuario</h2>
        <form @submit.prevent="guardarCambios">
          <div class="mb-3">
            <label class="block text-gray-700 mb-1">Nombre:</label>
            <input v-model="usuarioSeleccionado.nombre" type="text" class="w-full border rounded px-3 py-2"/>
          </div>
          <div class="mb-3">
            <label class="block text-gray-700 mb-1">Código Empresa:</label>
            <input v-model="usuarioSeleccionado.empresa_codempresa" type="text" class="w-full border rounded px-3 py-2"/>
          </div>
          <div class="mb-3">
            <label class="block text-gray-700 mb-1">Tipo:</label>
            <select v-model="usuarioSeleccionado.tipo" class="w-full border rounded px-3 py-2">
              <option value="A">Administrador</option>
              <option value="U">Usuario</option>
            </select>
          </div>

          <div class="flex justify-end gap-2 mt-4">
            <button type="button" @click="cerrarModal" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancelar</button>
            <button type="submit" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600">Guardar</button>
          </div>
        </form>
        <button @click="cerrarModal" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>
      </div>
    </div>


      <div v-if="usuarios.length === 0" class="text-center text-gray-500 mt-4 text-sm">
        No hay usuarios registrados.
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";

const usuarios = ref([]);
const mensaje = ref("");
const tipoMensaje = ref("exito");

const cargarUsuarios = async () => {
  try {
    const { data } = await axios.get("http://localhost:8000/api/users");
    if (data.success && Array.isArray(data.usuarios)) {
      usuarios.value = data.usuarios;
    } else {
      mensaje.value = "No se encontraron usuarios";
      tipoMensaje.value = "error";
    }
  } catch (err) {
    mensaje.value = err.message || "Error al cargar usuarios";
    tipoMensaje.value = "error";
  }

  setTimeout(() => {
    mensaje.value = "";
    tipoMensaje.value = "";
  }, 4000);
};

onMounted(cargarUsuarios);
</script>
