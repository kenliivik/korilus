<script setup>
import BottomNav from './components/BottomNav.vue'
</script>

<template>
  <div class="paper min-h-dvh">
    <main class="mx-auto flex max-w-[430px] flex-col gap-6 px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-32">
      <RouterView />
    </main>
    <BottomNav :active="$route.meta.tab" />
  </div>
</template>
