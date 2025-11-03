<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-md border border-gray-200">
      <div class="flex justify-center mb-6">
        <img src="https://cdn-icons-png.flaticon.com/512/2920/2920051.png" alt="Logo" class="w-16 h-16" />
      </div>

      <h2 class="text-2xl font-semibold text-center text-gray-800 mb-4">Bienvenido</h2>
      <p class="text-center text-gray-500 mb-6 text-sm">Inicia sesión para continuar</p>

      <form @submit.prevent="login" class="space-y-5">
        <div>
          <label class="block text-gray-600 mb-1 font-medium">Nombre de usuario</label>
          <input v-model="nombre" type="text" placeholder="Ej: Paulo Ramos"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:outline-none"
            required />
        </div>

        <div>
          <label class="block text-gray-600 mb-1 font-medium">Clave</label>
          <div class="relative">
            <input :type="mostrarPassword ? 'text' : 'password'" v-model="clave" placeholder="••••••••"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:outline-none"
              required />
            <button type="button" @click="mostrarPassword = !mostrarPassword"
              class="absolute right-3 top-2 text-gray-500 hover:text-gray-700">
              <span v-if="mostrarPassword">🙈</span>
              <span v-else>👁️</span>
            </button>
          </div>
        </div>

        <button type="submit"
          class="w-full bg-indigo-500 text-white py-2 rounded-lg font-semibold hover:bg-indigo-600 transition-colors duration-200 shadow-sm"
          :disabled="cargando">
          {{ cargando ? 'Ingresando...' : 'Ingresar' }}
        </button>

        <transition name="fade">
          <p v-if="error" class="text-red-500 text-center font-medium mt-2 bg-red-50 py-2 rounded-lg">
            {{ error }}
          </p>
        </transition>
      </form>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      nombre: "",
      clave: "",
      error: null,
      mostrarPassword: false,
      cargando: false,
      mensajeFallback: "Algo salió mal. Inténtalo nuevamente.",
    };
  },

  methods: {
    async login() {
      this.error = null;
      this.cargando = true;

      try {
        await axios.post("api/acceso", {
          nombre: this.nombre,
          clave: this.clave,
        });
        // No redirigimos aquí. El backend lo hará automáticamente.
      } catch (err) {
        this.error = err.response?.data?.message || this.mensajeFallback;
      } finally {
        this.cargando = false;
      }
    },
  },
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
