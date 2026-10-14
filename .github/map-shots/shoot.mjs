// Temporary: screenshots of the maps with real OSM tiles (the dev sandbox can't reach OSM). Removed before merge.
import { chromium, devices } from 'playwright'
const base = 'http://localhost:4173'
const t = Date.now()
const loc = (id, name, lat, lng, shared) => ({ id, name, lat, lng, updatedAt: t, visits: [{ date: '2026-08-10', berries: ['mustikas'], rating: 4, note: '' }], ...(shared ? { shared: true } : {}) })
const state = {
  locations: [loc('a', 'Pohlamets', 58.392, 26.62), loc('b', 'Mustikaraba', 58.41, 26.69), loc('c', 'Vaarikapõld', 58.37, 26.75)],
  shared: [loc('d', 'Ema koht', 58.43, 26.58, true)],
  reminders: [], notify: { start: true, peak: true }, savedRecipes: [], dirty: [], deleted: [],
}
import { readFileSync } from 'node:fs'
const variants = JSON.parse(readFileSync(new URL('./variants.json', import.meta.url)))
const shots = [
  ['list', '/asukohad'],
  ['spot', '/asukohad/a'],
  ['new', '/asukohad/uus'],
]
const browser = await chromium.launch()
for (const [vname, css] of Object.entries(variants)) {
  for (const [dname, opts] of [['phone', { ...devices['iPhone 13'] }], ['desktop', { viewport: { width: 1440, height: 900 } }]]) {
    const ctx = await browser.newContext({ ...opts, serviceWorkers: 'block', geolocation: { latitude: 58.392, longitude: 26.62 }, permissions: ['geolocation'] })
    await ctx.addInitScript((s) => localStorage.setItem('korilus', JSON.stringify(s)), state)
    for (const [sname, path] of shots) {
      if (vname !== 'current' && sname !== 'list') continue
      const page = await ctx.newPage()
      await page.goto(base + path)
      if (css) await page.addStyleTag({ content: css })
      await page.waitForSelector('.leaflet-tile-loaded')
      await page.waitForTimeout(2500)
      await page.screenshot({ path: `shots/${vname}-${sname}-${dname}.png` })
      if (sname === 'list' && vname === 'current') {
      await page.close()
    }
    await ctx.close()
  }
}
await browser.close()
