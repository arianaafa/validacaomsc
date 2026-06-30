import type {
  AuthTokenPayload,
  AuthUser,
  ChangePasswordCredentials,
  ChangePasswordResponse,
  LoginCredentials,
  RegisterCredentials,
} from '@/types/auth'
import { apiRequest } from '@/services/httpClient'

export async function login(credentials: LoginCredentials): Promise<AuthTokenPayload> {
  return apiRequest<AuthTokenPayload>('/login', {
    method: 'POST',
    body: { ...credentials },
  })
}

export async function register(credentials: RegisterCredentials): Promise<AuthTokenPayload> {
  return apiRequest<AuthTokenPayload>('/register', {
    method: 'POST',
    body: { ...credentials },
  })
}

export async function logout(token: string): Promise<{ message: string }> {
  return apiRequest<{ message: string }>('/logout', {
    method: 'POST',
    token,
  })
}

export async function refreshToken(token: string): Promise<AuthTokenPayload> {
  return apiRequest<AuthTokenPayload>('/refresh', {
    method: 'POST',
    token,
  })
}

export async function fetchMe(token: string): Promise<{ user: AuthUser }> {
  return apiRequest<{ user: AuthUser }>('/me', {
    method: 'GET',
    token,
  })
}

export async function changePassword(
  credentials: ChangePasswordCredentials,
  token: string,
): Promise<ChangePasswordResponse> {
  return apiRequest<ChangePasswordResponse>('/password', {
    method: 'POST',
    token,
    body: { ...credentials },
  })
}
