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

### ✅ Tamamlanan (Wave 3 — Dalga 3)

1. **Communication Hub (Faz 3.1):** E-posta/SMS/WhatsApp/Telegram tek merkezden, event→kanal seçimi
2. **Automation Engine (Faz 3.2):** WHEN/IF/THEN kuralları, hazır şablonlar
3. **Marketing (Faz 3.3):** Segment (VIP/inaktif/doğum günü) + kampanya gönderimi
4. **Review Engine (Faz 3.4):** Randevu-sonrası otomatik değerlendirme isteği + moderasyon
5. **WhatsApp Dual-Mode (Faz 3.5):** Resmi Meta Business API + QR-pairing alternatifi, `ki-wa-bridge` Node/Baileys sidecar — kod tamam, kullanıcının kendi yapacağı telefon/QR eşleştirme testi kaldı
6. **Analytics/BI (Faz 3.6):** Ciro trendi (gün/hafta/ay), kapasite kullanımı (provider bazlı booked/available dakika), müşteri kalıcılığı (yeni/dönen, churn %) — `Reports.php` üzerinde 3 yeni endpoint, view henüz ham JSON (tasarım ayrı turda)

**Ayrıca bu turda (roadmap dışı, canlıda fark edilen düzeltmeler):**
- Tenant kendi SMTP'sini bağlayabiliyor; bağlamazsa platform fallback SMTP (Superadmin panelden girilir) + otomatik tanıtım notu
- Superadmin panelinden kiracı admin kullanıcı adı/şifre değiştirme + e-posta ile şifre sıfırlama
- Admin paneli üst menü → sol sidebar (masaüstü sabit, mobil hamburger/offcanvas)
- Dil çözümleme düzeltmesi: tarayıcı dili artık platform varsayılanını (Türkçe) ezmiyor, "Varsayılan Dil" ayarı artık gerçekten çalışıyor
- Takvimde "İlk Müsaitlik" göstergesi + varsayılan Gün görünümü

## 🔄 Deployment

### Mevcut Durum

- **Container:** `ki-reservation-app` (PHP + Nginx)
- **Database:** `ki-reservation-db` (MariaDB)
- **WhatsApp Bridge:** `ki-wa-bridge` (Node/Baileys sidecar — Faz 3.5 part 2)
- **Proxy:** Nginx Proxy Manager → reservationapp.kibusiness.co
- **SSL:** Let's Encrypt

### Faz 3.5 WhatsApp (unofficial) Sınavı

`wa-bridge` servisi, `bridge/` dizininden inşa edilir ve resmi-öncesi (QR-pairing)
WhatsApp mesaj gönderimini yönetir. Sözleşme detayı: `docs/whatsapp-bridge-contract.md`.

Gereksinimler (`.env` içinde):

- `WA_BRIDGE_SECRET` — platform geneli bridge secret (admin panelde girilen değerle BİREBİR aynı olmalı)
- `WA_BRIDGE_TENANT_SECRETS` (opsiyonel) — `{"<tenant-key>":"<secret>"}` JSON, kiracı bazlı override

Oturum auth durumu `./files/wa-bridge-sessions/` altında tutulur (gitignore korumalı).
Container içi adres: `http://wa-bridge:3000` (uygulama bu adrese bağlanır).

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

> Not: `wa-bridge` servisinde kod değişikliği varsa deploy sonrası
> `docker compose up -d --build wa-bridge` ile yeniden inşâ edilir.

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
├── docker-compose.yml           ← Docker servisleri (app + db + wa-bridge)
├── Dockerfile                   ← PHP + Nginx image
├── README.md                    ← Bu dosya
├── bridge/                      ← WhatsApp sidecar (Node/Baileys)
│   ├── Dockerfile
│   ├── package.json
│   └── src/
│       ├── server.js            ← HTTP sözleşmesi (mevzu: whatsapp-bridge-contract.md)
│       └── bridge.js            ← Baileys oturum yönetimi
├── db/                          ← MySQL verileri (gitignore)
└── files/                       ← storage + wa-bridge oturumları (gitignore)
```

## 🎯 Owner Talimatı

> "Dalga 3 üzerinden devam edilecek. İlk iş: WhatsApp dual-mode (Meta Business API + QR alternatif)."

**Durum:** 🟢 CANLI — Wave 1 + Wave 2 + Wave 3 (Dalga 3, Faz 3.1-3.6) tamamlandı. Custom domain self-servis (Dalga 5'ten öne çekildi) + yeni Command Center Dashboard/tema motoru eklendi, **canlıda ama henüz commit edilmedi** (2026-09-10, oturum 2). Sıradaki: Dalga 4 (Marketplace Olgunlaştırma) + bu turun commit onayı. Detaylar için `docs/ROADMAP.md` ve `docs/SESSION_NOTES.md` (bölüm 7, 2026-09-10).

## ✅ Next Actions

1. [x] Analiz tamamlandı — proje dosyaları yazıldı
2. [x] `reservationadmin.kibusiness.co` doğru çalışıyor — DNS ve env var (`SUPERADMIN_DOMAIN`) zaten uyumlu, ek işlem gerekmedi
3. [x] Dalga 3 — Faz 3.1 Communication Hub, 3.2 Automation Engine, 3.3 Marketing, 3.4 Review Engine, 3.5 WhatsApp dual-mode, 3.6 Analytics/BI — hepsi canlıda
4. [ ] QR cihaz eşleştirme + gerçek mesaj gönderim testi (telefon + WhatsApp hesabı gerekir — kullanıcı kendi yapacak)
5. [ ] Meta Business API resmi onboarding (gerçek uygulama kimlikleri gerekir — kullanıcı kendi yapacak)
6. [ ] Platform fallback SMTP kimlik bilgilerini gir (`reservationadmin.kibusiness.co/superadmin_settings`) — kullanıcı kendi girecek
7. [ ] Faz 3.6 Analytics view'ini tasarım turunda iyileştir (şu an ham JSON)
8. [ ] Dalga 4 — Marketplace Olgunlaştırma başlat
9. [x] Command Center Dashboard (yeni landing sayfası) + 13 renk temalı görsel katman — canlıda, henüz commit edilmedi
10. [x] Özel Alan Adı self-servis (tenant kendi domainini bağlayabiliyor, DNS doğrulama + host cron otomasyonu) — canlıda, henüz commit edilmedi
11. [ ] Bu turun tüm değişikliklerini (bkz. `docs/SESSION_NOTES.md` bölüm 7) commit'lemek için kullanıcı onayı
12. [ ] Dashboard/Özel Alan Adı sayfalarının tarayıcıdan admin oturumuyla görsel kontrolü (bu turda giriş bilgisi yoktu)
