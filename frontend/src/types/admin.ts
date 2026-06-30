export interface PendingInvoice {
  id: number
  municipality_id: number
  municipality_name: string
  amount: string
  status: string
  due_date: string
  created_at: string | null
}

export interface AdminUser {
  id: number
  name: string
  email: string
  municipality_id: number | null
  force_password_change: boolean
  is_active: boolean
}

export interface ResetPasswordResult {
  message: string
  user: {
    id: number
    name: string
    email: string
  }
  temporary_password: string | null
  force_password_change: boolean
}

export interface UpdateUserStatusResult {
  message: string
  user: AdminUser
}
