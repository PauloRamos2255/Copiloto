<template>
  <header class="bg-gray-700 text-white flex items-center justify-between px-4 py-2 shadow-md z-20">
    <div class="flex items-center space-x-6">
      <div class="flex items-center space-x-2">
        <img src="/logo.png" alt="Logo" class="h-6" />
        <span class="text-lg font-semibold">Copiloto</span>
      </div>
      <nav class="flex items-center space-x-5 text-sm">
        <Link href="/dashboard" class="flex items-center space-x-1 hover:text-blue-400">
          <i class="fas fa-chart-line"></i>
          <span>Dashboard</span>
        </Link>
        <Link href="/segmentos" class="flex items-center space-x-1 hover:text-blue-400">
          <i class="fas fa-globe"></i>
          <span>Segmentos</span>
        </Link>
        <Link href="/usuarios" class="flex items-center space-x-1 hover:text-blue-400">
          <i class="fas fa-user"></i>
          <span>Usuarios</span>
        </Link>
        <Link href="/rutas" class="flex items-center space-x-1 hover:text-blue-400">
          <i class="fas fa-route"></i>
          <span>Rutas</span>
        </Link>
      </nav>
    </div>

    <div class="flex items-center space-x-4 relative">
      <span class="text-sm text-gray-300">{{ nombreUsuario }}</span>
      <button @click="toggleMenuUsuario" class="focus:outline-none">
        <i class="fas fa-ellipsis-v"></i>
      </button>
      <transition name="fade">
        <ul v-if="menuUsuarioAbierto"
            class="absolute right-0 top-10 bg-white text-gray-800 rounded-lg shadow-lg w-40 z-50">
          <li @click="cerrarSesion" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">Cerrar sesión</li>
        </ul>
      </transition>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';

const nombreUsuario = ref('Admin');
const menuUsuarioAbierto = ref(false);

function toggleMenuUsuario() {
  menuUsuarioAbierto.value = !menuUsuarioAbierto.value;
}

function cerrarSesion() {
  alert('Sesión cerrada');
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
