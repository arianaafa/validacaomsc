<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const isSidebarCollapsed = ref(false)
const isMobileMenuOpen = ref(false)

const pageTitle = computed(() => {
  const title = route.meta.title
  return typeof title === 'string' ? title : 'validaMSC'
})

const sidebarWidthClass = computed(() =>
  isSidebarCollapsed.value ? 'w-16' : 'w-64',
)

const mainOffsetClass = computed(() =>
  isSidebarCollapsed.value ? 'md:ml-16' : 'md:ml-64',
)

const navLinkBaseClass =
  'nav-link flex items-center gap-3 rounded-lg py-2.5 text-sm font-medium text-zinc-400 transition-colors border-l-2 border-transparent hover:bg-zinc-800/70 hover:text-zinc-100'

const navLinkActiveClass =
  'nav-link-active !bg-indigo-500/10 !text-indigo-100 !border-l-indigo-500'

async function handleLogout(): Promise<void> {
  await auth.logout()
  await router.replace({ name: 'login' })
}

function toggleMobileMenu(): void {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

function closeMobileMenu(): void {
  isMobileMenuOpen.value = false
}

function toggleSidebarCollapse(): void {
  isSidebarCollapsed.value = !isSidebarCollapsed.value
}
</script>

<template>
  <div class="flex min-h-screen w-full">
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-20 bg-black/50 md:hidden"
      aria-hidden="true"
      @click="closeMobileMenu"
    />

    <aside
      :class="[
        'fixed inset-y-0 left-0 z-30 flex flex-col bg-zinc-950 text-zinc-100 shadow-xl transition-all duration-300',
        sidebarWidthClass,
        isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
      ]"
      aria-label="Menu principal"
    >
      <div
        :class="[
          'shrink-0 border-b border-zinc-800/80',
          isSidebarCollapsed ? 'flex justify-center px-2 py-4' : 'px-5 py-5',
        ]"
      >
        <RouterLink
          to="/"
          class="brand-link flex items-center font-bold tracking-tight text-white"
          :class="isSidebarCollapsed ? 'justify-center' : ''"
          @click="closeMobileMenu"
        >
          <AuraLogo
            v-if="isSidebarCollapsed"
            dark
            icon-only
            :icon-size="36"
          />
          <AuraLogo
            v-else
            dark
            layout="vertical"
            :icon-size="36"
          />
        </RouterLink>
      </div>

      <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Navegação">
        <ul class="space-y-1">
          <li>
            <RouterLink
              to="/"
              :class="[navLinkBaseClass, isSidebarCollapsed ? 'justify-center px-2' : 'px-3']"
              active-class=""
              :exact-active-class="navLinkActiveClass"
              @click="closeMobileMenu"
            >
              <svg
                class="h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <rect x="3" y="3" width="7" height="9" rx="1" />
                <rect x="14" y="3" width="7" height="5" rx="1" />
                <rect x="14" y="12" width="7" height="9" rx="1" />
                <rect x="3" y="16" width="7" height="5" rx="1" />
              </svg>
              <span v-if="!isSidebarCollapsed">Dashboard</span>
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/msc/import"
              :class="[navLinkBaseClass, isSidebarCollapsed ? 'justify-center px-2' : 'px-3']"
              :active-class="navLinkActiveClass"
              @click="closeMobileMenu"
            >
              <svg
                class="h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <path d="M12 18v-6" />
                <path d="M9 15l3-3 3 3" />
              </svg>
              <span v-if="!isSidebarCollapsed">Importar MSC</span>
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/msc/rules"
              :class="[navLinkBaseClass, isSidebarCollapsed ? 'justify-center px-2' : 'px-3']"
              :active-class="navLinkActiveClass"
              @click="closeMobileMenu"
            >
              <svg
                class="h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="M9 11l3 3L22 4" />
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
              </svg>
              <span v-if="!isSidebarCollapsed">Regras de Validação</span>
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/settings"
              :class="[navLinkBaseClass, isSidebarCollapsed ? 'justify-center px-2' : 'px-3']"
              :active-class="navLinkActiveClass"
              @click="closeMobileMenu"
            >
              <svg
                class="h-5 w-5 shrink-0"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <circle cx="12" cy="12" r="3" />
                <path
                  d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"
                />
              </svg>
              <span v-if="!isSidebarCollapsed">Configurações</span>
            </RouterLink>
          </li>
        </ul>

        <button
          type="button"
          class="mt-4 hidden w-full items-center gap-2 rounded-lg py-2 text-sm font-medium text-zinc-500 transition-colors hover:bg-zinc-800/70 hover:text-zinc-300 md:flex"
          :class="isSidebarCollapsed ? 'justify-center px-2' : 'px-3'"
          :aria-label="isSidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
          :title="isSidebarCollapsed ? 'Expandir menu' : 'Recolher menu'"
          @click="toggleSidebarCollapse"
        >
          <svg
            class="h-4 w-4 shrink-0 transition-transform duration-300"
            :class="isSidebarCollapsed ? 'rotate-180' : ''"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <rect x="3" y="3" width="18" height="18" rx="2" />
            <path d="M9 3v18" />
            <path d="M14 9l3 3-3 3" />
          </svg>
          <span v-if="!isSidebarCollapsed">Recolher menu</span>
        </button>
      </nav>

      <div class="shrink-0 border-t border-zinc-800/80 p-3">
        <div
          v-if="!isSidebarCollapsed"
          class="mb-2 truncate px-2 text-sm font-medium text-zinc-300"
          :title="auth.user?.name"
        >
          {{ auth.user?.name ?? 'Usuário' }}
        </div>
        <button
          type="button"
          class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-zinc-500 transition-colors hover:bg-zinc-800/70 hover:text-red-400"
          :class="isSidebarCollapsed ? 'justify-center' : ''"
          @click="handleLogout"
        >
          <svg
            class="h-4 w-4 shrink-0"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.75"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
            <polyline points="16 17 21 12 16 7" />
            <line x1="21" y1="12" x2="9" y2="12" />
          </svg>
          <span v-if="!isSidebarCollapsed">Sair</span>
        </button>
      </div>
    </aside>

    <div
      :class="[
        'flex min-h-screen flex-1 flex-col transition-all duration-300',
        mainOffsetClass,
      ]"
    >
      <header
        class="sticky top-0 z-10 flex h-16 shrink-0 items-center gap-4 border-b border-slate-200 bg-white px-4 shadow-sm md:px-6"
      >
        <button
          type="button"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-600 transition-colors hover:bg-slate-100 md:hidden"
          aria-label="Abrir menu"
          @click="toggleMobileMenu"
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

        <h1 class="text-lg font-semibold text-slate-800">
          {{ pageTitle }}
        </h1>
      </header>

      <main class="flex-1 bg-slate-50 p-4 md:p-8 flex justify-center items-start">
        <!-- Contêiner wrapper para limitar a largura máxima do formulário e centralizar -->
        <div class="w-full max-w-7xl">
          <RouterView />
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
.brand-link {
  color: inherit;
  text-decoration: none;
}

.brand-link:hover {
  background-color: transparent;
  color: inherit;
}

.nav-link {
  color: inherit;
  text-decoration: none;
}

.nav-link:hover {
  color: rgb(244 244 245);
}

.nav-link-active {
  color: rgb(224 231 255);
}
</style>
