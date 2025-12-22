<template>

  <div>
    <Loader v-if="loading" />
    <div v-else>
      <div class="min-h-screen bg-gray-100 font-sans">
        <!-- Header -->
        <Header :nombreUsuario="nombreUsuario" />

        <main class="p-4 md:p-6">

          <!-- Encabezado y botón crear ruta -->
          <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-3 md:space-y-0">
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


          <div class="bg-white/70 backdrop-blur-md border border-gray-200 shadow-xl rounded-2xl p-5 mb-6 
         max-w-4xl mx-auto transition-all">
            <h3 class="text-lg font-bold text-blue-400 mb-4 flex items-center">
              <i class="fas fa-filter mr-2"></i> Filtros de búsqueda
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

              <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-blue-300"></i>
                <input type="text" v-model="filtro" placeholder="Filtrar por nombre..." class="w-full pl-10 px-4 py-3 border border-blue-300 rounded-xl 
               focus:border-blue-400 focus:ring-4 focus:ring-blue-100 
               shadow-md hover:shadow-lg transition-all" />
              </div>

              <!-- Select de tipo -->
              <div class="relative">
                <i class="fas fa-user-tag absolute left-3 top-3 text-blue-300"></i>
                <select v-model="filtroTipo" class="w-full pl-10 px-4 py-3 border border-blue-300 rounded-xl bg-white
               focus:border-blue-400 focus:ring-4 focus:ring-blue-100 
               shadow-md hover:shadow-lg transition-all">
                  <option value="">Todos los tipos</option>
                  <option value="G">General</option>
                  <option value="V">Vuelta</option>
                </select>
              </div>

            </div>
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
                  <!-- Skeleton de tabla principal -->
                  <template v-if="cargando">
                    <tr v-for="n in 5" :key="'ruta-skel-' + n" class="border-b border-gray-100 animate-pulse">
                      <td class="px-6 py-4">
                        <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidth() }"></div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                      </td>
                      <td class="px-6 py-4">
                        <div class="h-4 bg-gray-300 rounded" :style="{ width: randomWidthSmall() }"></div>
                      </td>
                      <td class="px-6 py-4 text-center space-x-2 flex justify-center">
                        <div class="h-4 w-6 bg-gray-300 rounded"></div>
                        <div class="h-4 w-6 bg-gray-300 rounded"></div>
                        <div class="h-4 w-6 bg-gray-300 rounded"></div>
                      </td>
                    </tr>
                  </template>

                  <!-- Tabla real -->
                  <template v-else>
                    <template v-for="(ruta, index) in rutasPaginadas" :key="ruta.id">
                      <!-- Fila principal -->
                      <tr class="border-b border-gray-100 hover:bg-blue-50 transition-all cursor-pointer group"
                        @click="toggleSegmentos(ruta)">
                        <td class="px-6 py-4 font-semibold text-gray-600 group-hover:text-blue-600">
                          {{ index + 1 + (paginaActual - 1) * registrosPorPagina }}
                        </td>
                        <td class="px-6 py-4 font-medium text-gray-900 group-hover:text-blue-700">{{ ruta.nombre }}</td>
                        <td class="px-6 py-4 text-gray-700">
                          {{ isNaN(Number(ruta.limiteGeneral))
                            ? '-'
                            : Number(ruta.limiteGeneral) % 1 === 0
                              ? Number(ruta.limiteGeneral)
                              : Number(ruta.limiteGeneral).toFixed(2)
                          }} km/h
                        </td>

                        <td class="px-6 py-4">
                          <span :class="[
                            'px-3 py-1 rounded-full text-xs font-bold',
                            ruta.tipo === 'G' || ruta.tipo === 'General' ? 'bg-green-100 text-green-700' :
                              ruta.tipo === 'V' || ruta.tipo === 'Vuelta' ? 'bg-yellow-100 text-yellow-700' :
                                'bg-gray-100 text-gray-600'
                          ]">
                            {{ ruta.tipo === 'G' ? 'General' : ruta.tipo === 'V' ? 'Vuelta' : ruta.tipo }}
                          </span>
                        </td>
                        <td class="px-6 py-4 text-center space-x-2">
                          <button
                            class="p-2 rounded-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition-colors"
                            @click.stop="editarRuta(ruta.id)" title="Editar ruta">
                            <i class="fas fa-edit"></i>
                          </button>
                          <button class="p-2 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors"
                            @click.stop="confirmarAccionRuta(ruta.id)" title="Clonar ruta">
                            <i class="fas fa-clone"></i>
                          </button>
                          <button class="p-2 rounded-full bg-red-50 text-red-600 hover:bg-red-100 transition-colors"
                            @click.stop="eliminarRuta(ruta.id)" :disabled="rutaEnUso" :title="rutaEnUso
                              ? 'No se puede eliminar (está asignada)'
                              : 'Eliminar ruta'">
                            <i class="fas fa-trash"></i>
                          </button>
                        </td>
                      </tr>
                      <!-- Segmentos expandibles -->
                      <transition name="fade-slide">
                        <tr v-if="String(registroExpandido) === String(ruta.id) && ruta.mostrarSegmentos"
                          class="bg-gradient-to-b from-blue-50 to-blue-25 transition-all">
                          <td colspan="5" class="px-3 md:px-6 py-4">

                            <div v-if="!ruta.segmentos || ruta.segmentosCargando" class="flex flex-wrap gap-1.5">
                              <div v-for="n in 8" :key="'skel-seg-' + n"
                                class="bg-white rounded border border-gray-200 shadow-sm p-1.5 w-28 overflow-hidden">

                                <h4 class="font-semibold text-xs text-gray-800 truncate">
                                  <div class="h-2 bg-gray-300 rounded w-5/6 relative overflow-hidden">
                                    <div
                                      class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                    </div>
                                  </div>
                                </h4>

                                <p class="text-xs text-gray-600 truncate mt-1">
                                <div class="h-1.5 bg-gray-300 rounded w-3/4 relative overflow-hidden">
                                  <div
                                    class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                  </div>
                                </div>
                                </p>

                                <div class="flex items-center gap-0.5 text-xs text-blue-600 font-bold mt-1">
                                  <div class="h-1.5 bg-gray-300 rounded w-1/2 relative overflow-hidden flex-1">
                                    <div
                                      class="absolute inset-0 bg-gradient-to-r from-gray-300 via-gray-200 to-gray-300 animate-shimmer">
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>

                            <div v-else class="flex flex-wrap gap-1.5">
                              <div v-for="segmento in ruta.segmentos" :key="'saved-' + segmento._tempId"
                                class="bg-white rounded border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-400 transition-all p-1.5 w-28 overflow-hidden">

                                <h4
                                  class="font-semibold text-xs text-gray-800 truncate hover:text-blue-600 transition-colors"
                                  :title="segmento.nombre">
                                  {{ segmento.nombre || `Seg ${segmento.id}` }}
                                </h4>

                                <p v-if="segmento.mensaje" class="text-xs text-gray-600 truncate"
                                  :title="segmento.mensaje">
                                  {{ segmento.mensaje }}
                                </p>

                                <div v-if="segmento.velocidad"
                                  class="flex items-center gap-0.5 text-xs text-blue-600 font-bold">
                                  <i class="fas fa-tachometer-alt text-xs"></i>
                                  <span>{{ segmento.velocidad }}km/h</span>
                                </div>

                              </div>
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

                  </template>

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
          <div v-if="formularioSegmentoAbierto" class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @click.self="cerrarFormulario">

            <!-- Fondo oscuro -->
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

            <!-- Loader -->
            <div v-if="cargandoFormulario"
              class="fixed inset-0 z-[60] flex items-center justify-center bg-white/95 rounded-2xl">
              <div class="flex flex-col items-center gap-3">
                <div class="animate-spin rounded-full h-12 w-12 border-t-4 border-blue-600 border-solid"></div>
                <p class="text-sm font-semibold text-gray-700">Cargando...</p>
              </div>
            </div>

            <!-- Contenedor principal del formulario -->
            <div v-if="!cargandoFormulario"
              class="bg-white max-w-[1200px] w-full rounded-2xl shadow-2xl max-h-[90vh] flex flex-col overflow-hidden relative z-50">

              <!-- Header -->
              <div
                class="bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-4 flex items-center justify-between flex-shrink-0">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                  <i class="fas fa-route"></i>
                  {{ nuevaRuta.id ? "Editar Ruta" : "Crear Nueva Ruta" }}
                </h2>
                <button @click="cerrarFormulario" class="text-white hover:bg-blue-500 p-2 rounded-lg transition-colors"
                  type="button">
                  <i class="fas fa-times text-xl"></i>
                </button>
              </div>

              <!-- Contenido -->
              <div class="flex flex-1 overflow-hidden">

                <!-- Panel izquierdo -->
                <div class="lg:w-1/2 flex flex-col p-6 overflow-hidden">

                  <!-- Campos principales: Nombre, Límite, Tipo, Logo -->
                  <div class="grid grid-cols-12 gap-3 mb-4 border-b border-gray-300 pb-4">

                    <!-- Nombre -->
                    <div class="col-span-3 flex flex-col">
                      <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-tag text-blue-600 mr-1"></i>Nombre
                      </label>
                      <input type="text" v-model="nuevaRuta.nombre" placeholder="Ej: Ruta Centro" maxlength="100"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none h-10" />
                    </div>

                    <!-- Límite -->
                    <div class="col-span-3 flex flex-col">
                      <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-gauge-high text-blue-600 mr-1"></i>Límite
                      </label>

                      <div class="relative">
                        <input type="number" v-model.number="nuevaRuta.limite" placeholder="00" min="0" max="200"
                          step="1" @input="onLimiteInput"
                          class="w-full border border-gray-300 rounded-lg pl-3 pr-10 py-2 bg-white text-sm font-semibold h-10 focus:outline-none focus:ring-2 focus:ring-blue-500" />

                        <span
                          class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-semibold text-blue-700 pointer-events-none">
                          km/h
                        </span>
                      </div>
                    </div>

                    <!-- Tipo -->
                    <div class="col-span-3 flex flex-col">
                      <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-road text-blue-600 mr-1"></i>Tipo
                      </label>
                      <select v-model="nuevaRuta.tipo"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none h-10">
                        <option value="" disabled>Seleccione</option>
                        <option value="General">General</option>
                        <option value="Vuelta">Vuelta</option>
                      </select>
                    </div>

                    <!-- Logo -->
                    <div class="col-span-3 flex flex-col">
                      <label class="block text-sm font-semibold text-gray-700 mb-1">
                        <i class="fas fa-image text-blue-600 mr-1"></i>Logo
                      </label>

                      <div
                        class="border-2 border-dashed rounded-lg h-10 flex items-center justify-center cursor-pointer transition-all"
                        :class="dragOverLogo ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-blue-400 hover:bg-gray-50'"
                        @dragover.prevent="dragOverLogo = true" @dragleave.prevent="dragOverLogo = false"
                        @drop.prevent="dropLogo" @click="abrirSelectorLogo" role="button" tabindex="0"
                        @keydown.enter="abrirSelectorLogo">

                        <input type="file" ref="logoInput" accept=".png,.jpg,.jpeg,.gif,.webp,.svg" class="hidden"
                          @change="previewLogo" />

                        <div v-if="!nuevaRuta.logoPreview"
                          class="text-center text-gray-400 text-sm pointer-events-none">
                          <i class="fas fa-cloud-upload-alt text-lg"></i>
                        </div>

                        <img v-else :src="nuevaRuta.logoPreview" class="h-8 w-auto object-contain mx-auto rounded" />
                      </div>
                    </div>

                    <!-- CUADRO VELOCIDAD -->
                    <div v-if="nuevaRuta.id"
                      class="col-span-12 mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-[13px]">

                      <div class="flex items-center gap-5">

                        <span class="font-semibold text-gray-700">Velocidad sugerida:</span>

                        <!-- BD -->
                        <label class="flex items-center cursor-pointer gap-1">
                          <input type="radio" name="velocidadSeleccionada" value="bd" v-model="velocidadElegida"
                            class="scale-75" checked />
                          <span class="text-gray-600">BD:</span>
                          <span class="font-semibold text-green-600">{{ Rutadb.limite }} km/h</span>
                        </label>

                        <!-- Actual -->
                        <label class="flex items-center cursor-pointer gap-1">
                          <input type="radio" name="velocidadSeleccionada" value="actual" v-model="velocidadElegida"
                            class="scale-75" />
                          <span class="text-gray-600">Actual:</span>
                          <span class="font-semibold text-blue-600">{{ velocidadPromedio }} km/h</span>
                        </label>

                      </div>
                    </div>

                    <!-- MENSAJE DE AYUDA -->
                    <div class="col-span-12 text-xs text-gray-600 mt-1 pl-1 flex items-center gap-2">
                      <i class="fas fa-info-circle text-blue-500 flex-shrink-0"></i>
                      <span class="whitespace-nowrap">
                        Logo solo acepta: <strong>PNG, JPG, JPEG, GIF, WebP, SVG</strong>
                      </span>
                    </div>
                    <div class="col-span-12 text-xs text-gray-600 mt-1 pl-1 flex items-center gap-2">
                      <i class="fas fa-info-circle text-blue-500 flex-shrink-0"></i>
                      <span class="whitespace-nowrap">
                        Si ninguna opción te convence, puedes borrar el valor y escribir la velocidad manualmente.
                      </span>
                    </div>


                  </div>

                  <!-- Segmentos y controles -->
                  <div class="flex flex-1 gap-3 overflow-hidden  pt-3">

                    <!-- Segmentos disponibles -->
                    <div
                      class="w-1/2 bg-green-50 rounded-xl border border-green-200 flex flex-col p-3 shadow-sm overflow-hidden">
                      <h3 class="font-semibold mb-2 flex items-center text-sm text-green-700 flex-shrink-0">
                        <i class="fas fa-list mr-2 text-green-600"></i>Disponibles ({{
                          segmentosDisponiblesFiltrados.length
                        }})
                      </h3>
                      <input type="text" v-model="filtroSegmentos" placeholder="Buscar..."
                        class="w-full px-2 py-1 border border-green-300 rounded-lg mb-2 text-sm focus:ring-2 focus:ring-green-400 focus:outline-none flex-shrink-0" />
                      <div
                        class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-green-300 scrollbar-track-transparent"
                        :class="dragOverDisponibles ? 'border-green-500 bg-green-50' : 'border-green-300'"
                        @dragover.prevent="dragOverDisponibles = true" @dragleave.prevent="dragOverDisponibles = false"
                        @drop.prevent="dropSegmentoEnDisponibles">
                        <div v-for="segmento in segmentosDisponiblesFiltrados" :key="segmento._tempId" draggable="true"
                          @dragstart="iniciarDragSegmento(segmento, 'disponibles')" @dragend="finalizarDrag"
                          @dblclick="agregarSegmentoARuta(segmento)"
                          class="p-1 mb-1 bg-green-100 border border-green-300 rounded cursor-pointer hover:bg-green-200 hover:shadow transition-all text-xs flex items-center justify-between group">
                          <div class="flex items-center">
                            <i class="fas fa-grip-vertical text-green-600 mr-1 text-xs"></i>
                            <span>{{ segmento.nombre }}</span>
                          </div>
                          <i
                            class="fas fa-arrow-right text-green-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                        </div>
                      </div>
                    </div>

                    <!-- Segmentos en ruta y controles -->
                    <div
                      class="w-1/2 bg-blue-50 rounded-xl border border-blue-200 flex flex-col p-3 shadow-sm overflow-hidden">
                      <h3 class="font-semibold mb-2 flex items-center text-sm text-blue-700 flex-shrink-0">
                        <i class="fas fa-route mr-2 text-blue-600"></i>En la Ruta ({{ segmentosRuta.length }})
                      </h3>

                      <!-- Editor compacto -->
                      <div v-if="segmentoSeleccionado"
                        class="bg-yellow-50 rounded-lg p-2 mb-2 border border-yellow-300 flex flex-col gap-1 flex-shrink-0">
                        <div class="flex items-center justify-between mb-1">
                          <span class="text-xs font-semibold text-gray-700">✏ Editando: <span class="text-yellow-700">{{
                            segmentoSeleccionado.nombre }}</span></span>
                          <button @click="segmentoSeleccionado = null"
                            class="text-gray-600 hover:text-gray-800 text-xs p-1 rounded" type="button">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                        <div class="grid grid-cols-2 gap-1">
                          <div>
                            <label maxlength="500" class="text-[10px] font-semibold text-gray-700 block">Mensaje</label>
                            <input type="text" v-model="segmentoSeleccionado.mensaje" placeholder="Velocidad máxima"
                              class="w-full border border-yellow-400 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-yellow-400 focus:outline-none" />
                          </div>
                          <div>
                            <label class="text-[10px] font-semibold text-gray-700 block">Velocidad</label>
                            <input type="number" v-model.number="segmentoSeleccionado.velocidad" placeholder="00"
                              min="0" max="200" step="1" @input="segmentoSeleccionado.velocidad = segmentoSeleccionado.velocidad
                                ? Math.min(Math.max(Math.trunc(segmentoSeleccionado.velocidad), 0), 200)
                                : 0"
                              class="w-full border border-yellow-400 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-yellow-400 focus:outline-none" />
                          </div>

                        </div>
                      </div>

                      <!-- Lista de segmentos en ruta -->
                      <div
                        class="border-2 border-dashed rounded-lg p-1.5 bg-white overflow-y-auto flex-1 scrollbar-thin scrollbar-thumb-blue-300 scrollbar-track-transparent"
                        :class="dragOverRuta ? 'border-blue-500 bg-blue-50' : 'border-blue-300'"
                        @dragover.prevent="dragOverRuta = true" @dragleave.prevent="dragOverRuta = false"
                        @drop.prevent="dropSegmentoEnRuta">
                        <div v-for="(segmento, idx) in segmentosRuta" :key="segmento._tempId" draggable="true"
                          @dragstart="iniciarDragSegmento(segmento, 'ruta')" @dragend="finalizarDrag"
                          @dragover.prevent="setDragToIndex(idx)" @drop.prevent="dropSobreSegmento(idx)"
                          @click="() => { seleccionarSegmento(segmento); centrarEnSegmento(segmento); }"
                          class="p-1 mb-1 rounded cursor-move transition-all border-l-4 text-xs"
                          :class="isSelected(segmento) ? 'bg-blue-200 border-blue-600 shadow-md' : 'bg-blue-100 border-blue-400 hover:bg-blue-150 hover:shadow-sm'">
                          <div class="flex items-center justify-between">
                            <div class="flex items-center">
                              <i class="fas fa-grip-vertical text-blue-600 mr-1 text-xs"></i>
                              <span class="font-semibold mr-1">{{ idx + 1 }}.</span>
                              <span>{{ segmento.nombre }}</span>
                            </div>
                            <span v-if="segmento.velocidad" class="text-[10px] text-blue-700">{{ segmento.velocidad }}
                              km/h</span>
                          </div>
                        </div>
                      </div>

                      <!-- Botones y validaciones -->
                      <div class="mt-2 flex flex-col gap-2">
                        <div class="flex gap-2">
                          <button @click="eliminarSeleccionado" :disabled="!segmentoSeleccionado"
                            class="flex-1 py-1.5 rounded bg-red-500 hover:bg-red-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                            <i class="fas fa-trash mr-1"></i>Eliminar
                          </button>
                          <button @click="limpiarSegmentos" :disabled="segmentosRuta.length === 0"
                            class="flex-1 py-1.5 rounded bg-yellow-500 hover:bg-yellow-600 disabled:bg-gray-300 text-white text-xs font-semibold transition-all">
                            <i class="fas fa-broom mr-1"></i>Limpiar
                          </button>
                        </div>
                        <div class="border-t border-gray-300 pt-1 text-xs space-y-1">
                          <div
                            v-if="segmentoSeleccionado && (!segmentoSeleccionado.mensaje || !segmentoSeleccionado.velocidad)"
                            class="p-1 rounded-lg bg-yellow-100 border border-yellow-300 text-yellow-800">
                            Completa los campos
                          </div>
                          <div v-else-if="segmentoSeleccionado"
                            class="p-1 rounded-lg bg-green-100 border border-green-300 text-green-800">
                            Segmento completo
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>

                </div>

                <!-- Panel derecho: Mapa -->
                <div class="lg:w-1/2 p-3 flex flex-col">
                  <div class="flex flex-col items-center mb-3">
                    <h3 class="font-semibold text-center text-base flex items-center justify-center text-gray-700 mb-1">
                      <i class="fas fa-map-marked-alt mr-2 text-blue-600 text-lg"></i>
                      Vista de la Ruta
                    </h3>
                    <div class="w-2/3 border-t-2 border-blue-400"></div>
                  </div>
                  <LMap ref="mapaRef" :zoom="zoom" :center="center"
                    style="height: calc(100% - 40px); width: 100%; border-radius: 12px; overflow: hidden;">

                    <!-- Capa de Google Maps -->
                    <LTileLayer :url="'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'"
                      :subdomains="['mt0', 'mt1', 'mt2', 'mt3']" :attribution="'© Google'" />

                    <!-- Polígonos y markers de segmentos -->
                    <template v-for="segmento in segmentosRuta" :key="segmento.id || segmento._tempId">
                      <LPolygon v-if="segmento.cordenadas?.length" :lat-lngs="segmento.cordenadas.map(c => [c.y, c.x])"
                        :color="convertirColorConAlpha(segmento.color || '#0000ff', 1)"
                        :fill-color="convertirColorConAlpha(segmento.color || '#0000ff', 0.4)" :fill-opacity="0.4"
                        :weight="3" />


                      <LMarker v-if="segmento.cordenadas?.length" :lat-lng="calcularCentro(segmento.cordenadas)"
                        :icon="crearCardIcon(segmento.nombre)" />
                    </template>
                  </LMap>

                </div>

              </div>

              <!-- Footer -->
              <div class="border-t bg-gray-50 px-6 py-4 flex flex-col md:flex-row justify-end gap-3">
                <button @click="cerrarFormulario" type="button"
                  class="px-6 py-2 border rounded-lg hover:bg-gray-100 transition-all font-medium w-full md:w-auto text-sm">
                  <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button @click="guardarRuta()" type="button"
                  class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-all font-medium shadow-md hover:shadow-lg w-full md:w-auto flex items-center justify-center gap-2 text-sm">
                  <i class="fas fa-save mr-1"></i> Guardar Ruta
                </button>
              </div>

            </div>
          </div>
        </transition>

      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, nextTick, onUnmounted } from "vue";
import axios from "axios";
import Header from "@/pages/Header.vue";
import Loader from "@/pages/Loader.vue";
import Swal from "sweetalert2";
// imports
import { LMap, LTileLayer, LMarker, LPolygon } from '@vue-leaflet/vue-leaflet';

import L from 'leaflet';

import "leaflet/dist/leaflet.css";

const loading = ref(true)

interface Punto {
  x: number; // longitud
  y: number; // latitud
}

interface Segmento {
  id?: number;
  codsegmento?: number;
  nombre: string;
  mensaje?: string;
  velocidad?: number;
  velocidadPermitida?: number | string;
  color?: string;
  detalles?: string;
  _tempId?: string;
  segmento_codsegmento?: number;
  cordenadas?: Punto[];
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
const filtroTipo = ref("");
const paginaActual = ref(1);
const registrosPorPagina = ref(7);
const listaSegmentos = ref<DetalleRuta[]>([]);
const cargando = ref(true);
const errorCarga = ref("");
const registroExpandido = ref<number | null>(null);
const formularioSegmentoAbierto = ref(false);
const cargandoFormulario = ref(false);
const logoInput = ref<HTMLInputElement | null>(null);
const todosSegmentos = ref<Segmento[]>([]);



//MAPA
// Configuración del mapa
const url = 'https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}'; // 'm' = mapa normal
const subdomains = ['mt0', 'mt1', 'mt2', 'mt3']; // subdominios de Google
const attribution = '© Google';
const zoom = ref(13);
const center = ref<[number, number]>([-12.0555785, -76.9816634]);
const markerLatLng = [-12.0464, -77.0428];



// Generar anchos aleatorios
const randomWidth = () => `${Math.floor(Math.random() * 12 + 20)}ch`;
const randomWidthSmall = () => `${Math.floor(Math.random() * 8 + 12)}ch`;







const nuevaRuta = reactive<Ruta>({
  nombre: "",
  limite: 0,
  tipo: "",
  logoPreview: "",
  logoFile: undefined,
  segmentos: [],
});

const Rutadb = reactive<Ruta>({
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
let tempIdCounter = 0;

const rutaEnUso = ref(false)

const mapaRef = ref<InstanceType<typeof LMap> | null>(null);

const actualizarMapa = async () => {
  await nextTick();
  if (mapaRef.value && mapaRef.value.leafletObject) {
    mapaRef.value.leafletObject.invalidateSize();
  } else {
    console.warn("No se encontró instancia del mapa para actualizar");
  }
};

watch(() => formularioSegmentoAbierto.value, (nuevoValor) => {
  if (nuevoValor) {
    setTimeout(actualizarMapa, 400);
  }
});

const verificarRuta = async (idRuta: 0) => {
  try {
    const res = await axios.get(`api/verificaruta/${idRuta}`)
    rutaEnUso.value = res.data.success // true si está asignada
  } catch (error) {
    console.error(error)
    rutaEnUso.value = false
  }
}




function generarTempId() {
  tempIdCounter++;
  return `segmento_${Date.now()}_${tempIdCounter}`;
}


function asignarTempId(segmento: Segmento): Segmento {
  if (!segmento._tempId) segmento._tempId = generarTempId();
  return segmento;
}



async function cargarSegmentos() {
  try {
    cargando.value = true;
    errorCarga.value = "";

    const response = await axios.get("api/segmentos", { timeout: 10000 });
    const data = response.data;

    // Intentamos obtener el array de segmentos
    let arr: Segmento[] = [];
    if (Array.isArray(data)) arr = data;
    else if (Array.isArray(data.data)) arr = data.data;
    else if (Array.isArray(data.segmentos)) arr = data.segmentos;

    if (arr.length === 0) {
      errorCarga.value = "No se encontraron segmentos";
      console.warn("No hay segmentos disponibles");
      todosSegmentos.value = [];
      segmentosDisponibles.value = [];
      return;
    }


    // Función para obtener un ID válido
    function obtenerId(seg: Segmento): number | undefined {
      const posiblesIds = ["id", "ID", "Id", "segmento_id", "codsegmento"];
      for (const p of posiblesIds) {
        const value = (seg as any)[p];
        if (value !== undefined && value !== null && value !== 0) return value;
      }
      return undefined;
    }

    // Filtramos segmentos válidos y asignamos ID
    const segmentosConId = arr
      .filter(seg => {
        const id = obtenerId(seg);
        if (!id) console.warn("Segmento sin ID válido:", seg);
        else seg.id = id;
        return !!id;
      })
      .map(s => ({ ...s, _tempId: generarTempId() })); // asignamos _tempId

    // Guardamos como lista maestra
    todosSegmentos.value = segmentosConId;

    // Inicializamos la lista de disponibles considerando los que ya están en la ruta
    resetearSegmentosDisponibles();
  } catch (error: any) {
    if (error.code === 'ECONNABORTED') {
      errorCarga.value = "Timeout: El servidor tardó demasiado en responder";
    } else if (error.message === 'Network Error') {
      errorCarga.value = "Error de red: Verifique su conexion al servidor ";
    } else {
      errorCarga.value = `Error: ${error.message}`;
    }

    todosSegmentos.value = [];
    segmentosDisponibles.value = [];
    alert(`Error al cargar los segmentos: ${errorCarga.value}`);
  } finally {
    cargando.value = false;
  }
}

// Recalcular los segmentos disponibles según los que ya están en la ruta
function resetearSegmentosDisponibles() {
  // IDs de los segmentos que ya están en la ruta (precargados o agregados)
  const idsEnRuta = new Set([
    ...segmentosRuta.value.map((s: Segmento) => s.id),
    ...(nuevaRuta.segmentos?.map((s: Segmento) => s.id) || [])
  ]);

  // Filtrar todos los segmentos para mostrar solo los disponibles
  segmentosDisponibles.value = todosSegmentos.value
    .filter((s: Segmento) => !idsEnRuta.has(s.id))
    .map(s => ({ ...s, _tempId: generarTempId() }));
}



async function cargarRutas() {
  cargando.value = true;
  errorCarga.value = "";
  rutas.value = [];

  const inicio = performance.now(); // ⏱ inicio

  try {
    const { data } = await axios.get("api/rutas");

    // tu lógica...
    const arr = Array.isArray(data) ? data : data.data || data.rutas || [];
    rutas.value = arr.map((r: any) => ({ ...r }));

  } catch (error) {
    console.error("Error al cargar rutas:", error);
  } finally {
    const fin = performance.now(); // ⏱ fin
    const duracion = (fin - inicio) / 1000; // en segundos
    console.log(`cargarRutas() tardó ${duracion.toFixed(2)} segundos`);
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

const tipoMap: Record<string, string> = { G: "General", V: "Vuelta" };
const rutasFiltradas = computed(() => {
  return rutas.value.filter(ruta => {
    const tipoNombre = tipoMap[ruta.tipo] ?? ruta.tipo;

    // Filtrar por texto
    const texto = filtro.value.trim().toLowerCase();
    const textoCoincide =
      !texto ||
      ruta.nombre.toLowerCase().includes(texto) ||
      tipoNombre.toLowerCase().includes(texto);

    // Filtrar por tipo select
    const tipoSeleccionado = filtroTipo.value.trim();
    const tipoCoincide = !tipoSeleccionado || tipoNombre === (tipoMap[tipoSeleccionado] ?? tipoSeleccionado);

    return textoCoincide && tipoCoincide;
  });
});





const totalPaginas = computed(() =>
  Math.max(1, Math.ceil(rutasFiltradas.value.length / registrosPorPagina.value))
);

// Rutas visibles según la página y registros por pantalla
const rutasPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * registrosPorPagina.value;
  return rutasFiltradas.value.slice(start, start + registrosPorPagina.value);
});

const props = defineProps<{
  ruta?: { limite?: number | null };
}>();


const limiteBD = ref<number | null>(null);
const velocidadesOriginalesBD = ref<number[]>([]);

const velocidadPromedio = computed(() => {
  if (!segmentosRuta.value || segmentosRuta.value.length === 0) return 0;
  const suma = segmentosRuta.value.reduce((acc, s) => acc + (s.velocidad ?? 0), 0);
  return Math.min(Math.round(suma / segmentosRuta.value.length), 200);
});





const velocidadElegida = ref(null);



const limiteEditado = ref(false);

// --- WATCH PARA CAMBIO DE RADIO ---
watch(velocidadElegida, (opcion) => {
  if (opcion === "bd") {
    nuevaRuta.limite = Rutadb.limite;
  }

  if (opcion === "actual") {
    nuevaRuta.limite = velocidadPromedio.value;
  }
});

// --- INPUT MANUAL DEL USUARIO ---
const onLimiteInput = () => {
  limiteEditado.value = true;

  // Limpia el radio seleccionado porque el usuario escribe manualmente
  velocidadElegida.value = null;

  if (nuevaRuta.limite != null) {
    nuevaRuta.limite = Math.min(
      Math.max(Math.trunc(nuevaRuta.limite), 0),
      200
    );
  } else {
    nuevaRuta.limite = 0;
  }
};




// Función auxiliar
function isSelected(segmento: Segmento): boolean {
  return segmentoSeleccionado.value?._tempId === segmento._tempId;
}


watch(filtro, () => {
  paginaActual.value = 1;
});

onMounted(async () => {
  loading.value = true

  await cargarSegmentos();
  await cargarRutas();
  calcularRegistrosPorPantalla();
  window.addEventListener('resize', calcularRegistrosPorPantalla);
  loading.value = false

});
onUnmounted(() => {
  window.removeEventListener('resize', calcularRegistrosPorPantalla);
});

async function abrirFormulario() {
  formularioSegmentoAbierto.value = true;
  cargandoFormulario.value = true;

  try {
    await cargarSegmentos();
  } catch (error) {
    console.error("Error cargando segmentos:", error);
  } finally {
    cargandoFormulario.value = false;
  }
}

function cerrarFormulario() {
  formularioSegmentoAbierto.value = false;

  // Limpiar formulario y ruta
  limpiarFormulario();

  // Restaurar todos los segmentos disponibles después de limpiar
  resetearSegmentosDisponibles();
}

function limpiarFormulario() {
  Object.assign(nuevaRuta, {
    id: 0,
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

function calcularRegistrosPorPantalla() {
  const altoPantalla = window.innerHeight;
  const altoCabecera = 150; // navbar + headers
  const altoFila = 56; // altura aproximada de cada fila
  const maxRegistros = Math.floor((altoPantalla - altoCabecera) / altoFila);
  registrosPorPagina.value = maxRegistros > 0 ? maxRegistros : 5;
}

function cambiarPagina(nuevaPagina: number) {
  if (nuevaPagina >= 1 && nuevaPagina <= totalPaginas.value) {
    paginaActual.value = nuevaPagina;
  }
}

async function guardarRuta() {
  if (cargandoFormulario.value) return;
  cargandoFormulario.value = true;

  // Validaciones básicas de la ruta
  if (!nuevaRuta.nombre.trim()) {
    toast.error("Por favor, ingrese un nombre para la ruta");
    cargandoFormulario.value = false;
    return;
  }

  if (!nuevaRuta.tipo) {
    toast.error("Por favor, seleccione un tipo de ruta");
    cargandoFormulario.value = false;
    return;
  }


  if (
    !nuevaRuta.limite ||
    isNaN(Number(nuevaRuta.limite)) ||
    Number(nuevaRuta.limite) <= 0 ||
    Number(nuevaRuta.limite) >= 201
  ) {
    toast.error("El límite debe ser un número mayor a 0 y menor a 200");
    cargandoFormulario.value = false;
    return;
  }

  if (segmentosRuta.value.length === 0) {
    toast.error("Por favor, agregue al menos un segmento a la ruta");
    cargandoFormulario.value = false;
    return;
  }

  // Validar segmento actualmente en edición (si existe)
  if (segmentoSeleccionado.value) {
    const errores = [];

    if (!segmentoSeleccionado.value.mensaje || segmentoSeleccionado.value.mensaje.trim() === '') {
      errores.push('El mensaje del segmento en edición es requerido');
    }

    if (segmentoSeleccionado.value.velocidad === null ||
      segmentoSeleccionado.value.velocidad === undefined ||
      segmentoSeleccionado.value.velocidad < 0) {
      errores.push('La velocidad del segmento en edición debe ser mayor o igual a 0');
    }

    if (errores.length > 0) {
      toast.error(errores.join('\n'));
      cargandoFormulario.value = false;
      return;
    }
  }

  // Validar todos los segmentos de la ruta
  const segmentosInvalidos = segmentosRuta.value.filter(s => s.id == null || s.id === 0);
  if (segmentosInvalidos.length > 0) {
    const nombres = segmentosInvalidos.map(s => s.nombre).join(", ");
    toast.error(`Los siguientes segmentos no tienen ID válido: ${nombres}`);
    cargandoFormulario.value = false;
    return;
  }

  // Validar que todos los segmentos tengan mensaje y velocidad
  const segmentosSinMensaje = segmentosRuta.value.filter(s =>
    !s.mensaje || s.mensaje.trim() === ''
  );
  if (segmentosSinMensaje.length > 0) {
    const nombres = segmentosSinMensaje.map(s => s.nombre).join(", ");
    toast.error(`Los siguientes segmentos no tienen mensaje: ${nombres}`);
    cargandoFormulario.value = false;
    return;
  }

  const segmentosSinVelocidad = segmentosRuta.value.filter(s =>
    s.velocidad === null || s.velocidad === undefined || s.velocidad < 0
  );
  if (segmentosSinVelocidad.length > 0) {
    const nombres = segmentosSinVelocidad.map(s => s.nombre).join(", ");
    toast.error(`Los siguientes segmentos tienen velocidad inválida: ${nombres}`);
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

  let url = "api/rutas";
  if (nuevaRuta.id) {
    url = `api/rutas/${nuevaRuta.id}`;
    formData.append("_method", "PUT");
  }

  try {
    const { data } = await axios.post(url, formData, {
      headers: { "Content-Type": "multipart/form-data" }
    });

    toast.success(nuevaRuta.id ? "Ruta actualizada exitosamente" : "Ruta creada exitosamente");
    cerrarFormulario();
    await cargarRutas();
    return data;

  } catch (error: any) {
    console.error("Error al guardar/actualizar la ruta:", error);
    if (error.response?.status === 422) {
      const mensajes = Object.entries(error.response.data.errors)
        .map(([campo, msgs]) => `${campo}: ${Array.isArray(msgs) ? msgs.join(", ") : msgs}`)
        .join("\n");
      toast.error(`Errores de validación:\n\n${mensajes}`);
    } else if (error.response?.data?.message) {
      toast.error(`Error: ${error.response.data.message}`);
    } else {
      toast.error("Error al guardar la ruta. Por favor, intente nuevamente.");
    }
  } finally {
    cargandoFormulario.value = false;
  }
}

async function editarRuta(id?: number) {
  if (!id) return;

  formularioSegmentoAbierto.value = true;
  cargandoFormulario.value = true;

  await nextTick();

  try {
    const rutaResp = await axios.get(`api/rutasid/${id}`);
    const ruta = rutaResp.data;

    if (!ruta) {
      alert("No se encontró la ruta");
      return;
    }

    const tipoSeleccionado =
      ruta.tipo === "G" ? "General" : ruta.tipo === "V" ? "Vuelta" : "Otro";


    const detallesRuta = ruta.detalles_ruta ?? [];
    const segmentos: Segmento[] = detallesRuta.map((d: any) => ({
      id: d.id ?? d.segmento_codsegmento,
      nombre: d.nombre ?? `Segmento ${d.id ?? d.segmento_codsegmento}`,
      color: d.color ?? "#cccccc",
      mensaje: d.mensaje ?? "",
      velocidad: Number(d.velocidadPermitida) || 0,
      _tempId: generarTempId(),
      cordenadas: Array.isArray(d.cordenadas) ? d.cordenadas : [], // siempre array
    }));

    const icono = ruta.icono ? 'storage/' + ruta.icono : "";


    const logoPreviewUrl = icono || "";


    Object.assign(Rutadb, {
      id: ruta.id ?? ruta.codruta,
      nombre: ruta.nombre ?? "",
      tipo: tipoSeleccionado,
      color: "",
      logo: icono,
      logoPreview: logoPreviewUrl,
      limite: ruta.limiteGeneral || 0,
      segmentos,
    });


    Object.assign(nuevaRuta, {
      id: ruta.id ?? ruta.codruta,
      nombre: ruta.nombre ?? "",
      tipo: tipoSeleccionado,
      color: "",
      logo: icono,
      logoPreview: logoPreviewUrl,
      limite: ruta.limiteGeneral || 0,
      segmentos,
    });

    // Asignar segmentos a la vista
    segmentosRuta.value = segmentos;
    resetearSegmentosDisponibles();
    setTimeout(actualizarMapa, 300);

  } catch (error: any) {
    console.error("Error al cargar la ruta:", error);
    toast.error("No se pudo cargar la ruta para editar");
  } finally {
    cargandoFormulario.value = false;
  }
}



async function confirmarAccionRuta(id?: number) {
  if (!id || id <= 0) {
    await Swal.fire({
      icon: "error",
      title: "Error",
      text: "El ID de la ruta no es válido",
    });
    return;
  }

  // Preguntar al usuario si quiere duplicar o crear la ruta de regreso
  const resultado = await Swal.fire({
    title: "¿Qué deseas hacer con esta ruta?",
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
  });

  if (resultado.isConfirmed) await duplicarRuta(id, false);
  else if (resultado.isDenied) await duplicarRuta(id, true);
}

async function duplicarRuta(rutaId: number, invertida = false): Promise<any> {
  if (!rutaId || rutaId <= 0) {
    await Swal.fire({
      icon: "error",
      title: "Error",
      text: "El ID de la ruta no es válido",
    });
    return;
  }

  Swal.fire({
    title: invertida ? "Creando ruta de regreso..." : "Duplicando ruta...",
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading(),
  });

  try {
    // Obtener la ruta completa desde la API
    const { data: ruta } = await axios.get(`api/rutasid/${rutaId}`, { timeout: 10000 });

    if (!ruta || !ruta.nombre) throw new Error("No se encontró la ruta o no tiene datos válidos");

    const detalles = ruta.detalles || ruta.detalles_ruta;
    if (!Array.isArray(detalles) || detalles.length === 0) throw new Error("La ruta no tiene segmentos para duplicar.");

    // Procesar segmentos
    const segmentosProcesados = invertida ? [...detalles].reverse() : [...detalles];
    const nuevoNombre = invertida ? `${ruta.nombre} (Vuelta)` : `${ruta.nombre} (Copia)`;

    const detallesData = segmentosProcesados.map((s: any, i: number) => ({
      segmento_codsegmento: s.segmento_codsegmento || s.codsegmento || s.id,
      velocidadPermitida: Number(s.velocidadPermitida) || 0,
      mensaje: s.mensaje || "",
      orden: i + 1
    }));

    // Datos para crear la nueva ruta
    const datosRuta = {
      nombre: nuevoNombre,
      tipo: invertida ? "V" : (ruta.tipo || "G"),
      limiteGeneral: Number(ruta.limiteGeneral) || 0,
      icono: ruta.icono || null,
      detalles: detallesData
    };

    const { data: rutaGuardada } = await axios.post(
      "api/duplicar",
      datosRuta,
      { headers: { "Content-Type": "application/json" } }
    );

    // Actualizar lista de rutas
    rutas.value.push(rutaGuardada);
    await cargarRutas();

    await Swal.fire({
      icon: "success",
      title: invertida ? "Ruta de regreso creada correctamente" : "Ruta duplicada correctamente",
      text: `"${nuevoNombre}" ha sido guardada exitosamente.`,
      confirmButtonText: "Aceptar"
    });

    return rutaGuardada;

  } catch (error: any) {
    const mensaje =
      error.response?.data?.message ||
      error.response?.data?.error ||
      error.message ||
      "Ocurrió un error inesperado.";

    await Swal.fire({
      icon: "error",
      title: "Error al guardar la ruta",
      text: mensaje,
      confirmButtonText: "Aceptar"
    });

    throw error;
  } finally {
    Swal.close(); // siempre cerrar modal de carga
  }
}




async function eliminarRuta(id?: number) {
  if (!id) return;

  try {
    // Mostrar pantalla de verificando
    Swal.fire({
      title: "Verificando ruta...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    // 1) Verificar si está asignada
    const { data: verificar } = await axios.get(
      `api/verificaruta/${id}`
    );

    Swal.close(); // cerrar el loading de "verificando"

    if (verificar.success) {
      // Ruta asignada: mostrar aviso
      await Swal.fire({
        icon: "warning",
        title: "No se puede eliminar",
        text: "La ruta está asignada en la tabla de asignaciones.",
        confirmButtonText: "Aceptar",
      });
      return;
    }

    // 2) Confirmación de eliminación
    const confirmar = await Swal.fire({
      title: "¿Está seguro de eliminar esta ruta?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "Cancelar",
      reverseButtons: true,
    });

    if (!confirmar.isConfirmed) return;

    // 3) Loading mientras se elimina
    Swal.fire({
      title: "Eliminando ruta...",
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    // 4) Eliminar ruta
    const { data } = await axios.delete(
      `api/rutas/${id}`
    );

    rutas.value = rutas.value.filter((r) => r.id !== id);

    Swal.close();

    await Swal.fire({
      icon: "success",
      title: "Ruta eliminada",
      text: data.message || "La ruta se eliminó correctamente",
      confirmButtonText: "Aceptar",
    });

  } catch (error: any) {
    Swal.close();

    let mensaje = "No se pudo eliminar la ruta.";
    if (error.response?.status === 405) {
      mensaje = "Método no permitido. Verifica la ruta DELETE en Laravel.";
    } else if (error.response?.data?.message) {
      mensaje = error.response.data.message;
    }

    await Swal.fire({
      icon: "error",
      title: "Error",
      text: mensaje,
      confirmButtonText: "Aceptar",
    });
  }
}




async function toggleSegmentos(ruta: Ruta) {
  if (registroExpandido.value === ruta.id) {
    registroExpandido.value = null;
    ruta.mostrarSegmentos = false;
    return;
  }

  rutas.value.forEach(r => r.mostrarSegmentos = false);


  ruta.mostrarSegmentos = true;
  registroExpandido.value = ruta.id ?? null;

  if (ruta.segmentos && ruta.segmentos.length > 0) return;


  ruta.segmentosCargando = true;

  try {
    const respuesta = await fetch(`/api/rutas/${ruta.id}`);
    const data = await respuesta.json();

    ruta.segmentos = data.map((segmento: any) => ({
      ...segmento,
      _tempId: generarTempId()
    }));
  } catch (error) {
    console.error('Error cargando segmentos:', error);
    ruta.segmentos = [];
  } finally {
    ruta.segmentosCargando = false;
  }
}



function abrirSelectorLogo() {
  logoInput.value?.click();
}

import { useToast } from "vue-toastification";

const toast = useToast();

function previewLogo(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];

  if (!file) {
    toast.warning("No se seleccionó ningún archivo.");
    nuevaRuta.logoPreview = ""; // Limpiar preview
    return;
  }

  const tiposPermitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'image/avif'];

  if (!tiposPermitidos.includes(file.type)) {
    toast.error("Formato no permitido. Solo se aceptan JPEG, PNG, GIF, SVG, WebP o AVIF.");
    nuevaRuta.logoPreview = ""; // Limpiar preview
    return;
  }

  const reader = new FileReader();
  reader.onload = () => {
    nuevaRuta.logoPreview = reader.result as string;
    nuevaRuta.logoFile = file;
  };
  reader.readAsDataURL(file);
}



function dropLogo(event: DragEvent) {
  dragOverLogo.value = false;
  const files = event.dataTransfer?.files;
  if (files?.[0]) {
    cargarPreview(files[0]);
  }
}

function cargarPreview(file: File) {
  if (!file) {
    toast.warning("No se seleccionó ningún archivo.");
    nuevaRuta.logoPreview = "";
    return;
  }

  // Tipos permitidos
  const tiposPermitidos = [
    "image/png",
    "image/jpeg",
    "image/jpg",
    "image/gif",
    "image/webp",
    "image/svg+xml"
  ];

  if (!tiposPermitidos.includes(file.type)) {
    toast.error("Solo se permiten archivos PNG, JPG, JPEG, GIF, WebP o SVG.");
    nuevaRuta.logoPreview = "";
    return;
  }

  // Tamaño máximo 5 MB
  if (file.size > 5 * 1024 * 1024) {
    toast.error("La imagen no debe superar los 5MB.");
    nuevaRuta.logoPreview = "";
    return;
  }

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


/* ============================
            MAPA
============================ */

function calcularCentro(cordenadas: { x: number; y: number }[]) {
  const latitudes = cordenadas.map(c => c.y);
  const longitudes = cordenadas.map(c => c.x);
  const lat = latitudes.reduce((a, b) => a + b, 0) / latitudes.length;
  const lng = longitudes.reduce((a, b) => a + b, 0) / longitudes.length;
  return [lat, lng] as [number, number];
}

// Crear un icono tipo “card” para la etiqueta permanente
function crearCardIcon(nombre: string) {
  return L.divIcon({
    className: 'segment-card',
    html: `
      <div style="
        background-color: white;
        border: 1px solid #888;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        text-align: center;
        white-space: nowrap;
      ">
        ${nombre}
      </div>
    `,
    iconSize: [80, 30],
    iconAnchor: [40, 15], // centra el icono sobre el punto
  });
}

const convertirColorConAlpha = (hex: string, alpha: number = 0.33): string => {
  const cleanHex = hex.replace("#", "");
  const bigint = parseInt(cleanHex, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const centrarEnSegmento = (segmento: Segmento) => {
  const mapa = mapaRef.value?.leafletObject;
  if (!mapa) {
    console.warn(" No se encontró la referencia del mapa.");
    return;
  }

  if (!segmento.cordenadas || segmento.cordenadas.length === 0) {
    console.warn(" El segmento no tiene coordenadas válidas.");
    return;
  }

  try {
    // Convertir coordenadas válidas y filtrar NaN
    const latLngs = segmento.cordenadas
      .map(c => [parseFloat(c.y as any), parseFloat(c.x as any)])
      .filter(([lat, lng]) => !isNaN(lat) && !isNaN(lng));

    if (latLngs.length === 0) {
      console.warn(" No hay coordenadas válidas para centrar el mapa.");
      return;
    }

    const bounds = L.latLngBounds(latLngs);

    // Centrar con animación suave
    mapa.flyToBounds(bounds, {
      padding: [60, 60],
      duration: 1.2,
      maxZoom: 16
    });
  } catch (err) {
    console.error(" Error al centrar en el segmento:", err);
  }
};


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

@keyframes pulse {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.4;
  }
}

/* Clase principal del icono */
.etiqueta-segmento {
  pointer-events: none;
  /* que no bloquee clicks en el mapa */
  text-align: center;
}

/* Contenido de la etiqueta */
.etiqueta-texto {
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid #333;
  border-radius: 4px;
  padding: 2px 6px;
  font-size: 12px;
  font-weight: bold;
  white-space: nowrap;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.segmento-card .card-content {
  background-color: white;
  padding: 4px 8px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  font-size: 12px;
  text-align: center;
  pointer-events: none;
  /* para que no interfiera con el mapa */
}

.segmento-card {
  background-color: white;
  border-radius: 4px;
  padding: 2px 6px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
  font-weight: bold;
  text-align: center;
}


.segment-card .card {
  background-color: white;
  padding: 4px 8px;
  border-radius: 8px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
  text-align: center;
  font-weight: bold;
  white-space: nowrap;
  pointer-events: none;
  /* que no interfiera con el zoom o drag */
}

.leaflet-container {
  width: 100%;
  height: 100%;
  min-height: 400px;
}

.map-wrapper {
  width: 100%;
  height: 100%;
  min-height: 400px;
  position: relative;
}

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Ocultar zoom buttons (+ y -) */
:deep(.leaflet-control-zoom) {
  display: none !important;
}

/* Ocultar atribución (OpenStreetMap) */
:deep(.leaflet-control-attribution) {
  display: none !important;
}

/* Ocultar escala */
:deep(.leaflet-control-scale) {
  display: none !important;
}
</style>
