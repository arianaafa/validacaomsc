import { computed, onMounted, onUnmounted, ref } from 'vue'

function formatRelativeTime(from: Date, now: Date): string {
  const diffMs = now.getTime() - from.getTime()
  const diffSec = Math.floor(diffMs / 1000)

  if (diffSec < 10) {
    return 'agora mesmo'
  }

  if (diffSec < 60) {
    return `há ${diffSec} segundo${diffSec === 1 ? '' : 's'}`
  }

  const diffMin = Math.floor(diffSec / 60)

  if (diffMin < 60) {
    return `há ${diffMin} minuto${diffMin === 1 ? '' : 's'}`
  }

  const diffHour = Math.floor(diffMin / 60)

  if (diffHour < 24) {
    return `há ${diffHour} hora${diffHour === 1 ? '' : 's'}`
  }

  const diffDay = Math.floor(diffHour / 24)
  return `há ${diffDay} dia${diffDay === 1 ? '' : 's'}`
}

export function useRelativeTime(timestamp: () => Date | null) {
  const now = ref(new Date())
  let intervalId: ReturnType<typeof setInterval> | null = null

  const relativeLabel = computed((): string => {
    const date = timestamp()

    if (!date || Number.isNaN(date.getTime())) {
      return '—'
    }

    return formatRelativeTime(date, now.value)
  })

  onMounted(() => {
    intervalId = setInterval(() => {
      now.value = new Date()
    }, 30_000)
  })

  onUnmounted(() => {
    if (intervalId) {
      clearInterval(intervalId)
    }
  })

  return {
    relativeLabel,
  }
}
