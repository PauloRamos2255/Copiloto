<template>
  <transition name="zoom-fade">
    <div v-if="visible" class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">

      <div class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden">

        <div class="flex justify-between items-center px-6 py-6 bg-blue-600">
          <h2 class="text-3xl font-bold text-white">
            {{ form.codusuario ? 'Editar Usuario / Conductor' : 'Registrar Usuario / Conductor' }}
          </h2>
          <button @click="emitClose" class="text-white hover:text-gray-300 text-3xl">&times;</button>
        </div>

        <div class="px-6 py-6 space-y-6">

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Empresa vinculada</label>
              <select v-model="form.empresa_codempresa"
                :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
                errores.empresa ? 'border-red-500' : 'border-gray-300']">
                <option value="">Seleccione empresa</option>
                <option v-for="e in empresas" :key="e.id" :value="e.id">
                  {{ e.nombre }}
                </option>
              </select>
              <p v-if="errores.empresa" class="text-red-600 text-xs mt-1">{{ errores.empresa }}</p>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Tipo</label>
              <select v-model="form.tipo" @change="reordenarConductor"
                :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
                errores.tipo ? 'border-red-500' : 'border-gray-300']">
                <option value="">Seleccione tipo</option>
                <option value="A">Administrador</option>
                <option value="U">Usuario</option>
                <option value="C">Conductor</option>
              </select>
              <p v-if="errores.tipo" class="text-red-600 text-xs mt-1">{{ errores.tipo }}</p>
            </div>

          </div>

          <!-- Nombre para usuario/administrador -->
          <div v-if="form.tipo !== 'C'">
            <label class="block text-gray-700 font-semibold mb-1">Nombre</label>
            <input v-model="form.nombre" type="text"
              :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
              errores.nombre ? 'border-red-500' : 'border-gray-300']"
              placeholder="Nombre del usuario" />
            <p v-if="errores.nombre" class="text-red-600 text-xs mt-1">{{ errores.nombre }}</p>
          </div>

          <!-- Campos para conductor -->
          <div v-if="form.tipo === 'C'" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Placa del vehículo</label>
              <div class="relative">
                <i class="fas fa-car absolute left-3 top-3 text-gray-500"></i>
                <input v-model="form.placa" maxlength="7" @input="enFormatoPlaca" @blur="validarPlacaDuplicada"
                  :class="['w-full border rounded-xl pl-10 px-4 py-2 focus:ring-2 focus:ring-blue-400',
                  errores.placa ? 'border-red-500' : 'border-gray-300']"
                  placeholder="A1A-000" />
              </div>
              <p v-if="errores.placa" class="text-red-600 text-xs mt-1">{{ errores.placa }}</p>
            </div>

            <div>
              <label class="block text-gray-700 font-semibold mb-1">Clave</label>
              <div class="relative">
                <input :type="showClave ? 'text' : 'password'" v-model="form.clave" @input="evaluarFuerzaClave"
                  :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
                  errores.clave ? 'border-red-500' : 'border-gray-300']"
                  placeholder="Clave del conductor" />
                <i @click="showClave = !showClave" :class="showClave ? 'fas fa-eye-slash' : 'fas fa-eye'"
                  class="absolute right-3 top-2.5 text-gray-500 cursor-pointer text-sm"></i>
              </div>

              <p v-if="errores.clave" class="text-red-600 text-xs mt-1">{{ errores.clave }}</p>

              <div class="h-1 mt-1 w-full rounded bg-gray-300">
                <div :class="fuerzaClase" :style="{ width: fuerzaPorcentaje + '%', height: '3px' }"
                  class="rounded transition-all duration-300"></div>
              </div>
              <p class="text-[0.65rem] mt-0.5 text-gray-600">{{ fuerzaTexto }}</p>
            </div>

            <div class="md:col-span-2">
              <label class="block text-gray-700 font-semibold mb-1">Datos del conductor</label>
              <textarea ref="datosTextArea" v-model="form.datos_conductor" @input="ajustarAltura"
                :style="{ height: alturaTextArea + 'px', maxHeight: maxAltura + 'px' }"
                :class="['w-full border rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400',
                errores.datos_conductor ? 'border-red-500' : 'border-gray-300']"
                placeholder="Información adicional"></textarea>
              <p v-if="errores.datos_conductor" class="text-red-600 text-xs mt-1">{{ errores.datos_conductor }}</p>
            </div>

          </div>

          <!-- Clave para usuario/administrador -->
          <div v-if="form.tipo !== 'C'">
            <label class="block text-gray-700 font-semibold mb-1">Clave</label>
            <div class="relative">
              <input :type="showClave2 ? 'text' : 'password'" v-model="form.clave" @input="evaluarFuerzaClave"
                :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
                errores.clave ? 'border-red-500' : 'border-gray-300']"
                placeholder="Ingrese la clave" />
              <i @click="showClave2 = !showClave2" :class="showClave2 ? 'fas fa-eye-slash' : 'fas fa-eye'"
                class="absolute right-3 top-3 text-gray-500 cursor-pointer"></i>
            </div>

            <p v-if="errores.clave" class="text-red-600 text-xs mt-1">{{ errores.clave }}</p>

            <div class="h-1 mt-2 w-full rounded bg-gray-300">
              <div :class="fuerzaClase" :style="{ width: fuerzaPorcentaje + '%' }"
                class="h-1 rounded transition-all duration-300"></div>
            </div>
            <p class="text-sm mt-1 text-gray-600">{{ fuerzaTexto }}</p>
          </div>

        </div>

        <div class="bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
          <button type="button" @click="emitClose" class="px-6 py-2 border rounded-xl hover:bg-gray-200 font-medium">
            Cancelar
          </button>

          <button type="button" @click="guardarUsuario"
            class="px-6 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 font-medium shadow-md">
            Guardar
          </button>
        </div>

      </div>

    </div>
  </transition>
</template>

<script setup>
import { reactive, ref, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({ visible: Boolean, usuario: Object });
const emit = defineEmits(['close', 'saved']);
const emitClose = () => emit('close');

// Datos
const empresas = ref([]);
const errores = reactive({
  empresa: '',
  tipo: '',
  nombre: '',
  placa: '',
  clave: '',
  datos_conductor: ''
});

// Fuerza de clave
const showClave = ref(false);
const showClave2 = ref(false);
const fuerzaTexto = ref('');
const fuerzaPorcentaje = ref(0);
const fuerzaClase = ref('bg-red-500');

// Altura textarea
const baseAltura = 100;
const alturaTextArea = ref(baseAltura);
const maxAltura = baseAltura * 1.15;

// Formulario
const form = reactive({
  codusuario: null,
  empresa_codempresa: '',
  nombre: '',
  placa: '',
  clave: '',
  tipo: '',
  identificador: '',
  datos_conductor: ''
});

// Cargar empresas
onMounted(async () => {
  try {
    const { data } = await axios.get("http://localhost:8000/api/empresas");
    if (data.ok) empresas.value = data.empresas;
  } catch (err) {
    console.error("Error cargando empresas:", err);
  }
});

// Cargar usuario a editar
watch(() => props.usuario, (u) => {
  if (!u) return;

  Object.assign(form, {
    codusuario: u.codusuario || null,
    empresa_codempresa: u.empresa_codempresa || '',
    nombre: u.nombre || '',
    placa: '',
    clave: '',
    tipo: u.tipo || '',
    identificador: u.identificador || '',
    datos_conductor: ''
  });

  nextTick(ajustarAltura);
  evaluarFuerzaClave();
}, { immediate: true });

// Formato de placa
const enFormatoPlaca = () => {
  let v = form.placa.toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (v.length > 3) v = v.slice(0, 3) + '-' + v.slice(3);
  form.placa = v.slice(0, 7);
};

// Validar duplicado
const validarPlacaDuplicada = async () => {
  if (!form.placa) return;
  try {
    const { data } = await axios.get(`http://localhost:8000/api/usuarios/placa/${form.placa}`);
    if (data.existe && data.id !== form.codusuario) {
      errores.placa = "Esta placa ya está registrada.";
    } else {
      errores.placa = "";
    }
  } catch (_) { }
};

// Limpiar datos al cambiar tipo
const reordenarConductor = () => {
  errores.placa = '';
  form.nombre = '';
};

// Evaluar fuerza de clave
const evaluarFuerzaClave = () => {
  const clave = form.clave || '';
  let score = 0;
  if (clave.length >= 6) score++;
  if (/[A-Z]/.test(clave)) score++;
  if (/[0-9]/.test(clave)) score++;
  if (/[^A-Za-z0-9]/.test(clave)) score++;

  switch (score) {
    case 1:
      fuerzaTexto.value = "Débil";
      fuerzaClase.value = "bg-red-500";
      fuerzaPorcentaje.value = 25;
      break;
    case 2:
      fuerzaTexto.value = "Normal";
      fuerzaClase.value = "bg-yellow-400";
      fuerzaPorcentaje.value = 50;
      break;
    case 3:
      fuerzaTexto.value = "Fuerte";
      fuerzaClase.value = "bg-green-400";
      fuerzaPorcentaje.value = 75;
      break;
    case 4:
      fuerzaTexto.value = "Muy fuerte";
      fuerzaClase.value = "bg-green-600";
      fuerzaPorcentaje.value = 100;
      break;
    default:
      fuerzaTexto.value = "";
      fuerzaClase.value = "bg-red-500";
      fuerzaPorcentaje.value = 0;
  }
};

// Ajustar altura de textarea
const ajustarAltura = () => {
  nextTick(() => {
    const ta = document.querySelector('textarea');
    if (!ta) return;
    ta.style.height = 'auto';
    alturaTextArea.value = Math.min(ta.scrollHeight, maxAltura);
  });
};

// Validar campos
const validarCampos = () => {
  errores.empresa = '';
  errores.tipo = '';
  errores.nombre = '';
  errores.placa = '';
  errores.clave = '';
  errores.datos_conductor = '';

  let valido = true;

  if (!form.empresa_codempresa) {
    errores.empresa = "Seleccione una empresa.";
    valido = false;
  }

  if (!form.tipo) {
    errores.tipo = "Seleccione un tipo.";
    valido = false;
  }

  if (form.tipo === "C") {
    if (!form.placa || form.placa.length < 6) {
      errores.placa = "Ingrese una placa válida (mínimo 6 caracteres).";
      valido = false;
    }

    if (!form.clave || form.clave.length < 6) {
      errores.clave = "La clave debe tener mínimo 6 caracteres.";
      valido = false;
    }

    if (!form.datos_conductor || form.datos_conductor.trim().length < 3) {
      errores.datos_conductor = "Ingrese datos del conductor.";
      valido = false;
    }
  } else { // Usuario/Admin
    if (!form.nombre || form.nombre.trim().length < 3) {
      errores.nombre = "El nombre es obligatorio y debe tener mínimo 3 caracteres.";
      valido = false;
    }

    if (!form.clave || form.clave.length < 6) {
      errores.clave = "La clave debe tener mínimo 6 caracteres.";
      valido = false;
    }
  }

  return valido;
};

// Generar identificador
const generarIdentificador = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  let result = '';
  for (let i = 0; i < 30; i++) {
    result += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  return result;
};

// Guardar usuario
const guardarUsuario = async () => {
  if (!validarCampos()) return;

  if (form.tipo !== 'C' && !form.identificador) {
    form.identificador = generarIdentificador();
  }

  const payload = {
    empresa_codempresa: form.empresa_codempresa,
    nombre: form.tipo === 'C' ? form.placa : form.nombre,
    tipo: form.tipo,
    clave: form.clave ,
    identificador:form.tipo === 'C' ?form.datos_conductor : form.identificador,
  };

  console.log(payload)
  const url = form.codusuario
    ? `http://localhost:8000/api/usuarios/${form.codusuario}`
    : `http://localhost:8000/api/usuarios`;

  const method = form.codusuario ? "put" : "post";

  try {
    const { data } = await axios({ method, url, data: payload });
    emit('saved', data);
    emitClose();
  } catch (err) {
    console.error("Error guardando usuario:", err);
  }
};
</script>


<style>
.zoom-fade-enter-active {
  animation: zoomFade .25s ease-out;
}

@keyframes zoomFade {
  from {
    opacity: 0;
    transform: scale(.85);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
