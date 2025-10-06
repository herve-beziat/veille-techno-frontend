<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useBoardUiStore } from '@/stores/board-ui'
import { useBoardDataStore } from '@/stores/board-data'

const auth = useAuthStore()
const router = useRouter()
const showKanban = ref(false)
const boardUi = useBoardUiStore()
const boardData = useBoardDataStore()

const boardLists = computed(() => boardData.lists)
const selectedListId = ref<number | null>(null)

watch(
  boardLists,
  (lists) => {
    if (selectedListId.value && !lists.some((list) => list.id === selectedListId.value)) {
      selectedListId.value = null
    }
  },
  { immediate: true },
)

async function ensureBoardRouteIsActive() {
  if (router.currentRoute.value.name !== 'board') {
    await router.push({ name: 'board' })
  }
  showKanban.value = true
}

async function handleKanbanClick() {
  await ensureBoardRouteIsActive()
}

function toggleKanbanMenu() {
  showKanban.value = !showKanban.value
}

async function handleCreateListClick() {
  await ensureBoardRouteIsActive()
  boardUi.openCreateListModal()
}

async function handleListClick(listId: number) {
  await ensureBoardRouteIsActive()
  selectedListId.value = listId
}

async function handleCreateCardClick() {
  if (!selectedListId.value) {
    return
  }

  await ensureBoardRouteIsActive()
  boardUi.openCreateCardModal(selectedListId.value)
}

function handleLogout() {
  auth.logout()
  boardData.clearLists()
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
          <p v-if="!boardLists.length" class="submenu__empty">Aucune liste pour le moment.</p>
          <ul v-else class="submenu__list">
            <li v-for="list in boardLists" :key="list.id" class="submenu__list-item">
              <button
                type="button"
                class="sublink sublink--list"
                :class="{ 'sublink--active': selectedListId === list.id }"
                @click="handleListClick(list.id)"
              >
                {{ list.title }}
              </button>
              <button
                v-if="selectedListId === list.id"
                type="button"
                class="sublink sublink--button submenu__list-action"
                @click.stop="handleCreateCardClick"
              >
                ➕ Nouvelle carte
              </button>
            </li>
          </ul>
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

.submenu__empty {
  font-size: 0.85rem;
  color: #6b7280;
  margin: 0;
}

.submenu__list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.submenu__list-item {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.submenu__list-action {
  padding-left: 1.5rem;
}

.sublink {
  text-decoration: none;
  color: #374151;
  font-size: 0.95rem;
}

.sublink--list {
  border: none;
  background: none;
  padding: 0;
  text-align: left;
  cursor: pointer;
}

.sublink--active {
  font-weight: 600;
  color: #111827;
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