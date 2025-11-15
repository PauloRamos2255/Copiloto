<template>
  <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 relative">
      <h2 class="text-xl font-semibold mb-4">
        {{ form.codusuario ? 'Editar Usuario' : 'Crear Usuario' }}
      </h2>

      <form @submit.prevent="guardarUsuario" class="space-y-4">

        <!-- Empresa (solo si no es conductor) -->
        <div v-if="form.tipo !== 'C'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Empresa <span class="text-red-500">*</span></label>
          <select
            v-model.number="form.empresa_codempresa"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="" disabled>Seleccione una empresa</option>
            <option v-for="empresa in empresas" :key="empresa.id" :value="empresa.id">
              {{ empresa.nombre }}
            </option>
          </select>
        </div>

        <!-- Nombre o Datos del Conductor -->
        <div v-if="form.tipo !== 'C'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
          <input
            v-model="form.nombre"
            type="text"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <div v-else>
          <label class="block text-sm font-medium text-gray-700 mb-1">Datos del Conductor <span class="text-red-500">*</span></label>
          <input
            v-model="form.nombre"
            type="text"
            placeholder="Nombre del conductor"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 mb-2"
          />
          <input
            v-model="form.placa"
            type="text"
            placeholder="Placa del vehículo (A1A-000)"
            required
            maxlength="7"
            @input="form.placa = formatPlaca(form.placa)"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Clave (solo para no conductores) -->
        <div v-if="form.tipo !== 'C'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Clave <span v-if="!form.codusuario" class="text-red-500">*</span></label>
          <input
            v-model="form.clave"
            :required="!form.codusuario"
            type="password"
            placeholder="Dejar vacío para no cambiar"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Tipo -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Tipo <span class="text-red-500">*</span></label>
          <select
            v-model="form.tipo"
            required
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="">Seleccione tipo</option>
            <option value="A">Administrador</option>
            <option value="U">Usuario</option>
            <option value="C">Conductor</option>
          </select>
        </div>

        <!-- Identificador (solo si no es conductor) -->
        <div v-if="form.tipo !== 'C'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Identificador</label>
          <input
            v-model="form.identificador"
            type="text"
            class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>

        <!-- Acciones -->
        <div class="flex justify-end space-x-3 mt-4">
          <button type="button" @click="$emit('close')" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancelar</button>
          <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
            {{ form.codusuario ? 'Actualizar' : 'Crear' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  visible: Boolean,
  usuario: Object
});

const emit = defineEmits(['close', 'saved']);

const apiURL = 'http://localhost:8000/api/usuarios';
const apiEmpresas = 'http://localhost:8000/api/empresas';

const form = reactive({
  codusuario: null,
  empresa_codempresa: null,
  nombre: '',
  placa: '',
  clave: '',
  tipo: '',
  identificador: ''
});

const empresas = ref([]);

// Cargar empresas al montar
onMounted(async () => {
  try {
    const { data } = await axios.get(apiEmpresas);
    if (data.ok && Array.isArray(data.empresas)) empresas.value = data.empresas;
  } catch (error) {
    console.error('Error al cargar empresas:', error);
    alert('No se pudieron cargar las empresas.');
  }
});

// Precargar usuario si existe
watch(
  () => props.usuario,
  (newVal) => {
    if (newVal) {
      form.codusuario = newVal.codusuario || null;
      form.empresa_codempresa = newVal.empresa_codempresa || null;
      form.nombre = newVal.nombre || '';
      form.placa = newVal.placa || '';
      form.clave = '';
      form.tipo = newVal.tipo || '';
      form.identificador = newVal.identificador || '';
    }
  },
  { immediate: true }
);

// Formatear placa: A1A-000
const formatPlaca = (value) => {
  if (!value) return '';
  let v = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
  if (v.length > 3) v = v.slice(0,3) + '-' + v.slice(3,6);
  return v.slice(0,7);
};

// Guardar usuario
const guardarUsuario = async () => {
  try {
    if (!form.nombre.trim()) return alert('El nombre es obligatorio');
    if (!form.tipo) return alert('Debe seleccionar un tipo');

    // Si es conductor, la placa es obligatoria
    if (form.tipo === 'C' && !form.placa.trim()) return alert('La placa es obligatoria');

    // Si no es conductor, la empresa es obligatoria
    if (form.tipo !== 'C' && !form.empresa_codempresa)
      return alert('Debe seleccionar una empresa');

    // --- Transformación de datos para coincidir con la DB ---
    let payload = {};

    if (form.tipo === 'C') {
      // Conductor: nombre = placa, identificador = nombre del conductor
      payload = {
        empresa_codempresa: 1, // si siempre es 1 o cámbialo si es dinámico
        nombre: form.placa,
        identificador: form.nombre, 
        tipo: 'C',
        clave: form.clave || '123456' // o forzar ingreso
      };
    } else {
      // Administrador o usuario normal
      payload = {
        empresa_codempresa: form.empresa_codempresa,
        nombre: form.nombre,
        clave: form.clave ? form.clave : undefined,
        tipo: form.tipo,
        identificador: form.identificador || null
      };
    }

    const url = form.codusuario ? `${apiURL}/${form.codusuario}` : apiURL;
    const method = form.codusuario ? 'put' : 'post';

    const response = await axios({ method, url, data: payload });

    emit('saved', response.data);
    emit('close');
    alert('Usuario guardado correctamente');

  } catch (err) {
    console.error('Error al guardar usuario:', err);

    if (err.response?.data?.errors) {
      alert(Object.values(err.response.data.errors).flat().join("\n"));
    } else {
      alert('Error en el servidor');
    }
  }
};

</script>
