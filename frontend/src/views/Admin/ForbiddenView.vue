<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="mx-auto flex max-w-lg flex-col items-center py-16 text-center">
    <div
      class="mb-4 inline-flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600"
      aria-hidden="true"
    >
      <svg
        class="h-7 w-7"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
    </div>

    <h2 class="text-2xl font-semibold text-slate-900">Acesso negado</h2>
    <p class="mt-2 text-sm text-slate-500">
      Você não tem permissão para acessar o painel administrativo global.
    </p>

    <RouterLink
      to="/"
      class="mt-6 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
    >
      Voltar ao dashboard
    </RouterLink>
  </section>
</template>
