<script setup>
import { ask } from '../../data/confirm.js'
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import RecipeFields from '../../components/RecipeFields.vue'
import { loadContent, saveContent } from '../../data/berries.js'
import { blankRecipe, cleanRecipe, slugify } from '../../data/recipes.js'
import { uploadImage } from '../../data/admin.js'

// Add (id "uus") or edit one recipe. Fields follow the recipe item in the build contract.
const props = defineProps({ id: { type: String, required: true } })
const router = useRouter()
const isNew = props.id === 'uus'

const blank = () => ({ ...blankRecipe(), image: '', hidden: false })
const draft = ref(null)
const missing = ref(false)
const busy = ref(false)
const error = ref('') // text, or 'auth' when the admin session is missing
let all = []

loadContent().then(({ recipes: list }) => {
  all = list
  const r = list.find((r) => r.id === props.id)
  if (!isNew && !r) return (missing.value = true)
  const d = { ...blank(), ...JSON.parse(JSON.stringify(r ?? {})) }
  if (!d.ingredients.length) d.ingredients.push({ amount: '', item: '' })
  if (!d.steps.length) d.steps.push('')
  draft.value = d
})


function fail(e) {
  error.value = e.status === 401 ? 'auth' : e.message
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
  const item = { id: isNew ? newId(d.title) : props.id, ...cleanRecipe(d) }
  if (d.image) item.image = d.image
  if (d.hidden) item.hidden = true
  busy.value = true
  error.value = ''
  try {
    await saveContent({ collection: 'recipes', action: 'save', item })
    router.replace('/admin/retseptid')
  } catch (e) {
    fail(e)
  }
}

async function remove() {
  if (!(await ask(`Kas kustutad „${draft.value.title}”?`))) return
  busy.value = true
  try {
    await saveContent({ collection: 'recipes', action: 'delete', id: props.id })
    router.replace('/admin/retseptid')
  } catch (e) {
    fail(e)
  }
}
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

    <RecipeFields :draft="draft" />

    <label class="grain card flex items-center justify-between text-lg">
      Peida rakendusest
      <input v-model="draft.hidden" type="checkbox" class="size-5 accent-sage" />
    </label>

    <p v-if="error === 'auth'" class="grain card text-lg">
      Oled välja logitud. <RouterLink to="/admin" class="underline underline-offset-4">Logi sisse</RouterLink> ja proovi uuesti.
    </p>
    <p v-else-if="error" class="grain card text-lg text-danger">{{ error }}</p>

    <button type="submit" class="btn bg-sage text-milk" :disabled="busy">{{ busy ? 'Salvestan…' : 'Salvesta' }}</button>
    <button v-if="!isNew" type="button" class="btn grain text-danger" :disabled="busy" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta
    </button>
  </form>
</template>
