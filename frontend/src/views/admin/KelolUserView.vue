<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Kelola User</h1>
      <p class="page-subtitle">Buat dan kelola akun user. Maksimal 2 user.</p>
    </div>

    <div class="page-body">
      <Loader v-if="loading" />

      <template v-else>
        <!-- Stats -->
        <div class="stats-row">
          <div class="card stat-card">
            <div class="stat-icon">👤</div>
            <div class="stat-label">Total User</div>
            <div class="stat-value blue">{{ data?.total_users ?? 0 }}</div>
            <div class="stat-sub">Maks. 2 user</div>
          </div>
          <div class="card stat-card">
            <div class="stat-icon">✔</div>
            <div class="stat-label">Slot Tersedia</div>
            <div class="stat-value" :class="slotsClass">{{ data?.slots_available ?? 0 }}</div>
            <div class="stat-sub">{{ data?.slots_available === 0 ? 'Kuota penuh' : 'Slot kosong' }}</div>
          </div>
        </div>

        <!-- User table -->
        <div class="card table-card">
          <div class="table-header">
            <h3 class="table-title">Daftar User</h3>
            <button
              class="btn btn-primary btn-sm"
              :disabled="(data?.slots_available ?? 0) === 0"
              @click="showCreate = true"
            >
              + Tambah User
            </button>
          </div>

          <AlertError :errors="tableErrors" />

          <table class="data-table">
            <thead>
              <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Annual Leave</th>
                <th>Sick Leave</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in data?.users" :key="user.id">
                <td><strong>{{ user.name }}</strong></td>
                <td>{{ user.email }}</td>
                <td>{{ balanceLabel(user, 'Annual Leave') }}</td>
                <td>{{ balanceLabel(user, 'Sick Leave') }}</td>
                <td>
                  <button class="btn btn-ghost btn-sm" @click="openPassword(user)">
                    Update Password
                  </button>
                </td>
              </tr>
              <tr v-if="!data?.users?.length">
                <td colspan="5" class="text-center muted" style="padding: 32px">
                  Belum ada user. Buat user baru.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>

    <!-- Create User Modal -->
    <Modal v-model="showCreate" title="Tambah User Baru" subtitle="Leave balance otomatis ter-assign saat user dibuat.">
      <AlertError :errors="formErrors" />
      <div class="form-group">
        <label class="form-label">Nama Lengkap</label>
        <input v-model="createForm.name" class="form-control" :class="{ error: fieldErrors.name }" placeholder="Budi Santoso" />
        <p v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</p>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Email</label>
          <input v-model="createForm.email" type="email" class="form-control" :class="{ error: fieldErrors.email }" placeholder="budi@energeek.id" />
          <p v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</p>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <input v-model="createForm.password" type="password" class="form-control" :class="{ error: fieldErrors.password }" placeholder="Min. 8 karakter" />
          <p v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</p>
        </div>
      </div>
      <div class="auto-assign-note">
        ℹ️ Auto-assign: Annual Leave (12 hari), Sick Leave (6 hari)
      </div>
      <div class="modal-actions">
        <button class="btn btn-primary" :disabled="creating" @click="handleCreate">
          {{ creating ? 'Menyimpan…' : 'Simpan User' }}
        </button>
        <button class="btn btn-ghost" @click="showCreate = false">Batal</button>
      </div>
    </Modal>

    <!-- Update Password Modal -->
    <Modal v-model="showPassword" :title="`Update Password — ${selectedUser?.name}`">
      <AlertError :errors="pwErrors" />
      <div class="form-group">
        <label class="form-label">Password Baru</label>
        <input v-model="newPassword" type="password" class="form-control" :class="{ error: pwFieldErrors.password }" placeholder="Min. 8 karakter" />
        <p v-if="pwFieldErrors.password" class="field-error">{{ pwFieldErrors.password }}</p>
      </div>
      <div class="modal-actions">
        <button class="btn btn-primary" :disabled="updatingPw" @click="handleUpdatePassword">
          {{ updatingPw ? 'Menyimpan…' : 'Simpan Password' }}
        </button>
        <button class="btn btn-ghost" @click="showPassword = false">Batal</button>
      </div>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, reactive } from 'vue'
import { userService } from '@/services/userService'
import { extractErrors, extractFieldErrors } from '@/plugins/helpers'
import type { AppUser, UsersAdminResponse } from '@/types'
import Loader from '@/components/Loader.vue'
import AlertError from '@/components/AlertError.vue'
import Modal from '@/components/Modal.vue'

const loading = ref(true)
const data = ref<UsersAdminResponse | null>(null)
const tableErrors = ref<string[]>([])

// Create user
const showCreate = ref(false)
const creating = ref(false)
const createForm = reactive({ name: '', email: '', password: '' })
const formErrors = ref<string[]>([])
const fieldErrors = ref<Record<string, string>>({})

// Update password
const showPassword = ref(false)
const selectedUser = ref<AppUser | null>(null)
const newPassword = ref('')
const updatingPw = ref(false)
const pwErrors = ref<string[]>([])
const pwFieldErrors = ref<Record<string, string>>({})

const slotsClass = computed(() => {
  const s = data.value?.slots_available ?? 0
  return s === 0 ? 'red' : 'green'
})

function balanceLabel(user: AppUser, typeName: string): string {
  const b = user.balances.find(b => b.leave_type_name === typeName)
  if (!b) return '—'
  return `${b.total_quota - b.used} / ${b.total_quota} hari`
}

async function loadUsers() {
  loading.value = true
  tableErrors.value = []
  try {
    const res = await userService.getAll()
    data.value = res.data
  } catch (err) {
    tableErrors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

async function handleCreate() {
  creating.value = true
  formErrors.value = []
  fieldErrors.value = {}
  try {
    await userService.create(createForm)
    showCreate.value = false
    Object.assign(createForm, { name: '', email: '', password: '' })
    await loadUsers()
  } catch (err) {
    fieldErrors.value = extractFieldErrors(err)
    formErrors.value = Object.keys(fieldErrors.value).length ? [] : extractErrors(err)
  } finally {
    creating.value = false
  }
}

function openPassword(user: AppUser) {
  selectedUser.value = user
  newPassword.value = ''
  pwErrors.value = []
  pwFieldErrors.value = {}
  showPassword.value = true
}

async function handleUpdatePassword() {
  if (!selectedUser.value) return
  updatingPw.value = true
  pwErrors.value = []
  pwFieldErrors.value = {}
  try {
    await userService.updatePassword(selectedUser.value.id, newPassword.value)
    showPassword.value = false
  } catch (err) {
    pwFieldErrors.value = extractFieldErrors(err)
    pwErrors.value = Object.keys(pwFieldErrors.value).length ? [] : extractErrors(err)
  } finally {
    updatingPw.value = false
  }
}

onMounted(loadUsers)
</script>

<style scoped>
@import '@/plugins/layout.css';

.page-body { padding: 24px 40px; display: flex; flex-direction: column; gap: 20px; }
.stats-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.table-card { padding: 0; overflow: hidden; }
.table-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
}
.table-title { font-size: 16px; font-weight: 700; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.auto-assign-note {
  background: #eff6ff; color: #1d4ed8;
  border-radius: 8px; padding: 10px 14px;
  font-size: 13px; margin-bottom: 20px;
}
.modal-actions { display: flex; gap: 10px; }
</style>
