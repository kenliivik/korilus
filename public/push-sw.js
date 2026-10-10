// Loaded into the service worker (vite.config.js → workbox.importScripts).
// Shows reminders sent by api/send.php; tapping one opens that species' page.
self.addEventListener('push', (event) => {
  const { title, body, url } = event.data.json()
  event.waitUntil(self.registration.showNotification(title, { body, icon: '/pwa-192x192.png', data: url }))
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()
  event.waitUntil(self.clients.openWindow(event.notification.data))
})
