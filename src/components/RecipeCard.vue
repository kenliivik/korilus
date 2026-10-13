<script setup>
import BerryGlyph from './BerryGlyph.vue'
import { recipeTypes } from '../data/recipes.js'

defineProps({
  recipe: { type: Object, required: true },
  tile: { type: Boolean, default: false }, // on desktop, a tall card with a big picture spilling over the top (Retseptid page)
})
</script>

<!-- One recipe in a list: picture (or the first berry's glyph), title, type and time. -->
<template>
  <RouterLink
    :to="`/retsept/${recipe.id}`"
    class="grain flex items-center gap-4 rounded-card p-3 text-sage shadow-neu-raised transition-shadow active:shadow-neu-inset"
    :class="tile && 'lg:flex-col lg:gap-3 lg:px-4 lg:pb-6 lg:text-center'"
  >
    <img
      v-if="recipe.image"
      :src="recipe.image"
      alt=""
      class="size-14 shrink-0 rounded-full object-cover shadow-neu-inset"
      :class="tile && 'lg:-mt-12 lg:aspect-[4/3] lg:h-auto lg:w-full lg:rounded-card lg:shadow-neu-raised-sm'"
    />
    <BerryGlyph v-else :name="recipe.berries[0] ?? ''" :size="56" class="shrink-0" :class="tile && 'lg:-mt-16 lg:size-44!'" />
    <span class="flex min-w-0 flex-col">
      <span class="text-xl leading-6" :class="tile && 'lg:text-2xl lg:leading-7'">{{ recipe.title }}</span>
      <span class="font-hand text-sm text-muted" :class="tile && 'lg:text-lg'">
        {{ [recipeTypes[recipe.type], recipe.time].filter(Boolean).join(' · ') }}
      </span>
    </span>
  </RouterLink>
</template>
