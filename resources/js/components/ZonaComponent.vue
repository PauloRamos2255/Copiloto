<template>
  <div>
    <h2 class="text-xl font-bold mb-4">Zonas</h2>
    <button @click="cargarZona" class="bg-blue-500 text-white px-4 py-2 rounded">Cargar zonas</button>
    <table class="table-auto border-collapse border mt-4 w-full">
      <thead>
        <tr class="bg-gray-200">
          <th class="border px-2 py-1">ID</th>
          <th class="border px-2 py-1">Nombre</th>
          <th class="border px-2 py-1">Color</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="z in zona" :key="z.id">
          <td class="border px-2 py-1">{{ z.id }}</td>
          <td class="border px-2 py-1">{{ z.nombre }}</td>
          <td class="border px-2 py-1">{{ z.color }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import axios from 'axios';
import { ref } from 'vue';

const loading = ref(false);
const zonas = ref([]);
const error = ref(null);

const cargarZonas = async (itemId) => {
    loading.value = true;
    error.value = null;
    
    try {
        const response = await axios.post('/api/zone-data', {
            itemId: itemId
        });
        
        if (response.data.success) {
            zonas.value = response.data.data;
            console.log('✅ Zonas cargadas:', response.data.data);
            return response.data.data;
        } else {
            error.value = response.data.error;
            console.error('❌ Error:', response.data.error);
        }
    } catch (err) {
        error.value = err.response?.data?.error || err.message;
        console.error('❌ Error al cargar zonas:', err);
        
        if (err.response) {
            console.error('Status:', err.response.status);
            console.error('Data:', err.response.data);
        }
    } finally {
        loading.value = false;
    }
};

// Ejemplo de uso
// cargarZonas(12345);
</script>