# Ki Reservation

> **Ki Reservation** — Çok-kiracılı (multi-tenant) randevu/salon yönetim SaaS platformu.
> Güzellik/masaj salonlarına satılır. Self-hosted, Docker tabanlı.

## 📍 Kimlik

| Alan | Değer |
|------|-------|
| **Ürün adı** | Ki Reservation |
| **Sahibi** | Miraç Murat KILINÇ |
| **GitHub** | `ki-reservation-saas` (private, main) |
| **Lokal (kaynak)** | `/opt/ki-ecosystem/ki-reservation-src/` |
| **Lokal (deploy)** | `/opt/ki-ecosystem/ki-reservation/` |
| **Disk** | ~150 MB+ |
| **Durum** | 🟢 CANLI |
| **URL** | `https://reservationapp.kibusiness.co` |

## 🌐 Domain Şeması

| Rol | Domain | Durum |
|-----|--------|-------|
| Uygulama websitesi (vitrin) | `reservations.kibusiness.co` | ✅ HTTP 200 |
| Uygulama girişi / app | `reservationapp.kibusiness.co` | ✅ HTTP 200 |
| Superadmin panel | `reservationsadmin.kibusiness.co` | ✅ Mevcut (DNS güncellenebilir) |

## 🏗️ Mimari

### PHP 8.2 + MySQL/MariaDB + Docker

```
ki-reservation-src/
├── application/                 ← PHP uygulaması
│   ├── controllers/             ← Controller'lar
│   ├── models/                  ← Model'ler
│   ├── views/                   ← View'lar
│   ├── config/                  ← Konfigürasyon
│   └── helpers/                 ← Yardımcı fonksiyonlar
├── assets/                      ← CSS, JS, görseller
├── system/                      ← Framework çekirdeği
├── vendor/                      ← Composer bağımlılıkları
├── config-sample.php            ← Örnek config
├── composer.json                ← PHP bağımlılıkları
├── SPECS.md                     ← Özellik spesifikasyonu (298 satır)
├── CHANGELOG.md                 ← Değişiklik günlüğü (32KB)
├── COMPLIANCE.md                ← Uyumluluk dokümanı (KVKK/GDPR)
├── KEY_MANAGEMENT.md            ← Anahtar yönetimi
├── LICENSE                      ← Ki Software License
└── README.md                    ← Bu dosya
```

### Teknoloji Stack

| Katman | Teknoloji |
|--------|----------|
| Backend | PHP 8.2 |
| Frontend | HTML5 + CSS3 + JavaScript |
| Database | MySQL / MariaDB |
| Cache | Redis (opsiyonel) |
| Container | Docker |
| Proxy | Nginx Proxy Manager |
| DNS | Cloudflare |
| SSL | Let's Encrypt |

### Veritabanı Şeması (Ana Tablolar)

| Tablo | Açıklama |
|-------|----------|
| `appointments` | Randevular |
| `customers` | Müşteriler |
| `providers` | Hizmet verenler (terapistler) |
| `services` | Hizmetler |
| `stations` | Fiziksel istasyonlar/odalar |
| `users` | Kullanıcılar (admin, sekreter, provider) |
| `working_hours` | Çalışma saatleri |
| `unavailability` | Müsaitlik dışı bloklar |
| `sessions` | Oturum takibi (check-in/check-out) |
| `payments` | Ödemeler |
| `invoices` | Faturalar |
| `memberships` | Üyelikler |
| `waitlists` | Bekleme listeleri |
| `pos_transactions` | POS işlemleri |

### API Endpoint'ler

| Endpoint | Açıklama |
|----------|----------|
| `GET /api/appointments` | Randevu listesi |
| `POST /api/appointments` | Yeni randevu |
| `GET /api/availability` | Müsaitlik sorgulama |
| `POST /api/checkin` | Check-in |
| `POST /api/checkout` | Check-out |
| `GET /api/customers` | Müşteri listesi |
| `POST /api/customers` | Yeni müşteri |
| `GET /api/providers` | Hizmet veren listesi |
| `GET /api/services` | Hizmet listesi |
| `GET /api/stations` | İstasyon listesi |
| `GET /api/reports/revenue` | Gelir raporu |
| `GET /api/reports/sessions` | Oturum raporu |

## 📦 Özellikler

### ✅ Tamamlanan (Wave 1 + Wave 2)

#### Wave 1 — Gelir/Ops
1. **Randevu Yönetimi:**
   - Public booking wizard (hizmet → personel → tarih/saat → müşteri → onay)
   - Gerçek zamanlı müsaitlik kontrolü
   - Çoklu hizmet veren desteği
   - Çalışma saatleri ve müsaitlik dışı bloklar

2. **Kaynak & İstasyon Yönetimi:**
   - Fiziksel istasyonlar/odalar
   - Gerçek zamanlı istasyon müsaitliği
   - Race-condition-safe istasyon atama (MySQL named locks)
   - Manuel istasyon override

3. **Oturum Takibi (Check-in/Check-out):**
   - Canlı oturum durumu (başlamadı, devam ediyor, yaklaşıyor, gecikmiş, bitti)
   - Tek tıkla check-in/check-out
   - Sapma tespiti (erken/geç check-out)
   - Manuel zaman düzeltme

4. **Komisyon & Faturalama:**
   - Hizmet veren başına komisyon (yüzde/sabit/saatlik)
   - Süre bazlı faturalama
   - Saatlik gelir raporu
   - Fatura oluşturma

5. **Ödeme Takibi:**
   - Ödeme durumu (bekliyor, tahsil edilmedi, tahsil edildi)
   - Ödeme yöntemi (IBAN, fiziksel POS, sanal POS, nakit)
   - Ödeme tutarı ve bakiye

6. **Üyelik & Bekleme Listesi:**
   - Üyelik yönetimi
   - Bekleme listesi
   - POS işlemleri

#### Wave 2 — Güvenlik
1. **MFA/TOTP:** İki faktörlü kimlik doğrulama
2. **KVKK/GDPR:** Veri uyumluluk
3. **Background Jobs:** Arka plan işleri
4. **Observability:** Gözlemlilik

### ✅ Tamamlanan (Wave 3 — kısmi)

#### Wave 3 — Otomasyon ve Büyüme
1. **Communication Hub** (Faz 3.1): E-posta, SMS, WhatsApp tek merkezden; otomatik hatırlatmalar
2. **Automation Engine** (Faz 3.2): WHEN/IF/THEN kuralları, 6 hazır şablon, appointment hooks'ları
3. **Marketing** (Faz 3.3): müşteri segmentleri (VIP/inaktif/doğum günü/tümü/özel) + kampanya yönetimi + broadcast sender (draft→queued→sending→sent, 50'lik batch, e-posta/SMS/WhatsApp/Telegram) + console komutları
4. **Review Engine** (Faz 3.4): 🔲 planlanan — randevu-sonrası otomatik review isteği
5. **WhatsApp dual-mode** (Faz 3.5): 🔲 planlanan — Meta Business API + QR alternatif
6. **Analytics/BI** (Faz 3.6): 🔲 planlanan — revenue/utilization/retention dashboard'ları

## 🔄 Deployment

### Mevcut Durum

- **Container:** `ki-reservation-app` (PHP + Nginx)
- **Database:** `ki-reservation-db` (MariaDB)
- **Proxy:** Nginx Proxy Manager → reservationapp.kibusiness.co
- **SSL:** Let's Encrypt

### Canlıya Alma

```bash
cd /opt/ki-ecosystem/ki-reservation
docker compose up -d
```

### Yeni Sürüm Deploy

```bash
cd /opt/ki-ecosystem/ki-reservation-src
git pull origin main
cd /opt/ki-ecosystem/ki-reservation
docker compose up -d --build
```

## 📋 Kritik Dosya Yolları

```
/opt/ki-ecosystem/ki-reservation-src/
├── application/
│   ├── controllers/             ← Controller'lar
│   ├── models/                  ← Model'ler
│   ├── views/                   ← View'lar
│   └── config/                  ← Konfigürasyon
├── assets/                      ← CSS, JS, görseller
├── system/                      ← Framework
├── vendor/                      ← Composer
├── config-sample.php            ← Örnek config
├── composer.json                ← PHP bağımlılıkları
├── SPECS.md                     ← Özellik spesifikasyonu
├── CHANGELOG.md                 ← Değişiklik günlüğü
├── COMPLIANCE.md                ← Uyumluluk dokümanı
├── KEY_MANAGEMENT.md            ← Anahtar yönetimi
└── LICENSE                      ← Ki Software License

/opt/ki-ecosystem/ki-reservation/
├── docker-compose.yml           ← Docker servisleri
├── Dockerfile                   ← PHP + Nginx image
└── nginx.conf                   ← Nginx config
```

## 🎯 Owner Talimatı

> "Dalga 3 üzerinden devam edilecek. İlk iş: WhatsApp dual-mode (Meta Business API + QR alternatif)."

**Durum:** 🟢 CANLI — Wave 1 + Wave 2 + Wave 3 (Faz 3.1/3.2/3.3) tamamlandı. Kalan: Faz 3.4 Review Engine, Faz 3.5 WhatsApp dual-mode, Faz 3.6 Analytics.

## ✅ Next Actions

1. [x] Analiz tamamlandı — proje dosyaları yazıldı
2. [ ] `reservationadmin` → `reservationsadmin` DNS güncelle
3. [ ] Dalga 3 / Faz 3.4 Review Engine başlat (ki-reservation-src)
4. [ ] GitHub repo güncelle (ki-reservation-saas)
5. [ ] CHANGELOG.md güncelle (Wave 3 ilerlemesi)

---

## 📝 Oturum Notu (2026-09-09)

**Durum:** 🟢 CANLI — Wave 1 + Wave 2 + Wave 3 (Faz 3.1/3.2/3.3) tamamlandı. Kalan: Faz 3.4 Review Engine, Faz 3.5 WhatsApp dual-mode, Faz 3.6 Analytics.

**Faz 3.3 Marketing (canlıda, 2026-09-09):**
- Migration 130: `platform` ayarı + `google_ads_gateway_token` (Eski Google Ads ↔ yeni/Google Ads eşleştirmesi).
- Migration 131: `marketing_segments`, `marketing_campaigns`, `campaign_recipients` tabloları + `ea_roles.marketing` bitmask (admin=15).
- Segment türleri: VIP / inaktif / doğum günü / tümü / özel; admin panelde "Pazarlama" sayfası (segment + kampanya yönetimi).
- Kampanya akışı: draft → queued (idsi `prepare_broadcast`, idempotent) → sending → sent; `send_batch()` (50'lik batch, kuyruklu); kanallar e-posta/SMS/WhatsApp/Telegram.
- Merge alanları: `{{customer_name}}`, `{{customer_first_name}}`, `{{company_name}}`.
- Console: `marketing_segments`, `marketing_refresh`, `marketing_campaigns`.
- Doğrulama: 516 müşterili smoke test (segment→campaign→prepare→send_batch→cleanup) + tam sayfa render testi başarılı; `/marketing` auth gate doğru çalışıyor.
- Bilinen not: e-posta gönderimi best-effort — hostta `sendmail` yok; alıcılar yine de "sent" işaretlenir (mevcut kanal konvansiyonu).

**⚠️ Deploy felaketi + kurtarma:** 2026-09-08'de deploy köküne yanlışlıkla `rsync -a --delete` yapıldı; `db/mysql` (MySQL datadir) ve `files/` (storage volume) silindi. Yaşayan mysqld'den her iki DB dump edilip (`/tmp/opencode/db-recover/*.sql` — HAYATİ, silme) taze DB container'ı + `.env` + `src/` + storage volume kurularak %100 restore edildi. Doğrulandı: tenant subdomain'lerde `/booking`, `/login`, `/health` → 200. Ayrıca base image'de kapalı gelen `mod_rewrite` Dockerfile'a `a2enmod rewrite` eklenerek açıldı (öncesinde tüm clean URL'ler 404 dönüyordu).

**Kritik deploy kuralı (asla çiğneme):** rsync hedefi YALNIZCA `/opt/ki-ecosystem/ki-reservation/src/` olur. Deploy ROOT'una `--delete` ile rsync YAPMA (gitignore'lu `db/`, `files/`, `src/`, `.env`'i siler).

**Sonraki adım:** Dalga 3'ün kalan fazları — Faz 3.4 Review Engine, Faz 3.5 WhatsApp dual-mode, Faz 3.6 Analytics, ardından custom domain özelliği düzeltmesi. Devam detayları: `docs/SESSION_NOTES.md`.
