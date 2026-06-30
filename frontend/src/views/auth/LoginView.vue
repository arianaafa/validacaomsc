<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const showPassword = ref(false)

const form = reactive({
  email: '',
  password: '',
})

const inputClass =
  'w-full rounded-lg border px-3.5 py-3 text-base font-normal text-slate-900 outline-none transition-colors focus:ring-2'

function inputStateClass(hasError: boolean): string {
  if (hasError) {
    return `${inputClass} border-red-500 focus:border-red-500 focus:ring-red-200`
  }

  return `${inputClass} border-slate-300 focus:border-aura-blue focus:ring-aura-blue/20`
}

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
  <div
    class="flex min-h-screen w-full flex-col items-center justify-center bg-slate-50 px-4 py-[clamp(2rem,6vh,5rem)]"
  >
    <div class="flex w-full max-w-md flex-col items-center gap-6 sm:gap-8 lg:gap-10">
      <header class="flex justify-center" aria-label="Aura Tech">
        <AuraLogo layout="vertical" :icon-size="96" />
      </header>

      <section class="w-full rounded-xl bg-white p-6 pb-8 shadow-md sm:p-8 sm:pb-10">
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
              :class="inputStateClass(Boolean(auth.fieldErrors.email?.length))"
              :aria-invalid="Boolean(auth.fieldErrors.email?.length)"
              aria-describedby="login-email-error"
            />
            <span
              v-if="auth.fieldErrors.email?.length"
              id="login-email-error"
              class="text-sm font-medium text-red-600"
              role="alert"
            >
              {{ auth.fieldErrors.email[0] }}
            </span>
          </label>

          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            Senha
            <div class="relative">
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                :class="[inputStateClass(Boolean(auth.fieldErrors.password?.length)), 'pr-11']"
                :aria-invalid="Boolean(auth.fieldErrors.password?.length)"
                aria-describedby="login-password-error"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600"
                :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="showPassword"
                @click="showPassword = !showPassword"
              >
                <svg
                  v-if="showPassword"
                  class="h-5 w-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.75"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                  />
                </svg>
                <svg
                  v-else
                  class="h-5 w-5"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.75"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                  />
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                  />
                </svg>
              </button>
            </div>
            <span
              v-if="auth.fieldErrors.password?.length"
              id="login-password-error"
              class="text-sm font-medium text-red-600"
              role="alert"
            >
              {{ auth.fieldErrors.password[0] }}
            </span>
          </label>

          <p v-if="auth.error" class="text-sm font-medium text-red-600" role="alert">{{ auth.error }}</p>

          <button
            type="submit"
            :disabled="auth.loading"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-aura-navy px-4 py-3.5 text-base font-semibold text-white transition hover:bg-aura-navy-dark disabled:cursor-not-allowed disabled:opacity-70"
          >
            <svg
              v-if="auth.loading"
              class="h-5 w-5 animate-spin"
              viewBox="0 0 24 24"
              fill="none"
              aria-hidden="true"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              />
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            {{ auth.loading ? 'Carregando...' : 'Entrar' }}
          </button>
        </form>

        <div class="mt-8 border-t border-slate-100 pt-6 text-center text-sm leading-6 text-slate-500 sm:mt-10 sm:pt-7">
          <p>Não possui uma instância para o seu município?</p>
          <p class="mt-2.5">
            <RouterLink
              to="/solicitar-demonstracao"
              class="font-semibold text-aura-navy underline decoration-aura-navy/30 underline-offset-2 transition hover:text-aura-navy-dark hover:decoration-aura-navy-dark"
            >
              Solicitar Demonstração / Contato Comercial
            </RouterLink>
          </p>
        </div>
      </section>
    </div>
  </div>
</template>
