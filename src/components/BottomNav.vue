<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  active: { type: String, default: 'home' },
})

const items = [
  { id: 'home', label: 'Avaleht', icon: 'home' },
  { id: 'locations', label: 'Asukohad', icon: 'pin' },
  { id: 'settings', label: 'Seaded', icon: 'settings' },
]
</script>

<template>
  <nav
    class="grain fixed inset-x-5 bottom-[max(1.5rem,env(safe-area-inset-bottom))] mx-auto flex h-16 max-w-[390px] gap-1 rounded-full p-2 shadow-neu-raised"
    aria-label="Peamenüü"
  >
    <a
      v-for="item in items"
      :key="item.id"
      href="#"
      :aria-current="item.id === active ? 'page' : undefined"
      class="flex flex-1 items-center justify-center gap-2 rounded-full text-[15px] leading-5 text-sage"
      :class="item.id === active ? 'grain shadow-neu-inset' : 'opacity-60'"
    >
      <AppIcon :name="item.icon" :size="20" />
      {{ item.label }}
    </a>
  </nav>
</template>
