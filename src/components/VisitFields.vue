<script setup>
import StarRating from './StarRating.vue'
import Stepper from './Stepper.vue'
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

// Litres per berry, optional, half a litre per tap. Back at 0 the amount is dropped, so "nothing logged" stays different from 0.
const amount = (id) => visit.value.amounts?.[id] ?? 0
function step(id, by) {
  const v = visit.value
  const next = Math.min(99, Math.max(0, Math.round((amount(id) + by) * 2) / 2))
  v.amounts ??= {}
  if (next) v.amounts[id] = next
  else delete v.amounts[id]
}
const shown = (n) => String(n).replace('.', ',')
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
    <div v-for="id in visit.berries" :key="id" class="flex items-center gap-3 text-lg">
      <span class="min-w-0 truncate">{{ produce.find((p) => p.id === id)?.name ?? id }}</span>
      <span class="leader" />
      <Stepper
        :down="`Vähem: ${produce.find((p) => p.id === id)?.name ?? id}`"
        :up="`Rohkem: ${produce.find((p) => p.id === id)?.name ?? id}`"
        :can-down="amount(id) > 0"
        @down="step(id, -0.5)"
        @up="step(id, 0.5)"
      >
        <template v-if="amount(id)">{{ shown(amount(id)) }} <span class="text-base text-muted">l</span></template>
        <span v-else class="text-muted">–</span>
      </Stepper>
    </div>
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
