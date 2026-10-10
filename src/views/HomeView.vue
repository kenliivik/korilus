<script setup>
import BerryTile from '../components/BerryTile.vue'
import LinkCard from '../components/LinkCard.vue'
import BottomNav from '../components/BottomNav.vue'
import { ripeBerries } from '../data/berries.js'

const month = new Intl.DateTimeFormat('et-EE', { month: 'long' }).format(new Date())
</script>

<template>
  <div class="paper min-h-dvh">
    <main class="mx-auto flex max-w-[430px] flex-col gap-6 px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-32">
      <header class="flex items-center justify-between">
        <h1 class="font-serif-sc text-[32px] leading-none tracking-wide text-sage">Korilus</h1>
        <span class="font-hand text-lg text-muted">{{ month }}</span>
      </header>

      <section class="flex flex-col gap-1">
        <h2 class="text-[32px] leading-9 text-sage">Mis on praegu küps?</h2>
        <p class="italic leading-5 text-muted">Kuus liiki ootab korjamist</p>
      </section>

      <section class="grid grid-cols-3 gap-x-3 gap-y-4" aria-label="Praegu küpsed marjad">
        <BerryTile v-for="b in ripeBerries" :key="b.id" :name="b.name" :glyph="b.glyph" />
      </section>

      <section class="flex flex-col gap-3">
        <h2 class="text-2xl leading-7 text-sage">Kuukalender</h2>
        <LinkCard label="Vaata täit kuukalendrit" icon="calendar" />
      </section>

      <section class="flex flex-col gap-3">
        <h2 class="text-2xl leading-7 text-sage">Asukohad</h2>
        <LinkCard label="Salvesta marjakoha asukohad" icon="pin" />
      </section>
    </main>

    <BottomNav active="home" />
  </div>
</template>
