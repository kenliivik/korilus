import { computed, reactive } from 'vue'
import { recipes } from './berries.js'
import { store, markShared } from './store.js'

// Recipe helpers. Ken's recipes (`recipes`) are loaded in berries.js; people's own ones live in store.myRecipes,
// and the ones their groups share in store.sharedRecipes.
export const recipeTypes = {
  moos: 'Moos',
  mahl: 'Mahl',
  kuivatamine: 'Kuivatamine',
  külmutamine: 'Külmutamine',
  hoidis: 'Hoidis',
  muu: 'Muu',
}

// The Retseptid page's filters, kept here so they survive opening a recipe and coming back.
export const recipeFilters = reactive({ berry: '', type: '', saved: false, own: false })

export const allRecipes = computed(() => [...recipes, ...store.myRecipes, ...store.sharedRecipes])
export const findRecipe = (id) => allRecipes.value.find((r) => r.id === id)
export const recipesFor = (berryId) => allRecipes.value.filter((r) => r.berries?.includes(berryId))
export const isMine = (r) => store.myRecipes.includes(r)

export const blankRecipe = () => ({
  title: '', berries: [], type: 'moos', time: '', yield: '', ingredients: [{ amount: '', item: '' }], steps: [''], tip: '',
})

// A recipe form's draft as it is saved: trimmed, empty rows and empty optional fields left out.
export function cleanRecipe(d) {
  const r = {
    title: d.title.trim(),
    berries: d.berries,
    type: d.type,
    ingredients: d.ingredients.map((i) => ({ amount: i.amount.trim(), item: i.item.trim() })).filter((i) => i.item),
    steps: d.steps.map((s) => s.trim()).filter(Boolean),
  }
  for (const k of ['time', 'yield', 'tip']) if (d[k]?.trim()) r[k] = d[k].trim()
  return r
}

// Saves an own recipe (new when it has no id) and returns its id.
export function saveMyRecipe(recipe) {
  const old = store.myRecipes.find((r) => r.id === recipe.id)
  const r = { ...recipe, id: recipe.id || `oma-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}` }
  r.updatedAt = Math.max(Date.now(), (old?.updatedAt ?? 0) + 1)
  markShared(Object.assign(r, old?.sharedTo && { sharedTo: old.sharedTo, sharedAt: old.sharedAt }))
  old ? store.myRecipes.splice(store.myRecipes.indexOf(old), 1, r) : store.myRecipes.unshift(r)
  if (!store.dirtyRecipes.includes(r.id)) store.dirtyRecipes.push(r.id)
  return r.id
}

export function removeMyRecipe(id) {
  const i = store.myRecipes.findIndex((r) => r.id === id)
  if (i === -1) return
  store.deletedRecipes.push({ id, updatedAt: Math.max(Date.now(), store.myRecipes[i].updatedAt + 1), deleted: true })
  store.myRecipes.splice(i, 1)
  delete store.notes[id]
}

export function toggleSavedRecipe(id) {
  const i = store.savedRecipes.indexOf(id)
  i === -1 ? store.savedRecipes.push(id) : store.savedRecipes.splice(i, 1)
}

// Personal notes on a recipe (see store.notes). Keys: 'i:' + ingredient, 's:' + step text, so a note stays with its line
// when Ken reorders the recipe. If he rewrites a line, the note is shown with the general note instead (orphanNotes).
export const ingKey = (ing) => `i:${ing.item}`
export const stepKey = (step) => `s:${step}`

function editNote(id, change) {
  const note = (store.notes[id] ??= { lines: {}, general: '', groupId: null, updatedAt: 0 })
  change(note)
  note.updatedAt = Math.max(Date.now(), note.updatedAt + 1)
  if (!store.dirtyNotes.includes(id)) store.dirtyNotes.push(id)
}

export const setNoteLine = (id, key, text) =>
  editNote(id, (n) => {
    if (text.trim()) n.lines[key] = text
    else delete n.lines[key]
  })
export const setNoteGeneral = (id, text) => editNote(id, (n) => (n.general = text))
export const setNoteGroup = (id, groupId) => editNote(id, (n) => (n.groupId = groupId))

// Every note on a line: the user's own first, then the ones shared by their groups.
export function notesOn(recipe, key) {
  const own = store.notes[recipe.id]?.lines[key]
  const shared = store.sharedNotes.filter((n) => n.recipe === recipe.id && n.lines[key]).map((n) => ({ text: n.lines[key], owner: n.owner }))
  return own ? [{ text: own, mine: true }, ...shared] : shared
}

// Line notes whose line is no longer in the recipe, as "suhkrut: 500 g".
export function orphanNotes(recipe, lines) {
  const keys = new Set([...(recipe.ingredients ?? []).map(ingKey), ...(recipe.steps ?? []).map(stepKey)])
  return Object.entries(lines ?? {}).filter(([k]) => !keys.has(k)).map(([k, v]) => `${k.slice(2)}: ${v}`)
}

// "Mustika-vaarikamoos" -> "mustika-vaarikamoos", for new recipe and berry addresses.
export const slugify = (s, fallback = 'retsept') =>
  s
    .toLowerCase()
    .replace(/[äå]/g, 'a').replace(/[õö]/g, 'o').replace(/ü/g, 'u').replace(/š/g, 's').replace(/ž/g, 'z')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || fallback

