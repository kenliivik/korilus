// Uploads a picture for /admin (api/upload.php). Big phone photos are shrunk here first, so they fit the
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
  if (!res.ok) throw new Error(res.status === 401 ? 'Logi uuesti sisse' : 'Pildi üleslaadimine ei õnnestunud')
  return (await res.json()).url
}
