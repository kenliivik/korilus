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
const visit = ref({ date: today(), berries: [], note: '', rating: 0 })
const gps = ref('')

// auto: the lookup when the form opens, which mustn't overwrite a spot already tapped on the map.
function locate(auto) {
  gps.value = 'Otsin, kus sa oled…'
  navigator.geolocation?.getCurrentPosition(
    ({ coords: c }) => {
      if (auto && coords.value) return (gps.value = '')
      coords.value = { lat: c.latitude, lng: c.longitude }
      gps.value = 'Sinu praegune koht on kaardil.'
    },
    () => (gps.value = 'Asukohta ei leidnud. Puuduta kaarti, et koht märkida.'),
    { enableHighAccuracy: true },
  ) ?? (gps.value = 'See telefon ei oska asukohta leida.')
}
if (!existing) locate(true)

// A spot without a place never shows on the map, so it needs one before saving.
const noPlace = ref(false)
function save() {
  if (!coords.value) return (noPlace.value = true)
  if (existing) {
    touch(Object.assign(existing, { name: name.value.trim(), ...coords.value, groupId: groupId.value }))
    return router.replace(`/asukohad/${existing.id}`)
  }
  store.locations.unshift(touch({
    id: crypto.randomUUID(),
    name: name.value.trim(),
    ...coords.value,
    groupId: groupId.value,
    visits: [visit.value],
  }))
  router.replace('/asukohad')
}
</script>

<template>
  <PageHeader :title="existing ? 'Muuda asukohta' : 'Lisa asukoht'" back="/asukohad" />

  <!-- Desktop: fields on the left, a big map on the right. On phones the wrapper dissolves and the buttons (order-1) stay last. -->
  <form class="flex flex-col gap-5 lg:grid lg:grid-cols-2 lg:items-start lg:gap-10" @submit.prevent="save">
    <div class="contents lg:flex lg:flex-col lg:gap-5">
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

    <p v-if="noPlace && !coords" role="alert" class="order-1 text-lg text-[#9c3a3a] lg:order-none">
      Märgi koht kaardile: puuduta kaarti või vajuta „Kasuta praegust asukohta”.
    </p>
    <button type="submit" class="btn order-1 bg-sage text-milk lg:order-none">Salvesta</button>
    <button type="button" class="btn order-1 grain text-sage lg:order-none" @click="router.back()">Tühista</button>
    </div>

    <section class="flex flex-col gap-2">
      <span class="text-xl">Asukoht</span>
      <PickMap v-model="coords" />
      <p class="font-hand text-lg text-muted">Puuduta kaarti, et koht märkida.</p>
      <button type="button" class="grain card flex items-center gap-3 text-left text-muted" @click="locate()">
        <AppIcon name="pin" :size="20" />
        <span class="flex-1">{{ gps || 'Kasuta praegust asukohta' }}</span>
      </button>
    </section>
  </form>
</template>
