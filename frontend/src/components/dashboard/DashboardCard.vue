<script setup lang="ts">
defineProps<{
  hover?: boolean
  padding?: boolean
}>()
</script>

<template>
  <article
    class="dashboard-card dashboard-fade-in"
    :class="[
      hover ? 'dashboard-card-hover' : '',
      padding ? 'p-6' : '',
    ]"
  >
    <slot />
  </article>
</template>
