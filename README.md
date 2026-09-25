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
- Database: **MySQL** (database `test_site`, tables created by `php artisan migrate`)
- Views: `resources/views/layouts/app.blade.php`, `home.blade.php`, `about.blade.php`
- Routes: `routes/web.php` (+ `routes/health.php`, mounted outside the session middleware)

## Run locally

You need MySQL (or MariaDB) running and an empty database in place **before**
the last two commands. Create it once:

```sql
-- in the mysql client, phpMyAdmin, or Adminer
CREATE DATABASE IF NOT EXISTS test_site
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Then:

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

> **Target server:** this guide is written for a Webuzo VPS at
> **`101.50.1.15`**.
>
> Paths in this section use `myuser` as your Webuzo account username — replace
> it with the real one. Wherever you see `101.50.1.15`, keep it as-is unless you
> are moving to a different server or a real domain name.
>
> **Read step 5 carefully:** a bare IP like `101.50.1.15` cannot serve the app
> from `public/`, because Webuzo only manages *named* virtual hosts. You will
> need either a real domain or a hosts-file entry. Step 5 covers both.

### 0. Make sure the server is actually reachable

Before anything else, confirm Webuzo is answering on `101.50.1.15` and that
ports 80/443 are open — fix this first, or every later step looks broken:

```bash
# from your own computer
ping 101.50.1.15
curl -I http://101.50.1.15/          # expect a response (Webuzo default page is fine for now)
```

If `ping` works but `curl` hangs or is refused:

- **Cloud firewall / security group** (DigitalOcean, AWS, Vultr, Hetzner, …):
  allow inbound TCP **80** and **443**. This is the single most common cause.
- **Server firewall** (`ufw`, `firewalld`, `iptables`): allow 80/443.
- Confirm Apache is running: **Admin Panel → Server Utilities**, or
  `systemctl status httpd` over SSH.

At this point you will see Webuzo's default page, not your app — that is
expected, and step 5 is what changes it.

### 1. Requirements

**Log in to Webuzo → Enduser Panel → Configuration → PHP Extensions**, select
the PHP version you will use (8.2 or newer), and enable these — they are **not**
all on by default on Webuzo and the app will not run without them:

| Extension | Why |
|---|---|
| `pdo_mysql` | **Required** — this app uses a MySQL/MariaDB database |
| `mysqli` | **Recommended** — needed by some MySQL tooling and phpMyAdmin |
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
`/home/myuser/test-site`. **Run these as your Webuzo account user, not as
`root`** — if you only have `root` and are unsure how, jump to
[step 2.5](#25-only-have-root-how-to-run-composer).

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

### 2.5 Only have root? How to run Composer

You ran `composer install`, and something told you **not to run Composer as
root**. You only have a root login, so it feels like a dead end. It is not.

**The short answer: you are logged into the wrong panel, not missing a user.**

Webuzo gives you **two separate logins** on the same server:

| | Root / Admin Panel | Enduser Panel |
|---|---|---|
| URL | `http://101.50.1.15:2002/` | `http://101.50.1.15:2002/enduser/` |
| Log in as | `root` | your **hosting account** username |
| For | server-wide settings | managing **one** site |

"Don't run Composer as root" does **not** mean you need `sudo` or a trick to
drop privileges — it means *do the app work in the **Enduser Panel***, where you
already are a normal, unprivileged user. Your root login is correct for
administering the server; it is simply the wrong place to build the app.

#### Step-by-step fix

**1. Find (or set) your hosting account's password.** In the **Admin Panel**:
**Endusers → List Users** → **Edit** next to your account → set a password →
save. Note the **username** (e.g. `myuser`).

> This is *not* weakening root or opening a hole. You are setting a password for
> the site's own account — which is the account you are supposed to use here.

**2. Log out and log into the Enduser Panel** at
`http://101.50.1.15:2002/enduser/` using that username and new password.

**3. Open Enduser Panel → Server Utilities → Terminal**, and **verify who you
are before doing anything else**:

```bash
whoami          # MUST print your account, e.g. myuser  → NOT "root"
id              # MUST NOT contain uid=0(root)
```

If `whoami` prints `root`, you are still in the Admin Panel. Go back to step 2.

**4. Now run the commands from step 2 normally — no `sudo`, ever:**

```bash
cd /home/myuser/test-site
composer install --no-dev --optimize-autoloader
```

Composer needs no special privileges. It only writes into
`/home/myuser/test-site`, which your account already owns, so file ownership
comes out correct automatically.

#### If you can only reach the server over SSH as `root`

Don't build the app as root — **switch to the site's user** for app commands:

```bash
whoami              # root
su - myuser         # become the hosting account; no password needed as root
whoami              # myuser  ← now correct
cd ~/test-site
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
exit                # back to root when finished
whoami              # root
```

Rule of thumb: run **`composer` and `php artisan` as `myuser`**; keep **`root`**
for server administration (restarting Apache/MySQL, firewall rules, system
packages).

#### What if no hosting account exists at all?

Then this is not a Webuzo *hosting* setup and you should create the account
rather than run the app as root — **Admin Panel → Endusers → Add User**, set a
username, password and domain. Use that account for everything from step 2
onwards.

#### Why `sudo composer` really does break things

This is not just style advice:

1. `sudo composer install` creates `vendor/`, and later `storage/`, owned by
   **root** — but Apache serves your site as **`myuser`**.
2. Apache then **cannot write** to `storage/logs/` or `storage/framework/`,
   which gives you **HTTP 500 on every single page**, with a misleading
   "unwritable"/"permission denied" message that is hard to trace.
3. Composer plugins **execute arbitrary code**. Run as root, one compromised or
   malicious package gets **full control of your VPS** — beyond just this site.

If you already ran `sudo composer install`, the recovery command is in
[step 4](#4-permissions):

```bash
chown -R myuser:myuser /home/myuser/test-site
```

### 3. Create the database and configure

**First, create the database in Webuzo.** Go to Enduser Panel →
**Database Management → MySQL / Database** and create a database. Webuzo
prefixes everything with your account username, so if your account is `myuser`
and you name the database `test_site`, the real name becomes `myuser_test_site`.
Create a database user at the same time and note the password.

*(If you prefer SQL, the panel runs this for you — but the raw equivalent is
shown in [Appendix: raw SQL](#appendix-raw-sql) at the end of this file.)*

Then configure the app. Run these as your Webuzo account user — **not** as
`root`. If that sentence is the thing blocking you, read
[step 2.5](#25-only-have-root-how-to-run-composer) first:

```bash
cd /home/myuser/test-site
cp .env.example .env
php artisan key:generate
php artisan migrate --force
```

Edit `.env` for production, including the database values **Webuzo gave you**
(note the `myuser_` prefix on the database name):

```env
APP_NAME="Dummy Test Site"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://101.50.1.15

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=myuser_test_site
DB_USERNAME=myuser_dbuser
DB_PASSWORD=the-password-you-set-in-the-panel
```

> Set `APP_URL` to how visitors actually reach the site. For a bare IP with no
> domain that is `http://101.50.1.15`; switch it to `https://101.50.1.15` after
> enabling SSL (step 6), or to `https://yourdomain.com` if you point a domain at
> the server. A wrong `APP_URL` does not break the pages — it produces wrong
> links in emails, redirects and asset URLs, so it is easy to miss.
>
> `APP_DEBUG=false` matters here: with debug on, an error page leaks your
> `.env`, database credentials and file paths to anyone who visits.
>
> If the password contains `#`, `"` or spaces, wrap it in quotes:
> `DB_PASSWORD="p#ss word"`.

### 4. Permissions

The files must be owned by **your Webuzo account user**, not `www-data`:

```bash
cd /home/myuser/test-site
chmod -R 755 storage bootstrap/cache
```

Ownership is normally already correct because you uploaded as that user. If you
ran Composer as `root` (or `sudo`) anywhere — see
[step 2.5](#25-only-have-root-how-to-run-composer) for why that causes
500 errors — fix it now (use your real username):

```bash
chown -R myuser:myuser /home/myuser/test-site
```

> If you have only a `root` SSH login, run app commands as the site's user
> instead of building as root: `su - myuser`, do the work, then `exit`. Full
> walkthrough in [step 2.5](#25-only-have-root-how-to-run-composer).

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

> **⚠️ You cannot do this with the bare IP `101.50.1.15`.**
>
> Webuzo's **Domains** section manages *named* virtual hosts. A raw IP is served
> by Apache's **default vhost**, which Webuzo does not let you re-point to
> `public/` from the panel. If you configure nothing else, opening
> `http://101.50.1.15/` will show Webuzo's own default page (or a directory
> listing / the un-parsed Laravel source), **not** your app.
>
> Pick one of these three options:
>
> **Option 1 — Point a real domain at the server (recommended for production).**
> Add an `A` record for e.g. `demo.yourdomain.com` → `101.50.1.15`, then add
> that domain in Webuzo and set its Domain Path as above. This is the only
> option that also gives you working Let's Encrypt SSL.
>
> **Option 2 — Use a hosts-file override for testing (no domain needed).**
> Add a fake name in Webuzo (e.g. `test-site.local`), set its Domain Path to
> `public/`, and on *your own* computer add this to your hosts file
> (`C:\Windows\System32\drivers\etc\hosts`, or `/etc/hosts`):
> ```
> 101.50.1.15   test-site.local
> ```
> Then browse to `http://test-site.local/`. The name resolves only on machines
> you configure, which is fine for a dummy test. **You cannot get an SSL
> certificate for this** — Let's Encrypt will not issue for a name it cannot
> resolve publicly.
>
> **Option 3 — Serve the IP with a temporary root-level `.htaccess`.**
> Only if you must reach the app by IP. Since Webuzo's default docroot is not
> `public/`, the app must live *inside* the docroot, and `.htaccess` rewrites
> requests into it. This is a workaround, not a clean deployment — it exposes
> the project directory to Apache and any bypass of the rewrite could leak
> `.env`. Use Option 1 or 2 for anything real.
>
> If you are unsure, use **Option 2** to prove the app runs, then move to
> **Option 1** for the real thing.

> If **Domain Path** cannot be changed, the domain is your account's *primary*
> domain (Webuzo locks that field for the primary domain). Fix it by adding a
> subdomain or addon domain for the site, setting its path, or ask your host to
> adjust the primary domain's docroot.

### 6. SSL

Don't install Certbot — Webuzo handles Let's Encrypt.

**You need a real, publicly-resolvable domain name for this** (Option 1 in
step 5). Let's Encrypt validates by connecting to your hostname, so it will
**fail** for a bare IP like `101.50.1.15` and for a hosts-file name like
`test-site.local`. If you are still on Option 2, skip this step for now — the
site works fine over plain HTTP for testing.

With a domain in place: when adding or editing the domain, tick **Let's Encrypt
Certificate**. Webuzo issues it and writes the HTTPS vhost automatically.
Re-check **Domains → Manage Domains** afterwards to confirm the certificate is
listed and valid.

Once SSL is active, update `APP_URL` in `.env` to `https://…` and re-run
`php artisan config:cache`.

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
(UptimeRobot, Better Uptime, a Webuzo cron + mail alert, etc.).

Use the **same hostname you set up in step 5** — the health check is on the
same vhost as the site, so if `http://101.50.1.15/health` returns Webuzo's
default page rather than JSON, your docroot is not pointing at `public/` yet
(re-read step 5):

```
http://test-site.local/health        # Option 2 (hosts-file name)
https://demo.yourdomain.com/health   # Option 1 (real domain + SSL)
```

It returns HTTP 200 with:
```json
{"status":"ok","app":"Dummy Test Site","env":"production","php":"8.2.x","laravel":"12.x","host":"...","time":"..."}
```

Point the monitor at `/health` rather than `/`. It is registered in
`routes/health.php` **without** the `web` middleware group, so it starts no
session and touches no database — meaning it keeps returning 200 even when the
database is broken, and a failure there points at PHP/Apache rather than at
MySQL.

**Verified by stopping MySQL entirely:** `/` returned **500** (it needs the
database for its session), while `/health` still returned **200**. The latency
log shows the contrast starkly — `/health` answered in ~0.2 ms while `/` hung
for 9–11 seconds waiting on a dead TCP connection to the database. Use `/health`
for uptime monitoring so that a database outage does not look like a PHP outage.

Laravel's built-in `/up` endpoint is also available if you prefer a bare 200
with no body. (It is registered as `health: '/up'` in `bootstrap/app.php`.)

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
curl -I http://101.50.1.15/health          # expect 200
curl -s http://101.50.1.15/health          # expect {"status":"ok", ...}
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
| `http://101.50.1.15/` shows Webuzo's default page, not the app | Expected — a bare IP is served by Apache's default vhost. Do **step 5** (use a domain or a hosts-file name). |
| `http://101.50.1.15/` shows a directory listing or raw PHP source | Docroot is not `public/`. Never leave it like this — `.env` may be downloadable. Apply step 5 immediately, then rotate `APP_KEY` and DB passwords. |
| `ping 101.50.1.15` works, but HTTP times out | Firewall blocking port 80. Open 80/443 in your **cloud security group** *and* the server firewall. See step 0. |
| Everything 404s except the homepage | `.htaccess` rewrite not applied — `AllowOverride All` is not in effect for the vhost, so `mod_rewrite` rules in `public/.htaccess` are ignored. |
| `could not find driver` | `pdo_mysql` not enabled. **Configuration → PHP Extensions**, enable `pdo_mysql` (and `mysqli`), apply. |
| You only have `root`, and are told not to use `sudo` | You are in the wrong panel. Log into the **Enduser Panel** (`/enduser/`) with the hosting account username — full walkthrough in [step 2.5](#25-only-have-root-how-to-run-composer). |
| 500s after a `sudo composer install` | `vendor/`/`storage/` now owned by root, so Apache cannot write. Fix: `chown -R myuser:myuser /home/myuser/test-site`. See [step 2.5](#25-only-have-root-how-to-run-composer). |
| Terminal shows `whoami` = `root` when following the guide | You opened the Admin Panel terminal. Switch to the Enduser Panel so files get the right owner — see [step 2.5](#25-only-have-root-how-to-run-composer). |
| `Access denied for user '...'@'localhost'` | Wrong `DB_USERNAME`/`DB_PASSWORD` in `.env`, or the user was not granted rights on **this** database. Re-check in **Database Management**; remember the `myuser_` prefix on both the database and user name. |
| `Unknown database 'test_site'` | The database does not exist, or you used the un-prefixed name. On Webuzo the real name is `myuser_test_site` — set `DB_DATABASE` to that. |
| `Connection refused` / `SQLSTATE[HY000] [2002]` | MySQL/MariaDB is not running, or `DB_HOST` is wrong. On a Webuzo box the database is almost always `127.0.0.1`; do **not** use `localhost` if the socket path differs. |
| `SQLSTATE[HY000] [1045]` on `/` but `/health` is 200 | Exactly the split the health check is designed to reveal: PHP/Apache are fine, the database credentials are the problem. |
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

## Appendix: raw SQL

You do **not** need this section to deploy — `php artisan migrate --force` is the
supported, preferred way to build the schema, and it is what keeps your database
in sync with future migrations. Use the SQL below only when you *cannot* run
artisan (for example a locked-down host, or you want to seed a database by hand
from a control-panel SQL box).

### Creating the database

On Webuzo, prefer the panel: **Enduser Panel → Database Management** creates the
database *and* the user *and* applies the grants for you, which avoids the
prefix and permission mistakes that cause most `Access denied` errors. If you do
have SQL access and want the raw equivalent:

```sql
-- Replace myuser with your Webuzo account username. Webuzo enforces the
-- prefix, so the real database name is myuser_test_site.
CREATE DATABASE IF NOT EXISTS `myuser_test_site`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Password in single quotes. '%' allows connections from any host;
-- use 'localhost' instead for a stricter, same-server-only grant.
CREATE USER IF NOT EXISTS 'myuser_dbuser'@'localhost'
  IDENTIFIED BY 'a-strong-password-here';

GRANT ALL PRIVILEGES ON `myuser_test_site`.* TO 'myuser_dbuser'@'localhost';

FLUSH PRIVILEGES;
```

Then point `.env` at those exact values (`DB_DATABASE=myuser_test_site`,
`DB_USERNAME=myuser_dbuser`, `DB_PASSWORD=...`).

### Table definitions

The following DDL is **not hand-written** — it was produced by running
`php artisan migrate` against MySQL/MariaDB and then dumping the result with
`mysqldump --no-data`. So it matches, byte for byte, whatever Laravel itself
would create. `utf8mb4` / `utf8mb4_unicode_ci` and `InnoDB` are the defaults
Laravel uses, and are what you want on Webuzo.

If you create the tables manually, also create the `migrations` table and its
rows — otherwise artisan will try to re-run every migration on top of your
tables and fail with "table already exists".

```sql
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only needed if you are creating tables by hand (see note above).
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Verifying the schema

```bash
# list the tables Laravel created
mysql -u myuser_dbuser -p myuser_test_site -e "SHOW TABLES;"

# confirm the database connection and current migration state
php artisan db:show
php artisan migrate:status
```

You should see nine tables: `cache`, `cache_locks`, `failed_jobs`,
`job_batches`, `jobs`, `migrations`, `password_reset_tokens`, `sessions`, and
`users`. If `sessions` is missing you will get 500s on every page, because
`SESSION_DRIVER=database` is the default.
