export interface KanbanCardData {
  id: number
  title: string
  description: string | null
  position: number
  createdAt?: string | null
  updatedAt?: string | null
}

export interface KanbanListData {
  id: number
  title: string
  position?: number | null
  cards: KanbanCardData[]
}