// stores/segmentosStore.js
import { defineStore } from 'pinia'

export const useSegmentosStore = defineStore('segmentos', {
  state: () => ({
    segmentos: [],
    loading: false,
    lastUpdate: null
  }),
  
  getters: {
    isCacheValid: (state) => {
      if (!state.lastUpdate) return false
      return Date.now() - state.lastUpdate < 30 * 60 * 1000
    }
  },
  
  actions: {
    async cargarSegmentos(force = false) {
      if (this.isCacheValid && !force) return
      
      this.loading = true
      const data = await fetch('/api/segmentos').then(r => r.json())
      this.segmentos = data.segmentos
      this.lastUpdate = Date.now()
      this.loading = false
    }
  },
  
  persist: true //  Auto-persistencia
})