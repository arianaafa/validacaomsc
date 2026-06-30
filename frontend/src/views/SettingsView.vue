<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
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
  'w-full rounded-lg border px-3.5 py-3 text-base font-normal text-slate-900 outline-none transition-colors focus:ring-2'

const submitButtonClass =
  'flex w-full items-center justify-center gap-2 rounded-lg bg-aura-navy px-4 py-3.5 text-base font-semibold text-white transition hover:bg-aura-navy-dark disabled:cursor-not-allowed disabled:opacity-70 sm:w-auto sm:min-w-[11rem]'

const userInitials = computed((): string => {
  const name = auth.user?.name?.trim()

  if (!name) {
    return '?'
  }

  const parts = name.split(/\s+/).filter(Boolean)

  if (parts.length === 1) {
    return parts[0]!.slice(0, 2).toUpperCase()
  }

  return `${parts[0]![0] ?? ''}${parts[parts.length - 1]![0] ?? ''}`.toUpperCase()
})

const accountStatus = computed((): { label: string; badgeClass: string } => {
  if (auth.user?.is_active === false) {
    return {
      label: 'Conta inativa',
      badgeClass: 'bg-red-50 text-red-700 ring-1 ring-red-600/20',
    }
  }

  return {
    label: 'Conta ativa',
    badgeClass: 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20',
  }
})

const municipalityName = computed((): string | null => auth.user?.municipality?.name ?? null)

const municipalityIbgeCode = computed((): string | null => auth.user?.municipality?.ibge_code ?? null)

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
  <section class="mx-auto w-full max-w-3xl">
    <header class="mb-6 sm:mb-8">
      <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
        Configurações
      </h2>
      <p class="mt-1.5 text-sm text-slate-500 sm:mt-2 sm:text-base">
        Gerencie os dados da sua conta e a segurança de acesso.
      </p>
    </header>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <section
        class="border-b border-slate-100 bg-gradient-to-r from-aura-bg-start to-white px-6 py-6 sm:px-8 sm:py-7"
        aria-labelledby="settings-account-heading"
      >
        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
          <div class="flex items-center gap-4">
            <div
              class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-aura-navy/10 text-lg font-bold text-aura-navy"
              aria-hidden="true"
            >
              {{ userInitials }}
            </div>

            <div class="min-w-0">
              <h3 id="settings-account-heading" class="truncate text-lg font-semibold text-slate-900">
                {{ auth.user?.name }}
              </h3>
              <p class="truncate text-sm text-slate-500">
                {{ auth.user?.email }}
              </p>
              <p
                v-if="municipalityName"
                class="mt-1 truncate text-sm text-slate-500"
              >
                Ambiente de validação:
                <span class="font-medium text-slate-700">{{ municipalityName }}</span>
              </p>
            </div>
          </div>

          <div
            class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-wide"
            :class="accountStatus.badgeClass"
          >
            {{ accountStatus.label }}
          </div>
        </div>

        <dl v-if="auth.user" class="mt-6 grid gap-4 sm:grid-cols-2">
          <div class="rounded-lg border border-slate-200/80 bg-white/80 px-4 py-3">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nome</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ auth.user.name }}</dd>
          </div>
          <div class="rounded-lg border border-slate-200/80 bg-white/80 px-4 py-3">
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail</dt>
            <dd class="mt-1 break-all text-sm font-medium text-slate-900">{{ auth.user.email }}</dd>
          </div>
          <div
            v-if="municipalityName"
            class="rounded-lg border border-slate-200/80 bg-white/80 px-4 py-3"
          >
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Município</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ municipalityName }}</dd>
          </div>
          <div
            v-if="municipalityIbgeCode"
            class="rounded-lg border border-slate-200/80 bg-white/80 px-4 py-3"
          >
            <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Código IBGE</dt>
            <dd class="mt-1 text-sm font-medium tabular-nums text-slate-900">{{ municipalityIbgeCode }}</dd>
          </div>
        </dl>
      </section>

      <section class="px-6 py-6 sm:px-8 sm:py-8" aria-labelledby="settings-password-heading">
        <header class="mb-5 sm:mb-6">
          <h3 id="settings-password-heading" class="text-xl font-semibold text-slate-900">
            Alterar senha
          </h3>
          <p class="mt-1.5 text-sm text-slate-500">
            Use uma senha forte com pelo menos 8 caracteres, diferente da atual.
          </p>
        </header>

        <div
          v-if="auth.user?.force_password_change"
          class="mb-5 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-relaxed text-amber-900 sm:mb-6"
          role="alert"
        >
          Sua senha foi redefinida por um administrador. Por segurança, crie uma nova senha
          privada antes de continuar usando o sistema.
        </div>

        <form class="grid max-w-xl gap-4" @submit.prevent="handleSubmit">
          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            Senha atual
            <div class="relative font-normal">
              <input
                id="current-password"
                v-model="form.current_password"
                :type="showCurrentPassword ? 'text' : 'password'"
                autocomplete="current-password"
                :disabled="submitting"
                :class="[inputStateClass(Boolean(fieldErrors.current_password?.length)), 'pr-11']"
                :aria-invalid="Boolean(fieldErrors.current_password?.length)"
                aria-describedby="current-password-error"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600"
                :aria-label="showCurrentPassword ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="showCurrentPassword"
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
              v-if="fieldErrors.current_password?.length"
              id="current-password-error"
              class="text-sm font-medium text-red-600"
              role="alert"
            >
              {{ fieldErrors.current_password[0] }}
            </span>
          </label>

          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            Nova senha
            <div class="relative font-normal">
              <input
                id="new-password"
                v-model="form.password"
                :type="showNewPassword ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="8"
                :disabled="submitting"
                :class="[inputStateClass(Boolean(fieldErrors.password?.length)), 'pr-11']"
                :aria-invalid="Boolean(fieldErrors.password?.length)"
                aria-describedby="new-password-error"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600"
                :aria-label="showNewPassword ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="showNewPassword"
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
              v-if="fieldErrors.password?.length"
              id="new-password-error"
              class="text-sm font-medium text-red-600"
              role="alert"
            >
              {{ fieldErrors.password[0] }}
            </span>
          </label>

          <label class="grid gap-2 text-sm font-semibold text-slate-700">
            Confirmar nova senha
            <div class="relative font-normal">
              <input
                id="confirm-password"
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                autocomplete="new-password"
                minlength="8"
                :disabled="submitting"
                :class="[inputStateClass(Boolean(fieldErrors.password_confirmation?.length)), 'pr-11']"
                :aria-invalid="Boolean(fieldErrors.password_confirmation?.length)"
                aria-describedby="confirm-password-error"
              />
              <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600"
                :aria-label="showConfirmPassword ? 'Ocultar senha' : 'Mostrar senha'"
                :aria-pressed="showConfirmPassword"
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
              v-if="fieldErrors.password_confirmation?.length"
              id="confirm-password-error"
              class="text-sm font-medium text-red-600"
              role="alert"
            >
              {{ fieldErrors.password_confirmation[0] }}
            </span>
          </label>

          <p
            v-if="errorMessage"
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
            role="alert"
          >
            {{ errorMessage }}
          </p>

          <p
            v-if="successMessage"
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            role="status"
          >
            {{ successMessage }}
          </p>

          <div class="border-t border-slate-100 pt-4">
            <button
              type="submit"
              :disabled="submitting"
              :class="submitButtonClass"
            >
              <svg
                v-if="submitting"
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
              {{ submitting ? 'Salvando...' : 'Alterar senha' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </section>
</template>
