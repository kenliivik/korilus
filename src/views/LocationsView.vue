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
  <h1 class="text-[32px] leading-9 lg:text-6xl lg:leading-tight">Minu marjakohad</h1>

  <!-- Desktop: spot list on the left, a tall map on the right that stays in view. On phones the wrappers dissolve. -->
  <div class="contents lg:grid lg:grid-cols-[22rem_1fr] lg:items-start lg:gap-10 xl:grid-cols-[26rem_1fr]">

  <SpotMap
    :spots="spots"
    :style="empty && { '--map-room': '4.5rem' }"
    class="lg:sticky lg:top-12 lg:col-start-2 lg:row-start-1 lg:h-[calc(100dvh-12rem)]"
  />

  <div class="contents lg:col-start-1 lg:row-start-1 lg:flex lg:flex-col lg:gap-8">
  <section v-for="list in lists" :key="list.title" class="flex flex-col gap-3" :class="{ 'order-1': empty }">
    <h2 class="text-2xl leading-7 lg:text-3xl lg:leading-9">{{ list.title }}</h2>
    <RouterLink
      v-for="l in list.spots"
      :key="l.id"
      :to="`/asukohad/${l.id}`"
      class="grain card flex flex-col gap-1 transition-shadow active:shadow-neu-inset"
    >
      <span class="text-xl lg:text-2xl">{{ l.name }}</span>
      <span v-if="l.shared" class="font-hand text-lg text-muted">{{ l.owner }}{{ groupName(l) ? ` · ${groupName(l)}` : '' }}</span>
      <span v-else-if="groupName(l)" class="font-hand text-lg text-muted">Jagatud: {{ groupName(l) }}</span>
      <span class="text-muted">Marjad: {{ berriesAt(l).map((id) => findProduce(id)?.name).join(', ') || '–' }}</span>
      <span class="flex items-center gap-2 text-muted">Viimati käidud: {{ l.visits[0]?.date }} <StarRating :model-value="averageRating(l)" readonly :size="14" /></span>
    </RouterLink>
    <p v-if="!list.spots.length" class="italic text-muted">Sul pole veel ühtegi marjakohta salvestatud.</p>
  </section>

  <RouterLink to="/asukohad/uus" class="btn bg-sage text-milk" :class="{ 'max-lg:hidden': !empty }">
    <AppIcon name="plus" :size="20" /> Lisa asukoht
  </RouterLink>
  </div>
  </div>

  <!-- Phone with spots saved: the list pushes the button far down, so "+" floats above the menu instead,
       at the right edge of the 430px column. -->
  <RouterLink
    v-if="!empty"
    to="/asukohad/uus"
    aria-label="Lisa asukoht"
    class="fixed right-[max(1.25rem,calc(50vw-195px))] bottom-[calc(max(1.5rem,env(safe-area-inset-bottom))+5rem)] z-10 grid size-14 place-items-center rounded-full bg-sage text-milk shadow-neu-raised active:shadow-neu-inset lg:hidden"
  >
    <AppIcon name="plus" :size="26" />
  </RouterLink>
</template>
