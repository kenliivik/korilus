// Recipe notes: syncing (src/data/account.js mergeNotes) and notes whose recipe line was rewritten (src/data/recipes.js).
// Run with `npm test`. The browser parts the modules touch at load are stubbed.
import test from 'node:test'
import assert from 'node:assert/strict'

await import('vue') // before the document stub, which Vue would take for a real page
globalThis.localStorage = { getItem: () => null, setItem() {} }
globalThis.document = { addEventListener() {}, visibilityState: 'hidden' }
globalThis.addEventListener = () => {}
globalThis.fetch = () => Promise.reject(new Error('offline'))

const { store } = await import('../src/data/store.js')
const { mergeNotes } = await import('../src/data/account.js')
const { setNoteLine, notesOn, orphanNotes } = await import('../src/data/recipes.js')

const reset = (notes = {}) => Object.assign(store, { notes, dirtyNotes: [], sharedNotes: [] })
const recipe = { id: 'moos', ingredients: [{ amount: '1 kg', item: 'suhkrut' }], steps: ['Keeda 20 minutit.'] }

test('a newer server note replaces the phone one, an older one does not', () => {
  reset({ moos: { lines: {}, general: 'phone', updatedAt: 200 }, mahl: { lines: {}, general: 'phone', updatedAt: 200 } })
  mergeNotes([
    { recipe: 'moos', mine: true, general: 'server', lines: {}, updatedAt: 300 },
    { recipe: 'mahl', mine: true, general: 'server', lines: {}, updatedAt: 100 },
  ])
  assert.equal(store.notes.moos.general, 'server')
  assert.equal(store.notes.mahl.general, 'phone')
})

test('group notes show on the line after the own one, empty ones are left out', () => {
  reset()
  setNoteLine('moos', 'i:suhkrut', '500 g')
  mergeNotes([
    { recipe: 'moos', mine: false, owner: 'Mari', general: '', lines: { 'i:suhkrut': '700 g' }, updatedAt: 1 },
    { recipe: 'moos', mine: false, owner: 'Jaan', general: '', lines: {}, updatedAt: 1 },
  ])
  assert.deepEqual(notesOn(recipe, 'i:suhkrut'), [{ text: '500 g', mine: true }, { text: '700 g', owner: 'Mari' }])
  assert.equal(store.sharedNotes.length, 1)
  assert.deepEqual(store.dirtyNotes, ['moos'])
})

test('an emptied line note goes away, and a note on a rewritten line moves to the general note', () => {
  reset()
  setNoteLine('moos', 'i:suhkrut', '500 g')
  setNoteLine('moos', 'i:suhkrut', ' ')
  assert.deepEqual(store.notes.moos.lines, {})
  assert.deepEqual(orphanNotes(recipe, { 's:Keeda 15 minutit.': 'kauem', 'i:suhkrut': 'vähem' }), ['Keeda 15 minutit.: kauem'])
})

const { merge } = await import('../src/data/account.js')
const { saveMyRecipe, removeMyRecipe, findRecipe } = await import('../src/data/recipes.js')

test('an own recipe is saved, synced, found and deleted with a tombstone', () => {
  Object.assign(store, { myRecipes: [], dirtyRecipes: [], deletedRecipes: [], sharedRecipes: [] })
  const id = saveMyRecipe({ title: 'Pohlamoos', berries: [], type: 'moos', ingredients: [], steps: [] })
  assert.match(id, /^oma-[a-z0-9]+$/)
  assert.deepEqual(store.dirtyRecipes, [id])
  store.sharedRecipes = merge([{ id: 'x', updatedAt: 1, mine: false, owner: 'Mari', deleted: false, title: 'Mari moos' }], store.myRecipes, store.deletedRecipes)
  assert.equal(findRecipe('x').owner, 'Mari')
  removeMyRecipe(id)
  assert.equal(findRecipe(id), undefined)
  assert.equal(store.deletedRecipes[0].id, id)
  // An older server copy does not bring it back.
  merge([{ id, updatedAt: 1, mine: true, deleted: false, title: 'Pohlamoos' }], store.myRecipes, store.deletedRecipes)
  assert.equal(store.myRecipes.length, 0)
})
