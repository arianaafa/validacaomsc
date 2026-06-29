export type MscRuleValidationType = 'linha' | 'global' | 'agrupamento'

export interface MscRule {
  id: string
  code: string
  name: string
  validation_type: MscRuleValidationType
  target_group: string
  objective: string
  error_message: string
  is_implemented: boolean
  created_at: string | null
  updated_at: string | null
}

export interface MscRulesListParams {
  search?: string
  validation_type?: MscRuleValidationType | ''
  page?: number
  per_page?: number
}

export interface MscRulesPaginatedResponse {
  current_page: number
  data: MscRule[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: Array<{ url: string | null; label: string; active: boolean }>
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export const MSC_RULE_VALIDATION_TYPE_LABELS: Record<MscRuleValidationType, string> = {
  linha: 'Por Linha',
  global: 'Global',
  agrupamento: 'Agrupamento',
}

export const MSC_RULE_VALIDATION_TYPE_CLASSES: Record<MscRuleValidationType, string> = {
  linha: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  global: 'bg-violet-50 text-violet-700 ring-violet-600/20',
  agrupamento: 'bg-teal-50 text-teal-700 ring-teal-600/20',
}

export const MSC_RULE_VALIDATION_TYPE_OPTIONS: Array<{
  value: MscRuleValidationType | ''
  label: string
}> = [
  { value: '', label: 'Todos os tipos' },
  { value: 'linha', label: 'Por Linha' },
  { value: 'global', label: 'Global' },
  { value: 'agrupamento', label: 'Agrupamento' },
]
