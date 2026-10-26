<script setup>
import { useRouter } from 'vue-router'
import AppIcon from './AppIcon.vue'

const props = defineProps({
  title: { type: String, required: true },
  back: { type: String, default: '' }, // where "Tagasi" goes when the screen was opened directly
})

const router = useRouter()
const goBack = () => (history.state?.back ? router.back() : router.push(props.back))
</script>

<template>
  <header class="flex items-center gap-4">
    <button
      v-if="back"
      type="button"
      aria-label="Tagasi"
      class="grain grid size-11 shrink-0 place-items-center rounded-full text-sage shadow-neu-raised-sm press active:shadow-neu-inset"
      @click="goBack"
    >
      <AppIcon name="back" :size="20" />
    </button>
    <h1 class="text-[32px] leading-9 text-sage" :class="$route.meta.wide && 'lg:text-6xl lg:leading-tight'">{{ title }}</h1>
    <slot />
  </header>
</template>
