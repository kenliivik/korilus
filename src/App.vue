<script setup>
import BottomNav from './components/BottomNav.vue'
import './data/account.js' // starts spot sync when logged in
</script>

<template>
  <div class="paper min-h-dvh">
    <!-- Desktop (lg+): the nav becomes a left rail; pages marked wide in the router get the full width. -->
    <main
      class="mx-auto flex max-w-[430px] flex-col gap-6 px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-32 lg:pb-12"
      :class="{ 'lg:max-w-6xl lg:pt-12 lg:pr-12 lg:pl-36': $route.meta.wide }"
    >
      <RouterView />
    </main>
    <BottomNav :active="$route.meta.tab" />
  </div>
</template>
