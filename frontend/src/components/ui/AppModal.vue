<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'

const props = defineProps<{
  modelValue: boolean
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'close'): void
}>()

function close() {
  emit('update:modelValue', false)
  emit('close')
}

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.modelValue) {
    close()
  }
}

function handleOverlayClick() {
  close()
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<template>
  <Teleport to="body">
    <div v-if="modelValue" class="modal-overlay" @click.self="handleOverlayClick">
      <div class="modal" role="dialog" aria-modal="true">
        <header v-if="$slots.header" class="modal__header">
          <slot name="header" />
          <button type="button" class="modal__close" aria-label="Fermer" @click="close">×</button>
        </header>

        <button
          v-else
          type="button"
          class="modal__close modal__close--no-header"
          aria-label="Fermer"
          @click="close"
        >
          ×
        </button>

        <section class="modal__body">
          <slot />
        </section>

        <footer v-if="$slots.footer" class="modal__footer">
          <slot name="footer" />
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  z-index: 1000;
}

.modal {
  background: #fff;
  border-radius: 12px;
  width: min(480px, 100%);
  box-shadow: 0 20px 45px rgba(15, 23, 42, 0.15);
  position: relative;
  overflow: hidden;
}

.modal__header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  font-weight: 600;
  font-size: 1.1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.modal__close {
  border: none;
  background: transparent;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
  padding: 0.25rem;
}

.modal__close:hover {
  color: #111827;
}

.modal__close--no-header {
  position: absolute;
  top: 0.5rem;
  right: 0.75rem;
}

.modal__body {
  padding: 1.5rem;
}

.modal__footer {
  padding: 1rem 1.5rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
</style>