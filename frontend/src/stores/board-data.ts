import { defineStore } from 'pinia'
import type { KanbanListData, KanbanCardData } from '@/types/kanban'

function cloneCard(card: KanbanCardData, listId: number): KanbanCardData {
  return {
    ...card,
    listId: card.listId ?? listId,
  }
}

export const useBoardDataStore = defineStore('boardData', {
  state: () => ({
    lists: [] as KanbanListData[],
  }),
  getters: {
    hasLists: (state) => state.lists.length > 0,
  },
  actions: {
    setLists(lists: KanbanListData[]) {
      this.lists = lists.map((list) => ({
        ...list,
        cards: list.cards.map((card) => cloneCard(card, list.id)),
      }))
    },
    clearLists() {
      this.lists = []
    },
  },
})