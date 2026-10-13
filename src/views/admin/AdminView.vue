<script setup>
import { ref, watch } from 'vue'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import BerryGlyph from '../../components/BerryGlyph.vue'
import LinkCard from '../../components/LinkCard.vue'
import { content, loadContent, saveContent } from '../../data/berries.js'
import { isAdmin, configured, checkAdmin, login, logout } from '../../data/admin.js'

const password = ref('')
const hash = ref('')
const message = ref('')

checkAdmin()
loadContent()

async function submit() {
  const r = await login(password.value)
  hash.value = r.hash ?? ''
  message.value = r.admin ? '' : { 'wrong password': 'Vale parool.', 'too many attempts': 'Liiga palju katseid. Proovi 15 minuti pärast uuesti.' }[r.error] ?? ''
  password.value = ''
}

async function save(body) {
  message.value = ''
  try {
    await saveContent(body)
  } catch (e) {
    if (e.status === 401) isAdmin.value = false
    message.value = `Salvestamine ei õnnestunud (${e.message}).`
  }
}

const toggleHidden = (b) => save({ collection: 'berries', action: 'save', item: { ...b, hidden: !b.hidden } })

// Drag the handle to reorder. The list is a local copy while dragging; the new order is saved on release.
const list = ref([])
watch(() => content.berries, (b) => (list.value = [...b]), { immediate: true })
const rows = ref(null)
const dragging = ref(null)

function drag(e) {
  if (!dragging.value) return
  const to = [...rows.value.children].findIndex((row) => e.clientY < row.getBoundingClientRect().bottom)
  const from = list.value.indexOf(dragging.value)
  list.value.splice(to === -1 ? list.value.length - 1 : to, 0, ...list.value.splice(from, 1))
}

function drop() {
  if (!dragging.value) return
  dragging.value = null
  const ids = list.value.map((b) => b.id)
  if (ids.join() !== content.berries.map((b) => b.id).join()) save({ collection: 'berries', action: 'reorder', ids })
}
</script>

<template>
  <PageHeader title="Haldus" back="/seaded" />

  <p v-if="isAdmin === null" class="italic text-muted">Laadin…</p>

  <form v-else-if="!isAdmin" class="flex flex-col gap-4" @submit.prevent="submit">
    <label class="flex flex-col gap-2">
      <span class="text-xl">Admini parool</span>
      <input v-model="password" type="password" required autocomplete="current-password" class="field" />
    </label>
    <button type="submit" class="btn bg-sage text-milk">Logi sisse</button>
    <p v-if="message" class="italic text-danger">{{ message }}</p>
    <div v-if="!configured" class="grain card flex flex-col gap-2 text-base">
      <p>Serveris pole veel admini parooli. Sisesta soovitud parool (vähemalt 8 märki) ja vajuta „Logi sisse”.</p>
      <template v-if="hash">
        <p>Seejärel lisa see rida serveris faili <code>api/data/config.php</code> (vaata README):</p>
        <code class="field text-sm break-all select-all">'admin_password_hash' => '{{ hash }}',</code>
      </template>
    </div>
  </form>

  <template v-else>
    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Marjad</h2>
      <p class="font-hand text-lg text-muted">Järjekorra muutmiseks lohista ⠿.</p>
      <ul ref="rows" class="flex flex-col gap-2">
        <li
          v-for="b in list"
          :key="b.id"
          class="grain card flex items-center gap-3 py-2 pl-2"
          :class="{ 'shadow-neu-inset': dragging === b, 'opacity-60': b.hidden }"
        >
          <span
            class="cursor-grab touch-none px-2 py-3 text-xl text-muted select-none"
            aria-hidden="true"
            @pointerdown="dragging = b; $event.currentTarget.setPointerCapture($event.pointerId)"
            @pointermove="drag"
            @pointerup="drop"
            @pointercancel="drop"
          >⠿</span>
          <BerryGlyph :name="b.id" :image="b.image" :size="32" />
          <RouterLink :to="`/admin/marjad/${b.id}`" class="flex-1 truncate text-lg">{{ b.name }}</RouterLink>
          <button type="button" class="text-base text-muted underline underline-offset-4" @click="toggleHidden(b)">
            {{ b.hidden ? 'Näita' : 'Peida' }}
          </button>
          <RouterLink :to="`/admin/marjad/${b.id}`" aria-label="Muuda" class="grain grid size-9 shrink-0 place-items-center rounded-full shadow-neu-raised-sm">
            <AppIcon name="edit" :size="18" />
          </RouterLink>
        </li>
      </ul>
      <p v-if="message" class="italic text-danger">{{ message }}</p>
      <RouterLink to="/admin/marjad" class="btn bg-sage text-milk"><AppIcon name="plus" :size="20" /> Lisa mari</RouterLink>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Retseptid</h2>
      <LinkCard label="Halda retsepte" icon="edit" to="/admin/retseptid" />
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Tagasiside</h2>
      <LinkCard label="Loe tagasisidet" icon="chat" to="/admin/tagasiside" />
    </section>

    <button type="button" class="btn grain text-sage" @click="logout">Logi välja</button>
  </template>
</template>
