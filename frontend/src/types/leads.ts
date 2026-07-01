export type LeadRequestStatus = 'pending' | 'trial' | 'approved' | 'failed'

export type LeadRequestRole = 'secretario' | 'contador' | 'auditor' | 'outros'

export interface LeadRequestPayload {
  name: string
  email: string
  phone: string
  organization_name: string
  cnpj: string
  ibge_code: string
  role: LeadRequestRole
  message?: string
}

export interface LeadRequestResponse {
  message: string
  lead_request: {
    id: string
    name: string
    email: string
    phone: string
    organization_name: string
    cnpj: string
    ibge_code: string
    role: LeadRequestRole
    message: string | null
    status: LeadRequestStatus
    user_id: number | null
    trial_started_at: string | null
    trial_expires_at: string | null
    approved_at: string | null
    created_at: string | null
  }
}

export const LEAD_ROLE_OPTIONS: Array<{ value: LeadRequestRole; label: string }> = [
  { value: 'secretario', label: 'Secretário(a) de Finanças' },
  { value: 'contador', label: 'Contador(a)' },
  { value: 'auditor', label: 'Auditor(a)' },
  { value: 'outros', label: 'Outros' },
]
