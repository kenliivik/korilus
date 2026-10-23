import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    VitePWA({
      registerType: 'autoUpdate',
      manifest: {
        name: 'Korilus',
        short_name: 'Korilus',
        description: 'Marjakalender ja sinu korjekohad',
        lang: 'et',
        start_url: '/',
        scope: '/',
        display: 'standalone',
        orientation: 'portrait',
        theme_color: '#FDFBF0',
        background_color: '#FDFBF0',
        // Phones and the service worker cache icons by file name: give them a new name when the icon changes.
        icons: [
          { src: 'icon-64.png', sizes: '64x64', type: 'image/png' },
          { src: 'icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'icon-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,png,svg,webp,woff2}'],
        importScripts: ['push-sw.js'],
        navigateFallbackDenylist: [/^\/api\//, /^\/uploads\//],
        // Berries and recipes from /admin: fresh when online, the last copy offline. Pictures never change.
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname === '/api/content.php',
            handler: 'NetworkFirst',
            options: { cacheName: 'content', networkTimeoutSeconds: 5 },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/uploads/'),
            handler: 'CacheFirst',
            options: { cacheName: 'uploads', expiration: { maxEntries: 300 } },
          },
          {
            // Map tiles the phone has already shown, for the forest without signal. Only what was viewed, never prefetched (OSM policy).
            urlPattern: ({ url }) => url.origin === 'https://tile.openstreetmap.org',
            handler: 'CacheFirst',
            options: {
              cacheName: 'tiles',
              expiration: { maxEntries: 3000, maxAgeSeconds: 60 * 60 * 24 * 30 },
              cacheableResponse: { statuses: [200] },
            },
          },
        ],
      },
    }),
  ],
})
