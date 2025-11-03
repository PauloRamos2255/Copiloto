<template>
  <div id="mapa-page">
    <!-- 🔹 Barra superior -->
    <header id="map-header">
      <div class="menu-izquierdo">
        <button @click="verSegmentos" class="menu-btn">📍 Ver Segmentos</button>
      </div>

      <div class="usuario-menu">
        <div class="usuario" @click="toggleMenuUsuario">
          👤 {{ nombreUsuario }}
          <span class="arrow" :class="{ abierto: menuUsuarioAbierto }">▼</span>
        </div>

        <!-- 🔹 Menú desplegable -->
        <transition name="fade">
          <ul v-if="menuUsuarioAbierto" class="dropdown-menu">
            <li @click="cerrarSesion">🚪 Cerrar Sesión</li>
          </ul>
        </transition>
      </div>
    </header>

    <!-- 🔹 Contenedor del mapa -->
    <div id="map"></div>
  </div>
</template>

<script>
import * as turf from "@turf/turf";

export default {
  name: "MapaComponent",
  data() {
    return {
      map: null,
      segmentos: [],
      polygons: [],
      googleLoaded: false,
      menuUsuarioAbierto: false,
      nombreUsuario: "Paulo Ramos", // Cambia según tu usuario
    };
  },

  async mounted() {
    await this.cargarGoogleMaps();
    await this.cargarSegmentos();
    this.$nextTick(() => this.initMap());
  },

  methods: {
    // 🔹 Cargar Google Maps
    cargarGoogleMaps() {
      return new Promise((resolve, reject) => {
        if (window.google && window.google.maps) return resolve();

        const script = document.createElement("script");
        script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyAwIMAPTeuBV2TJghm-1VTnOVl4yi4Y3rE"; 
        script.async = true;
        script.defer = true;
        script.onload = resolve;
        script.onerror = reject;
        document.head.appendChild(script);
      });
    },

    // 🔹 Redirigir a lista de segmentos
    verSegmentos() {
      window.location.href = "/listasegmento";
    },

    // 🔹 Obtener color ARGB y convertir a formato válido
    parseGoogleColor(argb) {
  if (!argb) return { color: "#cccccc", opacity: 0.5 };

  // Si el formato es ARGB (#AARRGGBB)
  if (argb.startsWith("#") && argb.length === 9) {
    const a = parseInt(argb.substring(1, 3), 16) / 255; // Alpha
    const r = argb.substring(3, 5);
    const g = argb.substring(5, 7);
    const b = argb.substring(7, 9);
    const color = `#${r}${g}${b}`;
    return { color, opacity: a };
  }

  // Si es RGB (#RRGGBB)
  if (argb.startsWith("#") && argb.length === 7) {
    return { color: argb, opacity: 1 };
  }

  // Si viene en otro formato
  return { color: "#cccccc", opacity: 0.5 };
},


    // 🔹 Cargar segmentos
    async cargarSegmentos() {
      try {
        const res = await fetch("http://localhost:8000/api/segmentos");
        const data = await res.json();
        this.segmentos = Array.isArray(data) ? data : data.segmentos || [];
        if (this.map) this.dibujarSegmentos();
      } catch (err) {
        console.error("❌ Error al cargar segmentos:", err);
      }
    },

    // 🔹 Inicializar mapa
    initMap() {
      const mapDiv = document.getElementById("map");
      if (!mapDiv) return console.error("❌ Falta el contenedor #map");

      this.map = new google.maps.Map(mapDiv, {
        center: { lat: -12.0464, lng: -77.0428 },
        zoom: 12,
        mapTypeId: "roadmap",
      });

      this.dibujarSegmentos();
    },

    // 🔹 Dibujar polígonos
    dibujarSegmentos() {
      const bounds = new google.maps.LatLngBounds();
      this.polygons.forEach((p) => p.setMap(null));
      this.polygons = [];

      this.segmentos.forEach((seg) => {
        if (seg.cordenadas?.length >= 3) {
          const puntos = seg.cordenadas.map((c) => ({
            lat: parseFloat(c.y),
            lng: parseFloat(c.x),
          }));

          const { color, opacity } = this.parseGoogleColor(seg.color);

          const polygon = new google.maps.Polygon({
            paths: puntos,
            strokeColor: "#000",
            strokeWeight: 1.5,
            fillColor: color,
            fillOpacity: opacity,
            map: this.map,
          });

          this.polygons.push(polygon);
          puntos.forEach((p) => bounds.extend(p));

          // Etiqueta centrada
          const coordsTurf = puntos.map((p) => [p.lng, p.lat]);
          const polygonGeoJSON = turf.polygon([[...coordsTurf, coordsTurf[0]]]);
          const centro = turf.pointOnFeature(polygonGeoJSON).geometry.coordinates;

          const label = document.createElement("div");
          label.className = "map-label";
          label.innerText = seg.nombre || seg.nombreSegmento || "Segmento";

          const overlay = new google.maps.OverlayView();
          overlay.onAdd = function () {
            this.getPanes().overlayLayer.appendChild(label);
          };
          overlay.draw = function () {
            const projection = this.getProjection();
            const pixel = projection.fromLatLngToDivPixel(
              new google.maps.LatLng(centro[1], centro[0])
            );
            label.style.left = pixel.x + "px";
            label.style.top = pixel.y + "px";
            label.style.position = "absolute";
          };
          overlay.onRemove = function () {
            label.remove();
          };
          overlay.setMap(this.map);
        }
      });

      if (!bounds.isEmpty()) this.map.fitBounds(bounds);
    },

    // 🔹 Mostrar/ocultar menú usuario
    toggleMenuUsuario() {
      this.menuUsuarioAbierto = !this.menuUsuarioAbierto;
    },

    // 🔹 Cerrar sesión correctamente
    async cerrarSesion() {
      if (confirm("¿Deseas cerrar sesión?")) {
        try {
          await fetch("/logout", {
            method: "POST",
            headers: {
              "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content"),
            },
          });
          window.location.href = "/login";
          history.pushState(null, null, "/login");
          window.onpopstate = () => history.pushState(null, null, "/login");
        } catch (e) {
          console.error("❌ Error cerrando sesión:", e);
        }
      }
    },
  },
};
</script>

<style>

#map div {
  cursor: default !important;
}

/* 🔹 Contenedor general */
#mapa-page {
  display: flex;
  flex-direction: column;
  height: 100vh;
  width: 100vw;
}

/* 🔹 Barra superior */
#map-header {
  background: #2c3e50;
  color: #fff;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 25px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

/* 🔹 Botones y menú */
.menu-btn {
  background: transparent;
  border: 1px solid #74b9ff;
  color: #74b9ff;
  padding: 8px 16px;
  border-radius: 6px;
  transition: all 0.3s ease;
  cursor: pointer;
}
.menu-btn:hover {
  background: #74b9ff;
  color: white;
}

.usuario-menu {
  position: relative;
}
.usuario {
  cursor: pointer;
  font-weight: 600;
}
.dropdown-menu {
  position: absolute;
  top: 55px;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
  list-style: none;
  padding: 5px 0;
  width: 160px;
}
.dropdown-menu li {
  padding: 10px 16px;
  cursor: pointer;
  color: #333;
  transition: background 0.3s;
}
.dropdown-menu li:hover {
  background: #f1f5f9;
}

/* 🔹 Etiquetas de nombre */
.map-label {
  background: rgba(255, 255, 255, 0.85);
  color: #000;
  border-radius: 6px;
  padding: 2px 8px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid #999;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  pointer-events: none;
}

/* 🔹 Mapa ocupa el resto */
#map {
  flex: 1;
  width: 100%;
}
</style>
