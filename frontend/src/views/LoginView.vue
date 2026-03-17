<template>
  <div class="login-page">
    <div class="login-card">
      <div class="login-brand">
        <h1 class="brand">Leave<span>Hub</span></h1>
        <p class="brand-desc">Leave Request Management System</p>
      </div>

      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input
            v-model="form.email"
            type="email"
            class="form-control"
            :class="{ error: fieldErrors.email }"
            placeholder="admin@energeek.id"
            autocomplete="email"
          />
          <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</p>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input
            v-model="form.password"
            type="password"
            class="form-control"
            :class="{ error: fieldErrors.password }"
            placeholder="••••••••••"
            autocomplete="current-password"
          />
          <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
        </div>

        <p v-if="globalError" class="global-error">{{ globalError }}</p>

        <button type="submit" class="btn btn-primary login-btn" :disabled="loading">
          {{ loading ? 'Logging in…' : 'Login' }}
        </button>
      </form>

      <p class="login-hint">Sanctum PAT · No register endpoint</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import { extractFieldErrors } from '@/plugins/helpers'

const router = useRouter()
const auth = useAuthStore()

const form = reactive({ email: '', password: '' })
const loading = ref(false)
const globalError = ref('')
const fieldErrors = ref<Record<string, string>>({})

async function handleLogin() {
  loading.value = true
  globalError.value = ''
  fieldErrors.value = {}

  try {
    await auth.login(form)
    router.push(auth.isAdmin ? '/admin' : '/user')
  } catch (err: unknown) {
    const e = err as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    fieldErrors.value = extractFieldErrors(err)
    if (!Object.keys(fieldErrors.value).length) {
      globalError.value = e?.response?.data?.message ?? 'Login gagal. Periksa email dan password.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
@import '@/plugins/layout.css';

.login-page {
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: #f3f4f6;
}
.login-card {
  background: #fff;
  border-radius: 12px;
  padding: 36px 32px;
  width: 100%; max-width: 360px;
  box-shadow: 0 4px 24px rgba(0,0,0,0.08);
}
.login-brand { margin-bottom: 28px; }
.brand { font-size: 22px; font-weight: 800; color: #111827; }
.brand span { color: #6366f1; }
.brand-desc { font-size: 13px; color: #6b7280; margin-top: 2px; }
.login-btn { width: 100%; padding: 11px; font-size: 15px; }
.global-error {
  background: #fee2e2; color: #991b1b;
  padding: 10px 12px; border-radius: 8px;
  font-size: 13px; margin-bottom: 14px;
}
.login-hint {
  text-align: center; color: #9ca3af; font-size: 12px; margin-top: 20px;
}
</style>
