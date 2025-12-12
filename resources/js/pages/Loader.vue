<template>
  <div class="loader-overlay">
    <div class="loader-content">

      <!-- Indicador de barras animadas -->
      <div class="loader-bars">
        <span v-for="n in 5" :key="n"></span>
      </div>

      <!-- Mensajes dinámicos -->
      <transition name="fade-slide">
        <p class="loader-message" :key="activeMessage">{{ activeMessage }}</p>
      </transition>

    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";

const messages = [
  "Preparando datos...",
  "Optimizando recursos...",
  "Sincronizando con el servidor...",
  "Cargando interfaz...",
  "Casi listo..."
];

const activeMessage = ref(messages[0]);

onMounted(() => {
  let index = 0;
  setInterval(() => {
    index = (index + 1) % messages.length;
    activeMessage.value = messages[index];
  }, 2500);
});
</script>

<style scoped>
.loader-overlay {
  position: fixed;
  inset: 0;
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(6px);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.loader-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 20px;
}

/* BARRAS ANIMADAS */
.loader-bars {
  display: flex;
  gap: 10px;
  align-items: flex-end;
}

.loader-bars span {
  display: block;
  width: 14px;
  height: 40px;
  background: linear-gradient(180deg, #3b82f6, #60a5fa);
  border-radius: 6px;
  box-shadow: 0 0 12px rgba(59, 130, 246, 0.4);
  animation: pulse 1.2s ease-in-out infinite;
  transform-origin: bottom center;
}

.loader-bars span:nth-child(1) { animation-delay: 0s; }
.loader-bars span:nth-child(2) { animation-delay: 0.15s; }
.loader-bars span:nth-child(3) { animation-delay: 0.3s; }
.loader-bars span:nth-child(4) { animation-delay: 0.45s; }
.loader-bars span:nth-child(5) { animation-delay: 0.6s; }

@keyframes pulse {
  0%, 100% { transform: scaleY(0.5); opacity: 0.6; }
  50% { transform: scaleY(1.2); opacity: 1; }
}

/* MENSAJES */
.loader-message {
  font-size: 1.15rem;
  font-weight: 600;
  color: #1e3a8a;
  letter-spacing: 0.5px;
  text-align: center;
}

/* TRANSICIÓN MENSAJES */
.fade-slide-enter-from {
  opacity: 0;
  transform: translateY(10px);
}
.fade-slide-enter-to {
  opacity: 1;
  transform: translateY(0);
}
.fade-slide-enter-active {
  transition: all 0.6s ease;
}
.fade-slide-leave-active {
  opacity: 0;
  transition: opacity 0.4s ease;
}
</style>
