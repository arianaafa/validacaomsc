<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import MscValidationErrors from '@/components/MscValidationErrors.vue'
import { uploadMscSpreadsheet } from '@/services/mscApi'
import { ApiError } from '@/services/httpClient'
import { useAuthStore } from '@/stores/auth'
import type {
  MscTipoOption,
  MscTipoValue,
  MscUploadPayload,
  MscValidationError,
  MunicipioEnte,
} from '@/types/msc'
import { normalizeValidationErrors } from '@/types/msc'

const auth = useAuthStore()
const route = useRoute()

const periodo = ref('')
const tipoMsc = ref<MscTipoValue>('agregada')
const selectedFile = ref<File | null>(null)
const isDragging = ref(false)
const isSubmitting = ref(false)
const alertMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const validationErrors = ref<MscValidationError[]>([])
const lastAnalyzedFilename = ref('')
const uploadIbgeCode = ref<string | null>(null)
const uploadEnte = ref<MunicipioEnte | undefined>(undefined)

const tipoMscOptions: MscTipoOption[] = [
  { value: 'agregada', label: 'Agregada' },
  { value: 'estendida', label: 'Estendida' },
]

const isReuploadPrefill = computed((): boolean => {
  return typeof route.query.periodo === 'string' && typeof route.query.tipo_msc === 'string'
})

onMounted((): void => {
  const queryPeriodo = route.query.periodo
  const queryTipo = route.query.tipo_msc

  if (typeof queryPeriodo === 'string') {
    periodo.value = queryPeriodo
  }

  if (queryTipo === 'agregada' || queryTipo === 'estendida') {
    tipoMsc.value = queryTipo
  }
})

const canSubmit = computed(
  () =>
    periodo.value !== '' &&
    selectedFile.value !== null &&
    !isSubmitting.value,
)

function isAllowedImportFile(file: File): boolean {
  const lowerName = file.name.toLowerCase()

  return lowerName.endsWith('.csv') || lowerName.endsWith('.zip')
}

function setSelectedFile(file: File | null): void {
  if (file === null) {
    selectedFile.value = null
    return
  }

  if (!isAllowedImportFile(file)) {
    alertMessage.value = 'Apenas arquivos .csv ou .zip são permitidos.'
    selectedFile.value = null
    return
  }

  alertMessage.value = null
  successMessage.value = null
  validationErrors.value = []
  lastAnalyzedFilename.value = ''
  selectedFile.value = file
}

function handleDragOver(event: DragEvent): void {
  event.preventDefault()
  isDragging.value = true
}

function handleDragLeave(event: DragEvent): void {
  event.preventDefault()
  isDragging.value = false
}

function handleDrop(event: DragEvent): void {
  event.preventDefault()
  isDragging.value = false

  const file = event.dataTransfer?.files.item(0) ?? null

  if (file === null) {
    return
  }

  if (!isAllowedImportFile(file)) {
    alertMessage.value = 'Apenas arquivos .csv ou .zip são permitidos.'
    return
  }

  setSelectedFile(file)
}

function handleFileInputChange(event: Event): void {
  const input = event.target as HTMLInputElement
  const file = input.files?.item(0) ?? null
  setSelectedFile(file)
  input.value = ''
}

function clearFeedback(): void {
  alertMessage.value = null
  successMessage.value = null
  validationErrors.value = []
  lastAnalyzedFilename.value = ''
  uploadIbgeCode.value = null
  uploadEnte.value = undefined
}

function buildPayload(): MscUploadPayload | null {
  if (selectedFile.value === null || periodo.value === '') {
    return null
  }

  return {
    file: selectedFile.value,
    periodo: periodo.value,
    tipo_msc: tipoMsc.value,
  }
}

function formatApiErrors(errors: Record<string, string[]>): string {
  return Object.values(errors)
    .flat()
    .join(' ')
}

async function handleSubmit(): Promise<void> {
  clearFeedback()

  const payload = buildPayload()

  if (payload === null) {
    alertMessage.value = 'Selecione o período e um arquivo .csv ou .zip antes de enviar.'
    return
  }

  if (auth.accessToken === null) {
    alertMessage.value = 'Sessão expirada. Faça login novamente.'
    return
  }

  isSubmitting.value = true

  try {
    const response = await uploadMscSpreadsheet(payload, auth.accessToken)

    if (response.errors.length > 0) {
      validationErrors.value = normalizeValidationErrors(response.errors)
      lastAnalyzedFilename.value = response.upload.filename
      uploadIbgeCode.value = response.upload.ibge_code
      uploadEnte.value = response.upload.ente

      if (response.upload.status === 'falha') {
        alertMessage.value =
          'Ocorreu uma falha no processamento do arquivo. Consulte os detalhes abaixo.'
      } else {
        alertMessage.value =
          'A planilha foi recebida, mas a validação encontrou inconsistências.'
      }

      return
    }

    if (response.upload.status === 'sucesso') {
      successMessage.value = `Planilha "${response.upload.filename}" validada com sucesso.`
      selectedFile.value = null
      return
    }

    if (response.upload.status === 'falha') {
      alertMessage.value =
        'O processamento falhou sem detalhes registrados. Verifique o formato do CSV (delimitador, codificação UTF-8) e tente novamente.'
      return
    }

    if (response.upload.status === 'erro_validacao') {
      alertMessage.value =
        'A validação reprovou o arquivo, mas nenhum detalhe foi retornado pelo servidor.'
      return
    }

    alertMessage.value = `Upload finalizado com status: ${response.upload.status}.`
  } catch (error) {
    if (error instanceof ApiError) {
      const fieldErrors = formatApiErrors(error.errors)
      alertMessage.value = fieldErrors || error.message
      return
    }

    alertMessage.value =
      error instanceof Error ? error.message : 'Falha ao enviar a planilha.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="mx-auto w-full max-w-3xl px-4 py-8">
    <header class="mb-8">
      <p class="mb-2 text-sm font-semibold uppercase tracking-wider text-blue-600">
        MSC
      </p>
      <h1 class="text-3xl font-bold text-slate-900">Importar Planilha</h1>
      <p class="mt-2 text-slate-500">
        Envie o arquivo CSV ou ZIP (exportação SICONFI) da Matriz de Saldos Contábeis para validação estrutural.
      </p>
    </header>

    <form class="grid gap-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="handleSubmit">
      <label class="grid gap-2 text-sm font-semibold text-slate-700">
        Período
        <input
          v-model="periodo"
          type="month"
          required
          class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
        />
      </label>

      <label class="grid gap-2 text-sm font-semibold text-slate-700">
        Tipo da MSC
        <select
          v-model="tipoMsc"
          required
          class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200"
        >
          <option v-for="option in tipoMscOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </label>

      <div class="grid gap-2">
        <span class="text-sm font-semibold text-slate-700">Arquivo CSV ou ZIP</span>

        <div
          class="dropzone"
          :class="{ 'dropzone--active': isDragging, 'dropzone--filled': selectedFile !== null }"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
          @drop="handleDrop"
        >
          <input
            id="msc-file-input"
            type="file"
            accept=".csv,.zip,text/csv,application/zip"
            class="sr-only"
            @change="handleFileInputChange"
          />

          <label for="msc-file-input" class="dropzone__label">
            <span class="dropzone__title">
              Arraste o arquivo .csv ou .zip aqui ou clique para selecionar
            </span>
            <span v-if="selectedFile" class="dropzone__filename">
              {{ selectedFile.name }}
            </span>
          </label>
        </div>
      </div>

      <p v-if="alertMessage" class="rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-600">
        {{ alertMessage }}
      </p>

      <p
        v-if="successMessage"
        class="rounded-lg bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700"
      >
        {{ successMessage }}
      </p>

      <button
        type="submit"
        :disabled="!canSubmit"
        class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-3.5 text-base font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70"
      >
        <span
          v-if="isSubmitting"
          class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
          aria-hidden="true"
        />
        {{
          isSubmitting
            ? 'Processando e Validando Estrutura...'
            : 'Enviar Planilha'
        }}
      </button>
    </form>

    <MscValidationErrors
      v-if="validationErrors.length > 0"
      :errors="validationErrors"
      :analyzed-filename="lastAnalyzedFilename"
      :periodo="periodo"
      :ibge-code="uploadIbgeCode"
      :ente="uploadEnte"
      class="mt-6"
    />
  </section>
</template>

<style scoped>
.dropzone {
  border: 2px dashed #cbd5e1;
  border-radius: 0.75rem;
  background: #f8fafc;
  transition: border-color 0.2s ease, background-color 0.2s ease;
}

.dropzone--active {
  border-color: #2563eb;
  background: #eff6ff;
}

.dropzone--filled {
  border-color: #16a34a;
  background: #f0fdf4;
}

.dropzone__label {
  display: grid;
  gap: 0.5rem;
  padding: 2rem 1rem;
  cursor: pointer;
  text-align: center;
}

.dropzone__title {
  color: #475569;
  font-size: 0.95rem;
}

.dropzone__filename {
  color: #0f172a;
  font-weight: 600;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
