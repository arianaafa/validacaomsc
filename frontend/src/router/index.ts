import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      component: () => import('../layouts/MainLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../views/Dashboard.vue'),
          meta: { title: 'Dashboard' },
        },
        {
          path: 'msc/uploads/:id',
          name: 'msc-upload-detail',
          component: () => import('../views/MscUploadDetailView.vue'),
          meta: { title: 'Detalhes da Competência' },
        },
        {
          path: 'msc/import',
          name: 'msc-import',
          component: () => import('../views/MscImportView.vue'),
          meta: { title: 'Importar MSC' },
        },
        {
          path: 'msc/rules',
          name: 'msc-rules',
          component: () => import('../views/MscRules/Index.vue'),
          meta: { title: 'Regras de Validação' },
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('../views/SettingsView.vue'),
          meta: { title: 'Configurações' },
        },
      ],
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/auth/RegisterView.vue'),
      meta: { guestOnly: true },
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.user && auth.accessToken) {
    await auth.bootstrap()
  }

  if (to.matched.some((record) => record.meta.requiresAuth) && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'home' }
  }

  return true
})

export default router
