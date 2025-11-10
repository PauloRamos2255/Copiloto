<template>
  <div class="min-h-screen bg-gray-100 font-sans">
    <!-- Header -->
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-4 md:p-6">

      <!-- Encabezado y botón crear ruta -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
        <h1 class="text-2xl md:text-3xl lg:text-4xl font-extrabold bg-clip-text text-transparent 
                   bg-gradient-to-r from-blue-600 to-blue-400 drop-shadow-md flex items-center">
          <i class="fas fa-map-marked-alt mr-2"></i>Gestión de Rutas
        </h1>

        <button @click="formularioSegmentoAbierto = true" class="flex items-center space-x-2 bg-gradient-to-r from-blue-600 to-blue-500 
                       hover:from-blue-700 hover:to-blue-600 text-white px-5 py-2 rounded-xl 
                       shadow-lg hover:shadow-xl transition-all font-semibold w-full md:w-auto justify-center">
          <i class="fas fa-plus"></i>
          <span>Crear Ruta</span>
        </button>
      </div>

      <!-- Filtro de rutas -->
      <div class="mb-4">
        <input type="text" v-model="filtro" placeholder="Filtrar por nombre..."
          class="w-full md:w-64 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-300 focus:outline-none shadow-sm transition-all" />
      </div>

      <!-- Tabla de rutas -->
      <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm text-gray-800">
            <thead
              class="bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 uppercase text-xs font-semibold tracking-wide">
              <tr>
                <th class="px-6 py-4 text-left">#</th>
                <th class="px-6 py-4 text-left">Nombre</th>
                <th class="px-6 py-4 text-left">Límite</th>
                <th class="px-6 py-4 text-left">Tipo</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>

            <tbody>
              <template v-for="(ruta, index) in rutasPaginadas" :key="ruta.id">
                <!-- Fila principal -->
                <tr class="border-b border-gray-100 hover:bg-blue-50 transition-all cursor-pointer group"
                  @click="toggleSegmentos(ruta)">
                  <td class="px-6 py-4 font-semibold text-gray-600 group-hover:text-blue-600">
                    {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                  </td>
                  <td class="px-6 py-4 font-medium text-gray-900 group-hover:text-blue-700">
                    {{ ruta.nombre }}
                  </td>
                  <td class="px-6 py-4 text-gray-700">{{ ruta.limite }} km/h</td>
                  <td class="px-6 py-4">
                    <span :class="[
                      'px-3 py-1 rounded-full text-xs font-bold',
                      ruta.tipo === 'General'
                        ? 'bg-green-100 text-green-700'
                        : ruta.tipo === 'Vuelta'
                          ? 'bg-yellow-100 text-yellow-700'
                          : 'bg-gray-100 text-gray-600'
                    ]">
                      {{ ruta.tipo }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-center space-x-2">
                    <button class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                      @click.stop="editarRuta(ruta.id)" title="Editar ruta">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click.stop="confirmarAccionRuta(ruta.id)"
                      class="p-2 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                      title="Clonar ruta">
                      <i class="fas fa-clone"></i>
                    </button>
                    <button @click.stop="eliminarRuta(ruta.id)"
                      class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                      title="Eliminar ruta">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>

                <!-- Fila expandible con animación -->
                <transition name="fade-slide">
                  <tr v-if="ruta.mostrarSegmentos" class="bg-blue-50 transition-all">
                    <td colspan="5" class="px-6 md:px-10 py-4">
                      <div v-if="ruta.segmentos.length > 0" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div v-for="segmento in ruta.segmentos" :key="'saved-' + (segmento._tempId || segmento.id)"
                          class="p-4 bg-white rounded-lg border-l-4 border-blue-500 shadow-sm hover:shadow-md transition-all">
                          <div class="font-semibold text-gray-800">
                            {{ segmento.nombre || `Segmento ${segmento.id}` }}
                          </div>
                          <div class="text-sm text-gray-600 mt-1">
                            <span v-if="segmento.mensaje" class="block">{{ segmento.mensaje }}</span>
                            <span v-if="segmento.velocidad" class="text-blue-600 font-medium">
                              {{ segmento.velocidad }} km/h
                            </span>
                          </div>
                        </div>
                      </div>

                      <div v-else class="text-gray-400 italic text-center py-3">
                        Sin segmentos asignados
                      </div>
                    </td>
                  </tr>
                </transition>
              </template>

              <!-- Sin resultados -->
              <tr v-if="rutasFiltradas.length === 0">
                <td colspan="5" class="text-center py-10 text-gray-500">
                  <i class="fas fa-inbox text-4xl mb-2 block text-gray-400"></i>
                  No hay rutas disponibles
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div class="mt-4 flex flex-col md:flex-row justify-end items-center space-y-2 md:space-y-0 md:space-x-2">
        <button @click="cambiarPagina(paginaActual - 1)" :disabled="paginaActual === 1"
          class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-all w-full md:w-auto">
          Anterior
        </button>
        <span class="px-4 py-2 font-medium text-gray-700">Página {{ paginaActual }} de {{ totalPaginas }}</span>
        <button @click="cambiarPagina(paginaActual + 1)" :disabled="paginaActual >= totalPaginas"
          class="px-4 py-2 border rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 transition-all w-full md:w-auto">
          Siguiente
        </button>
      </div>
    </main>

    <!-- Modal Crear/Editar Ruta -->
    <transition name="fade">
      <div v-if="formularioSegmentoAbierto"
        class="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4"
        @click.self="cerrarFormulario">

        <div v-if="cargandoFormulario" class="absolute inset-0 bg-white/80 flex items-center justify-center z-50">
          <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-600 border-solid"></div>
        </div>

        <div
          class="bg-white w-full max-w-6xl rounded-2xl shadow-2xl max-h-[90vh] overflow-hidden flex flex-col relative">

          <!-- Loader superpuesto -->


          <!-- Contenido del formulario -->
          <div v-show="!cargandoFormulario" class="flex-1 flex flex-col overflow-hidden">

            <!-- Header Modal -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center justify-between">
              <h2 class="text-xl font-bold text-white flex items-center gap-2">
                <i class="fas fa-route"></i>
                {{ nuevaRuta.id ? 'Editar Ruta' : 'Crear Nueva Ruta' }}
              </h2>
              <button @click="cerrarFormulario" class="text-white hover:bg-blue-500 p-2 rounded-lg transition-colors">
                <i class="fas fa-times text-xl"></i>
              </button>
            </div>

            <!-- Contenido principal -->
            <div class="flex-1 overflow-y-auto p-6 space-y-5">

              <!-- Información básica -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                  <label class="block font-semibold mb-1 text-gray-700 text-[13px]">
                    <i class="fas fa-tag text-blue-600 mr-1"></i> Nombre de la Ruta
                  </label>
                  <input type="text" v-model="nuevaRuta.nombre" placeholder="Ej: Ruta Centro"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none shadow-sm transition-all text-[13px]" />
                </div>

                <div>
                  <label class="block font-semibold mb-1 text-gray-700 text-[13px]">
                    <i class="fas fa-gauge-high text-blue-600 mr-1"></i> Límite General
                  </label>
                  <div class="relative">
                    <input type="number" :value="velocidadPromedio" disabled
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-blue-50 text-[13px] cursor-not-allowed font-semibold" />
                    <span
                      class="absolute right-3 top-1/2 -translate-y-1/2 text-[12px] font-semibold text-blue-700">km/h</span>
                  </div>
                  <p v-if="velocidadPromedio > 0" class="text-[11px] text-gray-500 mt-1">
                    Promedio automático de segmentos
                  </p>
                </div>

                <div>
                  <label class="block font-semibold mb-1 text-gray-700 text-[13px]">
                    <i class="fas fa-road text-blue-600 mr-1"></i> Tipo de Ruta
                  </label>
                  <select v-model="nuevaRuta.tipo"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 focus:outline-none transition-all text-[13px]">
                    <option value="" disabled>Seleccione un tipo</option>
                    <option value="General">General</option>
                    <option value="Vuelta">Vuelta</option>
                  </select>
                </div>
              </div>

              <!-- Logo -->
              <div>
                <label class="block font-semibold mb-1 text-gray-700 text-[13px]">
                  <i class="fas fa-image text-blue-600 mr-1"></i> Logo de la Empresa
                </label>
                <div
                  class="border-2 border-dashed rounded-lg h-28 flex items-center justify-center cursor-pointer transition-all"
                  :class="dragOverLogo ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'"
                  @dragover.prevent="dragOverLogo = true" @dragleave.prevent="dragOverLogo = false"
                  @drop.prevent="dropLogo" @click="abrirSelectorLogo">
                  <input ref="logoInput" type="file" accept="image/*" class="hidden" @change="previewLogo" />
                  <div v-if="!nuevaRuta.logoPreview" class="text-center text-gray-400 text-[12px]">
                    <i class="fas fa-cloud-upload-alt text-2xl mb-1"></i>
                    <p>Arrastra tu logo aquí o haz clic</p>
                  </div>
                  <img v-if="nuevaRuta.logoPreview" :src="nuevaRuta.logoPreview"
                    class="h-28 w-auto object-contain mx-auto" alt="Logo preview" />
                </div>
              </div>

              <!-- Sección de segmentos -->
              <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
                <!-- Segmentos Disponibles -->
                <div class="lg:col-span-3 bg-green-50 rounded-lg border border-green-200 flex flex-col p-3"
                  style="height: 10cm;">
                  <h3 class="font-semibold mb-2 flex items-center text-sm">
                    <i class="fas fa-list mr-2 text-green-600"></i>Disponibles ({{ segmentosDisponiblesFiltrados.length
                    }})
                  </h3>

                  <input type="text" v-model="filtroSegmentos" placeholder="Buscar..."
                    class="w-full px-2 py-1.5 border border-green-300 rounded-lg mb-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none" />

                  <div class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1"
                    :class="dragOverDisponibles ? 'border-green-500 bg-green-50' : 'border-green-300'"
                    @dragover.prevent="dragOverDisponibles = true" @dragleave.prevent="dragOverDisponibles = false"
                    @drop.prevent="dropSegmentoEnDisponibles">

                    <div v-for="segmento in segmentosDisponiblesFiltrados" :key="segmento._tempId" draggable="true"
                      @dragstart="iniciarDragSegmento(segmento, 'disponibles')" @dragend="finalizarDrag"
                      @dblclick="agregarSegmentoARuta(segmento)"
                      class="p-1.5 mb-1.5 bg-green-100 border border-green-300 rounded cursor-pointer hover:bg-green-200 hover:shadow transition-all text-xs flex items-center justify-between group">
                      <div class="flex items-center">
                        <i class="fas fa-grip-vertical text-green-600 mr-1 text-xs"></i>
                        <span>{{ segmento.nombre }}</span>
                      </div>
                      <i
                        class="fas fa-arrow-right text-green-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                  </div>

                  <p class="text-xs text-gray-500 mt-1 text-center">
                    <i class="fas fa-info-circle mr-1"></i>Doble clic o arrastra
                  </p>
                </div>

                <!-- Segmentos en la Ruta -->
                <div class="lg:col-span-6 bg-blue-50 rounded-lg border border-blue-200 flex flex-col p-3"
                  style="height: 10cm;">
                  <h3 class="font-semibold mb-2 flex items-center text-sm">
                    <i class="fas fa-route mr-2 text-blue-600"></i>En la Ruta ({{ segmentosRuta.length }})
                  </h3>

                  <!-- Formulario de edición más compacto -->
                  <div v-if="segmentoSeleccionado" class="bg-yellow-50 rounded-lg p-2 mb-2 border border-yellow-300">
                    <div class="flex items-center justify-between mb-1">
                      <span class="text-xs font-semibold text-gray-700">
                        ✏ Editando: <span class="text-blue-600">{{ segmentoSeleccionado.nombre }}</span>
                      </span>
                      <button @click="segmentoSeleccionado = null" class="text-gray-600 hover:text-gray-800 text-xs">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                      <div>
                        <label class="text-[11px] font-semibold text-gray-700">Mensaje</label>
                        <input type="text" v-model="segmentoSeleccionado.mensaje" placeholder="Ej: Velocidad máxima"
                          class="w-full border border-yellow-400 rounded px-2 py-1 text-[11px] focus:ring-2 focus:ring-yellow-400 focus:outline-none" />
                      </div>
                      <div>
                        <label class="text-[11px] font-semibold text-gray-700">Velocidad</label>
                        <input type="number" v-model.number="segmentoSeleccionado.velocidad" placeholder="Ej: 80"
                          min="0"
                          class="w-full border border-yellow-400 rounded px-2 py-1 text-[11px] focus:ring-2 focus:ring-yellow-400 focus:outline-none" />
                      </div>
                    </div>
                  </div>

                  <!-- Lista de segmentos -->
                  <div class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1"
                    :class="dragOverRuta ? 'border-blue-500 bg-blue-50' : 'border-blue-300'"
                    @dragover.prevent="dragOverRuta = true" @dragleave.prevent="dragOverRuta = false"
                    @drop.prevent="dropSegmentoEnRuta">

                    <div v-for="(segmento, idx) in segmentosRuta" :key="segmento._tempId" draggable="true"
                      @dragstart="iniciarDragSegmento(segmento, 'ruta')" @dragend="finalizarDrag"
                      @dragover.prevent="setDragToIndex(idx)" @drop.prevent="dropSobreSegmento(idx)"
                      @click="seleccionarSegmento(segmento)"
                      class="p-1.5 mb-1 rounded cursor-move transition-all border-l-4 text-xs" :class="isSelected(segmento)
                        ? 'bg-blue-200 border-blue-600 shadow-md'
                        : 'bg-blue-100 border-blue-400 hover:bg-blue-150 hover:shadow-sm'">
                      <div class="flex items-center justify-between">
                        <div class="flex items-center">
                          <i class="fas fa-grip-vertical text-blue-600 mr-1 text-xs"></i>
                          <span class="font-semibold mr-1">{{ idx + 1 }}.</span>
                          <span>{{ segmento.nombre }}</span>
                        </div>
                        <span v-if="segmento.velocidad" class="text-[11px] text-blue-700">
                          {{ segmento.velocidad }} km/h
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Controles -->
                <div
                  class="lg:col-span-3 bg-purple-50 rounded-lg border border-purple-200 flex flex-col justify-between p-3"
                  style="height: 10cm;">
                  <h3 class="font-semibold text-center flex items-center justify-center mb-2 text-sm">
                    <i class="fas fa-sliders-h mr-1 text-purple-600"></i>Controles
                  </h3>

                  <!-- Botones -->
                  <div class="flex flex-col flex-1 justify-start space-y-2">
                    <div class="border-t border-gray-300 pt-2"></div>

                    <button @click="moverArribaSeleccionado"
                      :disabled="!segmentoSeleccionado || indiceSeleccionado === 0"
                      class="w-full py-1.5 rounded bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                      <i class="fas fa-arrow-up mr-1"></i>Arriba
                    </button>

                    <button @click="moverAbajoSeleccionado"
                      :disabled="!segmentoSeleccionado || indiceSeleccionado === segmentosRuta.length - 1"
                      class="w-full py-1.5 rounded bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                      <i class="fas fa-arrow-down mr-1"></i>Abajo
                    </button>

                    <div class="border-t border-gray-300 pt-2"></div>

                    <button @click="eliminarSeleccionado" :disabled="!segmentoSeleccionado"
                      class="w-full py-1.5 rounded bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                      <i class="fas fa-trash mr-1"></i>Eliminar
                    </button>

                    <div class="border-t border-gray-300 pt-2"></div>

                    <button @click="limpiarSegmentos" :disabled="segmentosRuta.length === 0"
                      class="w-full py-1.5 rounded bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                      <i class="fas fa-broom mr-1"></i>Limpiar
                    </button>
                  </div>

                  <!-- Validaciones -->
                  <div class="border-t border-gray-300 mt-3 pt-2">
                    <h4 class="text-xs font-semibold text-gray-700 mb-1">
                      <i class="fas fa-check-circle text-green-600 mr-1"></i>Validaciones
                    </h4>

                    <div class="text-xs space-y-1">
                      <div
                        v-if="segmentoSeleccionado && (!segmentoSeleccionado.mensaje || !segmentoSeleccionado.velocidad)"
                        class="p-1.5 rounded-lg bg-yellow-100 border border-yellow-300 text-yellow-800">
                        ⚠ Completa los campos del segmento seleccionado
                      </div>

                      <div v-else-if="segmentoSeleccionado"
                        class="p-1.5 rounded-lg bg-green-100 border border-green-300 text-green-800">
                        ✓ Segmento completo
                      </div>

                      <div v-if="segmentosRuta.length > 0"
                        class="p-1.5 rounded-lg bg-blue-100 border border-blue-300 text-blue-700">
                        Segmentos en ruta: <strong>{{ segmentosRuta.length }}</strong>
                      </div>

                      <div v-else class="p-1.5 rounded-lg bg-gray-100 border border-gray-300 text-gray-600">
                        No hay segmentos en la ruta
                      </div>
                    </div>
                  </div>
                </div>
              </div>


            </div>

            <!-- Footer modal -->
            <div class="border-t bg-gray-50 px-6 py-4 flex flex-col md:flex-row justify-end gap-3">
              <button @click="cerrarFormulario"
                class="px-6 py-2 border rounded-lg hover:bg-gray-100 transition-all font-medium w-full md:w-auto">
                <i class="fas fa-times mr-1"></i>Cancelar
              </button>
              <button @click="guardarRuta()"
                class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2">
                <!-- Mostrar loader si está cargando -->
                <template v-if="cargandoFormulario">
                  <div class="animate-spin rounded-full h-5 w-5 border-t-2 border-white border-solid"></div>
                  Guardando...
                </template>

                <!-- Mostrar texto normal si no está cargando -->
                <template v-else>
                  <i class="fas fa-save mr-1"></i> Guardar Ruta
                </template>
              </button>
            </div>

          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, nextTick } from "vue";
import axios from "axios";
import Header from "@/pages/Header.vue";
import Swal from "sweetalert2";

interface Segmento {
  id?: number;
  codsegmento?: number;
  nombre: string;
  mensaje?: string;
  velocidad?: number;
  velocidadPermitida?: number | string;
  color?: string;
  detalles?: string;
  _tempId?: number;
  segmento_codsegmento?: number;
}

interface DetalleRuta {
  id: number;
  ruta_codruta: number;
  segmento_codsegmento: number;
  nombre_segmento?: string;
  velocidadPermitida: string;
  mensaje?: string;
  created_at: string;
  updated_at: string;
}

interface Ruta {
  id?: number;
  codRuta?: number;
  codruta?: number;
  codigo?: number;
  cod_ruta?: number;
  nombre: string;
  limite: number;
  tipo: string;
  logoPreview: string;
  logoFile?: File;
  segmentos: Segmento[];
  mostrarSegmentos?: boolean;
  [key: string]: any;
}

type DragOrigen = 'disponibles' | 'ruta' | null;

const nombreUsuario = ref("Paulo Ramos");
const rutas = ref<Ruta[]>([]);
const filtro = ref("");
const paginaActual = ref(1);
const registrosPorPagina = 5;
const listaSegmentos = ref<DetalleRuta[]>([]);
const cargando = ref(false);
const errorCarga = ref("");

const formularioSegmentoAbierto = ref(false);
const cargandoFormulario = ref(false);
const logoInput = ref<HTMLInputElement | null>(null);

const nuevaRuta = reactive<Ruta>({
  nombre: "",
  limite: 0,
  tipo: "",
  logoPreview: "",
  logoFile: undefined,
  segmentos: [],
});

const segmentosRuta = ref<Segmento[]>([]);
const segmentosDisponibles = ref<Segmento[]>([]);
const segmentoSeleccionado = ref<Segmento | null>(null);
const filtroSegmentos = ref("");

const dragOverLogo = ref(false);
const dragOverDisponibles = ref(false);
const dragOverRuta = ref(false);

const draggedSegment = ref<Segmento | null>(null);
const dragOrigen = ref<DragOrigen>(null);
const dragToIndex = ref<number | null>(null);

function generarTempId(): number {
  return Date.now() + Math.random();
}

function asignarTempId(segmento: Segmento): Segmento {
  if (!segmento._tempId) segmento._tempId = generarTempId();
  return segmento;
}

function debugEstado() {
  console.log("=== DEBUG ESTADO ACTUAL ===");
  console.log("formularioSegmentoAbierto:", formularioSegmentoAbierto.value);
  console.log("segmentosDisponibles.value.length:", segmentosDisponibles.value.length);
  console.log("segmentosDisponibles.value:", segmentosDisponibles.value);
  console.log("segmentosDisponiblesFiltrados.value.length:", segmentosDisponiblesFiltrados.value.length);
  console.log("segmentosDisponiblesFiltrados.value:", segmentosDisponiblesFiltrados.value);
  console.log("filtroSegmentos.value:", filtroSegmentos.value);
  console.log("segmentosRuta.value:", segmentosRuta.value);
  console.log("=========================");
}

if (typeof window !== 'undefined') {
  (window as any).debugRutasEstado = debugEstado;
}

async function cargarSegmentos() {
  try {
    console.log("=== INICIANDO CARGA DE SEGMENTOS ===");
    cargando.value = true;
    errorCarga.value = "";

    const response = await axios.get("http://localhost:8000/api/segmentos", { timeout: 10000 });
    const data = response.data;

    console.log("Respuesta completa del API:", data);

    let arr: Segmento[] = [];
    if (Array.isArray(data)) arr = data;
    else if (Array.isArray(data.data)) arr = data.data;
    else if (Array.isArray(data.segmentos)) arr = data.segmentos;

    console.log("Total segmentos recibidos:", arr.length);

    if (arr.length === 0) {
      errorCarga.value = "No se encontraron segmentos";
      console.warn("⚠️ No hay segmentos disponibles");
      return;
    }

    function obtenerId(seg: Segmento): number | undefined {
      const posiblesIds = ["id", "ID", "Id", "segmento_id", "codsegmento"];
      for (const p of posiblesIds) {
        const value = (seg as any)[p];
        if (value !== undefined && value !== null && value !== 0) return value;
      }
      return undefined;
    }

    const segmentosConId = arr.filter(seg => {
      const id = obtenerId(seg);
      if (!id) console.warn("Segmento sin ID válido:", seg);
      else seg.id = id;
      return !!id;
    });

    segmentosDisponibles.value = segmentosConId.map(s => asignarTempId({ ...s }));

  } catch (error: any) {
    if (error.code === 'ECONNABORTED') {
      errorCarga.value = "Timeout: El servidor tardó demasiado en responder";
    } else if (error.message === 'Network Error') {
      errorCarga.value = "Error de red: Verifique que el servidor está corriendo en http://localhost:8000";
    } else {
      errorCarga.value = `Error: ${error.message}`;
    }

    segmentosDisponibles.value = [];
    alert(`Error al cargar los segmentos: ${errorCarga.value}`);

  } finally {
    cargando.value = false;
  }
}

async function cargarRutas() {
  try {
    console.log("=== INICIANDO CARGA DE RUTAS ===");
    cargando.value = true;

    const response = await axios.get("http://localhost:8000/api/rutas", {
      timeout: 10000
    });

    let arr: Ruta[] = Array.isArray(response.data)
      ? response.data
      : Array.isArray(response.data.data)
        ? response.data.data
        : Array.isArray(response.data.rutas)
          ? response.data.rutas
          : [];

    rutas.value = arr.map(r => {
      const idRuta = r.id || r.codRuta || r.codruta || r.codigo || r.cod_ruta;

      const segmentos: Segmento[] = (r.detalles ?? []).map((d: DetalleRuta) => {
        if (!d.segmento_codsegmento) {
          console.warn("Detalle sin ID de segmento:", d);
          return null;
        }
        return {
          id: d.segmento_codsegmento,
          mensaje: d.mensaje ?? "",
          velocidad: Number(d.velocidadPermitida) || 0,
          _tempId: generarTempId()
        };
      }).filter(Boolean) as Segmento[];

      return {
        ...r,
        id: idRuta,
        mostrarSegmentos: false,
        segmentos
      };
    });

  } catch (error: any) {
    console.error("Error al cargar las rutas:", error);

    if (error.code === 'ECONNABORTED') {
      errorCarga.value = "Timeout al cargar rutas";
    } else if (error.message === 'Network Error') {
      errorCarga.value = "Error de red al cargar rutas";
    }

    rutas.value = [];
    alert(`Error al cargar las rutas: ${error.message}`);

  } finally {
    cargando.value = false;
  }
}

const indiceSeleccionado = computed(() => {
  const seleccionado = segmentoSeleccionado.value;
  if (!seleccionado) return -1;
  return segmentosRuta.value.findIndex(
    (s) => (s._tempId ?? s.id) === (seleccionado._tempId ?? seleccionado.id)
  );
});

const segmentosDisponiblesFiltrados = computed(() => {
  const filtrados = segmentosDisponibles.value.filter(s =>
    s.nombre.toLowerCase().includes(filtroSegmentos.value.toLowerCase())
  );
  console.log("Segmentos filtrados:", filtrados.length, "de", segmentosDisponibles.value.length);
  return filtrados;
});

const rutasFiltradas = computed(() => {
  const filtradas = rutas.value.filter(r =>
    r.nombre.toLowerCase().includes(filtro.value.toLowerCase()) ||
    r.tipo.toLowerCase().includes(filtro.value.toLowerCase())
  );
  return filtradas;
});

const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(rutasFiltradas.value.length / registrosPorPagina))
);

const rutasPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina;
  return rutasFiltradas.value.slice(start, start + registrosPorPagina);
});

const velocidadPromedio = computed(() => {
  if (segmentosRuta.value.length === 0) return 0;
  const suma = segmentosRuta.value.reduce((acc, s) => acc + (s.velocidad || 0), 0);
  return Math.round(suma / segmentosRuta.value.length);
});

function isSelected(segmento: Segmento): boolean {
  return segmentoSeleccionado.value?._tempId === segmento._tempId;
}

watch(velocidadPromedio, (nuevoPromedio) => {
  nuevaRuta.limite = nuevoPromedio;
}, { immediate: true });

watch(filtro, () => {
  paginaActual.value = 1;
});

onMounted(async () => {
  try {
    await cargarSegmentos();
    await cargarRutas();
  } catch (error) {
    console.error("Error en la carga inicial:", error);
  }
});

async function abrirFormulario() {
  formularioSegmentoAbierto.value = true;
  cargandoFormulario.value = true;

  try {
    await cargarSegmentos();
    debugEstado();
  } catch (error) {
    console.error("Error cargando segmentos:", error);
  } finally {
    cargandoFormulario.value = false;
  }
}

function cerrarFormulario() {
  formularioSegmentoAbierto.value = false;
  limpiarFormulario();
}

function limpiarFormulario() {
  Object.assign(nuevaRuta, {
    nombre: "",
    limite: 0,
    tipo: "",
    logoPreview: "",
    logoFile: undefined,
    segmentos: [],
  });
  segmentosRuta.value = [];
  segmentoSeleccionado.value = null;
  dragOverLogo.value = false;
  dragOverRuta.value = false;
  dragOverDisponibles.value = false;
  draggedSegment.value = null;
  dragOrigen.value = null;
  dragToIndex.value = null;
  cargarSegmentos();
}

function seleccionarSegmento(segmento: Segmento) {
  segmentoSeleccionado.value = segmento;
}

function agregarSegmentoARuta(segmento: any) {
  if (!segmento) {
    console.error("Segmento inválido:", segmento);
    alert("Error: Segmento inválido");
    return;
  }

  const segId = segmento.id ?? segmento.codsegmento;

  if (!segId || segId === 0) {
    console.error("Segmento sin ID válido:", segmento);
    alert(`El segmento "${segmento.nombre}" no tiene un ID válido`);
    return;
  }

  const existe = segmentosRuta.value.some(s => (s.id ?? s.codsegmento) === segId);
  if (existe) {
    console.warn("Segmento ya existe en la ruta:", segmento.nombre);
    return;
  }

  segmentosRuta.value.push({
    ...segmento,
    id: segId,
    _tempId: generarTempId()
  });

  segmentosDisponibles.value = segmentosDisponibles.value.filter(
    s => (s.id ?? s.codsegmento) !== segId
  );

  console.log("Segmento agregado:", segmento.nombre);
}

function removerSegmento(segmento: Segmento) {
  if (!segmento) return;

  const idComparar = segmento._tempId ?? segmento.id;

  segmentosRuta.value = segmentosRuta.value.filter(
    s => (s._tempId ?? s.id) !== idComparar
  );

  const existeEnDisponibles = segmentosDisponibles.value.some(
    s => (s._tempId ?? s.id) === idComparar
  );

  if (!existeEnDisponibles) {
    segmentosDisponibles.value.unshift(asignarTempId(segmento));
  }

  if (segmentoSeleccionado.value?._tempId === segmento._tempId) {
    segmentoSeleccionado.value = null;
  }

  console.log("Segmento removido:", segmento.nombre);
}

function eliminarSeleccionado() {
  if (segmentoSeleccionado.value) {
    removerSegmento(segmentoSeleccionado.value);
  }
}

function limpiarSegmentos() {
  const segmentos = [...segmentosRuta.value];
  segmentos.forEach(segmento => {
    removerSegmento(segmento);
  });
}

function moverArribaSeleccionado() {
  if (!segmentoSeleccionado.value) return;

  const idComparar = segmentoSeleccionado.value._tempId ?? segmentoSeleccionado.value.id;
  const index = segmentosRuta.value.findIndex(
    s => (s._tempId ?? s.id) === idComparar
  );

  if (index > 0) {
    [segmentosRuta.value[index - 1], segmentosRuta.value[index]] =
      [segmentosRuta.value[index], segmentosRuta.value[index - 1]];
  }
}

function moverAbajoSeleccionado() {
  if (!segmentoSeleccionado.value) return;

  const idComparar = segmentoSeleccionado.value._tempId ?? segmentoSeleccionado.value.id;
  const index = segmentosRuta.value.findIndex(
    s => (s._tempId ?? s.id) === idComparar
  );

  if (index < segmentosRuta.value.length - 1) {
    [segmentosRuta.value[index], segmentosRuta.value[index + 1]] =
      [segmentosRuta.value[index + 1], segmentosRuta.value[index]];
  }
}

function cambiarPagina(nuevaPagina: number) {
  if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas.value) {
    paginaActual.value = nuevaPagina;
  }
}

async function guardarRuta() {
  if (cargandoFormulario.value) return;
  cargandoFormulario.value = true;

  if (!nuevaRuta.nombre.trim()) { alert("Por favor, ingrese un nombre para la ruta"); cargandoFormulario.value = false; return; }
  if (!nuevaRuta.tipo) { alert("Por favor, seleccione un tipo de ruta"); cargandoFormulario.value = false; return; }
  if (!nuevaRuta.limite || isNaN(Number(nuevaRuta.limite))) { alert("Por favor, ingrese un límite válido"); cargandoFormulario.value = false; return; }
  if (segmentosRuta.value.length === 0) { alert("Por favor, agregue al menos un segmento a la ruta"); cargandoFormulario.value = false; return; }

  const segmentosInvalidos = segmentosRuta.value.filter(s => s.id == null || s.id === 0);
  if (segmentosInvalidos.length > 0) {
    const nombres = segmentosInvalidos.map(s => s.nombre).join(", ");
    alert(`Los siguientes segmentos no tienen ID válido: ${nombres}`);
    cargandoFormulario.value = false;
    return;
  }

  const tipoMap: Record<string, string> = { general: "G", vuelta: "V" };
  const tipo = tipoMap[nuevaRuta.tipo.toLowerCase()] || "G";

  const formData = new FormData();
  formData.append("nombre", nuevaRuta.nombre.trim());
  formData.append("tipo", tipo);
  formData.append("limiteGeneral", String(nuevaRuta.limite));

  segmentosRuta.value.forEach((s, i) => {
    formData.append(`detalles[${i}][segmento_codsegmento]`, String(s.id));
    formData.append(`detalles[${i}][velocidadPermitida]`, String(s.velocidad || 0));
    formData.append(`detalles[${i}][mensaje]`, s.mensaje || '');
  });

  if (nuevaRuta.logoFile) {
    formData.append("icono", nuevaRuta.logoFile);
  }

  let url = "http://localhost:8000/api/rutas";
  if (nuevaRuta.id) {
    url = `http://localhost:8000/api/rutas/${nuevaRuta.id}`;
    formData.append("_method", "PUT");
  }

  try {
    const { data } = await axios.post(url, formData, {
      headers: { "Content-Type": "multipart/form-data" }
    });

    cerrarFormulario();
    await cargarRutas();
    return data;

  } catch (error: any) {
    console.error("Error al guardar/actualizar la ruta:", error);
    if (error.response?.status === 422) {
      const mensajes = Object.entries(error.response.data.errors)
        .map(([campo, msgs]) => `${campo}: ${Array.isArray(msgs) ? msgs.join(", ") : msgs}`)
        .join("\n");
      alert(`Errores de validación:\n\n${mensajes}`);
    } else if (error.response?.data?.message) {
      alert(`Error: ${error.response.data.message}`);
    } else {
      alert("Error al guardar la ruta. Por favor, intente nuevamente.");
    }
  }
  finally {
    cargandoFormulario.value = false;
  }
}

async function editarRuta(id?: number) {
  if (!id) return;

  formularioSegmentoAbierto.value = true;
  cargandoFormulario.value = true;

  await nextTick();

  try {
    const [rutaResp, segmentosResp] = await Promise.all([
      axios.get(`http://localhost:8000/api/rutas/${id}`),
      axios.get("http://localhost:8000/api/segmentos"),
    ]);

    const ruta = rutaResp.data;
    const segmentosAPI = Array.isArray(segmentosResp.data)
      ? segmentosResp.data
      : segmentosResp.data.segmentos ?? [];

    if (!ruta) {
      alert("No se encontró la ruta");
      return;
    }

    const segmentosMap = new Map<number, string>();
    for (const s of segmentosAPI) {
      const segId = s.codsegmento ?? s.id;
      if (segId) {
        segmentosMap.set(segId, s.nombre);
      }
    }

    const tipoSeleccionado =
      ruta.tipo === "G" ? "General" : ruta.tipo === "V" ? "Vuelta" : "Otro";

    const detallesRuta = ruta.detalles_ruta ?? ruta.detallesRuta ?? [];
    const segmentos: Segmento[] = detallesRuta
      .map((d: any) => {
        const segId = d.segmento_codsegmento ?? d.codsegmento ?? d.id;
        if (!segId) {
          console.warn("Segmento sin ID detectado:", d);
          return null;
        }
        return {
          id: segId,
          nombre: segmentosMap.get(segId) ?? d.nombre ?? `Segmento ${segId}`,
          mensaje: d.mensaje ?? "",
          velocidad: Number(d.velocidadPermitida) || 0,
          _tempId: generarTempId(),
        };
      })
      .filter((s: Segmento | null): s is Segmento => s !== null);

    const icono = ruta.icono ?? "";
    const logoPreviewUrl = icono
      ? icono.startsWith("http") || icono.startsWith("/storage")
        ? icono
        : `/storage/${icono}`
      : "";

    Object.assign(nuevaRuta, {
      id: ruta.codruta ?? ruta.id,
      nombre: ruta.nombre ?? "",
      tipo: tipoSeleccionado,
      color: ruta.color ?? "",
      logo: icono,
      logoPreview: logoPreviewUrl,
      limite: Number(ruta.limiteGeneral) || 0,
      segmentos,
    });

    const idsEnRuta = new Set(segmentos.map((s) => s.id));
    segmentosRuta.value = segmentos;

    const segmentosDisponiblesFiltered = segmentosAPI.filter((s: any) => {
      const segId = s.codsegmento ?? s.id;
      if (!segId) {
        console.warn("Segmento disponible sin ID:", s);
        return false;
      }
      return !idsEnRuta.has(segId);
    });

    segmentosDisponibles.value = segmentosDisponiblesFiltered;

    console.log("Ruta lista para edición:", nuevaRuta);
  } catch (error: any) {
    console.error("Error al cargar la ruta:", error);
    alert("No se pudo cargar la ruta para editar");
  } finally {
    cargandoFormulario.value = false;
  }
}

async function confirmarAccionRuta(id?: number) {
  if (!id || id <= 0) {
    console.error("ID de ruta inválido:", id);
    await Swal.fire({
      icon: "error",
      title: "Error",
      text: "El ID de la ruta no es válido",
    });
    return;
  }

  try {
    console.log(`=== CONFIRMANDO ACCIÓN PARA RUTA ${id} ===`);

    console.log(`Obteniendo ruta: http://localhost:8000/api/rutas/${id}`);
    const { data: ruta } = await axios.get(`http://localhost:8000/api/rutas/${id}`, {
      timeout: 10000
    });

    console.log("Ruta obtenida:", ruta);

    if (!ruta || !ruta.nombre) {
      throw new Error("La ruta no tiene datos válidos");
    }

    const detalles = ruta.detalles || ruta.detalles_ruta || [];
    if (!detalles || detalles.length === 0) {
      await Swal.fire({
        icon: "warning",
        title: "Ruta vacía",
        text: "Esta ruta no tiene segmentos para duplicar",
      });
      return;
    }

    console.log(`Ruta "${ruta.nombre}" tiene ${detalles.length} segmentos`);
    ruta.detalles_ruta = detalles;

    const resultado = await Swal.fire({
      title: `¿Qué deseas hacer con la ruta "${ruta.nombre}"?`,
      text: "Puedes duplicarla igual o crear la versión de regreso (segmentos invertidos).",
      icon: "question",
      showDenyButton: true,
      showCancelButton: true,
      confirmButtonText: "Duplicar igual",
      denyButtonText: "Ruta de regreso",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
      confirmButtonColor: "#2563eb",
      denyButtonColor: "#10b981",
      cancelButtonColor: "#6b7280",
      didOpen: () => {
        console.log("Modal de confirmación abierto");
      }
    });

    if (resultado.isConfirmed) {
      console.log("Usuario seleccionó: Duplicar igual");
      await duplicarRuta(ruta, false);

    } else if (resultado.isDenied) {
      console.log("Usuario seleccionó: Ruta de regreso");
      await duplicarRuta(ruta, true);

    } else if (resultado.isDismissed) {
      console.log("Usuario canceló la acción");
    }

    console.log("=== FIN CONFIRMACIÓN ===");

  } catch (error: any) {
    console.error("Error al obtener la ruta:", error);
    console.error("Detalles del error:", {
      message: error.message,
      response: error.response?.data,
      status: error.response?.status,
    });

    let mensajeError = "No se pudo cargar la información de la ruta";

    if (error.response?.data?.message) {
      mensajeError = error.response.data.message;
    } else if (error.message === "Network Error") {
      mensajeError = "Error de red: Verifica que el servidor esté en línea";
    } else if (error.code === "ECONNABORTED") {
      mensajeError = "Timeout: El servidor tardó demasiado en responder";
    } else if (error.response?.status === 404) {
      mensajeError = "La ruta no existe o ha sido eliminada";
    } else {
      mensajeError = error.message;
    }

    await Swal.fire({
      icon: "error",
      title: "Error al obtener la ruta",
      text: mensajeError,
      confirmButtonText: "Aceptar",
    });
  }
}

async function duplicarRuta(ruta: any, invertida = false): Promise<any> {
  try {
    console.log("Ruta recibida:", ruta);

    const rutaId = ruta?.id || ruta?.codruta;
    if (!rutaId) {
      throw new Error("La ruta no tiene un ID válido.");
    }

    let detalles = ruta.detalles || ruta.detalles_ruta;
    if (!Array.isArray(detalles) || detalles.length === 0) {
      console.log("Solicitando detalles al backend...");
      const { data } = await axios.get(`http://localhost:8000/api/rutas/${rutaId}`);
      detalles = data?.detallesRuta || [];
    }

    if (!Array.isArray(detalles) || detalles.length === 0) {
      throw new Error("La ruta no tiene segmentos para duplicar.");
    }

    const segmentosProcesados = invertida ? [...detalles].reverse() : [...detalles];

    const nuevoNombre = invertida
      ? `${ruta.nombre} (Vuelta)`
      : `${ruta.nombre} (Copia)`;

    const detallesData = segmentosProcesados.map((s: any, i: number) => ({
      segmento_codsegmento: s.segmento_codsegmento || s.codsegmento || s.id,
      velocidadPermitida: Number(s.velocidadPermitida) || 0,
      mensaje: s.mensaje || "",
      orden: i + 1
    }));

    const datosRuta = {
      nombre: nuevoNombre,
      tipo: invertida ? "V" : (ruta.tipo || "G"),
      limiteGeneral: Number(ruta.limiteGeneral) || 0,
      icono: ruta.icono || null,
      detalles: detallesData
    };

    console.log("Enviando datos a backend:", datosRuta);

    const { data: rutaGuardada } = await axios.post(
      "http://localhost:8000/api/duplicar",
      datosRuta,
      { headers: { "Content-Type": "application/json" } }
    );

    console.log("Ruta duplicada:", rutaGuardada);

    await Swal.fire({
      icon: "success",
      title: invertida
        ? "Ruta de regreso creada correctamente"
        : "Ruta duplicada correctamente",
      text: `"${nuevoNombre}" ha sido guardada exitosamente.`,
      confirmButtonText: "Aceptar"
    });

    return rutaGuardada;

  } catch (error: any) {
    console.error("Error al duplicar la ruta:", error);

    const mensaje =
      error.response?.data?.message ||
      error.response?.data?.error ||
      "Ocurrió un error inesperado.";

    await Swal.fire({
      icon: "error",
      title: "Error al guardar la ruta",
      text: mensaje,
      confirmButtonText: "Aceptar"
    });

    throw error;
  }
}

async function eliminarRuta(id?: number) {
  if (!confirm("¿Está seguro de eliminar esta ruta?")) return;

  try {
    const { data } = await axios.delete(`http://localhost:8000/api/rutas/${id}`);
    alert(data.message);
    await cargarRutas();
  } catch (error: any) {
    console.error("Error al eliminar la ruta:", error);

    if (error.response?.status === 405) {
      alert("Error: Método no permitido. Verifica la ruta DELETE en Laravel.");
    } else if (error.response?.data?.message) {
      alert(`Error: ${error.response.data.message}`);
    } else {
      alert("No se pudo eliminar la ruta.");
    }
  }
}

async function toggleSegmentos(ruta: Ruta) {
  ruta.mostrarSegmentos = !ruta.mostrarSegmentos;

  if (ruta.mostrarSegmentos && (!ruta.segmentos || ruta.segmentos.length === 0)) {
    try {
      const response = await axios.get(`http://localhost:8000/api/detallesRuta/${ruta.id}`);
      const detalles: DetalleRuta[] = Array.isArray(response.data) ? response.data : response.data.data ?? [];

      ruta.segmentos = await Promise.all(detalles.map(async d => {
        try {
          const segmentoRes = await axios.get(`http://localhost:8000/api/segmentos/${d.segmento_codsegmento}`);
          const nombreSegmento = segmentoRes.data?.nombre ?? `Segmento ${d.segmento_codsegmento}`;

          return {
            id: d.segmento_codsegmento,
            nombre: nombreSegmento,
            mensaje: d.mensaje ?? "",
            velocidad: Number(d.velocidadPermitida) || 0,
            _tempId: generarTempId()
          };
        } catch {
          return {
            id: d.segmento_codsegmento,
            nombre: `Segmento ${d.segmento_codsegmento}`,
            mensaje: d.mensaje ?? "",
            velocidad: Number(d.velocidadPermitida) || 0,
            _tempId: generarTempId()
          };
        }
      }));

    } catch (error) {
      console.error("Error cargando segmentos de la ruta:", error);
      ruta.segmentos = [];
    }
  }
}

function abrirSelectorLogo() {
  logoInput.value?.click();
}

function previewLogo(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files?.[0]) {
    cargarPreview(input.files[0]);
  }
}

function dropLogo(event: DragEvent) {
  dragOverLogo.value = false;
  const files = event.dataTransfer?.files;
  if (files?.[0]) {
    cargarPreview(files[0]);
  }
}

function cargarPreview(file: File) {
  if (!file.type.startsWith('image/')) return alert('Seleccione una imagen válida');
  if (file.size > 5 * 1024 * 1024) return alert('La imagen no debe superar los 5MB');

  const reader = new FileReader();
  reader.onload = e => {
    nuevaRuta.logoPreview = e.target?.result as string || "";
    nuevaRuta.logoFile = file;
  };
  reader.readAsDataURL(file);
}

function iniciarDragSegmento(segmento: Segmento, origen: 'disponibles' | 'ruta') {
  draggedSegment.value = segmento;
  dragOrigen.value = origen;
}

function finalizarDrag() {
  draggedSegment.value = null;
  dragOrigen.value = null;
  dragToIndex.value = null;
  dragOverRuta.value = false;
  dragOverDisponibles.value = false;
}

function setDragToIndex(index: number) {
  dragToIndex.value = index;
}

function dropSegmentoEnRuta(event?: DragEvent) {
  if (event) event.preventDefault();

  if (!draggedSegment.value) return;

  if (dragOrigen.value === 'disponibles') {
    agregarSegmentoARuta(draggedSegment.value);
  } else if (dragOrigen.value === 'ruta' && dragToIndex.value !== null) {
    const idComparar = draggedSegment.value._tempId ?? draggedSegment.value.id;
    const indexActual = segmentosRuta.value.findIndex(
      s => (s._tempId ?? s.id) === idComparar
    );

    if (indexActual !== -1 && indexActual !== dragToIndex.value) {
      const [moved] = segmentosRuta.value.splice(indexActual, 1);
      segmentosRuta.value.splice(dragToIndex.value, 0, moved);
    }
  }

  finalizarDrag();
}

function dropSegmentoEnDisponibles(event?: DragEvent) {
  if (event) event.preventDefault();

  if (!draggedSegment.value) return;

  if (dragOrigen.value === 'ruta') {
    removerSegmento(draggedSegment.value);
  }

  finalizarDrag();
}

function dropSobreSegmento(index: number) {
  dragToIndex.value = index;
}

function allowDrop(event: DragEvent) {
  event.preventDefault();
}
</script>

<style scoped>
/* ============================
   TRANSICIONES GENERALES
============================ */
.fade-enter-active,
.fade-leave-active,
.fade-slide-enter-active,
.fade-slide-leave-active,
.slide-up-enter-active,
.slide-up-leave-active,
.expand-enter-active,
.expand-leave-active,
.list-enter-active,
.list-leave-active,
.segment-enter-active,
.segment-leave-active,
.segment-list-enter-active,
.segment-list-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to,
.fade-slide-enter-from,
.fade-slide-leave-to,
.slide-up-enter-from,
.slide-up-leave-to,
.expand-enter-from,
.expand-leave-to,
.list-enter-from,
.list-leave-to,
.segment-enter-from,
.segment-leave-to,
.segment-list-enter-from,
.segment-list-leave-to {
  opacity: 0;
}

.fade-slide-enter-to,
.fade-slide-leave-from {
  opacity: 1;
  transform: translateY(0);
}

.slide-up-enter-from {
  transform: translateY(30px);
}

.slide-up-leave-to {
  transform: translateY(30px);
}

.expand-enter-active,
.expand-leave-active {
  max-height: 500px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
}

.list-enter-from,
.list-leave-to {
  transform: translateX(-30px);
}

.list-move,
.segment-move,
.segment-list-move {
  transition: transform 0.3s ease;
}

.segment-enter-from,
.segment-leave-to {
  transform: scale(0.8) translateY(-10px);
}

.segment-list-enter-from,
.segment-list-leave-to {
  transform: translateX(10px);
}

/* ============================
   ANIMACIONES
============================ */
@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.7;
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* ============================
   SMOOTH TRANSITIONS GLOBAL
============================ */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* ============================
   SCROLLBAR PERSONALIZADO
============================ */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e0;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #a0aec0;
}

/* Para Firefox */
* {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e0 transparent;
}
</style>
