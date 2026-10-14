# Korilus

Estonian-language PWA for wild berry and fruit seasons and foraging spots. Vue 3, Vite, Tailwind CSS v4, with a small PHP API.

```
npm install
npm run dev                       # local dev server
npm test                          # spot sync test
npm run build && npm run preview  # production build, test install and offline
```

Every push to `main` is built and deployed by `.github/workflows/deploy.yml`.
Server settings go in `api/data/config.php` on the server (see `public/api/config.example.php`); it is never in git.
