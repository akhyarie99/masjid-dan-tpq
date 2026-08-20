#!/usr/bin/env bash
# Perluas sertifikat SSL tpq.smartedugame.com supaya mencakup subdomain SEMUA
# tenant aktif (bukan wildcard cert — tidak butuh akses API DNS provider,
# cukup HTTP-01 lewat vhost Nginx "tpq" yang sudah wildcard server_name).
#
# Jalankan lewat cron (lihat instruksi di bawah), aman dipanggil berkali-kali
# — certbot --expand tidak melakukan apa-apa kalau domainnya sudah semua ada
# di sertifikat yang sama.
#
# Cron contoh (jalan tiap 30 menit):
#   */30 * * * * /var/www/html/tpq/deploy/sync-subdomain-certs.sh >> /var/log/tpq-cert-sync.log 2>&1
set -euo pipefail

APP_DIR="/var/www/html/tpq"
cd "$APP_DIR"

DOMAINS=$(php artisan tenants:domains)

if [ -z "$DOMAINS" ]; then
    echo "$(date -Iseconds) Tidak ada domain ditemukan, skip."
    exit 0
fi

ARGS=()
for d in $DOMAINS; do
    ARGS+=("-d" "$d")
done

echo "$(date -Iseconds) Sinkronisasi sertifikat untuk: ${DOMAINS//$'\n'/, }"

sudo certbot certonly --nginx "${ARGS[@]}" --expand --non-interactive --agree-tos -m admin@smartedugame.com

sudo nginx -t && sudo systemctl reload nginx

echo "$(date -Iseconds) Selesai."
