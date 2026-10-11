<script setup>
import { ref } from 'vue'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import { loadAllRecipes, recipeTypes } from '../../data/recipes.js'

// Every recipe, hidden ones too. Editing happens in RecipeEditor.
const list = ref(null)
loadAllRecipes().then((r) => (list.value = r))
</script>

<template>
  <PageHeader title="Retseptid" back="/admin" />

  <RouterLink to="/admin/retseptid/uus" class="btn bg-sage text-milk"><AppIcon name="plus" :size="20" /> Lisa retsept</RouterLink>

  <p v-if="!list" class="italic text-muted">Laadin…</p>
  <ul v-else-if="list.length" class="flex flex-col gap-3">
    <li v-for="r in list" :key="r.id">
      <RouterLink
        :to="`/admin/retseptid/${r.id}`"
        class="grain flex items-center gap-3 rounded-card p-4 shadow-neu-raised active:shadow-neu-inset"
        :class="{ 'opacity-60': r.hidden }"
      >
        <span class="flex flex-1 flex-col">
          <span class="text-xl leading-6">{{ r.title }}</span>
          <span class="font-hand text-sm text-muted">
            {{ recipeTypes[r.type] }}{{ r.hidden ? ' · peidetud' : '' }}
          </span>
        </span>
        <AppIcon name="edit" :size="20" />
      </RouterLink>
    </li>
  </ul>
  <p v-else class="italic text-muted">Retsepte veel pole.</p>
</template>
