<script setup lang="ts">
import { computed } from 'vue'
import { Clock, RefreshCw } from '@lucide/vue'
import BreadcrumbNav, { type BreadcrumbItem } from '@/components/dashboard/BreadcrumbNav.vue'
import { useGreeting } from '@/composables/useGreeting'

const props = defineProps<{
  userName?: string | null
  welcomeMessage?: string
  lastAccess?: string
  lastSync?: string
  breadcrumbs?: BreadcrumbItem[]
}>()

const { greetingWithName } = useGreeting(computed(() => props.userName))

const welcomeText = computed((): string => {
  if (props.welcomeMessage) {
    return props.welcomeMessage
  }

  return 'Bem-vinda ao Painel Administrativo da Aura Tech.'
})
</script>

<template>
  <header class="dashboard-fade-in space-y-5">
    <BreadcrumbNav :items="breadcrumbs" />

    <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
      <div class="max-w-3xl space-y-2">
        <p class="text-dashboard-subtitle font-semibold text-slate-800 dark:text-slate-100">
          {{ greetingWithName }}
        </p>
        <h1 class="text-dashboard-title">
          {{ welcomeText }}
        </h1>
      </div>

      <div class="flex shrink-0 flex-col gap-2 sm:flex-row lg:flex-col xl:flex-row">
        <div
          v-if="lastAccess"
          class="dashboard-card flex items-center gap-3 px-4 py-3 transition-all duration-150"
        >
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
            <Clock class="h-4 w-4 text-slate-500 dark:text-slate-400" stroke-width="1.75" />
          </div>
          <div>
            <p class="text-dashboard-caption font-medium text-slate-500 dark:text-slate-400">
              Último acesso
            </p>
            <p class="text-dashboard-body font-semibold text-slate-800 dark:text-slate-100">
              {{ lastAccess }}
            </p>
          </div>
        </div>

        <div
          v-if="lastSync"
          class="dashboard-card flex items-center gap-3 px-4 py-3 transition-all duration-150"
        >
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-950/40">
            <RefreshCw class="h-4 w-4 text-emerald-600 dark:text-emerald-400" stroke-width="1.75" />
          </div>
          <div>
            <p class="text-dashboard-caption font-medium text-slate-500 dark:text-slate-400">
              Última sincronização
            </p>
            <p class="text-dashboard-body font-semibold text-slate-800 dark:text-slate-100">
              {{ lastSync }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>
