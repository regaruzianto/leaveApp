<template>
  <div class="app-layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="brand-name">Leave<span>Hub</span></div>
        <div class="brand-sub">Leave Request Management</div>
      </div>

      <div class="sidebar-section-label">Admin Menu</div>
      <nav class="sidebar-nav">
        <RouterLink to="/admin/users" class="nav-item" :class="{ active: route.path === '/admin/users' }">
          <span class="nav-icon">🧑‍💼</span> Kelola User
        </RouterLink>
        <RouterLink to="/admin/leave-requests" class="nav-item" :class="{ active: route.path === '/admin/leave-requests' }">
          <span class="nav-icon">📋</span> Leave Requests
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

    <!-- Role switcher (UI indicator) -->
    <div class="role-switcher">
      <span>Role:</span>
      <button class="role-btn active">Admin</button>
      <button class="role-btn" @click="goUser">User</button>
      <button class="show-all-btn">🚩 Show All</button>
    </div>

    <!-- Page content -->
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
  auth.user?.name?.split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase() ?? 'A'
)

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}

function goUser() {
  // If user role exists, switch — else stay
  router.push('/user').catch(() => {})
}
</script>

<style scoped>
@import '@/plugins/layout.css';
</style>
