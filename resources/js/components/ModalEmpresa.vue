<template>
  <transition name="fade">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 relative">

        <!-- Header -->
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold text-gray-800">
            {{ empresa.id ? 'Editar Empresa' : 'Crear Empresa' }}
          </h2>
          <button @click="emitClose" class="text-gray-500 hover:text-gray-700 text-lg">&times;</button>
        </div>

        <!-- Formulario -->
        <form @submit.prevent="guardarEmpresa" class="space-y-4">
          <div>
            <label class="block text-gray-700 font-medium mb-1">Nombre <span class="text-red-500">*</span></label>
            <input v-model="empresa.nombre" type="text" required
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Observación</label>
            <textarea v-model="empresa.observacion" rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none"></textarea>
          </div>

          <div>
            <label class="block text-gray-700 font-medium mb-1">Teléfono</label>
            <input v-model="empresa.empresa_col" type="text"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none" />
          </div>

          <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="emitClose"
                    class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Cancelar</button>
            <button type="submit"
                    class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium">
              Guardar
            </button>
          </div>
        </form>

      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch } from 'vue';
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

watch(() => props.empresaData, (newVal) => {
  if (newVal) {
    empresa.value = { ...newVal };
  } else {
    empresa.value = {
      id: null,
      nombre: '',
      observacion: '',
      empresa_col: ''
    };
  }
}, { immediate: true, deep: true });

function emitClose() {
  emit('close');
  empresa.value = {
    id: null,
    nombre: '',
    observacion: '',
    empresa_col: ''
  };
}

async function guardarEmpresa() {
  try {
    if (!empresa.value.nombre || empresa.value.nombre.trim() === '') {
      return alert('El nombre es obligatorio');
    }

    const url = empresa.value.id 
      ? `/api/empresas/${empresa.value.id}` 
      : '/api/empresas';
    const method = empresa.value.id ? 'put' : 'post';

    const response = await axios({ method, url, data: empresa.value });

    if (response.data.ok) {
      emit('saved', response.data.empresa);
      emitClose();
      alert(response.data.msg || 'Empresa guardada exitosamente');
    } else {
      alert(response.data.msg || 'No se pudo guardar la empresa');
    }
  } catch (err) {
    console.error('Error al guardar empresa:', err);
    
    // Mostrar errores de validación si existen
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
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>