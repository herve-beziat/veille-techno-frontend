<script setup lang="ts">
defineProps<{ title: string }>()
import KanbanCard from './KanbanCard.vue'
</script>

<template>
  <div class="kanban-column">
    <h2>{{ title }}</h2>
    <KanbanCard title="Tâche exemple" />
  </div>
</template>

<style scoped>
.kanban-column {
  background: #f9f9f9;
  border: 1px solid #ddd;
  padding: 1rem;
  width: 250px;
}
</style>
