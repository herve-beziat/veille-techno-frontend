import { defineStore } from 'pinia'
import api from '../services/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    isLoggedIn: false,
    user: null as null | { id: number; email: string; name: string }
  }),

  actions: {
    // Sauvegarde du token dans le localStorage
    saveToken(token: string) {
      localStorage.setItem('token', token)
      this.isLoggedIn = true
    },

    // Supprime le token et réinitialise l'état
    clearToken() {
      localStorage.removeItem('token')
      this.isLoggedIn = false
      this.user = null
    },

    // Connexion : POST /api/login_check
    async login(email: string, password: string) {
      const { data } = await api.post('/login_check', { email, password })
      const token = data.token || data.jwt // selon config LexikJWT
      if (!token) throw new Error('Token JWT manquant')
      this.saveToken(token)
      return token
    },

    // Inscription : POST /api/register
    async register(name: string, email: string, password: string) {
      try {
        await api.post('/register', { name, email, password })
        return true
      } catch (e: any) {
        if (e.response?.status === 409) {
          throw new Error('Cet email est déjà utilisé ❌')
        }
        throw new Error("Erreur lors de l’inscription ❌")
      }
    },

    // Récupère l'utilisateur courant : GET /api/me
    async fetchMe() {
      try {
        const { data } = await api.get('/me')
        this.isLoggedIn = true
        this.user = data
      } catch {
        this.clearToken()
      }
    },

    // Déconnexion
    logout() {
      this.clearToken()
    },

    // Vérifie l'état au démarrage de l'application
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
