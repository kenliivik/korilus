<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  active: { type: String, default: 'home' },
})

const items = [
  { id: 'home', label: 'Avaleht', icon: 'home', to: '/' },
  { id: 'locations', label: 'Asukohad', icon: 'pin', to: '/asukohad' },
  { id: 'settings', label: 'Seaded', icon: 'settings', to: '/seaded' },
]
</script>

<template>
  <nav
    class="grain fixed inset-x-5 bottom-[max(1.5rem,env(safe-area-inset-bottom))] mx-auto flex h-16 max-w-[390px] gap-1 rounded-full p-2 shadow-neu-raised"
    aria-label="Peamenüü"
  >
    <RouterLink
      v-for="item in items"
      :key="item.id"
      :to="item.to"
      :aria-label="item.label"
      :aria-current="item.id === active ? 'page' : undefined"
      class="flex flex-1 items-center justify-center rounded-full text-sage"
      :class="item.id === active ? 'grain shadow-neu-inset' : 'opacity-60'"
    >
      <AppIcon :name="item.icon" />
    </RouterLink>
  </nav>
</template>
