<script setup>
// Read-only stars, or a 1–5 picker when used with v-model.
const model = defineModel({ type: Number, default: 0 })
defineProps({ readonly: Boolean, size: { type: Number, default: 18 } })
</script>

<template>
  <span class="inline-flex gap-1 text-sage" :aria-label="`${model} / 5`" :role="readonly ? 'img' : 'radiogroup'">
    <component
      :is="readonly ? 'span' : 'button'"
      v-for="n in 5"
      :key="n"
      :type="readonly ? undefined : 'button'"
      :aria-label="readonly ? undefined : `${n} tärni`"
      @click="readonly || (model = n)"
    >
      <svg :width="size" :height="size" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round">
        <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9L12 3Z" :fill="n <= model ? 'currentColor' : 'none'" />
      </svg>
    </component>
  </span>
</template>
