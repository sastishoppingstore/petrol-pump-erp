# Mehar ERP v4 — StackCP Deployment Guide

## Overview

Mehar ERP v4 is a **Laravel 12** + **MySQL 8** fuel station management system. This guide covers deployment on **StackCP** (Linux VPS with cron, backups, queue workers).

---

## 1. Server Requirements

- **OS:** Ubuntu 22.04 LTS or newer
- **PHP:** 8.2+ (with bcmath, PDO MySQL, GD, XML extensions)
- **MySQL:** 8.0+ (InnoDB, utf8mb4)
- **Web Server:** Nginx or Apache
- **Node.js:** 18+ (for asset building)
- **Redis:** Optional (for caching, sessions, queues)

### Install Base Packages

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-bcmath \
  php8.2-gd php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring \
  mysql-server mysql-client nginx git curl nodejs npm
```

---

## 2. Application Setup

### Clone repository

```bash
cd /var/www
git clone https://github.com/your-org/mehar-erp.git
cd mehar-erp
```

### Install dependencies

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### Configure environment

```bash
cp .env.example .env

# Edit .env with production values
nano .env
```

**Critical .env settings:**

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:XXXXXXXXXX  # Run: php artisan key:generate

DB_HOST=127.0.0.1
DB_DATABASE=mehar_erp_prod
DB_USERNAME=erpuser
DB_PASSWORD=SecurePassword123!

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=cookie

MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-email@company.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@company.com
```

### Setup database

```bash
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
```

### Create app user (non-root)

```bash
sudo useradd -m -s /bin/bash erpadmin
sudo chown -R erpadmin:erpadmin /var/www/mehar-erp
```

---

## 3. Web Server (Nginx)

### Configure Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/mehar-erp/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
    }
    
    location ~* /storage/ {
        deny all;
    }

    location ~* /(config|.env|.git)/ {
        deny all;
    }
}
```

Enable and restart:

```bash
sudo ln -s /etc/nginx/sites-available/mehar-erp /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

### SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot certonly --nginx -d yourdomain.com
```

Update Nginx to use SSL (add `listen 443 ssl http2;` and `ssl_certificate` directives).

---

## 4. Cron Jobs (Scheduler)

### Laravel cron entry

Add to `/etc/crontab` or user crontab:

```bash
* * * * * cd /var/www/mehar-erp && php artisan schedule:run >> /dev/null 2>&1
```

This runs Laravel's scheduler every minute, which then triggers:

- **23:30 daily:** `erp:daily-closing` — Run end-of-day reconciliation
- **02:00 daily:** `erp:backup` — Backup database (retention: 7 days)
- **Every hour:** `erp:sync-notifications` — Update low-stock, overdue, variance alerts
- **Weekly:** `erp:notifications-cleanup` — Clean old read notifications

### Manual cron commands

```bash
# Test daily closing for branch 1 today
php artisan erp:daily-closing --branch=1

# Test backup
php artisan erp:backup --retention=7

# Test notification sync
php artisan erp:sync-notifications
```

---

## 5. Database User & Permissions

### Create MySQL user

```sql
CREATE USER 'erpuser'@'localhost' IDENTIFIED BY 'SecurePassword123!';
GRANT CREATE, ALTER, DROP, INSERT, UPDATE, DELETE, SELECT, REFERENCES, 
      LOCK TABLES, EXECUTE, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, 
      ALTER ROUTINE, TRIGGER ON mehar_erp_prod.* TO 'erpuser'@'localhost';
FLUSH PRIVILEGES;
```

### Backup access

```bash
sudo usermod -a -G mysql erpadmin
```

---

## 6. Backups & Recovery

### Location

Backups stored in: `/var/www/mehar-erp/storage/backups/`

### Manual backup

```bash
php artisan erp:backup --retention=30
```

### Restore from backup

```bash
gunzip storage/backups/database_2026-10-05_02-00-00.sql.gz
mysql -u erpuser -p mehar_erp_prod < storage/backups/database_2026-10-05_02-00-00.sql
```

### External backup (daily to S3)

Add to crontab:

```bash
0 3 * * * cd /var/www/mehar-erp && php artisan storage:link && aws s3 sync storage/backups/ s3://your-bucket/backups/ --delete >> /dev/null 2>&1
```

---

## 7. Redis (Optional but Recommended)

```bash
sudo apt install redis-server -y
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

Update `.env`:

```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

---

## 8. Queue Workers (Background Jobs)

For email, reports, exports:

```bash
# Start worker (foreground for testing)
php artisan queue:work --timeout=3600 --tries=3

# Supervisor daemon (production)
sudo apt install supervisor -y
```

Create `/etc/supervisor/conf.d/mehar-worker.conf`:

```ini
[program:mehar-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/mehar-erp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/mehar-queue.log
```

Enable:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start mehar-queue-worker:*
```

---

## 9. Monitoring & Logs

### Application logs

```bash
tail -f storage/logs/laravel.log
```

### PHP-FPM logs

```bash
tail -f /var/log/php8.2-fpm.log
```

### Nginx logs

```bash
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

### Cron logs

```bash
grep CRON /var/log/syslog | tail -20
```

---

## 10. Security Hardening

### File permissions

```bash
sudo chown -R www-data:www-data /var/www/mehar-erp
sudo chmod -R 755 /var/www/mehar-erp
sudo chmod -R 775 /var/www/mehar-erp/storage
sudo chmod -R 775 /var/www/mehar-erp/bootstrap/cache
```

### Firewall

```bash
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
```

### `.env` protection

```bash
sudo chmod 600 /var/www/mehar-erp/.env
sudo chown root:www-data /var/www/mehar-erp/.env
```

### Disable directory listing

Already configured in Nginx.

### HTTPS redirect

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 11. Monitoring & Health Checks

### Uptime monitoring

```bash
# Test health endpoint
curl https://yourdomain.com/health
```

Expected response:

```json
{"status":"ok","database":"up","cache":"up"}
```

### Automated monitoring (e.g., StatusCake, Uptime Robot)

Add endpoint: `https://yourdomain.com/health`

---

## 12. Troubleshooting

| Problem | Solution |
|---------|----------|
| **502 Bad Gateway** | Check PHP-FPM: `sudo systemctl restart php8.2-fpm` |
| **Database connection fails** | Verify MySQL: `mysql -u erpuser -p mehar_erp_prod` |
| **Cron not running** | Check crontab: `crontab -l` and logs: `grep CRON /var/log/syslog` |
| **Storage not writable** | Fix permissions: `sudo chmod -R 775 storage/` |
| **Old backups not deleted** | Check script: `php artisan erp:backup --retention=7` |
| **Queue jobs stuck** | Restart workers: `sudo supervisorctl restart mehar-queue-worker:*` |

---

## 13. Production Checklist

- [ ] `.env` configured with production values
- [ ] `APP_DEBUG=false` and `APP_KEY` set
- [ ] Database migrated and seeded (`ProductionSeeder`)
- [ ] SSL certificate installed
- [ ] Nginx configured and restarted
- [ ] Cron job added to `/etc/crontab`
- [ ] Backups tested (`php artisan erp:backup`)
- [ ] Queue workers running (if using)
- [ ] Redis configured (if using)
- [ ] Logs monitored (check `/var/log/nginx/`, `/var/log/php8.2-fpm.log`, `storage/logs/laravel.log`)
- [ ] Health endpoint verified (`/health`)
- [ ] Firewall configured
- [ ] File permissions hardened
- [ ] Database user with minimal privileges created

---

## 14. Post-Deployment

### First login

1. Go to `https://yourdomain.com/login`
2. Email: `admin@company.com` (from seeder)
3. Password: `password` (from seeder) — **Change immediately**

### First-run wizard

1. Settings → Company → Add branches, fuel products, banks
2. Users → Create managers, cashiers, accountants
3. POS → Configure nozzles, tanks, dispensers
4. Accounting → Set up chart of accounts

---

**Questions?** Contact: `support@mehar-erp.com`
