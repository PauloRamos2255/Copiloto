<!-- Pages/Rutas.vue -->
<template>
  <div class="min-h-screen bg-gray-100">

    <!-- HEADER -->
    <Header :nombreUsuario="nombreUsuario" />

    <!-- CONTENIDO PRINCIPAL -->
    <main class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Gestión de Rutas</h1>
        <button @click="abrirFormulario = true"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2">
          <i class="fas fa-plus"></i>
          <span>Crear Ruta</span>
        </button>
      </div>

      <!-- FILTROS -->
      <div class="mb-4 flex gap-2">
        <input type="text" placeholder="Filtrar por nombre..." v-model="filtro"
               class="px-3 py-2 border border-black rounded w-64 focus:ring focus:ring-blue-300" />
      </div>

      <!-- TABLA DE RUTAS -->
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full text-left text-gray-700">
          <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-semibold">
            <tr>
              <th class="px-4 py-2">#</th>
              <th class="px-4 py-2">Nombre</th>
              <th class="px-4 py-2">Límite</th>
              <th class="px-4 py-2">Tipo</th>
              <th class="px-4 py-2 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="(ruta, index) in rutasPaginadas" :key="ruta.id">
              <tr class="border-b hover:bg-gray-50 transition cursor-pointer" @click="toggleSegmentos(ruta)">
                <td class="px-4 py-2">{{ index + 1 + (paginaActual-1)*registrosPorPagina }}</td>
                <td class="px-4 py-2 font-medium">{{ ruta.nombre }}</td>
                <td class="px-4 py-2">{{ ruta.limite }}</td>
                <td class="px-4 py-2">{{ ruta.tipo }}</td>
                <td class="px-4 py-2 text-center">
                  <button class="text-blue-500 hover:text-blue-700 mx-1"><i class="fas fa-edit"></i></button>
                  <button class="text-red-500 hover:text-red-700 mx-1"><i class="fas fa-trash"></i></button>
                </td>
              </tr>
              <tr v-if="ruta.mostrarSegmentos" v-for="s in ruta.segmentos" :key="s.id + '-seg'" class="bg-gray-50 text-gray-600">
                <td colspan="5" class="px-8 py-1">• {{ s.nombre }}</td>
              </tr>
            </template>
            <tr v-if="rutasFiltradas.length === 0">
              <td colspan="5" class="text-center py-4 text-gray-500">No hay rutas</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- PAGINADO -->
      <div class="mt-4 flex justify-end space-x-2">
        <button @click="paginaAnterior" :disabled="paginaActual===1"
                class="px-3 py-1 border rounded disabled:opacity-50">Anterior</button>
        <span>Página {{ paginaActual }} de {{ totalPaginas }}</span>
        <button @click="paginaSiguiente" :disabled="paginaActual===totalPaginas"
                class="px-3 py-1 border rounded disabled:opacity-50">Siguiente</button>
      </div>
    </main>

    <!-- FORMULARIO CREAR RUTA -->
    <transition name="fade">
      <div v-if="abrirFormulario"
           class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-5xl p-6 rounded shadow-lg overflow-y-auto max-h-[90vh]">

          <h2 class="text-xl font-bold mb-4">Crear Nueva Ruta</h2>

          <!-- Nombre, límite y tipo -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block font-semibold mb-1">Nombre de la Ruta</label>
              <input type="text" v-model="nuevaRuta.nombre"
                     class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-300" />
            </div>
            <div>
              <label class="block font-semibold mb-1">Límite General</label>
              <input type="number" v-model="nuevaRuta.limite" disabled
                     class="w-full border rounded px-3 py-2 bg-gray-100 cursor-not-allowed" />
            </div>
            <div>
              <label class="block font-semibold mb-1">Tipo de Ruta</label>
              <select v-model="nuevaRuta.tipo"
                      class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-300">
                <option disabled value="">Seleccione un tipo</option>
                <option>Urbana</option>
                <option>Interprovincial</option>
                <option>Internacional</option>
              </select>
            </div>
          </div>

          <!-- Logo -->
          <div class="mb-4">
            <label class="block font-semibold mb-1">Logo de la Empresa</label>
            <div
              class="border-dashed border-2 border-gray-300 rounded-lg h-32 flex items-center justify-center cursor-pointer relative hover:border-blue-400"
              @dragover.prevent
              @drop.prevent="dropLogo"
              @click="$refs.logoInput.click()"
            >
              <input
                ref="logoInput"
                type="file"
                accept=".jpg,.jpeg,.png,.svg,.gif"
                class="hidden"
                @change="previewLogo"
              />
              <span v-if="!nuevaRuta.logoPreview" class="text-gray-400">Arrastra tu logo aquí o haz click</span>
              <img v-if="nuevaRuta.logoPreview" :src="nuevaRuta.logoPreview" class="h-full object-contain" />
            </div>
          </div>

          <!-- Segmentos -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
            <!-- Lista de todos los segmentos -->
            <div>
              <h3 class="font-semibold mb-2">Segmentos Disponibles</h3>
              <ul id="disponibles" class="border rounded p-2 h-64 overflow-y-auto bg-gray-50">
                <li v-for="segmento in segmentosDisponibles" :key="segmento.id"
                    :data-id="segmento.id"
                    class="p-2 mb-1 bg-white rounded cursor-pointer shadow hover:bg-blue-50">
                  {{ segmento.nombre }}
                </li>
              </ul>
            </div>

            <!-- Lista de la nueva ruta con botones para subir/bajar -->
            <div>
              <h3 class="font-semibold mb-2">Segmentos en la Ruta</h3>
              <ul id="ruta" class="border rounded p-2 h-64 overflow-y-auto bg-gray-50">
                <li v-for="segmento in segmentosRuta" :key="segmento.id"
                    :data-id="segmento.id"
                    class="p-2 mb-1 bg-white rounded cursor-move shadow flex justify-between items-center">
                  <span>{{ segmento.nombre }}</span>
                  <div class="flex flex-col space-y-1 ml-2">
                    <button @click="moverArriba(segmento)" class="text-blue-500 hover:text-blue-700"><i class="fas fa-arrow-up"></i></button>
                    <button @click="moverAbajo(segmento)" class="text-blue-500 hover:text-blue-700"><i class="fas fa-arrow-down"></i></button>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <div class="flex justify-end space-x-2">
            <button @click="guardarRuta" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
            <button @click="abrirFormulario = false" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Cancelar</button>
          </div>

        </div>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import Header from './Header.vue';
import Sortable from 'sortablejs';

interface Segmento { id: number; nombre: string; }
interface Ruta {
  id?: number;
  nombre: string;
  limite: number;
  tipo: string;
  logo?: File | null;
  logoPreview?: string | null;
  segmentos: Segmento[];
  mostrarSegmentos?: boolean;
}

const nombreUsuario = ref('Admin');
const abrirFormulario = ref(false);
const filtro = ref('');
const paginaActual = ref(1);
const registrosPorPagina = ref(5);

const rutas = ref<Ruta[]>([
  { id: 1, nombre: 'Ruta Lima-Callao', limite: 14, tipo:'Urbana', segmentos:[{ id:1, nombre:'Lima-Callao' }], mostrarSegmentos:false },
  { id: 2, nombre: 'Ruta Miraflores-San Isidro', limite: 7, tipo:'Urbana', segmentos:[{ id:2, nombre:'Miraflores-San Isidro' }], mostrarSegmentos:false },
  { id: 3, nombre: 'Ruta Surco-La Molina', limite: 12, tipo:'Interprovincial', segmentos:[{ id:3, nombre:'Surco-La Molina' }], mostrarSegmentos:false },
  { id: 4, nombre: 'Ruta Chorrillos-Lince', limite: 10, tipo:'Urbana', segmentos:[{ id:4, nombre:'Chorrillos-Lince' }], mostrarSegmentos:false },
  { id: 5, nombre: 'Ruta San Borja-Barranco', limite: 8, tipo:'Urbana', segmentos:[{ id:5, nombre:'San Borja-Barranco' }], mostrarSegmentos:false },
]);

const segmentosDisponibles = ref<Segmento[]>([
  { id: 1, nombre: 'Lima - Callao' },
  { id: 2, nombre: 'Miraflores - San Isidro' },
  { id: 3, nombre: 'Surco - La Molina' },
  { id: 4, nombre: 'Chorrillos - Lince' },
  { id: 5, nombre: 'San Borja - Barranco' },
]);

const segmentosRuta = ref<Segmento[]>([]);

const nuevaRuta = ref<Ruta>({
  nombre: '',
  limite: 0,
  tipo:'',
  logo: null,
  logoPreview: null,
  segmentos: []
});

const rutasFiltradas = computed(() =>
  rutas.value.filter(r => r.nombre.toLowerCase().includes(filtro.value.toLowerCase()))
);

const totalPaginas = computed(() =>
  Math.ceil(rutasFiltradas.value.length / registrosPorPagina.value)
);

const rutasPaginadas = computed(() =>
  rutasFiltradas.value.slice((paginaActual.value-1)*registrosPorPagina.value, paginaActual.value*registrosPorPagina.value)
);

function toggleSegmentos(ruta: Ruta) {
  rutas.value.forEach(r => { if (r !== ruta) r.mostrarSegmentos = false; });
  ruta.mostrarSegmentos = !ruta.mostrarSegmentos;
}

function paginaAnterior() {
  if (paginaActual.value > 1) paginaActual.value--;
}

function paginaSiguiente() {
  if (paginaActual.value < totalPaginas.value) paginaActual.value++;
}

function previewLogo(event: Event) {
  const file = (event.target as HTMLInputElement).files?.[0];
  handleLogoFile(file);
}

function dropLogo(event: DragEvent) {
  const file = event.dataTransfer?.files?.[0];
  handleLogoFile(file);
}

function handleLogoFile(file: File | undefined) {
  if (!file) return;
  const formatosValidos = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif'];
  if (!formatosValidos.includes(file.type)) {
    alert('Formato no válido');
    nuevaRuta.value.logo = null;
    nuevaRuta.value.logoPreview = null;
    return;
  }
  nuevaRuta.value.logo = file;
  const reader = new FileReader();
  reader.onload = (e) => nuevaRuta.value.logoPreview = e.target?.result as string;
  reader.readAsDataURL(file);
}

function guardarRuta() {
  if (!nuevaRuta.value.nombre || segmentosRuta.value.length === 0 || !nuevaRuta.value.tipo) {
    alert('Complete todos los campos y agregue al menos un segmento');
    return;
  }

  const nueva: Ruta = {
    id: rutas.value.length + 1,
    nombre: nuevaRuta.value.nombre,
    limite: nuevaRuta.value.limite,
    tipo: nuevaRuta.value.tipo,
    segmentos: [...segmentosRuta.value],
    mostrarSegmentos: false
  };

  rutas.value.push(nueva);

  // Reiniciar formulario
  nuevaRuta.value = { nombre:'', limite:0, tipo:'', logo:null, logoPreview:null, segmentos:[] };
  segmentosRuta.value = [];
  abrirFormulario.value = false;
}

function moverArriba(segmento: Segmento) {
  const index = segmentosRuta.value.indexOf(segmento);
  if (index > 0) {
    segmentosRuta.value.splice(index, 1);
    segmentosRuta.value.splice(index-1, 0, segmento);
    actualizarLimite();
  }
}

function moverAbajo(segmento: Segmento) {
  const index = segmentosRuta.value.indexOf(segmento);
  if (index < segmentosRuta.value.length - 1) {
    segmentosRuta.value.splice(index, 1);
    segmentosRuta.value.splice(index+1, 0, segmento);
    actualizarLimite();
  }
}

function actualizarLimite() {
  nuevaRuta.value.limite = segmentosRuta.value.length * 10;
}

onMounted(() => {
  const disponiblesEl = document.getElementById('disponibles');
  const rutaEl = document.getElementById('ruta');
  if (disponiblesEl && rutaEl) {
    Sortable.create(disponiblesEl, {
      group: 'segmentos',
      animation: 150,
      sort: false,
      onAdd(evt) {
        const id = parseInt(evt.item.getAttribute('data-id')!);
        const index = segmentosDisponibles.value.findIndex(s => s.id === id);
        if (index >= 0) {
          segmentosRuta.value.push(segmentosDisponibles.value[index]);
          segmentosDisponibles.value.splice(index, 1);
          actualizarLimite();
        }
        evt.item.remove();
      }
    });

    Sortable.create(rutaEl, {
      group: 'segmentos',
      animation: 150,
      onUpdate: actualizarLimite
    });
  }
});
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
