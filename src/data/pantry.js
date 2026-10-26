import { computed } from 'vue'
import { store } from './store.js'

// Sahver: jars made, each linked to a recipe and to the batch (which berries, from which spot), with a note for next time.
// { id: 'purk-…', title, recipeId?, jars, left, made: 'YYYY-MM-DD', berries: [], spotId?, note, updatedAt }.
// Private, kept on this phone and synced with the account like own recipes (api/pantry.php).

export const byNewest = computed(() => [...store.pantry].sort((a, b) => b.made.localeCompare(a.made) || b.updatedAt - a.updatedAt))
export const findJar = (id) => store.pantry.find((j) => j.id === id)
// Earlier batches of a recipe that have a note, newest first: "Eelmisel korral: liiga magus".
export const notesFor = (recipeId) => byNewest.value.filter((j) => j.recipeId === recipeId && j.note.trim())

function touchJar(j) {
  j.updatedAt = Math.max(Date.now(), (j.updatedAt ?? 0) + 1)
  if (!store.dirtyPantry.includes(j.id)) store.dirtyPantry.push(j.id)
}

// Saves an entry (new when it has no id) and returns its id.
export function saveJar(entry) {
  const old = findJar(entry.id)
  const j = { ...entry, id: entry.id || `purk-${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}` }
  j.left = Math.min(j.left ?? j.jars, j.jars)
  old ? store.pantry.splice(store.pantry.indexOf(old), 1, j) : store.pantry.unshift(j)
  touchJar(j)
  return j.id
}

// One jar opened (−1) or put back (+1).
export function useJar(j, step) {
  j.left = Math.min(j.jars, Math.max(0, j.left + step))
  touchJar(j)
}

export function removeJar(id) {
  const i = store.pantry.findIndex((j) => j.id === id)
  if (i === -1) return
  store.deletedPantry.push({ id, updatedAt: Math.max(Date.now(), store.pantry[i].updatedAt + 1), deleted: true })
  store.pantry.splice(i, 1)
}
