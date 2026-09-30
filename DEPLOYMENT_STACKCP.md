# StackCP / cPanel Deployment Guide — Vital Petroleum ERP
**Station:** Vital Petroleum &bull; Mehar Filling Station, Sheikhupura  
**Framework:** Laravel 12 &bull; PHP 8.3 &bull; Livewire 3 &bull; MySQL 8.0+  
**Target Environment:** StackCP / 20i / cPanel Shared or Managed Cloud Hosting  

---

## 1. Architectural Overview & Security Layout

For shared and managed cloud environments such as StackCP, security best practice dictates that all core application source code, configuration files, vendor libraries, and database credentials remain **outside the public web document root** (`public_html`). Only public assets, fonts, compiled CSS/JS, and the entry `index.php` are placed inside `public_html`.

### Target Directory Layout
```text
/home/username/
├── petrol-pump-erp/                  <-- Core Laravel root (outside web access)
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   │   ├── app/
│   │   │   ├── backups/             <-- SQL.GZ and ZIP archives (protected)
│   │   │   └── public/
│   │   ├── framework/
│   │   │   ├── cache/
│   │   │   ├── sessions/
│   │   │   └── views/
│   │   └── logs/
│   ├── vendor/
│   ├── .env                         <-- Production credentials (never public)
│   └── artisan
│
└── public_html/                      <-- StackCP Web Root
    ├── .htaccess                    <-- URL rewrite rules & security headers
    ├── index.php                    <-- Front controller pointing to core
    ├── build/                       <-- Compiled Tailwind CSS and Vite assets
    ├── favicon.ico
    ├── robots.txt
    └── storage/                     <-- Symlink to ../petrol-pump-erp/storage/app/public
```

---

## 2. Server Requirements & PHP Extensions

Ensure the domain is configured on StackCP with **PHP 8.3** (or higher) with the following extensions active:

| Extension | Purpose in Vital Petroleum ERP |
|---|---|
| `bcmath` | **Mandatory** for high-precision currency (`DECIMAL(14,2)`) and litre (`DECIMAL(12,3)`) computations |
| `pdo_mysql` | Database connectivity with MySQL / MariaDB |
| `ctype`, `mbstring` | String manipulation and UTF-8 Urdu character rendering |
| `zlib` | Gzip compression for the pure-PHP chunked database backup engine |
| `zip` | Full system backup archive generation (`ZipArchive`) |
| `openssl` | Signed temporary download URLs, password hashing, and encrypted cookies |
| `fileinfo` | MIME detection for uploaded supplier invoices and fuel tanker bills |
| `dom`, `xml` | PDF generation (`barryvdh/laravel-dompdf`) for invoice & 22 core reports |
| `curl` | Outbound SMS and email delivery to station owners |

### Recommended `php.ini` Settings
In StackCP **PHP Configuration**:
```ini
max_execution_time = 300
memory_limit = 512M
post_max_size = 64M
upload_max_filesize = 32M
date.timezone = "Asia/Karachi"
```

---

## 3. Step-by-Step Deployment Procedure

### Step 3.1: Upload Application Files
1. SSH into the StackCP account or use SFTP.
2. Create the directory `/home/username/petrol-pump-erp`.
3. Upload all project files to `/home/username/petrol-pump-erp` (excluding `.git`, `node_modules`, and local `.env`).
4. Ensure files inside `public/` are copied to `/home/username/public_html/`.

### Step 3.2: Configure `public_html/index.php`
Edit `/home/username/public_html/index.php` to reference the core application outside `public_html`:

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/../petrol-pump-erp/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Composer autoloader
require __DIR__.'/../petrol-pump-erp/vendor/autoload.php';

// Bootstrap Laravel application
(require_once __DIR__.'/../petrol-pump-erp/bootstrap/app.php')
    ->handleRequest(Request::capture());
```

### Step 3.3: Storage Symlink
In SSH terminal:
```bash
ln -s /home/username/petrol-pump-erp/storage/app/public /home/username/public_html/storage
```
*If SSH is not available on your StackCP package, create a small temporary PHP script in `public_html/symlink.php`:*
```php
<?php
symlink('/home/username/petrol-pump-erp/storage/app/public', __DIR__ . '/storage');
echo "Symlink created";
```
*Run it once in the browser and delete the script immediately.*

### Step 3.4: File & Directory Permissions
Set permissions to ensure the web server (`nobody` or `www-data`) and CLI user have write access:
```bash
cd /home/username/petrol-pump-erp
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
find storage/ -type d -exec chmod 775 {} \;
find storage/ -type f -exec chmod 664 {} \;
```

---

## 4. Database Setup & Production `.env`

### Step 4.1: Create MySQL Database in StackCP
1. Navigate to **StackCP Control Panel > Database Management > MySQL Databases**.
2. Create database: `username_petrol_erp` (Collation: `utf8mb4_unicode_ci`).
3. Create user: `username_erpuser` with a strong password.
4. Grant **ALL PRIVILEGES** to the user on `username_petrol_erp`.

### Step 4.2: Configure `.env` File
Create `/home/username/petrol-pump-erp/.env`:
```env
APP_NAME="Vital Petroleum - Mehar Filling Station"
APP_ENV=production
APP_KEY=base64:... # Generate via php artisan key:generate
APP_DEBUG=false
APP_URL=https://meharfilling.com
APP_TIMEZONE="Asia/Karachi"

LOG_CHANNEL=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_petrol_erp
DB_USERNAME=username_erpuser
DB_PASSWORD=YourSecureProductionPassword!2026

BROADCAST_CONNECTION=log
CACHE_STORE=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Mail delivery for Daily Closing Summary
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=postmaster@meharfilling.com
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="owner@meharfilling.com"
MAIL_FROM_NAME="Mehar Filling Station"
```

### Step 4.3: Migrate & Seed Database
In SSH:
```bash
cd /home/username/petrol-pump-erp
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --force
```

### Step 4.4: Cache Configurations and Routes
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

---

## 5. Cron Job Configuration (Automated Tasks)

StackCP supports cron jobs via the **Cron Jobs** or **Scheduled Tasks** menu. Add the following entry to trigger Laravel's schedule runner every minute:

```bash
* * * * * cd /home/username/petrol-pump-erp && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks Executed by Vital ERP:
1. **Daily Auto-Backup:** Pure-PHP SQL database dump taken at 23:45 PKT and stored in `storage/app/backups/`.
2. **Shift Reconciliation Alert:** Checks forecourt shifts older than 12 hours that remain open.
3. **Daily Closing Reminder:** Dispatches prompt to station manager if physical dip entries are pending.
4. **Log Pruning:** Rotates audit and error logs older than 30 days.

---

## 6. Web Server Security (`.htaccess`)

Verify that `/home/username/public_html/.htaccess` contains:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Block access to hidden files (.env, .git)
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
```

---

## 7. Post-Deployment Verification Checklist

- [ ] HTTPS lock active and certificate valid.
- [ ] Login screen renders with Vital Petroleum branding (`#D71920`).
- [ ] Role-based authentication tests pass (Admin, Manager, Cashier).
- [ ] Forecourt POS loads active dispensers, nozzles, and fuel prices.
- [ ] Test sale completes with thermal receipt printed and GL entry posted.
- [ ] Daily Closing Wizard opens and verifies 4 checklist steps.
- [ ] Backup system creates `.sql.gz` dump without shell/mysqldump binary dependency.
- [ ] All 22 reports generate data with date ranges and export to PDF/CSV.
