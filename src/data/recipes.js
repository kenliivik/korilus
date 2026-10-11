import { loadContent, recipes, saveContent } from './berries.js'
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

// --- Admin: saves through the CMS loader, so the app and its cached copy update right away. ---

export { uploadImage } from './admin.js'

export class AuthError extends Error {}

// All recipes, hidden ones included.
export const loadAllRecipes = () => loadContent().then((c) => [...c.recipes])

const post = (body) =>
  saveContent({ collection: 'recipes', ...body }).then(
    (c) => c.recipes,
    (e) => { throw e.status === 401 ? new AuthError('Logi sisse') : e },
  )

export const saveRecipe = (item) => post({ action: 'save', item })
export const deleteRecipe = (id) => post({ action: 'delete', id })
