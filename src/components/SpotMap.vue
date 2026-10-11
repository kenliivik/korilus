<script setup>
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'

// OpenStreetMap tiles: the one outside service Korilus uses (Ken, 2026-10-10).
// Usage policy: keep the attribution, don't prefetch or bulk-download tiles.
const props = defineProps({ spots: { type: Array, required: true } })
const router = useRouter()
const el = ref()
let map

// Own spots in sage, spots shared by others in the muted brown.
const icon = (className) => L.divIcon({
  className,
  html: '<svg width="28" height="28" viewBox="0 0 24 24" fill="#fdfbf0" stroke="currentColor" stroke-width="1.5"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>',
  iconSize: [28, 28],
  iconAnchor: [14, 28],
})
const pin = icon('text-sage')
const sharedPin = icon('text-muted')

onMounted(() => {
  map = L.map(el.value, { zoomControl: false }).setView([58.6, 25], 7) // Estonia
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  }).addTo(map)
  for (const s of props.spots) {
    L.marker([s.lat, s.lng], { icon: s.shared ? sharedPin : pin, title: s.name }).addTo(map).on('click', () => router.push(`/asukohad/${s.id}`))
  }
  if (props.spots.length) map.fitBounds(props.spots.map((s) => [s.lat, s.lng]), { padding: [40, 40], maxZoom: 14 })
})
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div ref="el" class="z-0 aspect-square overflow-hidden rounded-card shadow-neu-inset" aria-label="Kaart" />
</template>
