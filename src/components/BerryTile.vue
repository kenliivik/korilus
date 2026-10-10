<script setup>
import BerryGlyph from './BerryGlyph.vue'

defineProps({
  name: { type: String, required: true },
  glyph: { type: String, required: true },
})
</script>

<template>
  <button
    type="button"
    class="grain flex flex-col items-center gap-3 rounded-card px-2 pt-4 pb-3 shadow-neu-raised transition-shadow active:shadow-neu-inset"
  >
    <span class="grain grid size-15 place-items-center rounded-full shadow-neu-inset">
      <BerryGlyph :name="glyph" />
    </span>
    <span class="text-base leading-5 text-sage">{{ name }}</span>
  </button>
</template>
