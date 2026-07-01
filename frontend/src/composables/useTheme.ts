import { computed, onMounted, ref, watch } from 'vue'

const STORAGE_KEY = 'validamsc.theme'

type Theme = 'light' | 'dark'

function getStoredTheme(): Theme {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored === 'light' || stored === 'dark') {
    return stored
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function applyTheme(theme: Theme): void {
  document.documentElement.classList.toggle('dark', theme === 'dark')
  document.documentElement.dataset.theme = theme
}

const theme = ref<Theme>('light')

export function useTheme() {
  const isDark = computed(() => theme.value === 'dark')

  function setTheme(value: Theme): void {
    theme.value = value
    localStorage.setItem(STORAGE_KEY, value)
    applyTheme(value)
  }

  function toggleTheme(): void {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  onMounted(() => {
    theme.value = getStoredTheme()
    applyTheme(theme.value)
  })

  watch(theme, (value) => {
    applyTheme(value)
  })

  return {
    theme,
    isDark,
    setTheme,
    toggleTheme,
  }
}
