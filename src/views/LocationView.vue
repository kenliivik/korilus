<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import StarRating from '../components/StarRating.vue'
import VisitFields from '../components/VisitFields.vue'
import SpotMap from '../components/SpotMap.vue'
import { directionsUrl } from '../components/map.js'
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

// Newest visit first; dates are YYYY-MM-DD so they sort as text.
const sortVisits = () => loc.value.visits.sort((a, b) => b.date.localeCompare(a.date))

const draft = ref(null)
function addVisit() {
  loc.value.visits.push(draft.value)
  sortVisits()
  touch(loc.value)
  draft.value = null
}

// Tapping an earlier visit opens it for editing in place: { i: index in loc.visits, visit: a copy }.
const editing = ref(null)
const editVisit = (v) => (editing.value = { i: loc.value.visits.indexOf(v), visit: JSON.parse(JSON.stringify(v)) })
function saveVisit() {
  loc.value.visits[editing.value.i] = editing.value.visit
  sortVisits()
  touch(loc.value)
  editing.value = null
}
function deleteVisit() {
  if (!confirm('Kas kustutad selle käigu?')) return
  loc.value.visits.splice(editing.value.i, 1)
  touch(loc.value)
  editing.value = null
}

function remove() {
  if (!confirm(`Kas kustutad „${loc.value.name}” koos kõigi märkmetega?`)) return
  removeLocation(loc.value)
  router.replace('/asukohad')
}
</script>

<template>
  <template v-if="loc">
    <PageHeader :title="loc.name" back="/asukohad" />
    <p v-if="loc.shared" class="font-hand text-xl text-muted">{{ loc.owner }} jagas seda{{ groupName ? ` grupis „${groupName}”` : '' }}</p>
    <p v-else-if="groupName" class="font-hand text-xl text-muted">Näevad ka grupi „{{ groupName }}” liikmed</p>

    <!-- Desktop: overview and actions on the left, the visit log on the right. On phones the wrappers dissolve
         and the actions (order-1) drop below the log as before. -->
    <div class="contents lg:grid lg:grid-cols-2 lg:items-start lg:gap-10">
    <div class="contents lg:flex lg:flex-col lg:gap-6">
    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7 lg:text-3xl lg:leading-9">Ülevaade</h2>
      <dl class="grain card grid grid-cols-[auto_1fr] items-center gap-x-4 gap-y-1 text-lg">
        <dt class="text-muted">Marjad</dt>
        <dd>{{ names(berriesAt(loc)) || '–' }}</dd>
        <dt class="text-muted">Hinne</dt>
        <dd><StarRating :model-value="averageRating(loc)" readonly /></dd>
        <dt class="text-muted">Käidud</dt>
        <dd>{{ loc.visits.length }} korda</dd>
      </dl>
    </section>

    <section v-if="loc.lat != null" class="flex flex-col gap-3">
      <SpotMap :spots="[loc]" small />
      <a :href="directionsUrl(loc)" target="_blank" rel="noopener" class="btn grain text-sage">
        <AppIcon name="pin" :size="20" /> Juhata kohale
      </a>
      <p class="text-center text-base text-muted">{{ loc.lat.toFixed(5) }}, {{ loc.lng.toFixed(5) }}</p>
    </section>

    <template v-if="!loc.shared">
      <form v-if="draft" class="grain card order-1 flex flex-col gap-4 lg:order-none" @submit.prevent="addVisit">
        <h2 class="text-2xl leading-7">Uus käik</h2>
        <VisitFields v-model="draft" />
        <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
        <button type="button" class="btn grain text-sage" @click="draft = null">Tühista</button>
      </form>
      <button v-else type="button" class="btn order-1 bg-sage text-milk lg:order-none" @click="draft = { date: today(), berries: [], note: '', rating: 0 }">
        <AppIcon name="plus" :size="20" /> Pane käik kirja
      </button>

      <div class="order-1 grid grid-cols-2 gap-4 lg:order-none">
        <RouterLink :to="`/asukohad/${loc.id}/muuda`" class="btn grain text-sage"><AppIcon name="edit" :size="20" /> Muuda</RouterLink>
        <button type="button" class="btn grain text-[#9c3a3a]" @click="remove"><AppIcon name="trash" :size="20" /> Kustuta</button>
      </div>
    </template>
    </div>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7 lg:text-3xl lg:leading-9">Varasemad käigud</h2>
      <template v-for="[year, visits] in years" :key="year">
        <h3 class="font-hand text-xl text-muted">{{ year }}</h3>
        <template v-for="(v, i) in visits" :key="i">
          <form v-if="editing && loc.visits[editing.i] === v" class="grain card flex flex-col gap-4" @submit.prevent="saveVisit">
            <VisitFields v-model="editing.visit" />
            <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
            <div class="grid grid-cols-2 gap-4">
              <button type="button" class="btn grain text-sage" @click="editing = null">Tühista</button>
              <button type="button" class="btn grain text-[#9c3a3a]" @click="deleteVisit"><AppIcon name="trash" :size="20" /> Kustuta käik</button>
            </div>
          </form>
          <component
            :is="loc.shared ? 'article' : 'button'"
            v-else
            :type="loc.shared ? undefined : 'button'"
            :aria-label="loc.shared ? undefined : `Muuda käiku ${v.date}`"
            class="grain card flex flex-col gap-1 text-left"
            :class="!loc.shared && 'transition-shadow active:shadow-neu-inset'"
            @click="loc.shared || editVisit(v)"
          >
            <span class="flex w-full items-center justify-between">{{ v.date }} <StarRating :model-value="v.rating" readonly :size="14" /></span>
            <span class="text-muted">{{ names(v.berries) }}</span>
            <span v-if="v.note" class="italic">„{{ v.note }}”</span>
          </component>
        </template>
      </template>
    </section>
    </div>
  </template>

  <PageHeader v-else title="Seda kohta ei leitud" back="/asukohad" />
</template>
