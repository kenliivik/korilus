<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 40 },
  image: { type: String, default: '' }, // a picture uploaded in /admin, shown instead of the drawing
})

// Ken's botanical illustrations, one per bundled species (src/assets/berries/<id>.webp).
const ART = Object.fromEntries(
  Object.entries(import.meta.glob('../assets/berries/*.webp', { eager: true, import: 'default' })).map(([path, url]) => [
    path.slice(path.lastIndexOf('/') + 1, -5),
    url,
  ]),
)

// Berries added in /admin without a picture get a plain sprig.
const GENERIC =
  '<path d="M20 5c-1 5-4 9-8 12M20 5c1 5 4 8 8 10" stroke="#6E8263" stroke-width="1.3" fill="none" stroke-linecap="round"/>' +
  '<path d="M20 5c5-3 10-1 11 2-5 2-8 1-11-2Z" fill="#6E8263"/>' +
  [[12, 24, 6, '#7A4E6E'], [27, 23, 6, '#86587A'], [19, 31, 5.5, '#6E4463']]
    .map(([cx, cy, r, fill]) => `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${fill}"/><circle cx="${cx - r * 0.35}" cy="${cy - r * 0.4}" r="${r * 0.28}" fill="#fff" fill-opacity="0.45"/>`)
    .join('')

const art = computed(() => ART[props.name])
</script>

<template>
  <img v-if="image" :src="image" alt="" class="rounded-full object-cover" :style="{ width: `${size}px`, height: `${size}px` }" />
  <img v-else-if="art" :src="art" alt="" class="object-contain" :style="{ width: `${size}px`, height: `${size}px` }" />
  <svg
    v-else
    :width="size"
    :height="size"
    viewBox="0 0 40 40"
    fill="none"
    aria-hidden="true"
    v-html="GENERIC"
  />
</template>
