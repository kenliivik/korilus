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
    class="grain flex flex-col items-center gap-3 rounded-card px-2 pt-4 pb-3 shadow-neu-raised transition-shadow active:shadow-neu-inset"
  >
    <span class="grain grid size-15 place-items-center rounded-full shadow-neu-inset">
      <BerryGlyph :name="glyph" :image="image" :size="image ? 56 : 40" />
    </span>
    <span class="flex flex-col items-center">
      <span class="text-base leading-5 text-sage">{{ name }}</span>
      <span v-if="note" class="font-hand text-sm text-muted">{{ note }}</span>
    </span>
  </RouterLink>
</template>
