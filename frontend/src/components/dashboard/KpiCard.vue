<script setup lang="ts">
defineProps<{
  label: string
  value: string | number
  hint?: string
  trend?: string
  trendUp?: boolean
  loading?: boolean
  accent?: 'default' | 'primary' | 'success' | 'warning' | 'danger'
}>()
</script>

<template>
  <article
    class="group relative overflow-hidden rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm transition-all duration-200 hover:border-slate-300/80 hover:shadow-md dark:border-slate-800 dark:bg-slate-900/60 dark:hover:border-slate-700"
  >
    <div
      class="pointer-events-none absolute -right-6 -top-6 h-24 w-24 rounded-full opacity-[0.07] transition-transform duration-300 group-hover:scale-110"
      :class="{
        'bg-slate-900 dark:bg-white': !accent || accent === 'default',
        'bg-aura-primary': accent === 'primary',
        'bg-emerald-500': accent === 'success',
        'bg-amber-500': accent === 'warning',
        'bg-red-500': accent === 'danger',
      }"
    />

    <div v-if="loading" class="space-y-3">
      <div class="h-4 w-24 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
      <div class="h-9 w-16 animate-pulse rounded bg-slate-200 dark:bg-slate-700" />
      <div class="h-3 w-32 animate-pulse rounded bg-slate-100 dark:bg-slate-800" />
    </div>

    <template v-else>
      <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
        {{ label }}
      </p>
      <p
        class="mt-2 text-3xl font-semibold tabular-nums tracking-tight"
        :class="{
          'text-slate-900 dark:text-white': !accent || accent === 'default',
          'text-aura-primary dark:text-aura-sky': accent === 'primary',
          'text-emerald-600 dark:text-emerald-400': accent === 'success',
          'text-amber-600 dark:text-amber-400': accent === 'warning',
          'text-red-600 dark:text-red-400': accent === 'danger',
        }"
      >
        {{ value }}
      </p>
      <div v-if="hint || trend" class="mt-2 flex flex-wrap items-center gap-2">
        <span
          v-if="trend"
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
          :class="trendUp
            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400'
            : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'"
        >
          <svg
            v-if="trendUp"
            class="h-3 w-3"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            aria-hidden="true"
          >
            <path d="M7 17l5-5 5 5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
          {{ trend }}
        </span>
        <span v-if="hint" class="text-xs text-slate-500 dark:text-slate-400">
          {{ hint }}
        </span>
      </div>
    </template>
  </article>
</template>
