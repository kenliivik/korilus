<script setup>
import { computed } from 'vue'
import AppIcon from './AppIcon.vue'
import BerryGlyph from './BerryGlyph.vue'
import { inSeason, months } from '../data/berries.js'

const month = months[new Date().getMonth()]
// A sprig of something in season fills the empty middle of the sidebar; off-season a lingonberry.
const sprig = computed(() => inSeason()[0] ?? { id: 'pohl' })

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
      :class="item.id === active ? 'grain shadow-neu-inset' : 'opacity-75'"
    >
      <AppIcon :name="item.icon" />
    </RouterLink>
  </nav>

  <!-- Desktop: full-height sidebar flush to the left edge, with labels. Settings sit at the bottom. -->
  <nav class="grain gutter fixed inset-y-0 left-0 hidden w-64 flex-col gap-2 px-6 py-10 shadow-neu-raised lg:flex" aria-label="Peamenüü">
    <RouterLink to="/" class="px-2 font-serif-sc text-[32px] leading-none tracking-wide text-sage">Korilus</RouterLink>
    <span class="mb-8 flex items-center gap-3 px-2 pt-2"><span class="font-hand text-lg text-muted">{{ month }}</span><span class="h-px flex-1 bg-gradient-to-r from-sage/40 to-transparent" /></span>
    <RouterLink
      v-for="item in items"
      :key="item.id"
      :to="item.to"
      :aria-current="item.id === active ? 'page' : undefined"
      class="flex items-center gap-4 rounded-full p-1 pr-4 text-lg text-sage"
      :class="[item.id === active ? '' : 'opacity-75 hover:opacity-100', item.id === 'settings' && 'order-2']"
    >
      <span class="flex size-11 items-center justify-center rounded-full transition-shadow duration-200" :class="item.id === active ? 'grain shadow-neu-inset' : 'hover:shadow-neu-inset'">
        <AppIcon :name="item.icon" />
      </span>
      {{ item.label }}
    </RouterLink>
    <BerryGlyph :name="sprig.id" :image="sprig.image" :size="176" class="order-1 mx-auto mt-auto mb-4 opacity-90" />
  </nav>
</template>
