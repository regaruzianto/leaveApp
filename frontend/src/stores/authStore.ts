import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'
import type { AuthUser, LoginCredentials } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthUser | null>(null)
  const token = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const isUser = computed(() => user.value?.role === 'user')

  function loadFromStorage() {
    const storedToken = localStorage.getItem('auth_token')
    const storedUser = localStorage.getItem('auth_user')
    if (storedToken && storedUser) {
      token.value = storedToken
      try {
        user.value = JSON.parse(storedUser)
      } catch {
        clearSession()
      }
    }
  }

  async function login(credentials: LoginCredentials) {
    const response = await authService.login(credentials)
    token.value = response.data.token
    user.value = response.data.user
    localStorage.setItem('auth_token', response.data.token)
    localStorage.setItem('auth_user', JSON.stringify(response.data.user))
    return response
  }

  async function logout() {
    try {
      await authService.logout()
    } catch {
      // Ignore errors on logout — clear session regardless
    } finally {
      clearSession()
    }
  }

  function clearSession() {
    user.value = null
    token.value = null
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
  }

  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    isUser,
    loadFromStorage,
    login,
    logout,
    clearSession,
  }
})
