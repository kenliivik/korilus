// The harvest log: how much of each berry was picked on a visit (visit.amounts = { mustikas: 3 }, in litres),
// and what the logged visits say about when a berry is at its best.

// Litres picked, or 0 when nothing was logged (older visits, and shared spots from older phones).
export const amountOf = (v, id) => Math.max(0, Number(v.amounts?.[id]) || 0)
export const litres = (n) => `${String(Math.round(n * 10) / 10).replace('.', ',')} l`

// "07-14" <-> day of a non-leap year, so dates from different years can be averaged.
const dayOf = (date) => Math.round((Date.UTC(2001, +date.slice(5, 7) - 1, +date.slice(8, 10)) - Date.UTC(2001, 0, 1)) / 864e5)
const dateOf = (day) => new Date(Date.UTC(2001, 0, 1) + Math.round(day) * 864e5).toISOString().slice(5, 10)
const average = (days) => dateOf(days.reduce((s, d) => s + d, 0) / days.length)

// "~14. juuli": a usual day, not a promise.
export const usualDay = (mmdd) => `~${new Date(`2001-${mmdd}T12:00`).toLocaleDateString('et-EE', { day: 'numeric', month: 'long' })}`

// For one berry over some visits: the usual best day (the biggest haul each year, averaged over the years)
// and the usual first find (the earliest visit it was found each year). best is null until an amount is logged.
export function bestTimes(visits, id) {
  const years = new Map()
  for (const v of visits) {
    if (!v.berries.includes(id)) continue
    const y = years.get(v.date.slice(0, 4)) ?? { first: v.date, best: null, most: 0 }
    if (v.date < y.first) y.first = v.date
    if (amountOf(v, id) > y.most) Object.assign(y, { best: v.date, most: amountOf(v, id) })
    years.set(v.date.slice(0, 4), y)
  }
  if (!years.size) return null
  const best = [...years.values()].filter((y) => y.best).map((y) => dayOf(y.best))
  return { first: average([...years.values()].map((y) => dayOf(y.first))), best: best.length ? average(best) : null, years: years.size, bestYears: best.length }
}

// Personal ripening dates for one berry, from all the visits the user logged (store.js ownVisits), or null.
export const personalDates = (visits, id) => bestTimes(visits, id)
