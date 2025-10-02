<script setup lang="ts">
defineProps<{ title: string }>()
</script>

<template>
  <div class="kanban-card">
    {{ title }}
  </div>
</template>

<style scoped>
.kanban-card {
  background: white;
  border: 1px solid #ccc;
  padding: 0.5rem;
  margin-top: 0.5rem;
  border-radius: 4px;
}
</style>
