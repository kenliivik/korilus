<script setup>
import BottomNav from './components/BottomNav.vue'
import './data/account.js' // starts spot sync when logged in
</script>

<template>
  <div class="paper min-h-dvh lg:pl-64">
    <!-- Desktop (lg+): the nav becomes a left sidebar; pages marked wide in the router get the full width;
         doc pages (admin) keep their content to a readable column. -->
    <main
      class="mx-auto flex max-w-[430px] flex-col gap-6 px-5 pt-[max(1rem,env(safe-area-inset-top))] pb-32 lg:pb-12"
      :class="{ 'lg:max-w-6xl lg:px-12 lg:pt-12': $route.meta.wide, 'lg:*:max-w-3xl': $route.meta.doc }"
    >
      <RouterView />
    </main>
    <BottomNav :active="$route.meta.tab" />
  </div>
</template>
