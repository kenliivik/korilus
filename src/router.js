import { createRouter, createWebHistory } from 'vue-router'
import HomeView from './views/HomeView.vue'
import YearCalendarView from './views/YearCalendarView.vue'
import ProduceView from './views/ProduceView.vue'
import LocationsView from './views/LocationsView.vue'
import LocationView from './views/LocationView.vue'
import LocationFormView from './views/LocationFormView.vue'
import SettingsView from './views/SettingsView.vue'

// `meta.tab` decides which bottom-nav tab is highlighted.
export default createRouter({
  history: createWebHistory(),
  scrollBehavior: () => ({ top: 0 }),
  routes: [
    { path: '/', component: HomeView, meta: { tab: 'home' } },
    { path: '/kalender', component: YearCalendarView, meta: { tab: 'home' } },
    { path: '/mari/:id', component: ProduceView, props: true, meta: { tab: 'home' } },
    { path: '/asukohad', component: LocationsView, meta: { tab: 'locations' } },
    { path: '/asukohad/uus', component: LocationFormView, meta: { tab: 'locations' } },
    { path: '/asukohad/:id', component: LocationView, props: true, meta: { tab: 'locations' } },
    { path: '/asukohad/:id/muuda', component: LocationFormView, props: true, meta: { tab: 'locations' } },
    { path: '/seaded', component: SettingsView, meta: { tab: 'settings' } },
    { path: '/:rest(.*)', redirect: '/' },
  ],
})
