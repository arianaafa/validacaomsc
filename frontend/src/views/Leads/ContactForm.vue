<script setup lang="ts">
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import { submitLeadRequest } from '@/services/leadsApi'
import { ApiError } from '@/services/httpClient'
import { LEAD_ROLE_OPTIONS, type LeadRequestRole } from '@/types/leads'

const form = reactive({
  name: '',
  email: '',
  phone: '',
  organization_name: '',
  role: '' as LeadRequestRole | '',
  message: '',
})

const loading = ref(false)
const submitted = ref(false)
const errorMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

async function handleSubmit(): Promise<void> {
  if (form.role === '') {
    fieldErrors.value = { role: ['Selecione um cargo.'] }
    return
  }

  loading.value = true
  errorMessage.value = null
  fieldErrors.value = {}

  try {
    await submitLeadRequest({
      name: form.name.trim(),
      email: form.email.trim(),
      phone: form.phone.trim(),
      organization_name: form.organization_name.trim(),
      role: form.role,
      message: form.message.trim() !== '' ? form.message.trim() : undefined,
    })

    submitted.value = true
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      fieldErrors.value = err.errors
    } else {
      errorMessage.value = 'Não foi possível enviar sua solicitação. Tente novamente em instantes.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="min-h-screen w-full bg-slate-50 px-4 py-10">
    <div class="mx-auto flex w-full max-w-5xl flex-col gap-8 lg:flex-row lg:items-start lg:gap-12">
      <aside class="flex flex-col items-center text-center lg:w-[340px] lg:items-start lg:text-left">
        <AuraLogo layout="vertical" :icon-size="96" class="mb-6" />

        <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">
          Instância dedicada
        </p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">
          Solicitar Demonstração
        </h1>
        <p class="mt-4 text-sm leading-relaxed text-slate-600">
          O Audita MSC opera em modelo single-tenant: cada município recebe uma
          instância isolada, segura e configurada para suas competências contábeis.
        </p>

        <ul class="mt-6 space-y-3 text-left text-sm text-slate-600">
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500" />
            Validação automatizada das regras SICONFI (MSC)
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500" />
            Ambiente exclusivo para secretarias de finanças e contabilidade
          </li>
          <li class="flex items-start gap-2">
            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500" />
            Implantação assistida pela equipe técnica
          </li>
        </ul>
      </aside>

      <section class="w-full flex-1 rounded-2xl border border-slate-200/80 bg-white p-8 shadow-sm">
        <div
          v-if="submitted"
          class="flex flex-col items-center gap-4 py-8 text-center"
        >
          <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-600/20"
          >
            <svg
              class="h-8 w-8"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-slate-900">Solicitação enviada!</h2>
          <p class="max-w-md text-sm leading-relaxed text-slate-600">
            Obrigado! Nossa equipe técnica entrará em contato em até 24 horas para
            apresentar a sua instância dedicada.
          </p>
          <RouterLink
            to="/login"
            class="mt-2 inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700"
          >
            Voltar ao login
          </RouterLink>
        </div>

        <template v-else>
          <header class="mb-6">
            <h2 class="text-xl font-semibold text-slate-900">Contato comercial</h2>
            <p class="mt-1 text-sm text-slate-500">
              Preencha os dados institucionais para receber uma apresentação personalizada.
            </p>
          </header>

          <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="handleSubmit">
            <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
              Nome completo
              <input
                v-model="form.name"
                type="text"
                autocomplete="name"
                required
                class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <span v-if="fieldErrors.name?.length" class="text-sm font-medium text-red-600">
                {{ fieldErrors.name[0] }}
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-700">
              E-mail institucional
              <input
                v-model="form.email"
                type="email"
                autocomplete="email"
                required
                placeholder="nome@prefeitura.gov.br"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <span v-if="fieldErrors.email?.length" class="text-sm font-medium text-red-600">
                {{ fieldErrors.email[0] }}
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-700">
              Telefone / WhatsApp
              <input
                v-model="form.phone"
                type="tel"
                autocomplete="tel"
                required
                placeholder="(00) 00000-0000"
                class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <span v-if="fieldErrors.phone?.length" class="text-sm font-medium text-red-600">
                {{ fieldErrors.phone[0] }}
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
              Nome da Prefeitura / Órgão
              <input
                v-model="form.organization_name"
                type="text"
                required
                placeholder="Prefeitura Municipal de ..."
                class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <span
                v-if="fieldErrors.organization_name?.length"
                class="text-sm font-medium text-red-600"
              >
                {{ fieldErrors.organization_name[0] }}
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
              Cargo
              <select
                v-model="form.role"
                required
                class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              >
                <option disabled value="">Selecione o cargo</option>
                <option
                  v-for="option in LEAD_ROLE_OPTIONS"
                  :key="option.value"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
              <span v-if="fieldErrors.role?.length" class="text-sm font-medium text-red-600">
                {{ fieldErrors.role[0] }}
              </span>
            </label>

            <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
              Mensagem (opcional)
              <textarea
                v-model="form.message"
                rows="4"
                placeholder="Conte-nos sobre suas necessidades de validação da MSC..."
                class="w-full resize-y rounded-lg border border-slate-300 px-3.5 py-3 text-base font-normal text-slate-900 outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200"
              />
              <span v-if="fieldErrors.message?.length" class="text-sm font-medium text-red-600">
                {{ fieldErrors.message[0] }}
              </span>
            </label>

            <p
              v-if="errorMessage"
              class="sm:col-span-2 text-sm font-medium text-red-600"
            >
              {{ errorMessage }}
            </p>

            <div class="sm:col-span-2">
              <button
                type="submit"
                :disabled="loading"
                class="w-full rounded-lg bg-indigo-600 px-4 py-3.5 text-base font-semibold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-70"
              >
                {{ loading ? 'Enviando solicitação...' : 'Solicitar demonstração' }}
              </button>
            </div>
          </form>

          <p class="mt-5 text-center text-sm text-slate-500">
            Já possui acesso à sua instância?
            <RouterLink to="/login" class="font-semibold text-indigo-600 hover:underline">
              Fazer login
            </RouterLink>
          </p>
        </template>
      </section>
    </div>
  </div>
</template>

<style scoped>
a {
  text-decoration: none;
}
</style>
