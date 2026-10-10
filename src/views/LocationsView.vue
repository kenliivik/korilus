<script setup>
import { computed } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import SpotMap from '../components/SpotMap.vue'
import StarRating from '../components/StarRating.vue'
import { findProduce } from '../data/berries.js'
import { store, berriesAt, averageRating } from '../data/store.js'

const spots = computed(() => store.locations.filter((l) => l.lat != null))
</script>

<template>
  <h1 class="text-[32px] leading-9">Marjakohtade kaart</h1>

  <SpotMap :spots="spots" />

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
