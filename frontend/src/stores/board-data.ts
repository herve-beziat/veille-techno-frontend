import { defineStore } from 'pinia'
import type { KanbanListData } from '@/types/kanban'

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
        cards: [...list.cards],
      }))
    },
    clearLists() {
      this.lists = []
    },
  },
})