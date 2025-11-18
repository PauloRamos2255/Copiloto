<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 bg-black/50 flex justify-center items-center p-4 z-50">

      <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-blue-600 flex items-center">
          <i class="fas fa-route mr-2"></i> Detalle de ruta
        </h2>

        <!-- Loader -->
        <div v-if="cargandoRuta" class="space-y-3 animate-pulse">
          <div class="h-5 w-40 bg-gray-200 rounded"></div>
          <div class="h-5 w-32 bg-gray-200 rounded"></div>
          <div class="h-5 w-28 bg-gray-200 rounded"></div>
          <div class="h-5 w-24 bg-gray-200 rounded"></div>
          <div class="h-20 w-20 bg-gray-200 rounded-xl"></div>
        </div>

        <!-- Datos reales -->
        <div v-else class="space-y-2">
          <p><strong>Código:</strong> {{ ruta.codruta }}</p>
          <p><strong>Nombre:</strong> {{ ruta.nombre }}</p>
          <p><strong>Límite:</strong> {{ ruta.limiteGeneral }}</p>
          <p><strong>Tipo:</strong> {{ ruta.tipo }}</p>
          <p><strong>Segmentos:</strong> {{ ruta.cantidadSegmentos }}</p>

          <div v-if="ruta.icono" class="mt-3">
            <img :src="baseUrl + ruta.icono" alt="icono" class="w-20 h-20 object-contain">
          </div>
        </div>

        <button @click="$emit('close')"
          class="mt-6 bg-blue-600 text-white w-full py-2 rounded-xl hover:bg-blue-700 transition">
          Cerrar
        </button>

      </div>

    </div>
  </transition>
</template>

<script setup>
defineProps({
  visible: Boolean,
  ruta: Object,
  cargandoRuta: Boolean
});

const baseUrl = "http://localhost:8000/storage/";
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity .3s;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
