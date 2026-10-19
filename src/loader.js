import { bundled } from './data/berries.js'

// The loading screen in index.html. It writes "Korilus" and a random picking tip in, letter by letter,
// and on the first open of a visit stays at least MIN_MS from page start so the writing can finish.
// Later reloads in the same visit skip the wait.
const MIN_MS = 2200
const loader = document.getElementById('loader')
const storage = (() => {
  try {
    return sessionStorage
  } catch {
    return null
  }
})()
const seen = storage?.getItem('korilus-loaded')

// Wraps each letter of `text` in a span that starts `step` ms after the one before it.
// Letters sit in a span per word, so lines still break only between words.
function write(el, text, start, step) {
  let i = 0
  for (const part of text.split(/(\s+)/)) {
    if (!part) continue
    if (/\s/.test(part)) {
      el.append(part)
      i += part.length
      continue
    }
    const word = document.createElement('span')
    for (const ch of part) {
      const letter = document.createElement('span')
      letter.textContent = ch
      letter.style.animationDelay = `${start + i++ * step}ms`
      word.append(letter)
    }
    el.append(word)
  }
}

if (loader && !seen) {
  const berry = bundled[Math.floor(Math.random() * bundled.length)]
  const tip = berry.tips[Math.floor(Math.random() * berry.tips.length)]
  const h1 = loader.querySelector('h1')
  const p = loader.querySelector('p')
  const name = document.createElement('b')
  // Write only once the fonts are in, so letters don't change shape halfway.
  const fonts = Promise.all(['1em "IM Fell Double Pica SC"', 'italic 1em "IM Fell Double Pica"'].map((f) => document.fonts.load(f)))
  Promise.race([fonts, new Promise((r) => setTimeout(r, 500))]).finally(() => {
    h1.textContent = ''
    write(h1, 'Korilus', 0, 90)
    write(name, berry.name, 650, 40)
    // Long tips write faster, so every tip is done in about a second.
    write(p, tip, 650 + berry.name.length * 40, Math.min(25, 1100 / tip.length))
    p.prepend(name)
    h1.classList.add('written')
    p.classList.add('written')
  })
} else if (loader) {
  loader.querySelector('h1').classList.add('written')
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
