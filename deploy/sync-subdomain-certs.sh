#!/usr/bin/env bash
# Perluas sertifikat SSL tpq.smartedugame.com supaya mencakup subdomain SEMUA
# tenant aktif (bukan wildcard cert — tidak butuh akses API DNS provider,
# cukup HTTP-01 lewat webroot vhost "tpq" yang sudah wildcard server_name).
#
# PENTING: pakai --webroot, BUKAN --nginx. Certbot dengan --nginx akan
# menulis ulang /etc/nginx/sites-available/tpq berdasarkan pemahamannya
# sendiri, dan pernah terbukti MENGHAPUS wildcard "*.tpq.smartedugame.com"
# yang sengaja ditambahkan manual di server_name — --webroot cuma taruh file
# tantangan ACME di public/.well-known/, sama sekali tidak menyentuh config
# Nginx.
#
# Cek dulu apakah daftar domain di sertifikat SAAT INI sudah sama dengan
# yang diinginkan sebelum benar-benar memanggil certbot — supaya cron yang
# jalan tiap 30 menit TIDAK memicu --expand berulang-ulang tanpa perubahan
# (certbot sendiri sebenarnya no-op kalau tidak ada domain baru, tapi
# pengecekan di sini menghindari overhead & jejak log yang tidak perlu).
#
# Cron contoh (jalan tiap 30 menit):
#   */30 * * * * /var/www/html/tpq/deploy/sync-subdomain-certs.sh >> /var/log/tpq-cert-sync.log 2>&1
set -euo pipefail

APP_DIR="/var/www/html/tpq"
CERT_NAME="tpq.smartedugame.com"
CERT_PATH="/etc/letsencrypt/live/${CERT_NAME}/fullchain.pem"

cd "$APP_DIR"

DESIRED=$(php artisan tenants:domains | sort)

if [ -z "$DESIRED" ]; then
    echo "$(date -Iseconds) Tidak ada domain ditemukan, skip."
    exit 0
fi

if [ -f "$CERT_PATH" ]; then
    CURRENT=$(openssl x509 -in "$CERT_PATH" -noout -ext subjectAltName \
        | grep -o 'DNS:[^,]*' | sed 's/DNS://' | sort)
else
    CURRENT=""
fi

if [ "$DESIRED" = "$CURRENT" ]; then
    echo "$(date -Iseconds) Tidak ada domain baru, skip."
    exit 0
fi

ARGS=()
while IFS= read -r d; do
    ARGS+=("-d" "$d")
done <<< "$DESIRED"

echo "$(date -Iseconds) Domain berubah, perluas sertifikat untuk: ${DESIRED//$'\n'/, }"

sudo certbot certonly --webroot -w "${APP_DIR}/public" "${ARGS[@]}" --expand --non-interactive --agree-tos -m admin@smartedugame.com

sudo nginx -t && sudo systemctl reload nginx

echo "$(date -Iseconds) Selesai."
