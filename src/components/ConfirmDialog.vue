<script setup>
import { ref, watch } from 'vue'
import { pending } from '../data/confirm.js'

// A native modal <dialog>: it traps focus, starts on "Tühista" and closes on Esc.
const el = ref(null)
watch(() => pending.resolve, (r) => (r ? el.value.showModal() : el.value.close()))

function answer(yes) {
  pending.resolve?.(yes)
  pending.resolve = null
}
</script>

<template>
  <dialog
    ref="el"
    class="grain card settle m-auto w-[calc(100%-2.5rem)] max-w-sm text-sage backdrop:bg-sage/25"
    @close="answer(false)"
  >
    <p class="text-lg leading-6">{{ pending.text }}</p>
    <div class="mt-5 grid grid-cols-2 gap-3">
      <button type="button" class="btn grain text-sage" @click="answer(false)">Tühista</button>
      <button type="button" class="btn grain text-danger" @click="answer(true)">{{ pending.ok }}</button>
    </div>
  </dialog>
</template>
