const STORAGE_KEY = 'validamsc.last_access'

export function recordLastAccess(): void {
  localStorage.setItem(STORAGE_KEY, new Date().toISOString())
}

export function getLastAccessDate(): Date | null {
  const raw = localStorage.getItem(STORAGE_KEY)

  if (!raw) {
    return null
  }

  const date = new Date(raw)
  return Number.isNaN(date.getTime()) ? null : date
}

export function formatLastAccess(date: Date | null): string {
  if (!date) {
    return 'Primeiro acesso'
  }

  const now = new Date()
  const isToday = date.toDateString() === now.toDateString()

  if (isToday) {
    return `Hoje às ${date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })}`
  }

  return date.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}
