#!/usr/bin/env bash
# Ki Reservation - tenant self-service custom domain, host-side worker (2026-09-10).
#
# Kiracıların panelden (Ayarlar > Özel Alan Adı) kendi kendine talep edip DNS'ini doğruladığı alan
# adlarını gerçekten etkinleştirir: sertifika alır, nginx (NPM) server block'unu yazar ve tenant
# kaydını günceller. Uygulama container'ı (ki-reservation-app) hiçbir zaman docker/certbot/nginx'e
# doğrudan erişemez - bu bilinçli bir güvenlik sınırı, bkz. Custom_domain.php'nin docblock'u. O
# yüzden bu adım host'ta, cron ile çalışır - uygulama tarafı sadece "DNS doğrulandı, sırada" diye
# işaretler (custom_domain_status = 'dns_verified'), gerçek işi hep bu script + değişmeden kalan
# add-custom-domain.sh yapar.
#
# Kurulum: bu script'i host'ta bir cron'a bağlayın, örn. her 5 dakikada bir:
#   */5 * * * * /opt/ki-ecosystem/ki-reservation/scripts/domain-worker.sh >> /var/log/ki-domain-worker.log 2>&1
#
# Bağımlılık: jq (JSON satırlarını ayrıştırmak için).
#
# 2026-09-10 düzeltmesi: eşzamanlılık artık uygulama tarafında çözülüyor - console
# domain_requests_pending her satırı teslim ederken 'dns_verified' -> 'provisioning' olarak
# atomik biçimde kilitliyor, bu yüzden add-custom-domain.sh 5 dakikadan uzun sürse bile bir
# sonraki cron tetiklemesi aynı alan adını ikinci kez işlemeye başlamıyor.
#
# 2026-09-10 düzeltmesi: add-custom-domain.sh'ın gerçek çıktısı artık yutulmuyor - hata durumunda
# son satırları tek satıra indirgenip domain_provision_mark'a geçiliyor, böylece kiracı hatanın
# ne olduğunu Özel Alan Adı sayfasında görebiliyor (yalnızca cron log'unda kalmıyor).

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_CONTAINER="booki-app"

if ! command -v jq >/dev/null 2>&1; then
    echo "HATA: jq kurulu değil (apt install jq)." >&2
    exit 1
fi

PENDING="$(docker exec -w /var/www/html "$APP_CONTAINER" php index.php console domain_requests_pending 2>/dev/null)"

if [[ -z "$PENDING" ]]; then
    echo "Bekleyen alan adı talebi yok."
    exit 0
fi

while IFS= read -r line; do
    [[ -z "$line" ]] && continue

    SUBDOMAIN="$(echo "$line" | jq -r '.subdomain')"
    CUSTOM_DOMAIN="$(echo "$line" | jq -r '.custom_domain')"

    if [[ -z "$SUBDOMAIN" || -z "$CUSTOM_DOMAIN" || "$CUSTOM_DOMAIN" == "null" ]]; then
        continue
    fi

    echo "==================================================================="
    echo "İşleniyor: tenant=${SUBDOMAIN} domain=${CUSTOM_DOMAIN}"

    RUN_LOG="$(mktemp)"
    "$SCRIPT_DIR/add-custom-domain.sh" "$SUBDOMAIN" "$CUSTOM_DOMAIN" > "$RUN_LOG" 2>&1
    RC=$?

    # add-custom-domain.sh'ın tüm çıktısı her hâlükârda cron log'una da düşsün.
    cat "$RUN_LOG"

    if [[ $RC -eq 0 ]]; then
        docker exec -w /var/www/html "$APP_CONTAINER" php index.php console domain_provision_mark "$SUBDOMAIN" active
        echo "Tamamlandı: ${SUBDOMAIN} -> ${CUSTOM_DOMAIN}"
    else
        # Gerçek hatayı kiracıya taşı: son 5 satırı tek satıra indir, kontrol karakterlerini at,
        # 240 karaktere kırp (sunucu tarafında domain_provision_mark zaten mb_substr(...,0,255)
        # uyguluyor). docker exec argümanları shell'den geçmediği için tırnaklı tek argüman güvenli.
        ERROR_MSG="$(tail -n 5 "$RUN_LOG" | tr '\n\r\t' '   ' | tr -s ' ' | sed 's/^ *//; s/ *$//' | cut -c1-240)"

        if [[ -z "$ERROR_MSG" ]]; then
            ERROR_MSG="Sertifika/nginx kurulumu başarısız oldu (detaylar için worker log'una bakın)."
        fi

        docker exec -w /var/www/html "$APP_CONTAINER" php index.php console domain_provision_mark "$SUBDOMAIN" failed "$ERROR_MSG"
        echo "BAŞARISIZ: ${SUBDOMAIN} -> ${CUSTOM_DOMAIN} :: ${ERROR_MSG}"
    fi

    rm -f "$RUN_LOG"
done <<< "$PENDING"
