<script setup>
import { computed } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import StarRating from '../components/StarRating.vue'
import { findProduce } from '../data/berries.js'
import { store, berriesAt, averageRating } from '../data/store.js'

// Offline sketch map: saved spots plotted from their coordinates, no map tiles (no outside requests).
const pins = computed(() => {
  const located = store.locations.filter((l) => l.lat != null)
  if (!located.length) return []
  const lats = located.map((l) => l.lat)
  const lngs = located.map((l) => l.lng)
  const [minLat, minLng] = [Math.min(...lats), Math.min(...lngs)]
  const span = Math.max(Math.max(...lats) - minLat, (Math.max(...lngs) - minLng) / 2, 0.005)
  return located.map((l) => ({
    ...l,
    x: 20 + ((l.lng - minLng) / 2 / span) * 60,
    y: 85 - ((l.lat - minLat) / span) * 65,
  }))
})
</script>

<template>
  <h1 class="text-[32px] leading-9">Marjakohtade kaart</h1>

  <section class="grain relative aspect-square overflow-hidden rounded-card shadow-neu-inset" aria-label="Kaart">
    <svg viewBox="0 0 100 100" class="absolute inset-0 size-full text-sage/15" aria-hidden="true">
      <path v-for="n in 9" :key="n" :d="`M${n * 10} 0V100M0 ${n * 10}H100`" stroke="currentColor" stroke-width="0.3" />
    </svg>
    <RouterLink
      v-for="pin in pins"
      :key="pin.id"
      :to="`/asukohad/${pin.id}`"
      class="absolute flex -translate-x-1/2 -translate-y-full flex-col items-center"
      :style="{ left: `${pin.x}%`, top: `${pin.y}%` }"
    >
      <AppIcon name="pin" :size="28" />
      <span class="font-hand text-sm whitespace-nowrap">{{ pin.name }}</span>
    </RouterLink>
    <p v-if="!pins.length" class="absolute inset-0 grid place-items-center p-8 text-center italic text-muted">
      Salvestatud kohad ilmuvad siia kaardile.
    </p>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Minu asukohad</h2>
    <RouterLink
      v-for="l in store.locations"
      :key="l.id"
      :to="`/asukohad/${l.id}`"
      class="grain card flex flex-col gap-1 transition-shadow active:shadow-neu-inset"
    >
      <span class="text-xl">{{ l.name }}</span>
      <span class="text-muted">Marjad: {{ berriesAt(l).map((id) => findProduce(id)?.name).join(', ') || '–' }}</span>
      <span class="flex items-center gap-2 text-muted">Viimati: {{ l.visits[0]?.date }} <StarRating :model-value="averageRating(l)" readonly :size="14" /></span>
    </RouterLink>
    <p v-if="!store.locations.length" class="italic text-muted">Sul pole veel ühtegi kohta salvestatud.</p>
  </section>

  <RouterLink to="/asukohad/uus" class="btn bg-sage text-milk">
    <AppIcon name="plus" :size="20" /> Lisa asukoht
  </RouterLink>
</template>
