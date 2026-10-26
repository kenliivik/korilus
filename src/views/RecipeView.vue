<script setup>
import { computed, nextTick, ref } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import AppIcon from '../components/AppIcon.vue'
import { contentState, findProduce } from '../data/berries.js'
import {
  findRecipe, isMine, recipeTypes, toggleSavedRecipe, ingKey, stepKey, notesOn, orphanNotes, setNoteLine, setNoteGeneral, setNoteGroup,
} from '../data/recipes.js'
import { store } from '../data/store.js'
import { account } from '../data/account.js'

const props = defineProps({ id: { type: String, required: true } })

const r = computed(() => findRecipe(props.id))
const berries = computed(() => r.value.berries.map(findProduce).filter(Boolean))
const saved = computed(() => store.savedRecipes.includes(props.id))
// The user's own recipe is edited directly, so it gets no handwritten notes.
const mine = computed(() => isMine(r.value))
const sharedWith = (groupId) => account.groups.find((g) => g.id === groupId)?.name

// The user's own notes, written onto the recipe in handwriting. Ken's text never changes.
const note = computed(() => store.notes[props.id])
const hasNotes = computed(() => !!(note.value?.general || Object.keys(note.value?.lines ?? {}).length))
const orphans = computed(() => orphanNotes(r.value, note.value?.lines))
const sharedGeneral = computed(() => store.sharedNotes.filter((n) => n.recipe === props.id && n.general))
const groupName = computed(() => account.groups.find((g) => g.id === note.value?.groupId)?.name)
const noteGroup = computed({ get: () => note.value?.groupId ?? null, set: (g) => setNoteGroup(props.id, g) })
const editing = ref(false)
const open = ref(null) // the line being written on
// A note that starts with a number is a new amount, so the recipe's amount is crossed out.
const newAmount = (key) => /^\d/.test(store.notes[props.id]?.lines[key]?.trim() ?? '')

async function write(key) {
  open.value = open.value === key ? null : key
  await nextTick()
  document.getElementById(`note-${key}`)?.focus()
}
</script>

<template>
  <template v-if="r">
    <PageHeader :title="r.title" back="/retseptid" />

    <!-- Desktop: a plate with the picture and facts on the left, the method on the right. On phones the wrappers dissolve. -->
    <div class="contents lg:grid lg:grid-cols-[20rem_1fr] lg:items-start lg:gap-12 xl:grid-cols-[24rem_1fr]">
    <aside class="contents lg:sticky lg:top-12 lg:flex lg:flex-col lg:gap-6">
    <div v-if="!r.image && berries.length" class="grain card mt-20 hidden justify-center lg:flex">
      <BerryGlyph :name="berries[0].id" :image="berries[0].image" :size="256" class="-mt-24" />
    </div>
    <img v-if="r.image" :src="r.image" alt="" class="aspect-[4/3] w-full rounded-card object-cover shadow-neu-raised" />

    <dl class="grain card grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-lg lg:text-xl">
      <dt class="text-muted">Liik</dt>
      <dd>{{ recipeTypes[r.type] ?? r.type }}</dd>
      <template v-if="r.time">
        <dt class="text-muted">Aeg</dt>
        <dd>{{ r.time }}</dd>
      </template>
      <template v-if="r.yield">
        <dt class="text-muted">Kogus</dt>
        <dd>{{ r.yield }}</dd>
      </template>
      <template v-if="mine">
        <dt class="text-muted">Näeb</dt>
        <dd>{{ sharedWith(r.groupId) ? `Sina ja grupp „${sharedWith(r.groupId)}”` : 'Ainult sina' }}</dd>
      </template>
      <template v-else-if="r.owner">
        <dt class="text-muted">Lisas</dt>
        <dd>{{ r.owner }}</dd>
      </template>
    </dl>

    <section v-if="berries.length" class="flex flex-wrap gap-2" aria-label="Marjad">
      <RouterLink
        v-for="p in berries"
        :key="p.id"
        :to="`/mari/${p.id}`"
        class="grain flex items-center gap-2 rounded-full py-1 pr-4 pl-1 shadow-neu-raised-sm active:shadow-neu-inset"
      >
        <BerryGlyph :name="p.id" :size="36" />
        {{ p.name }}
      </RouterLink>
    </section>

    <RouterLink v-if="mine" :to="`/oma-retsept/${r.id}`" class="btn bg-sage text-milk">
      <AppIcon name="edit" :size="18" /> Muuda retsepti
    </RouterLink>
    <button
      v-else
      type="button"
      class="btn"
      :class="editing ? 'bg-sage text-milk max-lg:hidden' : 'grain text-sage'"
      :aria-pressed="editing"
      @click="(editing = !editing), (open = null)"
    >
      <AppIcon v-if="!editing" name="edit" :size="18" />
      {{ editing ? 'Valmis' : hasNotes ? 'Muuda märkmeid' : 'Lisa märkmeid' }}
    </button>

    <button
      v-if="!mine"
      type="button"
      class="btn order-1 lg:order-none"
      :class="saved ? 'grain text-sage' : 'bg-sage text-milk'"
      :aria-pressed="saved"
      @click="toggleSavedRecipe(r.id)"
    >
      <Transition name="swap" mode="out-in">
        <span :key="saved">{{ saved ? 'Lemmikutes ✓' : 'Lisa lemmikuks' }}</span>
      </Transition>
    </button>
    </aside>

    <div class="contents lg:flex lg:flex-col lg:gap-8">
    <section v-if="r.ingredients?.length" class="flex flex-col gap-3">
      <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Koostisosad</h2>
      <ul class="grain card grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-lg lg:gap-y-2 lg:p-6 lg:text-xl">
        <li v-for="(ing, i) in r.ingredients" :key="i" class="col-span-2 grid grid-cols-subgrid">
          <span class="text-right text-muted" :class="!editing && newAmount(ingKey(ing)) && 'line-through decoration-leaf'">{{ ing.amount }}</span>
          <span class="flex flex-wrap items-baseline gap-x-3">
            <span>{{ ing.item }}</span>
            <template v-if="!editing">
              <span v-for="(n, j) in notesOn(r, ingKey(ing))" :key="j" class="hand-note" :class="!n.mine && 'text-muted'">
                {{ n.text }}<span v-if="n.owner" class="ml-2 font-serif text-base not-italic">({{ n.owner }})</span>
              </span>
            </template>
            <button v-else type="button" class="note-pen" :aria-label="`Märge: ${ing.item}`" :aria-expanded="open === ingKey(ing)" @click="write(ingKey(ing))">
              <span v-if="open !== ingKey(ing) && store.notes[r.id]?.lines[ingKey(ing)]" class="hand-note">{{ store.notes[r.id].lines[ingKey(ing)] }}</span>
              <AppIcon name="edit" :size="16" />
            </button>
          </span>
          <textarea
            v-if="editing && open === ingKey(ing)"
            :id="`note-${ingKey(ing)}`"
            :value="store.notes[r.id]?.lines[ingKey(ing)] ?? ''"
            rows="1"
            maxlength="1000"
            class="note-field col-start-2 mt-1 mb-2"
            placeholder="nt. 500 g"
            @input="setNoteLine(r.id, ingKey(ing), $event.target.value)"
            @keydown.enter.prevent="open = null"
          />
        </li>
      </ul>
    </section>

    <section v-if="r.steps?.length" class="flex flex-col gap-3">
      <h2 class="ruled text-2xl leading-7 lg:text-3xl lg:leading-9">Valmistamine</h2>
      <ol class="grain card flex flex-col gap-3 text-lg lg:gap-4 lg:p-6 lg:text-xl">
        <li v-for="(step, i) in r.steps" :key="i" class="flex gap-3">
          <span class="font-hand text-xl leading-7 text-leaf">{{ i + 1 }}.</span>
          <span class="flex flex-1 flex-col">
            <span class="flex items-baseline gap-3">
              <span class="flex-1">{{ step }}</span>
              <button v-if="editing" type="button" class="note-pen" :aria-label="`Märge sammule ${i + 1}`" :aria-expanded="open === stepKey(step)" @click="write(stepKey(step))">
                <AppIcon name="edit" :size="16" />
              </button>
            </span>
            <textarea
              v-if="editing && open === stepKey(step)"
              :id="`note-${stepKey(step)}`"
              :value="store.notes[r.id]?.lines[stepKey(step)] ?? ''"
              rows="2"
              maxlength="1000"
              class="note-field mt-1"
              placeholder="Mida sa teed teisiti?"
              @input="setNoteLine(r.id, stepKey(step), $event.target.value)"
            />
            <span v-else v-for="(n, j) in notesOn(r, stepKey(step))" :key="j" class="hand-note mt-1" :class="!n.mine && 'text-muted'">
              {{ n.text }}<span v-if="n.owner" class="ml-2 font-serif text-base">({{ n.owner }})</span>
            </span>
          </span>
        </li>
      </ol>
    </section>

    <section v-if="editing" class="flex flex-col gap-4 px-2" aria-label="Minu märkmed">
      <label class="flex flex-col gap-1">
        <span class="text-lg text-muted">Üldine märge</span>
        <textarea
          :value="note?.general ?? ''"
          rows="3"
          maxlength="4000"
          class="note-field"
          placeholder="nt. lisasin sidrunikoort"
          @input="setNoteGeneral(r.id, $event.target.value)"
        />
      </label>
      <label v-if="account.user && account.groups.length" class="flex flex-col gap-1">
        <span class="text-lg text-muted">Kes märkmeid näeb</span>
        <select v-model="noteGroup" class="field">
          <option :value="null">Ainult mina</option>
          <option v-for="g in account.groups" :key="g.id" :value="g.id">Mina ja grupp „{{ g.name }}”</option>
        </select>
      </label>
    </section>
    <section v-else-if="note?.general || orphans.length || sharedGeneral.length" class="flex flex-col gap-3 px-2" aria-label="Märkmed">
      <div v-if="note?.general || orphans.length">
        <p v-for="(t, i) in [note.general, ...orphans].filter(Boolean)" :key="i" class="hand-note whitespace-pre-line">{{ t }}</p>
      </div>
      <p v-for="(n, i) in sharedGeneral" :key="n.owner + i" class="hand-note whitespace-pre-line text-muted">
        {{ n.general }}<span class="ml-2 font-serif text-base">({{ n.owner }})</span>
      </p>
    </section>
    <p v-if="!editing && hasNotes" class="-mt-2 px-2 text-base text-muted">
      Sinu märkmed käsikirjas · {{ groupName ? `näeb ka grupp „${groupName}”` : 'nähtavad ainult sulle' }}
    </p>

    <section v-if="r.tip" class="grain card flex flex-col gap-1">
      <h2 class="font-hand text-xl text-muted">Hea nõu</h2>
      <p class="text-lg italic lg:text-xl">{{ r.tip }}</p>
    </section>
    </div>
    </div>

    <!-- Phones: while writing notes, "Valmis" floats above the menu, so it's at hand wherever the pencil is
         (with room below the page so it never covers the last lines). -->
    <div v-if="editing" class="h-16 lg:hidden" />
    <button
      v-if="editing"
      type="button"
      class="btn fixed inset-x-5 bottom-[calc(max(1.5rem,env(safe-area-inset-bottom))+5rem)] z-10 mx-auto max-w-[390px] bg-sage text-milk shadow-neu-raised lg:hidden"
      @click="(editing = false), (open = null)"
    >
      Valmis
    </button>
  </template>

  <template v-else-if="!contentState.ready">
    <PageHeader title="" back="/retseptid" />
    <p class="italic text-muted">Laen retsepti…</p>
  </template>
  <PageHeader v-else title="Sellist retsepti ei leitud" back="/retseptid" />
</template>
