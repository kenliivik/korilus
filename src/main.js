import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router.js'

// After a deploy, an app left open still asks for the old page files, which are gone: load the new version instead of doing nothing.
// Once per page, so a file that is really missing can't reload forever.
router.onError((e, to) => {
  if (!/dynamically imported module|Importing a module script failed/.test(e?.message)) return
  try {
    if (sessionStorage.getItem('korilus-reloaded') === to.fullPath) return
    sessionStorage.setItem('korilus-reloaded', to.fullPath)
    location.assign(to.fullPath)
  } catch { /* no storage, so no reload: closing and opening the app still works */ }
})

createApp(App).use(router).mount('#app')
