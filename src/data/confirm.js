import { onBeforeUnmount, reactive } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'

// "Are you sure?" as a card in the app's own look (ConfirmDialog in App.vue) instead of the browser's grey confirm() popup.
// `await ask(text, 'Kustuta')` is true when the person pressed the button, false on "Tühista" or Esc.
export const pending = reactive({ text: '', ok: '', resolve: null })

export const ask = (text, ok = 'Kustuta') => new Promise((resolve) => Object.assign(pending, { text, ok, resolve }))

// For forms: asks before leaving the page (menu, back arrow, back gesture) while `changed()` is true,
// and the browser asks before closing the tab. Call the returned function after saving, so the form can go.
export function guardLeave(changed) {
  let done = false
  const unsaved = () => !done && changed()
  onBeforeRouteLeave(() => !unsaved() || ask('Kas loobud muudatustest? Need jäävad salvestamata.', 'Loobu'))
  const warn = (e) => unsaved() && e.preventDefault()
  addEventListener('beforeunload', warn)
  onBeforeUnmount(() => removeEventListener('beforeunload', warn))
  return () => (done = true)
}
