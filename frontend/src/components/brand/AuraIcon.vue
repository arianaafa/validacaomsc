<script setup lang="ts">
import { useId } from 'vue'

interface AuraIconProps {
  size?: number | string
}

withDefaults(defineProps<AuraIconProps>(), {
  size: 36,
})

const gradientId = `auraGradientIcon-${useId()}`
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 600 500"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    role="img"
    aria-label="Aura Tech"
    class="shrink-0 overflow-visible"
  >
    <defs>
      <linearGradient
        :id="gradientId"
        x1="50"
        y1="420"
        x2="450"
        y2="40"
        gradientUnits="userSpaceOnUse"
      >
        <stop offset="0%" stop-color="#004080" />
        <stop offset="65%" stop-color="#008CC4" />
        <stop offset="100%" stop-color="#00C8E6" />
      </linearGradient>
    </defs>

    <g transform="translate(10, 10)">
      <path
        d="M185 410 L290 232 C310 198 335 150 320 110 C305 70 260 70 230 100 L65 300 L65 350 L120 300 L215 190 C230 172 250 150 265 165 C280 180 255 215 240 235 L125 410 Z"
        :fill="`url(#${gradientId})`"
      />
      <path
        d="M245 60 C290 60 345 90 380 145 L435 235 C460 275 440 330 395 330 C365 330 345 305 330 280 L260 165 C280 140 310 115 285 95 C265 80 235 105 220 120 L160 195 L125 150 Z"
        :fill="`url(#${gradientId})`"
      />
      <path
        d="M150 330 C200 270 260 250 320 275 C370 295 410 260 390 210 C375 180 340 185 320 205 C260 260 190 265 140 310 Z"
        :fill="`url(#${gradientId})`"
      />
    </g>
  </svg>
</template>
