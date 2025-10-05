<script setup lang="ts">
import KanbanList from './KanbanList.vue'
import type { KanbanListData } from '@/types/kanban'

defineProps<{ lists: KanbanListData[] }>()
</script>

<template>
  <div class="kanban-board">
    <KanbanList v-for="list in lists" :key="list.id" :list="list" />
    <p v-if="!lists.length" class="kanban-board__empty">Vous n'avez pas encore de listes.</p>
  </div>
</template>

<style scoped>
.kanban-board {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.kanban-board__empty {
  color: #6b7280;
  font-style: italic;
}
</style>