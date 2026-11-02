import { reactive } from 'vue'

// A recipe picture is either a photo (cropped to fill its frame) or a drawing on a see-through background,
// like Ken's berry illustrations (shown whole, spilling over the top of its card). The picture itself tells
// which: a drawing has see-through corners. Answers are cached; until one is known the picture counts as a photo.
const known = reactive({})

export function isCutout(url) {
  if (!url) return false
  if (!(url in known)) {
    known[url] = false
    const img = new Image()
    img.onload = () => {
      try {
        const c = Object.assign(document.createElement('canvas'), { width: 16, height: 16 })
        const ctx = c.getContext('2d', { willReadFrequently: true })
        ctx.drawImage(img, 0, 0, 16, 16)
        const corners = [[0, 0], [15, 0], [0, 15], [15, 15]].map(([x, y]) => ctx.getImageData(x, y, 1, 1).data[3])
        known[url] = corners.filter((a) => a < 128).length >= 2
      } catch { /* another site's picture: keep it a photo */ }
    }
    img.src = url
  }
  return known[url]
}
