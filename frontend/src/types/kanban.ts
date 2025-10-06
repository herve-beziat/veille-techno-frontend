export interface KanbanCardData {
  id: number
  title: string
  description: string | null
  position: number
  listId?: number
  createdAt?: string | null
  updatedAt?: string | null
}

export interface KanbanListData {
  id: number
  title: string
  position?: number | null
  cards: KanbanCardData[]
}

export interface KanbanListPositionUpdate {
  id: number
  position: number
}

export interface KanbanCardPositionUpdate {
  id: number
  list_id: number
  position: number
}

export type KanbanBoardChange =
  | {
      type: 'reorder-lists'
      lists: KanbanListData[]
      order: KanbanListPositionUpdate[]
    }
  | {
      type: 'reorder-cards'
      lists: KanbanListData[]
      affectedLists: number[]
      updates: KanbanCardPositionUpdate[]
    }

export type CardAllResponse = {
  id: number
  title: string
  description: string | null
  position: number
  listId: number
  ownerId?: number | null
  createdAt?: string | null
  updatedAt?: string | null
}
