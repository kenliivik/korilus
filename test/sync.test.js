// Spot sync: which copy wins when the phone and the server disagree (src/data/account.js merge).
// Run with `npm test`. The browser parts the modules touch at load are stubbed.
import test from 'node:test'
import assert from 'node:assert/strict'

await import('vue') // before the document stub, which Vue would take for a real page
globalThis.localStorage = { getItem: () => null, setItem() {} }
globalThis.document = { addEventListener() {}, visibilityState: 'hidden' }
globalThis.addEventListener = () => {}
globalThis.fetch = () => Promise.reject(new Error('offline'))

const { store } = await import('../src/data/store.js')
const { merge } = await import('../src/data/account.js')

const spot = (id, updatedAt, name = id) => ({ id, updatedAt, name, lat: 58, lng: 25, visits: [] })
const mine = (s, extra = {}) => ({ ...s, mine: true, owner: 'Ken', deleted: false, ...extra })
const reset = (locations, deleted = []) => Object.assign(store, { locations, deleted, shared: [] })
const mergeSpots = (spots) => (store.shared = merge(spots))

test('the phone keeps its copy when it is newer', () => {
  reset([spot('a', 200, 'phone')])
  mergeSpots([mine(spot('a', 100, 'server'))])
  assert.equal(store.locations[0].name, 'phone')
})

test('a newer server copy replaces the phone copy, and new spots arrive', () => {
  reset([spot('a', 100, 'phone')])
  mergeSpots([mine(spot('a', 200, 'server')), mine(spot('b', 50))])
  assert.deepEqual(store.locations.map((l) => [l.id, l.name]).sort(), [['a', 'server'], ['b', 'b']])
})

test('a spot deleted on another phone disappears', () => {
  reset([spot('a', 100)])
  mergeSpots([{ id: 'a', updatedAt: 200, mine: true, deleted: true }])
  assert.equal(store.locations.length, 0)
})

test('a spot deleted on this phone does not come back from an older server copy', () => {
  reset([], [{ id: 'a', updatedAt: 300, deleted: true }])
  mergeSpots([mine(spot('a', 200))])
  assert.equal(store.locations.length, 0)
})

test('spots shared by others go to the shared list, read-only, and deleted ones are left out', () => {
  reset([spot('a', 100)])
  mergeSpots([{ ...spot('x', 100), mine: false, owner: 'Mari', deleted: false }, { id: 'y', updatedAt: 1, mine: false, deleted: true }])
  assert.deepEqual(store.shared.map((s) => [s.id, s.owner, s.shared]), [['x', 'Mari', true]])
  assert.equal(store.locations.length, 1)
})
