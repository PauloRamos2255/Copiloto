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

            <!-- Empresa -->
            <div>
              <label class="block text-gray-700 font-semibold mb-1">Empresa vinculada</label>
              <select v-model="form.empresa_codempresa"
                :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
                errores.empresa ? 'border-red-500' : 'border-gray-300']">
                <option value="">Seleccione empresa</option>
                <option v-for="e in empresas" :key="e.id" :value="e.id">{{ e.nombre }}</option>
              </select>
              <p v-if="errores.empresa" class="text-red-600 text-xs mt-1">{{ errores.empresa }}</p>
            </div>

            <!-- Tipo -->
            <div>
              <label class="block text-gray-700 font-semibold mb-1">Tipo</label>
              <select v-model="form.tipo" @change="cambiarTipo"
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

          <!-- Nombre solo para Usuario / Administrador -->
          <div v-if="form.tipo !== 'C'">
            <label class="block text-gray-700 font-semibold mb-1">Nombre</label>
            <input v-model="form.nombre" type="text"
             maxlength="100"
              :class="['w-full border rounded-xl px-4 py-2 focus:ring-2 focus:ring-blue-400',
              errores.nombre ? 'border-red-500' : 'border-gray-300']"
              placeholder="Nombre del usuario" />
            <p v-if="errores.nombre" class="text-red-600 text-xs mt-1">{{ errores.nombre }}</p>
          </div>

          <!-- Campos para Conductor -->
          <div v-if="form.tipo === 'C'" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Placa -->
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

            <!-- Clave conductor (solo crear) -->
            <div v-if="!form.codusuario">
              <label class="block text-gray-700 font-semibold mb-1">Clave</label>
              <div class="relative">
                <input :type="showClave ? 'text' : 'password'" v-model="form.clave" @input="evaluarFuerzaClave"
                 maxlength="100"
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

            <!-- Datos conductor -->
            <div class="md:col-span-2">
              <label class="block text-gray-700 font-semibold mb-1">Datos del conductor</label>
              <textarea ref="datosTextArea"  maxlength="30" v-model="form.datos_conductor" @input="ajustarAltura"
                :style="{ height: alturaTextArea + 'px', maxHeight: maxAltura + 'px' }"
                :class="['w-full border rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400',
                errores.datos_conductor ? 'border-red-500' : 'border-gray-300']"
                placeholder="Información adicional"></textarea>
              <p v-if="errores.datos_conductor" class="text-red-600 text-xs mt-1">{{ errores.datos_conductor }}</p>
            </div>

          </div>

          <!-- Clave para usuario/administrador (solo crear) -->
          <div v-if="form.tipo !== 'C' && !form.codusuario">
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
import { reactive, ref, watch, onMounted, nextTick } from "vue";
import axios from "axios";
import Swal from "sweetalert2";


const props = defineProps({ visible: Boolean, usuario: Object });
const emit = defineEmits(["close", "saved"]);
const emitClose = () => emit("close");

const empresas = ref([]);

// Errores
const errores = reactive({
  empresa: "",
  tipo: "",
  nombre: "",
  placa: "",
  clave: "",
  datos_conductor: "",
});

// Fuerza contraseña
const showClave = ref(false);
const showClave2 = ref(false);
const fuerzaTexto = ref("");
const fuerzaPorcentaje = ref(0);
const fuerzaClase = ref("bg-red-500");

// Textarea
const datosTextArea = ref(null);
const baseAltura = 100;
const alturaTextArea = ref(baseAltura);
const maxAltura = baseAltura * 1.15;

// Formulario
const form = reactive({
  codusuario: null,
  empresa_codempresa: "",
  nombre: "",
  placa: "",
  clave: "",
  tipo: "",
  identificador: "",
  datos_conductor: "",
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

// Cargar usuario
watch(
  () => props.usuario,
  (u) => {
    if (!u) return;

    Object.assign(form, {
      codusuario: u.codusuario || null,
      empresa_codempresa: u.empresa_codempresa || "",
      nombre: u.tipo !== "C" ? u.nombre : "",
      placa: u.tipo === "C" ? u.nombre : "",
      clave: "",
      tipo: u.tipo || "",
      identificador: u.identificador || "",
      datos_conductor: u.tipo === "C" ? u.identificador : "",
    });

    nextTick(ajustarAltura);
  },
  { immediate: true }
);

// Cambiar tipo
const cambiarTipo = () => {
  if (form.tipo !== "C") {
    form.placa = "";
    form.datos_conductor = "";
  }
};

// Placa formato
const enFormatoPlaca = () => {
  let v = form.placa.toUpperCase().replace(/[^A-Z0-9]/g, "");
  if (v.length > 3) v = v.slice(0, 3) + "-" + v.slice(3);
  form.placa = v.slice(0, 7);
};

// Validar placa duplicada
const validarPlacaDuplicada = async () => {
  if (!form.placa) return;
  try {
    const { data } = await axios.get(
      `http://localhost:8000/api/usuarios/placa/${form.placa}`
    );
    if (data.existe && data.id !== form.codusuario) {
      errores.placa = "Esta placa ya está registrada.";
    } else {
      errores.placa = "";
    }
  } catch (_) {}
};

// Fuerza clave
const evaluarFuerzaClave = () => {
  const c = form.clave || "";
  let score = 0;
  if (c.length >= 6) score++;
  if (/[A-Z]/.test(c)) score++;
  if (/[0-9]/.test(c)) score++;
  if (/[^A-Za-z0-9]/.test(c)) score++;

  fuerzaPorcentaje.value = score * 25;

  const clases = ["bg-red-500", "bg-yellow-400", "bg-green-400", "bg-green-600"];
  fuerzaClase.value = clases[score - 1] || "bg-red-500";

  const textos = ["Débil", "Normal", "Fuerte", "Muy fuerte"];
  fuerzaTexto.value = textos[score - 1] || "";
};

// Ajustar altura textarea
const ajustarAltura = () => {
  nextTick(() => {
    if (!datosTextArea.value) return;
    datosTextArea.value.style.height = "auto";
    alturaTextArea.value = Math.min(
      datosTextArea.value.scrollHeight,
      maxAltura
    );
  });
};

// Guardar usuario
const procesando = ref(false);

const guardarUsuario = async () => {
  if (procesando.value) return;
  procesando.value = true;

  errores.empresa = "";
  errores.tipo = "";
  errores.nombre = "";
  errores.placa = "";
  errores.clave = "";
  errores.datos_conductor = "";

  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 2000,
    timerProgressBar: true,
  });

  // VALIDACIONES
  if (!form.empresa_codempresa) {
    Toast.fire({ icon: "warning", title: "Seleccione una empresa" });
    procesando.value = false;
    return;
  }

  if (!form.tipo) {
    Toast.fire({ icon: "warning", title: "Seleccione un tipo de usuario" });
    procesando.value = false;
    return;
  }

  const nombreFinal = form.tipo === "C" ? form.placa : form.nombre;

  if (!nombreFinal.trim()) {
    Toast.fire({
      icon: "warning",
      title:
        form.tipo === "C"
          ? "La placa es obligatoria"
          : "El nombre es obligatorio",
    });
    procesando.value = false;
    return;
  }

  if (nombreFinal.length > 100) {
    Toast.fire({
      icon: "warning",
      title: "El nombre/placa no puede superar 100 caracteres",
    });
    procesando.value = false;
    return;
  }

  if (!form.codusuario && (!form.clave || !form.clave.trim())) {
    Toast.fire({ icon: "warning", title: "La clave es obligatoria" });
    procesando.value = false;
    return;
  }

  if (form.clave && form.clave.length > 100) {
    Toast.fire({
      icon: "warning",
      title: "La clave no puede superar 100 caracteres",
    });
    procesando.value = false;
    return;
  }

  const identificadorFinal =
    form.tipo === "C" ? form.datos_conductor : form.identificador;

  if (identificadorFinal && identificadorFinal.length > 30) {
    Toast.fire({
      icon: "warning",
      title: "El identificador no puede superar 30 caracteres",
    });
    procesando.value = false;
    return;
  }

  // PROCESO GUARDADO
  Swal.fire({
    title: form.codusuario ? "Actualizando usuario..." : "Guardando usuario...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  const payload = {
    empresa_codempresa: form.empresa_codempresa,
    nombre: nombreFinal,
    tipo: form.tipo,
    identificador: identificadorFinal,
  };

  if (!form.codusuario) payload.clave = form.clave;

  const url = form.codusuario
    ? `http://localhost:8000/api/usuarios/${form.codusuario}`
    : `http://localhost:8000/api/usuarios`;

  const method = form.codusuario ? "put" : "post";

  try {
    const { data } = await axios({ method, url, data: payload });

    Swal.close();

    Swal.fire({
      icon: "success",
      title: form.codusuario ? "Usuario actualizado" : "Usuario registrado",
      timer: 1500,
      showConfirmButton: false,
    });

    const empresa = empresas.value.find(
      (e) => e.id == form.empresa_codempresa
    );

    emit("saved", {
      usuario: {
        codusuario: data.usuario?.id || form.codusuario,
        nombre: nombreFinal,
        tipo: form.tipo,
        empresa_codempresa: form.empresa_codempresa,
        identificador: identificadorFinal,
      },
      empresa_nombre: empresa?.nombre || "Sin empresa",
    });

    emitClose();
  } catch (err) {
    Swal.close();

    console.error("Error guardando usuario:", err);

    Toast.fire({
      icon: "error",
      title: "Error al guardar el usuario",
    });
  } finally {
    procesando.value = false;
  }
};
</script>


<style>
.zoom-fade-enter-active {
  animation: zoomFade 0.25s ease-out;
}

@keyframes zoomFade {
  from {
    opacity: 0;
    transform: scale(0.85);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
