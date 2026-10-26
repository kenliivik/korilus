<script setup>
import { ref } from 'vue'
import AppIcon from '../../components/AppIcon.vue'
import { api } from '../../data/account.js'
import { ask } from '../../data/confirm.js'

// Haldus → Liitumine: the switch that lets anyone join (default off, invite only), and the accounts, for removing spam.
const info = ref(null)
const error = ref('')

async function load(body) {
  error.value = ''
  try {
    info.value = await api('settings.php', body)
  } catch (e) {
    error.value = e.message === 'network' ? 'Laadimine ei õnnestunud.' : 'Logi adminina sisse.'
  }
}
load()

async function toggle() {
  const open = !info.value.signupOpen
  const question = open
    ? 'Kas avad Koriluse kõigile? Siis saab igaüks oma e-postiga konto teha (kuni 30 uut kontot päevas).'
    : 'Kas sulged liitumise? Olemasolevad kontod jäävad alles, uued saavad tulla ainult kutsega.'
  if (await ask(question)) load({ action: 'signup', open })
}
const remove = async (u) => (await ask(`Kas kustutad konto ${u.email} koos kõigi selle andmetega?`)) && load({ action: 'delete_user', id: u.id })
const date = (s) => new Date(s * 1000).toLocaleDateString('et-EE', { day: 'numeric', month: 'short', year: 'numeric' })
</script>

<template>
  <section class="flex flex-col gap-3">
    <h2 class="text-2xl leading-7">Liitumine</h2>
    <p v-if="error" role="alert" class="text-lg text-danger">{{ error }}</p>
    <template v-if="info">
      <div class="grain card flex items-center gap-4">
        <span class="flex flex-1 flex-col">
          <span class="text-xl">{{ info.signupOpen || info.configOpen ? 'Avatud kõigile' : 'Ainult kutsega' }}</span>
          <span class="text-base text-muted">
            {{ info.users }} kontot{{ info.newToday ? `, täna ${info.newToday} uut` : '' }}
          </span>
        </span>
        <button
          type="button"
          role="switch"
          :aria-checked="info.signupOpen"
          aria-label="Avatud kõigile"
          class="relative h-9 w-16 shrink-0 rounded-full shadow-neu-inset transition-colors"
          :class="info.signupOpen ? 'bg-sage' : 'grain'"
          @click="toggle"
        >
          <span class="absolute top-1 left-1 size-7 rounded-full bg-milk shadow-neu-raised-sm transition-transform" :class="info.signupOpen && 'translate-x-7'" />
        </button>
      </div>
      <p v-if="info.configOpen" class="text-base italic text-muted">Serveri config.php avab liitumise praegu ka ise (signup_open).</p>
      <p class="text-base italic text-muted">
        Avatuna saab igaüks konto teha, kuni 30 uut päevas. Iseseisvalt liitunu ei saa esimesed 3 päeva võõrastele kutseid saata, ja Korilus saadab
        kokku kõige rohkem 60 kirja tunnis. Kõik jagatu jääb gruppidesse, avalikku sisu pole.
      </p>
      <details class="grain card text-lg">
        <summary class="cursor-pointer text-xl">Uusimad kontod</summary>
        <ul class="mt-3 flex flex-col">
          <li v-for="u in info.newest" :key="u.id" class="flex items-center gap-3 border-b border-sage/12 py-2 last:border-0">
            <span class="flex min-w-0 flex-1 flex-col">
              <span class="truncate">{{ u.name || u.email }}</span>
              <span class="truncate text-base text-muted">{{ u.email }} · {{ date(u.created) }}{{ u.invited ? ' · kutsega' : '' }}</span>
            </span>
            <button v-if="!u.isAdmin" type="button" class="p-2 text-danger" :aria-label="`Kustuta ${u.email}`" @click="remove(u)">
              <AppIcon name="trash" :size="20" />
            </button>
          </li>
        </ul>
      </details>
    </template>
    <p v-else-if="!error" class="italic text-muted">Laadin…</p>
  </section>
</template>
