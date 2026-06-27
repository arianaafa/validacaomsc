<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useMscPdfReport } from '@/composables/useMscPdfReport'
import type { MunicipioEnte, MscValidationError, MscValidationErrorTipo } from '@/types/msc'

const PAGE_SIZE = 10

const props = defineProps<{
  errors: MscValidationError[]
  analyzedFilename: string
  periodo: string
  ibgeCode?: string | null
  ente?: MunicipioEnte
}>()

const { exportToPdf } = useMscPdfReport()

const isExportingPdf = ref(false)

const searchQuery = ref('')
const currentPage = ref(1)

const filteredErrors = computed((): MscValidationError[] => {
  const query = searchQuery.value.trim().toLowerCase()

  if (query === '') {
    return props.errors
  }

  return props.errors.filter((error: MscValidationError): boolean => {
    const conta = error.conta_contabil?.toLowerCase() ?? ''
    const regra = error.codigo_regra.toLowerCase()

    return conta.includes(query) || regra.includes(query)
  })
})

const totalPages = computed((): number => {
  if (filteredErrors.value.length === 0) {
    return 1
  }

  return Math.ceil(filteredErrors.value.length / PAGE_SIZE)
})

const paginatedErrors = computed((): MscValidationError[] => {
  const start = (currentPage.value - 1) * PAGE_SIZE

  return filteredErrors.value.slice(start, start + PAGE_SIZE)
})

const paginationLabel = computed((): string => {
  const total = filteredErrors.value.length

  if (total === 0) {
    return 'Nenhum erro encontrado'
  }

  const start = (currentPage.value - 1) * PAGE_SIZE + 1
  const end = Math.min(currentPage.value * PAGE_SIZE, total)

  return `Exibindo ${start}–${end} de ${total} inconsistência${total === 1 ? '' : 's'}`
})

const canGoPrevious = computed((): boolean => currentPage.value > 1)

const canGoNext = computed((): boolean => currentPage.value < totalPages.value)

watch(searchQuery, (): void => {
  currentPage.value = 1
})

watch(
  (): number => filteredErrors.value.length,
  (): void => {
    if (currentPage.value > totalPages.value) {
      currentPage.value = totalPages.value
    }
  },
)

function formatLinha(linha: number | null): string {
  return linha === null ? '—' : String(linha)
}

function formatConta(contaContabil: string | null): string {
  return contaContabil ?? '—'
}

function formatTipoLabel(tipo: MscValidationErrorTipo): string {
  return tipo === 'erro' ? 'Erro' : 'Alerta'
}

function getTipoBadgeClasses(tipo: MscValidationErrorTipo): string {
  if (tipo === 'erro') {
    return 'bg-red-100 text-red-700 ring-red-200'
  }

  return 'bg-amber-100 text-amber-800 ring-amber-200'
}

function goToPreviousPage(): void {
  if (!canGoPrevious.value) {
    return
  }

  currentPage.value -= 1
}

function goToNextPage(): void {
  if (!canGoNext.value) {
    return
  }

  currentPage.value += 1
}

async function handleDownloadPdf(): Promise<void> {
  if (isExportingPdf.value) {
    return
  }

  isExportingPdf.value = true

  try {
    await exportToPdf(props.analyzedFilename, props.errors, {
      periodo: props.periodo,
      ibgeCode: props.ibgeCode,
      ente: props.ente,
    })
  } finally {
    isExportingPdf.value = false
  }
}
</script>

<template>
  <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <header class="border-b border-slate-100 bg-slate-50 px-4 py-4 sm:px-6">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h2 class="text-base font-semibold text-slate-900">Inconsistências da Validação</h2>
          <p class="mt-1 text-sm text-slate-500">
            {{ errors.length }} registro{{ errors.length === 1 ? '' : 's' }} no total
          </p>
        </div>

        <div class="flex w-full flex-col gap-2 sm:max-w-md sm:flex-row sm:items-center">
          <label class="grid min-w-0 flex-1 gap-1.5">
            <span class="sr-only">Buscar por conta ou regra</span>
            <input
              v-model="searchQuery"
              type="search"
              placeholder="Buscar por conta ou regra..."
              class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
            />
          </label>

          <button
            type="button"
            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-600 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isExportingPdf"
            @click="handleDownloadPdf"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              class="h-4 w-4"
              aria-hidden="true"
            >
              <path
                d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 1 0-1.09-1.03l-2.955 3.129V2.75Z"
              />
              <path
                d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"
              />
            </svg>
            {{ isExportingPdf ? 'Gerando PDF...' : 'Baixar PDF' }}
          </button>
        </div>
      </div>
    </header>

    <div class="overflow-x-auto">
      <table class="min-w-full text-left text-sm">
        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Linha</th>
            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Conta Contábil</th>
            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Regra</th>
            <th scope="col" class="px-4 py-3 font-semibold sm:px-6">Tipo</th>
            <th scope="col" class="min-w-64 px-4 py-3 font-semibold sm:px-6">Descrição</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100">
          <tr v-if="paginatedErrors.length === 0">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500 sm:px-6">
              Nenhuma inconsistência corresponde à busca informada.
            </td>
          </tr>

          <tr
            v-for="error in paginatedErrors"
            :key="error.id"
            class="transition-colors hover:bg-slate-50/80"
          >
            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-900 sm:px-6">
              {{ formatLinha(error.linha) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-700 sm:px-6">
              {{ formatConta(error.conta_contabil) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-800 sm:px-6">
              {{ error.codigo_regra }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 sm:px-6">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                :class="getTipoBadgeClasses(error.tipo)"
              >
                {{ formatTipoLabel(error.tipo) }}
              </span>
            </td>
            <td class="px-4 py-3 text-slate-600 sm:px-6">
              {{ error.descricao }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <footer
      v-if="filteredErrors.length > 0"
      class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6"
    >
      <p class="text-sm text-slate-600">
        {{ paginationLabel }}
      </p>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!canGoPrevious"
          @click="goToPreviousPage"
        >
          Anterior
        </button>

        <span class="min-w-24 text-center text-sm font-medium text-slate-600">
          Página {{ currentPage }} de {{ totalPages }}
        </span>

        <button
          type="button"
          class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="!canGoNext"
          @click="goToNextPage"
        >
          Próxima
        </button>
      </div>
    </footer>
  </section>
</template>
