<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const showKanban = ref(false)

function handleKanbanClick() {
  router.push({ name: 'board' })
  showKanban.value = true
}

function toggleKanbanMenu() {
  showKanban.value = !showKanban.value
}

function handleLogout() {
  auth.logout()
  router.push('/')  // retour à l'accueil
}
</script>

<template>
  <aside class="sidebar">
    <nav>
      <RouterLink to="/" class="link">🏠 Accueil</RouterLink>

      <!-- Liens visibles uniquement si connecté -->
      <div v-if="auth.isLoggedIn">
        <div class="menu-item" @click="handleKanbanClick">
          📋 Tableau Kanban
          <span class="arrow" @click.stop="toggleKanbanMenu">{{ showKanban ? "▼" : "▶" }}</span>
        </div>
        <div v-if="showKanban" class="submenu">
          <RouterLink to="/board" class="sublink">➕ Nouvelle liste</RouterLink>
        </div>

        <!-- Bouton Déconnexion -->
        <button class="logout" @click="handleLogout">🚪 Déconnexion</button>
      </div>
    </nav>
  </aside>
</template>

<style scoped>
/* ton CSS reste identique */
.sidebar {
  width: 240px;
  height: 100vh;
  background: #f9f9f9;
  border-right: 1px solid #ddd;
  padding: 1rem;
  box-sizing: border-box;
  position: fixed;
  top: 0;
  left: 0;
}
</style>