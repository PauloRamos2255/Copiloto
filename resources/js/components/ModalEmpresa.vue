<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 
                            bg-black/40 backdrop-blur-sm">
      <div class="w-full max-w-lg rounded-2xl overflow-hidden bg-gray-100 shadow-2xl flex flex-col max-h-[90vh]">

        <!-- Header -->
        <div class="flex justify-between items-center px-6 py-6 bg-blue-600">
          <h2 class="text-3xl font-bold text-white">
            {{ empresa.id ? 'Editar Empresa' : 'Crear Empresa' }}
          </h2>
          <button @click="emitClose" class="text-white hover:text-gray-200 text-3xl">&times;</button>
        </div>

        <!-- Contenido principal -->
        <div class="px-6 py-4 flex-1 overflow-y-auto space-y-4">

          <!-- Nombre -->
          <div>
            <label class="block text-gray-700 font-medium mb-1">
              Nombre <span class="text-red-500">*</span>
            </label>
            <input v-model="empresa.nombre" type="text" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
            <p v-if="errores.nombre" class="text-red-600 text-xs mt-1">{{ errores.nombre }}</p>
          </div>

          <!-- Observación -->
          <div>
            <label class="block text-gray-700 font-medium mb-1">Observación</label>
            <textarea v-model="empresa.observacion"
                      :style="{ resize: 'vertical', maxHeight: maxAlturaObservacion + 'px' }"
                      rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none">
            </textarea>
          </div>

          <!-- Teléfono -->
          <div>
            <label class="block text-gray-700 font-medium mb-1">Teléfono</label>
            <input v-model="empresa.empresa_col" type="text" maxlength="11" @input="formatearTelefono"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none" />
            <p v-if="errores.empresa_col" class="text-red-600 text-xs mt-1">{{ errores.empresa_col }}</p>
          </div>

        </div>

        <!-- Footer -->
        <div class="border-t border-gray-300 bg-gray-50 px-6 py-4 flex flex-col md:flex-row justify-end gap-3 flex-shrink-0">
          <button type="button" @click="emitClose"
                  class="px-6 py-2 border rounded-lg hover:bg-gray-200 transition-all font-medium w-full md:w-auto text-sm">
            <i class="fas fa-times mr-1"></i>Cancelar
          </button>
          <button type="button" @click="guardarEmpresa"
                  class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2 text-sm">
            <i class="fas fa-save mr-1"></i> Guardar
          </button>
        </div>

      </div>
    </div>
  </transition>
</template>


<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  visible: Boolean,
  empresaData: Object
});
const emit = defineEmits(['close', 'saved']);

const empresa = ref({
  id: null,
  nombre: '',
  observacion: '',
  empresa_col: ''
});

const errores = ref({
  nombre: '',
  empresa_col: ''
});

const maxAlturaObservacion = computed(() => 150); // 15% tamaño máximo

watch(() => props.empresaData, (newVal) => {
  if (newVal) {
    empresa.value = { ...newVal };

    // Aplicar formato al teléfono si existe
    if (empresa.value.empresa_col) {
      let val = empresa.value.empresa_col.replace(/\D/g, '').slice(0, 9);
      let formatted = '';
      for (let i = 0; i < val.length; i++) {
        formatted += val[i];
        if (i === 2 || i === 5) formatted += '-';
      }
      empresa.value.empresa_col = formatted;
    }
  } else {
    empresa.value = { id: null, nombre: '', observacion: '', empresa_col: '' };
  }
}, { immediate: true, deep: true });


function emitClose() {
  emit('close');
  empresa.value = { id: null, nombre: '', observacion: '', empresa_col: '' };
  errores.value = { nombre: '', empresa_col: '' };
}

function formatearTelefono(e) {
  const input = e.target;
  let val = input.value.replace(/\D/g, '').slice(0, 9); // solo dígitos

  let formatted = '';
  for (let i = 0; i < val.length; i++) {
    formatted += val[i];
    if (i === 2 || i === 5) formatted += '-';
  }

  // Si el último carácter es guion y el usuario está borrando, lo eliminamos
  if (e.inputType === 'deleteContentBackward' && formatted.endsWith('-')) {
    formatted = formatted.slice(0, -1);
  }

  empresa.value.empresa_col = formatted;

  // Ajustamos la posición del cursor
  let cursorPos = input.selectionStart;
  input.setSelectionRange(cursorPos, cursorPos);
}



async function guardarEmpresa() {
  errores.value = { nombre: '', empresa_col: '' };

  if (!empresa.value.nombre || empresa.value.nombre.trim() === '') {
    errores.value.nombre = 'El nombre es obligatorio';
    return;
  }

  const telefonoNumeros = empresa.value.empresa_col.replace(/\D/g, ''); // quitar guiones

  if (telefonoNumeros.length !== 9 && empresa.value.empresa_col.length > 0) {
    errores.value.empresa_col = 'El teléfono debe tener 9 números';
    return;
  }

  try {
    const payload = { ...empresa.value, empresa_col: telefonoNumeros }; // enviamos sin guiones

    const url = empresa.value.id ? `/api/empresas/${empresa.value.id}` : '/api/empresas';
    const method = empresa.value.id ? 'put' : 'post';

    const response = await axios({ method, url, data: payload });

    if (response.data.ok) {
      emit('saved', response.data.empresa);
      emitClose();
      alert(response.data.msg || 'Empresa guardada exitosamente');
    } else {
      alert(response.data.msg || 'No se pudo guardar la empresa');
    }
  } catch (err) {
    console.error('Error al guardar empresa:', err);
    if (err.response?.data?.errors) {
      const errors = Object.values(err.response.data.errors).flat().join('\n');
      alert('Errores de validación:\n' + errors);
    } else {
      const errorMsg = err.response?.data?.msg || 'Error al guardar la empresa';
      alert(errorMsg);
    }
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
