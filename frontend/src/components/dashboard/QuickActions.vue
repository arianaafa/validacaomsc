<script setup lang="ts">
import type { Component } from 'vue'
import { RouterLink } from 'vue-router'
import { ArrowRight } from '@lucide/vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'

export interface QuickActionItem {
  title: string
  description: string
  to: string
  icon?: Component
}

defineProps<{
  items: QuickActionItem[]
}>()
</script>

<template>
  <div class="grid gap-4 md:grid-cols-3">
    <RouterLink
      v-for="item in items"
      :key="item.to"
      :to="item.to"
      class="group block"
    >
      <DashboardCard hover padding class="dashboard-slide-up h-full">
        <div v-if="item.icon" class="mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800">
          <component :is="item.icon" class="h-5 w-5 text-slate-600 dark:text-slate-400" stroke-width="1.75" />
        </div>
        <h3 class="text-dashboard-body font-semibold text-slate-900 transition-colors duration-150 group-hover:text-aura-primary dark:text-white dark:group-hover:text-aura-sky">
          {{ item.title }}
        </h3>
        <p class="mt-1.5 text-dashboard-caption text-slate-500 dark:text-slate-400">
          {{ item.description }}
        </p>
        <span class="mt-4 inline-flex items-center gap-1 text-dashboard-caption font-semibold text-slate-600 transition-all duration-150 group-hover:gap-2 group-hover:text-aura-primary dark:text-slate-400 dark:group-hover:text-aura-sky">
          Acessar
          <ArrowRight class="h-3.5 w-3.5" stroke-width="2" />
        </span>
      </DashboardCard>
    </RouterLink>
  </div>
</template>

<style scoped>
a {
  text-decoration: none;
}

a:hover {
  background-color: transparent;
}
</style>
