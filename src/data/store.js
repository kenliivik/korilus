import { reactive, watch } from 'vue'
import { findProduce } from './berries.js'

// Everything is kept on this phone. Only the reminders also go to the server, for push (see syncPush).
const KEY = 'korilus'

export const store = reactive(
  JSON.parse(localStorage.getItem(KEY)) ?? {
    locations: [], // { id, name, lat, lng, visits: [{ date, berries, rating, note }] }, newest visit first
    reminders: [], // produce ids
    notify: { start: true, peak: true },
  },
)
store.savedRecipes ??= [] // recipe ids ("Pane meelde"); added later, so older phones don't have it yet

watch(store, () => localStorage.setItem(KEY, JSON.stringify(store)), { deep: true })

export const findLocation = (id) => store.locations.find((l) => l.id === id)
export const berriesAt = (loc) => [...new Set(loc.visits.flatMap((v) => v.berries))]
export const averageRating = (loc) =>
  Math.round(loc.visits.reduce((sum, v) => sum + v.rating, 0) / (loc.visits.length || 1))
export const today = () => new Date().toISOString().slice(0, 10)

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
