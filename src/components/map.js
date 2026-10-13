import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// OpenStreetMap tiles: the one outside service Korilus uses (Ken, 2026-10-10).
// Usage policy: keep the attribution, don't prefetch or bulk-download tiles.
// Tiles already seen are kept by the service worker (vite.config.js), so the map still shows in the forest without signal.
// crossOrigin makes them normal (CORS) responses, which the browser can store without the huge padding of opaque ones.
export function createMap(el) {
  const map = L.map(el, { zoomControl: false }).setView([58.6, 25], 7) // Estonia
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    crossOrigin: true,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map)
  offlineNote().addTo(map)
  return map
}

// Says why parts of the map are blank when the phone has no connection.
function offlineNote() {
  const note = L.control({ position: 'topleft' })
  note.onAdd = (map) => {
    const el = L.DomUtil.create('div', 'grain mr-2.5 rounded-2xl px-3 py-1.5 font-serif text-base leading-5 text-sage shadow-neu-raised-sm')
    el.textContent = 'Võrku pole. Kaart näitab ainult varem vaadatud kohti.'
    const show = () => (el.hidden = navigator.onLine)
    show()
    addEventListener('online', show)
    addEventListener('offline', show)
    map.on('unload', () => (removeEventListener('online', show), removeEventListener('offline', show)))
    return el
  }
  return note
}

// Opens the phone's own map app with directions to the spot: Apple Maps on iPhone, the default map app on Android
// (geo: link), OpenStreetMap directions on a computer.
export function directionsUrl({ lat, lng }) {
  const ua = navigator.userAgent
  if (/iPhone|iPad|iPod/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1)) return `https://maps.apple.com/?daddr=${lat},${lng}`
  if (/Android/.test(ua)) return `geo:${lat},${lng}?q=${lat},${lng}`
  return `https://www.openstreetmap.org/directions?route=%3B${lat}%2C${lng}#map=15/${lat}/${lng}`
}

// Own spots in sage, spots shared by others in the muted brown.
const icon = (className) => L.divIcon({
  className,
  html: '<svg width="28" height="28" viewBox="0 0 24 24" fill="#fdfbf0" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
  iconSize: [28, 28],
  iconAnchor: [14, 28],
})
export const pin = icon('text-sage')
export const sharedPin = icon('text-muted')
