import { reactive, watch } from 'vue'

// Everything is kept on this phone until there is a backend (see audits/wireframe-audit-2026-10-10.md).
const KEY = 'korilus'

export const store = reactive(
  JSON.parse(localStorage.getItem(KEY)) ?? {
    locations: [], // { id, name, lat, lng, visits: [{ date, berries, rating, note }] }, newest visit first
    reminders: [], // produce ids
    notify: { start: true, peak: true },
  },
)

watch(store, () => localStorage.setItem(KEY, JSON.stringify(store)), { deep: true })

export const findLocation = (id) => store.locations.find((l) => l.id === id)
export const berriesAt = (loc) => [...new Set(loc.visits.flatMap((v) => v.berries))]
export const averageRating = (loc) =>
  Math.round(loc.visits.reduce((sum, v) => sum + v.rating, 0) / (loc.visits.length || 1))
export const today = () => new Date().toISOString().slice(0, 10)

export function toggleReminder(id) {
  const i = store.reminders.indexOf(id)
  i === -1 ? store.reminders.push(id) : store.reminders.splice(i, 1)
}
