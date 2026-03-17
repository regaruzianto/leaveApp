// ============================================================
// Global TypeScript types for LeaveHub Frontend
// ============================================================

export type UserRole = 'admin' | 'user'

export type LeaveStatus = 'pending' | 'approved' | 'rejected' | 'cancelled'

export interface AuthUser {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface LeaveType {
  id: number
  name: string
  default_quota: number
}

export interface LeaveBalance {
  leave_type_id: number
  leave_type_name: string
  year: number
  total_quota: number
  used: number
  remaining: number
}

export interface LeaveBalanceSummary {
  year: number
  balances: LeaveBalance[]
  summary: {
    pending: number
    approved: number
    rejected: number
  }
}

export interface LeaveRequest {
  id: number
  user_id: number
  user_name: string
  leave_type_id: number
  leave_type_name: string
  start_date: string
  end_date: string
  total_days: number
  reason: string
  status: LeaveStatus
  admin_notes: string | null
  responded_by: number | null
  responded_by_name: string | null
  responded_at: string | null
  created_at: string
  deleted_at: string | null
  deleted_by: number | null
}

export interface LeaveRequestsResponse {
  pending: LeaveRequest[]
  history: LeaveRequest[]
  summary: {
    pending: number
    approved: number
    rejected: number
  }
}

export interface AppUser {
  id: number
  name: string
  email: string
  role: UserRole
  balances: LeaveBalance[]
}

export interface UsersAdminResponse {
  users: AppUser[]
  total_users: number
  max_users: number
  slots_available: number
}

export interface ApiError {
  message: string
  errors?: Record<string, string[]>
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface CreateUserPayload {
  name: string
  email: string
  password: string
}

export interface SubmitLeaveRequestPayload {
  leave_type_id: number
  start_date: string
  end_date: string
  reason: string
}

export interface RespondLeaveRequestPayload {
  action: 'approve' | 'reject'
  admin_notes?: string
}
