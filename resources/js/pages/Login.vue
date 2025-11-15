<template>
  <div class="login-wrapper">

    <!-- Fondo dinámico con gradientes animados -->
    <div class="dynamic-background">
      <div class="orb orb-1"></div>
      <div class="orb orb-2"></div>
      <div class="orb orb-3"></div>
      <div class="pattern-overlay"></div>
    </div>

    <!-- Brisas suaves animadas -->
    <div v-for="n in 5" :key="n" class="breeze" :style="{ animationDelay: `${n * 0.8}s` }"></div>

    <!-- Loader -->
    <div v-if="loading" class="loader-overlay">
      <div class="loader"></div>
    </div>

    <!-- Card -->
    <div class="login-card">
      <img src="https://progps.com.do/wp-content/uploads/2024/12/shipped.png" class="logo" alt="Logo">

      <h2 class="title">Acceso al Sistema</h2>
      <p class="subtitle">
        Inicia sesión para gestionar conductores, rutas y asignaciones activas.
      </p>
      
      <!-- Mensajes flash reactivos -->
      <transition name="fade">
        <div v-if="flash.error" class="msg-error">{{ flash.error }}</div>
      </transition>
      <transition name="fade">
        <div v-if="flash.success" class="msg-success">{{ flash.success }}</div>
      </transition>

      <!-- Formulario -->
      <form @submit.prevent="login" class="form-container">

        <div class="input-group">
          <label>Usuario</label>
          <input v-model="form.nombre" type="text" placeholder="Ingresa tu usuario" required />
        </div>

        <div class="input-group">
          <label>Clave</label>
          <div class="input-icon">
            <input :type="showPass ? 'text' : 'password'" v-model="form.clave" placeholder="Ingresa tu clave"
              required />
            <span class="icon-btn" @click="showPass = !showPass">
              <svg v-if="!showPass" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                stroke="#0ea5e9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.8 21.8 0 0 1 5.06-5.94" />
                <path d="M1 1l22 22" />
              </svg>
            </span>
          </div>
        </div>

        <button type="submit" class="login-btn">Ingresar</button>

      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'

export default {
  setup() {
    const page = usePage()

    // Flash reactivo
    const flash = reactive({ ...page.props.flash })

    // Actualizar flash si cambia en los props de Inertia
    watch(() => page.props.flash, (newFlash) => {
      Object.assign(flash, newFlash)
      // Ocultar mensajes automáticamente después de 3s
      if (newFlash.success) setTimeout(() => flash.success = null, 3000)
      if (newFlash.error) setTimeout(() => flash.error = null, 3000)
    })

    const form = useForm({
      nombre: '',
      clave: ''
    })

    const showPass = ref(false)
    const loading = ref(false)

    const login = () => {
      loading.value = true
      form.post('/acceso', {
        onFinish: () => {
          loading.value = false
        },
        onError: () => {
          // Si hay error, actualizar flash manualmente
          flash.error = page.props.flash.error || 'Error al iniciar sesión'
          setTimeout(() => flash.error = null, 3000)
        }
      })
    }

    return { form, showPass, loading, login, flash }
  }
}
</script>


<style scoped>
/* Fondo blanco */
.login-wrapper {
  width: 100%;
  height: 100vh;
  background: white;
  overflow: hidden;
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
}

/* Fondo dinámico con gradientes animados */
.dynamic-background {
  position: absolute;
  inset: 0;
  overflow: hidden;
  z-index: 1;
}

/* Orbes de gradiente animados */
.orb {
  position: absolute;
  border-radius: 50%;
  mix-blend-mode: multiply;
  filter: blur(80px);
  opacity: 0.3;
  animation: blob 7s infinite;
}

.orb-1 {
  width: 384px;
  height: 384px;
  background: linear-gradient(135deg, #93c5fd, #a5f3fc);
  top: 80px;
  left: 40px;
  animation-delay: 0s;
}

.orb-2 {
  width: 384px;
  height: 384px;
  background: linear-gradient(135deg, #7dd3fc, #bfdbfe);
  top: 160px;
  right: 40px;
  animation-delay: 2s;
}

.orb-3 {
  width: 384px;
  height: 384px;
  background: linear-gradient(135deg, #a5f3fc, #93c5fd);
  bottom: -32px;
  left: 33.333%;
  animation-delay: 4s;
}

/* Patrón sutil de fondo */
.pattern-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, transparent, rgba(219, 234, 254, 0.2), transparent);
  pointer-events: none;
}

/* Animación de blob */
@keyframes blob {

  0%,
  100% {
    transform: translate(0, 0) scale(1);
  }

  33% {
    transform: translate(30px, -50px) scale(1.1);
  }

  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
}

/* Brisas suaves animadas */
.breeze {
  position: absolute;
  width: 100%;
  height: 2px;
  background: linear-gradient(90deg, transparent, rgba(14, 165, 233, 0.15), transparent);
  animation: breeze-flow 6s ease-in-out infinite;
  z-index: 2;
}

.breeze:nth-child(1) {
  top: 20%;
  animation-delay: 0s;
}

.breeze:nth-child(2) {
  top: 35%;
  animation-delay: 0.8s;
}

.breeze:nth-child(3) {
  top: 50%;
  animation-delay: 1.6s;
}

.breeze:nth-child(4) {
  top: 65%;
  animation-delay: 2.4s;
}

.breeze:nth-child(5) {
  top: 80%;
  animation-delay: 3.2s;
}

@keyframes breeze-flow {
  0% {
    transform: translateX(-100%) scaleX(0);
    opacity: 0;
  }

  50% {
    opacity: 0.5;
  }

  100% {
    transform: translateX(100%) scaleX(1);
    opacity: 0;
  }
}

/* CARD celeste suave */
.login-card {
  width: 380px;
  padding: 35px 40px;
  background: #FFFFFF;
  border-radius: 18px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  animation: fadeIn 0.8s ease;
  z-index: 10;
  border: 2px solid #bae6fd;
  backdrop-filter: blur(10px);
  position: relative;
}

/* Logo */
.logo {
  width: 110px;
  margin: 0 auto 10px;
  display: block;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(25px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.title {
  text-align: center;
  color: #0369a1;
  font-size: 26px;
  font-weight: 700;
  margin: 0 0 10px 0;
}

.subtitle {
  text-align: center;
  color: #0c4a6e;
  margin-bottom: 20px;
  font-size: 15px;
}

/* Mensajes */
.msg-error {
  background: #fee2e2;
  color: #b91c1c;
  padding: 10px;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 12px;
  font-size: 14px;
}

.msg-success {
  background: #dcfce7;
  color: #166534;
  padding: 10px;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 12px;
  font-size: 14px;
}

/* Formulario */
.form-container {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

/* Inputs */
.input-group {
  margin-bottom: 0;
}

.input-group label {
  font-size: 14px;
  color: #0369a1;
  font-weight: 600;
  display: block;
  margin-bottom: 8px;
}

.input-group input {
  width: 100%;
  padding: 12px;
  border-radius: 10px;
  border: 2px solid #bae6fd;
  transition: all 0.25s ease;
  font-size: 14px;
}

.input-group input:focus {
  border-color: #0ea5e9;
  box-shadow: 0 0 6px rgba(14, 165, 233, 0.4);
  outline: none;
}

/* Icono dentro del input */
.input-icon {
  position: relative;
}

.icon-btn {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  cursor: pointer;
  transition: color 0.25s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-btn:hover {
  color: #0369a1;
}

/* Botón celeste elegante */
.login-btn {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #0284c7, #0ea5e9);
  color: white;
  font-size: 17px;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.25s ease;
  margin-top: 10px;
}

.login-btn:hover {
  background: linear-gradient(135deg, #0369a1, #0284c7);
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(14, 165, 233, 0.3);
}

.login-btn:active {
  transform: translateY(0);
}

/* Loader */
.loader-overlay {
  position: absolute;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 50;
}

.loader {
  width: 60px;
  height: 60px;
  border: 6px solid #bae6fd;
  border-top-color: #0284c7;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.6s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>