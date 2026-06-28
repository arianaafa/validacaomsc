<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
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
  buildMunicipioOptionsFromUploads,
  canReuploadUpload,
  formatPeriodo,
  formatPeriodoCurto,
  resolveFinalStatus,
} from '@/types/msc'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const uploads = ref<MscUploadRecord[]>([])
const selectedIbgeCode = ref('')

const municipioOptions = computed(() => buildMunicipioOptionsFromUploads(uploads.value))

const filteredUploads = computed((): MscUploadRecord[] => {
  if (selectedIbgeCode.value === '') {
    return uploads.value
  }

  return uploads.value.filter((upload) => upload.ibge_code === selectedIbgeCode.value)
})

const summary = computed(() => buildDashboardSummary(filteredUploads.value))

const trend = computed((): MscDashboardTrendPoint[] =>
  buildDashboardTrend(filteredUploads.value),
)

const hasUploads = computed((): boolean => uploads.value.length > 0)

const hasFilteredUploads = computed((): boolean => filteredUploads.value.length > 0)

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
  sucesso: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  atencao: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  inconsistente: 'bg-red-50 text-red-700 ring-red-600/20',
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

function syncSelectedIbgeCode(): void {
  const options = municipioOptions.value

  if (options.length === 0) {
    selectedIbgeCode.value = ''

    return
  }

  const stillValid = options.some(
    (option) => option.ibge_code === selectedIbgeCode.value,
  )

  if (!stillValid) {
    selectedIbgeCode.value = options[0]?.ibge_code ?? ''
  }
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
    syncSelectedIbgeCode()
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
  <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div class="flex flex-col gap-1">
        <p class="text-sm font-medium text-indigo-600">Audita MSC</p>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
          Painel de Competências
        </h2>
        <p class="text-sm text-slate-500">
          Visão consolidada das validações contábeis enviadas ao Audita MSC.
        </p>
      </div>

      <div
        v-if="municipioOptions.length > 0"
        class="flex shrink-0 flex-col gap-1.5 sm:min-w-[220px] sm:items-end"
      >
        <label
          for="municipio-select"
          class="text-xs font-semibold uppercase tracking-wide text-slate-500"
        >
          Município
        </label>
        <select
          id="municipio-select"
          v-model="selectedIbgeCode"
          class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200 sm:w-auto sm:min-w-[220px]"
        >
          <option
            v-for="municipio in municipioOptions"
            :key="municipio.ibge_code"
            :value="municipio.ibge_code"
          >
            {{ municipio.label }}
          </option>
        </select>
      </div>
    </header>

    <p
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
    >
      {{ errorMessage }}
    </p>

    <section v-if="loading" class="grid gap-4 sm:grid-cols-3">
      <div
        v-for="index in 3"
        :key="index"
        class="h-32 animate-pulse rounded-xl bg-white shadow-sm"
      />
    </section>

    <section v-else class="grid gap-4 sm:grid-cols-3">
      <article class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Total de Competências Analisadas</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
          {{ summary.total_competencias }}
        </p>
        <p class="mt-1 text-xs text-slate-400">Envios concluídos registrados</p>
      </article>

      <article class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Média de Inconsistências por Mês</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-slate-900">
          {{ formatAverage(summary.media_inconsistencias_mes) }}
        </p>
        <p class="mt-1 text-xs text-slate-400">Erros + alertas por competência</p>
      </article>

      <article class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <p class="text-sm font-medium text-slate-500">Taxa de Conformidade</p>
        <p class="mt-2 text-3xl font-bold tabular-nums text-indigo-600">
          {{ formatPercent(summary.taxa_conformidade) }}
        </p>
        <p class="mt-1 text-xs text-slate-400">Linhas sem erro crítico processadas</p>
      </article>
    </section>

    <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
      <div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <h3 class="text-lg font-semibold text-slate-900">Tendência de Erros vs Alertas</h3>
          <p class="text-sm text-slate-500">
            Evolução mensal da qualidade dos dados contábeis enviados.
          </p>
        </div>
        <div class="mt-3 flex items-center gap-4 text-xs font-medium text-slate-500 sm:mt-0">
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
        class="flex flex-col items-center justify-center gap-3 py-10 text-center"
      >
        <svg
          class="h-16 w-16 text-slate-200"
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
        <p class="text-sm text-slate-500">
          O gráfico será exibido após o primeiro envio de competência.
        </p>
      </div>

      <div v-else-if="!loading" class="overflow-x-auto">
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
            <span class="text-center text-[11px] font-medium text-slate-500">
              {{ formatPeriodoCurto(point.periodo) }}
            </span>
          </div>
        </div>
      </div>

      <div
        v-else
        class="h-52 animate-pulse rounded-lg bg-slate-100"
      />
    </section>

    <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="text-lg font-semibold text-slate-900">Histórico de Competências</h3>
        <p class="text-sm text-slate-500">
          Acompanhamento detalhado de cada período validado.
        </p>
      </div>

      <div v-if="loading" class="p-5">
        <div class="h-48 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div
        v-else-if="!hasUploads"
        class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center"
      >
        <svg
          class="h-20 w-20 text-slate-200"
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
          <h4 class="text-base font-semibold text-slate-800">Nenhuma competência enviada ainda</h4>
          <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Importe sua primeira planilha MSC para iniciar a auditoria contábil e
            acompanhar os indicadores aqui.
          </p>
        </div>
        <RouterLink
          to="/msc/import"
          class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
        >
          Importar MSC
        </RouterLink>
      </div>

      <div
        v-else-if="municipioOptions.length > 0 && !hasFilteredUploads"
        class="flex flex-col items-center justify-center gap-4 px-6 py-16 text-center"
      >
        <svg
          class="h-20 w-20 text-slate-200"
          viewBox="0 0 80 80"
          fill="none"
          aria-hidden="true"
        >
          <rect x="16" y="12" width="48" height="56" rx="6" stroke="currentColor" stroke-width="2" />
          <path d="M28 28h24M28 38h18M28 48h22" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <div class="max-w-sm">
          <h4 class="text-base font-semibold text-slate-800">
            Nenhuma competência para este município
          </h4>
          <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Selecione outro município ou importe uma planilha MSC vinculada a este ente.
          </p>
        </div>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Competência</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Tipo da MSC</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Erros Críticos</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Alertas</th>
              <th class="px-5 py-3 text-left font-semibold text-slate-600">Status Final</th>
              <th class="px-5 py-3 text-right font-semibold text-slate-600">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="upload in filteredUploads"
              :key="upload.id"
              class="transition-colors hover:bg-slate-50/60"
            >
              <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-900">
                {{ formatPeriodo(upload.periodo) }}
              </td>
              <td class="whitespace-nowrap px-5 py-4 text-slate-600">
                {{ MSC_TIPO_LABELS[upload.tipo_msc] }}
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex min-w-8 items-center justify-center rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-600/15"
                >
                  {{ upload.total_errors }}
                </span>
              </td>
              <td class="px-5 py-4">
                <span
                  class="inline-flex min-w-8 items-center justify-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/15"
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
                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700"
                  >
                    Visualizar Detalhes
                  </RouterLink>
                  <RouterLink
                    v-if="canReuploadUpload(upload.status)"
                    :to="{
                      name: 'msc-import',
                      query: { periodo: upload.periodo, tipo_msc: upload.tipo_msc },
                    }"
                    class="inline-flex items-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100"
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
