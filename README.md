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

## Login, groups and spot sync (server setup)

Logging in is optional: the app keeps working on the phone without it. When someone logs in (email + a 6-digit code
typed into the app), their spots sync to the server and they can share spots with groups such as "Pere".
It runs on the same Veebimajutus server with PHP and MariaDB, no other services. One-time setup:

1. **Database.** In Iseteenindus → Andmebaasid, create a MariaDB database and a user for it. Note the database name,
   user and password.
2. **Mailbox.** Create a mailbox such as `korilus@dsgnr.ee` (Iseteenindus → E-post). The login codes are sent from it.
   For mail to land in inboxes, check that dsgnr.ee has SPF, DKIM and DMARC records (Iseteenindus → DNS; DKIM may
   already be on). A DMARC record can start as `v=DMARC1; p=none`.
3. **Config file.** On your computer, make a file `config.php` like this (keep any keys already in it):

   ```php
   <?php
   return [
       'db' => ['dsn' => 'mysql:host=localhost;dbname=DATABASE_NAME;charset=utf8mb4', 'user' => 'DB_USER', 'pass' => 'DB_PASSWORD'],
       'smtp' => ['host' => 'mail.veebimajutus.ee', 'port' => 587, 'user' => 'korilus@dsgnr.ee', 'pass' => 'MAILBOX_PASSWORD', 'from' => 'korilus@dsgnr.ee'],
       'app_url' => 'https://korilus.dsgnr.ee', // the app's address, used in invitation emails
       'signup_open' => false, // true lets anyone log in; false = only people who were invited
   ];
   ```

   Use the SMTP server name Veebimajutus shows for the mailbox (port 587 with STARTTLS, or 465). Upload the file with
   WinSCP or FileZilla (SFTP, port 1022) to `htdocs/korilus/api/data/config.php`. That folder is never overwritten by
   the deploy and is not reachable from the web.
4. **Tables and first user.** Log in with SSH (on Windows: `ssh -p 1022 USER@HOST` in PowerShell, or PuTTY) and run:

   ```
   php ~/htdocs/korilus/api/migrate.php
   php ~/htdocs/korilus/api/create-user.php ken@dsgnr.ee --admin --name=Ken
   ```

   Without SSH, add each line once as a cron job in Iseteenindus → Cron (any time in the next minutes), wait for it
   to run (the result comes by email), then delete the cron job. `migrate.php` is safe to run again after updates.
5. Open the app → Seaded → Konto, log in with the code from the email, create a group and invite people by email.
   Invited people can then log in with their own address.

Helper scripts, `schema.sql` and `api/data/` are blocked from the web in `public/api/.htaccess`.
