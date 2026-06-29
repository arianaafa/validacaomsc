<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { fetchMscRules } from '@/services/mscRulesApi'
import { useAuthStore } from '@/stores/auth'
import type { MscRule, MscRuleValidationType } from '@/types/mscRules'
import {
  MSC_RULE_VALIDATION_TYPE_CLASSES,
  MSC_RULE_VALIDATION_TYPE_LABELS,
  MSC_RULE_VALIDATION_TYPE_OPTIONS,
} from '@/types/mscRules'

const auth = useAuthStore()

const loading = ref(true)
const errorMessage = ref<string | null>(null)
const rules = ref<MscRule[]>([])
const searchInput = ref('')
const debouncedSearch = ref('')
const selectedValidationType = ref<MscRuleValidationType | ''>('')
const currentPage = ref(1)
const lastPage = ref(1)
const totalItems = ref(0)
const perPage = ref(15)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

const paginationLabel = computed((): string => {
  if (totalItems.value === 0) {
    return 'Nenhuma regra encontrada'
  }

  const from = (currentPage.value - 1) * perPage.value + 1
  const to = Math.min(currentPage.value * perPage.value, totalItems.value)

  return `Exibindo ${from}–${to} de ${totalItems.value} regra${totalItems.value === 1 ? '' : 's'}`
})

const canGoPrevious = computed((): boolean => currentPage.value > 1)

const canGoNext = computed((): boolean => currentPage.value < lastPage.value)

function scheduleDebouncedSearch(value: string): void {
  if (debounceTimer !== null) {
    clearTimeout(debounceTimer)
  }

  debounceTimer = setTimeout((): void => {
    debouncedSearch.value = value
  }, 350)
}

async function loadRules(): Promise<void> {
  const token = auth.accessToken

  if (!token) {
    errorMessage.value = 'Sessão inválida. Faça login novamente.'
    loading.value = false
    return
  }

  loading.value = true
  errorMessage.value = null

  try {
    const response = await fetchMscRules(
      {
        search: debouncedSearch.value,
        validation_type: selectedValidationType.value,
        page: currentPage.value,
        per_page: perPage.value,
      },
      token,
    )

    rules.value = response.data
    currentPage.value = response.current_page
    lastPage.value = response.last_page
    totalItems.value = response.total
    perPage.value = response.per_page
  } catch {
    errorMessage.value = 'Não foi possível carregar as regras. Tente novamente em instantes.'
  } finally {
    loading.value = false
  }
}

function goToPreviousPage(): void {
  if (!canGoPrevious.value) {
    return
  }

  currentPage.value -= 1
}

function goToNextPage(): void {
  if (!canGoNext.value) {
    return
  }

  currentPage.value += 1
}

watch(searchInput, (value: string): void => {
  scheduleDebouncedSearch(value)
})

watch(debouncedSearch, (): void => {
  currentPage.value = 1
  void loadRules()
})

watch(selectedValidationType, (): void => {
  currentPage.value = 1
  void loadRules()
})

watch(currentPage, (): void => {
  void loadRules()
})

onMounted((): void => {
  void loadRules()
})
</script>

<template>
  <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
    <header class="flex flex-col gap-1">
      <p class="text-sm font-medium text-indigo-600">Audita MSC</p>
      <h2 class="text-2xl font-bold tracking-tight text-slate-900">
        Quadro Informativo de Regras
      </h2>
      <p class="text-sm text-slate-500">
        Consulte as regras de validação implementadas no sistema (D1_00018 e D1_00021 a D1_00044).
      </p>
    </header>

    <section class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-sm">
      <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-1.5">
          <label
            for="rules-search"
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Buscar regra
          </label>
          <div class="relative">
            <svg
              class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="11" cy="11" r="8" />
              <path d="m21 21-4.3-4.3" />
            </svg>
            <input
              id="rules-search"
              v-model="searchInput"
              type="search"
              placeholder="Código (ex: D1_00021) ou termos no nome/objetivo"
              class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-700 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
            />
          </div>
        </div>

        <div class="flex flex-col gap-1.5 lg:w-56">
          <label
            for="validation-type-filter"
            class="text-xs font-semibold uppercase tracking-wide text-slate-500"
          >
            Tipo de validação
          </label>
          <select
            id="validation-type-filter"
            v-model="selectedValidationType"
            class="rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
          >
            <option
              v-for="option in MSC_RULE_VALIDATION_TYPE_OPTIONS"
              :key="option.value || 'all'"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          v-for="option in MSC_RULE_VALIDATION_TYPE_OPTIONS.filter((item) => item.value !== '')"
          :key="option.value"
          type="button"
          class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset transition"
          :class="
            selectedValidationType === option.value
              ? MSC_RULE_VALIDATION_TYPE_CLASSES[option.value as MscRuleValidationType]
              : 'bg-slate-50 text-slate-600 ring-slate-200 hover:bg-slate-100'
          "
          @click="selectedValidationType = option.value as MscRuleValidationType"
        >
          {{ option.label }}
        </button>
        <button
          v-if="selectedValidationType !== ''"
          type="button"
          class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-200 transition hover:bg-slate-200"
          @click="selectedValidationType = ''"
        >
          Limpar filtro
        </button>
      </div>
    </section>

    <p
      v-if="errorMessage"
      class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
    >
      {{ errorMessage }}
    </p>

    <section class="rounded-xl border border-slate-200/80 bg-white shadow-sm">
      <div class="border-b border-slate-100 px-5 py-4">
        <h3 class="text-lg font-semibold text-slate-900">Regras implementadas</h3>
        <p class="text-sm text-slate-500">{{ paginationLabel }}</p>
      </div>

      <div v-if="loading" class="p-5">
        <div class="h-64 animate-pulse rounded-lg bg-slate-100" />
      </div>

      <div
        v-else-if="rules.length === 0"
        class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center"
      >
        <svg
          class="h-16 w-16 text-slate-200"
          viewBox="0 0 64 64"
          fill="none"
          aria-hidden="true"
        >
          <rect x="10" y="14" width="44" height="36" rx="4" stroke="currentColor" stroke-width="2" />
          <path d="M18 26h28M18 34h20M18 42h24" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <p class="text-sm text-slate-500">
          Nenhuma regra corresponde aos filtros informados.
        </p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full table-fixed divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50/80">
            <tr>
              <th class="w-[200px] px-5 py-3 text-left font-semibold text-slate-600">
                Código
              </th>
              <th class="whitespace-nowrap px-5 py-3 text-left font-semibold text-slate-600">
                Tipo
              </th>
              <th class="min-w-[180px] px-5 py-3 text-left font-semibold text-slate-600">
                Grupo Alvo
              </th>
              <th class="min-w-[220px] px-5 py-3 text-left font-semibold text-slate-600">
                Objetivo
              </th>
              <th class="whitespace-nowrap px-5 py-3 text-left font-semibold text-slate-600">
                Status
              </th>
              <th class="min-w-[220px] px-5 py-3 text-left font-semibold text-slate-600">
                Mensagem de Erro
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="rule in rules"
              :key="rule.id"
              class="align-top transition-colors hover:bg-slate-50/60"
            >
              <td class="w-[200px] px-5 py-4 align-top">
                <div class="font-mono text-xs font-semibold text-indigo-700">
                  {{ rule.code }}
                </div>
                <div class="mt-1 break-words text-xs font-medium leading-snug text-slate-700">
                  {{ rule.name }}
                </div>
              </td>
              <td class="whitespace-nowrap px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                  :class="MSC_RULE_VALIDATION_TYPE_CLASSES[rule.validation_type]"
                >
                  {{ MSC_RULE_VALIDATION_TYPE_LABELS[rule.validation_type] }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ rule.target_group }}
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ rule.objective }}
              </td>
              <td class="whitespace-nowrap px-5 py-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset"
                  :class="
                    rule.is_implemented
                      ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                      : 'bg-slate-100 text-slate-600 ring-slate-300/30'
                  "
                >
                  {{ rule.is_implemented ? 'Implementada' : 'Não implementada' }}
                </span>
              </td>
              <td class="px-5 py-4 text-slate-600">
                {{ rule.error_message }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="!loading && rules.length > 0 && lastPage > 1"
        class="flex flex-col gap-3 border-t border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <p class="text-sm text-slate-500">{{ paginationLabel }}</p>
        <div class="inline-flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canGoPrevious"
            @click="goToPreviousPage"
          >
            Anterior
          </button>
          <span class="text-xs font-medium text-slate-500">
            Página {{ currentPage }} de {{ lastPage }}
          </span>
          <button
            type="button"
            class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="!canGoNext"
            @click="goToNextPage"
          >
            Próxima
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
