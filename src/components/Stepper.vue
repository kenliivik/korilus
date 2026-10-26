<script setup>
// − value + for counting things by hand (jars left in Sahver, litres picked on a visit), the same everywhere.
defineProps({
  down: { type: String, required: true }, // aria-labels, e.g. "Võtsin ühe: Pohlamoos"
  up: { type: String, required: true },
  canDown: { type: Boolean, default: true },
  canUp: { type: Boolean, default: true },
})
defineEmits(['down', 'up'])
</script>

<template>
  <span class="flex shrink-0 items-center gap-0.5">
    <button type="button" class="grain grid size-10 place-items-center rounded-full text-xl shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-40 disabled:active:shadow-neu-raised-sm" :disabled="!canDown" :aria-label="down" @click="$emit('down')">−</button>
    <span class="min-w-11 text-center text-lg tabular-nums" aria-live="polite"><slot /></span>
    <button type="button" class="grain grid size-10 place-items-center rounded-full text-xl shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-40 disabled:active:shadow-neu-raised-sm" :disabled="!canUp" :aria-label="up" @click="$emit('up')">+</button>
  </span>
</template>
