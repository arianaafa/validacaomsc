<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps<{
  name?: string | null
  role?: string
  collapsed?: boolean
  profileTo?: string
}>()

const emit = defineEmits<{
  logout: []
}>()

const userInitials = computed((): string => {
  const trimmed = props.name?.trim()

  if (!trimmed) {
    return 'U'
  }

  const parts = trimmed.split(/\s+/).filter(Boolean)

  if (parts.length >= 2) {
    return `${parts[0]![0]}${parts[parts.length - 1]![0]}`.toUpperCase()
  }

  return trimmed.slice(0, 2).toUpperCase()
})
</script>

<template>
  <div
    class="shrink-0 border-t border-slate-800/80 p-3"
    :class="collapsed ? 'flex flex-col items-center gap-2' : ''"
  >
    <div
      v-if="!collapsed"
      class="mb-3 rounded-xl border border-slate-800/80 bg-slate-900/60 p-3"
    >
      <div class="flex items-center gap-3">
        <div
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-aura-primary to-aura-sky text-sm font-bold text-white shadow-lg shadow-aura-primary/20"
        >
          {{ userInitials }}
        </div>
        <div class="min-w-0 flex-1">
          <p
            class="truncate text-sm font-semibold text-slate-100"
            :title="name ?? undefined"
          >
            {{ name ?? 'Usuário' }}
          </p>
          <p class="truncate text-xs text-slate-500">
            {{ role ?? 'Membro' }}
          </p>
        </div>
      </div>

      <div class="mt-3 flex gap-2">
        <RouterLink
          v-if="profileTo"
          :to="profileTo"
          class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-700/80 bg-slate-800/50 px-2 py-1.5 text-xs font-medium text-slate-300 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white"
        >
          <svg
            class="h-3.5 w-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            aria-hidden="true"
          >
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
            <circle cx="12" cy="7" r="4" />
          </svg>
          Perfil
        </RouterLink>
        <button
          type="button"
          class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-700/80 bg-slate-800/50 px-2 py-1.5 text-xs font-medium text-slate-400 transition hover:border-red-900/50 hover:bg-red-950/30 hover:text-red-400"
          @click="emit('logout')"
        >
          <svg
            class="h-3.5 w-3.5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            aria-hidden="true"
          >
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          Sair
        </button>
      </div>
    </div>

    <template v-else>
      <div
        class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-aura-primary to-aura-sky text-xs font-bold text-white"
        :title="name ?? 'Usuário'"
      >
        {{ userInitials }}
      </div>
      <button
        type="button"
        class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 transition hover:bg-slate-800 hover:text-red-400"
        title="Sair"
        aria-label="Sair"
        @click="emit('logout')"
      >
        <svg
          class="h-4 w-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.75"
          aria-hidden="true"
        >
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" y1="12" x2="9" y2="12" />
        </svg>
      </button>
    </template>
  </div>
</template>
