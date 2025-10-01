import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

// Crée l'app Vue
const app = createApp(App)

// Active Pinia (gestion des stores)
const pinia = createPinia()
app.use(pinia)

// Active le router
app.use(router)

// ⚡ Initialisation de l'auth AVANT de monter l'app
const auth = useAuthStore(pinia)
auth.initAuth().then(() => {
  app.mount('#app')
})
