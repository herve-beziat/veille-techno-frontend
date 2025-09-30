<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import Sidebar from '../components/Sidebar.vue'
import { isAuthenticated, fetchMe, clearToken } from '../services/auth'

const connected = ref(false)

onMounted(async () => {
  if (!isAuthenticated()) {
    connected.value = false
    return
  }

  try {
    await fetchMe()
    connected.value = true
  } catch (e) {
    // Si le token est invalide, on le supprime
    clearToken()
    connected.value = false
  }
})
</script>

<template>
  <div class="home">
    <h1>Bienvenue sur notre appli Kanban 🎯</h1>
    <p>Organisez vos tâches facilement avec un tableau Kanban interactif.</p>

    <div class="content">
      <!-- Si connecté → afficher la sidebar -->
      <Sidebar v-if="connected" />

      <!-- Sinon → boutons Connexion / Inscription -->
      <div v-else class="actions">
        <RouterLink to="/login" class="btn">Connexion</RouterLink>
        <RouterLink to="/register" class="btn">Inscription</RouterLink>
      </div>
    </div>
  </div>
</template>


<style scoped>
.content {
  display: flex;
  gap: 2rem;
  margin-top: 1rem;
}
.actions {
  display: flex;
  gap: 1rem;
}
.btn {
  padding: .6rem 1rem;
  border: 1px solid #42b983;
  border-radius: .5rem;
  text-decoration: none;
  color: #42b983;
}
</style>
