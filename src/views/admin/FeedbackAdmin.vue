<script setup>
import { ref } from 'vue'
import PageHeader from '../../components/PageHeader.vue'
import AppIcon from '../../components/AppIcon.vue'
import { api } from '../../data/account.js'

// Everything sent from Seaded → Tagasiside, newest first.
const items = ref(null)
const error = ref('')

async function load(body) {
  error.value = ''
  try {
    items.value = (await api('feedback.php', body)).items
  } catch (e) {
    items.value ??= []
    error.value = e.message === 'network' ? 'Laadimine ei õnnestunud.' : 'Logi adminina sisse.'
  }
}
load()

const date = (s) => new Date(s * 1000).toLocaleString('et-EE', { day: 'numeric', month: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' })
const remove = (f) => confirm('Kas kustutad selle sõnumi?') && load({ action: 'delete', id: f.id })
</script>

<template>
  <PageHeader title="Tagasiside" back="/admin" />

  <p v-if="!items" class="italic text-muted">Laadin…</p>
  <p v-if="error" role="alert" class="text-lg text-danger">{{ error }}</p>
  <ul v-if="items?.length" class="flex flex-col gap-3">
    <li v-for="f in items" :key="f.id" class="grain card flex flex-col gap-2" :class="{ 'opacity-60': f.done }">
      <p class="font-hand text-sm text-muted">
        {{ date(f.created) }} · {{ f.name || f.email || 'nimetu' }}
        <a v-if="f.email" :href="`mailto:${f.email}`" class="underline underline-offset-4">{{ f.name ? f.email : 'vasta' }}</a>
      </p>
      <p class="whitespace-pre-line text-lg">{{ f.message }}</p>
      <div class="flex items-center justify-between text-base text-muted">
        <button type="button" class="underline underline-offset-4" @click="load({ action: 'done', id: f.id, done: !f.done })">
          {{ f.done ? 'Märgi tegemata' : 'Märgi tehtuks' }}
        </button>
        <button type="button" aria-label="Kustuta" @click="remove(f)"><AppIcon name="trash" :size="20" /></button>
      </div>
    </li>
  </ul>
  <p v-else-if="items && !error" class="italic text-muted">Tagasisidet veel pole.</p>
</template>
