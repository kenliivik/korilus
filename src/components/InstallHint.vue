<script setup>
import { ref } from 'vue'

// One-time hint, in the browser only, to add Korilus to the home screen: it then opens like an app,
// and an iPhone can only show reminders after that. "Hiljem" hides it for good on this phone.
const KEY = 'korilus-install-hint'
const installed = matchMedia('(display-mode: standalone)').matches || navigator.standalone
const ios = /iPhone|iPad|iPod/.test(navigator.userAgent) || (/Macintosh/.test(navigator.userAgent) && navigator.maxTouchPoints > 1)
let hidden = false
try { hidden = !!localStorage.getItem(KEY) } catch { /* private mode: just show it */ }
const show = ref(!installed && !hidden)

// Android/Chrome offers its own install dialog; keep the event so the button can open it.
const prompt = ref(null)
addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault()
  prompt.value = e
})

function dismiss() {
  show.value = false
  try { localStorage.setItem(KEY, '1') } catch { /* nothing to remember it in */ }
}
async function install() {
  prompt.value.prompt()
  if ((await prompt.value.userChoice).outcome === 'accepted') dismiss()
}
</script>

<template>
  <aside v-if="show" class="grain card flex flex-col gap-3 lg:hidden" aria-label="Lisa avakuvale">
    <p class="text-lg leading-6">
      Lisa Korilus telefoni avakuvale, siis avaneb see nagu äpp ja saab sulle marjade valmimisest teada anda.
    </p>
    <p v-if="ios" class="text-base text-muted">Vajuta all Jaga-nuppu ja vali „Lisa avakuvale”.</p>
    <p v-else-if="!prompt" class="text-base text-muted">Ava brauseri menüü ja vali „Lisa avakuvale” või „Installi rakendus”.</p>
    <div class="flex gap-3">
      <button v-if="prompt" type="button" class="btn flex-1 bg-sage text-milk" @click="install">Lisa avakuvale</button>
      <button type="button" class="btn flex-1 grain text-sage" @click="dismiss">Hiljem</button>
    </div>
  </aside>
</template>
