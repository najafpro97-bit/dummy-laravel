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
The project already lives in `C:\xampp\htdocs\test web laravel`, so you can also
just browse to `http://localhost/test%20web%20laravel/public`.

## Deploy to a VPS

### 1. Requirements on the server
```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-cli php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-sqlite3 php8.2-zip unzip git
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

### 5. Nginx site config
`/etc/nginx/sites-available/test-site`:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/test-site/public;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```
Enable and reload:
```bash
sudo ln -s /etc/nginx/sites-available/test-site /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 6. Permissions for cache (optional hardening)
```bash
sudo chown -R www-data:www-data /var/www/test-site
```

### 7. SSL (optional but recommended)
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

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
