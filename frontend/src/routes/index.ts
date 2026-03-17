import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
      meta: { requiresGuest: true },
    },
    // Admin routes
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresAdmin: true },
      children: [
        { path: '', redirect: '/admin/users' },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/KelolUserView.vue'),
        },
        {
          path: 'leave-requests',
          name: 'admin-leave-requests',
          component: () => import('@/views/admin/LeaveRequestsView.vue'),
        },
      ],
    },
    // User routes
    {
      path: '/user',
      component: () => import('@/views/user/UserLayout.vue'),
      meta: { requiresAuth: true, requiresUser: true },
      children: [
        { path: '', redirect: '/user/sisa-kuota' },
        {
          path: 'sisa-kuota',
          name: 'sisa-kuota',
          component: () => import('@/views/user/SisaKuotaView.vue'),
        },
        {
          path: 'ajukan-cuti',
          name: 'ajukan-cuti',
          component: () => import('@/views/user/AjukanCutiView.vue'),
        },
        {
          path: 'riwayat-cuti',
          name: 'riwayat-cuti',
          component: () => import('@/views/user/RiwayatCutiView.vue'),
        },
      ],
    },
    // Redirect root based on role
    {
      path: '/',
      redirect: () => {
        const auth = useAuthStore()
        if (!auth.isAuthenticated) return '/login'
        return auth.isAdmin ? '/admin' : '/user'
      },
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

// Navigation guards
router.beforeEach((to, _from, next) => {
  const auth = useAuthStore()
  auth.loadFromStorage()

  if (to.meta.requiresGuest && auth.isAuthenticated) {
    return next(auth.isAdmin ? '/admin' : '/user')
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return next(auth.isAuthenticated ? '/user' : '/login')
  }

  if (to.meta.requiresUser && !auth.isUser) {
    return next(auth.isAuthenticated ? '/admin' : '/login')
  }

  next()
})

export default router
