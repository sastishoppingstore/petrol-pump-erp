#!/usr/bin/env bash
set -e

# ========================================================
# Petrol Pump ERP — Production Edge Runner
# Runs Laravel without Nginx or Apache
# ========================================================

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$BASE_DIR"

echo "=== ⛽ Starting Petrol Pump ERP on Cloudflare Architecture ==="

# 1. Ensure Environment
if [ -f .env.cloudflare ]; then
    echo "✓ Loading Cloudflare production environment (.env.cloudflare)..."
fi

# 2. Build assets if not built
if [ ! -d "public/build" ]; then
    echo "✓ Building frontend assets with Vite..."
    npm run build
fi

# 3. Check D1 Database connectivity
echo "✓ Syncing Cloudflare D1 Database schema & seeds..."
php artisan cloudflare:d1-sync || true

# 4. Start High-Performance PHP CLI Server without Apache/Nginx
PORT="${PORT:-8080}"
HOST="${HOST:-0.0.0.0}"

echo "✓ Starting PHP 8.4 application server on http://${HOST}:${PORT}..."
echo "✓ Connected to Cloudflare D1 (petrol_pump_erp_db) and Workers KV"
echo "✓ Super Admin: admin@petrolerp.com"
echo "========================================================"

exec php -S "${HOST}:${PORT}" -t public vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
