import { apiRequest } from '@/services/httpClient'
import type {
  MscDashboardResponse,
  MscUploadPayload,
  MscUploadResponse,
} from '@/types/msc'

export async function fetchMscDashboard(token: string): Promise<MscDashboardResponse> {
  return apiRequest<MscDashboardResponse>('/msc/uploads', {
    method: 'GET',
    token,
  })
}

export async function fetchMscUpload(
  uploadId: string,
  token: string,
): Promise<MscUploadResponse> {
  return apiRequest<MscUploadResponse>(`/msc/uploads/${uploadId}`, {
    method: 'GET',
    token,
  })
}

export async function uploadMscSpreadsheet(
  payload: MscUploadPayload,
  token: string,
): Promise<MscUploadResponse> {
  const formData = new FormData()
  formData.append('file', payload.file)
  formData.append('periodo', payload.periodo)
  formData.append('tipo_msc', payload.tipo_msc)

  return apiRequest<MscUploadResponse>('/msc/uploads', {
    method: 'POST',
    body: formData,
    token,
  })
}
