<script setup lang="ts">
import { RouterLink } from 'vue-router'

defineProps<{
  to: string
  label: string
  collapsed?: boolean
  exact?: boolean
  activeClass?: string
}>()

const emit = defineEmits<{
  navigate: []
}>()
</script>

<template>
  <li class="sidebar-nav-item relative">
    <RouterLink
      :to="to"
      :exact-active-class="exact ? activeClass : ''"
      :active-class="exact ? '' : activeClass"
      class="nav-link group flex items-center gap-3 rounded-lg py-2 text-sm font-medium text-slate-400 transition-all duration-200 hover:bg-slate-800/70 hover:text-slate-100"
      :class="collapsed ? 'justify-center px-2' : 'px-3'"
      @click="emit('navigate')"
    >
      <span class="shrink-0 text-slate-400 transition-colors group-hover:text-slate-200 [&>svg]:h-[18px] [&>svg]:w-[18px]">
        <slot name="icon" />
      </span>
      <span
        v-if="!collapsed"
        class="truncate transition-opacity duration-200"
      >
        {{ label }}
      </span>
    </RouterLink>

    <span
      v-if="collapsed"
      class="sidebar-tooltip pointer-events-none absolute left-full top-1/2 z-50 ml-3 -translate-y-1/2 whitespace-nowrap rounded-md border border-slate-700 bg-slate-800 px-2.5 py-1 text-xs font-medium text-slate-100 opacity-0 shadow-xl transition-opacity duration-150"
      role="tooltip"
    >
      {{ label }}
    </span>
  </li>
</template>

<style scoped>
.sidebar-nav-item:hover .sidebar-tooltip {
  opacity: 1;
}

.nav-link {
  color: inherit;
  text-decoration: none;
}
</style>
