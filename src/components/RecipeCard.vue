<script setup>
import BerryGlyph from './BerryGlyph.vue'
import { recipeTypes } from '../data/recipes.js'

defineProps({ recipe: { type: Object, required: true } })
</script>

<!-- One recipe in a list: picture (or the first berry's glyph), title, type and time. -->
<template>
  <RouterLink
    :to="`/retsept/${recipe.id}`"
    class="grain flex items-center gap-4 rounded-card p-3 text-sage shadow-neu-raised transition-shadow active:shadow-neu-inset"
  >
    <img v-if="recipe.image" :src="recipe.image" alt="" class="size-14 shrink-0 rounded-full object-cover shadow-neu-inset" />
    <span v-else class="grain grid size-14 shrink-0 place-items-center rounded-full shadow-neu-inset">
      <BerryGlyph :name="recipe.berries[0] ?? ''" :size="48" />
    </span>
    <span class="flex min-w-0 flex-col">
      <span class="text-xl leading-6">{{ recipe.title }}</span>
      <span class="font-hand text-sm text-muted">
        {{ [recipeTypes[recipe.type], recipe.time].filter(Boolean).join(' · ') }}
      </span>
    </span>
  </RouterLink>
</template>
