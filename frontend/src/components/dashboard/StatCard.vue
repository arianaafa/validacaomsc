<script setup lang="ts">
import type { Component } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight } from '@lucide/vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

export type StatAccent = 'blue' | 'green' | 'orange' | 'red'

const accentClasses: Record<StatAccent, string> = {
  blue: 'stat-accent-blue',
  green: 'stat-accent-green',
  orange: 'stat-accent-orange',
  red: 'stat-accent-red',
}

const accentValueClasses: Record<StatAccent, string> = {
  blue: 'text-blue-600 dark:text-blue-400',
  green: 'text-emerald-600 dark:text-emerald-400',
  orange: 'text-orange-600 dark:text-orange-400',
  red: 'text-red-600 dark:text-red-400',
}

const accentTrendClasses: Record<StatAccent, string> = {
  blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
  green: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
  orange: 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300',
  red: 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300',
}

defineProps<{
  icon: Component
  title: string
  value: string | number
  description?: string
  trend?: string
  trendUp?: boolean
  actionLabel?: string
  actionTo?: string
  accent?: StatAccent
  loading?: boolean
}>()
</script>

<template>
  <DashboardCard hover padding class="dashboard-slide-up flex min-h-[180px] flex-col">
    <div v-if="loading" class="flex flex-1 flex-col gap-4">
      <div class="h-14 w-14 animate-pulse rounded-2xl bg-slate-100 dark:bg-slate-800" />
      <div class="h-4 w-24 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
      <div class="h-10 w-16 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
      <div class="mt-auto h-4 w-32 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
    </div>

    <template v-else>
      <div class="flex items-start justify-between gap-4">
        <div
          class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl transition-transform duration-150 group-hover:scale-105"
          :class="accentClasses[accent ?? 'blue']"
        >
          <component :is="icon" class="h-7 w-7" stroke-width="1.75" aria-hidden="true" />
        </div>

        <span
          v-if="trend"
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-dashboard-caption font-semibold"
          :class="accentTrendClasses[accent ?? 'blue']"
        >
          {{ trend }}
        </span>
      </div>

      <p class="mt-5 text-dashboard-caption font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
        {{ title }}
      </p>

      <p
        class="mt-1 text-[36px] font-bold tabular-nums leading-none tracking-tight"
        :class="accentValueClasses[accent ?? 'blue']"
      >
        {{ value }}
      </p>

      <p
        v-if="description"
        class="mt-2 text-dashboard-caption text-slate-500 dark:text-slate-400"
      >
        {{ description }}
      </p>

      <div class="mt-auto pt-5">
        <RouterLink
          v-if="actionTo && actionLabel"
          :to="actionTo"
          class="group/action inline-flex items-center gap-1.5 text-dashboard-caption font-semibold text-slate-700 transition-colors duration-150 hover:text-aura-primary dark:text-slate-300 dark:hover:text-aura-sky"
        >
          {{ actionLabel }}
          <ArrowRight
            class="h-3.5 w-3.5 transition-transform duration-150 group-hover/action:translate-x-0.5"
            stroke-width="2"
          />
        </RouterLink>
      </div>
    </template>
  </DashboardCard>
</template>

<style scoped>
a {
  text-decoration: none;
}

a:hover {
  background-color: transparent;
}
</style>
