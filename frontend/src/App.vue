<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

async function handleLogout(): Promise<void> {
  await auth.logout()
}
</script>

<template>
  <div class="flex min-h-screen w-full flex-col bg-slate-100">
    <header class="topbar">
      <RouterLink to="/" class="brand">validaMSC</RouterLink>

      <nav>
        <template v-if="auth.isAuthenticated">
          <RouterLink to="/msc/import">Importar MSC</RouterLink>
          <span class="user-label">{{ auth.user?.name }}</span>
          <button type="button" class="link-button" @click="handleLogout">
            Sair
          </button>
        </template>
        <template v-else>
          <RouterLink to="/login">Entrar</RouterLink>
          <RouterLink to="/register">Cadastrar</RouterLink>
        </template>
      </nav>
    </header>

    <main class="w-full flex-1">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.5rem;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
}

.brand {
  font-weight: 700;
  color: #0f172a;
  text-decoration: none;
}

nav {
  display: flex;
  align-items: center;
  gap: 1rem;
}

nav a {
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
}

.user-label {
  color: #475569;
  font-size: 0.9375rem;
}

.link-button {
  border: 0;
  background: transparent;
  color: #dc2626;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}
</style>
