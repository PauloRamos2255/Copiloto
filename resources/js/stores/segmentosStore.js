import { defineStore } from "pinia";
import axios from "axios";

export const useDataStore = defineStore("dataStore", {
  state: () => ({
    segmentos: null,
    rutas:   null,
    cargado: false,
  }),

  actions: {
    async cargarDatos() {
      // Si ya se cargó antes, NO volver a cargar
      if (this.cargado) return;

      try {
        const [respSegmentos, respRutas] = await Promise.all([
          axios.get("/api/segmentos"),
          axios.get("/api/rutas")
        ]);

        this.segmentos = respSegmentos.data;
        this.rutas = respRutas.data;
        this.cargado = true;
      } catch (e) {
        console.error("Error cargando datos:", e);
      }
    },

    // Si necesitas refrescar luego de crear / editar / eliminar
    limpiarCache() {
      this.segmentos = null;
      this.rutas = null;
      this.cargado = false;
    }
  }
});
