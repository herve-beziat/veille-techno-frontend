<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { isAxiosError } from 'axios'
import KanbanBoard from '../components/kanban/KanbanBoard.vue'
import AppModal from '@/components/ui/AppModal.vue'
import api from '@/services/api'
import type {
  KanbanBoardChange,
  KanbanCardData,
  KanbanListData,
  CardAllResponse,
  KanbanCategory,
} from '@/types/kanban'
import { useBoardUiStore } from '@/stores/board-ui'
import { useBoardDataStore } from '@/stores/board-data'
import KanbanCardEditModal from '@/components/kanban/KanbanCardEditModal.vue'

type BoardListResponse = {
  id: number
  title: string
  position?: number | null
  ownerId?: number | null
}

type CreateListResponse = {
  id: number
  position?: number | null
}

type CreateCardResponse = {
  id: number
  title: string
  description: string | null
  position: number
  listId: number
  createdAt?: string | null
  categoryId?: number | null
  categoryName?: string | null
  categoryColor?: string | null
}

const boardUi = useBoardUiStore()
const boardData = useBoardDataStore()

const lists = computed(() => boardData.lists)
const isLoading = ref(false)
const error = ref<string | null>(null)

const newListTitle = ref('')
const createListError = ref<string | null>(null)
const isCreatingList = ref(false)

const newCardTitle = ref('')
const newCardDescription = ref('')
const newCardCategoryId = ref<number | null>(null)
const createCardError = ref<string | null>(null)
const isCreatingCard = ref(false)

const categories = ref<KanbanCategory[]>([])
const isLoadingCategories = ref(false)
const categoriesError = ref<string | null>(null)
const boardMutationError = ref<string | null>(null)

// --- Gestion de l'édition d'une carte ---
const isEditCardModalOpen = ref(false)
const editingCard = ref<(KanbanCardData & { listId: number }) | null>(null)
const editCardTitle = ref('')
const editCardDescription = ref('')
const editCardError = ref<string | null>(null)
const isUpdatingCard = ref(false)

let boardMutationErrorTimeout: ReturnType<typeof setTimeout> | null = null

const isCreateListModalOpen = computed({
  get: () => boardUi.createListModalOpen,
  set: (value: boolean) => {
    if (value) {
      boardUi.openCreateListModal()
    } else {
      boardUi.closeCreateListModal()
    }
  },
})

const isCreateCardModalOpen = computed({
  get: () => boardUi.createCardModalForListId !== null,
  set: (value: boolean) => {
    if (!value) {
      boardUi.closeCreateCardModal()
    }
  },
})

const selectedListId = computed(() => boardUi.createCardModalForListId)
const targetList = computed(
  () => lists.value.find((list) => list.id === selectedListId.value) ?? null,
)

// --------------------------------------------------
// Fonctions utilitaires
// --------------------------------------------------
function sortListsByPosition(boardLists: KanbanListData[]): KanbanListData[] {
  return [...boardLists].sort((a, b) => {
    const posA = a.position ?? Number.MAX_SAFE_INTEGER
    const posB = b.position ?? Number.MAX_SAFE_INTEGER
    return posA - posB
  })
}

function sortCardsByPosition(cards: KanbanCardData[]): KanbanCardData[] {
  return [...cards].sort((a, b) => a.position - b.position)
}

function cloneBoardLists(boardLists: KanbanListData[]): KanbanListData[] {
  return boardLists.map((list) => ({
    ...list,
    cards: list.cards.map((card) => ({ ...card })),
  }))
}

function clearBoardMutationError() {
  if (boardMutationErrorTimeout) {
    clearTimeout(boardMutationErrorTimeout)
    boardMutationErrorTimeout = null
  }
  boardMutationError.value = null
}

function showBoardMutationError(message: string) {
  clearBoardMutationError()
  boardMutationError.value = message
  boardMutationErrorTimeout = setTimeout(() => {
    boardMutationError.value = null
    boardMutationErrorTimeout = null
  }, 5000)
}

function resetListForm() {
  newListTitle.value = ''
  createListError.value = null
  isCreatingList.value = false
}

function resetCardForm() {
  newCardTitle.value = ''
  newCardDescription.value = ''
  newCardCategoryId.value = null
  createCardError.value = null
  isCreatingCard.value = false
}

// --------------------------------------------------
// Watchers
// --------------------------------------------------
watch(
  () => isCreateListModalOpen.value,
  (isOpen) => {
    if (isOpen) {
      createListError.value = null
    } else {
      resetListForm()
    }
  },
)

watch(
  () => selectedListId.value,
  (listId, oldListId) => {
    if (!listId) {
      resetCardForm()
    } else {
      if (listId !== oldListId) {
        resetCardForm()
      }
      createCardError.value = null
    }
  },
)

// --------------------------------------------------
// Gestion des cartes
// --------------------------------------------------
function handleCardSelect(payload: { listId: number; card: KanbanCardData }) {
  const { listId, card } = payload

  editingCard.value = { ...card, listId }
  editCardTitle.value = card.title
  editCardDescription.value = card.description ?? ''
  editCardError.value = null
  isEditCardModalOpen.value = true
}

// --------------------------------------------------
// Board global
// --------------------------------------------------
async function handleBoardChange(change: KanbanBoardChange) {
  const previousState = cloneBoardLists(lists.value)
  boardData.setLists(change.lists)

  try {
    if (change.type === 'reorder-lists') {
      await api.post('/boardlists/reorder', { lists: change.order })
    } else {
      await api.post('/cards/reorder', { cards: change.updates })
    }
    clearBoardMutationError()
  } catch (err: unknown) {
    boardData.setLists(previousState)

    if (isAxiosError(err)) {
      const fallbackMessage =
        change.type === 'reorder-lists'
          ? 'Impossible de réordonner les listes pour le moment.'
          : 'Impossible de réordonner les cartes pour le moment.'

      showBoardMutationError(err.response?.data?.error ?? fallbackMessage)
    } else {
      showBoardMutationError(
        change.type === 'reorder-lists'
          ? 'Impossible de réordonner les listes pour le moment.'
          : 'Impossible de réordonner les cartes pour le moment.',
      )
    }
  }
}

async function fetchBoard() {
  isLoading.value = true
  error.value = null

  try {
    const [{ data: boardLists }, { data: allCards }] = await Promise.all([
      api.get<BoardListResponse[]>('/boardlists/all'),
      api.get<CardAllResponse[]>('/cards/all'),
    ])

    const cardsByListId = allCards.reduce((acc, card) => {
      if (!acc.has(card.listId)) {
        acc.set(card.listId, [])
      }
      acc.get(card.listId)?.push(card)
      return acc
    }, new Map<number, CardAllResponse[]>())

    const listsWithCards = boardLists.map((list) => {
      const cardsForList = cardsByListId.get(list.id) ?? []

      return {
        id: list.id,
        title: list.title,
        position: list.position ?? null,
        cards: sortCardsByPosition(
          cardsForList.map((card) => ({
            id: card.id,
            title: card.title,
            description: card.description,
            position: card.position,
            listId: card.listId ?? list.id,
            createdAt: card.createdAt ?? null,
            updatedAt: card.updatedAt ?? null,
            categoryId: card.categoryId ?? null,
            categoryName: card.categoryName ?? null,
            categoryColor: card.categoryColor ?? null,
          })),
        ),
      }
    })

    boardData.setLists(sortListsByPosition(listsWithCards))
    clearBoardMutationError()
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      if (err.response?.status === 401) {
        error.value = 'Veuillez vous reconnecter pour voir les listes et cartes.'
      } else {
        error.value =
          err.response?.data?.error ?? 'Impossible de charger le tableau pour le moment.'
      }
    } else {
      error.value = 'Impossible de charger le tableau pour le moment.'
    }
  } finally {
    isLoading.value = false
  }
}

// --------------------------------------------------
// Catégories
// --------------------------------------------------
async function fetchCategories() {
  isLoadingCategories.value = true
  categoriesError.value = null
  try {
    const { data } = await api.get<KanbanCategory[]>('/categories')
    categories.value = data
  } catch {
    categoriesError.value = 'Impossible de charger les catégories.'
  } finally {
    isLoadingCategories.value = false
  }
}

// --------------------------------------------------
// Création
// --------------------------------------------------
async function submitCreateList() {
  if (!newListTitle.value.trim()) {
    createListError.value = 'Le titre de la liste est requis.'
    return
  }

  isCreatingList.value = true
  createListError.value = null

  try {
    const { data } = await api.post<CreateListResponse>('/boardlists', {
      title: newListTitle.value.trim(),
    })

    const updatedLists = sortListsByPosition([
      ...lists.value,
      {
        id: data.id,
        title: newListTitle.value.trim(),
        position: data.position ?? null,
        cards: [],
      },
    ])

    boardData.setLists(updatedLists)
    clearBoardMutationError()
    boardUi.closeCreateListModal()
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      if (err.response?.status === 401) {
        createListError.value = 'Vous devez être connecté pour créer une liste.'
      } else {
        createListError.value =
          err.response?.data?.error ?? 'Impossible de créer la liste pour le moment.'
      }
    } else {
      createListError.value = 'Impossible de créer la liste pour le moment.'
    }
  } finally {
    isCreatingList.value = false
  }
}

async function submitCreateCard() {
  if (!targetList.value) {
    createCardError.value = 'La liste sélectionnée est introuvable.'
    return
  }

  if (!newCardTitle.value.trim()) {
    createCardError.value = 'Le titre de la carte est requis.'
    return
  }

  isCreatingCard.value = true
  createCardError.value = null

  try {
    const payload = {
      list_id: targetList.value.id,
      title: newCardTitle.value.trim(),
      description: newCardDescription.value.trim(),
      category_id: newCardCategoryId.value,
    }

    const { data } = await api.post<CreateCardResponse>('/cards', payload)

    const listIndex = lists.value.findIndex((list) => list.id === targetList.value?.id)
    if (listIndex !== -1) {
      const list = lists.value[listIndex]
      if (!list) {
        createCardError.value = 'La liste sélectionnée est introuvable.'
        return
      }
      const updatedCards = sortCardsByPosition([
        ...list.cards,
        {
          id: data.id,
          title: data.title,
          description: data.description,
          position: data.position,
          listId: targetList.value.id,
          createdAt: data.createdAt ?? null,
          categoryId: data.categoryId ?? null,
          categoryName: data.categoryName ?? null,
          categoryColor: data.categoryColor ?? null,
        },
      ]).map((card) => ({
        ...card,
        listId: targetList.value?.id ?? card.listId ?? list.id,
      }))

      const updatedLists = [...lists.value]
      updatedLists.splice(listIndex, 1, {
        ...list,
        cards: updatedCards,
      })
      boardData.setLists(sortListsByPosition(updatedLists))
      clearBoardMutationError()
    }

    boardUi.closeCreateCardModal()
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      if (err.response?.status === 401) {
        createCardError.value = 'Vous devez être connecté pour créer une carte.'
      } else {
        createCardError.value =
          err.response?.data?.error ?? 'Impossible de créer la carte pour le moment.'
      }
    } else {
      createCardError.value = 'Impossible de créer la carte pour le moment.'
    }
  } finally {
    isCreatingCard.value = false
  }
}

async function submitEditCard() {
  const cardToEdit = editingCard.value
  if (!cardToEdit) {
    editCardError.value = 'La carte sélectionnée est introuvable.'
    return
  }

  const trimmedTitle = editCardTitle.value.trim()
  if (!trimmedTitle) {
    editCardError.value = 'Le titre de la carte est requis.'
    return
  }

  isUpdatingCard.value = true
  editCardError.value = null

  try {
    const { data } = await api.put(`/cards/${cardToEdit.id}`, {
      title: trimmedTitle,
      description: editCardDescription.value,
    })

    const updatedLists = lists.value.map((list) => {
      if (list.id !== cardToEdit.listId) return list

      return {
        ...list,
        cards: list.cards.map((card) =>
          card.id === cardToEdit.id
            ? {
                ...card,
                title: data.title ?? trimmedTitle,
                description: data.description ?? editCardDescription.value,
              }
            : card,
        ),
      }
    })

    boardData.setLists(updatedLists)
    isEditCardModalOpen.value = false
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      editCardError.value = err.response?.data?.error ?? 'Impossible de mettre à jour la carte.'
    } else {
      editCardError.value = 'Erreur inattendue.'
    }
  } finally {
    isUpdatingCard.value = false
  }
}

function onCardUpdated(updatedCard: KanbanCardData) {
  const updatedLists = lists.value.map((list) => {
    if (list.id !== updatedCard.listId) return list

    return {
      ...list,
      cards: list.cards.map((card) =>
        card.id === updatedCard.id ? { ...card, ...updatedCard } : card,
      ),
    }
  })

  boardData.setLists(updatedLists)
  isEditCardModalOpen.value = false
}

function onCardDeleted(cardId: number) {
  const updatedLists = lists.value.map((list) => ({
    ...list,
    cards: list.cards.filter((c) => c.id !== cardId),
  }))
  boardData.setLists(updatedLists)
  isEditCardModalOpen.value = false
}

onMounted(() => {
  fetchBoard()
  fetchCategories()
})
</script>

<template>
  <div class="board-view">
    <h1>Mon Tableau Kanban</h1>

    <p v-if="isLoading" class="board-view__status">Chargement du tableau…</p>
    <p v-else-if="error" class="board-view__status board-view__status--error">{{ error }}</p>
    <KanbanBoard
      v-else
      :lists="lists"
      @board-change="handleBoardChange"
      @card-select="handleCardSelect"
    />

    <p
      v-if="boardMutationError && !isLoading && !error"
      class="board-view__status board-view__status--warning"
    >
      {{ boardMutationError }}
    </p>

    <AppModal v-model="isCreateListModalOpen">
      <template #header>Créer une nouvelle liste</template>

      <form id="create-list-form" class="modal-form" @submit.prevent="submitCreateList">
        <label class="modal-form__field">
          <span class="modal-form__label">Titre de la liste</span>
          <input
            v-model="newListTitle"
            type="text"
            name="list-title"
            class="modal-form__input"
            placeholder="Ex. À faire"
            autocomplete="off"
            required
          />
        </label>

        <p v-if="createListError" class="modal-form__error">{{ createListError }}</p>
      </form>

      <template #footer>
        <button
          type="button"
          class="modal-button modal-button--ghost"
          @click="isCreateListModalOpen = false"
        >
          Annuler
        </button>
        <button
          type="submit"
          form="create-list-form"
          class="modal-button"
          :disabled="isCreatingList"
        >
          {{ isCreatingList ? 'Création…' : 'Créer' }}
        </button>
      </template>
    </AppModal>

    <AppModal v-model="isCreateCardModalOpen">
      <template #header>
        Nouvelle carte
        <span v-if="targetList" class="modal-subtitle">— {{ targetList.title }}</span>
      </template>

      <form id="create-card-form" class="modal-form" @submit.prevent="submitCreateCard">
        <label class="modal-form__field">
          <span class="modal-form__label">Titre de la carte</span>
          <input
            v-model="newCardTitle"
            type="text"
            name="card-title"
            class="modal-form__input"
            placeholder="Ex. Contacter le client"
            autocomplete="off"
            required
          />
        </label>

        <label class="modal-form__field">
          <span class="modal-form__label">Description</span>
          <textarea
            v-model="newCardDescription"
            name="card-description"
            class="modal-form__textarea"
            rows="4"
            placeholder="Détails de la tâche"
          ></textarea>
        </label>

        <!-- Sélecteur Catégorie -->
        <label class="modal-form__field">
          <span class="modal-form__label">Catégorie</span>
          <select
            v-model="newCardCategoryId"
            class="modal-form__input"
            :disabled="isLoadingCategories"
          >
            <option :value="null">Aucune catégorie</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </label>

        <p v-if="createCardError" class="modal-form__error">{{ createCardError }}</p>
      </form>

      <template #footer>
        <button
          type="button"
          class="modal-button modal-button--ghost"
          @click="isCreateCardModalOpen = false"
        >
          Annuler
        </button>
        <button
          type="submit"
          form="create-card-form"
          class="modal-button"
          :disabled="isCreatingCard"
        >
          {{ isCreatingCard ? 'Création…' : 'Créer la carte' }}
        </button>
      </template>
    </AppModal>

    <KanbanCardEditModal
      v-model="isEditCardModalOpen"
      :card="editingCard"
      :list-title="lists.find((l) => l.id === editingCard?.listId)?.title ?? null"
      @updated="onCardUpdated"
      @delete="onCardDeleted"
    />
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

.board-view__status--warning {
  color: #b45309;
  background-color: #fef3c7;
  border: 1px solid #f59e0b;
  padding: 0.5rem 0.75rem;
  border-radius: 6px;
  margin-top: 0.5rem;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.modal-form__label {
  font-weight: 600;
  color: #1f2937;
}

.modal-form__input,
.modal-form__textarea {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  font-size: 0.95rem;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
  font-family: inherit;
}

.modal-form__input:focus,
.modal-form__textarea:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
  outline: none;
}

.modal-form__textarea {
  resize: vertical;
}

.modal-form__error {
  color: #dc2626;
  font-size: 0.9rem;
  margin-top: -0.5rem;
}

.modal-button {
  border: none;
  border-radius: 9999px;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    background 0.2s ease,
    transform 0.2s ease;
  background: linear-gradient(135deg, #6366f1, #4338ca);
  color: #fff;
}

.modal-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.modal-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.modal-button--ghost {
  background: #eef2ff;
  color: #4338ca;
}

.modal-button--ghost:hover {
  background: #e0e7ff;
}

.modal-subtitle {
  font-weight: 400;
  font-size: 0.9rem;
  color: #4b5563;
  margin-left: 0.5rem;
}
</style>
