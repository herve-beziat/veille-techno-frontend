import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

// ⚡ Important : initialiser l'auth AVANT de monter l'app
const auth = useAuthStore(pinia)
auth.initAuth().then(() => {
  app.mount('#app')
})
