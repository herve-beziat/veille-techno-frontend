<script setup lang="ts">
import { computed } from 'vue'
import KanbanCard from './KanbanCard.vue'
import type { KanbanListData } from '@/types/kanban'

const props = defineProps<{ list: KanbanListData }>()

const list = computed(() => props.list)
const cards = computed(() => [...list.value.cards].sort((a, b) => a.position - b.position))
</script>

<template>
  <div class="kanban-column">
    <h2>{{ list.title }}</h2>
    <KanbanCard v-for="card in cards" :key="card.id" :card="card" />
    <p v-if="!cards.length" class="kanban-column__empty">Aucune carte dans cette liste.</p>
  </div>
</template>

<style scoped>
.kanban-column {
  background: #f9f9f9;
  border: 1px solid #ddd;
  padding: 1rem;
  width: 250px;
  border-radius: 8px;
  min-height: 120px;
}

.kanban-column__empty {
  color: #9ca3af;
  font-style: italic;
  margin-top: 0.5rem;
}
</style>