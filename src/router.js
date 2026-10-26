import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import YearCalendarView from './views/YearCalendarView.vue'
import ProduceView from './views/ProduceView.vue'
import SettingsView from './views/SettingsView.vue'
import AccountView from './views/AccountView.vue'
import GroupView from './views/GroupView.vue'
import RecipesView from './views/RecipesView.vue'
import RecipeView from './views/RecipeView.vue'
import FeedbackView from './views/FeedbackView.vue'

// The places pages load separately because they bring the map library (Leaflet).
// `meta.tab` decides which bottom-nav tab is highlighted.
export default createRouter({
  history: createWebHistory(),
  // Back and forward return to where the page was scrolled; a new page starts at the top.
  scrollBehavior: (to, from, savedPosition) => savedPosition ?? { top: 0 },
  routes: [
    { path: '/', component: HomeView, meta: { tab: 'home', wide: true } },
    { path: '/kalender', component: YearCalendarView, meta: { tab: 'calendar', wide: true } },
    { path: '/mari/:id', component: ProduceView, props: true, meta: { tab: 'home', wide: true } },
    { path: '/asukohad', component: () => import('./views/LocationsView.vue'), meta: { tab: 'locations', wide: true } },
    { path: '/asukohad/uus', component: () => import('./views/LocationFormView.vue'), meta: { tab: 'locations', wide: true } },
    { path: '/asukohad/:id', component: () => import('./views/LocationView.vue'), props: true, meta: { tab: 'locations', wide: true } },
    { path: '/asukohad/:id/muuda', component: () => import('./views/LocationFormView.vue'), props: true, meta: { tab: 'locations', wide: true } },
    { path: '/retseptid', component: RecipesView, meta: { tab: 'recipes', wide: true } },
    { path: '/retsept/:id', component: RecipeView, props: true, meta: { tab: 'recipes', wide: true } },
    // Own recipes. `:id` "uus" = new.
    { path: '/oma-retsept/:id', component: () => import('./views/MyRecipeFormView.vue'), props: true, meta: { tab: 'recipes', wide: true, doc: true } },
    // Sahver (pantry). `:id` "uus" = new, with ?retsept=<id> from a recipe.
    { path: '/sahver', component: () => import('./views/PantryView.vue'), meta: { tab: 'recipes', wide: true } },
    { path: '/sahver/:id', component: () => import('./views/PantryFormView.vue'), props: true, meta: { tab: 'recipes', wide: true, doc: true } },
    // Admin screens are only for Ken, so they load separately. `:id` "uus" = new recipe.
    { path: '/admin/retseptid', component: () => import('./views/admin/RecipesAdmin.vue'), meta: { tab: 'settings', wide: true, doc: true } },
    { path: '/admin/retseptid/:id', component: () => import('./views/admin/RecipeEditor.vue'), props: true, meta: { tab: 'settings', wide: true, doc: true } },
    { path: '/seaded', component: SettingsView, meta: { tab: 'settings', wide: true } },
    { path: '/admin', component: () => import('./views/admin/AdminView.vue'), meta: { tab: 'settings', wide: true, doc: true } },
    { path: '/admin/marjad/:id?', component: () => import('./views/admin/BerryEditor.vue'), props: true, meta: { tab: 'settings', wide: true, doc: true } },
    { path: '/admin/tagasiside', component: () => import('./views/admin/FeedbackAdmin.vue'), meta: { tab: 'settings', wide: true, doc: true } },
    { path: '/tagasiside', component: FeedbackView, meta: { tab: 'settings' } },
    { path: '/konto', component: AccountView, meta: { tab: 'settings' } },
    { path: '/grupid/:id?', component: GroupView, props: true, meta: { tab: 'settings' } },
    { path: '/:rest(.*)', redirect: '/' },
  ],
})
