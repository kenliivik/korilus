<script setup>
import { computed, nextTick, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import StarRating from '../components/StarRating.vue'
import VisitFields from '../components/VisitFields.vue'
import SpotMap from '../components/SpotMap.vue'
import { directionsUrl } from '../components/map.js'
import { findProduce } from '../data/berries.js'
import { findLocation, berriesAt, averageRating, today, formatDate, touch } from '../data/store.js'
import { account } from '../data/account.js'

const props = defineProps({ id: { type: String, required: true } })

const loc = computed(() => findLocation(props.id))
const groupName = computed(() => account.groups.find((g) => g.id === loc.value?.groupId)?.name)
// "14. sept" for the ledger margin; the year is the heading above.
const shortDate = (d) => new Date(`${d}T12:00`).toLocaleDateString('et-EE', { day: 'numeric', month: 'short' })
const names = (ids) => ids.map((id) => findProduce(id)?.name).join(', ')
// Visits grouped by year (newest first), so seasons can be compared.
const years = computed(() =>
  loc.value.visits.reduce((m, v) => m.set(v.date.slice(0, 4), [...(m.get(v.date.slice(0, 4)) ?? []), v]), new Map()),
)

// Newest visit first; dates are YYYY-MM-DD so they sort as text.
const sortVisits = () => loc.value.visits.sort((a, b) => b.date.localeCompare(a.date))

// The visit just added or edited lights up in the log and scrolls into view.
const justSaved = ref(null)
async function showSaved(v) {
  justSaved.value = v
  await nextTick()
  document.querySelector('.just-saved')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  setTimeout(() => justSaved.value === v && (justSaved.value = null), 1400)
}

const draft = ref(null)
function addVisit() {
  loc.value.visits.push(draft.value)
  sortVisits()
  touch(loc.value)
  showSaved(draft.value)
  draft.value = null
}

// Tapping an earlier visit opens it for editing in place: { i: index in loc.visits, visit: a copy }.
const editing = ref(null)
const editVisit = (v) => (editing.value = { i: loc.value.visits.indexOf(v), visit: JSON.parse(JSON.stringify(v)) })
function saveVisit() {
  loc.value.visits[editing.value.i] = editing.value.visit
  sortVisits()
  touch(loc.value)
  showSaved(editing.value.visit)
  editing.value = null
}
function deleteVisit() {
  if (!confirm('Kas kustutad selle käigu?')) return
  loc.value.visits.splice(editing.value.i, 1)
  touch(loc.value)
  editing.value = null
}

const copied = ref(false)
async function copyCoords() {
  await navigator.clipboard?.writeText(`${loc.value.lat.toFixed(5)}, ${loc.value.lng.toFixed(5)}`)
  copied.value = true
  setTimeout(() => (copied.value = false), 1800)
}
</script>

<template>
  <template v-if="loc">
    <PageHeader :title="loc.name" back="/asukohad" />
    <p v-if="loc.shared" class="font-hand text-xl text-muted">{{ loc.owner }} jagas seda{{ groupName ? ` grupis „${groupName}”` : '' }}</p>
    <p v-else-if="groupName" class="font-hand text-xl text-muted">Näevad ka grupi „{{ groupName }}” liikmed</p>

    <!-- Desktop: map, actions and overview on the left, the visit log on the right. On phones the wrappers dissolve. -->
    <div class="contents lg:grid lg:grid-cols-2 lg:items-start lg:gap-10">
    <div class="contents lg:flex lg:flex-col lg:gap-6">
    <section v-if="loc.lat != null" class="flex flex-col gap-3">
      <SpotMap :spots="[loc]" small />
      <button type="button" class="self-center text-base text-muted" @click="copyCoords">
        {{ copied ? 'Koordinaadid kopeeritud' : `${loc.lat.toFixed(5)}, ${loc.lng.toFixed(5)}` }}
      </button>
    </section>

    <div class="grid gap-3" :class="!loc.shared && 'grid-cols-2'">
      <a v-if="loc.lat != null" :href="directionsUrl(loc)" target="_blank" rel="noopener" class="btn grain text-sage">
        <AppIcon name="pin" :size="20" /> Juhata kohale
      </a>
      <button v-if="!loc.shared && !draft" type="button" class="btn bg-sage text-milk" @click="draft = { date: today(), berries: [], note: '', rating: 0 }">
        <AppIcon name="plus" :size="20" /> Käisin siin
      </button>
    </div>

    <form v-if="draft" class="grain card flex flex-col gap-4" @submit.prevent="addVisit">
      <h2 class="text-2xl leading-7">Uus käik</h2>
      <VisitFields v-model="draft" />
      <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
      <button type="button" class="btn grain text-sage" @click="draft = null">Tühista</button>
    </form>

    <dl class="flex flex-col gap-2 text-lg">
      <div class="flex items-baseline gap-2"><dt class="text-muted">Marjad</dt><span class="leader" /><dd>{{ names(berriesAt(loc)) || '–' }}</dd></div>
      <div class="flex items-baseline gap-2"><dt class="text-muted">Hinne</dt><span class="leader" /><dd><StarRating :model-value="averageRating(loc)" readonly /></dd></div>
      <div class="flex items-baseline gap-2"><dt class="text-muted">Käidud</dt><span class="leader" /><dd>{{ loc.visits.length }} korda</dd></div>
    </dl>
    </div>

    <section class="flex flex-col">
      <h2 class="ruled mb-2 text-2xl leading-7 lg:text-3xl lg:leading-9">Käigud</h2>
      <template v-for="[year, visits] in years" :key="year">
        <h3 class="mt-3 font-hand text-xl text-muted">{{ year }}</h3>
        <template v-for="(v, i) in visits" :key="i">
          <form v-if="editing && loc.visits[editing.i] === v" class="grain card my-2 flex flex-col gap-4" @submit.prevent="saveVisit">
            <VisitFields v-model="editing.visit" />
            <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
            <div class="grid grid-cols-2 gap-4">
              <button type="button" class="btn grain text-sage" @click="editing = null">Tühista</button>
              <button type="button" class="btn grain text-danger" @click="deleteVisit"><AppIcon name="trash" :size="20" /> Kustuta käik</button>
            </div>
          </form>
          <!-- A ledger row, like a page of a field notebook: date in the margin, then what was found. -->
          <component
            :is="loc.shared ? 'article' : 'button'"
            v-else
            :type="loc.shared ? undefined : 'button'"
            :aria-label="loc.shared ? undefined : `Muuda käiku ${formatDate(v.date)}`"
            class="grid grid-cols-[4.5rem_1fr] gap-x-3 border-b border-sage/15 py-3 text-left"
            :class="[!loc.shared && 'press', v === justSaved && 'just-saved']"
            @click="loc.shared || editVisit(v)"
          >
            <span class="font-hand text-lg leading-7 whitespace-nowrap text-muted">{{ shortDate(v.date) }}</span>
            <span class="flex flex-col gap-0.5">
              <span class="flex items-center justify-between gap-2">{{ names(v.berries) || '–' }} <StarRating v-if="v.rating" :model-value="v.rating" readonly :size="14" /></span>
              <span v-if="v.note" class="italic text-muted">„{{ v.note }}”</span>
            </span>
          </component>
        </template>
      </template>
      <p v-if="!loc.visits.length" class="italic text-muted">Siin pole veel käidud.</p>
    </section>
    </div>

    <RouterLink v-if="!loc.shared" :to="`/asukohad/${loc.id}/muuda`" class="btn grain text-sage lg:self-start">
      <AppIcon name="edit" :size="20" /> Muuda kohta
    </RouterLink>
  </template>

  <PageHeader v-else title="Seda kohta ei leitud" back="/asukohad" />
</template>
