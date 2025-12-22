<template>
  <header class="bg-gray-700 text-white flex items-center justify-between px-4 py-2 shadow-md z-20">
    <div class="flex items-center space-x-6">
      <div class="flex items-center space-x-2">
        <img src="https://progps.com.do/wp-content/uploads/2024/12/shipped.png" alt="Logo" class="h-6" />
        <span class="text-lg font-semibold">Copiloto</span>
      </div>

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

    <div class="relative" ref="usuarioMenu">
      <button 
        @click="toggleMenuUsuario"
        class="focus:outline-none"
        :disabled="bloqueoToggle"
      >
        <span class="text-sm text-gray-300">{{ nombreUsuario }}</span>
        <i class="fas fa-ellipsis-v ml-2"></i>
      </button>

      <transition name="fade">
        <ul
          v-show="menuUsuarioAbierto"
          class="absolute right-0 top-10 bg-white text-gray-800 rounded-lg shadow-lg w-40 z-50"
        >
          <li 
            @click="cerrarSesion"
            class="px-4 py-2 hover:bg-gray-100"
            :class="bloqueado ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
          >
            Cerrar sesión
          </li>
        </ul>
      </transition>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';

const page = usePage();
const usuarioObj = page.props.auth?.user || {};

const nombreUsuario = ref(usuarioObj.nombre || 'Usuario');

const menuUsuarioAbierto = ref(false);

const bloqueoToggle = ref(false);

function toggleMenuUsuario() {
  if (bloqueoToggle.value) return;

  bloqueoToggle.value = true;
  menuUsuarioAbierto.value = !menuUsuarioAbierto.value;

  setTimeout(() => {
    bloqueoToggle.value = false;
  }, 200);
}

const usuarioMenu = ref(null);

function handleClickOutside(event) {
  if (usuarioMenu.value && !usuarioMenu.value.contains(event.target)) {
    menuUsuarioAbierto.value = false;
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));


const bloqueado = ref(false);

async function cerrarSesion() {
  if (bloqueado.value) return;

  bloqueado.value = true;
  menuUsuarioAbierto.value = false; 
  try {
    await router.post('/logout');
    router.visit('/login', { replace: true });
    localStorage.clear();
    sessionStorage.clear();
  } catch (error) {
    console.error('Error al cerrar sesión:', error);
    alert('No se pudo cerrar sesión correctamente.');
  } finally {
    bloqueado.value = false;
  }
}

// Links de navegación
const links = [
  { href: '/dashboard', text: 'Dashboard', icon: 'fas fa-chart-line' },
  { href: '/segmentos', text: 'Segmentos', icon: 'fas fa-globe' },
  { href: '/rutas', text: 'Rutas', icon: 'fas fa-route' },
  { href: '/asignar', text: 'Asignar rutas', icon: 'fas fa-user-check' },
  { href: '/usuarios', text: 'Usuarios', icon: 'fas fa-users' },
  { href: '/empresa', text: 'Empresa', icon: 'fas fa-building' },
];

// Determinar ruta activa
const isActive = (href) => page.url === href || page.url.startsWith(href + '/');
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
