<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import StarRating from '../components/StarRating.vue'
import VisitFields from '../components/VisitFields.vue'
import { findProduce } from '../data/berries.js'
import { findLocation, berriesAt, averageRating, today, touch, removeLocation } from '../data/store.js'
import { account } from '../data/account.js'

const props = defineProps({ id: { type: String, required: true } })
const router = useRouter()

const loc = computed(() => findLocation(props.id))
const groupName = computed(() => account.groups.find((g) => g.id === loc.value?.groupId)?.name)
const names = (ids) => ids.map((id) => findProduce(id)?.name).join(', ')
// Visits grouped by year (newest first), so seasons can be compared.
const years = computed(() =>
  loc.value.visits.reduce((m, v) => m.set(v.date.slice(0, 4), [...(m.get(v.date.slice(0, 4)) ?? []), v]), new Map()),
)

const draft = ref(null)
function addVisit() {
  loc.value.visits.unshift({ date: today(), ...draft.value })
  touch(loc.value)
  draft.value = null
}

function remove() {
  if (!confirm(`Kas kustutada „${loc.value.name}” koos kõigi märkustega?`)) return
  removeLocation(loc.value)
  router.replace('/asukohad')
}
</script>

<template>
  <template v-if="loc">
    <PageHeader :title="loc.name" back="/asukohad" />
    <p v-if="loc.shared" class="font-hand text-xl text-muted">Jagab {{ loc.owner }}{{ groupName ? ` grupis „${groupName}”` : '' }}</p>
    <p v-else-if="groupName" class="font-hand text-xl text-muted">Jagatud grupiga „{{ groupName }}”</p>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Asukoha info</h2>
      <dl class="grain card grid grid-cols-[auto_1fr] items-center gap-x-4 gap-y-1 text-lg">
        <dt class="text-muted">Marjad</dt>
        <dd>{{ names(berriesAt(loc)) || '–' }}</dd>
        <dt class="text-muted">Kvaliteet</dt>
        <dd><StarRating :model-value="averageRating(loc)" readonly /></dd>
        <dt class="text-muted">Külastatud</dt>
        <dd>{{ loc.visits.length }} korda</dd>
      </dl>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Külastuste ajalugu</h2>
      <template v-for="[year, visits] in years" :key="year">
        <h3 class="font-hand text-xl text-muted">{{ year }}</h3>
        <article v-for="(v, i) in visits" :key="i" class="grain card flex flex-col gap-1">
          <p class="flex items-center justify-between">{{ v.date }} <StarRating :model-value="v.rating" readonly :size="14" /></p>
          <p class="text-muted">{{ names(v.berries) }}</p>
          <p v-if="v.note" class="italic">„{{ v.note }}”</p>
        </article>
      </template>
    </section>

    <template v-if="!loc.shared">
      <form v-if="draft" class="grain card flex flex-col gap-4" @submit.prevent="addVisit">
        <h2 class="text-2xl leading-7">Tänane külastus</h2>
        <VisitFields v-model="draft" />
        <button type="submit" class="btn bg-sage text-milk">Salvesta märkus</button>
        <button type="button" class="btn grain text-sage" @click="draft = null">Tühista</button>
      </form>
      <button v-else type="button" class="btn bg-sage text-milk" @click="draft = { berries: [], note: '', rating: 0 }">
        <AppIcon name="plus" :size="20" /> Lisa märkus tänasel külastusel
      </button>

      <div class="grid grid-cols-2 gap-4">
        <RouterLink :to="`/asukohad/${loc.id}/muuda`" class="btn grain text-sage"><AppIcon name="edit" :size="20" /> Muuda</RouterLink>
        <button type="button" class="btn grain text-[#9c3a3a]" @click="remove"><AppIcon name="trash" :size="20" /> Kustuta</button>
      </div>
    </template>
  </template>

  <PageHeader v-else title="Asukohta ei leitud" back="/asukohad" />
</template>
