# StackCP Quick Deploy Script

StackCP پر 15 منٹ میں deploy کریں۔

## Prerequisites

- StackCP account + VPS (Ubuntu 22.04+)
- SSH access
- Domain name (optional, can use IP)

## Deploy Steps

### 1. SSH into VPS

```bash
ssh root@your-vps-ip
```

### 2. Run automated setup script

```bash
curl -fsSL https://raw.githubusercontent.com/your-org/mehar-erp/main/scripts/deploy-stackcp.sh | bash
```

**Or manually:**

### 3. Update system

```bash
apt update && apt upgrade -y
```

### 4. Install packages

```bash
apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-bcmath \
  php8.2-gd php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring \
  mysql-server mysql-client nginx git curl nodejs npm
```

### 5. Clone application

```bash
cd /var/www
git clone https://github.com/your-org/mehar-erp.git mehar-erp
cd mehar-erp
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 6. Configure

```bash
cp .env.example .env
# Edit with your settings
nano .env
```

**Minimum config:**

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_KEY_HERE

DB_DATABASE=mehar_prod
DB_USERNAME=erpuser
DB_PASSWORD=ChangeMe123!
```

### 7. Database setup

```bash
mysql -u root -p << EOF
CREATE DATABASE mehar_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erpuser'@'localhost' IDENTIFIED BY 'ChangeMe123!';
GRANT ALL PRIVILEGES ON mehar_prod.* TO 'erpuser'@'localhost';
FLUSH PRIVILEGES;
EOF

php artisan migrate --force
php artisan db:seed --class=ProductionSeeder
```

### 8. Web server (Nginx)

```bash
# Copy Nginx config
cp docs/nginx.conf /etc/nginx/sites-available/mehar-erp
ln -s /etc/nginx/sites-available/mehar-erp /etc/nginx/sites-enabled/

# Test and enable
nginx -t
systemctl restart nginx
```

### 9. SSL (Let's Encrypt)

```bash
apt install -y certbot python3-certbot-nginx
certbot certonly --nginx -d yourdomain.com
```

### 10. Add cron scheduler

```bash
(crontab -l 2>/dev/null; echo "* * * * * cd /var/www/mehar-erp && php artisan schedule:run >> /dev/null 2>&1") | crontab -
```

### 11. Permissions

```bash
chown -R www-data:www-data /var/www/mehar-erp
chmod -R 755 /var/www/mehar-erp
chmod -R 775 /var/www/mehar-erp/storage /var/www/mehar-erp/bootstrap/cache
chmod 600 /var/www/mehar-erp/.env
```

### 12. Test

```bash
# Health check
curl http://localhost/health

# Expected response
{"status":"ok","database":"up"}
```

### 13. Done! ✓

- Login: `https://yourdomain.com`
- Admin: `admin@company.com` / `password` (change!)
- Backups: `/var/www/mehar-erp/storage/backups/`
- Logs: `/var/www/mehar-erp/storage/logs/laravel.log`

---

## Troubleshooting

```bash
# Check PHP-FPM
systemctl status php8.2-fpm

# Check Nginx
systemctl status nginx && nginx -t

# Check MySQL
mysql -u erpuser -p mehar_prod -e "SELECT 1;"

# Check cron
sudo crontab -l

# View logs
tail -f storage/logs/laravel.log
```

---

**Need help?** Read full guide: `docs/DEPLOYMENT.md`
