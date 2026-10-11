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

## Admin (content editor)

`https://korilus.dsgnr.ee/admin` edits the berries (and later recipes) for every user, with no deploy.
Saved content lives in `api/data/content.json` on the server, with a dated copy of every version in
`api/data/backups/` (to undo a bad edit, copy one back over `content.json`). Uploaded pictures go to `uploads/`.
Neither folder is touched by deploys. Until something is saved, the app shows the berries bundled in `src/data/berries.js`.

Server settings live in `api/data/config.php`, which is never in git or deployed (see `public/api/config.example.php`).
Set the admin password once, in either way:

- **Over SSH** (Windows: PowerShell has `ssh` built in; use the host and user from Iseteenindus → SSH kasutajad):
  ```
  ssh -p 1022 USER@HOST
  php ~/htdocs/korilus/api/admin-password.php
  ```
  It asks for the password and writes `api/data/config.php` itself. Run it again to change the password
  (this also logs every device out).
- **Without SSH:** open `/admin`, type the password you want and press "Logi sisse". While no password is set,
  the page shows a line `'admin_password_hash' => '…',`. Make the file `htdocs/korilus/api/data/config.php`
  (with WinSCP, FileZilla or the host's file manager) from `config.example.php`, paste that line in, and log in.

The password is stored only as a hash. Wrong passwords are limited to 10 per 15 minutes per IP address.

Local test of the PHP parts: `npm run build && php -S localhost:8000 -t dist`, then call `http://localhost:8000/api/content.php`.
