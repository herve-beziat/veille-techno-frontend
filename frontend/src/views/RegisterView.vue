<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')
const success = ref('')

async function handleRegister() {
  error.value = ''
  success.value = ''
  loading.value = true

  try {
    await auth.register(name.value, email.value, password.value)

    success.value = 'Inscription réussie ✅'
    // après 1 seconde → redirection vers la page de login
    setTimeout(() => router.push('/login'), 1000)

  } catch (e: any) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="register">
    <h1>Inscription</h1>
    <form @submit.prevent="handleRegister">
      <label>
        Nom :
        <input v-model="name" type="text" required />
      </label>

      <label>
        Email :
        <input v-model="email" type="email" required />
      </label>

      <label>
        Mot de passe :
        <input v-model="password" type="password" required />
      </label>

      <button type="submit" :disabled="loading">
        {{ loading ? "Inscription..." : "S'inscrire" }}
      </button>

      <p v-if="error" class="error">{{ error }}</p>
      <p v-if="success" class="success">{{ success }}</p>
    </form>
  </div>
</template>

<style scoped>
.register {
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
.success {
  color: #0a0;
  margin-top: 0.5rem;
}
</style>
