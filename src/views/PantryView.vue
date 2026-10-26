<script setup>
import { computed } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import { byNewest, useJar } from '../data/pantry.js'
import { findRecipe } from '../data/recipes.js'
import { findLocation, formatDate } from '../data/store.js'

// Sahver: what was made this season and before, grouped by year, with jars left. −/+ when a jar is opened.
const years = computed(() =>
  byNewest.value.reduce((m, j) => m.set(j.made.slice(0, 4), [...(m.get(j.made.slice(0, 4)) ?? []), j]), new Map()),
)
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
      <li v-for="j in jars" :key="j.id" class="flex flex-wrap items-center gap-x-3 border-b border-sage/12 py-3 last:border-0">
        <RouterLink :to="`/sahver/${j.id}`" class="press flex min-w-0 flex-1 flex-col">
          <span class="truncate text-lg lg:text-xl">{{ j.title }}</span>
          <span class="text-base text-muted">
            {{ formatDate(j.made, false) }}<template v-if="findLocation(j.spotId ?? '')"> · {{ findLocation(j.spotId).name }}</template>
            <template v-if="j.recipeId && !findRecipe(j.recipeId)"> · retsepti enam pole</template>
          </span>
        </RouterLink>
        <span class="flex shrink-0 items-center gap-2">
          <button type="button" class="grain grid size-11 place-items-center rounded-full text-xl shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-40" :disabled="!j.left" :aria-label="`Võtsin ühe: ${j.title}`" @click="useJar(j, -1)">−</button>
          <span class="w-14 text-center text-lg tabular-nums" :aria-label="`${j.left} purki ${j.jars}-st alles`">{{ j.left }}<span class="text-base text-muted">/{{ j.jars }}</span></span>
          <button type="button" class="grain grid size-11 place-items-center rounded-full text-xl shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-40" :disabled="j.left >= j.jars" :aria-label="`Panin ühe tagasi: ${j.title}`" @click="useJar(j, 1)">+</button>
        </span>
        <RouterLink v-if="j.note" :to="`/sahver/${j.id}`" class="hand-note w-full">{{ j.note }}</RouterLink>
      </li>
    </ul>
  </section>

  <p v-if="!years.size" class="italic text-muted">Sahver on veel tühi. Kui oled midagi teinud, lisa purgid siia või retsepti juures „Tegin selle”.</p>
</template>
