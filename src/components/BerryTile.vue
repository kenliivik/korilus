<script setup>
import BerryGlyph from './BerryGlyph.vue'

defineProps({
  name: { type: String, required: true },
  glyph: { type: String, required: true },
  to: { type: String, required: true },
  note: { type: String, default: '' },
  image: { type: String, default: '' },
  season: { type: String, default: '' },
})

const now = new Date().getMonth()
const cell = { a: 'bg-leaf/45', t: 'bg-sage', l: 'hatch', '.': 'bg-sage/10' }
</script>

<template>
  <RouterLink
    :to="to"
    class="grain press flex flex-col items-center gap-2 rounded-card px-1 pb-3 shadow-neu-raised lg:gap-3 lg:pb-6 hover:shadow-neu-inset active:shadow-neu-inset"
  >
    <!-- The illustration spills over the top edge of the card (Ken's Figma mock, 2026-10-12). -->
    <BerryGlyph :name="glyph" :image="image" :size="100" class="-mt-8 lg:-mt-16 lg:size-48!" />
    <span class="flex flex-col items-center">
      <span class="text-base leading-5 text-sage lg:text-2xl lg:leading-7">{{ name }}</span>
      <span v-if="note" class="font-hand text-sm text-muted lg:text-lg">{{ note }}</span>
      <!-- The species' year in twelve cells, same marks as the calendar; this month is the taller one. -->
      <span v-if="season" class="mt-1.5 flex items-end gap-px lg:mt-2.5 lg:gap-0.5" aria-hidden="true">
        <span v-for="(c, i) in season" :key="i" class="w-1.5 rounded-[1px] lg:w-2.5" :class="[cell[c], i === now ? 'h-3 lg:h-4' : 'h-1.5 lg:h-2']" />
      </span>
    </span>
  </RouterLink>
</template>
