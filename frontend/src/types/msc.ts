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
  created_at: string | null
}

export interface MscUploadResponse {
  upload: MscUploadRecord
  errors: MscValidationErrorApi[]
}

export interface MscTipoOption {
  value: MscTipoValue
  label: string
}
