<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import MscValidationErrors from '@/components/MscValidationErrors.vue'
import { fetchMscUpload } from '@/services/mscApi'
import { useAuthStore } from '@/stores/auth'
import type { MunicipioEnte, MscValidationError } from '@/types/msc'
import {
  MSC_TIPO_LABELS,
  formatPeriodo,
  normalizeValidationErrors,
  resolveFinalStatus,
} from '@/types/msc'

const auth = useAuthStore()
const route = useRoute()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const filename = ref('')
const periodo = ref('')
const tipoMsc = ref('')
const totalErrors = ref(0)
const totalAlerts = ref(0)
const ibgeCode = ref<string | null>(null)
const ente = ref<MunicipioEnte | undefined>(undefined)
const validationErrors = ref<MscValidationError[]>([])

const uploadId = computed((): string => String(route.params.id ?? ''))

const statusLabels = {
  sucesso: 'Sucesso',
  atencao: 'Atenção',
  inconsistente: 'Inconsistente',
} as const

const statusClasses = {
  sucesso: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  atencao: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  inconsistente: 'bg-red-50 text-red-700 ring-red-600/20',
} as const

const finalStatus = computed(() => resolveFinalStatus(totalErrors.value, totalAlerts.value))

async function loadUploadDetail(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    const payload = await fetchMscUpload(uploadId.value, token)
    filename.value = payload.upload.filename
    periodo.value = payload.upload.periodo
    tipoMsc.value = payload.upload.tipo_msc
    totalErrors.value = payload.upload.total_errors
    totalAlerts.value = payload.upload.total_alerts
    ibgeCode.value = payload.upload.ibge_code
    ente.value = payload.upload.ente
    validationErrors.value = normalizeValidationErrors(payload.errors)
  } catch {
    errorMessage.value = 'Não foi possível carregar os detalhes desta competência.'
  } finally {
    loading.value = false
  }
}

onMounted((): void => {
  void loadUploadDetail()
})
</script>

<template>
  <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <RouterLink
          to="/"
          class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700"
        >
          ← Voltar ao Dashboard
        </RouterLink>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900">
          Detalhes da Competência
        </h2>
        <p v-if="!loading && periodo" class="mt-1 text-sm text-slate-500">
          {{ formatPeriodo(periodo) }}
          ·
          {{ MSC_TIPO_LABELS[tipoMsc as keyof typeof MSC_TIPO_LABELS] ?? tipoMsc }}
        </p>
      </div>

      <span
        v-if="!loading"
        class="inline-flex w-fit items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset"
        :class="statusClasses[finalStatus]"
      >
        {{ statusLabels[finalStatus] }}
      </span>
    </header>

    <p
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
    >
      {{ errorMessage }}
    </p>

    <div v-if="loading" class="h-64 animate-pulse rounded-xl bg-white shadow-sm" />

    <template v-else-if="!errorMessage">
      <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <dl class="grid gap-4 sm:grid-cols-3">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Arquivo</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ filename }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Competência</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ formatPeriodo(periodo) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Inconsistências</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">
              {{ validationErrors.length }} registro(s)
            </dd>
          </div>
        </dl>
      </section>

      <MscValidationErrors
        v-if="validationErrors.length > 0"
        :errors="validationErrors"
        :analyzed-filename="filename"
        :periodo="periodo"
        :ibge-code="ibgeCode"
        :ente="ente"
      />

      <section
        v-else
        class="flex flex-col items-center justify-center gap-3 rounded-xl border border-slate-200/80 bg-white px-6 py-14 text-center shadow-sm"
      >
        <svg
          class="h-16 w-16 text-emerald-200"
          viewBox="0 0 64 64"
          fill="none"
          aria-hidden="true"
        >
          <circle cx="32" cy="32" r="24" stroke="currentColor" stroke-width="2" />
          <path d="M22 32l7 7 13-14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
        <h3 class="text-base font-semibold text-slate-800">Competência conforme</h3>
        <p class="max-w-md text-sm text-slate-500">
          Nenhuma inconsistência foi registrada neste envio. A matriz está apta para consolidação.
        </p>
      </section>
    </template>
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
