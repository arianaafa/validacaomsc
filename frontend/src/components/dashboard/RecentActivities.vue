<script setup lang="ts">
import { CheckCircle2 } from '@lucide/vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

export interface ActivityItem {
  id: string
  label: string
  detail?: string
  time?: string
}

defineProps<{
  title?: string
  items: ActivityItem[]
  loading?: boolean
}>()
</script>

<template>
  <DashboardCard class="dashboard-slide-up h-full">
    <div class="dashboard-panel-header">
      <h2 class="text-dashboard-subtitle font-semibold text-slate-900 dark:text-white">
        {{ title ?? 'Últimas atividades' }}
      </h2>
    </div>

    <div v-if="loading" class="space-y-3 p-6">
      <div
        v-for="index in 4"
        :key="index"
        class="h-14 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800"
      />
    </div>

    <ul v-else-if="items.length" class="divide-y divide-slate-100 dark:divide-slate-800">
      <li
        v-for="item in items"
        :key="item.id"
        class="flex items-start gap-3 px-6 py-4 transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-800/30"
      >
        <CheckCircle2
          class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500 dark:text-emerald-400"
          stroke-width="2"
        />
        <div class="min-w-0 flex-1">
          <p class="text-dashboard-body font-medium text-slate-800 dark:text-slate-200">
            {{ item.label }}
          </p>
          <p
            v-if="item.detail"
            class="mt-0.5 truncate text-dashboard-caption text-slate-500 dark:text-slate-400"
          >
            {{ item.detail }}
          </p>
        </div>
        <time
          v-if="item.time"
          class="shrink-0 text-dashboard-caption text-slate-400"
        >
          {{ item.time }}
        </time>
      </li>
    </ul>

    <p v-else class="px-6 py-12 text-center text-dashboard-body text-slate-500 dark:text-slate-400">
      Nenhuma atividade recente.
    </p>
  </DashboardCard>
</template>
