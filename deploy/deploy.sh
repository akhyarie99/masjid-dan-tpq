#!/usr/bin/env bash
# Deploy script SiMasjid — jalankan dari server produksi (Ubuntu 24.04) di /var/www/html/tpq
set -euo pipefail

APP_DIR="/var/www/html/tpq"
cd "$APP_DIR"

# Kalau ada langkah manapun di bawah yang gagal (mis. supervisor/permission),
# situs tetap otomatis keluar dari mode maintenance alih-alih macet di 503 —
# insiden nyata pernah terjadi waktu step lain gagal sebelum "php artisan up"
# sempat jalan.
trap 'php artisan up || true' EXIT

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

# migrate cuma menerapkan skema — daftar permission per role didefinisikan di
# sini, bukan lewat migrasi, jadi harus disinkronkan ulang tiap deploy supaya
# permission baru (atau perubahan mapping role) benar-benar aktif. Aman
# dijalankan berkali-kali (syncPermissions menggantikan daftar lama).
echo "==> Sinkronisasi role & permission"
php artisan db:seed --class=RolePermissionSeeder --force

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

# "php artisan up" dijalankan oleh trap EXIT di atas, tidak perlu dipanggil manual.
echo "==> Deploy selesai."
