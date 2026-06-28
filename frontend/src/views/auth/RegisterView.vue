<script setup lang="ts">
import { reactive } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

async function handleSubmit(): Promise<void> {
  const success = await auth.register({
    name: form.name,
    email: form.email,
    password: form.password,
    password_confirmation: form.password_confirmation,
  })

  if (!success) {
    return
  }

  await router.push('/')
}
</script>

<template>
  <section class="auth-card">
    <header>
      <p class="eyebrow">Audita MSC</p>
      <h1>Criar conta</h1>
      <p class="subtitle">Cadastre-se para usar a plataforma.</p>
    </header>

    <form @submit.prevent="handleSubmit">
      <label>
        Nome
        <input v-model="form.name" type="text" autocomplete="name" required />
        <span v-if="auth.fieldErrors.name?.length" class="field-error">
          {{ auth.fieldErrors.name[0] }}
        </span>
      </label>

      <label>
        E-mail
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
        />
        <span v-if="auth.fieldErrors.email?.length" class="field-error">
          {{ auth.fieldErrors.email[0] }}
        </span>
      </label>

      <label>
        Senha
        <input
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
        />
        <span v-if="auth.fieldErrors.password?.length" class="field-error">
          {{ auth.fieldErrors.password[0] }}
        </span>
      </label>

      <label>
        Confirmar senha
        <input
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          minlength="8"
          required
        />
      </label>

      <p v-if="auth.error" class="form-error">{{ auth.error }}</p>

      <button type="submit" :disabled="auth.loading">
        {{ auth.loading ? 'Cadastrando...' : 'Cadastrar' }}
      </button>
    </form>

    <p class="footer-link">
      Já possui conta?
      <RouterLink to="/login">Fazer login</RouterLink>
    </p>
  </section>
</template>

<style scoped>
.auth-card {
  width: min(100%, 420px);
  margin: 0 auto;
  padding: 2rem;
  border: 1px solid #e2e8f0;
  border-radius: 1rem;
  background: #ffffff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

header {
  margin-bottom: 1.5rem;
}

.eyebrow {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #2563eb;
}

h1 {
  margin: 0;
  font-size: 1.75rem;
}

.subtitle {
  margin: 0.5rem 0 0;
  color: #64748b;
}

form {
  display: grid;
  gap: 1rem;
}

label {
  display: grid;
  gap: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

input {
  padding: 0.75rem 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.625rem;
  font: inherit;
}

input:focus {
  outline: 2px solid #93c5fd;
  border-color: #2563eb;
}

button {
  padding: 0.875rem 1rem;
  border: 0;
  border-radius: 0.625rem;
  background: #2563eb;
  color: #ffffff;
  font: inherit;
  font-weight: 600;
  cursor: pointer;
}

button:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.field-error,
.form-error {
  color: #dc2626;
  font-size: 0.8125rem;
  font-weight: 500;
}

.form-error {
  margin: 0;
}

.footer-link {
  margin: 1.25rem 0 0;
  text-align: center;
  color: #64748b;
}
</style>
