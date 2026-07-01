<script setup lang="ts">
import { nextTick, reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AuraLogo from '@/components/brand/AuraLogo.vue'
import DashboardMockup from '@/components/leads/DashboardMockup.vue'
import LeadIcon from '@/components/leads/LeadIcon.vue'
import { submitLeadRequest } from '@/services/leadsApi'
import { ApiError } from '@/services/httpClient'
import { LEAD_ROLE_OPTIONS, type LeadRequestRole } from '@/types/leads'

type FormField = 'name' | 'email' | 'phone' | 'organization_name' | 'cnpj' | 'ibge_code' | 'role'

const benefits = [
  { icon: 'shield' as const, title: 'Validação conforme o SICONFI' },
  { icon: 'chart' as const, title: 'Identificação automática de inconsistências' },
  { icon: 'zap' as const, title: 'Diagnóstico rápido antes do envio' },
  { icon: 'file' as const, title: 'Compatível com PCASP' },
  { icon: 'landmark' as const, title: 'Desenvolvido para órgãos públicos' },
  { icon: 'clock' as const, title: 'Retorno rápido para demonstração' },
]

const form = reactive({
  name: '',
  email: '',
  phone: '',
  organization_name: '',
  cnpj: '',
  ibge_code: '',
  role: '' as LeadRequestRole | '',
  message: '',
})

const touched = reactive<Record<FormField, boolean>>({
  name: false,
  email: false,
  phone: false,
  organization_name: false,
  cnpj: false,
  ibge_code: false,
  role: false,
})

const loading = ref(false)
const submitted = ref(false)
const errorMessage = ref<string | null>(null)
const fieldErrors = ref<Record<string, string[]>>({})

const requiredFields: FormField[] = [
  'name',
  'email',
  'phone',
  'organization_name',
  'cnpj',
  'ibge_code',
  'role',
]

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function stripDigits(value: string): string {
  return value.replace(/\D/g, '')
}

function inputStateClass(hasError: boolean, isValid = false, withIcon = true): string {
  const padding = withIcon ? 'pl-11 pr-4' : 'px-4'
  const base = `w-full h-12 rounded-xl border bg-white text-[15px] font-normal text-slate-900 outline-none transition-all duration-200 ${padding}`

  if (hasError) {
    return `${base} border-red-400 focus:border-red-500 focus:shadow-[0_0_0_4px_rgba(239,68,68,0.12)]`
  }

  if (isValid) {
    return `${base} border-emerald-400 pr-11 focus:border-aura-sky focus:shadow-[0_0_0_4px_rgba(14,165,233,0.12)]`
  }

  return `${base} border-slate-200 hover:border-slate-300 focus:border-aura-sky focus:shadow-[0_0_0_4px_rgba(14,165,233,0.12)]`
}

function textareaStateClass(hasError: boolean): string {
  const base =
    'w-full min-h-[120px] resize-y rounded-xl border bg-white py-3 pl-11 pr-4 text-[15px] font-normal text-slate-900 outline-none transition-all duration-200'

  if (hasError) {
    return `${base} border-red-400 focus:border-red-500 focus:shadow-[0_0_0_4px_rgba(239,68,68,0.12)]`
  }

  return `${base} border-slate-200 hover:border-slate-300 focus:border-aura-sky focus:shadow-[0_0_0_4px_rgba(14,165,233,0.12)]`
}

function markTouched(field: FormField): void {
  touched[field] = true
}

function fieldMessage(field: FormField): string | null {
  const serverError = fieldErrors.value[field]?.[0]
  if (serverError) {
    return serverError
  }

  if (!touched[field]) {
    return null
  }

  switch (field) {
    case 'name':
      if (!form.name.trim()) return 'Informe seu nome completo.'
      break
    case 'email':
      if (!form.email.trim()) return 'Informe um e-mail institucional.'
      if (!emailPattern.test(form.email.trim())) return 'Informe um e-mail válido.'
      break
    case 'phone':
      if (!form.phone.trim()) return 'Informe um telefone para contato.'
      break
    case 'organization_name':
      if (!form.organization_name.trim()) return 'Informe o nome da prefeitura ou órgão.'
      break
    case 'cnpj': {
      const cnpjDigits = stripDigits(form.cnpj)
      if (!cnpjDigits) return 'Informe o CNPJ do município ou órgão.'
      if (cnpjDigits.length !== 14) return 'Informe um CNPJ válido com 14 dígitos.'
      break
    }
    case 'ibge_code': {
      const ibgeDigits = stripDigits(form.ibge_code)
      if (!ibgeDigits) return 'Informe o código IBGE do município.'
      if (ibgeDigits.length !== 7) return 'Informe um código IBGE válido com 7 dígitos.'
      break
    }
    case 'role':
      if (form.role === '') return 'Selecione um cargo.'
      break
  }

  return null
}

function fieldHasError(field: FormField): boolean {
  return fieldMessage(field) !== null
}

function fieldIsValid(field: FormField): boolean {
  if (!touched[field] || fieldHasError(field)) return false

  switch (field) {
    case 'name':
      return form.name.trim() !== ''
    case 'email':
      return emailPattern.test(form.email.trim())
    case 'phone':
      return form.phone.trim() !== ''
    case 'organization_name':
      return form.organization_name.trim() !== ''
    case 'cnpj':
      return stripDigits(form.cnpj).length === 14
    case 'ibge_code':
      return stripDigits(form.ibge_code).length === 7
    case 'role':
      return form.role !== ''
    default:
      return false
  }
}

function isLeadRequestRole(value: LeadRequestRole | ''): value is LeadRequestRole {
  return value !== ''
}

function fieldErrorId(field: FormField): string {
  return `lead-${field}-error`
}

async function focusFirstInvalidField(): Promise<void> {
  await nextTick()
  document
    .querySelector<HTMLElement>('[data-lead-field][aria-invalid="true"]')
    ?.focus({ preventScroll: true })
  document
    .querySelector('[data-lead-field][aria-invalid="true"]')
    ?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

async function handleSubmit(): Promise<void> {
  for (const field of requiredFields) {
    touched[field] = true
  }

  if (
    fieldHasError('name') ||
    fieldHasError('email') ||
    fieldHasError('phone') ||
    fieldHasError('organization_name') ||
    fieldHasError('cnpj') ||
    fieldHasError('ibge_code') ||
    fieldHasError('role') ||
    !isLeadRequestRole(form.role)
  ) {
    await focusFirstInvalidField()
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
      cnpj: stripDigits(form.cnpj),
      ibge_code: stripDigits(form.ibge_code),
      role: form.role,
      message: form.message.trim() !== '' ? form.message.trim() : undefined,
    })

    submitted.value = true
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      fieldErrors.value = err.errors
      await focusFirstInvalidField()
    } else {
      errorMessage.value = 'Não foi possível enviar sua solicitação. Tente novamente em instantes.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="lead-page relative min-h-screen overflow-hidden bg-gradient-to-br from-aura-bg-start to-aura-bg-end">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
      <div class="absolute -right-20 -top-20 h-80 w-80 rounded-full bg-aura-cyan/10 blur-3xl" />
      <div class="absolute -bottom-16 -left-16 h-72 w-72 rounded-full bg-aura-blue/10 blur-3xl" />
      <div
        class="absolute right-1/4 top-1/3 h-px w-32 rotate-45 bg-gradient-to-r from-transparent via-aura-blue/20 to-transparent"
      />
      <div
        class="absolute bottom-1/4 left-1/3 h-24 w-24 rounded-full border border-aura-navy/5"
      />
    </div>

    <div
      class="relative mx-auto grid w-full max-w-7xl gap-8 px-4 py-8 md:px-6 md:py-10 lg:grid-cols-5 lg:gap-10 lg:py-12 xl:gap-14 xl:px-8"
    >
      <!-- Coluna institucional (~40%) -->
      <aside
        class="lead-fade-in flex flex-col lg:col-span-2 lg:px-4 xl:px-6"
      >
        <AuraLogo layout="vertical" :icon-size="88" class="mb-8 self-center lg:self-start" />

        <p class="text-center text-xs font-semibold uppercase tracking-[0.14em] text-aura-blue lg:text-left">
          Auditoria MSC
        </p>
        <p class="mt-2 text-center text-[13px] font-medium text-slate-500 lg:text-left">
          Audita MSC · Aura Tech
        </p>

        <h1
          class="mt-4 text-center text-[26px] font-bold leading-tight tracking-tight text-slate-900 sm:text-[32px] lg:text-left"
        >
          Validação Inteligente da Matriz de Saldos Contábeis
        </h1>

        <p class="mt-4 text-center text-[15px] leading-relaxed text-slate-600 lg:text-left">
          Automatize a validação das informações antes do envio ao SICONFI, identificando
          inconsistências de forma rápida, segura e conforme as regras do Tesouro Nacional.
        </p>

        <ul class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
          <li
            v-for="benefit in benefits"
            :key="benefit.title"
            class="lead-card-hover flex items-center gap-3 rounded-xl border border-white/70 bg-white/50 p-3.5 backdrop-blur-sm transition duration-200"
          >
            <span
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-aura-primary/10 to-aura-sky/10 text-aura-primary"
            >
              <LeadIcon :name="benefit.icon" />
            </span>
            <span class="text-[13px] font-medium leading-snug text-slate-700">{{ benefit.title }}</span>
          </li>
        </ul>

        <div class="lead-slide-up mt-8 hidden lg:block">
          <DashboardMockup />
        </div>
      </aside>

      <!-- Coluna formulário (~60%) -->
      <section
        class="lead-slide-up lg:col-span-3"
        style="animation-delay: 80ms"
      >
        <div
          class="rounded-[24px] border border-white/80 bg-white/95 p-6 shadow-[0_20px_60px_-15px_rgba(0,64,128,0.12)] backdrop-blur-sm sm:p-8 lg:p-10"
        >
          <!-- Sucesso -->
          <div
            v-if="submitted"
            class="mx-auto flex max-w-lg flex-col items-center gap-5 py-10 text-center"
          >
            <div
              class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-600/20"
            >
              <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
              </svg>
            </div>
            <h2 class="text-[32px] font-bold text-slate-900">Solicitação enviada!</h2>
            <p class="max-w-md text-[15px] leading-relaxed text-slate-600">
              Obrigado pelo interesse. Nossa equipe entrará em contato em até 24 horas úteis para
              agendar uma demonstração do Audita MSC.
            </p>
            <RouterLink
              to="/login"
              class="lead-btn-hover inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-aura-primary to-aura-sky px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-aura-primary/25 transition duration-200"
            >
              ← Voltar para o login
            </RouterLink>
          </div>

          <!-- Formulário -->
          <div v-else>
            <header class="border-b border-slate-100 pb-6">
              <div class="max-w-md">
                <h2 class="text-[32px] font-bold leading-tight tracking-tight text-slate-900">
                  Solicite uma demonstração
                </h2>
                <p class="mt-2 text-lg text-slate-600">
                  Receba uma apresentação personalizada da plataforma Aura Tech.
                </p>
              </div>
            </header>

            <p class="mt-4 text-[13px] text-slate-400">
              Campos com <span class="text-red-500">*</span> são obrigatórios.
            </p>

            <form
              class="mt-6 space-y-8"
              novalidate
              aria-labelledby="lead-form-title"
              @submit.prevent="handleSubmit"
            >
              <span id="lead-form-title" class="sr-only">Formulário de solicitação de demonstração</span>

              <!-- Seus dados -->
              <fieldset class="min-w-0 border-0 p-0">
                <legend class="mb-4 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                  Seus dados
                </legend>
                <div class="grid gap-4 sm:grid-cols-2">
                  <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
                    <span>Nome completo <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="user" />
                      </span>
                      <input
                        v-model="form.name"
                        data-lead-field
                        type="text"
                        autocomplete="name"
                        required
                        placeholder="Ex.: Maria da Silva"
                        :class="inputStateClass(fieldHasError('name'))"
                        :aria-invalid="fieldHasError('name')"
                        :aria-describedby="fieldMessage('name') ? fieldErrorId('name') : undefined"
                        @blur="markTouched('name')"
                      />
                    </div>
                    <span v-if="fieldMessage('name')" :id="fieldErrorId('name')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('name') }}
                    </span>
                  </label>

                  <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>E-mail institucional <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="mail" />
                      </span>
                      <input
                        v-model="form.email"
                        data-lead-field
                        type="email"
                        autocomplete="email"
                        required
                        placeholder="nome@prefeitura.gov.br"
                        :class="inputStateClass(fieldHasError('email'), fieldIsValid('email'))"
                        :aria-invalid="fieldHasError('email')"
                        :aria-describedby="fieldMessage('email') ? fieldErrorId('email') : undefined"
                        @blur="markTouched('email')"
                      />
                      <span
                        v-if="fieldIsValid('email')"
                        class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-emerald-500"
                      >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path stroke-linecap="round" stroke-linejoin="round" d="M20 6 9 17l-5-5" />
                        </svg>
                      </span>
                    </div>
                    <span v-if="fieldMessage('email')" :id="fieldErrorId('email')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('email') }}
                    </span>
                  </label>

                  <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>Telefone / WhatsApp <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="phone" />
                      </span>
                      <input
                        v-model="form.phone"
                        data-lead-field
                        type="tel"
                        autocomplete="tel"
                        required
                        placeholder="(61) 99999-9999"
                        :class="inputStateClass(fieldHasError('phone'))"
                        :aria-invalid="fieldHasError('phone')"
                        :aria-describedby="fieldMessage('phone') ? fieldErrorId('phone') : undefined"
                        @blur="markTouched('phone')"
                      />
                    </div>
                    <span v-if="fieldMessage('phone')" :id="fieldErrorId('phone')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('phone') }}
                    </span>
                  </label>
                </div>
              </fieldset>

              <!-- Instituição -->
              <fieldset class="min-w-0 border-0 p-0">
                <legend class="mb-4 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                  Instituição
                </legend>
                <div class="grid gap-4 sm:grid-cols-2">
                  <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
                    <span>Nome da Prefeitura / Órgão <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="building" />
                      </span>
                      <input
                        v-model="form.organization_name"
                        data-lead-field
                        type="text"
                        required
                        placeholder="Prefeitura Municipal de Campinas"
                        :class="inputStateClass(fieldHasError('organization_name'))"
                        :aria-invalid="fieldHasError('organization_name')"
                        :aria-describedby="fieldMessage('organization_name') ? fieldErrorId('organization_name') : undefined"
                        @blur="markTouched('organization_name')"
                      />
                    </div>
                    <span v-if="fieldMessage('organization_name')" :id="fieldErrorId('organization_name')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('organization_name') }}
                    </span>
                  </label>

                  <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>CNPJ <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="hash" />
                      </span>
                      <input
                        v-model="form.cnpj"
                        data-lead-field
                        type="text"
                        inputmode="numeric"
                        required
                        placeholder="00.000.000/0000-00"
                        maxlength="18"
                        :class="inputStateClass(fieldHasError('cnpj'))"
                        :aria-invalid="fieldHasError('cnpj')"
                        :aria-describedby="fieldMessage('cnpj') ? fieldErrorId('cnpj') : undefined"
                        @blur="markTouched('cnpj')"
                      />
                    </div>
                    <span v-if="fieldMessage('cnpj')" :id="fieldErrorId('cnpj')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('cnpj') }}
                    </span>
                  </label>

                  <label class="grid gap-2 text-sm font-semibold text-slate-700">
                    <span>Código IBGE do município <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="map-pin" />
                      </span>
                      <input
                        v-model="form.ibge_code"
                        data-lead-field
                        type="text"
                        inputmode="numeric"
                        required
                        placeholder="3509502"
                        maxlength="7"
                        :class="inputStateClass(fieldHasError('ibge_code'))"
                        :aria-invalid="fieldHasError('ibge_code')"
                        :aria-describedby="fieldMessage('ibge_code') ? fieldErrorId('ibge_code') : undefined"
                        @blur="markTouched('ibge_code')"
                      />
                    </div>
                    <span v-if="fieldMessage('ibge_code')" :id="fieldErrorId('ibge_code')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('ibge_code') }}
                    </span>
                  </label>

                  <label class="grid gap-2 text-sm font-semibold text-slate-700 sm:col-span-2">
                    <span>Cargo <span class="text-red-500" aria-hidden="true">*</span></span>
                    <div class="relative">
                      <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                        <LeadIcon name="briefcase" />
                      </span>
                      <select
                        v-model="form.role"
                        data-lead-field
                        required
                        :class="inputStateClass(fieldHasError('role'))"
                        :aria-invalid="fieldHasError('role')"
                        :aria-describedby="fieldMessage('role') ? fieldErrorId('role') : undefined"
                        @blur="markTouched('role')"
                      >
                        <option disabled value="">Selecione o cargo</option>
                        <option v-for="option in LEAD_ROLE_OPTIONS" :key="option.value" :value="option.value">
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                    <span v-if="fieldMessage('role')" :id="fieldErrorId('role')" class="text-[13px] font-normal text-red-600" role="alert">
                      {{ fieldMessage('role') }}
                    </span>
                  </label>
                </div>
              </fieldset>

              <!-- Mensagem -->
              <fieldset class="min-w-0 border-0 p-0">
                <legend class="mb-4 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                  Informações adicionais
                </legend>
                <label class="grid gap-2 text-sm font-semibold text-slate-700">
                  <span>Mensagem <span class="text-xs font-normal text-slate-400">(opcional)</span></span>
                  <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-3.5 text-slate-400">
                      <LeadIcon name="message" />
                    </span>
                    <textarea
                      v-model="form.message"
                      data-lead-field
                      rows="4"
                      placeholder="Conte um pouco sobre suas necessidades..."
                      :class="textareaStateClass(Boolean(fieldErrors.message?.length))"
                      :aria-invalid="Boolean(fieldErrors.message?.length)"
                    />
                  </div>
                  <span v-if="fieldErrors.message?.length" class="text-[13px] font-normal text-red-600" role="alert">
                    {{ fieldErrors.message[0] }}
                  </span>
                </label>
              </fieldset>

              <div
                v-if="errorMessage"
                class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700"
                role="alert"
              >
                {{ errorMessage }}
              </div>

              <div class="border-t border-slate-100 pt-6">
                <button
                  type="submit"
                  :disabled="loading"
                  class="lead-btn-hover flex w-full items-center justify-center gap-2 rounded-xl border-0 bg-gradient-to-r from-aura-primary to-aura-sky px-4 py-3.5 text-base font-semibold text-white shadow-lg shadow-aura-primary/25 transition duration-200 disabled:cursor-not-allowed disabled:opacity-70 disabled:shadow-none"
                >
                  <svg v-if="loading" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                  </svg>
                  <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M22 2 11 13" /><path d="M22 2 15 22 11 13 2 9 22 2" />
                  </svg>
                  {{ loading ? 'Enviando solicitação...' : 'Solicitar demonstração' }}
                </button>
              </div>
            </form>

            <footer class="mt-8 space-y-3 border-t border-slate-100 pt-6 text-center">
              <RouterLink
                to="/login"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-aura-primary transition duration-200 hover:text-aura-navy"
              >
                ← Voltar para o login
              </RouterLink>
              <p class="text-[13px] leading-relaxed text-slate-400">
                Seus dados são utilizados apenas para contato comercial, em conformidade com a LGPD.
              </p>
            </footer>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
@keyframes lead-fade-in {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes lead-slide-up {
  from {
    opacity: 0;
    transform: translateY(16px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.lead-fade-in {
  animation: lead-fade-in 0.2s ease-out both;
}

.lead-slide-up {
  animation: lead-slide-up 0.2s ease-out both;
}

.lead-card-hover:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px -12px rgba(0, 64, 128, 0.15);
  border-color: rgba(14, 165, 233, 0.2);
}

.lead-btn-hover:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 28px -8px rgba(30, 64, 175, 0.35);
}

.lead-btn-hover:not(:disabled):active {
  transform: translateY(0);
}
</style>
