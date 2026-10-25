import { reactive } from 'vue'

// "Are you sure?" as a card in the app's own look (ConfirmDialog in App.vue) instead of the browser's grey confirm() popup.
// `await ask(text, 'Kustuta')` is true when the person pressed the button, false on "Tühista" or Esc.
export const pending = reactive({ text: '', ok: '', resolve: null })

export const ask = (text, ok = 'Kustuta') => new Promise((resolve) => Object.assign(pending, { text, ok, resolve }))
