<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import BreadcrumbNav from '@/components/dashboard/BreadcrumbNav.vue'
import DashboardTopBar from '@/components/dashboard/DashboardTopBar.vue'
import SidebarNavItem from '@/components/dashboard/SidebarNavItem.vue'
import SidebarUserCard from '@/components/dashboard/SidebarUserCard.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const isSidebarCollapsed = ref(false)
const isMobileMenuOpen = ref(false)

const municipalityLabel = computed((): string | null => auth.user?.municipality?.name ?? null)

const sidebarWidthClass = computed(() =>
  isSidebarCollapsed.value ? 'w-[4.5rem]' : 'w-64',
)

const navLinkActiveClass =
  'nav-link-active !bg-slate-800 !text-aura-sky shadow-sm ring-1 ring-slate-700/50'

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

function updateSidebarForViewport(): void {
  const width = window.innerWidth

  if (width < 768) {
    isMobileMenuOpen.value = false
    return
  }

  isSidebarCollapsed.value = width < 1280
}

onMounted(() => {
  updateSidebarForViewport()
  window.addEventListener('resize', updateSidebarForViewport)
})

onUnmounted(() => {
  window.removeEventListener('resize', updateSidebarForViewport)
})
</script>

<template>
  <div class="min-h-screen w-full bg-dashboard">
    <div
      v-if="isMobileMenuOpen"
      class="fixed inset-0 z-20 bg-black/60 backdrop-blur-sm md:hidden"
      aria-hidden="true"
      @click="closeMobileMenu"
    />

    <div
      class="min-h-screen w-full transition-[grid-template-columns] duration-300 ease-in-out md:grid md:min-h-screen"
      :class="isSidebarCollapsed ? 'md:grid-cols-[4.5rem_minmax(0,1fr)]' : 'md:grid-cols-[16rem_minmax(0,1fr)]'"
    >
      <aside
        :class="[
          'z-30 flex h-screen shrink-0 flex-col border-r border-slate-800/80 bg-slate-950 text-slate-100 shadow-2xl',
          'max-md:fixed max-md:inset-y-0 max-md:left-0 md:sticky md:top-0',
          'transition-[width,transform] duration-300 ease-in-out',
          sidebarWidthClass,
          isMobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
        ]"
        aria-label="Menu principal"
      >
      <div
        :class="[
          'shrink-0 border-b border-slate-800/80',
          isSidebarCollapsed ? 'flex flex-col items-center px-2 py-5' : 'px-5 py-6 text-center',
        ]"
      >
        <RouterLink
          to="/"
          class="brand-link inline-flex flex-col items-center"
          @click="closeMobileMenu"
        >
          <AuraLogo
            v-if="isSidebarCollapsed"
            dark
            icon-only
            :icon-size="40"
          />
          <template v-else>
            <AuraLogo
              dark
              layout="vertical"
              :icon-size="48"
            />
            <p class="mt-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
              Aura Tech
            </p>
            <p class="mt-0.5 text-sm font-medium text-slate-300">
              Audita MSC
            </p>
            <p
              v-if="municipalityLabel"
              class="mt-1 max-w-full truncate text-xs text-slate-500"
              :title="municipalityLabel"
            >
              {{ municipalityLabel }}
            </p>
          </template>
        </RouterLink>
      </div>

      <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4" aria-label="Navegação">
        <div v-if="!isSidebarCollapsed" class="mb-2 px-3">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">
            Geral
          </p>
        </div>
        <ul class="space-y-0.5">
          <SidebarNavItem
            to="/"
            label="Dashboard"
            :collapsed="isSidebarCollapsed"
            exact
            :active-class="navLinkActiveClass"
            @navigate="closeMobileMenu"
          >
            <template #icon>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="9" rx="1" />
                <rect x="14" y="3" width="7" height="5" rx="1" />
                <rect x="14" y="12" width="7" height="9" rx="1" />
                <rect x="3" y="16" width="7" height="5" rx="1" />
              </svg>
            </template>
          </SidebarNavItem>
        </ul>

        <div
          class="my-4 border-t border-slate-800/80"
          :class="isSidebarCollapsed ? 'mx-1' : 'mx-3'"
        />

        <div v-if="!isSidebarCollapsed" class="mb-2 px-3">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">
            MSC
          </p>
        </div>
        <ul class="space-y-0.5">
          <SidebarNavItem
            to="/msc/import"
            label="Importar MSC"
            :collapsed="isSidebarCollapsed"
            :active-class="navLinkActiveClass"
            @navigate="closeMobileMenu"
          >
            <template #icon>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <path d="M12 18v-6" />
                <path d="M9 15l3-3 3 3" />
              </svg>
            </template>
          </SidebarNavItem>

          <SidebarNavItem
            to="/msc/rules"
            label="Regras de Validação"
            :collapsed="isSidebarCollapsed"
            :active-class="navLinkActiveClass"
            @navigate="closeMobileMenu"
          >
            <template #icon>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 11l3 3L22 4" />
                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
              </svg>
            </template>
          </SidebarNavItem>
        </ul>

        <div
          class="my-4 border-t border-slate-800/80"
          :class="isSidebarCollapsed ? 'mx-1' : 'mx-3'"
        />

        <div v-if="!isSidebarCollapsed" class="mb-2 px-3">
          <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-600">
            Conta
          </p>
        </div>
        <ul class="space-y-0.5">
          <SidebarNavItem
            to="/settings"
            label="Configurações"
            :collapsed="isSidebarCollapsed"
            :active-class="navLinkActiveClass"
            @navigate="closeMobileMenu"
          >
            <template #icon>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3" />
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
              </svg>
            </template>
          </SidebarNavItem>
        </ul>

        <button
          type="button"
          class="mt-4 hidden w-full items-center gap-2 rounded-lg py-2 text-xs font-medium text-slate-500 transition-all duration-200 hover:bg-slate-800/70 hover:text-slate-300 md:flex"
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

      <SidebarUserCard
        :name="auth.user?.name"
        :role="municipalityLabel ?? 'Usuário Municipal'"
        :collapsed="isSidebarCollapsed"
        profile-to="/settings"
        @logout="handleLogout"
      />
      </aside>

      <div class="flex min-h-screen min-w-0 flex-col">
        <DashboardTopBar
          show-mobile-menu-button
          @toggle-mobile-menu="toggleMobileMenu"
        />

        <div class="border-b border-slate-200/80 bg-white/50 px-4 py-3 dark:border-slate-800 dark:bg-slate-950/50 md:px-6">
          <BreadcrumbNav />
        </div>

        <main class="flex flex-1 flex-col bg-dashboard p-4 md:p-6 lg:p-8">
          <div class="mx-auto w-full min-w-0 max-w-[1400px] flex-1">
            <RouterView />
          </div>
        </main>
      </div>
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

.nav-link-active {
  color: var(--color-aura-sky);
}
</style>
