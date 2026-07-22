#!/usr/bin/env bash
# Deploy script SiMasjid — jalankan dari server produksi (Ubuntu 24.04) di /var/www/html/tpq
set -euo pipefail

APP_DIR="/var/www/html/tpq"
cd "$APP_DIR"

echo "==> Mengaktifkan mode maintenance"
php artisan down --retry=15 || true

echo "==> Menarik kode terbaru"
git pull origin main

echo "==> Instalasi dependency"
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

echo "==> Migrasi database"
php artisan migrate --force

echo "==> Membersihkan & menyusun cache konfigurasi"
php artisan config:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Restart queue worker"
php artisan queue:restart

echo "==> Reload supervisor"
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

echo "==> Perbaikan permission storage"
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache

echo "==> Menonaktifkan mode maintenance"
php artisan up

echo "==> Deploy selesai."
