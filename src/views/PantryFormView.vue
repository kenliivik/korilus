<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import { produce } from '../data/berries.js'
import { findRecipe, allRecipes } from '../data/recipes.js'
import { findJar, saveJar, removeJar } from '../data/pantry.js'
import { store, today } from '../data/store.js'
import { ask } from '../data/confirm.js'

// Add (id "uus", optionally ?retsept=<id> from a recipe's "Tegin selle") or edit a pantry entry.
const props = defineProps({ id: { type: String, required: true } })
const router = useRouter()
const existing = findJar(props.id)
const isNew = props.id === 'uus'
const from = findRecipe(useRoute().query.retsept ?? '')

const draft = ref(
  existing
    ? JSON.parse(JSON.stringify(existing))
    : { title: from?.title ?? '', recipeId: from?.id ?? null, jars: 1, made: today(), berries: [...(from?.berries ?? [])], spotId: null, note: '' },
)
// Picking a recipe fills in its name and berries when they're still empty.
function pickRecipe(id) {
  const r = findRecipe(id)
  draft.value.recipeId = r?.id ?? null
  if (r && !draft.value.title.trim()) draft.value.title = r.title
  if (r && !draft.value.berries.length) draft.value.berries = [...(r.berries ?? [])]
}
// With a recipe picked, only its berries (and any already chosen) are offered; "muuda" shows them all.
const allChips = ref(false)
const chips = computed(() => {
  const r = findRecipe(draft.value.recipeId ?? '')
  if (allChips.value || !r?.berries?.length) return produce
  return produce.filter((p) => r.berries.includes(p.id) || draft.value.berries.includes(p.id))
})
const toggle = (id) =>
  (draft.value.berries = draft.value.berries.includes(id) ? draft.value.berries.filter((b) => b !== id) : [...draft.value.berries, id])
// The batch: own spots where one of these berries was found, the ones found most recently first.
const spots = computed(() => store.locations.filter((l) => !draft.value.berries.length || l.visits.some((v) => v.berries.some((b) => draft.value.berries.includes(b)))))

function save() {
  const jars = Math.max(0, Math.min(999, Math.round(+draft.value.jars || 0)))
  const left = existing ? existing.left + jars - existing.jars : jars
  saveJar({ ...draft.value, title: draft.value.title.trim(), note: draft.value.note.trim(), jars, left: Math.max(0, left), id: existing?.id })
  router.replace('/sahver')
}

async function remove() {
  if (!(await ask(`Kas kustutad „${existing.title}”?`))) return
  removeJar(existing.id)
  router.replace('/sahver')
}
</script>

<template>
  <PageHeader :title="isNew ? 'Lisa purgid' : existing?.title || 'Muuda'" back="/sahver" />

  <p v-if="!isNew && !existing" class="italic text-muted">Seda kirjet ei leitud.</p>

  <form v-else class="flex flex-col gap-5 lg:max-w-2xl" @submit.prevent="save">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Retsept</span>
      <select :value="draft.recipeId" class="field" @change="pickRecipe($event.target.value)">
        <option :value="null">Ilma retseptita</option>
        <option v-for="r in allRecipes" :key="r.id" :value="r.id">{{ r.title }}</option>
      </select>
    </label>

    <label class="flex flex-col gap-2">
      <span class="text-xl">Mis see on?</span>
      <input v-model="draft.title" required maxlength="200" class="field" placeholder="nt. Pohlamoos" />
    </label>

    <div class="grid grid-cols-2 gap-4">
      <label class="flex flex-col gap-2">
        <span class="text-xl">Mitu purki?</span>
        <input v-model.number="draft.jars" type="number" inputmode="numeric" min="0" max="999" required class="field" />
      </label>
      <label class="flex flex-col gap-2">
        <span class="text-xl">Millal?</span>
        <input v-model="draft.made" type="date" required :max="today()" class="field" />
      </label>
    </div>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Millest?</legend>
      <div class="flex flex-wrap items-center gap-2">
        <button
          v-for="p in chips"
          :key="p.id"
          type="button"
          :aria-pressed="draft.berries.includes(p.id)"
          class="press rounded-full px-3 py-1.5 text-base"
          :class="draft.berries.includes(p.id) ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm'"
          @click="toggle(p.id)"
        >
          {{ p.name }}
        </button>
        <button v-if="chips.length < produce.length" type="button" class="px-2 py-1.5 font-hand text-lg text-muted underline underline-offset-4" @click="allChips = true">muuda</button>
      </div>
    </fieldset>

    <label v-if="store.locations.length" class="flex flex-col gap-2">
      <span class="text-xl">Kust korjatud?</span>
      <select v-model="draft.spotId" class="field">
        <option :value="null">Ei märgi</option>
        <option v-for="l in spots" :key="l.id" :value="l.id">{{ l.name }}</option>
      </select>
    </label>

    <label class="flex flex-col gap-2">
      <span class="text-xl">Märge järgmiseks korraks</span>
      <textarea v-model="draft.note" rows="3" maxlength="4000" class="field" placeholder='nt. "Liiga magus, järgmine kord vähem suhkrut"' />
    </label>

    <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="router.back()">Tühista</button>
    <button v-if="existing" type="button" class="mt-2 flex items-center justify-center gap-2 text-lg text-danger" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta
    </button>
  </form>
</template>
