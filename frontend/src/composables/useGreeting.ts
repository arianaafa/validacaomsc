import { computed, type MaybeRefOrGetter, toValue } from 'vue'

export function useGreeting(name?: MaybeRefOrGetter<string | null | undefined>) {
  const greeting = computed((): string => {
    const hour = new Date().getHours()

    if (hour >= 5 && hour < 12) {
      return 'Bom dia'
    }

    if (hour >= 12 && hour < 18) {
      return 'Boa tarde'
    }

    return 'Boa noite'
  })

  const greetingWithName = computed((): string => {
    const base = greeting.value
    const trimmed = toValue(name)?.trim()

    if (!trimmed) {
      return `${base} 👋`
    }

    const firstName = trimmed.split(/\s+/)[0]
    return `${base}, ${firstName} 👋`
  })

  return {
    greeting,
    greetingWithName,
  }
}
