import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// OpenStreetMap tiles: the one outside service Korilus uses (Ken, 2026-10-10).
// Usage policy: keep the attribution, don't prefetch or bulk-download tiles.
export function createMap(el) {
  const map = L.map(el, { zoomControl: false }).setView([58.6, 25], 7) // Estonia
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map)
  return map
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
