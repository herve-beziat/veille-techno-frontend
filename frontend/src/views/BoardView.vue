<script setup lang="ts">
import { onMounted, ref } from 'vue'
import KanbanBoard from '../components/kanban/KanbanBoard.vue'
import api from '@/services/api'
import type { KanbanCardData, KanbanListData } from '@/types/kanban'

type BoardListResponse = {
  id: number
  title: string
  position?: number | null
}

const lists = ref<KanbanListData[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)

async function fetchBoard() {
  isLoading.value = true
  error.value = null

  try {
    const { data: boardLists } = await api.get<BoardListResponse[]>('/boardlists')

    const listsWithCards = await Promise.all(
      boardLists.map(async (list) => {
        const { data: cards } = await api.get<KanbanCardData[]>('/cards', {
          params: { list_id: list.id },
        })

        const sortedCards = [...cards].sort((a, b) => a.position - b.position)

        return {
          id: list.id,
          title: list.title,
          position: list.position ?? null,
          cards: sortedCards,
        }
      }),
    )

    lists.value = listsWithCards.sort((a, b) => {
      const posA = a.position ?? Number.MAX_SAFE_INTEGER
      const posB = b.position ?? Number.MAX_SAFE_INTEGER
      return posA - posB
    })
  } catch (err: any) {
    if (err.response?.status === 401) {
      error.value = 'Veuillez vous reconnecter pour voir vos listes.'
    } else {
      error.value = err.response?.data?.error ?? 'Impossible de charger vos listes pour le moment.'
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchBoard)
</script>

<template>
  <div class="board-view">
    <h1>Mon Tableau Kanban</h1>

    <p v-if="isLoading" class="board-view__status">Chargement du tableau…</p>
    <p v-else-if="error" class="board-view__status board-view__status--error">{{ error }}</p>
    <KanbanBoard v-else :lists="lists" />
  </div>
</template>

<style scoped>
.board-view {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.board-view__status {
  color: #4a4a4a;
}

.board-view__status--error {
  color: #d93025;
}
</style>