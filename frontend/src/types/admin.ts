export interface PendingInvoice {
  id: number
  municipality_id: number
  municipality_name: string
  amount: string
  status: string
  due_date: string
  created_at: string | null
}

export interface AdminUser {
  id: number
  name: string
  email: string
  municipality_id: number | null
  force_password_change: boolean
  is_active: boolean
}

export interface ResetPasswordResult {
  message: string
  user: {
    id: number
    name: string
    email: string
  }
  temporary_password: string | null
  force_password_change: boolean
}

export interface UpdateUserStatusResult {
  message: string
  user: AdminUser
}

export type AdminLeadStatus = 'pending' | 'trial' | 'approved' | 'failed'

export interface AdminLeadUser {
  id: number
  name: string
  email: string
  is_active: boolean
  is_trial: boolean
  trial_expires_at: string | null
}

export interface AdminLeadRequest {
  id: string
  name: string
  email: string
  phone: string
  organization_name: string
  cnpj: string
  ibge_code: string
  role: string
  message: string | null
  status: AdminLeadStatus
  user_id: number | null
  trial_started_at: string | null
  trial_expires_at: string | null
  approved_at: string | null
  created_at: string | null
  user: AdminLeadUser | null
}

export interface StartLeadTrialResult {
  message: string
  lead_request: AdminLeadRequest
  user: { id: number; name: string; email: string }
  temporary_password: string
  email_sent: boolean
}

export interface LeadActionResult {
  message: string
  lead_request: AdminLeadRequest
}
