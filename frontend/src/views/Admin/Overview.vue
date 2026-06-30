<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchAdminUsers, fetchPendingInvoices } from '@/services/adminApi'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const pendingCount = ref(0)
const pendingTotal = ref(0)
const usersCount = ref(0)
const inactiveUsersCount = ref(0)
const usersPendingPasswordChange = ref(0)

const formattedTotal = computed((): string =>
  pendingTotal.value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
)

async function loadOverview(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    const [invoices, users] = await Promise.all([
      fetchPendingInvoices(token),
      fetchAdminUsers(token),
    ])

    pendingCount.value = invoices.length
    pendingTotal.value = invoices.reduce((sum, invoice) => sum + Number(invoice.amount), 0)
    usersCount.value = users.length
    inactiveUsersCount.value = users.filter((user) => !user.is_active).length
    usersPendingPasswordChange.value = users.filter((user) => user.force_password_change).length
  } catch {
    errorMessage.value = 'Não foi possível carregar os dados administrativos.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadOverview()
})
</script>

<template>
  <section class="space-y-6">
    <div>
      <h2 class="text-2xl font-semibold text-slate-900">Visão Geral</h2>
      <p class="mt-1 text-sm text-slate-500">
        Painel administrativo global da plataforma Audita MSC.
      </p>
    </div>

    <p
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <div
      v-if="loading"
      class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
      aria-busy="true"
      aria-label="Carregando indicadores"
    >
      <div
        v-for="index in 3"
        :key="index"
        class="h-28 animate-pulse rounded-xl bg-slate-200"
      />
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Faturas pendentes</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ pendingCount }}</p>
        <p class="mt-1 text-sm text-amber-700">{{ formattedTotal }} em aberto</p>
      </article>

      <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Usuários municipais</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ usersCount }}</p>
        <p class="mt-1 text-sm text-slate-500">
          {{ inactiveUsersCount }} inativo{{ inactiveUsersCount === 1 ? '' : 's' }}
        </p>
      </article>

      <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Troca de senha pendente</p>
        <p class="mt-2 text-3xl font-semibold text-slate-900">{{ usersPendingPasswordChange }}</p>
        <p class="mt-1 text-sm text-slate-500">Aguardando redefinição no login</p>
      </article>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <RouterLink
        to="/admin/invoices"
        class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md"
      >
        <h3 class="font-semibold text-slate-900 group-hover:text-amber-800">
          Consultar faturas pendentes
        </h3>
        <p class="mt-1 text-sm text-slate-500">
          Visualize cobranças em aberto de todos os municípios, ordenadas por vencimento.
        </p>
      </RouterLink>

      <RouterLink
        to="/admin/users"
        class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md"
      >
        <h3 class="font-semibold text-slate-900 group-hover:text-amber-800">
          Gerenciar usuários
        </h3>
        <p class="mt-1 text-sm text-slate-500">
          Ative ou desative contas municipais e controle o acesso à plataforma.
        </p>
      </RouterLink>

      <RouterLink
        to="/admin/users/reset-password"
        class="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-amber-300 hover:shadow-md"
      >
        <h3 class="font-semibold text-slate-900 group-hover:text-amber-800">
          Resetar senha de usuário
        </h3>
        <p class="mt-1 text-sm text-slate-500">
          Gere uma senha temporária e force a troca no próximo acesso do usuário.
        </p>
      </RouterLink>
    </div>
  </section>
</template>
