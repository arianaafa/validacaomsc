import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import * as authApi from '@/services/authApi'
import { ApiError } from '@/services/httpClient'
import type {
  AuthUser,
  LoginCredentials,
  RegisterCredentials,
} from '@/types/auth'

const STORAGE_KEY = 'validamsc.auth'

interface PersistedAuthState {
  user: AuthUser
  accessToken: string
  expiresAt: string | null
}

function loadPersistedState(): PersistedAuthState | null {
  const raw = localStorage.getItem(STORAGE_KEY)

  if (!raw) {
    return null
  }

  try {
    return JSON.parse(raw) as PersistedAuthState
  } catch {
    localStorage.removeItem(STORAGE_KEY)
    return null
  }
}

function persistState(state: PersistedAuthState | null): void {
  if (!state) {
    localStorage.removeItem(STORAGE_KEY)
    return
  }

  localStorage.setItem(STORAGE_KEY, JSON.stringify(state))
}

export const useAuthStore = defineStore('auth', () => {
  const persisted = loadPersistedState()

  const user = ref<AuthUser | null>(persisted?.user ?? null)
  const accessToken = ref<string | null>(persisted?.accessToken ?? null)
  const expiresAt = ref<string | null>(persisted?.expiresAt ?? null)
  const loading = ref(false)
  const error = ref<string | null>(null)
  const fieldErrors = ref<Record<string, string[]>>({})

  const isAuthenticated = computed(() => user.value !== null && accessToken.value !== null)

  const isSuperAdmin = computed(() => user.value?.is_superadmin === true)

  function setSession(payload: { user: AuthUser; access_token: string; expires_at: string | null }): void {
    user.value = payload.user
    accessToken.value = payload.access_token
    expiresAt.value = payload.expires_at

    persistState({
      user: payload.user,
      accessToken: payload.access_token,
      expiresAt: payload.expires_at,
    })
  }

  function clearSession(): void {
    user.value = null
    accessToken.value = null
    expiresAt.value = null
    persistState(null)
  }

  function clearErrors(): void {
    error.value = null
    fieldErrors.value = {}
  }

  function handleApiError(err: unknown): void {
    if (err instanceof ApiError) {
      error.value = err.message
      fieldErrors.value = err.errors
      return
    }

    error.value = 'Não foi possível concluir a operação.'
  }

  async function login(credentials: LoginCredentials): Promise<boolean> {
    clearErrors()
    loading.value = true

    try {
      const payload = await authApi.login(credentials)
      setSession(payload)
      return true
    } catch (err) {
      handleApiError(err)
      return false
    } finally {
      loading.value = false
    }
  }

  async function register(credentials: RegisterCredentials): Promise<boolean> {
    clearErrors()
    loading.value = true

    try {
      const payload = await authApi.register(credentials)
      setSession(payload)
      return true
    } catch (err) {
      handleApiError(err)
      return false
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    const token = accessToken.value

    if (token) {
      try {
        await authApi.logout(token)
      } catch {
        // Ignora falha de logout remoto e limpa sessão local.
      }
    }

    clearSession()
    clearErrors()
  }

  async function refresh(): Promise<boolean> {
    const token = accessToken.value

    if (!token) {
      return false
    }

    try {
      const payload = await authApi.refreshToken(token)
      setSession(payload)
      return true
    } catch {
      clearSession()
      return false
    }
  }

  async function bootstrap(): Promise<void> {
    const token = accessToken.value

    if (!token) {
      return
    }

    loading.value = true

    try {
      const payload = await authApi.fetchMe(token)
      user.value = payload.user
      persistState({
        user: payload.user,
        accessToken: token,
        expiresAt: expiresAt.value,
      })
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        const refreshed = await refresh()
        if (!refreshed) {
          clearSession()
        }
      } else {
        clearSession()
      }
    } finally {
      loading.value = false
    }
  }

  return {
    user,
    accessToken,
    expiresAt,
    loading,
    error,
    fieldErrors,
    isAuthenticated,
    isSuperAdmin,
    login,
    register,
    logout,
    refresh,
    bootstrap,
    clearErrors,
  }
})
