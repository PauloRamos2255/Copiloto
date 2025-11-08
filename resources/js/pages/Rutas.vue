<template>
  <div class="min-h-screen bg-gray-100">
    <Header :nombreUsuario="nombreUsuario" />

    <main class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Gestión de Rutas</h1>
        <button @click="formularioSegmentoAbierto = true"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 flex items-center space-x-2 transition-all hover:shadow-lg">
          <i class="fas fa-plus"></i>
          <span>Crear Ruta</span>
        </button>
      </div>

      <!-- FILTROS -->
      <div class="mb-4 flex gap-2">
        <input type="text" placeholder="Filtrar por nombre..." v-model="filtro"
          class="px-3 py-2 border border-black rounded w-64 focus:ring focus:ring-blue-300 transition-all" />
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
            <transition-group name="list" tag="tbody">
              <template v-for="(ruta, index) in rutasPaginadas" :key="ruta.id">
                <tr class="border-b hover:bg-gray-50 transition-all cursor-pointer" @click="toggleSegmentos(ruta)">
                  <td class="px-4 py-2">{{ index + 1 + (paginaActual - 1) * registrosPorPagina }}</td>
                  <td class="px-4 py-2 font-medium">{{ ruta.nombre }}</td>
                  <td class="px-4 py-2">{{ ruta.limite }}</td>
                  <td class="px-4 py-2">{{ ruta.tipo }}</td>
                  <td class="px-4 py-2 text-center">
                    <button class="text-blue-500 hover:text-blue-700 mx-1 transition-colors"><i
                        class="fas fa-edit"></i></button>
                    <button @click.stop="eliminarRuta(ruta.id)"
                      class="text-red-500 hover:text-red-700 mx-1 transition-colors"><i
                        class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <transition name="expand">
                  <tr v-if="ruta.mostrarSegmentos" class="bg-gradient-to-r from-blue-50 to-transparent">
                    <td colspan="5" class="px-8 py-3">
                      <div class="space-y-1">
                        <transition-group name="segment-list" tag="div" class="space-y-1">
                          <div v-for="s in ruta.segmentos" :key="s.id + '-seg'"
                            class="p-2 bg-white rounded shadow-sm border-l-4 border-blue-500 hover:shadow-md transition-all">
                            • {{ s.nombre }}
                            <span v-if="s.mensaje || s.velocidad" class="text-sm text-gray-500 ml-2">
                              <span v-if="s.mensaje">({{ s.mensaje }})</span>
                              <span v-if="s.velocidad" class="ml-2">[{{ s.velocidad }} km/h]</span>
                            </span>
                          </div>
                        </transition-group>
                        <div v-if="ruta.segmentos.length === 0" class="text-gray-400 italic">Sin segmentos asignados
                        </div>
                      </div>
                    </td>
                  </tr>
                </transition>
              </template>
              <tr v-if="rutasFiltradas.length === 0" key="no-rutas">
                <td colspan="5" class="text-center py-4 text-gray-500">No hay rutas</td>
              </tr>
            </transition-group>
          </tbody>
        </table>
      </div>

      <!-- PAGINADO -->
      <div class="mt-4 flex justify-end space-x-2">
        <button @click="paginaActual--" :disabled="paginaActual === 1"
          class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-200 transition-all">Anterior</button>
        <span class="px-3 py-1">Página {{ paginaActual }} de {{ totalPaginas }}</span>
        <button @click="paginaActual++" :disabled="paginaActual === totalPaginas"
          class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-200 transition-all">Siguiente</button>
      </div>
    </main>

    <!-- FORMULARIO CREAR RUTA -->
    <transition name="fade">
      <div v-if="formularioSegmentoAbierto"
        class="fixed inset-0 bg-white/30 backdrop-blur-lg bg-opacity-30 flex items-center justify-center z-50">
        <transition name="slide-up">
          <div class="bg-white w-full max-w-5xl rounded shadow-lg max-h-[90vh] overflow-hidden flex flex-col">

            <!-- Encabezado con gradiente suave -->
            <div
              class="bg-gradient-to-r from-blue-500 to-blue-400 px-6 py-4 flex items-center justify-between shadow-sm">
              <h2 class="text-xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-routes text-blue-100"></i>
                Crear Nueva Ruta
              </h2>
              <button @click="cerrarFormulario"
                class="text-white hover:text-blue-50 transition-colors p-2 rounded-lg hover:bg-blue-400">
                <i class="fas fa-times text-lg"></i>
              </button>
            </div>

            <!-- Contenido scrollable -->
            <div class="flex-1 overflow-y-auto p-6 pr-2">
              <!-- Nombre, límite y tipo -->
              <div
                class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                <div>
                  <label class="block font-semibold mb-2 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-tag text-blue-600 text-sm"></i>
                    Nombre de la Ruta
                  </label>
                  <input type="text" v-model="nuevaRuta.nombre"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all placeholder-gray-400"
                    placeholder="Ej: Ruta Centro" />
                </div>
                <div>
                  <label class="block font-semibold mb-2 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-gauge-high text-blue-600 text-sm"></i>
                    Límite General
                  </label>
                  <div class="relative">
                    <input type="number" :value="nuevaRuta.limite" disabled
                      class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-12 bg-blue-50 cursor-not-allowed text-gray-700 font-semibold" />
                    <div
                      class="absolute right-0 top-0 bottom-0 bg-gradient-to-r from-blue-100 to-blue-200 rounded-r-lg flex items-center justify-center px-4 border-l border-gray-300">
                      <span class="text-sm font-semibold text-blue-700">km/h</span>
                    </div>
                  </div>
                  <p v-if="velocidadPromedio > 0" class="text-xs text-gray-600 mt-1">
                    <i class="fas fa-info-circle mr-1"></i>
                    Promedio de velocidades de segmentos
                  </p>
                </div>
                <div>
                  <label class="block font-semibold mb-2 text-gray-700 flex items-center gap-2">
                    <i class="fas fa-road text-blue-600 text-sm"></i>
                    Tipo de Ruta
                  </label>
                  <select v-model="nuevaRuta.tipo"
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all">
                    <option disabled value="">Seleccione un tipo</option>
                    <option>General</option>
                    <option>Vuelta</option>
                  </select>
                </div>
              </div>

              <!-- Logo -->
              <div class="mb-6 p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg border border-gray-200">
                <label class="block font-semibold mb-3 text-gray-700 flex items-center gap-2">
                  <i class="fas fa-image text-blue-600 text-sm"></i>
                  Logo de la Empresa
                </label>
                <div
                  class="border-2 border-dashed border-blue-400 rounded-lg h-32 flex items-center justify-center cursor-pointer relative hover:border-blue-600 transition-all"
                  @dragover.prevent="dragOverLogo = true" @dragleave.prevent="dragOverLogo = false"
                  @drop.prevent="dropLogo" @click="abrirSelectorLogo"
                  :class="dragOverLogo ? 'bg-blue-100 border-blue-600 shadow-lg' : 'bg-white hover:bg-blue-50'">
                  <input ref="logoInput" type="file" accept=".jpg,.jpeg,.png,.svg,.gif" class="hidden"
                    @change="previewLogo" />
                  <transition name="fade">
                    <span v-if="!nuevaRuta.logoPreview" class="text-gray-500 flex items-center gap-2">
                      <i class="fas fa-cloud-arrow-up text-xl"></i>
                      Arrastra tu logo aquí o haz click
                    </span>
                    <img v-else :src="nuevaRuta.logoPreview" class="h-full object-contain animate-pulse" />
                  </transition>
                </div>
              </div>

              <!-- Segmentos -->
              <div class="grid grid-cols-1 md:grid-cols-4 gap-8 flex-1 min-h-0 mb-4">
                <!-- Segmentos Disponibles -->
                <div
                  class="bg-gradient-to-br from-green-50 to-transparent rounded-lg p-5 border border-green-200 flex flex-col">
                  <h3 class="font-semibold mb-2 flex items-center text-sm">
                    <i class="fas fa-list mr-2 text-green-600"></i>
                    Disponibles
                  </h3>

                  <!-- Filtro de búsqueda -->
                  <div class="mb-3">
                    <input type="text" v-model="filtroSegmentos" placeholder="Buscar segmento..."
                      class="w-full px-3 py-2 border border-green-300 rounded focus:ring focus:ring-green-300 text-sm transition-all" />
                  </div>

                  <!-- Lista de segmentos -->
                  <div class="flex-1 overflow-y-auto p-4">
                    <div class="border-2 border-dashed border-green-300 rounded p-3 bg-white" :class="[
                      formularioSegmentoAbierto ? 'max-h-64' : 'max-h-80',
                      'overflow-y-auto transition-all duration-300',
                      dragOverDisponibles ? 'bg-green-50 border-green-500' : ''
                    ]" @dragover.prevent="dragOverDisponibles = true" @dragleave.prevent="dragOverDisponibles = false"
                      @drop.prevent="dropSegmentoEnDisponibles">
                      <transition-group name="segment" tag="div" class="space-y-1">
                        <div v-for="segmento in segmentosDisponiblesFiltrados" :key="'disp-' + segmento.id"
                          draggable="true" @dragstart="iniciarDragSegmento(segmento, 'disponibles')"
                          @dragend="finalizarDrag" @click="moverSegmentoARuta(segmento)"
                          class="p-3 bg-gradient-to-r from-green-100 to-green-50 rounded cursor-pointer shadow-sm border border-green-300 transition-all text-sm flex justify-between items-center hover:shadow-md hover:border-green-500 hover:scale-105 active:scale-95">
                          <div class="flex items-center">
                            <i class="fas fa-grip-vertical text-green-600 mr-2 text-xs"></i>
                            <span>{{ segmento.nombre }}</span>
                          </div>
                          <i class="fas fa-arrow-right text-green-600 text-xs"></i>
                        </div>
                      </transition-group>

                      <div v-if="segmentosDisponiblesFiltrados.length === 0"
                        class="text-center text-gray-400 py-4 text-xs">
                        {{ segmentosDisponibles.length === 0 ? 'Todos en uso' : 'No se encontraron resultados' }}
                      </div>
                    </div>

                  </div>

                </div>



                <!-- Segmentos en la Ruta -->
                <div
                  class="bg-gradient-to-br from-blue-50 to-transparent rounded-lg p-5 border border-blue-200 flex flex-col h-full col-span-2">
                  <h3 class="font-semibold mb-2 flex items-center text-sm">
                    <i class="fas fa-route mr-2 text-blue-600"></i>
                    En la Ruta
                  </h3>

                  <!-- Formulario para segmento seleccionado -->
                  <div v-if="segmentoSeleccionado && formularioSegmentoAbierto"
                    class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded p-3 mb-3 border border-yellow-300 space-y-2">
                    <div class="flex items-center justify-between mb-2">
                      <label class="text-xs font-semibold text-gray-700">Editando: <span class="text-blue-600">{{
                        segmentoSeleccionado.nombre }}</span></label>
                      <button @click="formularioSegmentoAbierto = false"
                        class="text-xs text-gray-600 hover:text-gray-800 p-1 rounded hover:bg-yellow-200 transition-all"
                        title="Cerrar formulario">
                        <i class="fas fa-chevron-up"></i>
                      </button>
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-gray-700">Mensaje</label>
                      <input type="text" :value="segmentoSeleccionado.mensaje"
                        @input="(e: any) => segmentoSeleccionado!.mensaje = e.target.value"
                        placeholder="Ej: Velocidad máxima 80km/h"
                        class="w-full border border-yellow-400 rounded px-2 py-1 text-xs focus:ring focus:ring-yellow-300 transition-all" />
                    </div>
                    <div>
                      <label class="text-xs font-semibold text-gray-700">Velocidad (km/h)</label>
                      <input type="number" :value="segmentoSeleccionado.velocidad"
                        @input="(e: any) => segmentoSeleccionado!.velocidad = Number(e.target.value)"
                        placeholder="Ej: 80" min="0"
                        class="w-full border border-yellow-400 rounded px-2 py-1 text-xs focus:ring focus:ring-yellow-300 transition-all" />
                    </div>
                  </div>

                  <!-- Botón para abrir/cerrar formulario -->
                  <div v-if="segmentoSeleccionado && !formularioSegmentoAbierto"
                    class="bg-yellow-50 rounded p-2 mb-3 border border-yellow-300">
                    <button @click="formularioSegmentoAbierto = true"
                      class="w-full text-xs font-semibold text-yellow-700 hover:text-yellow-800 p-2 rounded hover:bg-yellow-100 transition-all flex items-center justify-center gap-2">
                      <i class="fas fa-chevron-down"></i>
                      Abrir formulario para {{ segmentoSeleccionado.nombre }}
                      <i class="fas fa-chevron-down"></i>
                    </button>
                  </div>

                  <div class="border-2 dashed border-blue-300 rounded p-3 bg-white"
                    :class="[formularioSegmentoAbierto ? 'max-h-64' : 'max-h-80', 'overflow-y-auto', dragOverRuta ? 'bg-blue-50 border-blue-500' : '']"
                    @dragover.prevent="dragOverRuta = true; if (dragSource?.source === 'ruta') dragToIndex = segmentosRuta.length"
                    @dragleave.prevent="dragOverRuta = false" @drop.prevent="dropSegmentoEnRuta"
                    @dragend="finalizarDrag">
                    <transition-group name="segment" tag="div" class="space-y-1">
                      <template v-for="(segmento, idx) in segmentosRuta" :key="'ruta-' + segmento.id">
                        <!-- Mostrar placeholder cuando se arrastra sobre este índice -->
                        <div
                          v-if="dragFromIndex !== null && dragToIndex !== null && dragToIndex !== dragFromIndex && ((dragToIndex < dragFromIndex && idx <= dragToIndex) || (dragToIndex > dragFromIndex && idx > dragFromIndex && idx <= dragToIndex))"
                          class="p-3 border-2 border-dashed border-blue-400 rounded bg-blue-200 opacity-70 transition-all text-sm">
                          <div class="flex items-center justify-center text-blue-600">
                            <i class="fas fa-arrow-right mr-1"></i>
                            Nueva posición
                          </div>
                        </div>

                        <!-- Item arrastrado con sombra -->
                        <div draggable="true" @dragstart="iniciarDragSegmento(segmento, 'ruta', idx)"
                          @dragend="finalizarDrag" @dragover.prevent="dragToIndex = idx"
                          @dragleave.prevent="dragToIndex = null" @drop.prevent="dropSobreSegmento(idx)"
                          @click="segmentoSeleccionado = segmento" :class="[
                            'p-3 bg-gradient-to-r from-blue-100 to-blue-50 rounded cursor-move shadow-sm hover:shadow-md border-l-4 border-blue-500 transition-all duration-200 text-sm',
                            segmentoSeleccionado === segmento ? 'ring-2 ring-blue-500 bg-blue-200' : '',
                            dragFromIndex === idx ? 'opacity-40 scale-95 shadow-lg' : '',
                            dragToIndex === idx && dragFromIndex !== null ? 'scale-105 ring-2 ring-blue-400' : ''
                          ]">
                          <div class="flex items-center justify-between">
                            <span>
                              <i class="fas fa-grip-vertical text-blue-600 mr-1 text-xs"></i>
                              <span class="font-medium">{{ idx + 1 }}.</span>
                              {{ segmento.nombre }}
                            </span>
                            <i class="fas fa-check text-blue-600 text-xs"></i>
                          </div>

                        </div>
                      </template>
                    </transition-group>
                    <div v-if="segmentosRuta.length === 0" class="text-center text-gray-400 py-4 text-xs">
                      Arrastra aquí
                    </div>
                  </div>
                </div>

                <!-- Botones de Control -->
                <div
                  class="flex flex-col justify-start space-y-2 bg-gradient-to-br from-purple-50 to-transparent rounded-lg p-5 border border-purple-200 h-full overflow-y-auto">
                  <h3 class="font-semibold text-center flex items-center justify-center mb-1 text-sm">
                    <i class="fas fa-sliders-h mr-1 text-purple-600"></i>
                    Controles
                  </h3>

                  <button @click="moverArribaSeleccionado"
                    :disabled="!segmentoSeleccionado || segmentosRuta.indexOf(segmentoSeleccionado) === 0"
                    class="w-full p-2 rounded bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white transition-all hover:shadow-md disabled:cursor-not-allowed active:scale-95 text-xs"
                    title="Mover arriba">
                    <i class="fas fa-arrow-up mr-1"></i>Arriba
                  </button>

                  <button @click="moverAbajoSeleccionado"
                    :disabled="!segmentoSeleccionado || segmentosRuta.indexOf(segmentoSeleccionado) === segmentosRuta.length - 1"
                    class="w-full p-2 rounded bg-blue-500 hover:bg-blue-600 disabled:bg-gray-300 text-white transition-all hover:shadow-md disabled:cursor-not-allowed active:scale-95 text-xs"
                    title="Mover abajo">
                    <i class="fas fa-arrow-down mr-1"></i>Abajo
                  </button>

                  <div class="border-t border-gray-300 my-1"></div>

                  <button @click="eliminarSeleccionado" :disabled="!segmentoSeleccionado"
                    class="w-full p-2 rounded bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white transition-all hover:shadow-md disabled:cursor-not-allowed active:scale-95 text-xs"
                    title="Eliminar">
                    <i class="fas fa-trash mr-1"></i>Eliminar
                  </button>

                  <div class="border-t border-gray-300 my-1"></div>

                  <button @click="limpiarSegmentos" :disabled="segmentosRuta.length === 0"
                    class="w-full p-2 rounded bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 text-white transition-all hover:shadow-md disabled:cursor-not-allowed active:scale-95 text-xs"
                    title="Limpiar todo">
                    <i class="fas fa-broom mr-1"></i>Limpiar
                  </button>

                  <div class="mt-auto pt-3 space-y-2">
                    <h4 class="text-xs font-semibold text-gray-700 mb-2">
                      <i class="fas fa-check-circle text-green-600 mr-1"></i>
                      Validaciones
                    </h4>

                    <div class="text-xs space-y-2">
                      <!-- Validaciones del segmento seleccionado -->
                      <transition name="fade">
                        <div v-if="segmentoSeleccionado" class="space-y-2">
                          <!-- Si NO están completos -->
                          <div v-if="!segmentoSeleccionado.mensaje || !segmentoSeleccionado.velocidad">
                            <!-- Si AMBOS están vacíos, mostrar solo "Completar los campos" -->
                            <div v-if="!segmentoSeleccionado.mensaje && !segmentoSeleccionado.velocidad"
                              class="p-2 rounded-lg bg-yellow-100 border-2 border-yellow-300">
                              <div class="font-semibold text-xs flex items-center gap-1 text-yellow-700">
                                <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                <span>⚠ Completar los campos</span>
                              </div>
                            </div>

                            <!-- Si solo falta MENSAJE -->
                            <div v-else-if="!segmentoSeleccionado.mensaje"
                              class="p-2 rounded flex items-start gap-2 bg-red-100 border border-red-300">
                              <i class="fas fa-times text-red-600 mt-0.5"></i>
                              <div class="flex-1">
                                <div class="text-red-700 font-semibold">
                                  Mensaje
                                </div>
                                <div class="text-red-600 text-xs">
                                  ✗ Completar campo
                                </div>
                              </div>
                            </div>

                            <!-- Si solo falta VELOCIDAD -->
                            <div v-else-if="!segmentoSeleccionado.velocidad"
                              class="p-2 rounded flex items-start gap-2 bg-red-100 border border-red-300">
                              <i class="fas fa-times text-red-600 mt-0.5"></i>
                              <div class="flex-1">
                                <div class="text-red-700 font-semibold">
                                  Velocidad
                                </div>
                                <div class="text-red-600 text-xs">
                                  ✗ Completar campo
                                </div>
                              </div>
                            </div>
                          </div>

                          <!-- Si ESTÁN completos, mostrar segmento completado -->
                          <div v-else class="p-2 rounded-lg bg-green-100 border-2 border-green-400">
                            <div class="font-semibold text-xs flex items-center gap-1 text-green-700">
                              <i class="fas fa-check-circle text-green-600"></i>
                              <span>✓ Segmento completado</span>
                            </div>
                          </div>
                        </div>
                      </transition>

                      <!-- Información de segmentos (siempre visible) -->
                      <div v-if="segmentosRuta.length > 0"
                        class="mt-2 p-2 rounded-lg bg-blue-100 border border-blue-300">
                        <div class="font-semibold text-xs flex items-center gap-1 text-blue-700">
                          <i class="fas fa-list-check text-blue-600"></i>
                          <span>Segmentos en ruta: <strong>{{ segmentosRuta.length }}</strong></span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Sección de Botones - FIJA en la parte inferior -->
            <div
              class="flex-shrink-0 border-t border-gray-200 bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4 flex justify-end space-x-3">
              <button @click="guardarRuta"
                class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-8 py-2 rounded-lg transition-all hover:shadow-lg active:scale-95 font-semibold flex items-center gap-2">
                <i class="fas fa-save"></i>Guardar Ruta
              </button>
              <button @click="cerrarFormulario"
                class="bg-gradient-to-r from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600 text-white px-8 py-2 rounded-lg transition-all hover:shadow-lg active:scale-95 font-semibold flex items-center gap-2">
                <i class="fas fa-times"></i>Cancelar
              </button>
            </div>

          </div>
        </transition>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted } from "vue";
import axios from "axios";
import Header from "@/pages/Header.vue";

// --- Datos del usuario ---
const nombreUsuario = ref("Paulo Ramos");

// --- Interfaces ---
interface Segmento {
  id: number;
  nombre: string;
  mensaje?: string;
  velocidad?: number;
  color?: string; // ✅ agregado para evitar el error
}

interface Ruta {
  id?: number;
  nombre: string;
  limite: number;
  tipo: string;
  logoPreview: string;
  segmentos: Segmento[];
  mostrarSegmentos?: boolean;
}

// --- Estado General ---
const rutas = ref<Ruta[]>([]);
const filtro = ref("");
const paginaActual = ref(1);
const registrosPorPagina = 5;

// --- Formulario ---
const formularioSegmentoAbierto = ref(false);
const logoInput = ref<HTMLInputElement | null>(null);
const dragOverLogo = ref(false);
const dragOverDisponibles = ref(false);
const dragOverRuta = ref(false);
const dragOverItem = ref<number | null>(null);
const dragSource = ref<{ source: "disponibles" | "ruta"; segmento: Segmento; index?: number } | null>(null);
const dragFromIndex = ref<number | null>(null);
const dragToIndex = ref<number | null>(null);

const nuevaRuta = reactive<Ruta>({
  nombre: "",
  limite: 0,
  tipo: "",
  logoPreview: "",
  segmentos: [],
});

// --- Segmentos ---
const segmentosRuta = ref<Segmento[]>([]);
const segmentosDisponibles = ref<Segmento[]>([]);
const segmentoSeleccionado = ref<Segmento | null>(null);
const search = ref("");
const filtroSegmentos = ref("");





// --- Cargar segmentos desde API ---
const cargarSegmentos = async () => {
  try {
    const response = await axios.get("http://localhost:8000/api/segmentos");
    const data = response.data;

    if (Array.isArray(data)) {
      segmentosDisponibles.value = data;
    } else if (Array.isArray(data.data)) {
      segmentosDisponibles.value = data.data;
    } else if (Array.isArray(data.segmentos)) {
      segmentosDisponibles.value = data.segmentos;
    } else {
      console.error("El formato de datos no es un array:", data);
      segmentosDisponibles.value = [];
    }
  } catch (error) {
    console.error("Error al cargar los segmentos:", error);
    segmentosDisponibles.value = [];
  }
};

// --- Filtrado de segmentos ---
const segmentosFiltrados = computed(() => {
  const texto = search.value.toLowerCase();
  return Array.isArray(segmentosDisponibles.value)
    ? segmentosDisponibles.value.filter((s) =>
      s.nombre.toLowerCase().includes(texto)
    )
    : [];
});

// --- Selección de segmento ---
const seleccionarSegmento = (segmento: Segmento) => {
  segmentoSeleccionado.value = segmento;
  console.log("✅ Segmento seleccionado:", segmento);
};



onMounted(() => {
  cargarSegmentos();
});

// --- Funciones Formulario ---
function cerrarFormulario() {
  formularioSegmentoAbierto.value = false;
  limpiarFormulario();
}

function limpiarFormulario() {
  nuevaRuta.nombre = "";
  nuevaRuta.limite = 0;
  nuevaRuta.tipo = "";
  nuevaRuta.logoPreview = "";
  segmentosRuta.value = [];
  segmentoSeleccionado.value = null;
  cargarSegmentos();
}

// --- Mostrar/ocultar segmentos en tabla ---
function toggleSegmentos(ruta: Ruta) {
  ruta.mostrarSegmentos = !ruta.mostrarSegmentos;
}

const moverSegmentoARuta = (segmento: Segmento) => {
  if (!segmento) return;

  // Evitar duplicados
  if (segmentosRuta.value.some(s => s.id === segmento.id)) return;

  // Quitar sin romper reactividad
  const index = segmentosDisponibles.value.findIndex(s => s.id === segmento.id);
  if (index !== -1) segmentosDisponibles.value.splice(index, 1);

  // Agregar a la ruta
  segmentosRuta.value.push({ ...segmento });

  segmentoSeleccionado.value = segmento;
};






// --- Agregar y remover segmentos ---
function agregarSegmento(segmento: Segmento) {
  if (!segmentosRuta.value.find((s) => s.id === segmento.id)) {
    const nuevoSegmento: Segmento = { ...segmento };
    segmentosRuta.value.push(nuevoSegmento);
    removerDeDisponibles(segmento.id);
    segmentoSeleccionado.value = nuevoSegmento;
  }
}

function removerSegmento(segmento: Segmento) {
  segmentosRuta.value = segmentosRuta.value.filter((s) => s.id !== segmento.id);
  agregarADisponibles(segmento);
}

function removerDeDisponibles(id: number) {
  segmentosDisponibles.value = segmentosDisponibles.value.filter(
    (s) => s.id !== id
  );
}

function agregarADisponibles(segmento: Segmento) {
  if (!segmentosDisponibles.value.find((s) => s.id === segmento.id)) {
    segmentosDisponibles.value.push(segmento);
    segmentosDisponibles.value.sort((a, b) => a.id - b.id);
  }
}

function limpiarSegmentos() {
  segmentosRuta.value.forEach((s) => agregarADisponibles(s));
  segmentosRuta.value = [];
  segmentoSeleccionado.value = null;
}

// --- Drag & Drop ---
function iniciarDragSegmento(
  segmento: Segmento,
  source: "disponibles" | "ruta",
  index?: number
) {
  dragSource.value = { source, segmento, index };
  if (source === "ruta" && index !== undefined) {
    dragFromIndex.value = index;
  }
}

function finalizarDrag() {
  dragSource.value = null;
  dragFromIndex.value = null;
  dragToIndex.value = null;
  dragOverItem.value = null;
}

function dropSegmentoEnRuta() {
  if (!dragSource.value) return;

  const { source, segmento } = dragSource.value;
  if (!segmento) return;

  if (source === "disponibles") {
    moverSegmentoARuta(segmento); // ✅ reutiliza la misma lógica
  } else if (source === "ruta" && dragSource.value.index !== undefined && dragToIndex.value !== null) {
    const indexOrigen = dragSource.value.index;
    let indexDestino = dragToIndex.value;

    if (indexOrigen !== indexDestino) {
      if (indexDestino > indexOrigen) indexDestino--;
      const temp = segmentosRuta.value[indexOrigen];
      segmentosRuta.value.splice(indexOrigen, 1);
      segmentosRuta.value.splice(indexDestino, 0, temp);
      segmentoSeleccionado.value = temp;
    }
  }

  dragOverRuta.value = false;
  finalizarDrag();
}

function dropSegmentoEnDisponibles() {
  if (!dragSource.value || dragSource.value.source === "disponibles") return;

  const segmento = dragSource.value.segmento;
  removerSegmento(segmento);

  dragOverDisponibles.value = false;
  finalizarDrag();
}

function dropSobreSegmento(indexDestino: number) {
  if (
    !dragSource.value ||
    dragSource.value.source !== "ruta" ||
    dragSource.value.index === undefined
  )
    return;

  const indexOrigen = dragSource.value.index;

  if (indexOrigen !== indexDestino) {
    const temp = segmentosRuta.value[indexOrigen];
    segmentosRuta.value.splice(indexOrigen, 1);
    segmentosRuta.value.splice(indexDestino, 0, temp);
    segmentoSeleccionado.value = temp;
  }

  dragToIndex.value = null;
  finalizarDrag();
}

// --- Botones de movimiento ---
function moverArribaSeleccionado() {
  if (!segmentoSeleccionado.value) return;
  const index = segmentosRuta.value.findIndex(
    (s) => s.id === segmentoSeleccionado.value!.id
  );
  if (index > 0) {
    const temp = segmentosRuta.value[index - 1];
    segmentosRuta.value[index - 1] = segmentosRuta.value[index];
    segmentosRuta.value[index] = temp;
  }
}

function moverAbajoSeleccionado() {
  if (!segmentoSeleccionado.value) return;
  const index = segmentosRuta.value.findIndex(
    (s) => s.id === segmentoSeleccionado.value!.id
  );
  if (index < segmentosRuta.value.length - 1) {
    const temp = segmentosRuta.value[index + 1];
    segmentosRuta.value[index + 1] = segmentosRuta.value[index];
    segmentosRuta.value[index] = temp;
  }
}

function eliminarSeleccionado() {
  const seleccionado = segmentoSeleccionado.value;
  if (!seleccionado) return; // ✅ Verificación segura antes de acceder a sus propiedades

  // Buscar el índice del segmento dentro de la ruta
  const index = segmentosRuta.value.findIndex((s) => s.id === seleccionado.id);

  if (index !== -1) {
    // Removerlo de la ruta
    const [eliminado] = segmentosRuta.value.splice(index, 1);

    // Devolverlo a la lista de disponibles si no está ya
    if (!segmentosDisponibles.value.some((s) => s.id === eliminado.id)) {
      segmentosDisponibles.value.push(eliminado);
    }

    // Limpiar selección
    segmentoSeleccionado.value = null;
  }
}



// --- Guardar Ruta ---
function guardarRuta() {
  if (!nuevaRuta.nombre.trim()) {
    alert("Por favor, ingrese un nombre para la ruta");
    return;
  }

  const ruta: Ruta = {
    id: Date.now(),
    ...nuevaRuta,
    segmentos: [...segmentosRuta.value],
  };
  rutas.value.push(ruta);
  cerrarFormulario();
}

// --- Eliminar Ruta ---
function eliminarRuta(id?: number) {
  rutas.value = rutas.value.filter((r) => r.id !== id);
}

// --- Logo ---
function dropLogo(event: DragEvent) {
  dragOverLogo.value = false;
  const files = event.dataTransfer?.files;
  if (files && files.length > 0) cargarPreview(files[0]);
}

function abrirSelectorLogo() {
  logoInput.value?.click();
}

function previewLogo(event: Event) {
  const input = event.target as HTMLInputElement;
  if (input.files && input.files[0]) cargarPreview(input.files[0]);
}

function cargarPreview(file: File) {
  const reader = new FileReader();
  reader.onload = (e) => {
    nuevaRuta.logoPreview =
      typeof e.target?.result === "string" ? e.target.result : "";
  };
  reader.readAsDataURL(file);
}

// --- Filtrado y Paginación ---
const rutasFiltradas = computed(() =>
  rutas.value.filter((r) =>
    r.nombre.toLowerCase().includes(filtro.value.toLowerCase())
  )
);

const totalPaginas = computed(
  () => Math.ceil(rutasFiltradas.value.length / registrosPorPagina) || 1
);

const rutasPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina;
  return rutasFiltradas.value.slice(start, start + registrosPorPagina);
});

const segmentosDisponiblesFiltrados = computed(() =>
  segmentosDisponibles.value.filter((s) =>
    s.nombre.toLowerCase().includes(filtroSegmentos.value.toLowerCase())
  )
);

const velocidadPromedio = computed(() => {
  if (segmentosRuta.value.length === 0) return 0;
  const suma = segmentosRuta.value.reduce(
    (acc, s) => acc + (s.velocidad || 0),
    0
  );
  return Math.round(suma / segmentosRuta.value.length);
});

// --- Actualizar el límite automáticamente ---
watch(
  velocidadPromedio,
  (nuevoPromedio) => {
    nuevaRuta.limite = nuevoPromedio;
  },
  { immediate: true }
);
</script>


<style scoped>
/* TRANSICIONES */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: translateY(30px);
}

.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  max-height: 500px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.3s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(-30px);
}

.list-move {
  transition: transform 0.3s ease;
}

.segment-enter-active,
.segment-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.segment-enter-from,
.segment-leave-to {
  opacity: 0;
  transform: scale(0.8) translateY(-10px);
}

.segment-move {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.segment-list-enter-active,
.segment-list-leave-active {
  transition: all 0.3s ease;
}

.segment-list-enter-from,
.segment-list-leave-to {
  opacity: 0;
  transform: translateX(10px);
}

.segment-list-move {
  transition: transform 0.3s ease;
}

/* ANIMACIONES */
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

/* Smooth transitions */
* {
  transition-property: background-color, border-color, color, fill, stroke, opacity;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}

/* SCROLLBAR PERSONALIZADO */
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
  transition: background 0.3s ease;
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