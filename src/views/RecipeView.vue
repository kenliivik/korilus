<script setup>
import { computed } from 'vue'
import PageHeader from '../components/PageHeader.vue'
import BerryGlyph from '../components/BerryGlyph.vue'
import { findProduce } from '../data/berries.js'
import { findRecipe, recipeTypes, toggleSavedRecipe } from '../data/recipes.js'
import { store } from '../data/store.js'

const props = defineProps({ id: { type: String, required: true } })

const r = computed(() => findRecipe(props.id))
const berries = computed(() => r.value.berries.map(findProduce).filter(Boolean))
const saved = computed(() => store.savedRecipes.includes(props.id))
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

    <button
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
      <h2 class="text-2xl leading-7 lg:text-3xl lg:leading-9">Koostisosad</h2>
      <ul class="grain card grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-lg lg:gap-y-2 lg:p-6 lg:text-xl">
        <li v-for="(ing, i) in r.ingredients" :key="i" class="col-span-2 grid grid-cols-subgrid">
          <span class="text-right text-muted">{{ ing.amount }}</span>
          <span>{{ ing.item }}</span>
        </li>
      </ul>
    </section>

    <section v-if="r.steps?.length" class="flex flex-col gap-3">
      <h2 class="text-2xl leading-7 lg:text-3xl lg:leading-9">Valmistamine</h2>
      <ol class="grain card flex flex-col gap-3 text-lg lg:gap-4 lg:p-6 lg:text-xl">
        <li v-for="(step, i) in r.steps" :key="i" class="flex gap-3">
          <span class="font-hand text-xl leading-7 text-leaf">{{ i + 1 }}.</span>
          <span>{{ step }}</span>
        </li>
      </ol>
    </section>

    <section v-if="r.tip" class="grain card flex flex-col gap-1">
      <h2 class="font-hand text-xl text-muted">Hea nõu</h2>
      <p class="text-lg italic lg:text-xl">{{ r.tip }}</p>
    </section>
    </div>
    </div>
  </template>

  <PageHeader v-else title="Sellist retsepti ei leitud" back="/retseptid" />
</template>
