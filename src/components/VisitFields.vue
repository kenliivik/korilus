<script setup>
import StarRating from './StarRating.vue'
import { produce } from '../data/berries.js'
import { today } from '../data/store.js'

// The parts of a visit: when, what was found, a note and a 1–5 rating.
const visit = defineModel({ type: Object, required: true })

const toggle = (id) =>
  (visit.value.berries = visit.value.berries.includes(id)
    ? visit.value.berries.filter((b) => b !== id)
    : [...visit.value.berries, id])
</script>

<template>
  <label class="flex flex-col gap-2">
    <span class="text-xl">Millal?</span>
    <input v-model="visit.date" type="date" required :max="today()" class="field" />
  </label>

  <fieldset class="flex flex-col gap-2">
    <legend class="mb-2 text-xl">Mida leidsid?</legend>
    <div class="flex flex-wrap gap-2">
      <button
        v-for="p in produce"
        :key="p.id"
        type="button"
        :aria-pressed="visit.berries.includes(p.id)"
        class="rounded-full px-3 py-1.5 text-base transition-shadow"
        :class="visit.berries.includes(p.id) ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm'"
        @click="toggle(p.id)"
      >
        {{ p.name }}
      </button>
    </div>
  </fieldset>

  <label class="flex flex-col gap-2">
    <span class="text-xl">Märkmed</span>
    <textarea v-model="visit.note" rows="3" class="field" placeholder='nt. "Palju marju, hommikul parim"' />
  </label>

  <div class="flex flex-col gap-2">
    <span class="text-xl">Kui hea koht?</span>
    <StarRating v-model="visit.rating" :size="30" />
  </div>
</template>
