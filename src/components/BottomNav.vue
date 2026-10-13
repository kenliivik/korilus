<script setup>
import AppIcon from './AppIcon.vue'

defineProps({
  active: { type: String, default: 'home' },
})

const items = [
  { id: 'home', label: 'Avaleht', icon: 'home', to: '/' },
  { id: 'calendar', label: 'Kalender', icon: 'calendar', to: '/kalender' },
  { id: 'recipes', label: 'Retseptid', icon: 'book', to: '/retseptid' },
  { id: 'locations', label: 'Asukohad', icon: 'pin', to: '/asukohad' },
  { id: 'settings', label: 'Seaded', icon: 'settings', to: '/seaded' },
]
</script>

<template>
  <nav
    class="grain fixed inset-x-5 bottom-[max(1.5rem,env(safe-area-inset-bottom))] mx-auto flex h-16 max-w-[390px] gap-1 rounded-full p-2 shadow-neu-raised lg:inset-x-auto lg:top-1/2 lg:bottom-auto lg:left-6 lg:h-auto lg:w-20 lg:-translate-y-1/2 lg:flex-col lg:gap-3 lg:py-3"
    aria-label="Peamenüü"
  >
    <RouterLink
      v-for="item in items"
      :key="item.id"
      :to="item.to"
      :aria-label="item.label"
      :aria-current="item.id === active ? 'page' : undefined"
      class="flex flex-1 items-center justify-center rounded-full text-sage lg:h-14 lg:flex-none"
      :class="item.id === active ? 'grain shadow-neu-inset' : 'opacity-60'"
    >
      <AppIcon :name="item.icon" />
    </RouterLink>
  </nav>
</template>
