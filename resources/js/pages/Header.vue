<template>
  <header class="bg-gray-700 text-white flex items-center justify-between px-4 py-2 shadow-md z-20">
    <div class="flex items-center space-x-6">

      <!-- Logo -->
      <div class="flex items-center space-x-2">
        <img src="https://progps.com.do/wp-content/uploads/2024/12/shipped.png" alt="Logo" class="h-6" />
        <span class="text-lg font-semibold">Copiloto</span>
      </div>

      <!-- Navegación -->
      <nav class="flex items-center space-x-5 text-sm">
        <Link
          v-for="link in links"
          :key="link.href"
          :href="link.href"
          prefetch
          preserve-scroll
          class="flex items-center space-x-1 px-3 py-2 rounded transition cursor-pointer"
          :class="isActive(link.href) ? 'bg-gray-600' : 'hover:text-blue-400'"
        >
          <i :class="link.icon"></i>
          <span>{{ link.text }}</span>
        </Link>
      </nav>
    </div>

    <!-- Usuario -->
    <div class="flex items-center space-x-4 relative">
      <span class="text-sm text-gray-300">{{ nombreUsuario }}</span>

      <button @click="toggleMenuUsuario" class="focus:outline-none">
        <i class="fas fa-ellipsis-v"></i>
      </button>

      <transition name="fade">
        <ul
          v-if="menuUsuarioAbierto"
          class="absolute right-0 top-10 bg-white text-gray-800 rounded-lg shadow-lg w-40 z-50"
        >
          <li @click="cerrarSesion" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
            Cerrar sesión
          </li>
        </ul>
      </transition>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();
const usuarioObj = page.props.auth?.user?.['App\\Models\\Usuario'] || {};
const nombreUsuario = ref(usuarioObj.nombre || 'Usuario');


const menuUsuarioAbierto = ref(false);

function toggleMenuUsuario() {
  menuUsuarioAbierto.value = !menuUsuarioAbierto.value;
}

const links = [
  { href: '/dashboard', text: 'Dashboard', icon: 'fas fa-chart-line' },
  { href: '/segmentos', text: 'Segmentos', icon: 'fas fa-globe' },
  { href: '/rutas', text: 'Rutas', icon: 'fas fa-route' },
  { href: '/asignar', text: 'Asignar rutas', icon: 'fas fa-user-check' },
  { href: '/usuarios', text: 'Usuarios', icon: 'fas fa-users' },
  { href: '/empresa', text: 'Empresa', icon: 'fas fa-building' },
];

// Ruta activa
const isActive = (href) => page.url === href || page.url.startsWith(href + '/');

// Cerrar sesión
async function cerrarSesion() {
  try {
    await router.post('/logout');
    router.visit('/login', { replace: true });
    localStorage.clear();
    sessionStorage.clear();
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
    alert('No se pudo cerrar sesión correctamente.');
  }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
