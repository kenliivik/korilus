<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import AppIcon from '../components/AppIcon.vue'
import { store } from '../data/store.js'
import { account, groupAction } from '../data/account.js'

// One group (members, invites, leave or delete), or with no id the "new group" form.
const props = defineProps({ id: { type: String, default: '' } })
const router = useRouter()

const group = computed(() => account.groups.find((g) => String(g.id) === props.id))
const owner = computed(() => group.value?.role === 'owner')
const spots = computed(() => [...store.locations, ...store.shared].filter((l) => l.groupId === group.value?.id).length)

const name = ref('')
const email = ref('')
const busy = ref(false)
const error = ref('')
const note = ref('')

async function act(body, done) {
  busy.value = true
  error.value = note.value = ''
  try {
    const groups = await groupAction({ groupId: group.value?.id, ...body })
    done?.(groups)
  } catch (e) {
    error.value = { email: 'See e-posti aadress ei tundu õige.', too_many: 'Täna on liiga palju kutseid saadetud. Proovi homme uuesti.' }[e.message] ?? 'Ei saanud ühendust. Kas internet töötab?'
  } finally {
    busy.value = false
  }
}

const create = () => act({ action: 'create', name: name.value }, (groups) =>
  router.replace(`/grupid/${Math.max(...groups.filter((g) => g.role === 'owner').map((g) => g.id))}`))
const invite = () => act({ action: 'invite', email: email.value }, () => {
  note.value = `Kutse läks teele: ${email.value}.`
  email.value = ''
})
const removeMember = (m) => confirm(`Kas eemaldad ${m.name} grupist?`) && act({ action: 'remove', userId: m.id })
const uninvite = (e) => act({ action: 'uninvite', email: e })
const leave = () => confirm(`Kas lahkud grupist „${group.value.name}”? Kohad, mida sa seal jagasid, jäävad jälle ainult sulle.`) &&
  act({ action: 'leave' }, () => router.replace('/konto'))
const remove = () => confirm(`Kas kustutad grupi „${group.value.name}”? Kõik grupiga jagatud kohad jäävad jälle ainult nende omanikele.`) &&
  act({ action: 'delete' }, () => router.replace('/konto'))
</script>

<template>
  <template v-if="!id && account.user">
    <PageHeader title="Uus grupp" back="/konto" />
    <form class="flex flex-col gap-4" @submit.prevent="create">
      <label class="flex flex-col gap-2">
        <span class="text-xl">Grupi nimi</span>
        <input v-model.trim="name" required maxlength="40" class="field" placeholder="nt. Pere" />
      </label>
      <p class="italic text-muted">Kui grupp on loodud, saad sinna inimesi kutsuda.</p>
      <button type="submit" class="btn bg-sage text-milk" :disabled="busy">Loo grupp</button>
    </form>
  </template>

  <template v-else-if="group">
    <PageHeader :title="group.name" back="/konto" />
    <p class="text-lg text-muted">Grupiga jagatud kohti: {{ spots }}. Koha jagamiseks ava see ja vajuta „Muuda”.</p>

    <section class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Liikmed</h2>
      <ul class="grain card flex flex-col gap-3 text-lg">
        <li v-for="m in group.members" :key="m.id" class="flex items-center justify-between gap-3">
          <span class="flex min-w-0 flex-col">
            <span>{{ m.name }}<span v-if="m.role === 'owner'" class="font-hand text-muted"> · looja</span></span>
            <span class="truncate text-base text-muted">{{ m.email }}</span>
          </span>
          <button
            v-if="owner && m.id !== account.user.id"
            type="button"
            class="text-base text-muted underline underline-offset-4"
            :disabled="busy"
            @click="removeMember(m)"
          >
            Eemalda
          </button>
        </li>
        <li v-for="e in group.invites" :key="e" class="flex items-center justify-between gap-3 text-muted">
          <span class="flex min-w-0 flex-col"><span class="truncate">{{ e }}</span><span class="text-base italic">kutse saadetud</span></span>
          <button type="button" class="text-base underline underline-offset-4" :disabled="busy" @click="uninvite(e)">Tühista</button>
        </li>
      </ul>
    </section>

    <section v-if="owner" class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7">Kutsu keegi</h2>
      <form class="flex gap-2" @submit.prevent="invite">
        <input v-model.trim="email" type="email" required class="field" placeholder="e-post" />
        <button type="submit" class="btn bg-sage px-4 text-milk" :disabled="busy">Kutsu</button>
      </form>
      <p class="text-base italic text-muted">Kes Korilust juba kasutab, lisatakse kohe. Teistele saadame kutse e-postiga.</p>
    </section>

    <p v-if="error" role="alert" class="text-lg text-[#9c3a3a]">{{ error }}</p>
    <p v-if="note" role="status" class="text-lg">{{ note }}</p>

    <button v-if="owner" type="button" class="btn grain text-[#9c3a3a]" :disabled="busy" @click="remove">
      <AppIcon name="trash" :size="20" /> Kustuta grupp
    </button>
    <button v-else type="button" class="btn grain text-[#9c3a3a]" :disabled="busy" @click="leave">Lahku grupist</button>
  </template>

  <PageHeader v-else title="Seda gruppi ei leia" back="/konto" />
</template>
