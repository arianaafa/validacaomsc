import { apiRequest } from '@/services/httpClient'
import type { MscRulesListParams, MscRulesPaginatedResponse } from '@/types/mscRules'

function buildQueryString(params: MscRulesListParams): string {
  const searchParams = new URLSearchParams()

  if (params.search && params.search.trim() !== '') {
    searchParams.set('search', params.search.trim())
  }

  if (params.validation_type && params.validation_type !== '') {
    searchParams.set('validation_type', params.validation_type)
  }

  if (params.page && params.page > 1) {
    searchParams.set('page', String(params.page))
  }

  if (params.per_page) {
    searchParams.set('per_page', String(params.per_page))
  }

  const query = searchParams.toString()

  return query === '' ? '' : `?${query}`
}

export async function fetchMscRules(
  params: MscRulesListParams,
  token: string,
): Promise<MscRulesPaginatedResponse> {
  return apiRequest<MscRulesPaginatedResponse>(`/v1/msc-rules${buildQueryString(params)}`, {
    method: 'GET',
    token,
  })
}
