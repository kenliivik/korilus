// Species, seasons and tips from the wireframes (Korilus, 2026-10-10).
// `season` has one character per month, January first:
// a = algus, t = tipp, l = lõpp, . = not in season (copied from "Aastane kalender").
// `glyph` keys map to the illustrations in components/BerryGlyph.vue.
export const produce = [
  { id: 'metsmaasikas', name: 'Metsmaasikas', latin: 'Fragaria vesca', season: '....at......' },
  { id: 'vaarikas', name: 'Vaarikas', latin: 'Rubus idaeus', season: '.....atl....' },
  {
    id: 'mustikas',
    name: 'Mustikas',
    latin: 'Vaccinium myrtillus',
    season: '......at....',
    dates: { Algus: '15. juuli', Tipp: '28. juuli – 10. august', Lõpp: '20. august' },
    tips: ['Otsi metsaservadest', 'Korja täiesti siniseid marju', 'Parim aeg on hommikul'],
  },
  { id: 'murakas', name: 'Murakas', latin: 'Rubus chamaemorus', season: '.....atl....' },
  { id: 'pohl', name: 'Pohl', latin: 'Vaccinium vitis-idaea', season: '.......atl..' },
  { id: 'johvikas', name: 'Jõhvikas', latin: 'Vaccinium oxycoccos', season: '........atl.' },
  { id: 'rabarber', name: 'Rabarber', latin: 'Rheum rhabarbarum', season: '...atl......' },
  { id: 'oun', name: 'Õun', latin: 'Malus domestica', season: '.......atl..' },
  { id: 'kirss', name: 'Kirss', latin: 'Prunus cerasus', season: '.....tl.....' },
]

export const phases = { a: 'Algus', t: 'Tipp', l: 'Lõpp' }
export const statusText = { a: 'hakkab valmima', t: 'küps!', l: 'hooaja lõpp', '.': 'pole hooaeg' }

export const months = Array.from({ length: 12 }, (_, i) =>
  new Intl.DateTimeFormat('et-EE', { month: 'long' }).format(new Date(2000, i, 1)),
)

export const findProduce = (id) => produce.find((p) => p.id === id)
export const phaseIn = (p, month = new Date().getMonth()) => p.season[month]
export const inSeason = (month = new Date().getMonth()) => produce.filter((p) => p.season[month] !== '.')

// Month-level dates for species without day-level dates from the documents.
export const seasonDates = (p) =>
  p.dates ??
  Object.fromEntries(
    Object.entries(phases)
      .map(([key, label]) => [label, [...p.season].flatMap((c, i) => (c === key ? months[i] : [])).join(', ')])
      .filter(([, v]) => v),
  )
