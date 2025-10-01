import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isLoggedIn: false,
    user: null as null | { id: number; email: string; name: string }
  }),

  actions: {
    saveToken(token: string) {
      localStorage.setItem('token', token)
      this.isLoggedIn = true
    },

    clearToken() {
      localStorage.removeItem('token')
      this.isLoggedIn = false
      this.user = null
    },

    async fetchMe() {
      try {
        const { data } = await api.get('/me')
        this.isLoggedIn = true
        this.user = data
      } catch {
        this.clearToken()
      }
    },

    logout() {
      this.clearToken()
    },

    // 🚀 Nouvelle action pour recharger l'état au démarrage
    async initAuth() {
      const token = localStorage.getItem('token')
      if (!token) {
        this.clearToken()
        return
      }
      await this.fetchMe()
    }
  }
})
