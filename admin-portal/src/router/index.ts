import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/auth/LoginView.vue'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { guest: true }
    },
    {
      path: '/',
      component: () => import('@/components/layout/MainLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/dashboard'
        },
        {
          path: 'dashboard',
          name: 'dashboard',
          component: () => import('../views/DashboardView.vue'),
        },
        {
          path: 'companies',
          name: 'companies',
          component: () => import('../views/admin/companies/CompaniesView.vue'),
        },
        {
          path: 'dids',
          name: 'dids',
          component: () => import('../views/admin/dids/DidsView.vue'),
        },
        {
          path: 'company-agents',
          name: 'company-agents',
          component: () => import('../views/admin/company-agents/CompanyAgentsView.vue'),
        },
        {
          path: 'invoices',
          name: 'invoices',
          component: () => import('../views/admin/invoices/InvoicesView.vue'),
        },
        {
          path: 'voices',
          name: 'voices',
          component: () => import('../views/admin/voices/VoicesView.vue'),
        },
        {
          path: 'reports',
          name: 'reports',
          component: () => import('../views/admin/reports/ReportsView.vue'),
        },
        {
          path: 'team',
          name: 'team',
          component: () => import('../views/admin/team/TeamView.vue'),
        },
        {
          path: 'activity-logs',
          name: 'activity-logs',
          component: () => import('../views/admin/activity-logs/ActivityLogsView.vue'),
        }
      ]
    },
  ],
})

router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()

  // Verify auth on protected routes or initially
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Attempt to restore session or redirect
    const isValid = await authStore.checkAuth()
    if (!isValid) {
      return next('/login')
    }
  }

  // Prevent authenticated users from visiting login
  if (to.meta.guest && authStore.isAuthenticated) {
    return next('/dashboard')
  }

  next()
})

export default router
