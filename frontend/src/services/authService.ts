import api from './api'
import type { LoginCredentials } from '@/types'

export const authService = {
  async login(credentials: LoginCredentials) {
    const response = await api.post('/auth/login', credentials)
    return response.data
  },

  async logout() {
    const response = await api.post('/auth/logout')
    return response.data
  },

  async me() {
    const response = await api.get('/auth/me')
    return response.data
  },
}
