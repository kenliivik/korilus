<script setup>
import StarRating from './StarRating.vue'
import { produce } from '../data/berries.js'
import { today } from '../data/store.js'

// The parts of a visit: when, what was found (and how much, the harvest log), a note and a 1–5 rating.
const visit = defineModel({ type: Object, required: true })

function toggle(id) {
  const v = visit.value
  if (v.berries.includes(id)) {
    v.berries = v.berries.filter((b) => b !== id)
    if (v.amounts) delete v.amounts[id]
  } else v.berries = [...v.berries, id]
}

// Litres per berry, optional. An emptied field drops the amount, so "nothing logged" stays different from 0.
function setAmount(id, value) {
  const v = visit.value
  v.amounts ??= {}
  if (value === '' || !(+value > 0)) delete v.amounts[id]
  else v.amounts[id] = Math.round(+value * 10) / 10
}
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
        class="press rounded-full px-3 py-1.5 text-base"
        :class="visit.berries.includes(p.id) ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm'"
        @click="toggle(p.id)"
      >
        {{ p.name }}
      </button>
    </div>
  </fieldset>

  <fieldset v-if="visit.berries.length" class="flex flex-col gap-2">
    <legend class="mb-2 text-xl">Kui palju korjasid?</legend>
    <label v-for="id in visit.berries" :key="id" class="flex items-center gap-3 text-lg">
      <span class="min-w-0 truncate">{{ produce.find((p) => p.id === id)?.name ?? id }}</span>
      <span class="leader" />
      <input
        type="number"
        inputmode="decimal"
        min="0"
        step="0.5"
        :value="visit.amounts?.[id] ?? ''"
        class="field w-24 shrink-0 py-2 text-right [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none"
        placeholder="–"
        @input="setAmount(id, $event.target.value)"
      />
      <span class="shrink-0 text-muted">liitrit</span>
    </label>
  </fieldset>

  <label class="flex flex-col gap-2">
    <span class="text-xl">Märkmed</span>
    <textarea v-model="visit.note" rows="3" class="field" placeholder='nt. "Palju marju, hommikul parim"' />
  </label>

  <div class="flex flex-col gap-2">
    <span class="text-xl">Kui hea koht?</span>
    <StarRating v-model="visit.rating" :size="32" />
  </div>
</template>
