#!/usr/bin/env bash
# BooKi - firma kendi domain/subdomain'ini bağladığında (SaaS "custom domain" özelliği)
# otomatik Let's Encrypt HTTP-01 sertifikası alıp NPM'e ekleyen ve tenant kaydını güncelleyen script.
#
# Kullanım: ./add-custom-domain.sh <subdomain> <custom_domain>
#   subdomain      - tenants.subdomain (örn. "salonflora")
#   custom_domain  - firmanın bağladığı domain/subdomain (örn. "rezervasyon.salonflora.tr")
#
# Önkoşul: firma kendi DNS'inde custom_domain'i bu sunucunun IP'sine (A kaydı) veya
# bookiapp.kibusiness.co'ya (CNAME) yönlendirmiş olmalı - script bunu doğrular, DNS henüz
# yayılmamışsa hata verip çıkar (yeniden çalıştırılabilir, veri kaybı riski yok).

set -euo pipefail

SUBDOMAIN="${1:-}"
CUSTOM_DOMAIN="${2:-}"
SERVER_IP="168.231.109.167"

if [[ -z "$SUBDOMAIN" || -z "$CUSTOM_DOMAIN" ]]; then
    echo "Kullanım: $0 <subdomain> <custom_domain>" >&2
    exit 1
fi

echo "==> DNS kontrolü: $CUSTOM_DOMAIN"
RESOLVED_IP="$(dig +short "$CUSTOM_DOMAIN" @1.1.1.1 | tail -1)"

if [[ "$RESOLVED_IP" != "$SERVER_IP" ]]; then
    echo "HATA: $CUSTOM_DOMAIN şu an $SERVER_IP'ye değil, '$RESOLVED_IP'ye çözümleniyor." >&2
    echo "Firmanın DNS'inde A kaydını $SERVER_IP'ye (veya CNAME'ini bookiapp.kibusiness.co'ya) yönlendirmesi gerekiyor." >&2
    exit 1
fi

echo "==> Let's Encrypt sertifikası alınıyor (HTTP-01, webroot)"
CERT_NAME="custom-${SUBDOMAIN}"
docker exec npm-app-1 certbot certonly \
    --webroot -w /data/letsencrypt-acme-challenge \
    -d "$CUSTOM_DOMAIN" \
    --cert-name "$CERT_NAME" \
    --non-interactive --agree-tos -m miracmuratkilinc@gmail.com

echo "==> NPM nginx server block yazılıyor"
TMP_CONF="$(mktemp)"
cat > "$TMP_CONF" <<EOF
# ------------------------------------------------------------
# ${CUSTOM_DOMAIN} (Ki Reservation - kiracı "${SUBDOMAIN}" custom domain, otomatik oluşturuldu)
# ------------------------------------------------------------

server {
  set \$forward_scheme http;
  set \$server         "ki-reservation-app";
  set \$port           80;

  listen 80;
  listen [::]:80;

  listen 443 ssl;
  listen [::]:443 ssl;

  server_name ${CUSTOM_DOMAIN};

  http2 on;

  include conf.d/include/ssl-cache.conf;
  include conf.d/include/ssl-ciphers.conf;
  ssl_certificate /etc/letsencrypt/live/${CERT_NAME}/fullchain.pem;
  ssl_certificate_key /etc/letsencrypt/live/${CERT_NAME}/privkey.pem;

  include conf.d/include/block-exploits.conf;

  add_header Strict-Transport-Security "max-age=63072000; preload" always;

  set \$trust_forwarded_proto "F";
  include conf.d/include/force-ssl.conf;

  proxy_set_header Upgrade \$http_upgrade;
  proxy_set_header Connection \$http_connection;
  proxy_http_version 1.1;

  access_log /data/logs/proxy-host-${CERT_NAME}_access.log proxy;
  error_log /data/logs/proxy-host-${CERT_NAME}_error.log warn;

  location / {
    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection \$http_connection;
    proxy_http_version 1.1;
    include conf.d/include/proxy.conf;
  }

  include /data/nginx/custom/server_proxy[.]conf;
}
EOF

docker cp "$TMP_CONF" "npm-app-1:/data/nginx/proxy_host/custom-${SUBDOMAIN}.conf"
rm -f "$TMP_CONF"

docker exec npm-app-1 nginx -t
docker exec npm-app-1 nginx -s reload

echo "==> Tenant kaydı güncelleniyor"
# 2026-09-10 düzeltmesi: container_name docker-compose.yml'de "ki-reservation-app" (bu script
# "ki-rezervasyon-app" yazıyordu, muhtemelen eski bir isimlendirmeden kalmıştı - bkz. `docker ps`).
docker exec -w /var/www/html ki-reservation-app php index.php console tenant_set_custom_domain "$SUBDOMAIN" "$CUSTOM_DOMAIN"

echo "==> Doğrulama"
sleep 2
CODE="$(curl -sk -o /dev/null -w '%{http_code}' "https://${CUSTOM_DOMAIN}/")"
echo "https://${CUSTOM_DOMAIN}/ -> HTTP $CODE"

echo "==> Tamamlandı: https://${CUSTOM_DOMAIN}/ artık kiracı '${SUBDOMAIN}''ye yönleniyor."
echo "NOT: Sertifika ~90 günde bir 'docker exec npm-app-1 certbot renew --cert-name ${CERT_NAME}' ile yenilenmeli (NPM'in kendi otomatik yenileme cron'u genelde bunu zaten kapsar)."
