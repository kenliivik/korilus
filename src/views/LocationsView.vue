<script setup>
import { computed } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import SpotMap from '../components/SpotMap.vue'
import StarRating from '../components/StarRating.vue'
import { findProduce } from '../data/berries.js'
import { store, berriesAt, averageRating } from '../data/store.js'
import { account } from '../data/account.js'

const spots = computed(() => [...store.locations, ...store.shared].filter((l) => l.lat != null))
const lists = computed(() => [
  { title: 'Minu kohad', spots: store.locations },
  ...(store.shared.length ? [{ title: 'Teiste kohad', spots: store.shared }] : []),
])
// No spots of my own yet: shorter map with "Lisa asukoht" right under it, so the button shows without scrolling.
const empty = computed(() => !store.locations.length)
const groupName = (l) => account.groups.find((g) => g.id === l.groupId)?.name
</script>

<template>
  <h1 class="text-[32px] leading-9">Minu marjakohad</h1>

  <SpotMap :spots="spots" :style="empty && { '--map-room': '4.5rem' }" />

  <section v-for="list in lists" :key="list.title" class="flex flex-col gap-3" :class="{ 'order-1': empty }">
    <h2 class="text-2xl leading-7">{{ list.title }}</h2>
    <RouterLink
      v-for="l in list.spots"
      :key="l.id"
      :to="`/asukohad/${l.id}`"
      class="grain card flex flex-col gap-1 transition-shadow active:shadow-neu-inset"
    >
      <span class="text-xl">{{ l.name }}</span>
      <span v-if="l.shared" class="font-hand text-lg text-muted">{{ l.owner }}{{ groupName(l) ? ` · ${groupName(l)}` : '' }}</span>
      <span v-else-if="groupName(l)" class="font-hand text-lg text-muted">Jagatud: {{ groupName(l) }}</span>
      <span class="text-muted">Marjad: {{ berriesAt(l).map((id) => findProduce(id)?.name).join(', ') || '–' }}</span>
      <span class="flex items-center gap-2 text-muted">Viimati käidud: {{ l.visits[0]?.date }} <StarRating :model-value="averageRating(l)" readonly :size="14" /></span>
    </RouterLink>
    <p v-if="!list.spots.length" class="italic text-muted">Sul pole veel ühtegi marjakohta salvestatud.</p>
  </section>

  <RouterLink to="/asukohad/uus" class="btn bg-sage text-milk">
    <AppIcon name="plus" :size="20" /> Lisa asukoht
  </RouterLink>
</template>
