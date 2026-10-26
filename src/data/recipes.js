import { recipes } from './berries.js'
import { store } from './store.js'

// Recipe helpers. The recipe list itself (`recipes`, `recipesFor`) is loaded in berries.js.
export const recipeTypes = {
  moos: 'Moos',
  mahl: 'Mahl',
  kuivatamine: 'Kuivatamine',
  külmutamine: 'Külmutamine',
  hoidis: 'Hoidis',
  muu: 'Muu',
}

export const findRecipe = (id) => recipes.find((r) => r.id === id)

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

