import { recipes } from './berries.js'
import { store } from './store.js'

// Recipe helpers. The recipe list itself (`recipes`, `recipesFor`) is loaded in berries.js.
export const recipeTypes = {
  moos: 'Moos',
  mahl: 'Mahl',
  kuivatamine: 'Kuivatamine',
  külmutamine: 'Külmutamine',
  muu: 'Muu',
}

export const findRecipe = (id) => recipes.find((r) => r.id === id)

export function toggleSavedRecipe(id) {
  const i = store.savedRecipes.indexOf(id)
  i === -1 ? store.savedRecipes.push(id) : store.savedRecipes.splice(i, 1)
}

// "Mustika-vaarikamoos" -> "mustika-vaarikamoos"
export const slugify = (s) =>
  s
    .toLowerCase()
    .replace(/[äå]/g, 'a').replace(/[õö]/g, 'o').replace(/ü/g, 'u').replace(/š/g, 's').replace(/ž/g, 'z')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || 'retsept'

// --- Admin: talks to the content API (see the build contract). ---

export class AuthError extends Error {}

async function call(url, init) {
  const res = await fetch(url, { credentials: 'same-origin', ...init })
  if (res.status === 401) throw new AuthError('Logi sisse')
  if (!res.ok) throw new Error((await res.json().catch(() => null))?.error ?? `Viga ${res.status}`)
  return res.json()
}

// After a save the public list should show the change right away; hidden recipes stay out of it.
const refresh = (content) => {
  if (content?.recipes) recipes.splice(0, recipes.length, ...content.recipes.filter((r) => !r.hidden))
  return content?.recipes ?? []
}

// All recipes, hidden ones included. Without the API (e.g. `vite dev`), falls back to the loaded list.
export const loadAllRecipes = () =>
  call('/api/content.php', { cache: 'no-store' })
    .then((c) => c.recipes ?? [])
    .catch(() => [...recipes])

const post = (body) =>
  call('/api/content.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ collection: 'recipes', ...body }),
  }).then(refresh)

export const saveRecipe = (item) => post({ action: 'save', item })
export const deleteRecipe = (id) => post({ action: 'delete', id })

export function uploadImage(file) {
  const body = new FormData()
  body.append('file', file)
  return call('/api/upload.php', { method: 'POST', body }).then((r) => r.url)
}
