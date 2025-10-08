<script setup lang="ts">
import { ref, watch } from 'vue'
import Draggable from 'vuedraggable'
import KanbanList from './KanbanList.vue'
import type {
  KanbanBoardChange,
  KanbanCardPositionUpdate,
  KanbanListData,
  KanbanListPositionUpdate,
} from '@/types/kanban'

type ListChangeEvent = {
  moved?: {
    oldIndex: number
    newIndex: number
  }
}

type CardChangeEvent = {
  moved?: {
    oldIndex: number
    newIndex: number
  }
  added?: {
    element: KanbanListData['cards'][number]
    newIndex: number
  }
}

type CardChangePayload = {
  listId: number
  event: CardChangeEvent
}

const props = defineProps<{ lists: KanbanListData[] }>()
const emit = defineEmits<{
  (e: 'board-change', payload: KanbanBoardChange): void
  (e: 'card-select', payload: { listId: number; card: KanbanListData['cards'][number] }): void
  (e: 'list-select', payload: KanbanListData): void
}>()

const internalLists = ref<KanbanListData[]>([])

watch(
  () => props.lists,
  (newLists) => {
    internalLists.value = newLists.map((list) => ({
      ...list,
      position: list.position ?? null,
      cards: list.cards.map((card) => ({
        ...card,
        listId: card.listId ?? list.id,
      })),
    }))
  },
  { immediate: true, deep: true },
)

function cloneLists(): KanbanListData[] {
  return internalLists.value.map((list) => ({
    ...list,
    cards: list.cards.map((card) => ({ ...card })),
  }))
}

function updateListPositions() {
  internalLists.value.forEach((list, index) => {
    list.position = index + 1
  })
}

function updateCardPositions(list: KanbanListData) {
  list.cards.forEach((card, index) => {
    card.position = index + 1
    card.listId = list.id
  })
}

function handleListChange(event: ListChangeEvent) {
  if (!event.moved) {
    return
  }

  updateListPositions()

  const order: KanbanListPositionUpdate[] = internalLists.value.map((list) => ({
    id: list.id,
    position: list.position ?? 0,
  }))

  emit('board-change', {
    type: 'reorder-lists',
    order,
    lists: cloneLists(),
  } satisfies KanbanBoardChange)
}

function handleCardChange(payload: CardChangePayload) {
  const { listId, event } = payload

  const moved = event.moved
  const added = event.added

  const affectedLists = new Set<number>()

  if (moved) {
    const list = internalLists.value.find((item) => item.id === listId)
    if (!list) {
      return
    }
    updateCardPositions(list)
    affectedLists.add(list.id)
  } else if (added) {
    const targetList = internalLists.value.find((item) => item.id === listId)
    if (!targetList) {
      return
    }

    const sourceListId = added.element.listId ?? listId
    const sourceList = internalLists.value.find((item) => item.id === sourceListId)

    updateCardPositions(targetList)
    affectedLists.add(targetList.id)

    if (sourceList && sourceList.id !== targetList.id) {
      updateCardPositions(sourceList)
      affectedLists.add(sourceList.id)
    }
  } else {
    return
  }

  const updates: KanbanCardPositionUpdate[] = []

  affectedLists.forEach((id) => {
    const list = internalLists.value.find((item) => item.id === id)
    if (!list) {
      return
    }

    list.cards.forEach((card, index) => {
      updates.push({
        id: card.id,
        list_id: list.id,
        position: index + 1,
      })
    })
  })

  emit('board-change', {
    type: 'reorder-cards',
    affectedLists: Array.from(affectedLists),
    updates,
    lists: cloneLists(),
  } satisfies KanbanBoardChange)
}
</script>

<template>
  <div class="kanban-board">
    <Draggable
      class="kanban-board__lists"
      ghost-class="kanban-board__ghost"
      v-model="internalLists"
      item-key="id"
      group="kanban-lists"
      :animation="200"
      @change="handleListChange"
    >
      <template #item="{ element }">
        <div class="kanban-board__column">
          <KanbanList
            :list="element"
            @card-change="handleCardChange"
            @card-select="emit('card-select', $event)"
            @list-select="emit('list-select', $event)"
          />
        </div>
      </template>
    </Draggable>

    <p v-if="!internalLists.length" class="kanban-board__empty">
      Vous n'avez pas encore de listes.
    </p>
  </div>
</template>

<style scoped>
.kanban-board {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.kanban-board__lists {
  display: flex;
  gap: 0.5rem;
  align-items: flex-start;
}

.kanban-board__column {
  cursor: grab;
}

.kanban-board__ghost {
  opacity: 0.5;
}

.kanban-board__empty {
  color: #6b7280;
  font-style: italic;
}
</style>
