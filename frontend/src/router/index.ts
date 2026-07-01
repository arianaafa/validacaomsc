import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/admin',
      component: () => import('../layouts/AdminLayout.vue'),
      meta: { requiresAuth: true, requiresSuperAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-overview',
          component: () => import('../views/Admin/Overview.vue'),
          meta: {
            title: 'Visão Geral',
            breadcrumbParent: { label: 'Dashboard', to: '/admin' },
          },
        },
        {
          path: 'invoices',
          name: 'admin-invoices',
          component: () => import('../views/Admin/PendingInvoices.vue'),
          meta: {
            title: 'Faturas Pendentes',
            breadcrumbParent: { label: 'Financeiro', to: '/admin/invoices' },
          },
        },
        {
          path: 'leads',
          name: 'admin-leads',
          component: () => import('../views/Admin/Leads.vue'),
          meta: {
            title: 'Leads',
            breadcrumbParent: { label: 'Administração', to: '/admin/leads' },
          },
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('../views/Admin/Users.vue'),
          meta: {
            title: 'Usuários',
            breadcrumbParent: { label: 'Administração', to: '/admin/users' },
          },
        },
        {
          path: 'users/reset-password',
          name: 'admin-reset-password',
          component: () => import('../views/Admin/ResetPassword.vue'),
          meta: {
            title: 'Resetar Senha',
            breadcrumbParent: { label: 'Segurança', to: '/admin/users/reset-password' },
          },
        },
      ],
    },
    {
      path: '/',
      component: () => import('../layouts/MainLayout.vue'),
      meta: { requiresAuth: true, municipalOnly: true },
      children: [
        {
          path: '',
          name: 'home',
          component: () => import('../views/Dashboard.vue'),
          meta: {
            title: 'Visão Geral',
            breadcrumbParent: { label: 'Dashboard', to: '/' },
          },
        },
        {
          path: 'msc/uploads/:id',
          name: 'msc-upload-detail',
          component: () => import('../views/MscUploadDetailView.vue'),
          meta: {
            title: 'Detalhes da Competência',
            breadcrumbParent: { label: 'Dashboard', to: '/' },
          },
        },
        {
          path: 'msc/import',
          name: 'msc-import',
          component: () => import('../views/MscImportView.vue'),
          meta: {
            title: 'Importar MSC',
            breadcrumbParent: { label: 'MSC', to: '/msc/import' },
          },
        },
        {
          path: 'msc/rules',
          name: 'msc-rules',
          component: () => import('../views/MscRules/Index.vue'),
          meta: {
            title: 'Regras de Validação',
            breadcrumbParent: { label: 'MSC', to: '/msc/rules' },
          },
        },
        {
          path: 'settings',
          name: 'settings',
          component: () => import('../views/SettingsView.vue'),
          meta: {
            title: 'Configurações',
            breadcrumbParent: { label: 'Conta', to: '/settings' },
          },
        },
      ],
    },
    {
      path: '/admin/forbidden',
      name: 'admin-forbidden',
      component: () => import('../views/Admin/ForbiddenView.vue'),
      meta: { requiresAuth: true, title: 'Acesso negado' },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/auth/LoginView.vue'),
      meta: { guestOnly: true },
    },
    {
      path: '/solicitar-demonstracao',
      name: 'lead-contact',
      component: () => import('../views/Leads/ContactForm.vue'),
      meta: { guestOnly: true, title: 'Solicitar Demonstração' },
    },
    {
      path: '/register',
      redirect: { name: 'lead-contact' },
    },
  ],
})

function defaultAuthenticatedPath(isSuperAdmin: boolean): string {
  return isSuperAdmin ? '/admin' : '/'
}

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (
    auth.accessToken
    && (
      !auth.user
      || typeof auth.user.is_superadmin !== 'boolean'
      || typeof auth.user.is_active !== 'boolean'
    )
  ) {
    await auth.bootstrap()
  }

  if (to.matched.some((record) => record.meta.requiresAuth) && !auth.isAuthenticated) {
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.matched.some((record) => record.meta.requiresAuth) && auth.isAuthenticated && auth.user?.is_active === false) {
    await auth.logout()
    return {
      name: 'login',
      query: { redirect: to.fullPath },
    }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return defaultAuthenticatedPath(auth.isSuperAdmin)
  }

  if (to.matched.some((record) => record.meta.requiresSuperAdmin) && !auth.isSuperAdmin) {
    return { name: 'admin-forbidden' }
  }

  if (to.matched.some((record) => record.meta.municipalOnly) && auth.isSuperAdmin) {
    return '/admin'
  }

  return true
})

export default router
