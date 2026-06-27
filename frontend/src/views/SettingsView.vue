<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <section class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="mb-1 text-xl font-semibold text-slate-900">Conta</h2>
      <p class="mb-6 text-sm text-slate-500">
        Ajustes da sua conta no validaMSC.
      </p>

      <dl v-if="auth.user" class="grid gap-4">
        <div class="flex flex-col gap-1 border-b border-slate-100 pb-4 sm:flex-row sm:justify-between">
          <dt class="text-sm text-slate-500">Nome</dt>
          <dd class="text-sm font-medium text-slate-900">{{ auth.user.name }}</dd>
        </div>
        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between">
          <dt class="text-sm text-slate-500">E-mail</dt>
          <dd class="text-sm font-medium text-slate-900">{{ auth.user.email }}</dd>
        </div>
      </dl>
    </div>
  </section>
</template>
