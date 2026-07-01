import { apiRequest } from '@/services/httpClient'
import type {
  AdminLeadRequest,
  AdminUser,
  LeadActionResult,
  PendingInvoice,
  ResetPasswordResult,
  StartLeadTrialResult,
  UpdateUserStatusResult,
} from '@/types/admin'

export async function fetchPendingInvoices(token: string): Promise<PendingInvoice[]> {
  const payload = await apiRequest<{ invoices: PendingInvoice[] }>('/admin/invoices/pending', {
    method: 'GET',
    token,
  })

  return payload.invoices
}

export async function fetchAdminUsers(token: string): Promise<AdminUser[]> {
  const payload = await apiRequest<{ users: AdminUser[] }>('/admin/users', {
    method: 'GET',
    token,
  })

  return payload.users
}

export async function resetUserPassword(
  userId: number,
  token: string,
  password?: string,
): Promise<ResetPasswordResult> {
  return apiRequest<ResetPasswordResult>(`/admin/users/${userId}/reset-password`, {
    method: 'POST',
    token,
    body: password ? { password } : {},
  })
}

export async function updateUserStatus(
  userId: number,
  isActive: boolean,
  token: string,
): Promise<UpdateUserStatusResult> {
  return apiRequest<UpdateUserStatusResult>(`/admin/users/${userId}/status`, {
    method: 'PATCH',
    token,
    body: { is_active: isActive },
  })
}

export async function fetchAdminLeadRequests(token: string): Promise<AdminLeadRequest[]> {
  const payload = await apiRequest<{ lead_requests: AdminLeadRequest[] }>('/admin/lead-requests', {
    method: 'GET',
    token,
  })

  return payload.lead_requests
}

export async function startLeadTrial(
  leadId: string,
  token: string,
): Promise<StartLeadTrialResult> {
  return apiRequest<StartLeadTrialResult>(`/admin/lead-requests/${leadId}/start-trial`, {
    method: 'POST',
    token,
  })
}

export async function approveLeadRequest(
  leadId: string,
  token: string,
): Promise<LeadActionResult> {
  return apiRequest<LeadActionResult>(`/admin/lead-requests/${leadId}/approve`, {
    method: 'POST',
    token,
  })
}

export async function failLeadRequest(
  leadId: string,
  token: string,
): Promise<LeadActionResult> {
  return apiRequest<LeadActionResult>(`/admin/lead-requests/${leadId}/fail`, {
    method: 'POST',
    token,
  })
}
