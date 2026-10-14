import { reactive, watch } from 'vue'
import { store } from './store.js'

// Login (email + typed code), groups and spot sync with the server (public/api/*.php).
// The spots stay local-first in store.locations: the app works the same logged out, and when logged in
// changed spots go up and the server's copies come back, the newer updatedAt winning.
const KEY = 'korilus-account'
const saved = JSON.parse(localStorage.getItem(KEY)) ?? {}

export const account = reactive({
  user: saved.user ?? null, // { id, email, name, isAdmin }
  groups: saved.groups ?? [], // { id, name, role, members: [{ id, name, email (own only), role }], invites: [email] }
  invitations: [], // groups the user was invited to: { groupId, name, from }
  syncedAt: saved.syncedAt ?? 0, // when this phone last synced (0 = not since logging in)
  syncing: false,
  error: '',
})

watch(
  () => [account.user, account.groups, account.syncedAt],
  () => localStorage.setItem(KEY, JSON.stringify({ user: account.user, groups: account.groups, syncedAt: account.syncedAt })),
  { deep: true },
)

// POST when there is a body, else GET. Throws an Error whose message is the server's error code (or "network").
export async function api(file, body) {
  let res, data
  try {
    res = await fetch(`/api/${file}`, body && { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })
    data = await res.json()
  } catch {
    throw new Error('network')
  }
  if (res.status === 401 && data.error === 'login') forget()
  if (!res.ok) throw new Error(data.error || 'network')
  if (data.user !== undefined) account.user = data.user
  if (data.groups) account.groups = data.groups
  if (data.invitations) account.invitations = data.invitations
  return data
}

// Logged out (or the session ended): the spots stay on this phone, the shared ones go.
function forget() {
  Object.assign(account, { user: null, groups: [], invitations: [], syncedAt: 0, error: '' })
  store.shared = []
}

export const requestCode = (email) => api('login.php', { action: 'code', email })

export async function verifyCode(email, code) {
  await api('login.php', { action: 'verify', email, code })
  account.syncedAt = 0
  return sync()
}

export async function logout() {
  await api('login.php', { action: 'logout' }).catch(() => {})
  forget()
}

// For a lost phone: every phone and computer has to log in again, this one included.
export async function logoutAll() {
  await api('account.php', { action: 'logout_all' })
  forget()
}

export async function deleteAccount() {
  await api('account.php', { action: 'delete' })
  forget()
}

export const saveName = (name) => api('account.php', { action: 'name', name })

// Group changes can show or hide shared spots, so sync after each.
export async function groupAction(body) {
  const data = await api('groups.php', body)
  sync()
  return data.groups
}

export async function downloadData() {
  const res = await fetch('/api/account.php')
  if (!res.ok) throw new Error('network')
  const a = Object.assign(document.createElement('a'), {
    href: URL.createObjectURL(await res.blob()),
    download: 'korilus-minu-andmed.json',
  })
  a.click()
  setTimeout(() => URL.revokeObjectURL(a.href), 10000)
}

let running = null
export function sync() {
  if (!account.user) return Promise.resolve()
  return (running ??= push().finally(() => (running = null)))
}

async function push() {
  account.syncing = true
  const started = Date.now()
  // After logging in send every spot, later only the changed ones.
  const changed = account.syncedAt ? store.locations.filter((l) => store.dirty.includes(l.id)) : [...store.locations]
  const sent = new Map(changed.map((l) => [l.id, l.updatedAt]))
  const tombstones = [...store.deleted]
  try {
    const { spots } = await api('spots.php', { spots: [...changed.map((l) => ({ ...l })), ...tombstones] })
    store.dirty = store.dirty.filter((id) => store.locations.some((l) => l.id === id && l.updatedAt !== sent.get(id)))
    store.deleted = store.deleted.filter((d) => !tombstones.includes(d))
    merge(spots)
    account.syncedAt = started
    account.error = ''
    if (store.dirty.length || store.deleted.length) later(1500) // changed while this sync was on its way
  } catch (e) {
    account.error = e.message
  } finally {
    account.syncing = false
  }
}

export function merge(spots) {
  const shared = []
  for (const { mine, owner, deleted, ...spot } of spots) {
    if (!mine) {
      if (!deleted) shared.push({ ...spot, owner, shared: true })
      continue
    }
    const i = store.locations.findIndex((l) => l.id === spot.id)
    if (i !== -1 && store.locations[i].updatedAt >= spot.updatedAt) continue // this phone's copy is newer, it goes up next time
    if (deleted) {
      if (i !== -1) store.locations.splice(i, 1)
    } else if (!store.deleted.some((d) => d.id === spot.id && d.updatedAt > spot.updatedAt)) {
      i === -1 ? store.locations.unshift(spot) : (store.locations[i] = spot)
    }
  }
  store.shared = shared
}

// Sync shortly after a change, when the app comes back to the screen, and when the phone is online again.
let timer
const later = (ms) => {
  clearTimeout(timer)
  timer = setTimeout(sync, ms)
}
watch(() => store.dirty.length + store.deleted.length, (n) => n && account.user && later(1500))
document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && Date.now() - account.syncedAt > 60000 && sync())
addEventListener('online', () => sync())

if (account.user) {
  api('login.php')
    .then((d) => (d.user ? sync() : forget()))
    .catch(() => {})
}
