import { bundled } from './data/berries.js'

// The loading screen in index.html. On the first open of a visit it shows a random tip and stays
// at least MIN_MS from page start so the berries get to hop; later reloads in the same visit skip the wait.
const MIN_MS = 1400
const loader = document.getElementById('loader')
const storage = (() => {
  try {
    return sessionStorage
  } catch {
    return null
  }
})()
const seen = storage?.getItem('korilus-loaded')

if (loader && !seen) {
  const berry = bundled[Math.floor(Math.random() * bundled.length)]
  const tip = berry.tips[Math.floor(Math.random() * berry.tips.length)]
  const p = loader.querySelector('p')
  p.innerHTML = '<b></b>'
  p.firstChild.textContent = berry.name
  p.append(tip)
  requestAnimationFrame(() => p.classList.add('shown'))
}

export function hideLoader() {
  if (!loader) return
  const wait = seen ? 0 : Math.max(0, MIN_MS - performance.now())
  setTimeout(() => {
    storage?.setItem('korilus-loaded', '1')
    loader.classList.add('done')
    setTimeout(() => loader.remove(), 400) // after the fade
  }, wait)
}
