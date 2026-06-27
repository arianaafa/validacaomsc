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

export interface MscUploadRecord {
  id: string
  filename: string
  hash: string
  status: MscUploadStatus
  periodo: string
  tipo_msc: MscTipoValue
  total_lines: number
  total_errors: number
  total_alerts: number
  created_at: string | null
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
