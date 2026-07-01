const API_URL = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

export class ApiError extends Error {
  status: number
  errors: Record<string, string[]>

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

type RequestOptions = Omit<RequestInit, 'body'> & {
  body?: BodyInit | object | null
  token?: string | null
}

export async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')

  if (options.token) {
    headers.set('Authorization', `Bearer ${options.token}`)
  }

  let body = options.body ?? null

  if (body !== null && !(body instanceof FormData) && typeof body === 'object') {
    headers.set('Content-Type', 'application/json')
    body = JSON.stringify(body)
  }

  const response = await fetch(`${API_URL}/api${path}`, {
    ...options,
    headers,
    body: body as BodyInit | null | undefined,
  })

  const contentType = response.headers.get('content-type') ?? ''
  const payload = contentType.includes('application/json')
    ? await response.json()
    : null

  if (!response.ok) {
    throw new ApiError(
      payload?.message ?? 'Erro inesperado na requisição.',
      response.status,
      payload?.errors ?? {},
    )
  }

  return payload as T
}

export { API_URL }
