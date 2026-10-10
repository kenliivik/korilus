<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  label: { type: String, required: true },
  icon: { type: String, required: true },
  href: { type: String, default: '#' },
})
</script>

<template>
  <a
    :href="href"
    class="grain flex items-center gap-4 rounded-card p-4 text-sage shadow-neu-raised transition-shadow active:shadow-neu-inset"
  >
    <span class="grain grid size-11 shrink-0 place-items-center rounded-full shadow-neu-inset">
      <AppIcon :name="icon" />
    </span>
    <span class="flex-1 text-xl leading-6">{{ label }}</span>
    <span class="grain grid size-9 shrink-0 place-items-center rounded-full shadow-neu-raised-sm">
      <AppIcon name="arrow" :size="18" />
    </span>
  </a>
</template>
