<script setup>
import { computed } from 'vue'
import BerryTile from '../components/BerryTile.vue'
import { inSeason, phaseIn, statusText, months } from '../data/berries.js'

const month = months[new Date().getMonth()]
const ripe = computed(() => inSeason()) // updates when new content arrives from the server
</script>

<template>
  <header class="flex items-center justify-between">
    <h1 class="font-serif-sc text-[32px] leading-none tracking-wide text-sage lg:text-5xl">Korilus</h1>
    <span class="font-hand text-lg text-muted lg:text-3xl">{{ month }}</span>
  </header>

  <section class="flex flex-col gap-1 lg:mt-6 lg:gap-2 lg:border-t lg:border-sage/20 lg:pt-8">
    <h2 class="text-[32px] leading-9 text-sage lg:text-6xl lg:leading-tight">Mida korjata?</h2>
    <p class="italic leading-5 text-muted lg:text-xl">
      {{ ripe.length ? 'Need on praegu valmis:' : 'Praegu pole midagi korjata. Vaata kalendrist, mis on varsti valmimas.' }}
    </p>
  </section>

  <section v-if="ripe.length" class="mt-6 grid grid-cols-3 gap-x-3 gap-y-12 lg:mt-20 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-24 xl:grid-cols-5" aria-label="Praegu valmis korjamiseks">
    <BerryTile
      v-for="p in ripe"
      :key="p.id"
      :name="p.name"
      :glyph="p.id"
      :image="p.image"
      :to="`/mari/${p.id}`"
      :note="statusText[phaseIn(p)]"
    />
  </section>
</template>
