#!/bin/bash
# BooKi — Kanonik kaynaktan geliştirme ortamına senkron
# Kullanım: ./scripts/dev-sync.sh [--build]
#
# Kanonik:  /opt/ki-ecosystem/ki-reservation-src/ (git repo: booki-saas)
# Dev:      /opt/ki-ecosystem/ki-booki-dev/src/
#
# Not: Canlı ortam (/opt/ki-ecosystem/ki-reservation/) BUNA DOKUNMAZ.
# Onay sonrası canlıya taşıma için: scripts/promote-to-prod.sh

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CANONICAL="/opt/ki-ecosystem/ki-reservation-src"
DEV_DIR="/opt/ki-ecosystem/ki-booki-dev"
WITH_BUILD="${1:-}"

if [ ! -d "$CANONICAL/application" ]; then
    echo "HATA: Kanonik kaynak bulunamadı: $CANONICAL" >&2
    exit 1
fi

echo "[-dev-sync] Kanonik → Dev src rsync"
rsync -a --delete --exclude='.git' --exclude='.tokensave' --exclude='tokensave-docs' \
    "$CANONICAL/" "$DEV_DIR/src/"

echo "[-dev-sync] ✓ Senkron tamamlandı"

if [ "$WITH_BUILD" = "--build" ]; then
    echo "[-dev-sync] Dev container yeniden derleniyor..."
    cd "$DEV_DIR"
    docker compose build app
    docker compose up -d app
    sleep 3
    docker exec -u www-data ki-booki-dev-app php index.php console migrate --force || echo "  (migrate çıktısı yukarıda - hata olabilir, kontrol edin)"
    echo "[-dev-sync] ✓ Dev ortamı hazır: http://localhost:8080"
fi