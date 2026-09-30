# Vital Petroleum ERP — Installation & Deployment Guide

**Version:** 1.0  
**Date:** October 2026  
**Target:** StackCP / cPanel Shared Hosting  
**Framework:** Laravel 12 × PHP 8.2+  

---

## 📋 Table of Contents

1. [Pre-Installation](#pre-installation)
2. [One-Click Installation](#one-click-installation)
3. [Manual Installation](#manual-installation)
4. [Initial Configuration](#initial-configuration)
5. [Scheduler Setup](#scheduler-setup)
6. [Backup & Recovery](#backup--recovery)
7. [Troubleshooting](#troubleshooting)
8. [Feature Overview](#feature-overview)

---

## Pre-Installation

### Requirements

- **Hosting:** StackCP, cPanel, or similar shared hosting
- **PHP:** 8.2+ with extensions:
  - `pdo_mysql` (database)
  - `bcmath` (money/quantity calculations)
  - `xml` (PDF generation)
  - `gd` (images)
  - `zip` (backups)
  - `curl` (SMS/email APIs)
- **MySQL:** 8.0+ (InnoDB, utf8mb4)
- **Web Server:** Apache with `mod_rewrite`
- **Disk Space:** 500MB (code + logs)
- **Email:** SMTP credentials (Gmail, SendGrid, or server mail)

### Check Your Setup

1. **Log into cPanel / StackCP**
2. **Go to:** PHP Configuration → PHP Version → Select 8.2+
3. **Ensure Extensions** are enabled:
   - Navigate to: Select PHP Version → Extensions
   - Enable: `pdo_mysql`, `bcmath`, `xml`, `gd`, `zip`, `curl`, `json`, `openssl`

---

## One-Click Installation

### Step 1: Upload Files

1. Download the `petrol-pump-erp-[version].zip` file
2. In cPanel → **File Manager** → Navigate to **public_html**
3. **Upload** the ZIP file
4. **Extract** it (right-click → Extract)
5. **Move contents** up one level (all files directly in `public_html/`)

### Step 2: Run Installer

1. Open your browser: `https://yourdomain.com/install.php`
2. **Welcome Screen** → Click **"Start Installation"**
3. **Database Configuration:**
   - **Host:** `localhost` (usually)
   - **User:** From cPanel Database Wizard (e.g., `username_erp`)
   - **Password:** Strong password from cPanel
   - **Database:** New name (e.g., `username_petrol_erp`)
4. **Admin Setup:**
   - **Company Name:** `Mehar Filling Station` (or your name)
   - **Admin Name:** Your full name
   - **Admin Email:** Your email
   - **Admin Password:** Strong password (min 8 chars)
5. **Click:** **"Complete Installation"**

### Step 3: First Login

1. Installation redirects to login
2. **Email:** The admin email you entered
3. **Password:** The password you set
4. **Click:** **Sign In**

✅ **Done!** You're now in the dashboard.

---

## Manual Installation

If one-click fails, install manually:

### Step 1: Database Creation

1. In cPanel → **MySQL Databases**
2. **Create Database:** Name it `username_petrol_erp` (max 64 chars)
3. **Create User:** `username_erpuser`
4. Set strong password
5. **Add to Database:** Grant ALL privileges

### Step 2: File Structure

```
/home/username/
├── public_html/              (web-accessible)
│   ├── index.php
│   ├── build/                (compiled CSS/JS)
│   ├── .htaccess
│   └── storage → ../petrol-pump-erp/storage/app/public (symlink)
│
└── petrol-pump-erp/          (private, outside web)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    └── artisan
```

### Step 3: Upload Code

1. **Via SFTP/FTP:**
   - Upload everything to `/home/username/petrol-pump-erp/`
   - Except: `.git/`, `node_modules/`, local `.env`

2. **Via Composer (if available):**
   ```bash
   composer install
   ```

### Step 4: Create .env File

Create `/home/wafa-tech/petrol-pump-erp/.env`:

```env
APP_NAME="Vital Petroleum"
APP_ENV=production
APP_KEY=base64:GENERATE_THIS_WITH_ARTISAN
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_TIMEZONE=Asia/Karachi

LOG_CHANNEL=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=username_petrol_erp
DB_USERNAME=username_erpuser
DB_PASSWORD=YOUR_STRONG_PASSWORD_HERE

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=admin@meharfilling.com
MAIL_FROM_NAME="Mehar Filling Station"

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### Step 5: Generate APP_KEY

In SSH (or cPanel Terminal):
```bash
cd /home/username/petrol-pump-erp
php artisan key:generate --force
```

Copy the output `APP_KEY=base64:...` and paste into `.env`

### Step 6: Run Migrations

```bash
cd /home/username/petrol-pump-erp
php artisan migrate --force
```

### Step 7: Create Admin

```bash
php artisan tinker

# Inside tinker shell:
$user = new App\Models\User();
$user->name = "Your Name";
$user->email = "your@email.com";
$user->password = bcrypt("strong_password");
$user->save();

$user->roles()->attach(\App\Models\Role::where('name', 'ADMIN')->first());

exit;
```

### Step 8: File Permissions

```bash
chmod -R 755 /home/username/petrol-pump-erp
chmod -R 775 /home/username/petrol-pump-erp/storage
chmod -R 775 /home/username/petrol-pump-erp/bootstrap/cache
find /home/username/petrol-pump-erp/storage -type f -exec chmod 664 {} \;
```

---

## Initial Configuration

### 1. Login & Configure Settings

1. Go to: **Settings → System Settings**
2. Configure:

#### Fuel Prices
- Set petrol, diesel, HSD, octane rates
- Rates apply to all new sales immediately

#### Tax Settings
- GST Rate: (usually 17%)
- FBR Rate: (0 if not applicable)
- Provincial Tax: (varies by region)
- Enable FBR Invoicing: Toggle on/off

#### Variance Thresholds
- Shift Cash: Rs 500 (alert if ±Rs 500)
- Meter: 5 L (alert if ±5L)
- Tank: 10 L (alert if ±10L)
- Low Stock: 500 L (alert if tank < 500L)

#### Notifications
- Enable SMS: Toggle on
- Enable Email: Toggle on
- SMS Provider: Choose (Jazz/Zong/Telenor)
- SMS API Key: Enter your provider's API key
- Alert Email: Your admin email
- Alert Phone: Your phone number (+923001234567 format)

#### Report Settings
- Auto-Generate: Enable 24h (daily), 12h (optional), 7d, 30d
- Send Time: 23:00 (11 PM default, when day closes)
- Include: PDF + Excel

#### Remember/Keep Alerts
- Select which alerts to archive for history
- Keep for: 30 days (auto-delete older)

### 2. Setup Branches

1. **Settings → Branches**
2. Create each fuel station branch:
   - Name: `Mehar Filling Station - Sheikhupura`
   - Code: `MFS001`
   - City: `Sheikhupura`
   - Status: `Active`

### 3. Add Users

1. **Settings → Users**
2. Create roles first:
   - CASHIER (POS access only)
   - ATTENDANT (nozzle view only)
   - MANAGER (approvals + close shifts)
   - ACCOUNTANT (reports, no POS)

3. Add users and assign roles + branches

### 4. Setup Fuel Products

1. **Fuel Products**
2. Add each fuel type:
   - Name: `Petrol (Mogas)`
   - Code: `MOG`
   - Unit: Liter
   - Tax Rate: 17%
   - Min Stock: 500 L
   - Status: Active

### 5. Setup Tanks & Nozzles

1. **Tanks**
   - Tank Number: `T01`
   - Fuel: Select petrol
   - Capacity: 10,000 L
   - Current Stock: 0 (start)

2. **Dispensers & Nozzles**
   - Dispenser: `D01`
   - Nozzles: `D01-N01` (petrol)

---

## Scheduler Setup

### For Automated Reports & Alerts

The system generates reports hourly and sends daily closing reports at 23:00 (11 PM).

#### Via cPanel Cron

1. **cPanel → Cron Jobs**
2. **Add New Cron Job:**

```
* * * * * cd /home/username/petrol-pump-erp && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

This runs every minute. Laravel's scheduler determines what to execute based on time.

#### Commands Scheduled

- **Hourly:** `reports:generate` — Auto-generate 12h, 24h, 7d, 15d, 30d reports
- **Daily 23:00:** `reports:send-daily` — Email + SMS daily closing report to admins
- **Every 6 hours:** `alerts:cleanup` — Delete alerts older than configured days
- **Weekly:** `audit-logs:prune` — Archive old audit logs

---

## Backup & Recovery

### Automated Backups

The system creates MySQL dumps daily in `storage/app/backups/`

### Manual Backup

In SSH:
```bash
cd /home/username/petrol-pump-erp
php artisan backup:create
```

### Restore from Backup

1. Download `storage/app/backups/latest-backup.sql.gz` via FTP
2. In cPanel → **phpMyAdmin**
3. Select database → **Import**
4. Upload `.sql.gz` file
5. Click **Go**

---

## Troubleshooting

### Issue: "500 Internal Server Error"

**Check:**
1. `.env` exists and has correct DB credentials
2. PHP extensions enabled (bcmath, pdo_mysql, xml)
3. `storage/` directory writable (chmod 775)
4. Check `storage/logs/laravel.log` for details

**Fix:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Issue: "Database Connection Failed"

**Check:**
1. Database user exists in cPanel MySQL
2. Password is correct
3. Database name matches
4. MySQL service is running

**Test:**
```bash
mysql -h localhost -u username_erpuser -p username_petrol_erp -e "SELECT 1;"
```

### Issue: "SMS Not Sending"

**Check:**
1. Settings → SMS Provider configured
2. API Key is valid (not expired)
3. Phone number format: `03001234567` or `+923001234567`
4. Check `storage/logs/laravel.log` for errors

### Issue: "Mail Not Sending"

**Check:**
1. SMTP credentials in `.env` correct
2. Gmail: Use App Password (not regular password)
3. SendGrid: Valid API key
4. Check `storage/logs/laravel.log`

---

## Feature Overview

### 🔧 Admin Control Panel

**Everything is controlled via Settings → (Categories)**

| Category | Controls |
|----------|----------|
| **Fuel** | Prices (per liter), update anytime |
| **Tax** | GST, FBR, provincial rates |
| **Variance** | Alert thresholds (cash, meters, tanks, stock) |
| **Notifications** | Email/SMS toggle, providers, recipients |
| **Reports** | Which reports to auto-generate, send time |
| **Remember** | Which alerts to archive, keep days |
| **System** | Company name, timezone, currency |

### 📊 Auto-Reports (Real Data)

Generated hourly, 5 periods:
- **12h:** Last 12 hours (live tracking)
- **24h:** Yesterday (daily closing)
- **7d:** Last week (weekly summary)
- **15d:** Half month (trend)
- **30d:** Last month (monthly P&L)

Each includes:
- Sales, litres, transactions
- Payment breakdown (cash/card/credit)
- Profitability (gross margin, net profit, %)
- Stock movements & variance
- Fuel breakdown

### 📬 Email Reports

Rich HTML table format, sent at 23:00 daily to all admins:
- Sales summary
- Profitability metrics
- Stock movements
- Fuel breakdown
- Link to dashboard

### 📱 SMS Alerts

Short messages for:
- Low stock alerts
- Shift variance warnings
- Overdue credit notifications
- Pending approvals

### 🔔 Notification Preferences

Each admin can customize:
- Which alerts to receive
- Email, SMS, or both
- Contact info (email, phone)
- Active/inactive toggle

---

## First Day Setup

### Morning (Opening)

1. ✅ Login to dashboard
2. ✅ Go to **Fuel Products** → Add petrol, diesel, octane
3. ✅ Go to **Tanks** → Add tank capacity (10,000 L)
4. ✅ Go to **Dispensers & Nozzles** → Link nozzles to tanks
5. ✅ Go to **POS** → Start processing sales
6. ✅ Dashboard shows live: sales, fuel, revenue, expenses

### Evening (Reports)

1. ✅ At 23:00: Email report auto-sent to admins
2. ✅ SMS summary auto-sent to configured phone
3. ✅ Dashboard shows 24h report with 5 periods of data

### Settings Check

- ✅ Fuel prices set correctly
- ✅ Tax rates match local regulations
- ✅ Variance thresholds suit your operations
- ✅ SMS provider & API key active
- ✅ Backup running (daily automatic)

---

## Support & Troubleshooting

### Common Issues

| Problem | Solution |
|---------|----------|
| Reports not generating | Check cron job is running: `php artisan schedule:work` |
| SMS not sending | Verify API key in Settings → SMS Provider |
| Email delays | Check queue: `php artisan queue:work` (if using queue) |
| Database full | Backup & restore, or increase quota with hosting |
| Permission errors | Run: `chmod -R 775 storage/ bootstrap/cache/` |

### Emergency Recovery

**Restore from latest backup:**
```bash
cd /home/username/petrol-pump-erp
php artisan backup:restore  # (if command exists)
```

Or manually restore via phpMyAdmin.

---

## What's Next?

- ✅ System is live and operational
- ✅ Auto-reports running (hourly)
- ✅ Daily closing reports sent (23:00)
- ✅ Notifications active (SMS + email)
- ✅ All settings admin-controlled

### Optional Enhancements (Future)

- Voice guides for illiterate cashiers
- Mobile app for outdoor stock checks
- Advanced forecasting (ML)
- Multi-language support
- API for external integrations

---

## Contact & Support

**For issues or questions:**
- Check `/storage/logs/laravel.log` for technical errors
- Review this guide's Troubleshooting section
- Contact your hosting provider for server issues
- For feature requests, open an issue on the project repository

---

**Last Updated:** October 2026  
**Version:** 1.0 (Stable)  
**Framework:** Laravel 12 × PHP 8.2+  

---

## 🎉 Congratulations!

Your **Vital Petroleum ERP** is ready for production. All systems are:
- ✅ Installed
- ✅ Configured
- ✅ Scheduled
- ✅ Protected
- ✅ Monitored

**Happy pumping!** 🚗⛽
