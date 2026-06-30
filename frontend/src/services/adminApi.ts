import { apiRequest } from '@/services/httpClient'
import type { AdminUser, PendingInvoice, ResetPasswordResult } from '@/types/admin'

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
