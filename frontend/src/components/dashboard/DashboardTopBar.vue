<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useTheme } from '@/composables/useTheme'

defineProps<{
  showMobileMenuButton?: boolean
}>()

const emit = defineEmits<{
  toggleMobileMenu: []
}>()

const auth = useAuthStore()
const { isDark, toggleTheme } = useTheme()

const searchQuery = ref('')

const userInitials = computed((): string => {
  const name = auth.user?.name?.trim()

  if (!name) {
    return 'U'
  }

  const parts = name.split(/\s+/).filter(Boolean)

  if (parts.length >= 2) {
    return `${parts[0]![0]}${parts[parts.length - 1]![0]}`.toUpperCase()
  }

  return name.slice(0, 2).toUpperCase()
})

const userRole = computed((): string =>
  auth.isSuperAdmin ? 'Administrador Global' : 'Usuário Municipal',
)
</script>

<template>
  <header
    class="sticky top-0 z-10 flex h-14 shrink-0 items-center gap-3 border-b border-slate-200/80 bg-white/80 px-4 backdrop-blur-md dark:border-slate-800 dark:bg-slate-950/80 md:gap-4 md:px-6"
  >
    <button
      v-if="showMobileMenuButton"
      type="button"
      class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800 md:hidden"
      aria-label="Abrir menu"
      @click="emit('toggleMobileMenu')"
    >
      <svg
        class="h-5 w-5"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        aria-hidden="true"
      >
        <line x1="3" y1="6" x2="21" y2="6" />
        <line x1="3" y1="12" x2="21" y2="12" />
        <line x1="3" y1="18" x2="21" y2="18" />
      </svg>
    </button>

    <div class="relative hidden min-w-0 flex-1 sm:block sm:max-w-md">
      <svg
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
      >
        <circle cx="11" cy="11" r="8" />
        <path d="M21 21l-4.35-4.35" stroke-linecap="round" />
      </svg>
      <input
        v-model="searchQuery"
        type="search"
        placeholder="Pesquisar..."
        class="h-9 w-full rounded-lg border border-slate-200/80 bg-slate-50/80 pl-9 pr-3 text-sm text-slate-800 placeholder:text-slate-400 transition focus:border-aura-primary/40 focus:bg-white focus:outline-none focus:ring-2 focus:ring-aura-primary/20 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-aura-sky/40 dark:focus:bg-slate-900 dark:focus:ring-aura-sky/20"
      />
    </div>

    <div class="ml-auto flex items-center gap-1 sm:gap-1.5">
      <button
        type="button"
        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        aria-label="Notificações"
      >
        <svg
          class="h-[18px] w-[18px]"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" />
          <path d="M13.73 21a2 2 0 0 1-3.46 0" />
        </svg>
        <span class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-aura-sky ring-2 ring-white dark:ring-slate-950" />
      </button>

      <button
        type="button"
        class="hidden h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200 sm:inline-flex"
        aria-label="Ajuda"
      >
        <svg
          class="h-[18px] w-[18px]"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <circle cx="12" cy="12" r="10" />
          <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" />
          <line x1="12" y1="17" x2="12.01" y2="17" />
        </svg>
      </button>

      <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        :aria-label="isDark ? 'Ativar tema claro' : 'Ativar tema escuro'"
        @click="toggleTheme"
      >
        <svg
          v-if="isDark"
          class="h-[18px] w-[18px]"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <circle cx="12" cy="12" r="5" />
          <line x1="12" y1="1" x2="12" y2="3" />
          <line x1="12" y1="21" x2="12" y2="23" />
          <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" />
          <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
          <line x1="1" y1="12" x2="3" y2="12" />
          <line x1="21" y1="12" x2="23" y2="12" />
          <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" />
          <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
        </svg>
        <svg
          v-else
          class="h-[18px] w-[18px]"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
        </svg>
      </button>

      <div class="ml-1 hidden items-center gap-2.5 rounded-lg border border-slate-200/80 bg-slate-50/50 py-1 pl-1 pr-3 dark:border-slate-700 dark:bg-slate-900/40 sm:flex">
        <div
          class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-aura-primary to-aura-sky text-[10px] font-bold text-white"
          :title="auth.user?.name ?? 'Usuário'"
        >
          {{ userInitials }}
        </div>
        <div class="min-w-0">
          <p class="truncate text-xs font-semibold text-slate-800 dark:text-slate-100">
            {{ auth.user?.name ?? 'Usuário' }}
          </p>
          <p class="truncate text-[10px] text-slate-500 dark:text-slate-400">
            {{ userRole }}
          </p>
        </div>
      </div>
    </div>
  </header>
</template>
