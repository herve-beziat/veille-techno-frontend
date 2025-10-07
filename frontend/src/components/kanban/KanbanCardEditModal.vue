<script setup lang="ts">
import { ref, watch } from 'vue'
import api from '@/services/api'
import { isAxiosError } from 'axios'
import AppModal from '@/components/ui/AppModal.vue'
import type { KanbanCardData } from '@/types/kanban'
import { computed } from 'vue'

const props = defineProps<{
  modelValue: boolean
  card: KanbanCardData | null
  listTitle?: string | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
  (e: 'updated', card: KanbanCardData): void
  (e: 'delete', id: number): void
}>()

const title = ref('')
const description = ref('')
const isSaving = ref(false)
const error = ref<string | null>(null)

const isOpen = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value),
})

// 🔄 Synchronise les données de la carte à éditer
watch(
  () => props.card,
  (newCard) => {
    if (newCard) {
      title.value = newCard.title
      description.value = newCard.description ?? ''
      error.value = null
    }
  },
  { immediate: true }
)

async function saveChanges() {
  if (!props.card) return

  isSaving.value = true
  error.value = null

  try {
    const { data } = await api.put(`/cards/${props.card.id}`, {
      title: title.value.trim(),
      description: description.value.trim(),
    })

    emit('updated', data)
    emit('update:modelValue', false)
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      error.value = err.response?.data?.error ?? 'Impossible de mettre à jour la carte.'
    } else {
      error.value = 'Erreur inconnue lors de la mise à jour.'
    }
  } finally {
    isSaving.value = false
  }
}

async function deleteCard() {
  if (!props.card) return
  if (!confirm('Voulez-vous vraiment supprimer cette carte ?')) return

  try {
    await api.delete(`/cards/${props.card.id}`)
    emit('delete', props.card.id)
    emit('update:modelValue', false)
  } catch (err: unknown) {
    if (isAxiosError(err)) {
      error.value = err.response?.data?.error ?? 'Impossible de supprimer la carte.'
    } else {
      error.value = 'Erreur inconnue lors de la suppression.'
    }
  }
}

function close() {
  emit('update:modelValue', false)
}
</script>

<template>
  <AppModal v-model="isOpen">
    <template #header>
      Modifier la carte
      <span v-if="listTitle" class="modal-subtitle">— {{ listTitle }}</span>
    </template>

    <form class="modal-form" @submit.prevent="saveChanges">
      <label class="modal-form__field">
        <span class="modal-form__label">Titre</span>
        <input
          v-model="title"
          type="text"
          class="modal-form__input"
          placeholder="Titre de la carte"
          required
        />
      </label>

      <label class="modal-form__field">
        <span class="modal-form__label">Description</span>
        <textarea
          v-model="description"
          rows="4"
          class="modal-form__textarea"
          placeholder="Détails..."
        ></textarea>
      </label>

      <p v-if="error" class="modal-form__error">{{ error }}</p>
    </form>

    <template #footer>
      <button
        type="button"
        class="modal-button modal-button--danger"
        @click="deleteCard"
      >
        Supprimer la carte
      </button>
      <button type="button" class="modal-button modal-button--ghost" @click="close">
        Annuler
      </button>
      <button class="modal-button" :disabled="isSaving" @click="saveChanges">
        {{ isSaving ? 'Enregistrement…' : 'Enregistrer' }}
      </button>
    </template>
  </AppModal>
</template>

<style scoped>
/* ===== Styles de formulaire modale ===== */
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

.modal-form__input,
.modal-form__textarea {
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 0.65rem 0.85rem;
  font-size: 0.95rem;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
  font-family: inherit;
}

.modal-form__input:focus,
.modal-form__textarea:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
  outline: none;
}

.modal-form__textarea {
  resize: vertical;
}

.modal-form__error {
  color: #dc2626;
  font-size: 0.9rem;
  margin-top: -0.5rem;
}

/* ===== Boutons ===== */
.modal-button {
  border: none;
  border-radius: 9999px;
  padding: 0.6rem 1.25rem;
  font-weight: 600;
  cursor: pointer;
  transition:
    background 0.2s ease,
    transform 0.2s ease;
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

/* Bouton secondaire */
.modal-button--ghost {
  background: #eef2ff;
  color: #4338ca;
}

.modal-button--ghost:hover {
  background: #e0e7ff;
}

/* Ton bouton rouge personnalisé (déjà présent, on le garde) */
.modal-button--danger {
  background: #dc2626;
  color: #fff;
}

.modal-button--danger:hover {
  background: #b91c1c;
}

/* Sous-titre dans l’en-tête */
.modal-subtitle {
  font-weight: 400;
  font-size: 0.9rem;
  color: #4b5563;
  margin-left: 0.5rem;
}
</style>

