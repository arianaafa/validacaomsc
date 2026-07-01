<script setup lang="ts">
import { computed } from 'vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

export interface ChartPoint {
  label: string
  value: number
}

const props = defineProps<{
  title: string
  subtitle?: string
  data: ChartPoint[]
  loading?: boolean
  accent?: 'blue' | 'green' | 'orange'
  formatValue?: (value: number) => string
}>()

const accentBarClasses = {
  blue: 'from-blue-500 to-blue-400 dark:from-blue-600 dark:to-blue-400',
  green: 'from-emerald-500 to-emerald-400 dark:from-emerald-600 dark:to-emerald-400',
  orange: 'from-orange-500 to-orange-400 dark:from-orange-600 dark:to-orange-400',
}

const maxValue = computed((): number => {
  const max = props.data.reduce((current, point) => Math.max(current, point.value), 0)
  return max > 0 ? max : 1
})

function barHeight(value: number): string {
  return `${Math.max(10, (value / maxValue.value) * 100)}%`
}

function displayValue(value: number): string {
  return props.formatValue ? props.formatValue(value) : String(value)
}
</script>

<template>
  <DashboardCard class="dashboard-slide-up h-full">
    <div class="dashboard-panel-header">
      <h2 class="text-dashboard-subtitle font-semibold text-slate-900 dark:text-white">
        {{ title }}
      </h2>
      <p v-if="subtitle" class="mt-1 text-dashboard-caption text-slate-500 dark:text-slate-400">
        {{ subtitle }}
      </p>
    </div>

    <div v-if="loading" class="p-6">
      <div class="h-48 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
    </div>

    <div v-else class="overflow-x-auto p-6 pt-4">
      <div class="flex min-w-[320px] items-end gap-3" style="height: 180px">
        <div
          v-for="point in data"
          :key="point.label"
          class="group flex flex-1 flex-col items-center gap-2"
        >
          <span class="text-dashboard-caption font-semibold tabular-nums text-slate-600 dark:text-slate-400">
            {{ displayValue(point.value) }}
          </span>
          <div class="flex h-32 w-full items-end justify-center">
            <div
              class="w-full max-w-12 rounded-t-xl bg-gradient-to-t transition-all duration-200 group-hover:scale-y-105"
              :class="accentBarClasses[accent ?? 'blue']"
              :style="{ height: barHeight(point.value) }"
              :title="`${point.label}: ${displayValue(point.value)}`"
            />
          </div>
          <span class="text-center text-dashboard-caption font-medium text-slate-500 dark:text-slate-400">
            {{ point.label }}
          </span>
        </div>
      </div>
    </div>
  </DashboardCard>
</template>
