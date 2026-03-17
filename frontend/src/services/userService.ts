import api from './api'
import type { CreateUserPayload } from '@/types'

export const userService = {
  // Admin
  async getAll() {
    const response = await api.get('/admin/users')
    return response.data
  },

  async create(payload: CreateUserPayload) {
    const response = await api.post('/admin/users', payload)
    return response.data
  },

  async updatePassword(id: number, password: string) {
    const response = await api.patch(`/admin/users/${id}/password`, { password })
    return response.data
  },
}

export const leaveTypeService = {
  async getAll() {
    const response = await api.get('/leave-types')
    return response.data
  },
}

export const leaveBalanceService = {
  async getMine() {
    const response = await api.get('/leave-balances')
    return response.data
  },
}
