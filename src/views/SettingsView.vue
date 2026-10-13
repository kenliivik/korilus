<script setup>
import { computed } from 'vue'
import AppIcon from '../components/AppIcon.vue'
import LinkCard from '../components/LinkCard.vue'
import { findProduce } from '../data/berries.js'
import { store, toggleReminder, toggleNotify } from '../data/store.js'
import { account } from '../data/account.js'

const toggles = [
  { key: 'start', label: 'Marjad hakkavad valmima' },
  { key: 'peak', label: 'Marjad on tipus' },
]
const notes = computed(() => store.locations.flatMap((l) => l.visits).filter((v) => v.note).length)
</script>

<template>
  <h1 class="text-[32px] leading-9">Seaded</h1>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Konto</h2>
    <RouterLink to="/konto" class="grain card flex items-center justify-between gap-3 text-lg transition-shadow active:shadow-neu-inset">
      <span class="truncate">{{ account.user ? account.user.email : 'Logi sisse või loo grupp' }}</span>
      <AppIcon name="arrow" :size="20" />
    </RouterLink>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Märguanded</h2>
    <button
      v-for="t in toggles"
      :key="t.key"
      type="button"
      role="switch"
      :aria-checked="store.notify[t.key]"
      class="grain card flex items-center justify-between text-left text-lg"
      @click="toggleNotify(t.key)"
    >
      {{ t.label }}
      <span class="flex w-14 rounded-full p-1 shadow-neu-inset" :class="store.notify[t.key] ? 'justify-end bg-leaf/30' : 'grain'">
        <span class="size-5 rounded-full shadow-neu-raised-sm" :class="store.notify[t.key] ? 'bg-sage' : 'grain'" />
      </span>
    </button>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Aktiivsed meeldetuletused</h2>
    <ul v-if="store.reminders.length" class="grain card flex flex-col gap-2 text-lg">
      <li v-for="id in store.reminders" :key="id" class="flex items-center justify-between">
        <RouterLink :to="`/mari/${id}`">{{ findProduce(id)?.name }}</RouterLink>
        <button type="button" class="text-base text-muted underline underline-offset-4" @click="toggleReminder(id)">Eemalda</button>
      </li>
    </ul>
    <p v-else class="italic text-muted">Lisa meeldetuletus marja lehelt.</p>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Keel</h2>
    <p class="grain card text-lg">Eesti ✓</p>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Andmed</h2>
    <dl class="grain card grid grid-cols-[1fr_auto] gap-y-1 text-lg">
      <dt>Asukohad</dt><dd>{{ store.locations.length }} kohta</dd>
      <dt>Märkused</dt><dd>{{ notes }} märkust</dd>
    </dl>
    <p class="text-base italic text-muted">{{ account.user ? 'Andmed on selles seadmes ja sünkroonitakse sinu kontoga.' : 'Andmed on salvestatud ainult selles seadmes.' }}</p>
  </section>

  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Rakenduse kohta</h2>
    <LinkCard label="Saada ettepanek või tagasiside" icon="chat" to="/tagasiside" />
    <p class="grain card text-lg">Korilus v1.0</p>
  </section>
</template>
