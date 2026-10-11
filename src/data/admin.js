import { ref } from 'vue'

// Admin login for /admin (api/admin-login.php). The session lives in an HttpOnly cookie, so the app only asks.
export const isAdmin = ref(null) // null until checked
export const configured = ref(true) // false while the server has no admin password yet

const call = (body) =>
  fetch('/api/admin-login.php', body && { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) })

export async function checkAdmin() {
  try {
    const r = await (await call()).json()
    configured.value = r.configured
    return (isAdmin.value = r.admin)
  } catch {
    return (isAdmin.value = false)
  }
}

// Resolves to { admin } or { error, hash } (hash: what to put in config.php while no password is set).
export async function login(password) {
  const r = await (await call({ password })).json().catch(() => ({ error: 'offline' }))
  isAdmin.value = !!r.admin
  return r
}

export async function logout() {
  await call({ logout: true })
  isAdmin.value = false
}

// Uploads a picture (api/upload.php). Big phone photos are shrunk here first, so they fit the
// server's upload limit; the server then resizes to 800px.
export async function uploadImage(file) {
  try {
    const bmp = await createImageBitmap(file)
    const s = Math.min(1, 1200 / Math.max(bmp.width, bmp.height))
    const canvas = Object.assign(document.createElement('canvas'), { width: Math.round(bmp.width * s), height: Math.round(bmp.height * s) })
    canvas.getContext('2d').drawImage(bmp, 0, 0, canvas.width, canvas.height)
    file = (await new Promise((done) => canvas.toBlob(done, 'image/webp', 0.9))) ?? file
  } catch { /* send the original */ }
  const form = new FormData()
  form.append('file', file)
  const res = await fetch('/api/upload.php', { method: 'POST', body: form })
  if (res.status === 401) isAdmin.value = false
  if (!res.ok) throw new Error('Pildi üleslaadimine ebaõnnestus')
  return (await res.json()).url
}
