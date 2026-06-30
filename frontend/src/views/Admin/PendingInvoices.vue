<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { fetchPendingInvoices } from '@/services/adminApi'
import { useAuthStore } from '@/stores/auth'
import type { PendingInvoice } from '@/types/admin'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const invoices = ref<PendingInvoice[]>([])

const totalAmount = computed((): number =>
  invoices.value.reduce((sum, invoice) => sum + Number(invoice.amount), 0),
)

const formattedTotal = computed((): string =>
  totalAmount.value.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
)

function formatCurrency(value: string): string {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}

function formatDate(value: string): string {
  return new Date(`${value}T00:00:00`).toLocaleDateString('pt-BR')
}

function dueDateClass(dueDate: string): string {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  const due = new Date(`${dueDate}T00:00:00`)

  if (due < today) {
    return 'text-red-700 font-medium'
  }

  const diffDays = Math.ceil((due.getTime() - today.getTime()) / (1000 * 60 * 60 * 24))

  if (diffDays <= 7) {
    return 'text-amber-700 font-medium'
  }

  return 'text-slate-700'
}

async function loadInvoices(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    invoices.value = await fetchPendingInvoices(token)
  } catch {
    errorMessage.value = 'Não foi possível carregar as faturas pendentes.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadInvoices()
})
</script>

<template>
  <section class="space-y-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h2 class="text-2xl font-semibold text-slate-900">Faturas Pendentes</h2>
        <p class="mt-1 text-sm text-slate-500">
          Cobranças em aberto de todos os municípios, ordenadas pelo vencimento mais próximo.
        </p>
      </div>

      <div
        v-if="!loading && invoices.length > 0"
        class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900"
      >
        Total em aberto: <strong>{{ formattedTotal }}</strong>
      </div>
    </div>

    <p
      v-if="errorMessage"
      class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      role="alert"
    >
      {{ errorMessage }}
    </p>

    <div
      v-if="loading"
      class="h-64 animate-pulse rounded-xl bg-slate-200"
      aria-busy="true"
      aria-label="Carregando faturas"
    />

    <div
      v-else-if="invoices.length === 0"
      class="rounded-xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm"
    >
      <p class="text-base font-medium text-slate-900">Nenhuma fatura pendente</p>
      <p class="mt-1 text-sm text-slate-500">
        Todos os municípios estão em dia com os pagamentos.
      </p>
    </div>

    <div
      v-else
      class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
    >
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Município
              </th>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Valor
              </th>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Vencimento
              </th>
              <th scope="col" class="px-4 py-3 text-left font-semibold text-slate-600">
                Status
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="invoice in invoices"
              :key="invoice.id"
              class="hover:bg-slate-50/80"
            >
              <td class="px-4 py-3 font-medium text-slate-900">
                {{ invoice.municipality_name }}
              </td>
              <td class="px-4 py-3 text-slate-700">
                {{ formatCurrency(invoice.amount) }}
              </td>
              <td class="px-4 py-3" :class="dueDateClass(invoice.due_date)">
                {{ formatDate(invoice.due_date) }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-amber-600/20"
                >
                  Pendente
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</template>
