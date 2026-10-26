<script setup>
import { computed, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import AppIcon from '../components/AppIcon.vue'
import RecipeCard from '../components/RecipeCard.vue'
import { findProduce, phaseIn, statusText, dateLabel } from '../data/berries.js'
import { recipesFor } from '../data/recipes.js'
import { store, toggleReminder, formatDate } from '../data/store.js'

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

    <!-- Desktop: a plate with the picture, season and reminder on the left, tips, recipes and spots on the right. On phones the wrappers dissolve. -->
    <div class="contents lg:grid lg:grid-cols-[20rem_1fr] lg:items-start lg:gap-12 xl:grid-cols-[24rem_1fr]">
    <aside class="contents lg:sticky lg:top-12 lg:flex lg:flex-col lg:gap-6">
    <section class="flex flex-col items-center gap-2 lg:hidden">
      <BerryGlyph :name="p.id" :image="p.image" :size="200" />
      <p class="italic text-muted">{{ p.latin }}</p>
    </section>
    <section class="grain card mt-20 hidden flex-col items-center gap-2 lg:flex">
      <BerryGlyph :name="p.id" :image="p.image" :size="256" class="-mt-24" />
      <p class="text-xl italic text-muted">{{ p.latin }}</p>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Valmimisaeg</h2>
      <dl class="grain card grid grid-cols-[auto_1fr] gap-x-4 px-5 text-lg lg:text-xl [&>*]:border-b [&>*]:border-sage/12 [&>*]:py-1.5 [&>*:nth-last-child(-n+2)]:border-0">
        <template v-for="(value, label) in p.dates" :key="label">
          <dt class="text-muted">{{ dateLabel(label) }}</dt>
          <dd>{{ value }}</dd>
        </template>
        <dt class="text-muted">Praegu</dt>
        <dd class="font-hand text-xl">{{ statusText[phaseIn(p)] }}</dd>
      </dl>
    </section>

    <button type="button" class="btn" :class="reminded ? 'grain text-sage' : 'bg-sage text-milk'" @click="remind">
      <AppIcon name="bell" :size="20" />
      <Transition name="swap" mode="out-in">
        <span :key="reminded">{{ reminded ? 'Annan teada ✓' : 'Anna teada, kui valmib' }}</span>
      </Transition>
    </button>
    <p v-if="noPush" class="italic text-muted">
      Jätsin meelde, aga see telefon ei luba teateid saata. iPhone'is lisa Korilus enne avakuvale (Jaga → Lisa avakuvale).
    </p>
    </aside>

    <div class="contents lg:flex lg:flex-col lg:gap-8">
    <section class="flex flex-col gap-3">
      <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Korjaja nõuanded</h2>
      <ul class="grain card flex list-outside list-disc flex-col gap-1 pl-9 text-lg marker:text-leaf lg:gap-2 lg:p-6 lg:pl-11 lg:text-xl">
        <li v-for="tip in p.tips" :key="tip">{{ tip }}</li>
      </ul>
    </section>

    <section v-if="linkedRecipes.length" class="flex flex-col gap-3">
      <h2 class="flex items-baseline justify-between text-2xl leading-7 lg:text-3xl lg:leading-9">
        Retseptid
        <RouterLink :to="`/retseptid?mari=${p.id}`" class="text-base text-muted underline underline-offset-4">Kõik retseptid</RouterLink>
      </h2>
      <RecipeCard v-for="r in linkedRecipes" :key="r.id" :recipe="r" />
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Minu kohad</h2>
      <ul v-if="foundAt.length" class="grain card flex flex-col gap-2 text-lg lg:p-6 lg:text-xl">
        <li v-for="f in foundAt" :key="f.id">
          <RouterLink :to="`/asukohad/${f.id}`" class="press flex items-center gap-2">
            <AppIcon name="pin" :size="18" />{{ f.name }} <span class="ml-auto text-base text-muted">{{ formatDate(f.date) }}</span>
          </RouterLink>
        </li>
      </ul>
      <p v-else class="italic text-muted">Sul pole veel ühtegi kohta, kus see kasvab.</p>
    </section>
    </div>
    </div>
  </template>

  <PageHeader v-else title="Seda marja ei leitud" back="/" />
</template>
