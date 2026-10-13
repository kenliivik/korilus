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

// "Mustika-vaarikamoos" -> "mustika-vaarikamoos", for new recipe and berry addresses.
export const slugify = (s, fallback = 'retsept') =>
  s
    .toLowerCase()
    .replace(/[äå]/g, 'a').replace(/[õö]/g, 'o').replace(/ü/g, 'u').replace(/š/g, 's').replace(/ž/g, 'z')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '') || fallback

