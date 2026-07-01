<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import DashboardPageHeader from '@/components/dashboard/DashboardPageHeader.vue'
import KpiCard from '@/components/dashboard/KpiCard.vue'
import { useGreeting } from '@/composables/useGreeting'
import { useRelativeTime } from '@/composables/useRelativeTime'
import { fetchMscDashboard } from '@/services/mscApi'
import { useAuthStore } from '@/stores/auth'
import type {
  MscDashboardTrendPoint,
  MscFinalStatus,
  MscUploadRecord,
} from '@/types/msc'
import {
  MSC_TIPO_LABELS,
  buildDashboardSummary,
  buildDashboardTrend,
  canReuploadUpload,
  formatPeriodo,
  formatPeriodoCurto,
  resolveFinalStatus,
} from '@/types/msc'

const auth = useAuthStore()
const { greetingWithName } = useGreeting(computed(() => auth.user?.name))

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const uploads = ref<MscUploadRecord[]>([])
const lastLoadedAt = ref<Date | null>(null)

const { relativeLabel: lastUpdatedLabel } = useRelativeTime(() => lastLoadedAt.value)

const municipalityLabel = computed((): string | null => auth.user?.municipality?.name ?? null)

const summary = computed(() => buildDashboardSummary(uploads.value))

const trend = computed((): MscDashboardTrendPoint[] =>
  buildDashboardTrend(uploads.value),
)

const hasUploads = computed((): boolean => uploads.value.length > 0)

const chartMaxValue = computed((): number => {
  const max = trend.value.reduce(
    (current, point) => Math.max(current, point.total_errors + point.total_alerts),
    0,
  )

  return max > 0 ? max : 1
})

const statusLabels: Record<MscFinalStatus, string> = {
  sucesso: 'Sucesso',
  atencao: 'Atenção',
  inconsistente: 'Inconsistente',
}

const statusClasses: Record<MscFinalStatus, string> = {
  sucesso: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/40 dark:text-emerald-400',
  atencao: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/40 dark:text-amber-400',
  inconsistente: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/40 dark:text-red-400',
}

function formatPercent(value: number): string {
  return `${value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`
}

function formatAverage(value: number): string {
  return value.toLocaleString('pt-BR', { maximumFractionDigits: 1 })
}

function barHeight(value: number): string {
  return `${Math.max(4, (value / chartMaxValue.value) * 100)}%`
}

async function loadDashboard(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    const payload = await fetchMscDashboard(token)
    uploads.value = payload.uploads
    lastLoadedAt.value = new Date()
  } catch {
    errorMessage.value = 'Não foi possível carregar o dashboard. Tente novamente em instantes.'
  } finally {
    loading.value = false
  }
}

onMounted((): void => {
  void loadDashboard()
})
</script>

<template>
  <div class="flex w-full flex-col gap-8">
    <DashboardPageHeader
      title="Painel de Competências"
      :greeting="greetingWithName"
      subtitle="Visão consolidada das validações contábeis enviadas ao Audita MSC."
      :last-updated="lastUpdatedLabel"
    />

    <div
      v-if="municipalityLabel"
      class="inline-flex w-fit items-center gap-2 rounded-lg border border-slate-200/80 bg-white px-3 py-2 text-sm shadow-sm dark:border-slate-800 dark:bg-slate-900/60"
    >
      <svg
        class="h-4 w-4 text-slate-400"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.75"
        aria-hidden="true"
      >
        <path d="M3 21h18" />
        <path d="M5 21V7l8-4v18" />
        <path d="M19 21V11l-6-4" />
      </svg>
      <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Município</span>
      <span class="font-medium text-slate-800 dark:text-slate-200">{{ municipalityLabel }}</span>
    </div>

    <p
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400"
    >
      {{ errorMessage }}
    </p>

    <section class="grid gap-4 sm:grid-cols-3">
      <KpiCard
        label="Competências Analisadas"
        :value="summary.total_competencias"
        hint="Envios concluídos registrados"
        :loading="loading"
        accent="primary"
      />
      <KpiCard
        label="Média de Inconsistências"
        :value="formatAverage(summary.media_inconsistencias_mes)"
        hint="Erros + alertas por competência"
        :loading="loading"
        accent="warning"
      />
      <KpiCard
        label="Taxa de Conformidade"
        :value="formatPercent(summary.taxa_conformidade)"
        hint="Linhas sem erro crítico processadas"
        :loading="loading"
        accent="success"
      />
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-header flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h3 class="text-base font-semibold text-slate-900 dark:text-white">
            Tendência de Erros vs Alertas
          </h3>
          <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            Evolução mensal da qualidade dos dados contábeis enviados.
          </p>
        </div>
        <div class="flex items-center gap-4 text-xs font-medium text-slate-500 dark:text-slate-400">
          <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm bg-red-500" />
            Erros críticos
          </span>
          <span class="inline-flex items-center gap-1.5">
            <span class="h-2.5 w-2.5 rounded-sm bg-amber-400" />
            Alertas
          </span>
        </div>
      </div>

      <div
        v-if="!loading && trend.length === 0"
        class="flex flex-col items-center justify-center gap-3 px-6 py-14 text-center"
      >
        <svg
          class="h-16 w-16 text-slate-200 dark:text-slate-700"
          viewBox="0 0 64 64"
          fill="none"
          aria-hidden="true"
        >
          <rect x="8" y="36" width="8" height="20" rx="2" fill="currentColor" />
          <rect x="22" y="28" width="8" height="28" rx="2" fill="currentColor" />
          <rect x="36" y="20" width="8" height="36" rx="2" fill="currentColor" />
          <rect x="50" y="32" width="8" height="24" rx="2" fill="currentColor" />
          <path d="M8 12h48" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <p class="text-sm text-slate-500 dark:text-slate-400">
          O gráfico será exibido após o primeiro envio de competência.
        </p>
      </div>

      <div v-else-if="!loading" class="overflow-x-auto p-5 pt-2">
        <div class="flex min-w-[520px] items-end gap-3 px-1 pb-2" style="height: 220px">
          <div
            v-for="point in trend"
            :key="point.periodo"
            class="flex flex-1 flex-col items-center gap-2"
          >
            <div class="flex h-44 w-full items-end justify-center gap-1">
              <div
                class="w-5 rounded-t-md bg-red-500 transition-all duration-300"
                :style="{ height: barHeight(point.total_errors) }"
                :title="`${point.total_errors} erros`"
              />
              <div
                class="w-5 rounded-t-md bg-amber-400 transition-all duration-300"
                :style="{ height: barHeight(point.total_alerts) }"
                :title="`${point.total_alerts} alertas`"
              />
            </div>
            <span class="text-center text-[11px] font-medium text-slate-500 dark:text-slate-400">
              {{ formatPeriodoCurto(point.periodo) }}
            </span>
          </div>
        </div>
      </div>

      <div v-else class="m-5 h-52 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800" />
    </section>

    <section class="dashboard-panel">
      <div class="dashboard-panel-header">
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">
          Histórico de Competências
        </h3>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
          Acompanhamento detalhado de cada período validado.
        </p>
      </div>

      <div v-if="loading" class="p-5">
        <div class="h-48 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800" />
      </div>

      <div
        v-else-if="!hasUploads"
        class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center"
      >
        <svg
          class="h-20 w-20 text-slate-200 dark:text-slate-700"
          viewBox="0 0 80 80"
          fill="none"
          aria-hidden="true"
        >
          <rect x="16" y="12" width="48" height="56" rx="6" stroke="currentColor" stroke-width="2" />
          <path d="M28 28h24M28 38h18M28 48h22" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          <circle cx="56" cy="56" r="12" fill="white" stroke="currentColor" stroke-width="2" />
          <path d="M52 56l3 3 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <div class="max-w-sm">
          <h4 class="text-base font-semibold text-slate-800 dark:text-slate-200">
            Nenhuma competência enviada ainda
          </h4>
          <p class="mt-2 text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            Importe sua primeira planilha MSC para iniciar a auditoria contábil e
            acompanhar os indicadores aqui.
          </p>
        </div>
        <RouterLink
          to="/msc/import"
          class="inline-flex items-center justify-center rounded-lg bg-aura-primary px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-aura-navy dark:bg-aura-sky dark:text-slate-950 dark:hover:bg-aura-cyan"
        >
          Importar MSC
        </RouterLink>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
          <thead class="bg-slate-50/80 dark:bg-slate-900/40">
            <tr>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Competência
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Tipo da MSC
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Erros Críticos
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Alertas
              </th>
              <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                Status Final
              </th>
              <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                Ações
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-800 dark:bg-transparent">
            <tr
              v-for="upload in uploads"
              :key="upload.id"
              class="transition-colors hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
            >
              <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900 dark:text-slate-100">
                {{ formatPeriodo(upload.periodo) }}
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-slate-600 dark:text-slate-400">
                {{ MSC_TIPO_LABELS[upload.tipo_msc] }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex min-w-8 items-center justify-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/15 dark:bg-red-950/40 dark:text-red-400"
                >
                  {{ upload.total_errors }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex min-w-8 items-center justify-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/15 dark:bg-amber-950/40 dark:text-amber-400"
                >
                  {{ upload.total_alerts }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                  :class="statusClasses[resolveFinalStatus(upload.total_errors, upload.total_alerts)]"
                >
                  {{ statusLabels[resolveFinalStatus(upload.total_errors, upload.total_alerts)] }}
                </span>
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-right">
                <div class="inline-flex items-center gap-2">
                  <RouterLink
                    :to="{ name: 'msc-upload-detail', params: { id: upload.id } }"
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-aura-primary/30 hover:text-aura-primary dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-aura-sky/30 dark:hover:text-aura-sky"
                  >
                    Visualizar
                  </RouterLink>
                  <RouterLink
                    v-if="canReuploadUpload(upload.status)"
                    :to="{
                      name: 'msc-import',
                      query: { periodo: upload.periodo, tipo_msc: upload.tipo_msc },
                    }"
                    class="inline-flex items-center rounded-lg border border-aura-primary/20 bg-aura-primary/5 px-3 py-1.5 text-xs font-semibold text-aura-primary transition hover:bg-aura-primary/10 dark:border-aura-sky/20 dark:bg-aura-sky/10 dark:text-aura-sky"
                  >
                    Reenviar
                  </RouterLink>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>

<style scoped>
a {
  text-decoration: none;
}

a:hover {
  background-color: transparent;
}
</style>
