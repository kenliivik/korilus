<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import VisitFields from '../components/VisitFields.vue'
import PickMap from '../components/PickMap.vue'
import { store, today, touch } from '../data/store.js'
import { account } from '../data/account.js'

// New spot (no id) or editing an existing one ("Muuda").
const props = defineProps({ id: { type: String, default: '' } })
const router = useRouter()
const existing = props.id && store.locations.find((l) => l.id === props.id) // own spots only, shared ones are read-only

const name = ref(existing?.name ?? '')
const coords = ref(existing?.lat != null ? { lat: existing.lat, lng: existing.lng } : null)
const groupId = ref(existing?.groupId ?? null) // null = private
const visit = ref({ berries: [], note: '', rating: 0 })
const gps = ref('')

// auto: the lookup when the form opens, which mustn't overwrite a spot already tapped on the map.
function locate(auto) {
  gps.value = 'Otsin asukohta…'
  navigator.geolocation?.getCurrentPosition(
    ({ coords: c }) => {
      if (auto && coords.value) return (gps.value = '')
      coords.value = { lat: c.latitude, lng: c.longitude }
      gps.value = 'Praegune asukoht on kaardil märgitud.'
    },
    () => (gps.value = 'Asukohta ei õnnestunud leida. Puuduta kaarti, et koht märkida.'),
    { enableHighAccuracy: true },
  ) ?? (gps.value = 'See seade ei toeta asukoha leidmist.')
}
if (!existing) locate(true)

function save() {
  if (existing) {
    touch(Object.assign(existing, { name: name.value.trim(), ...coords.value, groupId: groupId.value }))
    return router.replace(`/asukohad/${existing.id}`)
  }
  store.locations.unshift(touch({
    id: crypto.randomUUID(),
    name: name.value.trim(),
    ...coords.value,
    groupId: groupId.value,
    visits: [{ date: today(), ...visit.value }],
  }))
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

    <label v-if="account.user && account.groups.length" class="flex flex-col gap-2">
      <span class="text-xl">Jaga</span>
      <select v-model="groupId" class="field">
        <option :value="null">Privaatne (ainult mina)</option>
        <option v-for="g in account.groups" :key="g.id" :value="g.id">Grupiga „{{ g.name }}”</option>
      </select>
    </label>

    <section class="flex flex-col gap-2">
      <span class="text-xl">Asukoht</span>
      <PickMap v-model="coords" />
      <p class="font-hand text-lg text-muted">Puuduta kaarti, et koht märkida.</p>
      <button type="button" class="grain card flex items-center gap-3 text-left text-muted" @click="locate()">
        <AppIcon name="pin" :size="20" />
        <span class="flex-1">{{ gps || 'Kasuta praegust asukohta' }}</span>
      </button>
    </section>

    <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="router.back()">Tühista</button>
  </form>
</template>
