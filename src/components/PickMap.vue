<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import L from 'leaflet'
import { createMap, pin, droppedPin } from './map.js'

// Tap the map to put the spot there (e.g. adding it at home after the forest trip).
const coords = defineModel({ type: Object, default: null }) // { lat, lng } or null
const emit = defineEmits(['pick']) // the person tapped the map (not the GPS)
const el = ref()
let map, marker, tapped = false, ready = false

function show(c) {
  marker?.remove()
  if (!c) return
  // A fresh marker each time, so the pin drops in again where you tapped (the saved spot just sits there).
  marker = L.marker([c.lat, c.lng], { icon: ready ? droppedPin : pin }).addTo(map)
  if (!tapped) map.setView([c.lat, c.lng], Math.max(map.getZoom(), 14)) // GPS: zoom in on it
  tapped = false
}

onMounted(() => {
  map = createMap(el.value)
  map.on('click', (e) => {
    tapped = true
    navigator.vibrate?.(8) // a small tick on Android phones
    coords.value = { lat: e.latlng.lat, lng: e.latlng.lng }
    emit('pick')
  })
  show(coords.value)
  ready = true
})
watch(coords, show) // GPS or a tap moved the spot
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div ref="el" class="z-0 aspect-square overflow-hidden rounded-card shadow-neu-inset" aria-label="Kaart, puuduta koha märkimiseks" />
</template>
