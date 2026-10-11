import { reactive } from 'vue'

// Species, seasons and tips, edited in /admin and served by api/content.php.
// The list below is bundled with the app and used until the server has content (and offline on first run).
// Mustikas dates and its first three tips come from the wireframes;
// the rest was looked up on 2026-10-10 (RMK "Korilase nurgake", ERR, Looduskalender, Postimees, supertaim.ee).
// `season` has one character per month, January first: a = algus, t = tipp, l = lõpp, . = not in season.
// Ids of the bundled nine map to the illustrations in components/BerryGlyph.vue; new berries may have an `image`.
export const bundled = [
  {
    id: 'metsmaasikas',
    name: 'Metsmaasikas',
    latin: 'Fragaria vesca',
    season: '.....at.....',
    dates: { Algus: 'jaanipäev', Tipp: 'juuli algus', Lõpp: 'juuli lõpp' },
    tips: ['Otsi raiesmikelt, lagendikelt ja metsaservadest', 'Pärast jaanipäeva leiad neid peamiselt tuttavatest kohtadest'],
  },
  {
    id: 'vaarikas',
    name: 'Vaarikas',
    latin: 'Rubus idaeus',
    season: '......tl....',
    dates: { Algus: 'juuli', Lõpp: 'augusti lõpp' },
    tips: ['Parimad paigad on 3–4 aasta vanused raiesmikud'],
  },
  {
    id: 'mustikas',
    name: 'Mustikas',
    latin: 'Vaccinium myrtillus',
    season: '......at....',
    dates: { Algus: '15. juuli', Tipp: '28. juuli – 10. august', Lõpp: '20. august' },
    tips: [
      'Otsi metsaservadest',
      'Korja täiesti siniseid marju',
      'Parim aeg on hommikul',
      'Magusamad marjad on kuivades männikutes, suuremad rabaservadel ja kuusikutes',
    ],
  },
  {
    id: 'murakas',
    name: 'Murakas',
    latin: 'Rubus chamaemorus',
    season: '......tl....',
    dates: { Algus: 'juuli', Lõpp: 'augusti esimene pool' },
    tips: ['Küps mari on meekollane, mitte punane', 'Igal varrel on üks mari, seega varu kannatust'],
  },
  {
    id: 'pohl',
    name: 'Pohl',
    latin: 'Vaccinium vitis-idaea',
    season: '.......atl..',
    dates: { Algus: 'august', Tipp: 'suve lõpp – september', Lõpp: 'oktoober' },
    tips: ['Otsi kuivadest hõredatest männikutest, nõmmedelt ja rabaservadelt'],
  },
  {
    id: 'johvikas',
    name: 'Jõhvikas',
    latin: 'Vaccinium oxycoccos',
    season: '........at..',
    dates: { Algus: 'septembri keskpaik', Tipp: 'pärast esimesi öökülmi', Lõpp: 'oktoober' },
    tips: [
      'Parim aeg on pärast esimesi öökülmi',
      'Ära korja liiga vara: järelvalminud marjad on vähem maitsvad',
      'Öökülma näinud marjad on magusamad, kuid säilivad halvemini',
    ],
  },
  {
    id: 'rabarber',
    name: 'Rabarber',
    latin: 'Rheum rhabarbarum',
    season: '...atl......',
    dates: { Algus: 'aprill', Lõpp: '24. juuni (jaanipäev)' },
    tips: [
      'Vali tugevad läikivad varred ilma pruunide laikudeta',
      'Ära korja pärast jaanipäeva, siis on vartes palju oblikhapet',
      'Lehed ei sobi söögiks',
    ],
  },
  {
    id: 'oun',
    name: 'Õun',
    latin: 'Malus domestica',
    season: '.......atl..',
    dates: { Algus: 'augusti teine pool (suveõunad)', Lõpp: 'hilissügis (talveõunad)' },
    tips: ['Küps õun tuleb oksast kergelt lahti, ilma tõmbamata', "Suveõuna (nt 'Valge klaar') koor muutub rohelisest kollakasvalgeks"],
  },
  {
    id: 'kirss',
    name: 'Kirss',
    latin: 'Prunus cerasus',
    season: '......tl....',
    dates: { Algus: 'juuli keskpaik', Lõpp: 'augusti algus' },
    tips: ['Varased sordid valmivad juuli keskel, hilised juuli lõpus'],
  },
]

// Everything from the server (hidden items too, for the admin). `produce` and `recipes` are what the app shows.
// They are filled in place, so imports stay live when new content arrives.
const KEY = 'korilus-content'
export const content = reactive({ version: 0, berries: [], recipes: [] })
export const produce = reactive([])
export const recipes = reactive([])
let serverHasBerries = null // unknown until the server has answered

function setContent(c) {
  Object.assign(content, { version: c.version, berries: c.berries?.length ? c.berries : bundled, recipes: c.recipes ?? [] })
  produce.splice(0, produce.length, ...content.berries.filter((b) => !b.hidden))
  recipes.splice(0, recipes.length, ...content.recipes.filter((r) => !r.hidden))
  return content
}

function fromServer(c) {
  serverHasBerries = !!c.berries?.length
  if (c.version) {
    setContent(c)
    try { localStorage.setItem(KEY, JSON.stringify(c)) } catch { /* full storage: the cached copy just stays older */ }
  }
  return content
}

// Last good copy first, then whatever the server has now. Offline, the service worker answers from its cache.
try { setContent(JSON.parse(localStorage.getItem(KEY)) ?? {}) } catch { setContent({}) }
export const loadContent = () => fetch('/api/content.php').then((r) => r.json()).then(fromServer).catch(() => content)
loadContent()

// For /admin: posts one change to api/content.php (see the build contract) and shows the result.
// The first berry change also stores the bundled list on the server, so editing one doesn't drop the rest.
export async function saveContent(body) {
  const post = async (b) => {
    const res = await fetch('/api/content.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(b) })
    if (!res.ok) throw Object.assign(new Error((await res.json().catch(() => ({}))).error ?? res.statusText), { status: res.status })
    return fromServer(await res.json())
  }
  if (serverHasBerries === null) await loadContent()
  if (body.collection === 'berries' && serverHasBerries === false) {
    for (const item of bundled) await post({ collection: 'berries', action: 'save', item })
  }
  return post(body)
}

export const recipesFor = (berryId) => recipes.filter((r) => r.berries?.includes(berryId))

export const phases = { a: 'Algus', t: 'Tipp', l: 'Lõpp' }
export const statusText = { a: 'hakkab valmima', t: 'küps!', l: 'hooaja lõpp', '.': 'pole hooaeg' }

export const months = Array.from({ length: 12 }, (_, i) =>
  new Intl.DateTimeFormat('et-EE', { month: 'long' }).format(new Date(2000, i, 1)),
)

export const findProduce = (id) => produce.find((p) => p.id === id)
export const phaseIn = (p, month = new Date().getMonth()) => p.season[month]
export const inSeason = (month = new Date().getMonth()) => produce.filter((p) => p.season[month] !== '.')

