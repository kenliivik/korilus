<script setup>
import { computed } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import Stepper from '../components/Stepper.vue'
import { byNewest, useJar } from '../data/pantry.js'
import { findRecipe } from '../data/recipes.js'
import { findLocation, formatDate } from '../data/store.js'

// Sahver: what was made this season and before, grouped by year, with jars left. −/+ when a jar is opened.
// Used-up batches go last in their year and fade, but stay for their note.
const years = computed(() =>
  [...byNewest.value]
    .sort((a, b) => !a.left - !b.left)
    .reduce((m, j) => m.set(j.made.slice(0, 4), [...(m.get(j.made.slice(0, 4)) ?? []), j]), new Map()),
)
const where = (j) => [formatDate(j.made, false), findLocation(j.spotId ?? '')?.name, j.recipeId && !findRecipe(j.recipeId) && 'retsepti enam pole'].filter(Boolean).join(' · ')
const leftIn = (jars) => jars.reduce((sum, j) => sum + j.left, 0)
</script>

<template>
  <PageHeader title="Sahver" back="/retseptid" />
  <p class="-mt-3 italic leading-5 text-muted lg:text-xl">Mis on tehtud ja kui palju on alles.</p>

  <RouterLink to="/sahver/uus" class="btn bg-sage text-milk lg:self-start">
    <AppIcon name="plus" :size="20" /> Lisa purgid
  </RouterLink>

  <section v-for="[year, jars] in years" :key="year" class="flex flex-col gap-3 lg:max-w-3xl">
    <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">{{ year }} <span class="font-hand text-xl text-muted">· alles {{ leftIn(jars) }} purki</span></h2>
    <ul class="grain card flex flex-col">
      <li v-for="j in jars" :key="j.id" class="grid grid-cols-[3.5rem_1fr_auto] items-center gap-x-3 border-b border-sage/12 py-3 last:border-0" :class="!j.left && 'opacity-55'">
        <BerryGlyph v-if="j.berries[0]" :name="j.berries[0]" :size="56" />
        <span v-else class="grid size-14 place-items-center text-leaf"><AppIcon name="book" :size="28" /></span>
        <RouterLink :to="`/sahver/${j.id}`" class="press flex min-w-0 flex-col">
          <span class="text-lg leading-6 lg:text-xl">{{ j.title }}</span>
          <span class="text-base leading-5 text-muted">{{ where(j) }}</span>
        </RouterLink>
        <Stepper :down="`Võtsin ühe: ${j.title}`" :up="`Panin ühe tagasi: ${j.title}`" :can-down="j.left > 0" :can-up="j.left < j.jars" @down="useJar(j, -1)" @up="useJar(j, 1)">
          <template v-if="j.left">{{ j.left }}<span class="text-base text-muted">/{{ j.jars }}</span></template>
          <span v-else class="font-hand text-base text-muted">tühi</span>
        </Stepper>
        <RouterLink v-if="j.note" :to="`/sahver/${j.id}`" class="hand-note col-span-2 col-start-2 mt-1">{{ j.note }}</RouterLink>
      </li>
    </ul>
  </section>

  <p v-if="!years.size" class="italic text-muted">Sahver on veel tühi. Kui oled midagi teinud, lisa purgid siia või retsepti juures „Tegin selle”.</p>
</template>
