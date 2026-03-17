import api from './api'
import type { RespondLeaveRequestPayload, SubmitLeaveRequestPayload } from '@/types'

export const leaveRequestService = {
  async getAll() {
    const response = await api.get('/leave-requests')
    return response.data
  },

  async submit(payload: SubmitLeaveRequestPayload) {
    const response = await api.post('/leave-requests', payload)
    return response.data
  },

  async cancel(id: number) {
    const response = await api.patch(`/leave-requests/${id}/cancel`)
    return response.data
  },

  async softDelete(id: number) {
    const response = await api.delete(`/leave-requests/${id}`)
    return response.data
  },

  // Admin only
  async respond(id: number, payload: RespondLeaveRequestPayload) {
    const response = await api.post(`/admin/leave-requests/${id}/respond`, payload)
    return response.data
  },
}
