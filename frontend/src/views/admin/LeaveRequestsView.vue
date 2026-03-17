<template>
  <div>
    <div class="page-header">
      <h1 class="page-title">Semua Leave Request</h1>
      <p class="page-subtitle">Kelola dan respond permohonan cuti dari semua user.</p>
    </div>

    <div class="page-body">
      <Loader v-if="loading" />

      <template v-else>
        <!-- Summary stats -->
        <div class="stats-row">
          <div class="card stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-value yellow">{{ summary.pending }}</div>
            <div class="stat-sub">Menunggu keputusan</div>
          </div>
          <div class="card stat-card">
            <div class="stat-icon">✔</div>
            <div class="stat-label">Approved</div>
            <div class="stat-value green">{{ summary.approved }}</div>
            <div class="stat-sub">Disetujui</div>
          </div>
          <div class="card stat-card">
            <div class="stat-icon">✖</div>
            <div class="stat-label">Rejected</div>
            <div class="stat-value red">{{ summary.rejected }}</div>
            <div class="stat-sub">Ditolak</div>
          </div>
        </div>

        <!-- Pending — needs action -->
        <div v-if="pending.length" class="card table-card">
          <div class="table-header">
            <h3 class="table-title">Perlu Tindakan</h3>
            <span class="pending-badge">⚡ {{ pending.length }} pending</span>
          </div>
          <table class="data-table">
            <thead>
              <tr>
                <th>User</th><th>Tipe</th><th>Tanggal</th><th>Hari</th>
                <th>Alasan</th><th>Status</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in pending" :key="r.id">
                <td><strong>{{ r.user_name }}</strong></td>
                <td>{{ r.leave_type_name }}</td>
                <td>{{ fmtRange(r.start_date, r.end_date) }}</td>
                <td>{{ r.total_days }}</td>
                <td>{{ r.reason }}</td>
                <td><StatusBadge :status="r.status" /></td>
                <td class="action-cell">
                  <button class="btn btn-success btn-sm" @click="openRespond(r, 'approve')">Approve</button>
                  <button class="btn btn-danger btn-sm" @click="openRespond(r, 'reject')">Reject</button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Inline confirm panels -->
          <div v-if="respondTarget" class="confirm-panels">
            <div class="confirm-panel" :class="respondAction">
              <div class="confirm-title">
                {{ respondAction === 'approve' ? 'Approve' : 'Reject' }} Request — {{ respondTarget.user_name }}
              </div>
              <div class="confirm-detail">
                {{ respondTarget.leave_type_name }} · {{ fmtRange(respondTarget.start_date, respondTarget.end_date) }} ({{ respondTarget.total_days }} hari)<br/>
                Alasan: {{ respondTarget.reason }}
              </div>
              <p v-if="respondAction === 'approve'" class="confirm-balance">
                Sisa balance setelah approve: {{ balanceAfter }} hari
              </p>
              <div class="form-group mt-4">
                <label class="form-label">Catatan Admin (opsional)</label>
                <textarea v-model="adminNotes" class="form-control" rows="3"
                  :placeholder="respondAction === 'approve' ? 'Approved, selamat berlibur.' : 'Alasan penolakan...'" />
              </div>
              <AlertError :errors="respondErrors" />
              <div class="confirm-actions">
                <button class="btn" :class="respondAction === 'approve' ? 'btn-success' : 'btn-danger'"
                  :disabled="responding" @click="confirmRespond">
                  {{ responding ? 'Memproses…' : (respondAction === 'approve' ? 'Konfirmasi Approve' : 'Konfirmasi Reject') }}
                </button>
                <button class="btn btn-ghost" @click="respondTarget = null">Batal</button>
              </div>
            </div>
          </div>
        </div>

        <!-- History -->
        <div class="card table-card">
          <div class="table-header">
            <h3 class="table-title">Riwayat Semua Request</h3>
          </div>
          <AlertError :errors="deleteErrors" />
          <table class="data-table">
            <thead>
              <tr>
                <th>User</th><th>Tipe</th><th>Tanggal</th><th>Hari</th>
                <th>Status</th><th>Direspon</th><th>Catatan</th><th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in history" :key="r.id">
                <td>{{ r.user_name }}</td>
                <td>{{ r.leave_type_name }}</td>
                <td>{{ fmtRange(r.start_date, r.end_date) }}</td>
                <td>{{ r.total_days }}</td>
                <td><StatusBadge :status="r.status" /></td>
                <td>{{ r.responded_at ? fmtDate(r.responded_at) : '—' }}</td>
                <td>{{ r.admin_notes ?? '—' }}</td>
                <td>
                  <button v-if="!r.deleted_at" class="btn btn-danger btn-sm"
                    @click="handleDelete(r.id)">Hapus</button>
                  <span v-else class="muted" style="font-size:12px">Dihapus</span>
                </td>
              </tr>
              <tr v-if="!history.length">
                <td colspan="8" class="text-center muted" style="padding: 32px">Belum ada riwayat.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { leaveRequestService } from '@/services/leaveRequestService'
import { leaveBalanceService } from '@/services/userService'
import { formatDateRange, formatDate, extractErrors } from '@/plugins/helpers'
import type { LeaveRequest, LeaveRequestsResponse } from '@/types'
import StatusBadge from '@/components/StatusBadge.vue'
import AlertError from '@/components/AlertError.vue'
import Loader from '@/components/Loader.vue'

const loading = ref(true)
const data = ref<LeaveRequestsResponse | null>(null)
const deleteErrors = ref<string[]>([])

const pending = computed(() => data.value?.pending ?? [])
const history = computed(() => data.value?.history ?? [])
const summary = computed(() => data.value?.summary ?? { pending: 0, approved: 0, rejected: 0 })

const respondTarget = ref<LeaveRequest | null>(null)
const respondAction = ref<'approve' | 'reject'>('approve')
const adminNotes = ref('')
const responding = ref(false)
const respondErrors = ref<string[]>([])

const balanceAfter = computed(() => {
  // Rough display — actual check done server-side
  return '?'
})

function fmtRange(s: string, e: string) { return formatDateRange(s, e) }
function fmtDate(d: string) { return formatDate(d) }

async function load() {
  loading.value = true
  try {
    const res = await leaveRequestService.getAll()
    data.value = res.data
  } catch (err) {
    deleteErrors.value = extractErrors(err)
  } finally {
    loading.value = false
  }
}

function openRespond(r: LeaveRequest, action: 'approve' | 'reject') {
  respondTarget.value = r
  respondAction.value = action
  adminNotes.value = ''
  respondErrors.value = []
}

async function confirmRespond() {
  if (!respondTarget.value) return
  responding.value = true
  respondErrors.value = []
  try {
    await leaveRequestService.respond(respondTarget.value.id, {
      action: respondAction.value,
      admin_notes: adminNotes.value || undefined,
    })
    respondTarget.value = null
    await load()
  } catch (err) {
    respondErrors.value = extractErrors(err)
  } finally {
    responding.value = false
  }
}

async function handleDelete(id: number) {
  deleteErrors.value = []
  try {
    await leaveRequestService.softDelete(id)
    await load()
  } catch (err) {
    deleteErrors.value = extractErrors(err)
  }
}

onMounted(load)
</script>

<style scoped>
@import '@/plugins/layout.css';

.page-body { padding: 24px 40px; display: flex; flex-direction: column; gap: 20px; }
.stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.table-card { padding: 0; overflow: hidden; }
.table-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
}
.table-title { font-size: 16px; font-weight: 700; }
.pending-badge {
  background: #fef3c7; color: #92400e;
  padding: 4px 10px; border-radius: 999px;
  font-size: 12px; font-weight: 700;
}
.action-cell { display: flex; gap: 6px; }
.confirm-panels { padding: 0 24px 24px; }
.confirm-panel {
  border-radius: 10px; padding: 20px; margin-top: 16px;
}
.confirm-panel.approve { border-left: 4px solid var(--green); background: #f0fdf4; }
.confirm-panel.reject  { border-left: 4px solid var(--red); background: #fef2f2; }
.confirm-title { font-size: 15px; font-weight: 700; margin-bottom: 8px; }
.confirm-detail { font-size: 13px; color: #374151; line-height: 1.7; }
.confirm-balance { font-size: 13px; color: var(--accent); font-weight: 600; margin-top: 4px; }
.confirm-actions { display: flex; gap: 10px; margin-top: 8px; }
</style>
