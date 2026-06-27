import { apiRequest } from '@/services/httpClient'
import type { MscUploadPayload, MscUploadResponse } from '@/types/msc'

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
