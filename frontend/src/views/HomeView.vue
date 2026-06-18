<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { fetchHealth } from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const apiLoading = ref(true)
const apiError = ref<string | null>(null)
const apiHealth = ref<{ status: string; app: string; database: string } | null>(null)

onMounted(async () => {
  try {
    apiHealth.value = await fetchHealth()
  } catch (err) {
    apiError.value = err instanceof Error ? err.message : 'Falha ao conectar com a API'
  } finally {
    apiLoading.value = false
  }
})
</script>

<template>
  <main class="home">
    <section class="hero">
      <p class="eyebrow">validaMSC</p>
      <h1>Backend Laravel + Frontend Vue 3</h1>
      <p class="subtitle">
        Stack containerizada com Docker Compose e PostgreSQL.
      </p>
    </section>

    <section v-if="auth.user" class="status-card">
      <h2>Sessão ativa</h2>
      <dl>
        <div>
          <dt>Nome</dt>
          <dd>{{ auth.user.name }}</dd>
        </div>
        <div>
          <dt>E-mail</dt>
          <dd>{{ auth.user.email }}</dd>
        </div>
        <div v-if="auth.expiresAt">
          <dt>Token expira em</dt>
          <dd>{{ new Date(auth.expiresAt).toLocaleString() }}</dd>
        </div>
      </dl>
    </section>

    <section class="status-card">
      <h2>Status da API</h2>

      <p v-if="apiLoading">Verificando conexão...</p>
      <p v-else-if="apiError" class="error">{{ apiError }}</p>
      <dl v-else-if="apiHealth">
        <div>
          <dt>Status</dt>
          <dd>{{ apiHealth.status }}</dd>
        </div>
        <div>
          <dt>Aplicação</dt>
          <dd>{{ apiHealth.app }}</dd>
        </div>
        <div>
          <dt>Banco</dt>
          <dd>{{ apiHealth.database }}</dd>
        </div>
      </dl>
    </section>
  </main>
</template>

<style scoped>
.home {
  display: grid;
  gap: 2rem;
  max-width: 720px;
  margin: 0 auto;
  padding: 3rem 1.5rem;
}

.hero {
  display: grid;
  gap: 0.75rem;
}

.eyebrow {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2563eb;
}

h1 {
  margin: 0;
  font-size: clamp(2rem, 4vw, 2.75rem);
}

.subtitle {
  margin: 0;
  color: #475569;
  line-height: 1.6;
}

.status-card {
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  background: #f8fafc;
}

.status-card h2 {
  margin: 0 0 1rem;
  font-size: 1.125rem;
}

dl {
  display: grid;
  gap: 0.75rem;
  margin: 0;
}

dl div {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
}

dt {
  color: #64748b;
}

dd {
  margin: 0;
  font-weight: 600;
}

.error {
  margin: 0;
  color: #dc2626;
}
</style>
