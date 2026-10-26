<script setup>
import { computed, nextTick, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import StarRating from '../components/StarRating.vue'
import VisitFields from '../components/VisitFields.vue'
import SpotMap from '../components/SpotMap.vue'
import { directionsUrl } from '../components/map.js'
import { findProduce } from '../data/berries.js'
import { findLocation, today, formatDate, myVisits, touchVisits, allVisits } from '../data/store.js'
import { account } from '../data/account.js'
import { ask, guardLeave } from '../data/confirm.js'
import { amountOf, litres, bestTimes, usualDay } from '../data/harvest.js'

const props = defineProps({ id: { type: String, required: true } })

const loc = computed(() => findLocation(props.id))
const groupName = computed(() => account.groups.find((g) => g.id === loc.value?.groupId)?.name)
// "14. sept" for the ledger margin; the year is the heading above.
const shortDate = (d) => new Date(`${d}T12:00`).toLocaleDateString('et-EE', { day: 'numeric', month: 'short' })
const names = (ids) => ids.map((id) => findProduce(id)?.name).join(', ')
// Everyone's visits (the user's own can be edited; on a shared spot the user can add theirs too),
// grouped by year (newest first), so seasons can be compared.
const rows = computed(() => allVisits(loc.value))
// "Mustikas 3 l, pohl" for a ledger row (the harvest log), and the litres picked in a year (everyone's).
const picked = (v) => v.berries.map((id) => [findProduce(id)?.name, amountOf(v, id) && litres(amountOf(v, id))].filter(Boolean).join(' ')).join(', ')
const yearTotal = (list) => list.reduce((sum, { v }) => sum + v.berries.reduce((s, id) => s + amountOf(v, id), 0), 0)
// When each berry is at its best here, from everyone's logged hauls.
const bestHere = computed(() =>
  allBerries.value.flatMap((id) => {
    const t = bestTimes(rows.value.map((r) => r.v), id)
    return t?.best ? [{ id, name: findProduce(id)?.name ?? id, day: usualDay(t.best), years: t.bestYears }] : []
  }),
)
const years = computed(() =>
  rows.value.reduce((m, r) => m.set(r.v.date.slice(0, 4), [...(m.get(r.v.date.slice(0, 4)) ?? []), r]), new Map()),
)
// The overview counts every visit; visits saved without stars don't count for the rating.
const allBerries = computed(() => [...new Set(rows.value.flatMap((r) => r.v.berries))])
const rating = computed(() => {
  const rated = rows.value.filter((r) => r.v.rating)
  return Math.round(rated.reduce((sum, r) => sum + r.v.rating, 0) / (rated.length || 1))
})

// The list the user's own visits live in. Newest first; dates are YYYY-MM-DD so they sort as text.
const mine = () => myVisits(loc.value)
const sortVisits = () => mine().sort((a, b) => b.date.localeCompare(a.date))

// The visit just added or edited lights up in the log and scrolls into view.
const justSaved = ref(null)
async function showSaved(v) {
  justSaved.value = v
  await nextTick()
  document.querySelector('.just-saved')?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
  setTimeout(() => justSaved.value === v && (justSaved.value = null), 1400)
}

const draft = ref(null)
const blankVisit = () => ({ date: today(), berries: [], note: '', rating: 0 })
// An open visit form with something in it asks before the page is left.
guardLeave(
  () =>
    (draft.value && JSON.stringify(draft.value) !== JSON.stringify(blankVisit())) ||
    (editing.value && JSON.stringify(editing.value.visit) !== JSON.stringify(mine()[editing.value.i])),
)
function addVisit() {
  mine().push(draft.value)
  sortVisits()
  touchVisits(loc.value)
  showSaved(draft.value)
  draft.value = null
}

// Tapping one of the user's earlier visits opens it for editing in place: { i: index in mine(), visit: a copy }.
const editing = ref(null)
const editVisit = (v) => (editing.value = { i: mine().indexOf(v), visit: JSON.parse(JSON.stringify(v)) })
function saveVisit() {
  mine()[editing.value.i] = editing.value.visit
  sortVisits()
  touchVisits(loc.value)
  showSaved(editing.value.visit)
  editing.value = null
}
async function deleteVisit() {
  if (!(await ask('Kas kustutad selle käigu?'))) return
  mine().splice(editing.value.i, 1)
  touchVisits(loc.value)
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
      <button type="button" class="self-center text-base text-muted" aria-live="polite" @click="copyCoords">
        {{ copied ? 'Koordinaadid kopeeritud' : `${loc.lat.toFixed(5)}, ${loc.lng.toFixed(5)}` }}
      </button>
    </section>

    <div class="grid gap-3" :class="loc.lat != null && !draft && 'grid-cols-2'">
      <a v-if="loc.lat != null" :href="directionsUrl(loc)" target="_blank" rel="noopener" class="btn grain text-sage">
        <AppIcon name="pin" :size="20" /> Juhata kohale
      </a>
      <button v-if="!draft" type="button" class="btn bg-sage text-milk" @click="draft = blankVisit()">
        <AppIcon name="plus" :size="20" /> Käisin siin
      </button>
    </div>

    <form v-if="draft" class="grain card settle flex flex-col gap-4" @submit.prevent="addVisit">
      <h2 class="text-2xl leading-7">Uus käik</h2>
      <VisitFields v-model="draft" />
      <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
      <button type="button" class="btn grain text-sage" @click="draft = null">Tühista</button>
    </form>

    <dl class="flex flex-col gap-2 text-lg">
      <div class="flex items-baseline gap-2"><dt class="text-muted">Marjad</dt><span class="leader" /><dd>{{ names(allBerries) || '–' }}</dd></div>
      <div class="flex items-baseline gap-2"><dt class="text-muted">Hinne</dt><span class="leader" /><dd><StarRating :model-value="rating" readonly /></dd></div>
      <div class="flex items-baseline gap-2"><dt class="text-muted">Käidud</dt><span class="leader" /><dd>{{ rows.length }} korda</dd></div>
      <div v-for="b in bestHere" :key="b.id" class="flex items-baseline gap-2">
        <dt class="text-muted">{{ b.name }} parim</dt><span class="leader" />
        <dd class="whitespace-nowrap">{{ b.day }} <span v-if="b.years > 1" class="text-base text-muted">({{ b.years }} aastat)</span></dd>
      </div>
    </dl>
    </div>

    <section class="flex flex-col">
      <h2 class="ruled mb-2 text-2xl leading-7 lg:text-3xl lg:leading-9">Käigud</h2>
      <template v-for="[year, list] in years" :key="year">
        <h3 class="mt-3 font-hand text-xl text-muted">{{ year }}<template v-if="yearTotal(list)"> · kokku {{ litres(yearTotal(list)) }}</template></h3>
        <template v-for="({ v, mine: own, name }, i) in list" :key="i">
          <form v-if="editing && own && mine()[editing.i] === v" class="grain card settle my-2 flex flex-col gap-4" @submit.prevent="saveVisit">
            <VisitFields v-model="editing.visit" />
            <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
            <div class="grid grid-cols-2 gap-4">
              <button type="button" class="btn grain text-sage" @click="editing = null">Tühista</button>
              <button type="button" class="btn grain text-danger" @click="deleteVisit"><AppIcon name="trash" :size="20" /> Kustuta käik</button>
            </div>
          </form>
          <!-- A ledger row, like a page of a field notebook: date in the margin, then what was found.
               The user's own visits can be tapped to edit; others' carry the name of who went. -->
          <component
            :is="own ? 'button' : 'article'"
            v-else
            :type="own ? 'button' : undefined"
            :aria-label="own ? `Muuda käiku ${formatDate(v.date)}` : undefined"
            class="grid grid-cols-[4.5rem_1fr] gap-x-3 border-b border-sage/15 py-3 text-left"
            :class="[own && 'press', v === justSaved && 'just-saved']"
            @click="own && editVisit(v)"
          >
            <span class="font-hand text-lg leading-7 whitespace-nowrap text-muted">{{ shortDate(v.date) }}</span>
            <span class="flex flex-col gap-0.5">
              <span class="flex items-center justify-between gap-2">{{ picked(v) || '–' }} <StarRating v-if="v.rating" :model-value="v.rating" readonly :size="14" /></span>
              <span v-if="v.note" class="italic text-muted">„{{ v.note }}”</span>
              <span v-if="name" class="font-hand text-lg text-muted">{{ name }}</span>
            </span>
          </component>
        </template>
      </template>
      <p v-if="!rows.length" class="italic text-muted">Siin pole veel käidud.</p>
    </section>
    </div>

    <RouterLink v-if="!loc.shared" :to="`/asukohad/${loc.id}/muuda`" class="btn grain text-sage lg:self-start">
      <AppIcon name="edit" :size="20" /> Muuda kohta
    </RouterLink>
  </template>

  <PageHeader v-else title="Seda kohta ei leitud" back="/asukohad" />
</template>
