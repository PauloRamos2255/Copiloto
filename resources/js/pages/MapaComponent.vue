<template>
    <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    integrity="sha512-Fo3rlrZj/k7ujTTXRN1E0Yx1U9vC5O5Ebxk6A0UQp6L7A5U4rQbslcmvW7xR9g0QKjYbq/yZHB6PZ9F4p6XfKw=="
    crossorigin="anonymous"
    referrerpolicy="no-referrer"
  />
  <div id="mapa-page" class="relative h-screen w-full overflow-hidden">
    <!-- Header -->
    <header class="bg-gray-700 text-white flex items-center justify-between px-4 py-2 shadow-md z-20">
      <div class="flex items-center space-x-6">
        <div class="flex items-center space-x-2">
          <img src="/logo.png" alt="Logo" class="h-6" />
          <span class="text-lg font-semibold">Copiloto</span>
        </div>

        <nav class="flex items-center space-x-5 text-sm">
          <button class="flex items-center space-x-1 hover:text-blue-400">
            <i class="fas fa-chart-line"></i><span>Dashboard</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="togglePanel">
            <i class="fas fa-globe"></i><span>Segmentos</span>
          </button>
          <button class="flex items-center space-x-1 hover:text-blue-400" @click="verUsuarios">
            <i class="fas fa-user"></i><span>Usuarios</span>
          </button>
        </nav>
      </div>

      <div class="flex items-center space-x-4 relative">
        <span class="text-sm text-gray-300">{{ nombreUsuario }}</span>
        <button @click="toggleMenuUsuario" class="focus:outline-none">
          <i class="fas fa-ellipsis-v"></i>
        </button>

        <transition name="fade">
          <ul
            v-if="menuUsuarioAbierto"
            class="absolute right-0 top-10 bg-white text-gray-800 rounded-lg shadow-lg w-40"
          >
            <li @click="cerrarSesion" class="px-4 py-2 hover:bg-gray-100 cursor-pointer">
              Cerrar sesión
            </li>
          </ul>
        </transition>
      </div>
    </header>

    <!-- Sidebar Segmentos -->
    <transition name="slide">
  <aside
    v-if="panelAbierto"
    class="fixed left-0 top-12 bg-white w-80 shadow-lg z-30 overflow-y-auto"
    style="height: calc(100%);"
  >
    <!-- Encabezado fijo -->
    <div class="sticky top-0 bg-white z-10 border-b">
      <div class="flex justify-between items-center mb-4">
  <h2 class="text-lg font-semibold text-gray-800">Segmentos</h2>
  <button
    @click="actualizarSegmentos"
    :disabled="loadingActualizar"
    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm flex items-center space-x-1"
  >
    <i class="fas fa-sync-alt" :class="{ 'animate-spin': loadingActualizar }"></i>
    <span>{{ loadingActualizar ? 'Actualizando...' : 'Actualizar' }}</span>
  </button>
</div>


      <!-- Barra de búsqueda -->
      <div class="flex items-center px-4 pb-3 space-x-2">
        <i class="fas fa-search text-gray-400"></i>
        <input
          type="text"
          v-model="busqueda"
          placeholder="Buscar segmento..."
          class="w-full border rounded px-2 py-1 focus:outline-none focus:ring focus:ring-blue-300"
        />
      </div>
    </div>

    <!-- Lista de segmentos -->
    <div class="p-4">
      <div
        v-for="segmento in segmentosFiltrados"
        :key="segmento.id"
        class="flex justify-between items-center p-2 hover:bg-gray-100 rounded cursor-pointer"
        @click="centrarMapa(segmento)"
      >
        <div class="flex items-center space-x-2">
          <i class="fas fa-draw-polygon"></i>
          <span>{{ segmento.nombre }}</span>
        </div>

        <div class="flex items-center space-x-4 text-gray-600">
          <div class="flex items-center space-x-1">
            <i class="fas fa-bus"></i>
            <span>{{ segmento.unidades }}</span>
          </div>
          <i class="fas fa-wrench cursor-pointer"></i>
        </div>
      </div>
    </div>
  </aside>
</transition>

<div
  v-if="notificacion"
  class="fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-white transition"
  :class="{
    'bg-green-500': notificacion.tipo === 'exito',
    'bg-red-500': notificacion.tipo === 'error',
    'bg-blue-500': notificacion.tipo === 'info'
  }"
>
  {{ notificacion.mensaje }}
</div>



    <!-- Mapa -->
    <div id="map" class="h-full w-full"></div>
  </div>
</template>

<script>
import * as turf from "@turf/turf";
import axios from "axios";
const itemId = 402037903;

export default {
  name: "MapaComponent",

  data() {
    return {
      panelAbierto: true,
      menuUsuarioAbierto: false,
      nombreUsuario: "Paulo Ramos",
      busqueda: "",
      segmentos: [],
      polygons: [],
      overlays: [],
      map: null,
      loadingActualizar: false,
      error: null,
      itemId: null,
      notificacion: null,
      lastSegmentosHash: {}, 
    autoRefreshInterval: null,
    };
  },

  async mounted() {
     this.iniciarAutoRefresh();
    await this.cargarGoogleMaps();
    this.initMap();
    await this.cargarSegmentos();
  },

  computed: {
    segmentosFiltrados() {
      if (!this.busqueda) return this.segmentos;
      const texto = this.busqueda.toLowerCase();
      return this.segmentos.filter((s) => s.nombre?.toLowerCase().includes(texto));
    },
  },

  methods: {

     iniciarAutoRefresh() {
    // Ejecutar inmediatamente al iniciar
    this.refreshSiHayCambios();

    // Cada 30 minutos
    this.autoRefreshInterval = setInterval(() => {
      this.refreshSiHayCambios();
    }, 30 * 60 * 1000);
  },

  // Genera un hash simple por segmento basado en coordenadas y color
  generarHashSegmento(seg) {
    const coords = (seg.cordenadas || []).map(c => `${c.x},${c.y}`).join('|');
    return `${seg.id}:${coords}:${seg.color}`;
  },

  async refreshSiHayCambios() {
    try {
      const sid = await this.obtenerSID();
      const { data } = await axios.post('http://localhost:8000/api/zone-data', { itemId, sid });

      if (!data.success || !Array.isArray(data.zones)) return;

      const nuevosSegmentos = data.zones.map(z => ({
        id: z.id ?? z.codsegmento,
        nombre: z.n ?? z.nombre ?? 'Sin nombre',
        color: z.color ?? (z.c !== undefined ? '#' + Number(z.c).toString(16).padStart(6,'0') : '#FFFFFF'),
        cordenadas: this.normalizarCoordenadas(z),
        bounds: z.b ?? z.bounds ?? {}
      }));

      // Detectar cambios comparando hashes
      let hayCambios = false;
      const nuevosHashes = {};
      nuevosSegmentos.forEach(seg => {
        const hash = this.generarHashSegmento(seg);
        nuevosHashes[seg.id] = hash;
        if (this.lastSegmentosHash[seg.id] !== hash) {
          hayCambios = true;
        }
      });

      if (hayCambios || Object.keys(this.lastSegmentosHash).length !== nuevosSegmentos.length) {
  console.log('🔄 Cambios detectados, actualizando mapa...');

  this.segmentos = nuevosSegmentos;
  this.limpiarMapa();
  this.dibujarSegmentos();
  this.lastSegmentosHash = nuevosHashes;

  // 🔹 Guardar en la base de datos
  try {
    await axios.post('http://localhost:8000/api/segmentos/guardar', { zonas: this.segmentos });
    this.mostrarNotificacion('✅ Segmentos actualizados automáticamente y guardados', 'exito');
  } catch (err) {
    console.error('❌ Error al guardar segmentos automáticamente:', err);
    this.mostrarNotificacion('❌ Error al guardar segmentos automáticamente', 'error');
  }
} else {
  console.log('✔️ No hay cambios en los segmentos.');
}

    } catch (err) {
      console.error('❌ Error al refrescar segmentos automáticamente:', err);
    }
  },

  beforeDestroy() {
    if (this.autoRefreshInterval) clearInterval(this.autoRefreshInterval);
  },


    mostrarNotificacion(mensaje, tipo = "info") {
  this.notificacion = { mensaje, tipo };
  setTimeout(() => (this.notificacion = null), 4000);
},

    async cargarGoogleMaps() {
      return new Promise((resolve, reject) => {
        // si ya está cargado, resolvemos
        if (window.google && window.google.maps) return resolve();

        const script = document.createElement("script");
        // reemplaza TU_API_KEY por tu API key (o usa variable de entorno)
        script.src = "https://maps.googleapis.com/maps/api/js?key=TU_API_KEY";
        script.async = true;
        script.defer = true;
        script.onload = () => {
          // a veces onload se dispara pero google aún no está listo; comprobamos
          const check = () => {
            if (window.google && window.google.maps) return resolve();
            // reintentamos en 50ms
            setTimeout(check, 50);
          };
          check();
        };
        script.onerror = (e) => reject(new Error("No se pudo cargar Google Maps: " + e));
        document.head.appendChild(script);
      });
    },

    initMap() {
      const mapDiv = document.getElementById("map");
      if (!mapDiv) return console.error("Falta el contenedor #map");
      this.map = new google.maps.Map(mapDiv, {
        center: { lat: -12.0464, lng: -77.0428 },
        zoom: 12,
        mapTypeId: "roadmap",
      });
    },

    async cargarSegmentos() {
      try {
        const res = await fetch("http://localhost:8000/api/segmentos");
        const data = await res.json();
        console.log("📦 Datos recibidos (API /api/segmentos):", data);

        // Normalizar: soporta dos formatos: arreglo directo o { segmentos: [...] }
        let lista = Array.isArray(data) ? data : data.segmentos ?? data.zones ?? [];
        this.segmentos = lista.map((seg) => ({
          id: seg.id ?? seg.codsegmento ?? seg.codigo ?? null,
          nombre: seg.n ?? seg.nombre ?? seg.name ?? "Sin nombre",
          // color: acepta string '#RRGGBB' o número (c)
          color:
            seg.color ??
            (seg.c !== undefined && seg.c !== null
              ? "#" + Number(seg.c).toString(16).padStart(6, "0")
              : seg.color_hex ?? "#FFFFFF"),
          // cordenadas: normaliza varios formatos posibles
          cordenadas: this.normalizarCoordenadas(seg),
          bounds: seg.b ?? seg.bounds ?? {},
        }));

        console.log("🔎 Segmentos normalizados:", this.segmentos);

        this.limpiarMapa();
        this.dibujarSegmentos();
      } catch (err) {
        console.error("Error al cargar segmentos:", err);
        this.error = err.message || err;
      }
    },


    normalizarCoordenadas(seg) {
      let raw = seg.p ?? seg.cordenadas ?? seg.coordenadas ?? seg.points ?? [];
      // si viene como string JSON
      if (typeof raw === "string") {
        try {
          raw = JSON.parse(raw);
        } catch (e) {
          console.warn("No se pudo parsear coordenadas string:", raw);
          raw = [];
        }
      }
      if (!Array.isArray(raw)) return [];

      const coords = raw
        .map((p) => {
          // formato objeto {x, y}
          if (p && typeof p === "object" && p.x !== undefined && p.y !== undefined) {
            return { x: Number(p.x), y: Number(p.y) };
          }
          // formato array [lon, lat] o [lat, lon] — intentamos detectar
          if (Array.isArray(p) && p.length >= 2) {
            const a = Number(p[0]);
            const b = Number(p[1]);
            // asumimos [lon, lat] porque Wialon usa x=lon, y=lat
            return { x: a, y: b };
          }
          // formato con nombres diferentes
          if (p && (p.lon !== undefined || p.lng !== undefined)) {
            return { x: Number(p.lon ?? p.lng), y: Number(p.lat ?? p.latitude ?? p.y) };
          }
          return null;
        })
        .filter(Boolean)
        // eliminar puntos inválidos (NaN)
        .filter((pt) => !Number.isNaN(pt.x) && !Number.isNaN(pt.y));

      // eliminar duplicados exactos
      const uniq = [];
      const seen = new Set();
      coords.forEach((pt) => {
        const key = `${pt.x}-${pt.y}`;
        if (!seen.has(key)) {
          seen.add(key);
          uniq.push(pt);
        }
      });
      return uniq;
    },

    limpiarMapa() {
      this.polygons.forEach((p) => p.polygon?.setMap(null));
      this.overlays.forEach((o) => o.setMap(null));
      this.polygons = [];
      this.overlays = [];
    },

    dibujarSegmentos() {
      if (!this.map) return console.warn("Mapa no inicializado aún");
      const bounds = new google.maps.LatLngBounds();
      // limpiar antes de dibujar
      this.polygons.forEach((p) => p.polygon?.setMap(null));
      this.overlays.forEach((o) => o.setMap(null));
      this.polygons = [];
      this.overlays = [];

      console.log("📍 Dibujando segmentos (cantidad):", this.segmentos.length);

      this.segmentos.forEach((seg) => {
        const coords = seg.cordenadas || [];
        // si no hay puntos o menos de 2, no dibujamos
        if (!coords || coords.length < 2) {
          console.warn("Segmento omitido (puntos insuficientes):", seg.id, coords.length);
          return;
        }

        // Convertir a {lat, lng} (google usa lat, lng)
        const puntos = coords.map((c) => ({ lat: parseFloat(c.y), lng: parseFloat(c.x) }));

        // Si algunos puntos fallan (NaN), descartamos el segmento
        if (puntos.some((p) => Number.isNaN(p.lat) || Number.isNaN(p.lng))) {
          console.warn("Segmento con coordenadas inválidas, omitido:", seg.id, puntos);
          return;
        }

        // Si tiene exactamente 2 puntos, lo dibujamos como Polyline en vez de Polygon
        if (puntos.length === 2) {
          const linea = new google.maps.Polyline({
            path: puntos,
            strokeColor: "#000",
            strokeWeight: 2,
            map: this.map,
          });
          this.polygons.push({ id: seg.id, polygon: linea });
          puntos.forEach((p) => bounds.extend(p));
        } else {
          // cerramos la lista si no está cerrada (para turf)
          const coordsTurf = puntos.map((p) => [p.lng, p.lat]);
          // try/catch por seguridad con turf
          let centro = null;
          try {
            // turf necesita un anillo cerrado; repetimos el primer punto al final
            const ring = coordsTurf.slice();
            if (ring.length && (ring[0][0] !== ring[ring.length - 1][0] || ring[0][1] !== ring[ring.length - 1][1])) {
              ring.push(ring[0]);
            }
            centro = turf.pointOnFeature(turf.polygon([ring])).geometry.coordinates;
          } catch (e) {
            console.warn("Turf no pudo calcular centro para segmento:", seg.id, e);
          }

          const { color, opacity } = this.parseGoogleColor(seg.color);

          const polygon = new google.maps.Polygon({
            paths: puntos,
            strokeColor: "#000",
            strokeWeight: 1,
            fillColor: color,
            fillOpacity: opacity ?? 0.5,
            map: this.map,
          });

          this.polygons.push({ id: seg.id, polygon });
          puntos.forEach((p) => bounds.extend(p));

          // etiqueta (overlay) — solo si turf pudo calcular centro
          if (centro) {
            const label = document.createElement("div");
            label.className = "map-label";
            label.innerText = seg.nombre || "Segmento";

            const overlay = new google.maps.OverlayView();
            overlay.onAdd = function () {
              this.getPanes().overlayLayer.appendChild(label);
            };
            overlay.draw = function () {
              const projection = this.getProjection();
              if (!projection) return;
              const pixel = projection.fromLatLngToDivPixel(new google.maps.LatLng(centro[1], centro[0]));
              label.style.position = "absolute";
              label.style.left = pixel.x + "px";
              label.style.top = pixel.y + "px";
              label.style.transform = "translate(-50%, -50%)";
              label.style.pointerEvents = "none";
              label.style.padding = "2px 6px";
              label.style.background = "rgba(255,255,255,0.9)";
              label.style.borderRadius = "4px";
              label.style.fontSize = "12px";
              label.style.boxShadow = "0 1px 3px rgba(0,0,0,0.2)";
            };
            overlay.onRemove = function () {
              label.remove();
            };
            overlay.setMap(this.map);
            this.overlays.push(overlay);
          }
        }
      });

      // Ajustar vista si hay bounds
      try {
        if (!bounds.isEmpty()) this.map.fitBounds(bounds);
      } catch (e) {
        console.warn("No se pudo ajustar bounds:", e);
      }
    },

    parseGoogleColor(argb) {
      if (!argb) return { color: "#cccccc", opacity: 0.5 };
      // si viene como número (entero) tipo Wialon
      if (typeof argb === "number") {
        return { color: "#" + Number(argb).toString(16).padStart(6, "0"), opacity: 1 };
      }
      if (typeof argb === "string") {
        // '#RRGGBB'
        if (argb.startsWith("#") && (argb.length === 7 || argb.length === 9)) {
          if (argb.length === 7) return { color: argb, opacity: 1 };
          // #AARRGGBB -> extraemos alpha
          const a = parseInt(argb.substring(1, 3), 16) / 255;
          return { color: `#${argb.substring(3)}`, opacity: a };
        }
        // 'RRGGBB' sin #
        if (/^[0-9a-fA-F]{6}$/.test(argb)) return { color: "#" + argb, opacity: 1 };
      }
      return { color: "#cccccc", opacity: 0.5 };
    },

    centrarMapa(segmento) {
      const coords = segmento.cordenadas || segmento.p || [];
      if (!coords.length) return;
      const puntos = coords.map((c) => ({ lat: parseFloat(c.y), lng: parseFloat(c.x) }));
      const bounds = new google.maps.LatLngBounds();
      puntos.forEach((p) => bounds.extend(p));
      this.map.fitBounds(bounds);
    },

    async obtenerSID() {
      const { data } = await axios.get("http://localhost:8000/api/obtener-sid");
      if (data.success && data.sid) return data.sid;
      throw new Error(data.error || "No se pudo obtener el SID");
    },

    async actualizarSegmentos() {
  this.loadingActualizar = true;
  this.error = null;

  try {
    // 🔹 1. Obtener SID
    const sid = await this.obtenerSID();

    // 🔹 2. Solicitar datos reales de zonas
    const { data } = await axios.post('http://localhost:8000/api/zone-data', { itemId, sid });

    if (!data.success || !Array.isArray(data.zones)) {
      throw new Error('No se recibieron zonas válidas');
    }

    // 🔹 3. Normalizar y guardar segmentos en la variable local
    this.segmentos = data.zones.map((z) => {
      const cordenadas = this.normalizarCoordenadas(z);
      const color =
        z.color ?? (z.c !== undefined ? '#' + Number(z.c).toString(16).padStart(6, '0') : '#FFFFFF');

      return {
        id: z.id ?? z.codsegmento,
        nombre: z.n ?? z.nombre ?? 'Sin nombre',
        color,
        cordenadas,
        bounds: z.b ?? z.bounds ?? {},
      };
    });

    // 🔹 3.1 Guardar en la base de datos
    await axios.post('http://localhost:8000/api/segmentos/guardar', { zonas: this.segmentos });

    // 🔹 4. Limpiar mapa y dibujar
    this.limpiarMapa();
    this.dibujarSegmentos();

    this.mostrarNotificacion('✅ Segmentos actualizados correctamente', 'exito');
  } catch (err) {
    console.error('❌ Error al actualizar segmentos:', err);
    this.error = err.message || JSON.stringify(err);
    this.mostrarNotificacion('❌ Error al actualizar segmentos', 'error');
  } finally {
    this.loadingActualizar = false;
  }
},


    togglePanel() {
      this.panelAbierto = !this.panelAbierto;
    },
    toggleMenuUsuario() {
      this.menuUsuarioAbierto = !this.menuUsuarioAbierto;
    },
    verUsuarios() {
      window.location.href = "/usuarios";
    },
    async cerrarSesion() {
      if (!confirm("¿Deseas cerrar sesión?")) return;
      try {
        await fetch("/logout", {
          method: "POST",
          headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content"),
          },
        });
        window.location.href = "/login";
      } catch (e) {
        console.error("Error cerrando sesión:", e);
      }
    },
  },
};
</script>



<style>
/* Transiciones */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-enter-active {
  transition: transform 0.3s ease-out;
}
.slide-enter-from {
  transform: translateX(-100%);
}
.slide-leave-active {
  transition: transform 0.3s ease-in;
}
.slide-leave-to {
  transform: translateX(-100%);
}

/* Etiquetas del mapa */
.map-label {
  background: white;
  border: 1px solid #ccc;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 12px;
  white-space: nowrap;
}
</style>
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
/>

