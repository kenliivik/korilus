import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import router from './router.js'
import { hideLoader } from './loader.js'

createApp(App).use(router).mount('#app')
router.isReady().then(hideLoader)
