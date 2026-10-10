<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import VisitFields from '../components/VisitFields.vue'
import { store, findLocation, today } from '../data/store.js'

// New spot (no id) or editing an existing one ("Muuda").
const props = defineProps({ id: { type: String, default: '' } })
const router = useRouter()
const existing = props.id && findLocation(props.id)

const name = ref(existing?.name ?? '')
const coords = ref(existing ? { lat: existing.lat, lng: existing.lng } : null)
const visit = ref({ berries: [], note: '', rating: 0 })
const gps = ref('')

function locate() {
  gps.value = 'Otsin asukohta…'
  navigator.geolocation?.getCurrentPosition(
    ({ coords: c }) => {
      coords.value = { lat: c.latitude, lng: c.longitude }
      gps.value = `Asukoht leitud (${c.latitude.toFixed(4)}, ${c.longitude.toFixed(4)})`
    },
    () => (gps.value = 'Asukohta ei õnnestunud leida. Koht salvestatakse ilma kaardita.'),
    { enableHighAccuracy: true },
  ) ?? (gps.value = 'See seade ei toeta asukoha leidmist.')
}
if (!existing) locate()

function save() {
  if (existing) {
    Object.assign(existing, { name: name.value.trim(), ...coords.value })
    return router.replace(`/asukohad/${existing.id}`)
  }
  store.locations.unshift({
    id: crypto.randomUUID(),
    name: name.value.trim(),
    ...coords.value,
    visits: [{ date: today(), ...visit.value }],
  })
  router.replace('/asukohad')
}
</script>

<template>
  <PageHeader :title="existing ? 'Muuda asukohta' : 'Lisa asukoht'" back="/asukohad" />

  <form class="flex flex-col gap-5" @submit.prevent="save">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Koha nimi</span>
      <input v-model="name" required class="field" placeholder='nt. "Vana metsa pool"' />
    </label>

    <VisitFields v-if="!existing" v-model="visit" />

    <button type="button" class="grain card flex items-center gap-3 text-left text-muted" @click="locate">
      <AppIcon name="pin" :size="20" />
      <span class="flex-1">{{ gps || 'Puuduta, et salvestada praegune asukoht' }}</span>
    </button>

    <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="router.back()">Tühista</button>
  </form>
</template>
