<script setup>
import { ref, computed, watch } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import SortSwitch from '../components/SortSwitch.vue'
import { produce, phases, statusText, months } from '../data/berries.js'

const now = new Date().getMonth()
const selected = ref(now)
const step = (d) => (selected.value = (selected.value + d + 12) % 12)

const cellColor = { a: 'bg-leaf/35', t: 'bg-sage', l: 'hatch', '.': '' }

// A–Ü or by when ripening starts, remembered on this device.
const SORT_KEY = 'korilus-calendar-sort'
let saved = null
try { saved = localStorage.getItem(SORT_KEY) } catch { /* private mode: start with the default */ }
const sort = ref(saved === 'az' ? 'az' : 'season')
watch(sort, (v) => { try { localStorage.setItem(SORT_KEY, v) } catch { /* nothing to remember it in */ } })
const byName = (a, b) => a.name.localeCompare(b.name, 'et')
const start = (p) => p.season.search(/[^.]/)
const sorted = computed(() => [...produce].sort(sort.value === 'az' ? byName : (a, b) => start(a) - start(b) || byName(a, b)))
const inMonth = computed(() => sorted.value.filter((p) => p.season[selected.value] !== '.'))
const sorts = { season: 'Hooaeg', az: 'A–Ü' }
</script>

<template>
  <PageHeader title="Korjekalender"><SortSwitch v-model="sort" :options="sorts" class="ml-auto lg:hidden" /></PageHeader>

  <!-- Desktop: year chart with the month panel beside it (below it on small laptops). On phones the wrappers dissolve (display: contents). -->
  <div class="contents lg:flex lg:flex-col lg:gap-6 xl:grid xl:grid-cols-[1fr_22rem] xl:items-start xl:gap-10">
  <div class="contents lg:flex lg:flex-col lg:gap-6">
  <div class="flex items-center justify-between gap-5">
  <ul class="flex gap-5 text-base text-muted lg:text-lg" aria-label="Selgitus">
    <li v-for="(label, key) in phases" :key="key" class="flex items-center gap-2">
      <span class="size-4 rounded" :class="cellColor[key]" />{{ label }}
    </li>
  </ul>
  <SortSwitch v-model="sort" :options="sorts" class="hidden lg:flex" />
  </div>

  <section class="grain card grid grid-cols-[5.5rem_repeat(12,1fr)] items-center gap-y-1.5 px-3 lg:grid-cols-[10rem_repeat(12,1fr)] lg:gap-y-2.5 lg:px-6 lg:py-6">
    <span />
    <button
      v-for="(m, i) in months"
      :key="m"
      type="button"
      :aria-label="m"
      :aria-pressed="i === selected"
      class="h-11 rounded-full text-center text-sm uppercase lg:h-9 lg:text-base lg:normal-case"
      :class="i === selected ? 'grain shadow-neu-inset' : i === now ? 'text-sage' : 'text-muted'"
      @click="selected = i"
    >
      <span class="lg:hidden">{{ m[0] }}</span><span class="hidden lg:inline">{{ m.slice(0, 3) }}</span>
    </button>

    <!-- Keyed by order too, so a re-sort fades the rows in instead of jumping. -->
    <template v-for="p in sorted" :key="sort + p.id">
      <RouterLink :to="`/mari/${p.id}`" class="fade-in truncate pr-1 text-[15px] leading-8 underline-offset-4 active:underline lg:text-lg lg:hover:underline">
        {{ p.name }}
      </RouterLink>
      <span
        v-for="(c, i) in p.season"
        :key="i"
        class="fade-in mx-px h-6 cursor-pointer rounded-sm lg:mx-0.5 lg:h-8"
        @click="selected = i"
        :class="cellColor[c] || (i === selected && 'bg-sage/8')"
        :title="c !== '.' ? `${months[i]}: ${phases[c]}` : undefined"
      />
    </template>
  </section>

  <p class="font-hand text-lg text-muted lg:text-xl">Puuduta marja nime, et rohkem lugeda.</p>
  </div>

  <section class="flex flex-col gap-3">
    <h2 class="ruled text-2xl leading-7">Kuu kaupa</h2>
    <div class="grain card flex flex-col gap-3">
      <div class="flex items-center justify-between">
        <button type="button" aria-label="Eelmine kuu" class="grain press grid size-11 place-items-center rounded-full shadow-neu-raised-sm active:shadow-neu-inset" @click="step(-1)">
          <AppIcon name="back" :size="18" />
        </button>
        <span class="text-xl">{{ months[selected] }}<span v-if="selected === now" class="font-hand text-base text-muted"> · see kuu</span></span>
        <button type="button" aria-label="Järgmine kuu" class="grain press grid size-11 place-items-center rounded-full shadow-neu-raised-sm active:shadow-neu-inset" @click="step(1)">
          <AppIcon name="arrow" :size="18" />
        </button>
      </div>
      <ul v-if="inMonth.length" :key="selected" class="fade-in flex flex-col gap-1">
        <li v-for="p in inMonth" :key="p.id">
          <RouterLink :to="`/mari/${p.id}`" class="press flex items-center gap-3 text-lg lg:text-xl">
            <span class="flex items-center gap-3"><BerryGlyph :name="p.id" :image="p.image" :size="64" class="hidden lg:block" />{{ p.name }}</span><span class="leader" /><span class="font-hand text-base text-muted">{{ statusText[p.season[selected]] }}</span>
          </RouterLink>
        </li>
      </ul>
      <p v-else class="italic text-muted">Sel kuul pole midagi korjata.</p>
    </div>
  </section>
  </div>
</template>
