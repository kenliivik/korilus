import { reactive, watch } from 'vue'
import { store } from './store.js'

// Login (email + typed code), groups and spot sync with the server (public/api/*.php).
// The spots stay local-first in store.locations: the app works the same logged out, and when logged in
// changed spots go up and the server's copies come back, the newer updatedAt winning.
const KEY = 'korilus-account'
const saved = JSON.parse(localStorage.getItem(KEY)) ?? {}

export const account = reactive({
  user: saved.user ?? null, // { id, email, name, isAdmin }
  groups: saved.groups ?? [], // { id, name, role, members: [{ id, name, email (own only), role }], invites: [{ id, userId, name }] }
  people: saved.people ?? [], // who the user can add to a group without an email: { id, name }
  invitations: [], // groups the user was invited to: { groupId, name, from }
  syncedAt: saved.syncedAt ?? 0, // when this phone last synced (0 = not since logging in)
  syncing: false,
  error: '',
})

watch(
  () => [account.user, account.groups, account.people, account.syncedAt],
  () => localStorage.setItem(KEY, JSON.stringify({ user: account.user, groups: account.groups, people: account.people, syncedAt: account.syncedAt })),
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
  if (data.people) account.people = data.people
  if (data.invitations) account.invitations = data.invitations
  return data
}

// Logged out (or the session ended): the spots stay on this phone, the shared ones go.
function forget() {
  Object.assign(account, { user: null, groups: [], people: [], invitations: [], syncedAt: 0, error: '' })
  store.shared = []
  store.sharedRecipes = []
  store.sharedNotes = []
  store.otherVisits = {}
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
  try {
    store.shared = merge(await pushList('spots.php', 'spots', store.locations, 'dirty', 'deleted'), store.locations, store.deleted)
    store.sharedRecipes = merge(await pushList('myrecipes.php', 'recipes', store.myRecipes, 'dirtyRecipes', 'deletedRecipes'), store.myRecipes, store.deletedRecipes)
    await pushNotes()
    await pushVisits()
    account.syncedAt = started
    account.error = ''
    // Changed while this sync was on its way.
    if (store.dirty.length + store.deleted.length + store.dirtyRecipes.length + store.deletedRecipes.length + store.dirtyNotes.length + store.dirtyGuest.length) later(1500)
  } catch (e) {
    account.error = e.message
  } finally {
    account.syncing = false
  }
}

// Spots and own recipes sync the same way. After logging in every item goes up, later only the changed (dirty) ones
// and the deleted ones (tombstones). Answers with the server's list.
async function pushList(file, key, list, dirtyKey, deletedKey) {
  const changed = account.syncedAt ? list.filter((x) => store[dirtyKey].includes(x.id)) : [...list]
  const sent = new Map(changed.map((x) => [x.id, x.updatedAt]))
  const tombstones = [...store[deletedKey]]
  const data = await api(file, { [key]: [...changed.map((x) => ({ ...x })), ...tombstones] })
  store[dirtyKey] = store[dirtyKey].filter((id) => list.some((x) => x.id === id && x.updatedAt !== sent.get(id)))
  store[deletedKey] = store[deletedKey].filter((d) => !tombstones.includes(d))
  return data[key]
}

// Puts the server's copies into `list` (store.locations or store.myRecipes), the newer updatedAt winning,
// and returns the ones others share with the user's groups.
export function merge(items, list = store.locations, deletedList = store.deleted) {
  const shared = []
  for (const { mine, owner, deleted, ...item } of items) {
    if (!mine) {
      if (!deleted) shared.push({ ...item, owner, shared: true })
      continue
    }
    const i = list.findIndex((x) => x.id === item.id)
    if (i !== -1 && list[i].updatedAt >= item.updatedAt) continue // this phone's copy is newer, it goes up next time
    if (deleted) {
      if (i !== -1) list.splice(i, 1)
    } else if (!deletedList.some((d) => d.id === item.id && d.updatedAt > item.updatedAt)) {
      i === -1 ? list.unshift(item) : (list[i] = item)
    }
  }
  return shared
}

async function pushNotes() {
  const changed = (account.syncedAt ? store.dirtyNotes : Object.keys(store.notes)).filter((id) => store.notes[id])
  const sent = new Map(changed.map((id) => [id, store.notes[id].updatedAt]))
  const { notes } = await api('notes.php', { notes: changed.map((id) => ({ recipe: id, ...store.notes[id] })) })
  store.dirtyNotes = store.dirtyNotes.filter((id) => store.notes[id] && store.notes[id].updatedAt !== sent.get(id))
  mergeNotes(notes)
}

export function mergeNotes(notes) {
  const shared = []
  for (const { recipe, mine, owner, ...note } of notes) {
    if (!mine) {
      if (note.general || Object.keys(note.lines ?? {}).length) shared.push({ recipe, owner, lines: note.lines ?? {}, general: note.general ?? '' })
    } else if ((store.notes[recipe]?.updatedAt ?? 0) < note.updatedAt) {
      store.notes[recipe] = { lines: {}, general: '', ...note }
    }
  }
  store.sharedNotes = shared
}

// The user's visits on spots others share, the same way as notes. Others' visits come back into store.otherVisits.
async function pushVisits() {
  const changed = (account.syncedAt ? store.dirtyGuest : Object.keys(store.guestVisits)).filter((id) => store.guestVisits[id])
  const sent = new Map(changed.map((id) => [id, store.guestVisits[id].updatedAt]))
  const { visits } = await api('visits.php', { visits: changed.map((id) => ({ spot: id, ...store.guestVisits[id] })) })
  store.dirtyGuest = store.dirtyGuest.filter((id) => store.guestVisits[id] && store.guestVisits[id].updatedAt !== sent.get(id))
  const others = {}
  for (const { spot, mine, name, updatedAt, visits: list } of visits) {
    if (!mine) (others[spot] ??= []).push({ name, visits: list })
    else if ((store.guestVisits[spot]?.updatedAt ?? 0) < updatedAt) store.guestVisits[spot] = { visits: list, updatedAt }
  }
  store.otherVisits = others
}

// Sync shortly after a change, when the app comes back to the screen, and when the phone is online again.
let timer
const later = (ms) => {
  clearTimeout(timer)
  timer = setTimeout(sync, ms)
}
watch(() => store.dirty.length + store.deleted.length + store.dirtyRecipes.length + store.deletedRecipes.length + store.dirtyNotes.length + store.dirtyGuest.length, (n) => n && account.user && later(1500))
document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && Date.now() - account.syncedAt > 60000 && sync())
addEventListener('online', () => sync())

if (account.user) {
  api('login.php')
    .then((d) => (d.user ? sync() : forget()))
    .catch(() => {})
}
