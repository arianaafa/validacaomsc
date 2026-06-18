export interface AuthUser {
  id: number
  name: string
  email: string
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

export interface ValidationErrorResponse {
  message: string
  errors: Record<string, string[]>
}
