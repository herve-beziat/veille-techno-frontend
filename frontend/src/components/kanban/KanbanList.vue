<script setup lang="ts">
import { computed } from 'vue'
import Draggable from 'vuedraggable'
import KanbanCard from './KanbanCard.vue'
import type { KanbanListData } from '@/types/kanban'

const props = defineProps<{ list: KanbanListData }>()
const emit = defineEmits<{
  (e: 'card-change', payload: {
    listId: number
    event: {
      moved?: { oldIndex: number; newIndex: number }
      added?: { element: KanbanListData['cards'][number]; newIndex: number }
    }
  }): void
}>()

const list = computed(() => props.list)

function handleCardChange(event: {
  moved?: { oldIndex: number; newIndex: number }
  added?: { element: KanbanListData['cards'][number]; newIndex: number }
}) {
  emit('card-change', { listId: list.value.id, event })
}
</script>

<template>
  <div class="kanban-column" :data-list-id="list.id">
    <h2 class="kanban-column__title">{{ list.title }}</h2>

    <Draggable
      class="kanban-column__cards"
      ghost-class="kanban-card--ghost"
      :list="list.cards"
      item-key="id"
      group="kanban-cards"
      :animation="200"
      @change="handleCardChange"
    >
      <template #item="{ element }">
        <KanbanCard :card="element" />
      </template>

      <template #footer>
        <p v-if="!list.cards.length" class="kanban-column__empty">Aucune carte dans cette liste.</p>
      </template>
    </Draggable>
  </div>
</template>

<style scoped>
.kanban-column {
  background: #f9f9f9;
  border: 1px solid #ddd;
  padding: 1rem;
  width: 250px;
  border-radius: 8px;
  min-height: 160px;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.kanban-column__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.kanban-column__cards {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-height: 120px;
}

.kanban-card--ghost {
  opacity: 0.6;
}

.kanban-column__empty {
  color: #9ca3af;
  font-style: italic;
  text-align: center;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  padding: 0.75rem;
  background-color: #f9fafb;
}
</style>