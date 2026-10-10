<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: { type: String, required: true },
  size: { type: Number, default: 40 },
})

const leaf = (d, extra = '') => `<path d="${d}" fill="#6E8263" ${extra}/>`

function druplets(cx, cy, r, rows, fill, edge) {
  let s = ''
  rows.forEach((n, i) => {
    const y = cy + (i - (rows.length - 1) / 2) * r * 1.7
    for (let j = 0; j < n; j++) {
      const x = cx + (j - (n - 1) / 2) * r * 1.9
      s += `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${r}" fill="${fill}" stroke="${edge}" stroke-width="0.6"/>`
      s += `<circle cx="${(x - r * 0.35).toFixed(1)}" cy="${(y - r * 0.35).toFixed(1)}" r="${(r * 0.3).toFixed(1)}" fill="#fff" fill-opacity="0.5"/>`
    }
  })
  return s
}

const berry = (cx, cy, r, fill, hl = 0.55) =>
  `<circle cx="${cx}" cy="${cy}" r="${r}" fill="${fill}"/><circle cx="${cx - r * 0.35}" cy="${cy - r * 0.4}" r="${r * 0.28}" fill="#fff" fill-opacity="${hl}"/>`

// Same drawings as the berry/* components in Figma.
const GLYPHS = {
  metsmaasikas:
    leaf('M14 9c3-4 8-4 6 1 4-3 8 0 4 3-4 1-8 1-10-4Z') +
    '<path d="M20 12c7 0 10 7 8 13-2 6-6 9-8 9s-6-3-8-9c-2-6 1-13 8-13Z" fill="#C8473E"/>' +
    [[17, 18], [23, 18], [20, 22], [16, 25], [24, 25], [20, 29]]
      .map(([x, y]) => `<ellipse cx="${x}" cy="${y}" rx="0.8" ry="1.1" fill="#F2D27A"/>`)
      .join('') +
    '<ellipse cx="16.5" cy="16" rx="1.6" ry="2.6" fill="#fff" fill-opacity="0.35"/>',
  vaarikas:
    leaf('M8 8c6-3 11 0 11 5-5 1-10-1-11-5Z') +
    leaf('M32 8c-6-3-11 0-11 5 5 1 10-1 11-5Z') +
    druplets(20, 23, 3.1, [3, 4, 4, 3, 2], '#C4566A', '#9C3A4E'),
  mustikas:
    leaf('M6 12c5-6 12-5 13 1-6 3-11 2-13-1Z') +
    berry(15, 26, 7, '#3F4E77', 0.4) +
    berry(26, 22, 6.5, '#4A5A86', 0.4) +
    '<circle cx="26" cy="16.8" r="1.4" fill="#2C3655"/><circle cx="15" cy="20.2" r="1.4" fill="#2C3655"/>',
  murakas:
    leaf('M10 30c-4 4-1 7 3 5M30 30c4 4 1 7-3 5', 'stroke="#6E8263" stroke-width="2"') +
    druplets(20, 20, 3.4, [2, 3, 4, 3, 2], '#E0A341', '#B97A22'),
  pohl:
    leaf('M20 6c6 1 9 6 7 10-5-1-8-5-7-10Z') +
    leaf('M20 6c-6 1-9 6-7 10 5-1 8-5 7-10Z') +
    berry(14, 24, 5, '#B42A2F') +
    berry(24, 23, 5, '#C0353A') +
    berry(19, 31, 5, '#A82429') +
    berry(28, 31, 4, '#B42A2F'),
  johvikas:
    '<path d="M20 4c-2 6-6 9-10 12M20 4c2 6 7 9 11 11" stroke="#6E8263" stroke-width="1.3" fill="none" stroke-linecap="round"/>' +
    leaf('M20 4c4-2 8-1 9 2-4 1-7 0-9-2Z') +
    '<ellipse cx="11" cy="24" rx="6.5" ry="7.5" fill="#9B2233"/><ellipse cx="8.8" cy="21" rx="1.8" ry="2.4" fill="#fff" fill-opacity="0.45"/>' +
    '<ellipse cx="29" cy="25" rx="7" ry="8" fill="#A9283A"/><ellipse cx="26.6" cy="21.6" rx="1.9" ry="2.6" fill="#fff" fill-opacity="0.45"/>',
}

const markup = computed(() => GLYPHS[props.name] ?? '')
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 40 40"
    fill="none"
    aria-hidden="true"
    v-html="markup"
  />
</template>
