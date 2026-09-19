#!/bin/bash
# BooKi Kod Promosyonu: Geliştirme → Canlı
# Kullanım: ./scripts/promote-to-prod.sh [--dry-run]
#
# Bu script:
# 1. Kaynak kodu (canonical) canlı deploy dizinine rsync ile senkronize eder
# 2. Canlı Docker imajını yeniden derler
# 3. Canlı container'ı yeniden başlatır
# 4. Migration'ları çalıştırır
#
# ÖNCEKİ ADIM: Geliştirme ortamında test edilmiş olmalı!

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CANONICAL_SRC="/opt/ki-ecosystem/ki-reservation-src"
DEPLOY_DIR="/opt/ki-ecosystem/ki-reservation"
DRY_RUN="${1:-}"

log() { echo -e "\033[0;32m[-promote]\033[0m $1"; }
warn() { echo -e "\033[0;33m[-promote]\033[0m $1"; }
err() { echo -e "\033[0;31m[-promote]\033[0m $1" >&2; exit 1; }

# Check canonical source exists
if [ ! -d "$CANONICAL_SRC/application" ]; then
    err "Canonical source bulunamadı: $CANONICAL_SRC"
fi

# Check deploy dir exists
if [ ! -d "$DEPLOY_DIR/src" ]; then
    err "Deploy dizini bulunamadı: $DEPLOY_DIR/src"
fi

# Check for uncommitted changes in canonical
cd "$CANONICAL_SRC"
if [ -n "$(git status --porcelain)" ]; then
    warn "UYARI: Canonical repo'da commit'lenmemiş değişiklikler var!"
    git status --porcelain
    echo ""
    read -p "Devam etmek istiyor musunuz? (y/N): " -r
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        err "İptal edildi."
    fi
fi

log "Adım 1/5: Canonical → Deploy rsync"
if [ "$DRY_RUN" = "--dry-run" ]; then
    log "[DRY-RUN] rsync --dry-run çalıştırılacaktı"
    rsync -a --delete --dry-run --exclude='.git' "$CANONICAL_SRC/" "$DEPLOY_DIR/src/"
else
    rsync -a --delete --exclude='.git' "$CANONICAL_SRC/" "$DEPLOY_DIR/src/"
    log "✓ rsync tamamlandı"
fi

log "Adım 2/5: ASSET_VERSION bumped"
NEW_VERSION="prod-$(date +%Y%m%d-%H%M)"
if [ "$DRY_RUN" = "--dry-run" ]; then
    log "[DRY-RUN] ASSET_VERSION $NEW_VERSION olarak ayarlanacaktı"
else
    sed -i "s/ASSET_VERSION: .*/ASSET_VERSION: \"$NEW_VERSION\"/" "$DEPLOY_DIR/docker-compose.yml"
    log "✓ ASSET_VERSION: $NEW_VERSION"
fi

log "Adım 3/5: Docker build (app)"
if [ "$DRY_RUN" = "--dry-run" ]; then
    log "[DRY-RUN] docker compose build app çalıştırılacaktı"
else
    cd "$DEPLOY_DIR"
    docker compose build app
    log "✓ Docker build tamamlandı"
fi

log "Adım 4/5: Container restart + migration"
if [ "$DRY_RUN" = "--dry-run" ]; then
    log "[DRY-RUN] docker compose up -d app + migrate çalıştırılacaktı"
else
    cd "$DEPLOY_DIR"
    docker compose up -d app
    sleep 5
    docker exec -u www-data ki-reservation-app php index.php console migrate --force
    log "✓ Container restart + migration tamamlandı"
fi

log "Adım 5/5: Sağlık kontrolü"
if [ "$DRY_RUN" = "--dry-run" ]; then
    log "[DRY-RUN] Sağlık kontrolü çalıştırılacaktı"
else
    sleep 3
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://bookiapp.kibusiness.co/health || true)
    if [ "$HTTP_CODE" = "200" ]; then
        log "✓ Sağlık kontrolü başarılı (HTTP $HTTP_CODE)"
    else
        warn "⚠ Sağlık kontrolü başarısız (HTTP $HTTP_CODE) - logları kontrol edin"
    fi
fi

log "========================================="
log "Promosyon tamamlandı!"
log "Canlı URL: https://bookiapp.kibusiness.co"
log "Admin URL: https://admin-bookiapp.kibusiness.co"
log "========================================="
