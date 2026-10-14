<script setup>
import { ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import { account, api } from '../data/account.js'

const messages = {
  too_many: 'Oled täna juba palju kirjutanud. Proovi hiljem uuesti.',
  email: 'See e-posti aadress ei tundu õige.',
  message: 'Kirjuta paar sõna.',
}

const message = ref('')
const email = ref('')
const website = ref('') // hidden from people; bots fill it
const busy = ref(false)
const error = ref('')
const sent = ref(false)

async function send() {
  busy.value = true
  error.value = ''
  try {
    await api('feedback.php', { message: message.value, email: email.value, website: website.value })
    sent.value = true
    message.value = ''
  } catch (e) {
    error.value = messages[e.message] ?? 'Saatmine ei õnnestunud. Kas internet töötab?'
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <PageHeader title="Tagasiside" back="/seaded" />

  <template v-if="sent">
    <p class="grain card text-lg">Aitäh! Sinu sõnum jõudis kohale.</p>
    <button type="button" class="btn grain text-sage" @click="sent = false">Kirjuta veel</button>
  </template>

  <form v-else class="flex flex-col gap-4" @submit.prevent="send">
    <p class="text-lg">Kas sul on mõni idee, mis Korilusest puudu jääb, või midagi ei tööta? Kirjuta siia.</p>
    <label class="flex flex-col gap-2">
      <span class="text-xl">Sõnum</span>
      <textarea v-model="message" required minlength="3" maxlength="4000" rows="6" class="field" />
    </label>
    <label v-if="!account.user" class="flex flex-col gap-2">
      <span class="text-xl">E-post <span class="text-base text-muted">(kui soovid vastust)</span></span>
      <input v-model.trim="email" type="email" autocomplete="email" class="field" placeholder="nimi@näide.ee" />
    </label>
    <p v-else class="text-base italic text-muted">Vastame aadressile {{ account.user.email }}.</p>
    <input v-model="website" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true" class="hidden" />
    <p class="text-base italic text-muted">
      Rämpsposti tõkestamiseks hoiame su IP-aadressi 30 päeva. Vaata ka <RouterLink to="/konto" class="underline">privaatsust</RouterLink>.
    </p>
    <button type="submit" class="btn bg-sage text-milk" :disabled="busy">{{ busy ? 'Saadan…' : 'Saada' }}</button>
    <p v-if="error" role="alert" class="text-lg text-danger">{{ error }}</p>
  </form>
</template>
