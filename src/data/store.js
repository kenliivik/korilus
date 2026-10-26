import { reactive, watch } from 'vue'
import { findProduce } from './berries.js'

// Everything is kept on this phone. The reminders also go to the server, for push (see syncPush),
// and when logged in the spots sync with the account (see account.js).
const KEY = 'korilus'

export const store = reactive(
  JSON.parse(localStorage.getItem(KEY)) ?? {
    locations: [], // { id, name, lat, lng, visits: [{ date, berries, rating, note }], updatedAt, groupId? }, newest visit first
    reminders: [], // produce ids
    notify: { start: true, peak: true },
  },
)
store.savedRecipes ??= [] // recipe ids ("Pane meelde"); added later, so older phones don't have it yet

// Added with login (Sprint 3), so older saves get them here: updatedAt (ms) decides which copy wins when syncing,
// dirty lists spots changed since the last sync, deleted keeps { id, updatedAt, deleted } until the server has it,
// shared holds spots that others share with my groups (read-only, from the server).
store.dirty ??= store.locations.map((l) => l.id)
store.deleted ??= []
store.shared ??= []
for (const l of store.locations) l.updatedAt ??= Date.now()
// Own notes on Ken's recipes, by recipe id: { lines: { 'i:suhkrut': '500 g', 's:<step text>': '...' }, general, groupId, updatedAt }.
// They sync like spots: dirtyNotes lists recipe ids changed since the last sync, sharedNotes holds what the user's groups share.
// Recipes the user wrote (their own recipe book), synced like spots: { id: 'oma-…', title, type, berries, ingredients, steps,
// time?, yield?, tip?, groupId, updatedAt }. sharedRecipes holds the ones others share with the user's groups (with owner).
store.myRecipes ??= []
store.dirtyRecipes ??= []
store.deletedRecipes ??= []
store.sharedRecipes ??= []
store.notes ??= {}
store.dirtyNotes ??= []
store.sharedNotes ??= [] // { recipe, owner, lines, general }
// Visits on spots others share with the user's groups ("Käisin siin" on a shared spot), by spot id: { visits, updatedAt }.
// They sync like notes (dirtyGuest). otherVisits holds the visits other people logged on spots the user sees: { spotId: [{ name, visits }] }.
store.guestVisits ??= {}
store.dirtyGuest ??= []
store.otherVisits ??= {}

watch(store, () => localStorage.setItem(KEY, JSON.stringify(store)), { deep: true })

export const findLocation = (id) => store.locations.find((l) => l.id === id) ?? store.shared.find((l) => l.id === id)
export const berriesAt = (loc) => [...new Set(loc.visits.flatMap((v) => v.berries))]
// Visits saved without stars (rating 0) don't count.
export const averageRating = (loc) => {
  const rated = loc.visits.filter((v) => v.rating)
  return Math.round(rated.reduce((sum, v) => sum + v.rating, 0) / (rated.length || 1))
}
// "2026-08-02" -> "2. august 2026" (or "2. august" under a year heading).
export const formatDate = (d, year = true) =>
  new Date(`${d}T12:00`).toLocaleDateString('et-EE', { day: 'numeric', month: 'long', ...(year && { year: 'numeric' }) })
export const today = () => new Date().toLocaleDateString('sv-SE') // YYYY-MM-DD in local time (toISOString would give yesterday after midnight)

// Call after changing a spot, so it gets synced. Returns the spot.
export function touch(loc) {
  loc.updatedAt = Math.max(Date.now(), (loc.updatedAt ?? 0) + 1)
  if (!store.dirty.includes(loc.id)) store.dirty.push(loc.id)
  return loc
}

// The user's own visits on a spot: its own list, or on a shared spot the ones they logged there.
export const myVisits = (loc) => (loc.shared ? (store.guestVisits[loc.id] ??= { visits: [], updatedAt: 0 }).visits : loc.visits)

// Call after changing myVisits(loc), so they get synced.
export function touchVisits(loc) {
  if (!loc.shared) return touch(loc)
  const g = store.guestVisits[loc.id]
  g.updatedAt = Math.max(Date.now(), g.updatedAt + 1)
  if (!store.dirtyGuest.includes(loc.id)) store.dirtyGuest.push(loc.id)
}

// Every visit at a spot, newest first: { v, mine, name }. name is who logged it, when it wasn't the user.
export function allVisits(loc) {
  const others = (store.otherVisits[loc.id] ?? []).flatMap((o) => o.visits.map((v) => ({ v, name: o.name })))
  const owner = loc.shared ? loc.visits.map((v) => ({ v, name: loc.owner })) : []
  const mine = (loc.shared ? store.guestVisits[loc.id]?.visits ?? [] : loc.visits).map((v) => ({ v, mine: true }))
  return [...mine, ...owner, ...others].sort((a, b) => b.v.date.localeCompare(a.v.date))
}

export function removeLocation(loc) {
  store.locations.splice(store.locations.indexOf(loc), 1)
  store.deleted.push({ id: loc.id, updatedAt: Math.max(Date.now(), loc.updatedAt + 1), deleted: true })
}

export function toggleReminder(id) {
  const i = store.reminders.indexOf(id)
  i === -1 ? store.reminders.push(id) : store.reminders.splice(i, 1)
  return syncPush()
}

export function toggleNotify(key) {
  store.notify[key] = !store.notify[key]
  return syncPush()
}

// Sends this phone's reminders to api/push.php, which pushes them when a season starts or peaks.
// Resolves to false when this browser can't get push (e.g. an iPhone before "Lisa avaekraanile").
export async function syncPush() {
  try {
    const reg = await navigator.serviceWorker?.getRegistration()
    if (!reg?.pushManager || (!store.reminders.length && Notification.permission !== 'granted')) return false
    if ((await Notification.requestPermission()) !== 'granted') return false
    let sub = await reg.pushManager.getSubscription()
    if (!sub) {
      const { publicKey } = await (await fetch('/api/push.php')).json()
      const key = Uint8Array.from(atob(publicKey.replace(/-/g, '+').replace(/_/g, '/')), (c) => c.charCodeAt(0))
      sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key })
    }
    // Berries removed or hidden in /admin are skipped.
    const reminders = store.reminders.map(findProduce).filter(Boolean).map(({ id, name, season }) => ({ id, name, season }))
    const res = await fetch('/api/push.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ subscription: sub, reminders, notify: store.notify }),
    })
    return res.ok
  } catch {
    return false
  }
}
