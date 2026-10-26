import { computed } from 'vue'
import { store } from './store.js'
import { findProduce } from './berries.js'

// A quiet line on home about what the user's groups did lately, built from what sync already brings
// (store.shared, store.sharedRecipes). Only the last two weeks and the newest three, and nothing shared before sharedAt existed.
const DAYS = 14

export function activityFrom(spots, recipes, now = Date.now()) {
  const since = now - DAYS * 864e5
  const items = []
  for (const s of spots) {
    if (s.sharedAt > since) items.push({ at: s.sharedAt, who: s.owner, text: `jagas kohta „${s.name}”`, to: `/asukohad/${s.id}` })
    for (const v of s.visits ?? []) {
      const at = new Date(`${v.date}T12:00`).getTime()
      if (!(at > since) || at > now || !(s.sharedAt <= at + 864e5)) continue // a visit before the spot was shared isn't news
      const what = v.berries.map((id) => findProduce(id)?.name.toLowerCase()).filter(Boolean).join(', ')
      items.push({ at, who: s.owner, text: `käis kohas „${s.name}”${what ? ` · ${what}` : ''}`, to: `/asukohad/${s.id}` })
    }
  }
  for (const r of recipes) {
    if (r.sharedAt > since) items.push({ at: r.sharedAt, who: r.owner, text: `lisas retsepti „${r.title}”`, to: `/retsept/${r.id}` })
  }
  return items.sort((a, b) => b.at - a.at).slice(0, 3)
}

export const activity = computed(() => activityFrom(store.shared, store.sharedRecipes))

// "täna", "eile", "3 päeva tagasi".
export function ago(at, now = Date.now()) {
  const days = Math.round((new Date(now).setHours(12, 0, 0, 0) - new Date(at).setHours(12, 0, 0, 0)) / 864e5)
  return days <= 0 ? 'täna' : days === 1 ? 'eile' : `${days} päeva tagasi`
}
