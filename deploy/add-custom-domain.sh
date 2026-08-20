#!/usr/bin/env bash
# Aktifkan custom domain tenant yang SUDAH terverifikasi (custom_domain_verified_at
# terisi, lihat menu Pengaturan > Alamat Portal). Jalankan manual oleh admin
# server sekali per domain baru — bukan otomatis, supaya tidak perlu Caddy
# atau ubah konfigurasi Nginx level global yang bisa memengaruhi ~25 aplikasi
# lain di server yang sama. Vhost baru ini identik dengan vhost "tpq" yang
# sudah ada (root & PHP-FPM sama — semua tenant satu aplikasi Laravel yang
# sama, dibedakan lewat Host header oleh ResolveTenant middleware), cuma
# server_name-nya beda.
set -euo pipefail

if [ $# -ne 1 ]; then
    echo "Pemakaian: $0 <domain>"
    echo "Contoh:    $0 tpqnurulhuda.com"
    exit 1
fi

DOMAIN="$1"
CONF_PATH="/etc/nginx/sites-available/tpq-custom-${DOMAIN}"

if [ -e "$CONF_PATH" ]; then
    echo "Vhost untuk ${DOMAIN} sudah ada di ${CONF_PATH}. Hapus dulu kalau mau bikin ulang."
    exit 1
fi

echo "==> Membuat vhost Nginx untuk ${DOMAIN}"
sudo tee "$CONF_PATH" > /dev/null <<EOF
server {
    listen 80;
    server_name ${DOMAIN};
    root /var/www/html/tpq/public;
    index index.php;

    client_max_body_size 20m;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~* \.(?:css|js|svg|woff2?|ttf|eot|png|jpg|jpeg|gif|ico|webp)\$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
    }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }

    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
}
EOF

sudo ln -sf "$CONF_PATH" "/etc/nginx/sites-enabled/tpq-custom-${DOMAIN}"

echo "==> Cek syntax Nginx"
sudo nginx -t

echo "==> Reload Nginx (supaya HTTP-01 challenge certbot bisa lewat vhost baru ini)"
sudo systemctl reload nginx

echo "==> Menerbitkan sertifikat SSL (pastikan DNS ${DOMAIN} sudah mengarah ke IP server ini!)"
sudo certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m admin@smartedugame.com

echo "==> Selesai. ${DOMAIN} sudah aktif dengan HTTPS."
