// The group activity line on home (src/data/activity.js). Run with `npm test`.
import test from 'node:test'
import assert from 'node:assert/strict'

await import('vue')
globalThis.localStorage = { getItem: () => null, setItem() {} }

const { activityFrom, ago } = await import('../src/data/activity.js')
const { markShared } = await import('../src/data/store.js')

const now = new Date('2026-07-20T18:00').getTime()
const day = 864e5

test('new shares and visits from the last two weeks, newest first, at most three', () => {
  const spots = [
    { id: 's1', name: 'Rabaserv', owner: 'Mari', sharedAt: now - 3 * day, visits: [{ date: '2026-07-19', berries: ['mustikas'] }, { date: '2026-06-01', berries: [] }] },
    { id: 's2', name: 'Vana koht', owner: 'Jaan', visits: [{ date: '2026-07-18', berries: [] }] }, // shared before sharedAt existed
  ]
  const recipes = [{ id: 'r1', title: 'Pohlamoos', owner: 'Jaan', sharedAt: now - 20 * day }]
  assert.deepEqual(activityFrom(spots, recipes, now).map((a) => `${a.who} ${a.text}`), ['Mari käis kohas „Rabaserv” · mustikas', 'Mari jagas kohta „Rabaserv”'])
})

test('sharing again with the same group is not news, another group is', () => {
  const spot = markShared({ groupId: 1 })
  const first = spot.sharedAt
  assert.equal(markShared(spot).sharedAt, first)
  markShared(Object.assign(spot, { groupId: null }))
  assert.equal(spot.sharedTo, undefined)
  assert.ok(markShared(Object.assign(spot, { groupId: 2 })).sharedTo === 2)
})

test('days ago in words', () => {
  assert.equal(ago(now - 2 * 3600e3, now), 'täna')
  assert.equal(ago(now - day, now), 'eile')
  assert.equal(ago(now - 3 * day, now), '3 päeva tagasi')
})
