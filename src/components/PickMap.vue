<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'
import { createMap, pin } from './map.js'

// Tap the map to put the spot there (e.g. adding it at home after the forest trip).
const coords = defineModel({ type: Object, default: null }) // { lat, lng } or null
const el = ref()
let map, marker, tapped = false

function show(c) {
  if (!c) return marker?.remove()
  marker ??= L.marker([c.lat, c.lng], { icon: pin })
  marker.setLatLng([c.lat, c.lng]).addTo(map)
  if (!tapped) map.setView([c.lat, c.lng], Math.max(map.getZoom(), 14)) // GPS: zoom in on it
  tapped = false
}

onMounted(() => {
  map = createMap(el.value)
  map.on('click', (e) => {
    tapped = true
    coords.value = { lat: e.latlng.lat, lng: e.latlng.lng }
  })
  show(coords.value)
})
watch(coords, show) // GPS or a tap moved the spot
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div ref="el" class="z-0 aspect-square overflow-hidden rounded-card shadow-neu-inset" aria-label="Kaart, puuduta koha märkimiseks" />
</template>
