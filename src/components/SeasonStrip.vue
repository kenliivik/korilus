<script setup>
import { computed } from 'vue'
import { produce, months } from '../data/berries.js'

// How many species are in season each month: the year at a glance, linked to the full calendar.
const now = new Date().getMonth()
const counts = computed(() => months.map((_, i) => produce.filter((p) => p.season[i] !== '.').length))
const max = computed(() => Math.max(1, ...counts.value))
// Off-season, say when picking starts again.
const starts = computed(() => {
  if (counts.value[now]) return null
  for (let d = 1; d < 12; d++) if (counts.value[(now + d) % 12]) return months[(now + d) % 12]
  return null
})
</script>

<template>
  <RouterLink to="/kalender" class="flex flex-col gap-1.5" aria-label="Korjehooaeg kuude kaupa, ava kalender">
    <div class="grid h-12 grid-cols-12 items-end border-b border-sage/30 lg:h-16">
      <span
        v-for="(n, i) in counts"
        :key="i"
        class="mx-auto w-3/5 max-w-4 rounded-t-[3px]"
        :class="i === now ? 'bg-sage' : 'bg-sage/25'"
        :style="{ height: n ? `${(n / max) * 100}%` : '0' }"
        :title="`${months[i]}: ${n} liiki`"
      />
    </div>
    <div class="grid grid-cols-12 text-center text-xs uppercase lg:text-sm">
      <span v-for="(m, i) in months" :key="m" :class="i === now ? 'text-sage' : 'text-muted'">{{ m[0] }}</span>
    </div>
    <p v-if="starts" class="font-hand text-base text-muted">Hooaeg algab {{ starts }}is</p>
  </RouterLink>
</template>
