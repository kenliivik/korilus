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
  <!-- Phone and tablet: icon-only pill at the bottom. -->
  <nav
    class="grain fixed inset-x-5 bottom-[max(1.5rem,env(safe-area-inset-bottom))] mx-auto flex h-16 max-w-[390px] gap-1 rounded-full p-2 shadow-neu-raised lg:hidden"
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

  <!-- Desktop: full-height sidebar flush to the left edge, with labels. Settings sit at the bottom. -->
  <nav class="grain fixed inset-y-0 left-0 hidden w-64 flex-col gap-2 px-6 py-10 shadow-neu-raised lg:flex" aria-label="Peamenüü">
    <RouterLink to="/" class="mb-10 px-2 font-serif-sc text-[32px] leading-none tracking-wide text-sage">Korilus</RouterLink>
    <RouterLink
      v-for="item in items"
      :key="item.id"
      :to="item.to"
      :aria-current="item.id === active ? 'page' : undefined"
      class="flex items-center gap-4 rounded-full p-1 pr-4 text-lg text-sage"
      :class="[item.id === active ? '' : 'opacity-70 hover:opacity-100', item.id === 'settings' && 'mt-auto']"
    >
      <span class="flex size-11 items-center justify-center rounded-full" :class="item.id === active && 'grain shadow-neu-inset'">
        <AppIcon :name="item.icon" />
      </span>
      {{ item.label }}
    </RouterLink>
  </nav>
</template>
