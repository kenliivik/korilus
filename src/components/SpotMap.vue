<script setup>
import { onMounted, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import L from 'leaflet'
import { createMap, pin, sharedPin } from './map.js'

const props = defineProps({
  spots: { type: Array, required: true },
  small: { type: Boolean, default: false }, // one spot's own page: a short map that doesn't open the spot again
})
const router = useRouter()
const el = ref()
let map, pins
const fit = (spots) => spots.length && map.fitBounds(spots.map((s) => [s.lat, s.lng]), { padding: [40, 40], maxZoom: 14 })

// Redrawn when the spots change, e.g. a sync brings spots shared by the group after the map opened.
function draw(spots) {
  pins.clearLayers()
  for (const s of spots) {
    L.marker([s.lat, s.lng], { icon: s.shared ? sharedPin : pin, title: s.name }).addTo(pins).on('click', () => props.small || router.push(`/asukohad/${s.id}`))
  }
}

onMounted(() => {
  map = createMap(el.value)
  map.attributionControl.setPosition('bottomleft') // the phone's floating "+" sits bottom right on Asukohad
  pins = L.layerGroup().addTo(map)
  draw(props.spots)
  fit(props.spots)
})
watch(() => props.spots, (spots, before) => {
  if (!pins) return
  draw(spots)
  if (!before.length) fit(spots) // the first spots arrived: zoom to them, otherwise keep the view
})
onBeforeUnmount(() => map?.remove())
</script>

<template>
  <!-- Fills the screen between the page title and the bottom menu (9.25rem = title, gaps and menu; --map-room makes space for more). -->
  <div v-if="small" ref="el" class="z-0 aspect-[4/3] overflow-hidden rounded-card shadow-neu-inset" aria-label="Kaart" />
  <div
    v-else
    ref="el"
    class="z-0 h-[calc(100dvh_-_max(1rem,env(safe-area-inset-top))_-_max(1.5rem,env(safe-area-inset-bottom))_-_9.25rem_-_var(--map-room,0rem))] min-h-72 shrink-0 overflow-hidden rounded-card shadow-neu-inset"
    aria-label="Kaart"
  />
</template>
