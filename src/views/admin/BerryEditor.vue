<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import BerryGlyph from '../../components/BerryGlyph.vue'
import { bundled, content, loadContent, saveContent, months, phases, statusText, dateLabel } from '../../data/berries.js'
import { checkAdmin, uploadImage } from '../../data/admin.js'

// Add a berry (no id) or edit one. Saving sends it to api/content.php, so every phone gets it.
const props = defineProps({ id: { type: String, default: '' } })
const router = useRouter()

const form = ref(null)
const preview = ref(false)
const busy = ref(false)
const message = ref('')

;(async () => {
  if (!(await checkAdmin())) return router.replace('/admin')
  await loadContent()
  const b = content.berries.find((b) => b.id === props.id)
  form.value = {
    id: '', name: '', latin: '', season: '............', image: '', hidden: false,
    ...JSON.parse(JSON.stringify(b ?? {})),
  }
  form.value.dates = { Algus: '', Tipp: '', Lõpp: '', ...form.value.dates }
  form.value.tips = form.value.tips?.length ? form.value.tips : ['']
})()

// New berries get their id (and address /mari/<id>) from the name: "Põldmari" → "poldmari".
const slug = (s) => s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '').slice(0, 40)
const newId = computed(() => {
  const base = slug(form.value?.name ?? '') || 'mari'
  let id = base
  for (let n = 2; content.berries.some((b) => b.id === id); n++) id = `${base}-${n}`
  return id
})

// Tap a month to step it through: not in season → algus → tipp → lõpp.
const next = { '.': 'a', a: 't', t: 'l', l: '.' }
const cellColor = { a: 'bg-leaf/35', t: 'bg-sage text-milk', l: 'bg-muted/35', '.': 'grain shadow-neu-raised-sm' }
const tapMonth = (i) => (form.value.season = form.value.season.slice(0, i) + next[form.value.season[i]] + form.value.season.slice(i + 1))

const item = computed(() => {
  const f = form.value
  const b = {
    id: f.id || newId.value,
    name: f.name.trim(),
    latin: f.latin.trim(),
    season: f.season,
    dates: Object.fromEntries(Object.entries(f.dates).map(([k, v]) => [k, v.trim()]).filter(([, v]) => v)),
    tips: f.tips.map((t) => t.trim()).filter(Boolean),
  }
  if (f.image) b.image = f.image
  if (f.hidden) b.hidden = true
  return b
})

async function pickImage(e) {
  const file = e.target.files[0]
  if (!file) return
  busy.value = true
  message.value = ''
  try {
    form.value.image = await uploadImage(file)
  } catch (err) {
    message.value = err.message
  }
  busy.value = false
}

async function run(body) {
  busy.value = true
  message.value = ''
  try {
    await saveContent(body)
    router.push('/admin')
  } catch (e) {
    if (e.status === 401) return router.replace('/admin')
    message.value = `Salvestamine ei õnnestunud (${e.message}).`
  }
  busy.value = false
}

function save() {
  if (form.value.season === '............') return (message.value = 'Märgi kalendris vähemalt üks kuu.')
  run({ collection: 'berries', action: 'save', item: item.value })
}

function remove() {
  if (confirm(`Kas kustutad „${form.value.name}”? Marja saab ka lihtsalt peita.`)) run({ collection: 'berries', action: 'delete', id: form.value.id })
}
</script>

<template>
  <PageHeader :title="id ? 'Muuda marja' : 'Lisa mari'" back="/admin" />

  <p v-if="!form" class="italic text-muted">Laadin…</p>

  <!-- Preview: roughly how the berry page will look. -->
  <template v-else-if="preview">
    <section class="flex flex-col items-center gap-2">
      <BerryGlyph :name="item.id" :image="item.image" :size="200" />
      <h2 class="text-[32px] leading-9">{{ item.name || 'Nimeta' }}</h2>
      <p class="italic text-muted">{{ item.latin }}</p>
    </section>
    <section class="grain card grid grid-cols-12 gap-1 px-3">
      <span v-for="(c, i) in item.season" :key="i" class="h-6 rounded-sm" :class="c !== '.' && cellColor[c]" :title="months[i]" />
      <span v-for="(m, i) in months" :key="m" class="text-center text-xs text-muted uppercase" :class="{ 'text-sage': item.season[i] !== '.' }">{{ m[0] }}</span>
    </section>
    <dl class="grain card grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-lg">
      <template v-for="(value, label) in item.dates" :key="label">
        <dt class="text-muted">{{ dateLabel(label) }}</dt>
        <dd>{{ value }}</dd>
      </template>
      <dt class="text-muted">Praegu</dt>
      <dd class="font-hand text-xl">{{ statusText[item.season[new Date().getMonth()]] }}</dd>
    </dl>
    <ul v-if="item.tips.length" class="grain card flex list-inside list-disc flex-col gap-1 text-lg marker:text-leaf">
      <li v-for="tip in item.tips" :key="tip">{{ tip }}</li>
    </ul>
    <button type="button" class="btn bg-sage text-milk" :disabled="busy" @click="save">Salvesta</button>
    <button type="button" class="btn grain text-sage" @click="preview = false">Muuda edasi</button>
    <p v-if="message" class="italic text-[#9c3a3a]">{{ message }}</p>
  </template>

  <form v-else class="flex flex-col gap-5" @submit.prevent="save">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Nimi</span>
      <input v-model="form.name" required maxlength="40" class="field" placeholder="nt. Põldmari" />
      <span v-if="!form.id && form.name" class="text-base text-muted">Aadress: /mari/{{ newId }}</span>
    </label>

    <label class="flex flex-col gap-2">
      <span class="text-xl">Ladinakeelne nimi</span>
      <input v-model="form.latin" maxlength="80" class="field italic" placeholder="nt. Rubus caesius" />
    </label>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Hooaeg</legend>
      <div class="grid grid-cols-4 gap-2">
        <button
          v-for="(m, i) in months"
          :key="m"
          type="button"
          class="rounded-xl py-2 text-sm transition-shadow"
          :class="cellColor[form.season[i]]"
          :aria-label="`${m}: ${phases[form.season[i]] ?? 'pole hooaeg'}`"
          @click="tapMonth(i)"
        >
          {{ m }}
        </button>
      </div>
      <ul class="flex gap-5 text-base text-muted">
        <li v-for="(label, key) in phases" :key="key" class="flex items-center gap-2">
          <span class="size-4 rounded" :class="cellColor[key]" />{{ label }}
        </li>
      </ul>
      <p class="font-hand text-lg text-muted">Puuduta kuud: algus → parim aeg → lõpp → tühi.</p>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Valmimisaeg sõnadega</legend>
      <label v-for="(_, k) in form.dates" :key="k" class="grid grid-cols-[5.5rem_1fr] items-center gap-3">
        <span class="text-muted">{{ dateLabel(k) }}</span>
        <input v-model="form.dates[k]" maxlength="80" class="field" placeholder="nt. juuli algus" />
      </label>
    </fieldset>

    <fieldset class="flex flex-col gap-2">
      <legend class="mb-2 text-xl">Korjaja nõuanded</legend>
      <div v-for="(_, i) in form.tips" :key="i" class="flex items-center gap-2">
        <input v-model="form.tips[i]" maxlength="300" class="field" />
        <button type="button" aria-label="Eemalda nõuanne" class="grain grid size-9 shrink-0 place-items-center rounded-full text-muted shadow-neu-raised-sm" @click="form.tips.splice(i, 1)">
          <AppIcon name="trash" :size="18" />
        </button>
      </div>
      <button type="button" class="btn grain text-sage" @click="form.tips.push('')"><AppIcon name="plus" :size="20" /> Lisa nõuanne</button>
    </fieldset>

    <fieldset class="flex flex-col gap-3">
      <legend class="mb-2 text-xl">Pilt</legend>
      <div class="flex items-center gap-4">
        <BerryGlyph :name="item.id" :image="form.image" :size="80" class="shrink-0" />
        <div class="flex flex-1 flex-col gap-2">
          <label class="btn grain cursor-pointer text-base text-sage">
            {{ busy ? 'Laadin üles…' : form.image ? 'Vaheta pilt' : 'Lisa pilt' }}
            <input type="file" accept="image/*" class="sr-only" :disabled="busy" @change="pickImage" />
          </label>
          <button v-if="form.image" type="button" class="text-base text-muted underline underline-offset-4" @click="form.image = ''">Eemalda pilt</button>
        </div>
      </div>
      <p class="text-base italic text-muted">Foto või joonistus. Kui pilti pole, näitame tavalist joonistust.</p>
    </fieldset>

    <label class="grain card flex items-center justify-between text-lg">
      Peida rakendusest
      <input v-model="form.hidden" type="checkbox" class="size-6 accent-sage" />
    </label>

    <p v-if="message" class="italic text-[#9c3a3a]">{{ message }}</p>
    <button type="button" class="btn grain text-sage" @click="preview = true">Eelvaade</button>
    <button type="submit" class="btn bg-sage text-milk" :disabled="busy">Salvesta</button>
    <!-- The app's own species come back after deleting, so they can only be hidden. -->
    <button v-if="form.id && !bundled.some((b) => b.id === form.id)" type="button" class="btn grain text-[#9c3a3a]" :disabled="busy" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta
    </button>
  </form>
</template>
