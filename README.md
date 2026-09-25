# Dummy Test Site (Laravel)

A minimal Laravel placeholder app used to verify that a VPS / web server / PHP
stack is configured correctly. No external services or APIs required.

## What's inside

| Route     | Description                                   |
|-----------|-----------------------------------------------|
| `/`       | Home page showing PHP/Laravel/host info       |
| `/about`  | Static "About" page                           |
| `/health` | JSON status endpoint (handy for monitoring)   |

- Framework: **Laravel 12**
- Database: **SQLite** (file at `database/database.sqlite`)
- Views: `resources/views/layouts/app.blade.php`, `home.blade.php`, `about.blade.php`
- Routes: `routes/web.php`

## Run locally

```bash
composer install
cp .env.example .env       # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Then open http://127.0.0.1:8000

### If you use XAMPP
XAMPP already runs Apache, so nothing extra to install. The project lives in
`C:\xampp\htdocs\test web laravel`, so browse to:

```
http://localhost/test%20web%20laravel/public
```

To get a clean URL like `http://test-site.local`, add a virtual host in
`C:\xampp\apache\conf\extra\httpd-vhosts.conf`:

```apache
<VirtualHost *:80>
    ServerName test-site.local
    DocumentRoot "C:/xampp/htdocs/test web laravel/public"
    <Directory "C:/xampp/htdocs/test web laravel/public">
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Then add `127.0.0.1 test-site.local` to `C:\Windows\System32\drivers\etc\hosts`
and restart Apache from the XAMPP control panel. `AllowOverride All` matters
here too — Laravel's rewrite rules live in `public/.htaccess`.

## Deploy to a VPS running Webuzo

Webuzo already ships Apache, PHP and MySQL, and **manages the virtual hosts for
you**. So you do *not* create files in `/etc/apache2/sites-available/`, you do
*not* run `a2ensite`, and you do *not* `chown` to `www-data` — on Webuzo every
hosting account runs as its **own system user** with its own home directory.

Instead you do three things in the panel: **enable PHP extensions → upload the
code → point the domain at `public/`**. Then you manage everything else
(SSL, PHP version, logs, cron, disk) from the panel too.

> Paths in this section use `myuser` as your Webuzo account username and
> `example.com` as your domain. Replace both with your real values.

### 1. Requirements

**Log in to Webuzo → Enduser Panel → Configuration → PHP Extensions**, select
the PHP version you will use (8.2 or newer), and enable these — they are **not**
all on by default on Webuzo and the app will not run without them:

| Extension | Why |
|---|---|
| `pdo_sqlite` + `sqlite3` | **Required** — this app uses an SQLite database |
| `mbstring` | Laravel string handling |
| `openssl` | Encryption, `APP_KEY` |
| `ctype`, `fileinfo`, `json`, `tokenizer` | Laravel core |
| `curl` | Outbound HTTP |
| `zip` | Composer package extraction |
| `xml` / `dom` | Required by PHPUnit & various packages |

Then **Configuration → MultiPHP Manager**: pick the domain and set it to your
PHP 8.2+ version, click **Apply**. If you skip this the domain keeps Webuzo's
older default PHP and Laravel 12 will fail with a syntax/version error.

### 2. Upload the code

Two options — both put the app in your account's home directory, e.g.
`/home/myuser/test-site`.

**Option A — Webuzo web Terminal (no SSH needed)**

Enduser Panel → **Server Utilities → Terminal**, then:

```bash
cd /home/myuser
git clone <your-repo-url> test-site
cd test-site
composer install --no-dev --optimize-autoloader
```

**Option B — File Manager + SSH**

Upload a zip via **Server Utilities → File Manager** (or use SFTP), extract to
`/home/myuser/test-site`, then run the `composer install` line above over SSH.

> If `composer` is not found, use the PHP binary directly:
> `/usr/local/apps/php82/bin/php /usr/local/bin/composer install --no-dev -o`
> (adjust `php82` to the version you enabled in MultiPHP Manager).

### 3. Configure

Using the Terminal (or SSH) as your Webuzo user — **not** with `sudo`:

```bash
cd /home/myuser/test-site
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
```

Edit `.env` for production:
```env
APP_NAME="Dummy Test Site"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://example.com
```

> `APP_DEBUG=false` matters here: with debug on, an error page leaks your
> `.env`, database path and file paths to anyone who visits.

### 4. Permissions

The files must be owned by **your Webuzo account user**, not `www-data`:

```bash
cd /home/myuser/test-site
chmod -R 755 storage bootstrap/cache
```

Ownership is normally already correct because you uploaded as that user. If you
used `sudo` or root anywhere, fix it (use your real username):

```bash
chown -R myuser:myuser /home/myuser/test-site
```

Do **not** run `chmod -R 777` — it is unnecessary and insecure.

### 5. Point the domain at `public/`

This replaces the manual Apache vhost step. Laravel must be served from `public/`
so that `.env`, `vendor/` and `storage/` are never web-accessible.

1. Enduser Panel → **Domains → Add Domain** (if you have not added it yet).
2. Enduser Panel → **Domains → Manage Domains** → **Edit** next to your domain.
3. Set **Domain Path** to:
   ```
   /home/myuser/test-site/public
   ```
4. Click **Edit Domain** to save.

That's it — Webuzo regenerates the vhost and reloads Apache for you.

> If **Domain Path** cannot be changed, the domain is your account's *primary*
> domain (Webuzo locks that field for the primary domain). Fix it by adding a
> subdomain or addon domain for the site, setting its path, or ask your host to
> adjust the primary domain's docroot.

### 6. SSL

Don't install Certbot — Webuzo handles Let's Encrypt.

When adding or editing the domain, tick **Let's Encrypt Certificate**. Webuzo
issues it and writes the HTTPS vhost automatically. Re-check
**Domains → Manage Domains** afterwards to confirm the certificate is listed
and valid.

### 7. Cache for speed (after everything works)

```bash
cd /home/myuser/test-site
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Re-run `php artisan config:clear && php artisan cache:clear` any time you
> change `.env`, then re-cache. A stale config cache is the single most common
> cause of "I changed `.env` and nothing happened".

### 8. Scheduled tasks (optional)

If you add scheduled jobs later, register Laravel's scheduler **through the
panel** rather than editing crontab by hand:

Enduser Panel → **Server Utilities → Cron Job → Add Cron Job**, with:

- **Command:** `/usr/local/apps/php82/bin/php /home/myuser/test-site/artisan schedule:run`
- **Schedule:** every minute

Webuzo's cron form accepts standard cron expressions
(`* * * * *` = every minute). Add your email in **Cron Email** to get
notified when the job runs.

## Monitoring & management through Webuzo

Everything below is done from the panel — no server access required.

### Uptime / health monitoring
The app exposes a JSON health endpoint. Add it to any uptime monitor
(UptimeRobot, Better Uptime, a Webuzo cron + mail alert, etc.):

```
https://example.com/health
```

It returns HTTP 200 with:
```json
{"status":"ok","app":"Dummy Test Site","env":"production","php":"8.2.x","laravel":"12.x","host":"...","time":"..."}
```

Point the monitor at `/health` rather than `/`. It is registered in
`routes/health.php` **without** the `web` middleware group, so it starts no
session and touches no database — meaning it keeps returning 200 even when the
database is broken, and a failure there points at PHP/Apache rather than at
SQLite. Verified: with `database.sqlite` removed, `/` returns 500 while
`/health` still returns 200.

Beanstalk, Laravel's built-in `/up` endpoint, is also available if you prefer a
bare 200 with no body.

### Logs
| What you want | Where in Webuzo |
|---|---|
| Apache/web errors for the domain | **Admin Panel → Logs → Domain Error Log** |
| Raw Apache access & error logs | **Admin Panel → Logs → Raw Logs** |
| PHP errors caught by Laravel | `storage/logs/laravel.log` — open via **File Manager** |
| Login attempts | **Enduser → Server Utilities → Login Logs** |
| Blocked / brute-force traffic | **Admin Panel → Logs → Brute Force Logs** |
| WAF blocks (if ModSecurity is on) | **Admin Panel → Logs → ModSecurity Logs** |

Watch `laravel.log` right after deploying; a permissions or `APP_KEY` problem
shows up there within seconds of the first request.

### Resource monitoring
| What you want | Where in Webuzo |
|---|---|
| Disk usage for the account | **Enduser → Server Utilities → Disk Usage** |
| Bandwidth / traffic | **Enduser → Server Utilities → Bandwidth** |
| Visitors & AWStats | **Enduser → Server Utilities → Visitors / AWStats** |
| Running processes | **Admin Panel → System Health → Process Manager** |
| Current disk usage (server-wide) | **Admin Panel → System Health → Current Disk Usage** |

### Routine management
| Task | Where |
|---|---|
| Change PHP version per domain | **Enduser → Configuration → MultiPHP Manager** |
| Tune `memory_limit`, `upload_max_filesize`, `max_execution_time` | **Enduser → Configuration → PHP INI Editor** |
| Enable/disable a PHP extension | **Enduser → Configuration → PHP Extensions** |
| Renew / view SSL | **Enduser → Domains → Manage Domains** |
| Back up the site + database | **Enduser → Server Utilities → Backup** |
| Browse/edit files | **Enduser → Server Utilities → File Manager** |
| Run commands without SSH | **Enduser → Server Utilities → Terminal** |
| Restart Apache / MySQL after a config change | **Admin Panel → Server Utilities** |

### Health check from the console

```bash
curl -I https://example.com/health          # expect 200
curl -s https://example.com/health          # expect {"status":"ok", ...}
```

Because `/health` is independent of the database, use it as a two-step
diagnostic:

| `/` | `/health` | Meaning |
|---|---|---|
| 200 | 200 | Everything working |
| 500 | 200 | PHP/Apache fine — the problem is the **database or `storage/`** |
| 500 | 500 | **PHP/Apache, routing or docroot** problem (check extension & Domain Path) |

## Troubleshooting

### Webuzo-specific

| Symptom | Cause / fix |
|---|---|
| `could not find driver` / `Database file does not exist` | `pdo_sqlite` + `sqlite3` not enabled. **Configuration → PHP Extensions**, enable them, apply. |
| `Composer detected issues... requires php >= 8.2` | Domain still on old PHP. **Configuration → MultiPHP Manager** → select domain → PHP 8.2+ → Apply. |
| 500 on every page, `storage/logs` empty | `storage/` or `bootstrap/cache/` not writable. `chmod -R 755` them and confirm ownership is your Webuzo user. |
| `/` works but `/about` 404s | Domain Path is set to the project root instead of `.../test-site/public`. Fix in **Domains → Manage Domains → Edit**. |
| `.env` or `vendor/` visible in browser | Same cause as above — docroot must be `public/`. Move it immediately and rotate `APP_KEY` and any DB credentials. |
| "You don't have permission to access this resource" (403) | Webuzo applies cPanel-style permissions. Ensure directories are `755` and files `644`; remove any `777`. |
| Cron job silently does nothing | Use the **absolute** PHP path in the cron command, and check the cron email output. |
| Changes to `.env` ignored | A cached config. Run `php artisan config:clear` then re-cache. |

### General

| Symptom | Cause / fix |
|---|---|
| Blank white page | `APP_DEBUG=true` temporarily and read the error; check `storage/logs/laravel.log`. |
| 500 after deploy | Almost always `APP_KEY` missing (`php artisan key:generate`) or unwritable `storage/`. |
| Slow first load | Normal with `config:cache`/`route:cache` not yet run — do step 7. |

**Never enable `APP_DEBUG=true` on a public site** — Laravel's debug page
exposes environment variables and file paths.
