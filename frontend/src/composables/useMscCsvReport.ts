import type { MscValidationError, MscValidationErrorTipo } from '@/types/msc'

const CSV_DELIMITER = ';'

export interface MscCsvReportOptions {
  outputFilename?: string
}

function formatLinha(linha: number | null): string {
  return linha === null ? '' : String(linha)
}

function formatConta(contaContabil: string | null): string {
  return contaContabil ?? ''
}

function formatTipoLabel(tipo: MscValidationErrorTipo): string {
  return tipo === 'erro' ? 'Erro' : 'Alerta'
}

function escapeCsvField(value: string): string {
  if (/[;"\n\r]/.test(value)) {
    return `"${value.replace(/"/g, '""')}"`
  }

  return value
}

function buildCsvRow(values: string[]): string {
  return values.map(escapeCsvField).join(CSV_DELIMITER)
}

function sanitizeOutputFilename(filename: string): string {
  const baseName = filename.trim().replace(/\.(csv|pdf|zip)$/i, '')
  const sanitized = baseName.replace(/[^\w.-]+/g, '_').replace(/_+/g, '_')

  if (sanitized === '') {
    return 'validamsc-inconsistencias.csv'
  }

  return `${sanitized}-inconsistencias.csv`
}

function resolveOutputFilename(
  analyzedFilename: string,
  outputFilename?: string,
): string {
  const customName = outputFilename?.trim()

  if (customName === undefined || customName === '') {
    return sanitizeOutputFilename(analyzedFilename)
  }

  return customName.endsWith('.csv') ? customName : `${customName}.csv`
}

function buildCsvContent(errors: MscValidationError[]): string {
  const lines = [
    buildCsvRow(['Linha', 'Conta', 'Regra', 'Tipo', 'Descrição']),
    ...errors.map((error: MscValidationError): string =>
      buildCsvRow([
        formatLinha(error.linha),
        formatConta(error.conta_contabil),
        error.codigo_regra,
        formatTipoLabel(error.tipo),
        error.descricao,
      ]),
    ),
  ]

  return `\uFEFF${lines.join('\r\n')}`
}

function triggerDownload(filename: string, content: string): void {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const anchor = document.createElement('a')

  anchor.href = url
  anchor.download = filename
  anchor.style.display = 'none'
  document.body.appendChild(anchor)
  anchor.click()
  document.body.removeChild(anchor)
  URL.revokeObjectURL(url)
}

export function useMscCsvReport(): {
  exportToCsv: (
    filename: string,
    errors: MscValidationError[],
    options?: MscCsvReportOptions,
  ) => void
} {
  function exportToCsv(
    filename: string,
    errors: MscValidationError[],
    options?: MscCsvReportOptions,
  ): void {
    const analyzedFilename = filename.trim() === '' ? 'validamsc' : filename.trim()
    const outputFilename = resolveOutputFilename(analyzedFilename, options?.outputFilename)

    triggerDownload(outputFilename, buildCsvContent(errors))
  }

  return { exportToCsv }
}
