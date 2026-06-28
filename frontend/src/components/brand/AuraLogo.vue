<script setup lang="ts">
import { computed } from 'vue'
import AuraIcon from '@/components/brand/AuraIcon.vue'

const props = withDefaults(
  defineProps<{
    iconSize?: number | string
    dark?: boolean
    layout?: 'horizontal' | 'vertical'
  }>(),
  {
    iconSize: 36,
    dark: false,
    layout: 'horizontal',
  },
)

const textClass = computed(() =>
  props.dark
    ? 'font-brand text-zinc-100'
    : 'font-brand text-[#004080]',
)

const containerClass = computed(() =>
  props.layout === 'vertical'
    ? 'flex flex-col items-center gap-4'
    : 'flex items-center gap-3',
)

const textSizeClass = computed(() =>
  props.layout === 'vertical'
    ? 'text-xl sm:text-2xl'
    : 'text-sm sm:text-base',
)
</script>

<template>
  <div :class="containerClass">
    <AuraIcon :size="iconSize" />
    <span
      :class="[
        textClass,
        textSizeClass,
        'font-extrabold uppercase leading-none tracking-[0.14em]',
      ]"
    >
      AURA TECH
    </span>
  </div>
</template>

<style scoped>
.font-brand {
  font-family:
    Montserrat,
    Inter,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;
}
</style>
