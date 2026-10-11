<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import { produce } from '../../data/berries.js'
import { AuthError, deleteRecipe, loadAllRecipes, recipeTypes, saveRecipe, slugify, uploadImage } from '../../data/recipes.js'

// Add (id "uus") or edit one recipe. Fields follow the recipe item in the build contract.
const props = defineProps({ id: { type: String, required: true } })
const router = useRouter()
const isNew = props.id === 'uus'

const blank = () => ({ title: '', image: '', berries: [], type: 'moos', time: '', yield: '', ingredients: [{ amount: '', item: '' }], steps: [''], tip: '', hidden: false })
const draft = ref(null)
const missing = ref(false)
const busy = ref(false)
const error = ref('') // text, or 'auth' when the admin session is missing
let all = []

loadAllRecipes().then((list) => {
  all = list
  const r = list.find((r) => r.id === props.id)
  if (!isNew && !r) return (missing.value = true)
  const d = { ...blank(), ...JSON.parse(JSON.stringify(r ?? {})) }
  if (!d.ingredients.length) d.ingredients.push({ amount: '', item: '' })
  if (!d.steps.length) d.steps.push('')
  draft.value = d
})

const toggleBerry = (id) => {
  const b = draft.value.berries
  b.includes(id) ? b.splice(b.indexOf(id), 1) : b.push(id)
}
const move = (i, by) => {
  const s = draft.value.steps
  s.splice(i + by, 0, ...s.splice(i, 1))
}

function fail(e) {
  error.value = e instanceof AuthError ? 'auth' : e.message
  busy.value = false
}

async function pickImage(e) {
  const file = e.target.files[0]
  if (!file) return
  busy.value = true
  error.value = ''
  try {
    draft.value.image = await uploadImage(file)
    busy.value = false
  } catch (err) {
    fail(err)
  }
}

// New ids come from the title and must not clash with another recipe.
function newId(title) {
  const base = slugify(title)
  let id = base
  for (let n = 2; all.some((r) => r.id === id); n++) id = `${base}-${n}`
  return id
}

async function save() {
  const d = draft.value
  const item = {
    id: isNew ? newId(d.title) : props.id,
    title: d.title.trim(),
    berries: d.berries,
    type: d.type,
    ingredients: d.ingredients.map((i) => ({ amount: i.amount.trim(), item: i.item.trim() })).filter((i) => i.item),
    steps: d.steps.map((s) => s.trim()).filter(Boolean),
  }
  for (const k of ['image', 'time', 'yield', 'tip']) if (d[k]?.trim()) item[k] = d[k].trim()
  if (d.hidden) item.hidden = true
  busy.value = true
  error.value = ''
  try {
    await saveRecipe(item)
    router.replace('/admin/retseptid')
  } catch (e) {
    fail(e)
  }
}

async function remove() {
  if (!confirm(`Kas kustutada „${draft.value.title}”?`)) return
  busy.value = true
  try {
    await deleteRecipe(props.id)
    router.replace('/admin/retseptid')
  } catch (e) {
    fail(e)
  }
}

const chip = (on) => (on ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm')
const small = 'grain grid size-9 shrink-0 place-items-center rounded-full shadow-neu-raised-sm active:shadow-neu-inset disabled:opacity-30'
</script>

<template>
  <PageHeader :title="isNew ? 'Uus retsept' : 'Muuda retsepti'" back="/admin/retseptid" />

  <p v-if="missing" class="italic text-muted">Sellist retsepti ei leitud.</p>
  <p v-else-if="!draft" class="italic text-muted">Laadin…</p>

  <form v-else class="flex flex-col gap-5" @submit.prevent="save">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Pealkiri</span>
      <input v-model="draft.title" required class="field" placeholder="nt. Mustikamoos" />
    </label>

    <div class="flex flex-col gap-2">
      <span class="text-xl">Pilt</span>
      <img v-if="draft.image" :src="draft.image" alt="" class="aspect-[4/3] w-full rounded-card object-cover shadow-neu-raised" />
      <div class="flex gap-3">
        <label class="btn grain flex-1 cursor-pointer text-sage">
          {{ draft.image ? 'Vaheta pilt' : 'Lisa pilt' }}
          <input type="file" accept="image/*" class="sr-only" :disabled="busy" @change="pickImage" />
        </label>
        <button v-if="draft.image" type="button" class="btn grain text-muted" @click="draft.image = ''">Eemalda</button>
      </div>
    </div>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Marjad</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="p in produce"
          :key="p.id"
          type="button"
          :aria-pressed="draft.berries.includes(p.id)"
          class="rounded-full px-3 py-1.5 text-base"
          :class="chip(draft.berries.includes(p.id))"
          @click="toggleBerry(p.id)"
        >
          {{ p.name }}
        </button>
      </div>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Liik</legend>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="(label, t) in recipeTypes"
          :key="t"
          type="button"
          :aria-pressed="draft.type === t"
          class="rounded-full px-3 py-1.5 text-base"
          :class="chip(draft.type === t)"
          @click="draft.type = t"
        >
          {{ label }}
        </button>
      </div>
    </fieldset>

    <div class="grid grid-cols-2 gap-3">
      <label class="flex flex-col gap-2">
        <span class="text-xl">Aeg</span>
        <input v-model="draft.time" class="field" placeholder="nt. 40 min" />
      </label>
      <label class="flex flex-col gap-2">
        <span class="text-xl">Saagis</span>
        <input v-model="draft.yield" class="field" placeholder="nt. 4 purki" />
      </label>
    </div>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Koostisosad</legend>
      <div v-for="(ing, i) in draft.ingredients" :key="i" class="flex items-center gap-2">
        <input v-model="ing.amount" class="field w-24! shrink-0" placeholder="1 kg" aria-label="Kogus" />
        <input v-model="ing.item" class="field min-w-0" placeholder="mustikaid" aria-label="Koostisosa" />
        <button type="button" :class="small" aria-label="Eemalda rida" @click="draft.ingredients.splice(i, 1)">
          <AppIcon name="trash" :size="16" />
        </button>
      </div>
      <button type="button" class="btn grain text-sage" @click="draft.ingredients.push({ amount: '', item: '' })">
        <AppIcon name="plus" :size="20" /> Lisa koostisosa
      </button>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Sammud</legend>
      <div v-for="(_, i) in draft.steps" :key="i" class="flex items-start gap-2">
        <span class="font-hand w-6 pt-3 text-xl text-leaf">{{ i + 1 }}.</span>
        <textarea v-model="draft.steps[i]" rows="2" class="field min-w-0" :aria-label="`Samm ${i + 1}`" />
        <div class="flex flex-col gap-1">
          <button type="button" :class="small" aria-label="Liiguta üles" :disabled="i === 0" @click="move(i, -1)">
            <AppIcon name="up" :size="16" />
          </button>
          <button type="button" :class="small" aria-label="Liiguta alla" :disabled="i === draft.steps.length - 1" @click="move(i, 1)">
            <span class="grid rotate-180"><AppIcon name="up" :size="16" /></span>
          </button>
          <button type="button" :class="small" aria-label="Eemalda samm" @click="draft.steps.splice(i, 1)">
            <AppIcon name="trash" :size="16" />
          </button>
        </div>
      </div>
      <button type="button" class="btn grain text-sage" @click="draft.steps.push('')">
        <AppIcon name="plus" :size="20" /> Lisa samm
      </button>
    </fieldset>

    <label class="flex flex-col gap-2">
      <span class="text-xl">Nõuanne</span>
      <textarea v-model="draft.tip" rows="2" class="field" placeholder="Valikuline" />
    </label>

    <label class="grain card flex items-center justify-between text-lg">
      Peida avalikust nimekirjast
      <input v-model="draft.hidden" type="checkbox" class="size-5 accent-sage" />
    </label>

    <p v-if="error === 'auth'" class="grain card text-lg">
      Sessioon on aegunud. <RouterLink to="/admin" class="underline underline-offset-4">Logi sisse</RouterLink> ja proovi uuesti.
    </p>
    <p v-else-if="error" class="grain card text-lg text-[#9c3a3a]">{{ error }}</p>

    <button type="submit" class="btn bg-sage text-milk" :disabled="busy">{{ busy ? 'Salvestan…' : 'Salvesta' }}</button>
    <button v-if="!isNew" type="button" class="btn grain text-[#9c3a3a]" :disabled="busy" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta
    </button>
  </form>
</template>
