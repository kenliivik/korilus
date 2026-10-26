<script setup>
import AppIcon from './AppIcon.vue'
import { produce } from '../data/berries.js'
import { recipeTypes } from '../data/recipes.js'

// The recipe fields shared by Ken's editor in /admin and the own-recipe form: berries, type, time, amount,
// ingredients, steps and tip. `draft` comes from blankRecipe() and is changed in place.
const props = defineProps({ draft: { type: Object, required: true } })

const toggleBerry = (id) => {
  const b = props.draft.berries
  b.includes(id) ? b.splice(b.indexOf(id), 1) : b.push(id)
}
const move = (i, by) => {
  const s = props.draft.steps
  s.splice(i + by, 0, ...s.splice(i, 1))
}

const chip = (on) => (on ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm')
const small = 'grain grid size-9 shrink-0 place-items-center rounded-full shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-30'
</script>

<template>
    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Marjad</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="p in produce"
          :key="p.id"
          type="button"
          :aria-pressed="draft.berries.includes(p.id)"
          class="rounded-full px-3 py-1.5 text-base"
          :class="chip(draft.berries.includes(p.id))"
          @click="toggleBerry(p.id)"
        >
          {{ p.name }}
        </button>
      </div>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Liik</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="(label, t) in recipeTypes"
          :key="t"
          type="button"
          :aria-pressed="draft.type === t"
          class="rounded-full px-3 py-1.5 text-base"
          :class="chip(draft.type === t)"
          @click="draft.type = t"
        >
          {{ label }}
        </button>
      </div>
    </fieldset>

    <div class="grid grid-cols-2 gap-3">
      <label class="flex flex-col gap-2">
        <span class="text-xl">Aeg</span>
        <input v-model="draft.time" class="field" placeholder="nt. 40 min" />
      </label>
      <label class="flex flex-col gap-2">
        <span class="text-xl">Kogus</span>
        <input v-model="draft.yield" class="field" placeholder="nt. 4 purki" />
      </label>
    </div>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Koostisosad</legend>
      <div v-for="(ing, i) in draft.ingredients" :key="i" class="flex items-center gap-2">
        <input v-model="ing.amount" class="field w-24! shrink-0" placeholder="1 kg" aria-label="Kogus" />
        <input v-model="ing.item" class="field min-w-0" placeholder="mustikaid" aria-label="Koostisosa" />
        <button type="button" :class="small" aria-label="Eemalda rida" @click="draft.ingredients.splice(i, 1)">
          <AppIcon name="trash" :size="16" />
        </button>
      </div>
      <button type="button" class="btn grain text-sage" @click="draft.ingredients.push({ amount: '', item: '' })">
        <AppIcon name="plus" :size="20" /> Lisa koostisosa
      </button>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Sammud</legend>
      <div v-for="(_, i) in draft.steps" :key="i" class="flex items-start gap-2">
        <span class="font-hand w-6 pt-3 text-xl text-leaf">{{ i + 1 }}.</span>
        <textarea v-model="draft.steps[i]" rows="2" class="field min-w-0" :aria-label="`Samm ${i + 1}`" />
        <div class="flex flex-col gap-1">
          <button type="button" :class="small" aria-label="Liiguta üles" :disabled="i === 0" @click="move(i, -1)">
            <AppIcon name="up" :size="16" />
          </button>
          <button type="button" :class="small" aria-label="Liiguta alla" :disabled="i === draft.steps.length - 1" @click="move(i, 1)">
            <span class="grid rotate-180"><AppIcon name="up" :size="16" /></span>
          </button>
          <button type="button" :class="small" aria-label="Eemalda samm" @click="draft.steps.splice(i, 1)">
            <AppIcon name="trash" :size="16" />
          </button>
        </div>
      </div>
      <button type="button" class="btn grain text-sage" @click="draft.steps.push('')">
        <AppIcon name="plus" :size="20" /> Lisa samm
      </button>
    </fieldset>

    <label class="flex flex-col gap-2">
      <span class="text-xl">Hea nõu</span>
      <textarea v-model="draft.tip" rows="2" class="field" placeholder="Valikuline" />
    </label>

</template>
