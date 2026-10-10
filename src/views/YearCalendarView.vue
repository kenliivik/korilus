<script setup>
import { ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import { produce, phases, statusText, months, inSeason } from '../data/berries.js'

const now = new Date().getMonth()
const selected = ref(now)
const step = (d) => (selected.value = (selected.value + d + 12) % 12)

const cellColor = { a: 'bg-leaf/35', t: 'bg-sage', l: 'bg-muted/35', '.': '' }
</script>

<template>
  <PageHeader title="Aastane kalender" back="/" />

  <ul class="flex gap-5 text-base text-muted" aria-label="Legend">
    <li v-for="(label, key) in phases" :key="key" class="flex items-center gap-2">
      <span class="size-4 rounded" :class="cellColor[key]" />{{ label }}
    </li>
  </ul>

  <section class="grain card grid grid-cols-[5.5rem_repeat(12,1fr)] items-center gap-y-1.5 px-3">
    <span />
    <button
      v-for="(m, i) in months"
      :key="m"
      type="button"
      :aria-label="m"
      :aria-pressed="i === selected"
      class="h-7 rounded-full text-center text-sm uppercase"
      :class="i === selected ? 'grain shadow-neu-inset' : i === now ? 'text-sage' : 'text-muted'"
      @click="selected = i"
    >
      {{ m[0] }}
    </button>

    <template v-for="p in produce" :key="p.id">
      <RouterLink :to="`/mari/${p.id}`" class="truncate pr-1 text-[15px] leading-8 underline-offset-4 active:underline">
        {{ p.name }}
      </RouterLink>
      <span
        v-for="(c, i) in p.season"
        :key="i"
        class="mx-px h-6 rounded-sm"
        :class="cellColor[c] || (i === selected && 'bg-sage/8')"
        :title="c !== '.' ? `${months[i]}: ${phases[c]}` : undefined"
      />
    </template>
  </section>

  <p class="font-hand text-lg text-muted">Puuduta marja nime, et näha detaile.</p>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Kuuvaade</h2>
    <div class="grain card flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <button type="button" aria-label="Eelmine kuu" class="grain grid size-9 place-items-center rounded-full shadow-neu-raised-sm" @click="step(-1)">
          <AppIcon name="back" :size="18" />
        </button>
        <span class="text-xl">{{ months[selected] }}<span v-if="selected === now" class="font-hand text-base text-muted"> · praegu</span></span>
        <button type="button" aria-label="Järgmine kuu" class="grain grid size-9 place-items-center rounded-full shadow-neu-raised-sm" @click="step(1)">
          <AppIcon name="arrow" :size="18" />
        </button>
      </div>
      <ul v-if="inSeason(selected).length" class="flex flex-col gap-1">
        <li v-for="p in inSeason(selected)" :key="p.id">
          <RouterLink :to="`/mari/${p.id}`" class="flex justify-between text-lg">
            {{ p.name }} <span class="font-hand text-base text-muted">{{ statusText[p.season[selected]] }}</span>
          </RouterLink>
        </li>
      </ul>
      <p v-else class="italic text-muted">Sel kuul pole midagi hooajal.</p>
    </div>
  </section>
</template>
