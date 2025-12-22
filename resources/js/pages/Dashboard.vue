<template>
  <div class="min-h-screen bg-gray-100 font-sans">

    <!-- Header -->
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-4 md:p-6">
      <div class="bg-white shadow-lg rounded-2xl p-6 border border-gray-200 text-center">
        <i class="fas fa-tachometer-alt text-6xl text-blue-400 mb-4"></i>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Dashboard en Desarrollo</h1>
        <p class="text-gray-500">Aquí se mostrarán los indicadores y estadísticas cuando esté listo.</p>
      </div>
    </main>

  </div>
</template>

<script setup>
import Header from '@/pages/Header.vue';

const nombreUsuario = 'Paulo Ramos';
</script>
