<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchAdminUsers, updateUserStatus } from '@/services/adminApi'
import { ApiError } from '@/services/httpClient'
import { useAuthStore } from '@/stores/auth'
import type { AdminUser } from '@/types/admin'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const users = ref<AdminUser[]>([])
const updatingUserId = ref<number | null>(null)

async function loadUsers(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    users.value = await fetchAdminUsers(token)
  } catch {
    errorMessage.value = 'Não foi possível carregar os usuários.'
  } finally {
    loading.value = false
  }
}

async function handleToggle(user: AdminUser): Promise<void> {
  const token = auth.accessToken

  if (!token || updatingUserId.value !== null) {
    return
  }

  const nextActive = !user.is_active

  if (
    !nextActive
    && !window.confirm(
      `Desativar ${user.name}? Todas as sessões ativas deste usuário serão encerradas.`,
    )
  ) {
    return
  }

  updatingUserId.value = user.id
  successMessage.value = null
  errorMessage.value = null

  try {
    const payload = await updateUserStatus(user.id, nextActive, token)
    users.value = users.value.map((item) =>
      item.id === user.id ? payload.user : item,
    )
    successMessage.value = payload.message
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
    } else {
      errorMessage.value = 'Não foi possível atualizar o status do usuário.'
    }
  } finally {
    updatingUserId.value = null
  }
}

onMounted(() => {
  void loadUsers()
})
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-900">Usuários</h2>
        <p class="mt-1 text-sm text-slate-500">
          Ative ou desative contas municipais. Usuários inativos não conseguem acessar o sistema.
        </p>
      </div>

      <RouterLink
        to="/admin/users/reset-password"
        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
      >
        Resetar senha
      </RouterLink>
    </div>

    <p
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <p
      v-if="successMessage"
      class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
      role="status"
    >
      {{ successMessage }}
    </p>

    <div
      v-if="loading"
      class="h-64 animate-pulse rounded-xl bg-slate-200"
      aria-busy="true"
      aria-label="Carregando usuários"
    />

    <div
      v-else-if="users.length === 0"
      class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm"
    >
      <p class="text-base font-medium text-slate-900">Nenhum usuário municipal cadastrado</p>
    </div>

    <div
      v-else
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Usuário
              </th>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Status
              </th>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Observações
              </th>
              <th scope="col" class="px-4 py-3 text-right font-semibold text-slate-600">
                Acesso
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="user in users"
              :key="user.id"
              class="hover:bg-slate-50/80"
            >
              <td class="px-4 py-4">
                <p class="font-medium text-slate-900">{{ user.name }}</p>
                <p class="mt-0.5 text-slate-500">{{ user.email }}</p>
              </td>
              <td class="px-4 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="user.is_active
                    ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                    : 'bg-red-50 text-red-700 ring-red-600/20'"
                >
                  {{ user.is_active ? 'Ativa' : 'Inativa' }}
                </span>
              </td>
              <td class="px-4 py-4 text-slate-500">
                <span v-if="user.force_password_change">Troca de senha pendente</span>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-3">
                  <span
                    class="text-xs font-medium"
                    :class="user.is_active ? 'text-emerald-700' : 'text-slate-500'"
                  >
                    {{ user.is_active ? 'Ativo' : 'Inativo' }}
                  </span>
                  <button
                    type="button"
                    role="switch"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                    :class="user.is_active ? 'bg-emerald-500' : 'bg-slate-300'"
                    :aria-checked="user.is_active"
                    :aria-label="`${user.is_active ? 'Desativar' : 'Ativar'} ${user.name}`"
                    :disabled="updatingUserId === user.id"
                    @click="handleToggle(user)"
                  >
                    <span
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition"
                      :class="user.is_active ? 'translate-x-5' : 'translate-x-0'"
                    />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
