import { defineStore } from 'pinia'

export const useBoardUiStore = defineStore('boardUi', {
  state: () => ({
    createListModalOpen: false,
    createCardModalForListId: null as number | null,
  }),
  actions: {
    openCreateListModal() {
      this.createListModalOpen = true
    },
    closeCreateListModal() {
      this.createListModalOpen = false
    },
    openCreateCardModal(listId: number) {
      this.createCardModalForListId = listId
    },
    closeCreateCardModal() {
      this.createCardModalForListId = null
    },
  },
})