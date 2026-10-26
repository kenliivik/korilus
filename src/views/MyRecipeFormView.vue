<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import RecipeFields from '../components/RecipeFields.vue'
import { blankRecipe, cleanRecipe, removeMyRecipe, saveMyRecipe } from '../data/recipes.js'
import { store } from '../data/store.js'
import { account } from '../data/account.js'
import { ask, guardLeave } from '../data/confirm.js'

// Add (id "uus") or edit one of the user's own recipes. They stay on this phone, or in their account when logged in,
// and only they see them unless they share them with a group.
const props = defineProps({ id: { type: String, required: true } })
const router = useRouter()
const existing = store.myRecipes.find((r) => r.id === props.id)
const isNew = props.id === 'uus'

const d = { ...blankRecipe(), groupId: null, ...JSON.parse(JSON.stringify(existing ?? {})) }
if (!d.ingredients.length) d.ingredients.push({ amount: '', item: '' })
if (!d.steps.length) d.steps.push('')
const draft = ref(d)
const before = JSON.stringify(d)
const leave = guardLeave(() => JSON.stringify(draft.value) !== before)

function save() {
  const id = saveMyRecipe({ ...cleanRecipe(draft.value), id: existing?.id, groupId: draft.value.groupId })
  leave()
  router.replace(`/retsept/${id}`)
}

async function remove() {
  if (!(await ask(`Kas kustutad „${existing.title}”?`))) return
  removeMyRecipe(existing.id)
  leave()
  router.replace('/retseptid')
}
</script>

<template>
  <PageHeader :title="isNew ? 'Uus retsept' : 'Muuda retsepti'" :back="existing ? `/retsept/${existing.id}` : '/retseptid'" />

  <p v-if="!isNew && !existing" class="italic text-muted">Sellist retsepti ei leitud.</p>

  <form v-else class="flex flex-col gap-5" @submit.prevent="save">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Pealkiri</span>
      <input v-model="draft.title" required class="field" placeholder="nt. Vanaema pohlamoos" />
    </label>

    <RecipeFields :draft="draft" />

    <label v-if="account.user && account.groups.length" class="flex flex-col gap-2">
      <span class="text-xl">Jaga</span>
      <select v-model="draft.groupId" class="field">
        <option :value="null">Ainult mina</option>
        <option v-for="g in account.groups" :key="g.id" :value="g.id">Mina ja grupp „{{ g.name }}”</option>
      </select>
    </label>
    <p v-else class="text-lg text-muted">Seda retsepti näed ainult sina.</p>

    <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="router.back()">Tühista</button>
    <button v-if="existing" type="button" class="mt-2 flex items-center justify-center gap-2 text-lg text-danger" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta retsept
    </button>
  </form>
</template>
