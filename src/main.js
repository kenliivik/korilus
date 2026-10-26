import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router.js'

// After a deploy, an app left open still asks for the old page files (script or style), which are gone: load the new version instead of doing nothing.
// Not again within 10 s, so a file that is really missing can't reload forever, but every later deploy is caught too.
router.onError((e, to) => {
  if (!/dynamically imported module|Importing a module script failed|Unable to preload CSS/.test(e?.message)) return
  try {
    if (Date.now() - Number(sessionStorage.getItem('korilus-reloaded')) < 10000) return
    sessionStorage.setItem('korilus-reloaded', Date.now())
    location.assign(to.fullPath)
  } catch { /* no storage, so no reload: closing and opening the app still works */ }
})

createApp(App).use(router).mount('#app')
