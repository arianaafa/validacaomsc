export interface AuthMunicipality {
  id: number
  name: string
  ibge_code: string
}

export interface AuthUser {
  id: number
  name: string
  email: string
  is_superadmin: boolean
  force_password_change: boolean
  municipality_id: number | null
  municipality: AuthMunicipality | null
  is_active: boolean
  is_trial: boolean
  trial_expires_at: string | null
}

export interface AuthTokenPayload {
  user: AuthUser
  access_token: string
  expires_at: string | null
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterCredentials {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface ChangePasswordCredentials {
  current_password: string
  password: string
  password_confirmation: string
}

export interface ChangePasswordResponse {
  message: string
  user: AuthUser
}

export interface ValidationErrorResponse {
  message: string
  errors: Record<string, string[]>
}
