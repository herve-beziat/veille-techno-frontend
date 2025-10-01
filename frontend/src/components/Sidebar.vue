<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const auth = useAuthStore()
const router = useRouter()
const showKanban = ref(false)

function toggleKanban() {
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
        <div class="menu-item" @click="toggleKanban">
          📋 Tableau Kanban
          <span class="arrow">{{ showKanban ? "▼" : "▶" }}</span>
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

nav {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.link {
  text-decoration: none;
  color: #333;
  font-weight: 500;
}

.link:hover {
  color: #42b983;
}

.menu-item {
  cursor: pointer;
  font-weight: 500;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.arrow {
  font-size: 0.8rem;
}

.submenu {
  margin-left: 1rem;
  margin-top: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.sublink {
  text-decoration: none;
  color: #666;
}

.sublink:hover {
  color: #42b983;
}

.logout {
  background: none;
  border: none;
  cursor: pointer;
  color: #c00;
  font-weight: 500;
  text-align: left;
  padding: 0;
}

.logout:hover {
  text-decoration: underline;
}
</style>
