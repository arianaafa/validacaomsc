<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import {
  approveLeadRequest,
  failLeadRequest,
  fetchAdminLeadRequests,
  startLeadTrial,
} from '@/services/adminApi'
import { ApiError } from '@/services/httpClient'
import { useAuthStore } from '@/stores/auth'
import type { AdminLeadRequest, AdminLeadStatus } from '@/types/admin'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const leads = ref<AdminLeadRequest[]>([])
const actingLeadId = ref<string | null>(null)
const lastTemporaryPassword = ref<string | null>(null)

const statusLabels: Record<AdminLeadStatus, string> = {
  pending: 'Pendente',
  trial: 'Em teste',
  approved: 'Aprovado',
  failed: 'Falho / Expirado',
}

const statusClasses: Record<AdminLeadStatus, string> = {
  pending: 'bg-slate-100 text-slate-700 ring-slate-500/20',
  trial: 'bg-amber-50 text-amber-800 ring-amber-600/20',
  approved: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  failed: 'bg-red-50 text-red-700 ring-red-600/20',
}

const pendingCount = computed((): number =>
  leads.value.filter((lead) => lead.status === 'pending').length,
)

function formatDate(value: string | null): string {
  if (!value) {
    return '—'
  }

  return new Date(value).toLocaleString('pt-BR')
}

async function loadLeads(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    leads.value = await fetchAdminLeadRequests(token)
  } catch {
    errorMessage.value = 'Não foi possível carregar os leads.'
  } finally {
    loading.value = false
  }
}

function replaceLead(updated: AdminLeadRequest): void {
  leads.value = leads.value.map((lead) => (lead.id === updated.id ? updated : lead))
}

async function handleStartTrial(lead: AdminLeadRequest): Promise<void> {
  const token = auth.accessToken

  if (!token || actingLeadId.value !== null) {
    return
  }

  if (
    !window.confirm(
      `Provisionar trial para ${lead.name}? Será criado município, usuário e senha temporária.`,
    )
  ) {
    return
  }

  actingLeadId.value = lead.id
  successMessage.value = null
  errorMessage.value = null
  lastTemporaryPassword.value = null

  try {
    const payload = await startLeadTrial(lead.id, token)
    replaceLead(payload.lead_request)
    lastTemporaryPassword.value = payload.temporary_password
    successMessage.value = `${payload.message} Senha temporária: ${payload.temporary_password}`
  } catch (err) {
    errorMessage.value = err instanceof ApiError ? err.message : 'Não foi possível iniciar o trial.'
  } finally {
    actingLeadId.value = null
  }
}

async function handleApprove(lead: AdminLeadRequest): Promise<void> {
  const token = auth.accessToken

  if (!token || actingLeadId.value !== null) {
    return
  }

  actingLeadId.value = lead.id
  successMessage.value = null
  errorMessage.value = null

  try {
    const payload = await approveLeadRequest(lead.id, token)
    replaceLead(payload.lead_request)
    successMessage.value = payload.message
  } catch (err) {
    errorMessage.value = err instanceof ApiError ? err.message : 'Não foi possível aprovar o lead.'
  } finally {
    actingLeadId.value = null
  }
}

async function handleFail(lead: AdminLeadRequest): Promise<void> {
  const token = auth.accessToken

  if (!token || actingLeadId.value !== null) {
    return
  }

  if (!window.confirm(`Marcar ${lead.name} como falho/expirado?`)) {
    return
  }

  actingLeadId.value = lead.id
  successMessage.value = null
  errorMessage.value = null

  try {
    const payload = await failLeadRequest(lead.id, token)
    replaceLead(payload.lead_request)
    successMessage.value = payload.message
  } catch (err) {
    errorMessage.value = err instanceof ApiError ? err.message : 'Não foi possível atualizar o lead.'
  } finally {
    actingLeadId.value = null
  }
}

onMounted(() => {
  void loadLeads()
})
</script>

<template>
  <section class="space-y-6">
    <div>
      <h2 class="text-2xl font-semibold text-slate-900">Leads</h2>
      <p class="mt-1 text-sm text-slate-500">
        Gerencie solicitações de demonstração: trial (24h, 1 importação), aprovação ou descarte.
      </p>
      <p v-if="pendingCount > 0" class="mt-2 text-sm font-medium text-amber-700">
        {{ pendingCount }} lead(s) aguardando provisionamento.
      </p>
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
    />

    <div
      v-else-if="leads.length === 0"
      class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm"
    >
      <p class="text-base font-medium text-slate-900">Nenhum lead recebido ainda</p>
    </div>

    <div
      v-else
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Lead</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Município</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
              <th class="px-4 py-3 text-left font-semibold text-slate-600">Trial</th>
              <th class="px-4 py-3 text-right font-semibold text-slate-600">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="lead in leads"
              :key="lead.id"
              class="align-top hover:bg-slate-50/80"
            >
              <td class="px-4 py-4">
                <p class="font-medium text-slate-900">{{ lead.name }}</p>
                <p class="mt-0.5 text-slate-500">{{ lead.email }}</p>
                <p class="mt-1 text-xs text-slate-400">{{ formatDate(lead.created_at) }}</p>
              </td>
              <td class="px-4 py-4 text-slate-600">
                <p>{{ lead.organization_name }}</p>
                <p class="mt-0.5 text-xs text-slate-400">IBGE {{ lead.ibge_code }}</p>
              </td>
              <td class="px-4 py-4">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                  :class="statusClasses[lead.status]"
                >
                  {{ statusLabels[lead.status] }}
                </span>
              </td>
              <td class="px-4 py-4 text-slate-500">
                <template v-if="lead.status === 'trial'">
                  <p>Início: {{ formatDate(lead.trial_started_at) }}</p>
                  <p>Expira: {{ formatDate(lead.trial_expires_at) }}</p>
                </template>
                <template v-else-if="lead.status === 'approved'">
                  <p>Aprovado: {{ formatDate(lead.approved_at) }}</p>
                </template>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-4">
                <div class="flex flex-wrap justify-end gap-2">
                  <button
                    v-if="lead.status === 'pending'"
                    type="button"
                    class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-amber-600 disabled:opacity-60"
                    :disabled="actingLeadId === lead.id"
                    @click="handleStartTrial(lead)"
                  >
                    Iniciar trial
                  </button>
                  <button
                    v-if="lead.status === 'trial'"
                    type="button"
                    class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-emerald-700 disabled:opacity-60"
                    :disabled="actingLeadId === lead.id"
                    @click="handleApprove(lead)"
                  >
                    Aprovar
                  </button>
                  <button
                    v-if="lead.status === 'pending' || lead.status === 'trial'"
                    type="button"
                    class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100 disabled:opacity-60"
                    :disabled="actingLeadId === lead.id"
                    @click="handleFail(lead)"
                  >
                    Falhar
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
