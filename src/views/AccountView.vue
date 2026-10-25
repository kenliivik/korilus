<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppIcon from '../components/AppIcon.vue'
import PageHeader from '../components/PageHeader.vue'
import { store } from '../data/store.js'
import { account, requestCode, verifyCode, logout, logoutAll, deleteAccount, saveName, groupAction, downloadData } from '../data/account.js'
import { ask } from '../data/confirm.js'

const router = useRouter()
const messages = {
  not_invited: 'Korilusse saab praegu ainult kutsega. Küsi kutset kelleltki, kes seda juba kasutab.',
  too_many: 'Liiga palju katseid. Oota paar minutit ja proovi uuesti.',
  wrong_code: 'Kood ei sobi. Proovi uuesti.',
  expired: 'Kood on aegunud või sisestati liiga mitu korda valesti. Küsi uus kood.',
  mail: 'Kirja saatmine ei õnnestunud. Proovi hiljem uuesti.',
  email: 'See e-posti aadress ei tundu õige.',
}
const say = (e) => messages[e.message] ?? 'Ei saanud ühendust. Kas internet töötab?'

const email = ref('')
const code = ref('')
const step = ref('email')
const busy = ref(false)
const error = ref('')
const note = ref('')

// Runs fn, showing a spinner state and any error under the form.
async function run(fn) {
  busy.value = true
  error.value = note.value = ''
  try {
    await fn()
  } catch (e) {
    error.value = say(e)
  } finally {
    busy.value = false
  }
}

const sendCode = () => run(async () => {
  await requestCode(email.value)
  step.value = 'code'
  code.value = ''
})
const login = () => run(() => verifyCode(email.value, code.value))

const name = ref('')
watch(() => account.user?.name, (n) => (name.value = n ?? ''), { immediate: true })
const rename = () => run(() => saveName(name.value))

const friend = ref('')
const invite = () => run(async () => {
  await groupAction({ action: 'invite', email: friend.value })
  note.value = `Kutse läks teele: ${friend.value}.`
  friend.value = ''
})

const answer = (action, groupId) => run(() => groupAction({ action, groupId }))

const download = () => run(downloadData)
const signOut = () => run(async () => {
  await logout()
  step.value = 'email'
})
const signOutAll = async () =>
  (await ask('Kas logid välja kõigis seadmetes? Ka kadunud telefon ei pääse siis enam su kontole.', 'Logi välja')) &&
  run(async () => {
    await logoutAll()
    step.value = 'email'
  })
const remove = async () =>
  (await ask('Kas kustutad konto? Serverist kaovad su konto, kohad ja grupid, mille sa lõid. Selles telefonis jäävad kohad alles.')) &&
  run(async () => {
    await deleteAccount()
    router.replace('/seaded')
  })

const time = (ms) => new Date(ms).toLocaleTimeString('et-EE', { hour: '2-digit', minute: '2-digit' })
</script>

<template>
  <PageHeader title="Konto" back="/seaded" />

  <template v-if="!account.user">
    <p class="text-lg">
      Logi sisse, siis jäävad su marjakohad alles ka uut telefoni võttes ja saad neid perega jagada. Ilma kontota töötab kõik
      edasi, lihtsalt ainult selles telefonis.
    </p>

    <form v-if="step === 'email'" class="flex flex-col gap-4" @submit.prevent="sendCode">
      <label class="flex flex-col gap-2">
        <span class="text-xl">E-post</span>
        <input v-model.trim="email" type="email" required autocomplete="email" class="field" placeholder="nimi@näide.ee" />
      </label>
      <button type="submit" class="btn bg-sage text-milk" :disabled="busy">{{ busy ? 'Saadan…' : 'Saada kood' }}</button>
    </form>

    <form v-else class="flex flex-col gap-4" @submit.prevent="login">
      <p class="text-lg">Saatsime aadressile <b class="font-normal underline">{{ email }}</b> kuuekohalise koodi. See kehtib 10 minutit.</p>
      <label class="flex flex-col gap-2">
        <span class="text-xl">Kood</span>
        <input
          v-model="code"
          required
          inputmode="numeric"
          autocomplete="one-time-code"
          pattern="\s*\d{3}\s*\d{3}\s*"
          maxlength="7"
          class="field text-center text-3xl tracking-[0.4em]"
          placeholder="••••••"
        />
      </label>
      <button type="submit" class="btn bg-sage text-milk" :disabled="busy">{{ busy ? 'Kontrollin…' : 'Logi sisse' }}</button>
      <div class="flex justify-between text-base text-muted">
        <button type="button" class="underline underline-offset-4" @click="step = 'email'">Muuda aadressi</button>
        <button type="button" class="underline underline-offset-4" :disabled="busy" @click="sendCode">Saada uus kood</button>
      </div>
    </form>
    <p v-if="error" role="alert" class="text-lg text-danger">{{ error }}</p>
  </template>

  <template v-else>
    <section class="flex flex-col gap-3">
      <form class="grain card flex flex-col gap-3" @submit.prevent="rename">
        <label class="flex flex-col gap-2">
          <span class="text-muted">Sinu nimi, nagu grupikaaslased seda näevad</span>
          <span class="flex gap-2">
            <input v-model.trim="name" required maxlength="60" class="field" />
            <button v-if="name !== account.user.name" type="submit" class="btn bg-sage px-4 text-milk" :disabled="busy">Salvesta</button>
          </span>
        </label>
        <p class="text-lg">{{ account.user.email }}</p>
        <p class="text-base italic text-muted">
          {{ account.syncing ? 'Salvestan kontole…' : account.error ? 'Kontole salvestamine ei õnnestunud, proovin hiljem uuesti.'
            : account.syncedAt ? `Kohad on kontol, viimati kell ${time(account.syncedAt)}.` : 'Kohad pole veel kontole jõudnud.' }}
        </p>
      </form>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Grupid</h2>
      <div v-for="i in account.invitations" :key="i.groupId" class="grain card flex flex-col gap-3">
        <p class="text-lg">{{ i.from || 'Keegi' }} kutsus sind gruppi „{{ i.name }}”.</p>
        <span class="flex gap-2">
          <button type="button" class="btn bg-sage px-4 text-milk" :disabled="busy" @click="answer('accept', i.groupId)">Liitu</button>
          <button type="button" class="btn grain px-4 text-sage" :disabled="busy" @click="answer('decline', i.groupId)">Loobu</button>
        </span>
      </div>
      <RouterLink
        v-for="g in account.groups"
        :key="g.id"
        :to="`/grupid/${g.id}`"
        class="grain card flex items-center justify-between press active:shadow-neu-inset"
      >
        <span class="flex flex-col">
          <span class="text-xl">{{ g.name }}</span>
          <span class="text-muted">{{ g.members.length }} {{ g.members.length === 1 ? 'liige' : 'liiget' }}{{ g.role === 'owner' ? ' · sinu grupp' : '' }}</span>
        </span>
        <AppIcon name="arrow" :size="20" />
      </RouterLink>
      <p v-if="!account.groups.length" class="italic text-muted">
        Loo grupp, näiteks „Pere”, ja kutsu sinna oma inimesed. Iga koha juures saad valida, kas seda näed ainult sina või ka grupp.
      </p>
      <RouterLink to="/grupid" class="btn grain text-sage"><AppIcon name="plus" :size="20" /> Uus grupp</RouterLink>
    </section>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Kutsu sõber</h2>
      <form class="flex gap-2" @submit.prevent="invite">
        <input v-model.trim="friend" type="email" required class="field" placeholder="sõbra e-post" />
        <button type="submit" class="btn bg-sage px-4 text-milk" :disabled="busy">Kutsu</button>
      </form>
    </section>

    <p v-if="error" role="alert" class="text-lg text-danger">{{ error }}</p>
    <p v-if="note" role="status" class="text-lg">{{ note }}</p>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Minu andmed</h2>
      <RouterLink v-if="account.user.isAdmin" to="/admin" class="btn grain text-sage">Halda sisu</RouterLink>
      <button type="button" class="btn grain text-sage" :disabled="busy" @click="download">Laadi oma andmed alla</button>
      <button type="button" class="btn grain text-sage" :disabled="busy" @click="signOut">Logi välja</button>
      <button type="button" class="btn grain text-sage" :disabled="busy" @click="signOutAll">Logi välja kõigis seadmetes</button>
      <button type="button" class="btn grain text-danger" :disabled="busy" @click="remove">
        <AppIcon name="trash" :size="20" /> Kustuta konto
      </button>
      <p class="text-base italic text-muted">Kui välja logid, jäävad su {{ store.locations.length }} kohta sellesse telefoni alles.</p>
    </section>
  </template>

  <details class="grain card text-lg">
    <summary class="cursor-pointer text-xl">Privaatsus</summary>
    <div class="mt-3 flex flex-col gap-3">
      <p>
        Korilust haldab Ken Liivik eraviisiliselt, sõpradele ja tasuta. Andmete kohta saad küsida
        <RouterLink to="/tagasiside" class="underline">tagasiside kaudu</RouterLink>.
      </p>
      <p>Ilma kontota on kõik su andmed ainult selles telefonis.</p>
      <p>
        Kui logid sisse, hoiame Koriluse serveris (Veebimajutus, Eestis) su e-posti aadressi, nime, marjakohti koos
        märkmetega ja gruppe, kuhu kuulud. Nii jäävad kohad alles ka uues telefonis ja saad neid jagada.
      </p>
      <p>
        Grupiga jagatud kohti ja su nime näevad selle grupi liikmed. Kohti, mis on ainult sinu, ei näe keegi teine. Andmeid
        kasutame ainult selleks, et Korilus töötaks: neid ei müüda ega anta kellelegi edasi. Reklaame ega jälgimist pole.
      </p>
      <p>
        Sisselogimiseks saadame e-postiga ühekordse koodi. Arvamiskatsete piiramiseks hoiame koodi ja su IP-aadressi
        ööpäeva. Et sa ei peaks iga kord uuesti sisse logima, kasutame üht vajalikku küpsist.
      </p>
      <p>
        Kui kutsud sõbra, hoiame tema e-posti aadressi, kuni ta kutse vastu võtab, kuid kõige rohkem 90 päeva. Kui ta
        liitub, jätame meelde, et te tunnete teineteist, et saaksite üksteist gruppi kutsuda e-posti sisestamata. Tagasiside
        hoiame, kuni oleme selle läbi vaadanud, ja selle saatja IP-aadressi 30 päeva.
      </p>
      <p>
        Meeldetuletuste jaoks hoiame serveris su telefoni teavitusaadressi (selle annab Google, Apple või Mozilla) ja
        valitud marjad, ilma nime ja e-postita. Kui eemaldad kõik meeldetuletused, kustub see.
      </p>
      <p>
        Kaart tuleb OpenStreetMapist, nii et kaarti vaadates näeb OpenStreetMap Foundation (Suurbritannia) su IP-aadressi.
        Veebimajutus hoiab tavapäraseid serverilogisid.
      </p>
      <p>
        Saad oma andmed igal ajal alla laadida ja konto kustutada. Siis kaovad serverist su konto, kohad ja su loodud
        grupid. Kui oleme midagi valesti teinud, võid pöörduda ka Andmekaitse Inspektsiooni poole (aki.ee).
      </p>
    </div>
  </details>
</template>
