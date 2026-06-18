const apiUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:8000'

export async function fetchHealth() {
  const response = await fetch(`${apiUrl}/api/health`)

  if (!response.ok) {
    throw new Error(`API respondeu com status ${response.status}`)
  }

  return response.json() as Promise<{
    status: string
    app: string
    database: string
  }>
}
