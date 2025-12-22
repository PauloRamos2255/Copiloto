<template>
  <div class="p-4">
    <h2 class="text-xl font-bold mb-4">🗺️ Segmentos en el Mapa</h2>
    <div id="map" style="height: 600px; width: 100%; border-radius: 10px;"></div>
  </div>
</template>

<script>
import L from "leaflet";
import "leaflet/dist/leaflet.css";

export default {
  data() {
    return {
      map: null,
      segmentos: [],
      polygons: []
    };
  },
  async mounted() {
    await this.cargarSegmentos(); // Esperamos a que carguen los datos
    this.initMap(); // Luego recién creamos el mapa
  },
  methods: {
    async cargarSegmentos() {
      try {
        const response = await fetch("http://localhost:8000/api/segmentos");
        const data = await response.json();

        // Aseguramos que los datos existan
        this.segmentos = Array.isArray(data)
          ? data
          : data.segmentos || [];

        console.log(" Segmentos cargados:", this.segmentos);
      } catch (error) {
        console.error(" Error al cargar los segmentos:", error);
        this.segmentos = [];
      }
    },

    initMap() {
      // Crear mapa centrado en Lima
      this.map = L.map("map").setView([-12.0464, -77.0428], 12);

      // Capa base gratuita de OpenStreetMap
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
      }).addTo(this.map);

      // Dibujar segmentos
      if (this.segmentos && this.segmentos.length) {
        this.dibujarSegmentos();
      } else {
        console.warn(" No hay segmentos para mostrar en el mapa");
      }
    },

    dibujarSegmentos() {
      const bounds = [];

      this.segmentos.forEach(seg => {
        if (seg.cordenadas && seg.cordenadas.length) {
          const puntos = seg.cordenadas.map(c => [
            parseFloat(c.y),
            parseFloat(c.x)
          ]);

          const polygon = L.polygon(puntos, {
            color: seg.color || "#FF0000",
            weight: 2,
            fillOpacity: 0.4
          }).addTo(this.map);

          this.polygons.push(polygon);
          bounds.push(...puntos);
        }
      });

      if (bounds.length) this.map.fitBounds(bounds);
    }
  }
};
</script>

<style>
#map {
  height: 600px;
  width: 100%;
}
</style>
