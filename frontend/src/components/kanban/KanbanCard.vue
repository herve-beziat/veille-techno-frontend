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
    <div v-if="card.categoryName" class="kanban-card__footer">
      <span
        class="kanban-card__category-dot"
        :style="{ backgroundColor: card.categoryColor ?? '#6b7280' }"
        aria-hidden="true"
      />
      <span class="kanban-card__category-label">{{ card.categoryName }}</span>
    </div>
  </div>
</template>

<style scoped>
.kanban-card {
  background: white;
  border: 1px solid #d1d5db;
  padding: 0.75rem;
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  cursor: grab;
  user-select: none;
}

.kanban-card__footer {
  margin-top: 0.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.75rem;
  font-weight: 500;
  color: #4b5563;
}

.kanban-card__category-dot {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 9999px;
  flex-shrink: 0;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.kanban-card__category-label {
  line-height: 1;
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