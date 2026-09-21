#!/usr/bin/env bash
# ==============================================================================
# BooKi Full System Test Runner (Tüm Sistem & 220+ Use Case Doğrulama)
# ==============================================================================
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "=============================================================================="
echo "🚀 BooKi Full System Test Başlatılıyor..."
echo "Tarih: $(date)"
echo "Çalışma Dizini: $ROOT_DIR"
echo "=============================================================================="

CONTAINER="ki-reservation-app"

# 1. Konteyner Durum Kontrolü
echo ""
echo "--- [1/5] Konteyner Sağlık ve Çalışma Kontrolleri ---"
if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
    echo "❌ HATA: '${CONTAINER}' konteyneri çalışmıyor!"
    exit 1
fi
echo "✅ '${CONTAINER}' aktif ve çalışıyor."

# 2. PHP Sözdizimi Kontrolleri
echo ""
echo "--- [2/5] PHP Kod Tabanı Sözdizimi (Lint) Taraması ---"
ERRORS=0
for f in $(find application/controllers application/models application/libraries application/helpers tests/System -name "*.php"); do
    if ! php -l "$f" > /dev/null 2>&1; then
        echo "❌ Sözdizimi hatası: $f"
        ERRORS=$((ERRORS + 1))
    fi
done

if [ "$ERRORS" -eq 0 ]; then
    echo "✅ Tüm PHP dosyaları (Controllers, Models, Libraries, Helpers, System Tests) sözdizimi açısından kusursuz."
else
    echo "❌ Toplam $ERRORS dosyada sözdizimi hatası bulundu!"
    exit 1
fi

# 3. Dosyaların Konteyner İle Eşitlenmesi
echo ""
echo "--- [3/5] Kod ve Test Dosyalarının Konteynere Eşitlenmesi ---"
docker cp application/. "${CONTAINER}:/var/www/html/application/"
docker cp assets/. "${CONTAINER}:/var/www/html/assets/"
docker cp tests/. "${CONTAINER}:/var/www/html/tests/"
docker cp phpunit.xml "${CONTAINER}:/var/www/html/phpunit.xml"
echo "✅ Dosyalar güncellendi."

# 4. PHPUnit Test Paketi (56 Temel + 220 Use Case + 13 UI/UX Kalite Muhafızı = 289 Test)
echo ""
echo "--- [4/5] PHPUnit Tüm Sistem Testleri & UI Kalite Muhafızları (289 Test) ---"
docker exec "${CONTAINER}" vendor/bin/phpunit
echo "✅ PHPUnit tüm test paketleri (Unit, Integration, System 220 Use Cases ve UI Kalite Muhafızları) başarıyla tamamlandı."

# 5. Canlı HTTP & API Doğrulama Testleri
echo ""
echo "--- [5/5] Canlı HTTP, Güvenlik ve Keşif Uç Noktaları Doğrulaması ---"

check_endpoint() {
    local host="$1"
    local path="$2"
    local expected_pattern="$3"
    local desc="$4"

    local code
    code=$(docker exec "${CONTAINER}" curl -s -o /dev/null -w "%{http_code}" -H "Host: ${host}" "http://localhost${path}")
    if [[ "$code" =~ ^($expected_pattern)$ ]]; then
        echo "  ✅ [HTTP $code] $desc (Host: $host, Path: $path)"
    else
        echo "  ❌ [HTTP $code Beklenen: $expected_pattern] $desc (Host: $host, Path: $path)"
        return 1
    fi
}

check_endpoint "bookiapp.kibusiness.co" "/" "200" "Landing Ana Sayfası"
check_endpoint "booki.kibusiness.co" "/marketplace" "200" "Marketplace Keşif Portalı"
check_endpoint "qatest-bookiapp.kibusiness.co" "/login" "200" "Kiracı Giriş Ekranı"
check_endpoint "qatest-bookiapp.kibusiness.co" "/portal" "302|303|307" "Müşteri Portalı Oturum Koruması"
check_endpoint "booki.kibusiness.co" "/privacy" "200" "Gizlilik Politikası (KVKK)"
check_endpoint "booki.kibusiness.co" "/terms" "200" "Kullanım Koşulları"
check_endpoint "booki.kibusiness.co" "/sitemap.xml" "200" "Dinamik XML Sitemap"
check_endpoint "booki.kibusiness.co" "/robots.txt" "200" "Robots.txt"
check_endpoint "booki.kibusiness.co" "/llms.txt" "200" "AI GEO Dokümantasyonu"

echo ""
echo "=============================================================================="
echo "🎉 TEBRİKLER: FULL SYSTEM TEST BAŞARIYLA GEÇTİ!"
echo "Toplam 289 test, 550+ assertion, UI kalite muhafızları ve canlı uç noktalar eksiksiz doğrulandı."
echo "=============================================================================="
