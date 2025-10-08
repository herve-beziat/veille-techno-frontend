<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import api from '@/services/api'
import { isAxiosError } from 'axios'
import AppModal from '@/components/ui/AppModal.vue'
import type { KanbanListData, KanbanCardData } from '@/types/kanban'

const props = defineProps<{
  modelValue: boolean
  list: KanbanListData | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'updated', list: KanbanListData): void
  (e: 'delete', id: number): void
}>()

// --- États locaux
const title = ref('')
const isSaving = ref(false)
const isDeleting = ref(false)
const error = ref<string | null>(null)

const isOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

// 🔄 Synchronise les données de la liste à éditer
watch(
  () => props.list,
  (newList) => {
    if (newList) {
      title.value = newList.title
      error.value = null
    } else {
      title.value = ''
      error.value = null
    }
  },
  { immediate: true },
)

// 💾 Sauvegarde la modification
async function saveChanges() {
  if (!props.list) return

  const trimmedTitle = title.value.trim()
  if (!trimmedTitle) {
    error.value = 'Le titre de la liste est requis.'
    return
  }

  isSaving.value = true
  error.value = null

  try {
    const { data } = await api.put(`/boardlists/${props.list.id}`, {
      title: trimmedTitle,
    })

    emit('updated', data)
    emit('update:modelValue', false)
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      error.value =
        err.response?.data?.error ?? 'Impossible de mettre à jour la liste.'
    } else {
      error.value = 'Erreur inconnue lors de la mise à jour.'
    }
  } finally {
    isSaving.value = false
  }
}

// 🗑️ Suppression de la liste
async function deleteList() {
  if (!props.list) return
  if (!confirm('Voulez-vous vraiment supprimer cette liste et toutes ses cartes ?')) return

  isDeleting.value = true
  error.value = null

  try {
    await api.delete(`/boardlists/${props.list.id}`)
    emit('delete', props.list.id)
    emit('update:modelValue', false)
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      error.value =
        err.response?.data?.error ?? 'Impossible de supprimer la liste.'
    } else {
      error.value = 'Erreur inconnue lors de la suppression.'
    }
  } finally {
    isDeleting.value = false
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <AppModal v-model="isOpen">
    <template #header>
      Modifier la liste
      <span v-if="list" class="modal-subtitle"> {{ list.title }}</span>
    </template>

    <form class="modal-form" @submit.prevent="saveChanges">
      <label class="modal-form__field">
        <span class="modal-form__label">Titre</span>
        <input
          v-model="title"
          type="text"
          class="modal-form__input"
          placeholder="Titre de la liste"
          required
        />
      </label>

      <p v-if="error" class="modal-form__error">{{ error }}</p>
    </form>

    <template #footer>
      <button
        type="button"
        class="modal-button modal-button--danger"
        :disabled="isDeleting"
        @click="deleteList"
      >
        {{ isDeleting ? 'Suppression…' : 'Supprimer la liste' }}
      </button>
      <button
        type="button"
        class="modal-button modal-button--ghost"
        @click="close"
      >
        Annuler
      </button>
      <button
        class="modal-button"
        :disabled="isSaving"
        @click="saveChanges"
      >
        {{ isSaving ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
    </template>
  </AppModal>
</template>

<style scoped>
.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-form__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.modal-form__label {
  font-weight: 600;
  color: #1f2937;
}

.modal-form__input {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  font-size: 0.95rem;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
  font-family: inherit;
}

.modal-form__input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
  outline: none;
}

.modal-form__error {
  color: #dc2626;
  font-size: 0.9rem;
  margin-top: -0.5rem;
}

/* Boutons */
.modal-button {
  border: none;
  border-radius: 9999px;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
  background: linear-gradient(135deg, #6366f1, #4338ca);
  color: #fff;
}

.modal-button:hover:not(:disabled) {
  transform: translateY(-1px);
}

.modal-button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.modal-button--ghost {
  background: #eef2ff;
  color: #4338ca;
}

.modal-button--ghost:hover {
  background: #e0e7ff;
}

.modal-button--danger {
  background: #dc2626;
  color: #fff;
}

.modal-button--danger:hover {
  background: #b91c1c;
}

/* Petit sous-titre */
.modal-subtitle {
  font-weight: 400;
  font-size: 0.9rem;
  color: #4b5563;
  margin-left: 0.5rem;
}
</style>
