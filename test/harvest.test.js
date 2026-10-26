// The harvest log's "usual best day" (src/data/harvest.js). Run with `npm test`.
import test from 'node:test'
import assert from 'node:assert/strict'
import { bestTimes, amountOf, litres } from '../src/data/harvest.js'

const visit = (date, berries, amounts) => ({ date, berries, ...(amounts && { amounts }) })

test('the best day is the biggest haul each year, averaged over the years', () => {
  const t = bestTimes([
    visit('2025-07-10', ['mustikas'], { mustikas: 1 }),
    visit('2025-07-20', ['mustikas'], { mustikas: 4 }),
    visit('2026-07-24', ['mustikas'], { mustikas: 3 }),
    visit('2026-07-30', ['mustikas', 'pohl'], { mustikas: 1 }),
  ], 'mustikas')
  assert.deepEqual(t, { first: '07-17', best: '07-22', years: 2, bestYears: 2 })
})

test('without amounts there is a first find but no best day', () => {
  assert.deepEqual(bestTimes([visit('2026-08-20', ['pohl']), visit('2026-08-02', ['pohl'])], 'pohl'), { first: '08-02', best: null, years: 1, bestYears: 0 })
  assert.equal(bestTimes([visit('2026-08-20', ['pohl'])], 'mustikas'), null)
})

test('odd amounts from other phones count as nothing', () => {
  assert.equal(amountOf(visit('2026-08-20', ['pohl'], { pohl: 'x' }), 'pohl'), 0)
  assert.equal(amountOf(visit('2026-08-20', ['pohl'], { pohl: -2 }), 'pohl'), 0)
  assert.equal(litres(0.1 + 0.2), '0,3 l')
})
