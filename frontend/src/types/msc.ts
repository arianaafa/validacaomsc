export type MscTipoValue = 'agregada' | 'estendida'

export type MscUploadStatus = 'processando' | 'sucesso' | 'erro_validacao' | 'falha'

export type MscValidationErrorTipo = 'erro' | 'alerta'

export interface MscUploadPayload {
  file: File
  periodo: string
  tipo_msc: MscTipoValue
}

export interface MscValidationError {
  id: string
  linha: number | null
  conta_contabil: string | null
  tipo: MscValidationErrorTipo
  codigo_regra: string
  descricao: string
}

/** Payload parcial retornado pela API antes da normalização para exibição. */
export interface MscValidationErrorApi {
  linha: number | null
  codigo_regra: string
  descricao: string
  tipo: MscValidationErrorTipo
  conta_contabil?: string | null
}

export function normalizeValidationErrors(
  errors: MscValidationErrorApi[],
): MscValidationError[] {
  return errors.map((error, index): MscValidationError => ({
    id: `${error.codigo_regra}-${error.linha ?? 'na'}-${index}`,
    linha: error.linha,
    conta_contabil: error.conta_contabil ?? null,
    tipo: error.tipo,
    codigo_regra: error.codigo_regra,
    descricao: error.descricao,
  }))
}

export interface MunicipioEnte {
  municipio: string
  uf: string
  estado: string
}

export interface MscUploadRecord {
  id: string
  filename: string
  hash: string
  status: MscUploadStatus
  periodo: string
  tipo_msc: MscTipoValue
  ibge_code: string | null
  ente: MunicipioEnte
  total_lines: number
  total_errors: number
  total_alerts: number
  created_at: string | null
}

export interface MunicipioOption {
  ibge_code: string
  label: string
}

export function buildMunicipioOptionsFromUploads(
  uploads: readonly MscUploadRecord[],
): MunicipioOption[] {
  const options = new Map<string, MunicipioOption>()

  for (const upload of uploads) {
    const code = upload.ibge_code?.trim() ?? ''

    if (code === '') {
      continue
    }

    if (options.has(code)) {
      continue
    }

    const { municipio, uf } = upload.ente
    const label = municipio !== '' && uf !== ''
      ? `${municipio} - ${uf}`
      : `IBGE ${code}`

    options.set(code, { ibge_code: code, label })
  }

  return [...options.values()].sort((left, right) =>
    left.label.localeCompare(right.label, 'pt-BR'),
  )
}

export interface MscDashboardSummary {
  total_competencias: number
  media_inconsistencias_mes: number
  taxa_conformidade: number
}

export interface MscDashboardTrendPoint {
  periodo: string
  total_errors: number
  total_alerts: number
}

export interface MscDashboardResponse {
  summary: MscDashboardSummary
  trend: MscDashboardTrendPoint[]
  uploads: MscUploadRecord[]
}

export function buildDashboardSummary(
  uploads: readonly MscUploadRecord[],
): MscDashboardSummary {
  const totalCompetencias = uploads.length

  if (totalCompetencias === 0) {
    return {
      total_competencias: 0,
      media_inconsistencias_mes: 0,
      taxa_conformidade: 100,
    }
  }

  const monthlyTotals = new Map<string, number[]>()

  for (const upload of uploads) {
    const inconsistencies = upload.total_errors + upload.total_alerts
    const periodUploads = monthlyTotals.get(upload.periodo) ?? []
    periodUploads.push(inconsistencies)
    monthlyTotals.set(upload.periodo, periodUploads)
  }

  const monthlyAverages = [...monthlyTotals.values()].map(
    (values) => values.reduce((sum, value) => sum + value, 0) / values.length,
  )

  const mediaInconsistenciasMes = Math.round(
    (monthlyAverages.reduce((sum, value) => sum + value, 0) / monthlyAverages.length) * 10,
  ) / 10

  const totalLines = uploads.reduce((sum, upload) => sum + upload.total_lines, 0)
  const totalErrors = uploads.reduce((sum, upload) => sum + upload.total_errors, 0)

  const taxaConformidade = totalLines > 0
    ? Math.round(Math.max(0, (1 - totalErrors / totalLines) * 100) * 10) / 10
    : 100

  return {
    total_competencias: totalCompetencias,
    media_inconsistencias_mes: mediaInconsistenciasMes,
    taxa_conformidade: taxaConformidade,
  }
}

export function buildDashboardTrend(
  uploads: readonly MscUploadRecord[],
): MscDashboardTrendPoint[] {
  const grouped = new Map<string, MscDashboardTrendPoint>()

  for (const upload of uploads) {
    const current = grouped.get(upload.periodo) ?? {
      periodo: upload.periodo,
      total_errors: 0,
      total_alerts: 0,
    }

    current.total_errors += upload.total_errors
    current.total_alerts += upload.total_alerts
    grouped.set(upload.periodo, current)
  }

  return [...grouped.values()].sort((left, right) =>
    left.periodo.localeCompare(right.periodo),
  )
}

export type MscFinalStatus = 'sucesso' | 'atencao' | 'inconsistente'

export interface MscTipoOption {
  value: MscTipoValue
  label: string
}

export const MSC_TIPO_LABELS: Record<MscTipoValue, string> = {
  agregada: 'Agregada (MSCC)',
  estendida: 'Estendida (MSCE)',
}

export const MESES_PT: readonly string[] = [
  'Janeiro',
  'Fevereiro',
  'Março',
  'Abril',
  'Maio',
  'Junho',
  'Julho',
  'Agosto',
  'Setembro',
  'Outubro',
  'Novembro',
  'Dezembro',
]

export function formatPeriodo(periodo: string): string {
  const [year, month] = periodo.split('-')
  const monthIndex = Number.parseInt(month ?? '', 10) - 1

  if (!year || monthIndex < 0 || monthIndex > 11) {
    return periodo
  }

  return `${MESES_PT[monthIndex]} / ${year}`
}

export function formatPeriodoCurto(periodo: string): string {
  const [year, month] = periodo.split('-')
  const monthIndex = Number.parseInt(month ?? '', 10) - 1

  if (!year || monthIndex < 0 || monthIndex > 11) {
    return periodo
  }

  return `${MESES_PT[monthIndex]?.slice(0, 3) ?? month}.${year.slice(2)}`
}

export function resolveFinalStatus(
  totalErrors: number,
  totalAlerts: number,
): MscFinalStatus {
  if (totalErrors > 0) {
    return 'inconsistente'
  }

  if (totalAlerts > 0) {
    return 'atencao'
  }

  return 'sucesso'
}

export function canReuploadUpload(status: MscUploadStatus): boolean {
  return status === 'erro_validacao' || status === 'falha'
}

export interface MscUploadResponse {
  upload: MscUploadRecord
  errors: MscValidationErrorApi[]
}
