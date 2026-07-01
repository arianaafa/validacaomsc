<script setup lang="ts">
import BreadcrumbNav, { type BreadcrumbItem } from '@/components/dashboard/BreadcrumbNav.vue'

defineProps<{
  title: string
  subtitle?: string
  greeting?: string
  lastUpdated?: string
  breadcrumbs?: BreadcrumbItem[]
}>()
</script>

<template>
  <div class="space-y-4">
    <BreadcrumbNav :items="breadcrumbs" />

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="space-y-1">
        <p
          v-if="greeting"
          class="text-lg font-semibold text-slate-900 dark:text-white"
        >
          {{ greeting }}
        </p>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
          {{ title }}
        </h1>
        <p
          v-if="subtitle"
          class="max-w-2xl text-sm text-slate-500 dark:text-slate-400"
        >
          {{ subtitle }}
        </p>
      </div>

      <div
        v-if="lastUpdated"
        class="flex shrink-0 items-center gap-2 rounded-lg border border-slate-200/80 bg-white px-3 py-2 text-xs text-slate-500 shadow-sm dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-400"
      >
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-40" />
          <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500" />
        </span>
        <span>
          Última atualização:
          <span class="font-medium text-slate-700 dark:text-slate-300">{{ lastUpdated }}</span>
        </span>
      </div>
    </div>
  </div>
</template>
