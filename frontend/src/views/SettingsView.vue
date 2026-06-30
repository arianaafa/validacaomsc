<script setup lang="ts">
import { reactive, ref } from 'vue'
import { changePassword } from '@/services/authApi'
import { ApiError } from '@/services/httpClient'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})
const successMessage = ref<string | null>(null)

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const inputClass =
  'w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:ring-2'

function inputStateClass(hasError: boolean): string {
  if (hasError) {
    return `${inputClass} border-red-500 focus:border-red-500 focus:ring-red-200`
  }

  return `${inputClass} border-slate-300 focus:border-aura-blue focus:ring-aura-blue/20`
}

function resetForm(): void {
  form.current_password = ''
  form.password = ''
  form.password_confirmation = ''
  showCurrentPassword.value = false
  showNewPassword.value = false
  showConfirmPassword.value = false
}

async function handleSubmit(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    return
  }

  submitting.value = true
  errorMessage.value = null
  fieldErrors.value = {}
  successMessage.value = null

  try {
    const payload = await changePassword({ ...form }, token)
    auth.updateUser(payload.user)
    successMessage.value = payload.message
    resetForm()
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      fieldErrors.value = err.errors
    } else {
      errorMessage.value = 'Não foi possível alterar a senha.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-2xl space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="mb-1 text-xl font-semibold text-slate-900">Conta</h2>
      <p class="mb-6 text-sm text-slate-500">
        Ajustes da sua conta no Audita MSC.
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

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <h2 class="mb-1 text-xl font-semibold text-slate-900">Alterar senha</h2>
      <p class="mb-6 text-sm text-slate-500">
        Use uma senha forte com pelo menos 8 caracteres.
      </p>

      <div
        v-if="auth.user?.force_password_change"
        class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900"
        role="alert"
      >
        Sua senha foi redefinida por um administrador. Por segurança, crie uma nova senha
        privada antes de continuar usando o sistema.
      </div>

      <form class="space-y-5" @submit.prevent="handleSubmit">
        <div>
          <label for="current-password" class="mb-1.5 block text-sm font-medium text-slate-700">
            Senha atual
          </label>
          <div class="relative">
            <input
              id="current-password"
              v-model="form.current_password"
              :type="showCurrentPassword ? 'text' : 'password'"
              autocomplete="current-password"
              :disabled="submitting"
              :class="[inputStateClass(Boolean(fieldErrors.current_password?.length)), 'pr-11']"
              :aria-invalid="Boolean(fieldErrors.current_password?.length)"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
              :aria-label="showCurrentPassword ? 'Ocultar senha' : 'Mostrar senha'"
              @click="showCurrentPassword = !showCurrentPassword"
            >
              <svg
                v-if="showCurrentPassword"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                aria-hidden="true"
              >
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
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
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
          <p
            v-if="fieldErrors.current_password?.length"
            class="mt-1 text-sm text-red-600"
          >
            {{ fieldErrors.current_password[0] }}
          </p>
        </div>

        <div>
          <label for="new-password" class="mb-1.5 block text-sm font-medium text-slate-700">
            Nova senha
          </label>
          <div class="relative">
            <input
              id="new-password"
              v-model="form.password"
              :type="showNewPassword ? 'text' : 'password'"
              autocomplete="new-password"
              minlength="8"
              :disabled="submitting"
              :class="[inputStateClass(Boolean(fieldErrors.password?.length)), 'pr-11']"
              :aria-invalid="Boolean(fieldErrors.password?.length)"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
              :aria-label="showNewPassword ? 'Ocultar senha' : 'Mostrar senha'"
              @click="showNewPassword = !showNewPassword"
            >
              <svg
                v-if="showNewPassword"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                aria-hidden="true"
              >
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
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
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
          <p
            v-if="fieldErrors.password?.length"
            class="mt-1 text-sm text-red-600"
          >
            {{ fieldErrors.password[0] }}
          </p>
        </div>

        <div>
          <label for="confirm-password" class="mb-1.5 block text-sm font-medium text-slate-700">
            Confirmar nova senha
          </label>
          <div class="relative">
            <input
              id="confirm-password"
              v-model="form.password_confirmation"
              :type="showConfirmPassword ? 'text' : 'password'"
              autocomplete="new-password"
              minlength="8"
              :disabled="submitting"
              :class="[inputStateClass(Boolean(fieldErrors.password_confirmation?.length)), 'pr-11']"
              :aria-invalid="Boolean(fieldErrors.password_confirmation?.length)"
            />
            <button
              type="button"
              class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-500 hover:text-slate-700"
              :aria-label="showConfirmPassword ? 'Ocultar senha' : 'Mostrar senha'"
              @click="showConfirmPassword = !showConfirmPassword"
            >
              <svg
                v-if="showConfirmPassword"
                class="h-5 w-5"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.75"
                aria-hidden="true"
              >
                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94" />
                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19" />
                <line x1="1" y1="1" x2="23" y2="23" />
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
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </button>
          </div>
          <p
            v-if="fieldErrors.password_confirmation?.length"
            class="mt-1 text-sm text-red-600"
          >
            {{ fieldErrors.password_confirmation[0] }}
          </p>
        </div>

        <p
          v-if="errorMessage"
          class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          role="alert"
        >
          {{ errorMessage }}
        </p>

        <p
          v-if="successMessage"
          class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800"
          role="status"
        >
          {{ successMessage }}
        </p>

        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="submitting"
        >
          {{ submitting ? 'Salvando...' : 'Alterar senha' }}
        </button>
      </form>
    </div>
  </section>
</template>
