<script setup lang="ts">
import { computed } from 'vue'
import logoLightSrc from '@/assets/aura-tech-logo.png'
import logoDarkSrc from '@/assets/aura-tech-logo-dark.png'
import iconSrc from '@/assets/aura-tech-icon.png'

const props = withDefaults(
  defineProps<{
    iconSize?: number | string
    dark?: boolean
    layout?: 'horizontal' | 'vertical'
    iconOnly?: boolean
  }>(),
  {
    iconSize: 36,
    dark: false,
    layout: 'horizontal',
    iconOnly: false,
  },
)

const logoSrc = computed(() => {
  if (props.iconOnly) {
    return iconSrc
  }

  return props.dark ? logoDarkSrc : logoLightSrc
})

const logoHeight = computed(() => {
  const size = Number(props.iconSize)

  if (props.iconOnly) {
    return size
  }

  if (props.layout === 'vertical') {
    return Math.round(size * 1.45)
  }

  return Math.round(size * 1.15)
})
</script>

<template>
  <img
    :src="logoSrc"
    alt="Aura Tech"
    class="block h-auto w-auto max-w-full shrink-0 object-contain"
    :style="{ height: `${logoHeight}px` }"
    decoding="async"
  />
</template>
