import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import YearCalendarView from './views/YearCalendarView.vue'
import ProduceView from './views/ProduceView.vue'
import LocationsView from './views/LocationsView.vue'
import LocationView from './views/LocationView.vue'
import LocationFormView from './views/LocationFormView.vue'
import SettingsView from './views/SettingsView.vue'
import AccountView from './views/AccountView.vue'
import GroupView from './views/GroupView.vue'
import RecipesView from './views/RecipesView.vue'
import RecipeView from './views/RecipeView.vue'
import FeedbackView from './views/FeedbackView.vue'

// `meta.tab` decides which bottom-nav tab is highlighted.
export default createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', component: HomeView, meta: { tab: 'home', wide: true } },
    { path: '/kalender', component: YearCalendarView, meta: { tab: 'calendar', wide: true } },
    { path: '/mari/:id', component: ProduceView, props: true, meta: { tab: 'home' } },
    { path: '/asukohad', component: LocationsView, meta: { tab: 'locations' } },
    { path: '/asukohad/uus', component: LocationFormView, meta: { tab: 'locations' } },
    { path: '/asukohad/:id', component: LocationView, props: true, meta: { tab: 'locations' } },
    { path: '/asukohad/:id/muuda', component: LocationFormView, props: true, meta: { tab: 'locations' } },
    { path: '/retseptid', component: RecipesView, meta: { tab: 'recipes' } },
    { path: '/retsept/:id', component: RecipeView, props: true, meta: { tab: 'recipes' } },
    // Admin screens are only for Ken, so they load separately. `:id` "uus" = new recipe.
    { path: '/admin/retseptid', component: () => import('./views/admin/RecipesAdmin.vue'), meta: { tab: 'settings' } },
    { path: '/admin/retseptid/:id', component: () => import('./views/admin/RecipeEditor.vue'), props: true, meta: { tab: 'settings' } },
    { path: '/seaded', component: SettingsView, meta: { tab: 'settings' } },
    { path: '/admin', component: () => import('./views/admin/AdminView.vue'), meta: { tab: 'settings' } },
    { path: '/admin/marjad/:id?', component: () => import('./views/admin/BerryEditor.vue'), props: true, meta: { tab: 'settings' } },
    { path: '/admin/tagasiside', component: () => import('./views/admin/FeedbackAdmin.vue'), meta: { tab: 'settings' } },
    { path: '/tagasiside', component: FeedbackView, meta: { tab: 'settings' } },
    { path: '/konto', component: AccountView, meta: { tab: 'settings' } },
    { path: '/grupid/:id?', component: GroupView, props: true, meta: { tab: 'settings' } },
    { path: '/:rest(.*)', redirect: '/' },
  ],
})
