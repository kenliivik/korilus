<script setup>
import { computed } from 'vue'
import BerryTile from '../components/BerryTile.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import InstallHint from '../components/InstallHint.vue'
import { inSeason, comingSoon, phaseIn, statusText, months } from '../data/berries.js'
import { activity, ago } from '../data/activity.js'

const month = months[new Date().getMonth()]
const ripe = computed(() => inSeason()) // updates when new content arrives from the server
const next = computed(() => comingSoon())
</script>

<template>
  <header class="flex items-center justify-between lg:-mb-6 lg:h-0 lg:items-start lg:justify-end">
    <h1 class="lg:hidden"><img src="/logo.svg" alt="Korilus" class="h-10 w-auto" /></h1>
    <span class="font-hand text-lg text-muted lg:mt-8 lg:text-3xl">{{ month }}</span>
  </header>

  <section class="flex flex-col gap-1 lg:gap-2">
    <h2 class="text-[32px] leading-9 text-sage lg:text-6xl lg:leading-tight">Mida korjata?</h2>
    <p class="italic leading-5 text-muted lg:text-xl">
      {{ ripe.length ? 'Need on praegu valmis:' : 'Praegu pole midagi korjata.' }}
    </p>
  </section>

  <section v-if="ripe.length" class="mt-6 grid grid-cols-3 gap-x-3 gap-y-12 lg:mt-20 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-24 xl:grid-cols-5" aria-label="Praegu valmis korjamiseks">
    <BerryTile
      v-for="(p, i) in ripe"
      :key="p.id"
      :name="p.name"
      :glyph="p.id"
      :image="p.image"
      :to="`/mari/${p.id}`"
      :note="statusText[phaseIn(p)]"
      class="settle"
      :style="{ '--i': i }"
    />
  </section>

  <!-- What ripens next, so the page has something to look forward to off-season. -->
  <section v-if="next.length" class="mt-4 flex flex-col gap-3 lg:mt-16 lg:max-w-xl">
    <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Varsti valmimas</h2>
    <ul class="grain card flex flex-col gap-1">
      <li v-for="{ p, month } in next" :key="p.id">
        <RouterLink :to="`/mari/${p.id}`" class="press flex items-center gap-3 text-lg lg:text-xl">
          <span class="flex items-center gap-3"><BerryGlyph :name="p.id" :image="p.image" :size="48" />{{ p.name }}</span>
          <span class="leader" />
          <span class="font-hand text-base text-muted">{{ months[month] }}</span>
        </RouterLink>
      </li>
    </ul>
  </section>

  <!-- What the user's groups did lately: a few quiet lines, no badges or counts. -->
  <section v-if="activity.length" class="mt-4 flex flex-col gap-2 lg:mt-16 lg:max-w-xl" aria-label="Sõpradelt">
    <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Sõpradelt</h2>
    <ul class="flex flex-col">
      <li v-for="a in activity" :key="a.at + a.to">
        <RouterLink :to="a.to" class="press flex items-baseline gap-3 border-b border-sage/12 py-2 text-lg">
          <span class="min-w-0 flex-1">{{ a.who }} {{ a.text }}</span>
          <span class="shrink-0 font-hand text-base text-muted">{{ ago(a.at) }}</span>
        </RouterLink>
      </li>
    </ul>
  </section>

  <InstallHint class="mt-4" />
</template>
