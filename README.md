# Korilus

Estonian-language PWA for wild berry and fruit seasons and foraging spots. Vue 3, Vite, Tailwind CSS v4.

```
npm install
npm run dev                       # local dev server
npm run build && npm run preview  # production build, test install and offline
```

## Deploy

Every push to `main` builds the app and uploads `dist/` to `htdocs/korilus` on the Veebimajutus server
(`.github/workflows/deploy.yml`). It needs three repository secrets (Settings → Secrets and variables → Actions):

- `SSH_HOST`: the SSH server address from Iseteenindus → Veebiserver → SSH kasutajad
- `SSH_USER`: the SSH username
- `SSH_PRIVATE_KEY`: a deploy-only private key without a passphrase; its `.pub` half is added under SSH kasutajad → Võtmed

A run can also be started by hand from the Actions tab.
