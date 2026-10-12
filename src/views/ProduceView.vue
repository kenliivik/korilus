<script setup>
import { computed, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import AppIcon from '../components/AppIcon.vue'
import RecipeCard from '../components/RecipeCard.vue'
import { findProduce, phaseIn, statusText, recipesFor } from '../data/berries.js'
import { store, toggleReminder } from '../data/store.js'

const props = defineProps({ id: { type: String, required: true } })

const p = computed(() => findProduce(props.id))
// Spots where this was found, with the latest date it was found there.
const foundAt = computed(() =>
  store.locations.flatMap((l) => {
    const v = l.visits.find((v) => v.berries.includes(props.id))
    return v ? [{ id: l.id, name: l.name, date: v.date }] : []
  }),
)
const linkedRecipes = computed(() => recipesFor(props.id))
const reminded = computed(() => store.reminders.includes(props.id))
const noPush = ref(false)
const remind = async () => (noPush.value = !(await toggleReminder(props.id)) && reminded.value)
</script>

<template>
  <template v-if="p">
    <PageHeader :title="p.name" back="/" />

    <section class="flex flex-col items-center gap-2">
      <span class="grain grid size-48 place-items-center rounded-full shadow-neu-inset">
        <BerryGlyph :name="p.id" :image="p.image" :size="176" />
      </span>
      <p class="italic text-muted">{{ p.latin }}</p>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Küpsemisaeg</h2>
      <dl class="grain card grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-lg">
        <template v-for="(value, label) in p.dates" :key="label">
          <dt class="text-muted">{{ label }}</dt>
          <dd>{{ value }}</dd>
        </template>
        <dt class="text-muted">Praegu</dt>
        <dd class="font-hand text-xl">{{ statusText[phaseIn(p)] }}</dd>
      </dl>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Korjamise nõuanded</h2>
      <ul class="grain card flex list-inside list-disc flex-col gap-1 text-lg marker:text-leaf">
        <li v-for="tip in p.tips" :key="tip">{{ tip }}</li>
      </ul>
    </section>

    <section v-if="linkedRecipes.length" class="flex flex-col gap-3">
      <h2 class="flex items-baseline justify-between text-2xl leading-7">
        Retseptid
        <RouterLink :to="`/retseptid?mari=${p.id}`" class="text-base text-muted underline underline-offset-4">Kõik retseptid</RouterLink>
      </h2>
      <RecipeCard v-for="r in linkedRecipes" :key="r.id" :recipe="r" />
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Kus leidsid</h2>
      <ul v-if="foundAt.length" class="grain card flex flex-col gap-2 text-lg">
        <li v-for="f in foundAt" :key="f.id">
          <RouterLink :to="`/asukohad/${f.id}`" class="flex items-center gap-2">
            <AppIcon name="pin" :size="18" />{{ f.name }} <span class="ml-auto text-base text-muted">{{ f.date }}</span>
          </RouterLink>
        </li>
      </ul>
      <p v-else class="italic text-muted">Sa pole seda veel üheski kohas märkinud.</p>
    </section>

    <button type="button" class="btn" :class="reminded ? 'grain text-sage' : 'bg-sage text-milk'" @click="remind">
      <AppIcon name="bell" :size="20" />
      {{ reminded ? 'Meeldetuletus on sees' : 'Märguanne, kui küps' }}
    </button>
    <p v-if="noPush" class="italic text-muted">
      Meeldetuletus on salvestatud, kuid see seade ei luba teavitusi. iPhone'is lisa Korilus enne avaekraanile (Jaga → Lisa avaekraanile).
    </p>
  </template>

  <PageHeader v-else title="Sellist marja ei leitud" back="/" />
</template>
