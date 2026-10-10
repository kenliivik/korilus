<script setup>
import BerryTile from '../components/BerryTile.vue'
import LinkCard from '../components/LinkCard.vue'
import { inSeason, phaseIn, statusText, months } from '../data/berries.js'

const month = months[new Date().getMonth()]
const ripe = inSeason()
</script>

<template>
  <header class="flex items-center justify-between">
    <h1 class="font-serif-sc text-[32px] leading-none tracking-wide text-sage">Korilus</h1>
    <span class="font-hand text-lg text-muted">{{ month }}</span>
  </header>

  <section class="flex flex-col gap-1">
    <h2 class="text-[32px] leading-9 text-sage">Mis on praegu küps?</h2>
    <p class="italic leading-5 text-muted">
      {{ ripe.length ? `${ripe.length} liiki on praegu hooajal` : 'Praegu pole midagi hooajal. Vaata aastast kalendrit.' }}
    </p>
  </section>

  <section v-if="ripe.length" class="grid grid-cols-3 gap-x-3 gap-y-4" aria-label="Praegu hooajal">
    <BerryTile
      v-for="p in ripe"
      :key="p.id"
      :name="p.name"
      :glyph="p.id"
      :to="`/mari/${p.id}`"
      :note="statusText[phaseIn(p)]"
    />
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7 text-sage">Kalender</h2>
    <LinkCard label="Vaata aastast kalendrit" icon="calendar" to="/kalender" />
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7 text-sage">Asukohad</h2>
    <LinkCard label="Salvesta marjakoha asukohad" icon="pin" to="/asukohad" />
  </section>
</template>
