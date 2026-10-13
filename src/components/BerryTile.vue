<script setup>
import BerryGlyph from './BerryGlyph.vue'

defineProps({
  name: { type: String, required: true },
  glyph: { type: String, required: true },
  to: { type: String, required: true },
  note: { type: String, default: '' },
  image: { type: String, default: '' },
})
</script>

<template>
  <RouterLink
    :to="to"
    class="grain flex flex-col items-center gap-2 rounded-card px-1 pb-3 shadow-neu-raised lg:gap-3 lg:pb-6 transition-shadow hover:shadow-neu-inset active:shadow-neu-inset"
  >
    <!-- The illustration spills over the top edge of the card (Ken's Figma mock, 2026-10-12). -->
    <BerryGlyph :name="glyph" :image="image" :size="100" class="-mt-8 lg:-mt-16 lg:size-48!" />
    <span class="flex flex-col items-center">
      <span class="text-base leading-5 text-sage lg:text-2xl lg:leading-7">{{ name }}</span>
      <span v-if="note" class="font-hand text-sm text-muted lg:text-lg">{{ note }}</span>
    </span>
  </RouterLink>
</template>
