<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  error.value = ''
  loading.value = true

  try {
    // Appel au backend
    const token = await auth.login(email.value, password.value)

    // Récupère l'utilisateur connecté
    await auth.fetchMe()

    // Redirection vers la page d'accueil
    router.push('/')
  } catch (e) {
    error.value = 'Identifiants invalides ❌'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login">
    <h1>Connexion</h1>
    <form @submit.prevent="handleLogin">
      <label>
        Email :
        <input v-model="email" type="email" required />
      </label>

      <label>
        Mot de passe :
        <input v-model="password" type="password" required />
      </label>

      <button type="submit" :disabled="loading">
        {{ loading ? "Connexion..." : "Se connecter" }}
      </button>

      <p v-if="error" class="error">{{ error }}</p>
    </form>
  </div>
</template>

<style scoped>
.login {
  max-width: 400px;
  margin: 2rem auto;
  padding: 2rem;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
}
label {
  display: block;
  margin-bottom: 1rem;
}
input {
  width: 100%;
  padding: 0.5rem;
  margin-top: 0.25rem;
  border: 1px solid #ccc;
  border-radius: 4px;
}
button {
  margin-top: 1rem;
  padding: 0.6rem 1.2rem;
  background: #42b983;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}
button:disabled {
  background: #a0d8b3;
  cursor: not-allowed;
}
.error {
  color: #c00;
  margin-top: 0.5rem;
}
</style>
