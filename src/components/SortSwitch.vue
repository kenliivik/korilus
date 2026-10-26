<script setup>
// Two-way switch for the calendar order. v-model holds the key of the chosen option.
defineProps({ options: { type: Object, required: true } })
const model = defineModel({ type: String, required: true })
</script>

<template>
  <div role="group" aria-label="Järjestus" class="grain flex shrink-0 rounded-full p-0.5 shadow-neu-raised-sm lg:p-1">
    <button
      v-for="(label, key) in options"
      :key="key"
      type="button"
      :aria-pressed="model === key"
      class="press h-11 min-w-16 rounded-full px-3 text-base lg:h-9 lg:text-lg"
      :class="model === key ? 'grain shadow-neu-inset text-sage' : 'text-muted lg:hover:text-sage'"
      @click="model = key"
    >
      {{ label }}
    </button>
  </div>
</template>
