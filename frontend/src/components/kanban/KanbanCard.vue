<script setup lang="ts">
import { computed } from 'vue'
import type { KanbanCardData } from '@/types/kanban'

const props = defineProps<{ card: KanbanCardData }>()
const emit = defineEmits<{ (e: 'select', card: KanbanCardData): void }>()

const card = computed(() => props.card)

function handleClick() {
  emit('select', card.value)
}
</script>

<template>
  <div class="kanban-card" @click.stop="handleClick">
    <h3 class="kanban-card__title">{{ card.title }}</h3>
    <p v-if="card.description" class="kanban-card__description">{{ card.description }}</p>
  </div>
</template>

<style scoped>
.kanban-card {
  background: white;
  border: 1px solid #ccc;
  padding: 0.75rem;
  border-radius: 6px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  cursor: grab;
  user-select: none;
}

.kanban-card__title {
  font-size: 1rem;
  font-weight: 600;
  margin: 0;
}

.kanban-card__description {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #4b5563;
  white-space: pre-wrap;
}
</style>