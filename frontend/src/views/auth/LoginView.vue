<script setup lang="ts">
import { reactive } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const form = reactive({
  email: '',
  password: '',
})

async function handleSubmit(): Promise<void> {
  const success = await auth.login({
    email: form.email,
    password: form.password,
  })

  if (!success) {
    return
  }

  const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/'
  await router.push(redirect)
}
</script>

<template>
  <div class="flex min-h-screen w-full flex-col items-center justify-center bg-slate-50 px-4 py-8 sm:py-12">
    <div class="flex w-full max-w-md flex-col items-center">
      <header class="mb-6 flex justify-center sm:mb-8" aria-label="Aura Tech">
        <AuraLogo layout="vertical" :icon-size="96" />
      </header>

      <section class="w-full rounded-xl bg-white p-6 shadow-md sm:p-8">
        <header class="mb-5 sm:mb-6">
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Entrar</h1>
          <p class="mt-1.5 text-sm text-slate-500 sm:mt-2 sm:text-base">
            Acesse sua conta para continuar.
          </p>
        </header>

        <form class="grid gap-4" @submit.prevent="handleSubmit">
          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            E-mail
            <input
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
              class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
            />
            <span v-if="auth.fieldErrors.email?.length" class="text-sm font-medium text-red-600">
              {{ auth.fieldErrors.email[0] }}
            </span>
          </label>

          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            Senha
            <input
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
            />
            <span v-if="auth.fieldErrors.password?.length" class="text-sm font-medium text-red-600">
              {{ auth.fieldErrors.password[0] }}
            </span>
          </label>

          <p v-if="auth.error" class="text-sm font-medium text-red-600">{{ auth.error }}</p>

          <button
            type="submit"
            :disabled="auth.loading"
            class="w-full rounded-lg bg-blue-600 px-4 py-3.5 text-base font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
          >
            {{ auth.loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </form>

        <p class="mt-5 text-center text-sm leading-relaxed text-slate-500">
        Não possui uma instância para o seu município?
        <RouterLink
          to="/solicitar-demonstracao"
          class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
        >
          Solicitar Demonstração / Contato Comercial
          </RouterLink>
        </p>
      </section>
    </div>
  </div>
</template>
