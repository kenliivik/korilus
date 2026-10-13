<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import VisitFields from '../components/VisitFields.vue'
import { store, today, touch } from '../data/store.js'
import { account } from '../data/account.js'

// New spot (no id) or editing an existing one ("Muuda").
const props = defineProps({ id: { type: String, default: '' } })
const router = useRouter()
const existing = props.id && store.locations.find((l) => l.id === props.id) // own spots only, shared ones are read-only

const name = ref(existing?.name ?? '')
const coords = ref(existing ? { lat: existing.lat, lng: existing.lng } : null)
const groupId = ref(existing?.groupId ?? null) // null = private
const visit = ref({ berries: [], note: '', rating: 0 })
const gps = ref('')

function locate() {
  gps.value = 'Otsin, kus sa oled…'
  navigator.geolocation?.getCurrentPosition(
    ({ coords: c }) => {
      coords.value = { lat: c.latitude, lng: c.longitude }
      gps.value = `Koht on kaardil (${c.latitude.toFixed(4)}, ${c.longitude.toFixed(4)})`
    },
    () => (gps.value = 'Asukohta ei leidnud. Salvestan koha ilma kaardita.'),
    { enableHighAccuracy: true },
  ) ?? (gps.value = 'See telefon ei oska asukohta leida.')
}
if (!existing) locate()

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
        <option :value="null">Ainult mina</option>
        <option v-for="g in account.groups" :key="g.id" :value="g.id">Mina ja grupp „{{ g.name }}”</option>
      </select>
    </label>

    <button type="button" class="grain card flex items-center gap-3 text-left text-muted" @click="locate">
      <AppIcon name="pin" :size="20" />
      <span class="flex-1">{{ gps || 'Puuduta, et märkida koht kaardile' }}</span>
    </button>

    <button type="submit" class="btn bg-sage text-milk">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="router.back()">Tühista</button>
  </form>
</template>
