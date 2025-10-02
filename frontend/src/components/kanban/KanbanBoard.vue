<script setup lang="ts">
import KanbanList from './KanbanList.vue'

defineProps<{ title: string }>()
</script>

<template>
  <div class="kanban-board">
    <KanbanList title="À faire" />
    <KanbanList title="En cours" />
    <KanbanList title="Fini" />
  </div>
</template>

<style scoped>
.kanban-board {
  display: flex;
  gap: 1rem;
}
</style>
