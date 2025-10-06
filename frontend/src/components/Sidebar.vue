<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useBoardUiStore } from '@/stores/board-ui'

const auth = useAuthStore()
const router = useRouter()
const showKanban = ref(false)
const boardUi = useBoardUiStore()

function handleKanbanClick() {
  router.push({ name: 'board' })
  showKanban.value = true
}

function toggleKanbanMenu() {
  showKanban.value = !showKanban.value
}

async function handleCreateListClick() {
  if (router.currentRoute.value.name !== 'board') {
    await router.push({ name: 'board' })
  }
  showKanban.value = true
  boardUi.openCreateListModal()
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
          <button type="button" class="sublink sublink--button" @click="handleCreateListClick">
            ➕ Nouvelle liste
          </button>
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

.submenu {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin: 0.75rem 0 0 1rem;
}

.sublink {
  text-decoration: none;
  color: #374151;
  font-size: 0.95rem;
}

.sublink--button {
  border: none;
  background: none;
  padding: 0;
  text-align: left;
  cursor: pointer;
}

.sublink--button:hover {
  color: #111827;
}
</style>