<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

export interface BreadcrumbItem {
  label: string
  to?: string
}

const props = defineProps<{
  items?: BreadcrumbItem[]
}>()

const route = useRoute()

const resolvedItems = computed((): BreadcrumbItem[] => {
  if (props.items?.length) {
    return props.items
  }

  const crumbs: BreadcrumbItem[] = []
  const parent = route.meta.breadcrumbParent

  if (typeof parent === 'string') {
    crumbs.push({ label: parent })
  } else if (parent && typeof parent === 'object' && 'label' in parent) {
    const parentMeta = parent as { label: string; to?: string }
    crumbs.push({
      label: parentMeta.label,
      to: parentMeta.to,
    })
  }

  const title = route.meta.title
  if (typeof title === 'string') {
    crumbs.push({ label: title })
  }

  return crumbs
})
</script>

<template>
  <nav
    v-if="resolvedItems.length"
    aria-label="Breadcrumb"
    class="flex items-center gap-1.5 text-sm"
  >
    <template v-for="(item, index) in resolvedItems" :key="`${item.label}-${index}`">
      <svg
        v-if="index > 0"
        class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-slate-600"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        aria-hidden="true"
      >
        <path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round" />
      </svg>

      <RouterLink
        v-if="item.to && index < resolvedItems.length - 1"
        :to="item.to"
        class="font-medium text-slate-500 transition-colors hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
      >
        {{ item.label }}
      </RouterLink>
      <span
        v-else
        class="font-medium"
        :class="index === resolvedItems.length - 1
          ? 'text-slate-900 dark:text-white'
          : 'text-slate-500 dark:text-slate-400'"
      >
        {{ item.label }}
      </span>
    </template>
  </nav>
</template>
