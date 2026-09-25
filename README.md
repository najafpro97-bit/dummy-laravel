`git i# Dummy Test Site (Laravel)

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

## Deploy to a VPS

### 1. Requirements on the server
```bash
sudo apt update
sudo apt install -y apache2 libapache2-mod-php php8.2-cli php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip unzip git
```
Enable the required Apache modules (rewrite is what Laravel needs for clean URLs):
```bash
sudo a2enmod rewrite
sudo a2enmod headers
```
Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Upload the code
```bash
cd /var/www
sudo git clone <your-repo-url> test-site   # or scp the folder up
cd test-site
composer install --no-dev --optimize-autoloader
```

### 3. Configure
```bash
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
APP_URL=https://your-domain.com
```

### 4. Permissions
```bash
sudo chown -R www-data:www-data storage bootstrap/cache database
sudo chmod -R 775 storage bootstrap/cache database
```

### 5. Apache site config
Create `/etc/apache2/sites-available/test-site.conf`:
```apache
<VirtualHost *:80>
    ServerName your-domain.com
    ServerAlias www.your-domain.com
    DocumentRoot /var/www/test-site/public

    <Directory /var/www/test-site/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Block access to dotfiles (.env, .git, etc.)
    <FilesMatch "^\.">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/test-site-error.log
    CustomLog ${APACHE_LOG_DIR}/test-site-access.log combined
</VirtualHost>
```

> `AllowOverride All` is essential — it lets Laravel's bundled `public/.htaccess`
> route requests through `index.php`. If `mod_rewrite` is off or
> `AllowOverride` is `None`, every page except `/` returns a 404.

Enable the site and disable the default one:
```bash
sudo a2ensite test-site.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### 6. Permissions for cache (optional hardening)
```bash
sudo chown -R www-data:www-data /var/www/test-site
```

### 7. SSL (optional but recommended)
```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```
Certbot installs the certificate and adds the HTTPS virtual host for you.

### 8. Cache for speed (after everything works)
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Re-run `php artisan cache:clear && php artisan config:clear` any time you
> change `.env`.

## Verify the deployment
```bash
curl -I https://your-domain.com          # expect 200
curl https://your-domain.com/health      # expect {"status":"ok", ...}
```

## Apache troubleshooting

| Symptom | Cause / fix |
|---|---|
| `/` works but `/about` returns 404 | `mod_rewrite` disabled, or `AllowOverride None`. Run `sudo a2enmod rewrite` and set `AllowOverride All`, then reload Apache. |
| Page shows PHP source code or downloads | `libapache2-mod-php` not installed/enabled. Run `sudo a2enmod php8.2` and reload. |
| 403 Forbidden on every request | Directory permissions or `Require all granted` missing from the vhost. |
| Blank page / 500 | Check `/var/log/apache2/test-site-error.log` and `storage/logs/laravel.log`. Usually `storage`/`bootstrap/cache` permissions or a wrong `APP_KEY`. |
| `.env` viewable in the browser | `DocumentRoot` points at the project root instead of `.../public`. |

Confirm the active modules with `apache2ctl -M | grep -E 'rewrite|php'`.
