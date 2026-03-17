<template>
  <div class="app-layout">
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="brand-name">Leave<span>Hub</span></div>
        <div class="brand-sub">Leave Request Management</div>
      </div>

      <div class="sidebar-section-label">User Menu</div>
      <nav class="sidebar-nav">
        <RouterLink to="/user/sisa-kuota"   class="nav-item" :class="{ active: route.path === '/user/sisa-kuota' }">
          <span class="nav-icon">🏦</span> Sisa Kuota
        </RouterLink>
        <RouterLink to="/user/ajukan-cuti"  class="nav-item" :class="{ active: route.path === '/user/ajukan-cuti' }">
          <span class="nav-icon">✏️</span> Ajukan Cuti
        </RouterLink>
        <RouterLink to="/user/riwayat-cuti" class="nav-item" :class="{ active: route.path === '/user/riwayat-cuti' }">
          <span class="nav-icon">📋</span> Riwayat Cuti
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <div class="avatar">{{ initials }}</div>
        <div class="footer-info">
          <div class="footer-name">{{ auth.user?.name }}</div>
          <div class="footer-email">{{ auth.user?.email }}</div>
        </div>
        <button class="logout-btn" title="Logout" @click="handleLogout">⏻</button>
      </div>
    </aside>

    <!-- Role switcher -->
    <div class="role-switcher">
      <span>Role:</span>
      <button class="role-btn" @click="goAdmin">Admin</button>
      <button class="role-btn active">User</button>
      <button class="show-all-btn">🚩 Show All</button>
    </div>

    <main class="main-content">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const initials = computed(() =>
  auth.user?.name?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() ?? 'U'
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
function goAdmin() {
  router.push('/admin').catch(() => {})
}
</script>

<style scoped>
@import '@/plugins/layout.css';
</style>
