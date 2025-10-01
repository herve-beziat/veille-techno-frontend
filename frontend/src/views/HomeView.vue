<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="home">
    <h1>Bienvenue sur notre appli Kanban 🎯</h1>
    <p>Organisez vos tâches facilement avec un tableau Kanban interactif.</p>

    <div class="content">
      <!-- Si pas connecté → boutons Connexion / Inscription -->
      <div v-if="!auth.isLoggedIn" class="actions">
        <RouterLink to="/login" class="btn">Connexion</RouterLink>
        <RouterLink to="/register" class="btn">Inscription</RouterLink>
      </div>

      <!-- Si connecté → simple message -->
      <div v-else>
        <p>Bonjour {{ auth.user?.name }} 👋</p>
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
