<template>
  <div class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-md border border-gray-200">
      <div class="flex justify-center mb-6">
        <img
          src="https://cdn-icons-png.flaticon.com/512/2920/2920051.png"
          alt="Logo"
          class="w-16 h-16"
        />
      </div>

      <h2 class="text-2xl font-semibold text-center text-gray-800 mb-4">Bienvenido</h2>
      <p class="text-center text-gray-500 mb-6 text-sm">Inicia sesión para continuar</p>

      <form @submit.prevent="login" class="space-y-5">
        <!-- Usuario -->
        <div>
          <label class="block text-gray-600 mb-1 font-medium">Nombre de usuario</label>
          <input
            v-model="form.nombre"
            type="text"
            placeholder="Ej: Paulo Ramos"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:outline-none"
            required
          />
          <transition name="fade">
            <p v-if="form.errors.nombre" class="text-red-500 text-sm mt-1">
              {{ form.errors.nombre }}
            </p>
          </transition>
        </div>

        <!-- Clave -->
        <div>
          <label class="block text-gray-600 mb-1 font-medium">Clave</label>
          <div class="relative">
            <input
              :type="mostrarPassword ? 'text' : 'password'"
              v-model="form.clave"
              placeholder="••••••••"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:outline-none"
              required
            />
            <button
              type="button"
              @click="mostrarPassword = !mostrarPassword"
              class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
            >
              <span v-if="mostrarPassword">🙈</span>
              <span v-else>👁</span>
            </button>
          </div>
          <transition name="fade">
            <p v-if="form.errors.clave" class="text-red-500 text-sm mt-1">
              {{ form.errors.clave }}
            </p>
          </transition>
        </div>

        <!-- Botón -->
        <button
          type="submit"
          class="w-full bg-indigo-500 text-white py-2 rounded-lg font-semibold hover:bg-indigo-600 transition-colors duration-200 shadow-sm"
          :disabled="form.processing"
        >
          {{ form.processing ? 'Ingresando...' : 'Ingresar' }}
        </button>
      </form>

      <!-- Error global (credenciales incorrectas) -->
      <transition name="fade">
        <p
          v-if="form.errors.nombre && !form.errors.nombre.includes('required')"
          class="text-red-500 text-center font-medium mt-4 bg-red-50 py-2 rounded-lg"
        >
          {{ form.errors.nombre }}
        </p>
      </transition>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";

const mostrarPassword = ref(false);

// useForm maneja errores de validación
const form = useForm({
  nombre: "",
  clave: "",
});

const login = () => {
  form.post("/acceso", {
    onSuccess: () => {
      console.log("✅ Sesión iniciada correctamente");
    },
    onError: () => {
      // useForm ya llena form.errors automáticamente
    },
  });
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
