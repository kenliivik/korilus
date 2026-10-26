<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import PageHeader from '../components/PageHeader.vue'
import RecipeCard from '../components/RecipeCard.vue'
import AppIcon from '../components/AppIcon.vue'
import { produce } from '../data/berries.js'
import { allRecipes, recipeTypes } from '../data/recipes.js'
import { store } from '../data/store.js'

// Ken's recipes, the user's own ones and the ones their groups share. Filters: one berry, one type, the favourites,
// or only the user's own recipe book (own + shared). `?mari=` comes from a berry page.
const recipes = allRecipes
const berry = ref(useRoute().query.mari ?? '')
const type = ref('')
const saved = ref(false)
const own = ref(false)
const ownCount = computed(() => store.myRecipes.length + store.sharedRecipes.length)

// Offer only berries and types that actually have recipes.
const berries = computed(() => produce.filter((p) => recipes.value.some((r) => r.berries.includes(p.id))))
const types = computed(() => Object.keys(recipeTypes).filter((t) => recipes.value.some((r) => r.type === t)))
const shown = computed(() =>
  recipes.value.filter(
    (r) =>
      (!own.value || store.myRecipes.includes(r) || store.sharedRecipes.includes(r)) &&
      (!berry.value || r.berries.includes(berry.value)) &&
      (!type.value || r.type === type.value) &&
      (!saved.value || store.savedRecipes.includes(r.id)),
  ),
)

const chip = (on) => (on ? 'bg-sage text-milk shadow-neu-inset' : 'grain shadow-neu-raised-sm') + ' lg:px-4 lg:py-2 lg:text-lg'
</script>

<template>
  <PageHeader title="Retseptid" />

  <p class="-mt-3 italic leading-5 text-muted lg:text-xl">Moosid, mahlad ja talvevarud.</p>

  <RouterLink to="/oma-retsept/uus" class="btn grain text-sage lg:self-start">
    <AppIcon name="plus" :size="20" /> Lisa oma retsept
  </RouterLink>

  <section v-if="recipes.length" class="flex flex-col gap-3" aria-label="Valikud">
    <div class="flex flex-wrap gap-2">
      <button type="button" :aria-pressed="!berry" class="rounded-full px-3 py-1.5" :class="chip(!berry)" @click="berry = ''">
        Kõik marjad
      </button>
      <button
        v-for="p in berries"
        :key="p.id"
        type="button"
        :aria-pressed="berry === p.id"
        class="rounded-full px-3 py-1.5"
        :class="chip(berry === p.id)"
        @click="berry = berry === p.id ? '' : p.id"
      >
        {{ p.name }}
      </button>
    </div>
    <div class="flex flex-wrap gap-2">
      <button type="button" :aria-pressed="!type && !saved && !own" class="rounded-full px-3 py-1.5" :class="chip(!type && !saved && !own)" @click="(type = ''), (saved = false), (own = false)">
        Kõik liigid
      </button>
      <button
        v-for="t in types"
        :key="t"
        type="button"
        :aria-pressed="type === t"
        class="rounded-full px-3 py-1.5"
        :class="chip(type === t)"
        @click="type = type === t ? '' : t"
      >
        {{ recipeTypes[t] }}
      </button>
      <button
        v-if="store.savedRecipes.length"
        type="button"
        :aria-pressed="saved"
        class="rounded-full px-3 py-1.5"
        :class="chip(saved)"
        @click="saved = !saved"
      >
        Lemmikud
      </button>
      <button v-if="ownCount" type="button" :aria-pressed="own" class="rounded-full px-3 py-1.5" :class="chip(own)" @click="own = !own">
        Minu retseptid
      </button>
    </div>
  </section>

  <section v-if="shown.length" :key="berry + type + saved + own" class="fade-in flex flex-col gap-3 lg:mt-16 lg:grid lg:grid-cols-3 lg:gap-x-8 lg:gap-y-20 xl:grid-cols-4">
    <RecipeCard v-for="r in shown" :key="r.id" :recipe="r" tile />
  </section>
  <p v-else class="italic text-muted">
    {{ recipes.length ? 'Selliseid retsepte ei leidnud.' : 'Retsepte veel pole. Lisa oma esimene retsept ülal.' }}
  </p>
</template>
