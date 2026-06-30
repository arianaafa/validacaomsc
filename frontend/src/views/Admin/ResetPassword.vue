<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { fetchAdminUsers, resetUserPassword } from '@/services/adminApi'
import { ApiError } from '@/services/httpClient'
import { useAuthStore } from '@/stores/auth'
import type { AdminUser, ResetPasswordResult } from '@/types/admin'

const auth = useAuthStore()

const loadingUsers = ref(true)
const submitting = ref(false)
const errorMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})
const users = ref<AdminUser[]>([])
const result = ref<ResetPasswordResult | null>(null)
const copied = ref(false)

const form = reactive({
  userId: '',
  password: '',
  useCustomPassword: false,
})

const selectedUser = computed((): AdminUser | null => {
  if (form.userId === '') {
    return null
  }

  return users.value.find((user) => user.id === Number(form.userId)) ?? null
})

const inputClass =
  'w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-900 outline-none transition-colors focus:ring-2'

function inputStateClass(hasError: boolean): string {
  if (hasError) {
    return `${inputClass} border-red-500 focus:border-red-500 focus:ring-red-200`
  }

  return `${inputClass} border-slate-300 focus:border-amber-500 focus:ring-amber-500/20`
}

async function loadUsers(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loadingUsers.value = false
    return
  }

  loadingUsers.value = true
  errorMessage.value = null

  try {
    users.value = await fetchAdminUsers(token)
  } catch {
    errorMessage.value = 'Não foi possível carregar a lista de usuários.'
  } finally {
    loadingUsers.value = false
  }
}

async function handleSubmit(): Promise<void> {
  const token = auth.accessToken

  if (!token || form.userId === '') {
    fieldErrors.value = { user_id: ['Selecione um usuário.'] }
    return
  }

  submitting.value = true
  errorMessage.value = null
  fieldErrors.value = {}
  result.value = null
  copied.value = false

  try {
    result.value = await resetUserPassword(
      Number(form.userId),
      token,
      form.useCustomPassword && form.password !== '' ? form.password : undefined,
    )

    form.password = ''
    await loadUsers()
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      fieldErrors.value = err.errors
    } else {
      errorMessage.value = 'Não foi possível redefinir a senha.'
    }
  } finally {
    submitting.value = false
  }
}

async function copyTemporaryPassword(): Promise<void> {
  if (!result.value?.temporary_password) {
    return
  }

  try {
    await navigator.clipboard.writeText(result.value.temporary_password)
    copied.value = true
  } catch {
    copied.value = false
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <section class="mx-auto max-w-2xl space-y-6">
    <div>
      <h2 class="text-2xl font-semibold text-slate-900">Resetar Senha</h2>
      <p class="mt-1 text-sm text-slate-500">
        Redefina a senha de um usuário municipal. O usuário será obrigado a trocá-la no próximo login.
      </p>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
      <form class="space-y-5" @submit.prevent="handleSubmit">
        <div>
          <label for="admin-user-id" class="mb-1.5 block text-sm font-medium text-slate-700">
            Usuário
          </label>

          <select
            id="admin-user-id"
            v-model="form.userId"
            :disabled="loadingUsers || submitting"
            :class="inputStateClass(Boolean(fieldErrors.user_id?.length))"
            :aria-invalid="Boolean(fieldErrors.user_id?.length)"
          >
            <option value="">
              {{ loadingUsers ? 'Carregando usuários...' : 'Selecione um usuário' }}
            </option>
            <option
              v-for="user in users"
              :key="user.id"
              :value="String(user.id)"
            >
              {{ user.name }} — {{ user.email }}
            </option>
          </select>

          <p
            v-if="fieldErrors.user_id?.length"
            class="mt-1 text-sm text-red-600"
          >
            {{ fieldErrors.user_id[0] }}
          </p>

          <p
            v-if="selectedUser?.force_password_change"
            class="mt-2 text-sm text-amber-700"
          >
            Este usuário já possui uma troca de senha pendente.
          </p>
        </div>

        <div>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input
              v-model="form.useCustomPassword"
              type="checkbox"
              class="rounded border-slate-300 text-amber-600 focus:ring-amber-500"
              :disabled="submitting"
            />
            Definir senha manualmente
          </label>
        </div>

        <div v-if="form.useCustomPassword">
          <label for="admin-password" class="mb-1.5 block text-sm font-medium text-slate-700">
            Nova senha temporária
          </label>
          <input
            id="admin-password"
            v-model="form.password"
            type="password"
            minlength="8"
            autocomplete="new-password"
            :disabled="submitting"
            :class="inputStateClass(Boolean(fieldErrors.password?.length))"
            placeholder="Mínimo de 8 caracteres"
          />
          <p
            v-if="fieldErrors.password?.length"
            class="mt-1 text-sm text-red-600"
          >
            {{ fieldErrors.password[0] }}
          </p>
        </div>

        <p
          v-if="errorMessage"
          class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
          role="alert"
        >
          {{ errorMessage }}
        </p>

        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="submitting || loadingUsers"
        >
          {{ submitting ? 'Redefinindo...' : 'Redefinir senha' }}
        </button>
      </form>
    </div>

    <div
      v-if="result"
      class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm"
      role="status"
    >
      <h3 class="font-semibold text-emerald-900">Senha redefinida com sucesso</h3>
      <p class="mt-1 text-sm text-emerald-800">
        {{ result.user.name }} ({{ result.user.email }}) deverá trocar a senha no próximo acesso.
      </p>

      <div
        v-if="result.temporary_password"
        class="mt-4 rounded-lg border border-emerald-300 bg-white p-4"
      >
        <p class="text-sm font-medium text-slate-700">Senha temporária gerada</p>
        <div class="mt-2 flex flex-wrap items-center gap-3">
          <code class="rounded bg-slate-100 px-3 py-1.5 font-mono text-sm text-slate-900">
            {{ result.temporary_password }}
          </code>
          <button
            type="button"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            @click="copyTemporaryPassword"
          >
            {{ copied ? 'Copiado!' : 'Copiar' }}
          </button>
        </div>
        <p class="mt-2 text-xs text-slate-500">
          Compartilhe esta senha com o usuário por um canal seguro. Ela não será exibida novamente.
        </p>
      </div>
    </div>
  </section>
</template>
