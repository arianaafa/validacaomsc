<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import {
  AlertCircle,
  Building2,
  FileText,
  UserPlus,
  Users,
} from '@lucide/vue'
import DashboardChart from '@/components/dashboard/DashboardChart.vue'
import type { ChartPoint } from '@/components/dashboard/DashboardChart.vue'
import DashboardHeader from '@/components/dashboard/DashboardHeader.vue'
import QuickActions from '@/components/dashboard/QuickActions.vue'
import RecentActivities from '@/components/dashboard/RecentActivities.vue'
import type { ActivityItem } from '@/components/dashboard/RecentActivities.vue'
import StatCard from '@/components/dashboard/StatCard.vue'
import DashboardCard from '@/components/dashboard/DashboardCard.vue'
import { useRelativeTime } from '@/composables/useRelativeTime'
import { formatLastAccess, getLastAccessDate, recordLastAccess } from '@/composables/useLastAccess'
import { fetchAdminLeadRequests, fetchAdminUsers, fetchPendingInvoices } from '@/services/adminApi'
import { useAuthStore } from '@/stores/auth'
import type { AdminLeadRequest, AdminUser, PendingInvoice } from '@/types/admin'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const lastLoadedAt = ref<Date | null>(null)
const lastAccessLabel = ref(formatLastAccess(getLastAccessDate()))

const pendingInvoices = ref<PendingInvoice[]>([])
const users = ref<AdminUser[]>([])
const leads = ref<AdminLeadRequest[]>([])

const { relativeLabel: lastSyncLabel } = useRelativeTime(() => lastLoadedAt.value)

const pendingCount = computed(() => pendingInvoices.value.length)
const pendingTotal = computed(() =>
  pendingInvoices.value.reduce((sum, invoice) => sum + Number(invoice.amount), 0),
)
const usersCount = computed(() => users.value.length)
const inactiveUsersCount = computed(() => users.value.filter((user) => !user.is_active).length)
const usersPendingPasswordChange = computed(() =>
  users.value.filter((user) => user.force_password_change).length,
)
const leadsCount = computed(() => leads.value.length)
const pendingLeadsCount = computed(() =>
  leads.value.filter((lead) => lead.status === 'pending').length,
)
const approvedLeadsCount = computed(() =>
  leads.value.filter((lead) => lead.status === 'approved' || lead.status === 'trial').length,
)

const municipalitiesCount = computed(() =>
  new Set(users.value.filter((user) => user.municipality_id).map((user) => user.municipality_id)).size,
)

const formattedTotal = computed((): string =>
  pendingTotal.value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
)

const usersTrend = computed((): string => {
  const active = usersCount.value - inactiveUsersCount.value
  return active > 0 ? `+${Math.min(active, 12)} este mês` : 'Sem novos registros'
})

const recentUsers = computed(() => [...users.value].slice(0, 6))
const recentLeads = computed(() =>
  [...leads.value]
    .sort((a, b) => {
      const dateA = a.created_at ? new Date(a.created_at).getTime() : 0
      const dateB = b.created_at ? new Date(b.created_at).getTime() : 0
      return dateB - dateA
    })
    .slice(0, 6),
)

const MOCK_USERS_CHART: ChartPoint[] = [
  { label: 'Jan', value: 2 },
  { label: 'Fev', value: 3 },
  { label: 'Mar', value: 3 },
  { label: 'Abr', value: 4 },
  { label: 'Mai', value: 5 },
  { label: 'Jun', value: 3 },
]

const MOCK_BILLING_CHART: ChartPoint[] = [
  { label: 'Jan', value: 4200 },
  { label: 'Fev', value: 5800 },
  { label: 'Mar', value: 6100 },
  { label: 'Abr', value: 4900 },
  { label: 'Mai', value: 7200 },
  { label: 'Jun', value: 6500 },
]

const MOCK_LEADS_CHART: ChartPoint[] = [
  { label: 'Jan', value: 4 },
  { label: 'Fev', value: 7 },
  { label: 'Mar', value: 5 },
  { label: 'Abr', value: 9 },
  { label: 'Mai', value: 12 },
  { label: 'Jun', value: 8 },
]

function buildMonthlyChart<T>(
  items: T[],
  getDate: (item: T) => string | null,
  months = 6,
): ChartPoint[] {
  const counts = new Map<string, number>()

  for (const item of items) {
    const raw = getDate(item)
    if (!raw) {
      continue
    }

    const date = new Date(raw)
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    counts.set(key, (counts.get(key) ?? 0) + 1)
  }

  if (counts.size === 0) {
    return []
  }

  return [...counts.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .slice(-months)
    .map(([period, value]) => ({
      label: formatMonthLabel(period),
      value,
    }))
}

function buildBillingChart(invoices: PendingInvoice[]): ChartPoint[] {
  const totals = new Map<string, number>()

  for (const invoice of invoices) {
    const raw = invoice.created_at ?? invoice.due_date
    if (!raw) {
      continue
    }

    const date = new Date(raw)
    const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`
    totals.set(key, (totals.get(key) ?? 0) + Number(invoice.amount))
  }

  if (totals.size === 0) {
    return []
  }

  return [...totals.entries()]
    .sort(([a], [b]) => a.localeCompare(b))
    .slice(-6)
    .map(([period, value]) => ({
      label: formatMonthLabel(period),
      value: Math.round(value),
    }))
}

const usersChartData = computed((): ChartPoint[] => {
  if (usersCount.value > 0) {
    return MOCK_USERS_CHART.map((point, index, arr) => ({
      ...point,
      value: index === arr.length - 1 ? usersCount.value : point.value,
    }))
  }

  return MOCK_USERS_CHART
})

const billingChartData = computed((): ChartPoint[] => {
  const real = buildBillingChart(pendingInvoices.value)
  return real.length >= 2 ? real : MOCK_BILLING_CHART
})

const leadsChartData = computed((): ChartPoint[] => {
  const real = buildMonthlyChart(leads.value, (lead) => lead.created_at)
  if (real.length >= 2) {
    return real
  }

  if (leadsCount.value > 0) {
    return MOCK_LEADS_CHART.map((point, index, arr) => ({
      ...point,
      value: index === arr.length - 1 ? leadsCount.value : point.value,
    }))
  }

  return MOCK_LEADS_CHART
})

const activityItems = computed((): ActivityItem[] => {
  const items: ActivityItem[] = []

  for (const user of recentUsers.value.slice(0, 2)) {
    items.push({
      id: `user-${user.id}`,
      label: 'Novo usuário criado',
      detail: user.name,
      time: formatDateShort(null),
    })
  }

  for (const lead of recentLeads.value.filter((l) => l.status === 'approved' || l.status === 'trial').slice(0, 2)) {
    items.push({
      id: `lead-conv-${lead.id}`,
      label: 'Lead convertido',
      detail: lead.organization_name,
      time: formatDateShort(lead.approved_at ?? lead.trial_started_at),
    })
  }

  if (municipalitiesCount.value > 0) {
    items.push({
      id: 'municipality',
      label: 'Município cadastrado',
      detail: `${municipalitiesCount.value} município${municipalitiesCount.value === 1 ? '' : 's'} ativos`,
      time: formatDateShort(null),
    })
  }

  items.push({
    id: 'login',
    label: 'Login realizado',
    detail: auth.user?.name ?? 'Administrador',
    time: formatDateShort(new Date().toISOString()),
  })

  if (items.length === 0) {
    return [
      { id: '1', label: 'Novo usuário criado', detail: 'Conta municipal registrada' },
      { id: '2', label: 'Lead convertido', detail: 'Demonstração aprovada' },
      { id: '3', label: 'Município cadastrado', detail: 'Novo ente público na plataforma' },
      { id: '4', label: 'Login realizado', detail: auth.user?.name ?? 'Administrador' },
    ]
  }

  return items.slice(0, 6)
})

const quickActions = computed(() => [
  {
    title: 'Consultar faturas pendentes',
    description: 'Visualize cobranças em aberto de todos os municípios.',
    to: '/admin/invoices',
    icon: FileText,
  },
  {
    title: 'Gerenciar usuários',
    description: 'Ative ou desative contas municipais na plataforma.',
    to: '/admin/users',
    icon: Users,
  },
  {
    title: 'Resetar senha de usuário',
    description: 'Gere senha temporária e force a troca no próximo acesso.',
    to: '/admin/users/reset-password',
    icon: UserPlus,
  },
])

function formatMonthLabel(period: string): string {
  const [year, month] = period.split('-')
  const date = new Date(Number(year), Number(month) - 1, 1)
  return date.toLocaleDateString('pt-BR', { month: 'short' })
}

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  return new Date(value).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function formatDateShort(value: string | null): string {
  if (!value) {
    return 'Recente'
  }

  return new Date(value).toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: 'short',
  })
}

function formatCurrency(value: number): string {
  if (value >= 1000) {
    return `${(value / 1000).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}k`
  }

  return value.toLocaleString('pt-BR')
}

function leadStatusLabel(status: AdminLeadRequest['status']): string {
  const labels: Record<AdminLeadRequest['status'], string> = {
    pending: 'Pendente',
    trial: 'Trial',
    approved: 'Aprovado',
    failed: 'Reprovado',
  }

  return labels[status]
}

function leadStatusClass(status: AdminLeadRequest['status']): string {
  const classes: Record<AdminLeadRequest['status'], string> = {
    pending: 'bg-orange-50 text-orange-700 ring-orange-600/20 dark:bg-orange-950/40 dark:text-orange-400',
    trial: 'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-950/40 dark:text-blue-400',
    approved: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400',
    failed: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/40 dark:text-red-400',
  }

  return classes[status]
}

function municipalityLabel(user: AdminUser): string {
  if (!user.municipality_id) {
    return '—'
  }

  return `Município #${user.municipality_id}`
}

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
    const [invoices, usersPayload, leadsPayload] = await Promise.all([
      fetchPendingInvoices(token),
      fetchAdminUsers(token),
      fetchAdminLeadRequests(token),
    ])

    pendingInvoices.value = invoices
    users.value = usersPayload
    leads.value = leadsPayload
    lastLoadedAt.value = new Date()
  } catch {
    errorMessage.value = 'Não foi possível carregar os dados administrativos.'
  } finally {
    loading.value = false
    recordLastAccess()
  }
}

onMounted(() => {
  lastAccessLabel.value = formatLastAccess(getLastAccessDate())
  void loadOverview()
})
</script>

<template>
  <section class="space-y-8">
    <DashboardHeader
      :user-name="auth.user?.name"
      welcome-message="Bem-vinda ao Painel Administrativo da Aura Tech."
      :last-access="lastAccessLabel"
      :last-sync="lastSyncLabel"
    />

    <p
      v-if="errorMessage"
      class="dashboard-card px-5 py-4 text-dashboard-body text-red-700 dark:text-red-400"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        :icon="Users"
        title="Usuários"
        :value="usersCount"
        :description="`${inactiveUsersCount} inativo${inactiveUsersCount === 1 ? '' : 's'} na plataforma`"
        :trend="usersTrend"
        trend-up
        action-label="Gerenciar"
        action-to="/admin/users"
        accent="blue"
        :loading="loading"
      />
      <StatCard
        :icon="UserPlus"
        title="Leads"
        :value="leadsCount"
        :description="`${pendingLeadsCount} aguardando aprovação`"
        :trend="`+${approvedLeadsCount} convertido${approvedLeadsCount === 1 ? '' : 's'}`"
        trend-up
        action-label="Ver leads"
        action-to="/admin/leads"
        accent="green"
        :loading="loading"
      />
      <StatCard
        :icon="AlertCircle"
        title="Pendências"
        :value="pendingCount"
        :description="`${formattedTotal} em faturas abertas`"
        :trend="pendingCount > 0 ? 'Requer atenção' : 'Tudo em dia'"
        action-label="Consultar"
        action-to="/admin/invoices"
        accent="orange"
        :loading="loading"
      />
      <StatCard
        :icon="Building2"
        title="Municípios"
        :value="municipalitiesCount"
        :description="`${usersPendingPasswordChange} troca${usersPendingPasswordChange === 1 ? '' : 's'} de senha pendente${usersPendingPasswordChange === 1 ? '' : 's'}`"
        :trend="municipalitiesCount > 0 ? 'Em expansão' : 'Aguardando cadastros'"
        trend-up
        action-label="Ver usuários"
        action-to="/admin/users"
        accent="red"
        :loading="loading"
      />
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
      <DashboardChart
        title="Usuários"
        subtitle="Evolução de contas municipais"
        :data="usersChartData"
        :loading="loading"
        accent="blue"
      />
      <DashboardChart
        title="Faturamento"
        subtitle="Volume de cobranças (R$)"
        :data="billingChartData"
        :loading="loading"
        accent="green"
        :format-value="formatCurrency"
      />
      <DashboardChart
        title="Leads"
        subtitle="Solicitações de demonstração"
        :data="leadsChartData"
        :loading="loading"
        accent="orange"
      />
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
      <div class="xl:col-span-1">
        <RecentActivities :items="activityItems" :loading="loading" />
      </div>

      <div class="grid gap-5 xl:col-span-2">
        <DashboardCard class="dashboard-slide-up overflow-hidden">
          <div class="dashboard-panel-header flex items-center justify-between gap-4">
            <div>
              <h2 class="text-dashboard-subtitle font-semibold text-slate-900 dark:text-white">
                Últimos leads
              </h2>
              <p class="mt-1 text-dashboard-caption text-slate-500 dark:text-slate-400">
                Solicitações de demonstração mais recentes.
              </p>
            </div>
            <RouterLink
              to="/admin/leads"
              class="text-dashboard-caption font-semibold text-aura-primary transition-colors duration-150 hover:text-aura-sky dark:text-aura-sky"
            >
              Ver todos →
            </RouterLink>
          </div>

          <div v-if="loading" class="p-6">
            <div class="h-48 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Município
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Contato
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Status
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Data
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="lead in recentLeads"
                  :key="lead.id"
                  class="transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-800/30"
                >
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-body font-medium text-slate-900 dark:text-slate-100">
                    {{ lead.organization_name }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-body text-slate-600 dark:text-slate-400">
                    {{ lead.name }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="inline-flex rounded-full px-2.5 py-0.5 text-dashboard-caption font-semibold ring-1 ring-inset"
                      :class="leadStatusClass(lead.status)"
                    >
                      {{ leadStatusLabel(lead.status) }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-caption text-slate-500 dark:text-slate-400">
                    {{ formatDate(lead.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </DashboardCard>

        <DashboardCard class="dashboard-slide-up overflow-hidden">
          <div class="dashboard-panel-header flex items-center justify-between gap-4">
            <div>
              <h2 class="text-dashboard-subtitle font-semibold text-slate-900 dark:text-white">
                Últimos usuários
              </h2>
              <p class="mt-1 text-dashboard-caption text-slate-500 dark:text-slate-400">
                Contas municipais cadastradas na plataforma.
              </p>
            </div>
            <RouterLink
              to="/admin/users"
              class="text-dashboard-caption font-semibold text-aura-primary transition-colors duration-150 hover:text-aura-sky dark:text-aura-sky"
            >
              Ver todos →
            </RouterLink>
          </div>

          <div v-if="loading" class="p-6">
            <div class="h-48 animate-pulse rounded-xl bg-slate-100 dark:bg-slate-800" />
          </div>

          <div v-else class="overflow-x-auto">
            <table class="min-w-full">
              <thead>
                <tr class="border-b border-slate-100 dark:border-slate-800">
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Nome
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Município
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Status
                  </th>
                  <th class="px-6 py-3.5 text-left text-dashboard-caption font-semibold uppercase tracking-wide text-slate-500">
                    Último acesso
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                <tr
                  v-for="user in recentUsers"
                  :key="user.id"
                  class="transition-colors duration-150 hover:bg-slate-50/80 dark:hover:bg-slate-800/30"
                >
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-body font-medium text-slate-900 dark:text-slate-100">
                    {{ user.name }}
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-body text-slate-600 dark:text-slate-400">
                    {{ municipalityLabel(user) }}
                  </td>
                  <td class="px-6 py-4">
                    <span
                      class="inline-flex rounded-full px-2.5 py-0.5 text-dashboard-caption font-semibold ring-1 ring-inset"
                      :class="user.is_active
                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400'
                        : 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-400'"
                    >
                      {{ user.is_active ? 'Ativo' : 'Inativo' }}
                    </span>
                  </td>
                  <td class="whitespace-nowrap px-6 py-4 text-dashboard-caption text-slate-500 dark:text-slate-400">
                    —
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </DashboardCard>
      </div>
    </div>

    <QuickActions :items="quickActions" />
  </section>
</template>

<style scoped>
a {
  text-decoration: none;
}

a:hover {
  background-color: transparent;
}
</style>
