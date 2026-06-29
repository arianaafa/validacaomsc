import { apiRequest } from '@/services/httpClient'
import type { LeadRequestPayload, LeadRequestResponse } from '@/types/leads'

export async function submitLeadRequest(
  payload: LeadRequestPayload,
): Promise<LeadRequestResponse> {
  return apiRequest<LeadRequestResponse>('/v1/lead-requests', {
    method: 'POST',
    body: payload,
  })
}
