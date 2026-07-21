# Deployment SiMasjid

Berkas di folder ini adalah artefak deployment untuk server produksi (Ubuntu 24.04). Sesuaikan
domain, path, dan kredensial sebelum dipakai.

## Isi folder

- `nginx.conf` — virtual host Nginx, termasuk proxy WebSocket untuk Reverb (`/app`).
- `supervisor.conf` — proses queue worker & Reverb yang dijaga tetap hidup oleh Supervisor.
- `.env.production.example` — salinan contoh `.env` untuk produksi. **Jangan commit `.env` asli ke git.**
- `deploy.sh` — script deploy yang idempotent, dijalankan ulang setiap kali ada rilis baru.

## Setup awal (sekali saja, sebelum `deploy.sh` pertama kali dipakai)

```bash
# 1. Paket sistem
sudo apt install php8.3 php8.3-{fpm,mysql,gd,zip,mbstring,xml,curl,intl,redis} \
    mysql-server redis-server nginx supervisor certbot python3-certbot-nginx
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs composer

# 2. Clone & konfigurasi
sudo mkdir -p /var/www/simasjid && sudo chown $USER:$USER /var/www/simasjid
git clone <repo-url> /var/www/simasjid
cd /var/www/simasjid
cp deploy/.env.production.example .env
php artisan key:generate
# isi kredensial DB, Fonnte, Midtrans, Reverb, dll di .env

# 3. Dependency & build awal
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Database & storage
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan storage:link

# 5. Nginx + Supervisor + SSL
sudo cp deploy/nginx.conf /etc/nginx/sites-available/simasjid
sudo ln -s /etc/nginx/sites-available/simasjid /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

sudo cp deploy/supervisor.conf /etc/supervisor/conf.d/simasjid.conf
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start all

sudo certbot --nginx -d simasjid.yourdomain.com

# 6. Cron (Laravel Scheduler)
crontab -e
# Tambahkan baris:
# * * * * * cd /var/www/simasjid && php artisan schedule:run >> /dev/null 2>&1

# 7. Permission
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 755 storage bootstrap/cache
```

## Deploy rilis berikutnya

```bash
cd /var/www/simasjid && bash deploy/deploy.sh
```

`deploy.sh` menarik kode terbaru, memasang dependency, menjalankan migrasi, membangun ulang
cache Laravel, me-restart queue worker, dan me-reload Supervisor — aman dijalankan berulang.

## Akses Telescope

Telescope aktif di semua environment (termasuk produksi) untuk memantau job gagal, exception,
dan scheduled task, tapi hanya bisa diakses oleh user dengan role `super_admin`
(lihat `app/Providers/TelescopeServiceProvider.php`). Akses lewat `/telescope` setelah login.

## Catatan Redis vs driver lokal

Proyek ini dikembangkan secara lokal di Windows tanpa Redis (pakai `database`/`file` driver, lihat
`.env`). Di server produksi Linux, Redis tersedia — `.env.production.example` sudah diarahkan ke
Redis untuk session/cache/queue karena lebih cepat di bawah beban. Jika Redis belum diprovision,
proyek tetap berjalan normal dengan driver `database`/`file` seperti di `.env` lokal; cukup ubah
`SESSION_DRIVER`, `QUEUE_CONNECTION`, dan `CACHE_STORE`, lalu sesuaikan `command=` di
`supervisor.conf` (`queue:work redis` → `queue:work database`).
