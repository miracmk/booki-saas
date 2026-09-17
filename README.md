# BooKi

> **BooKi** — Çok-kiracılı (multi-tenant) randevu/salon yönetim SaaS platformu.
> Güzellik/masaj/spor/restoran/otel/klinik/stüdyo gibi randevu tabanlı işletmelere satılır.
> Self-hosted, Docker tabanlı, PHP 8.2 (CodeIgniter 3 çekirdeği, `alextselegidis/easyappointments`
> fork'undan doğdu ama bugünkü kod tabanı çok geniş özel geliştirme içeriyor).

## 📍 Kimlik

| Alan | Değer |
|------|-------|
| **Ürün adı** | BooKi |
| **Sahibi** | Miraç Murat KILINÇ |
| **GitHub** | `miracmk/booki-saas` (private, `main`) |
| **Kanonik kaynak** | `/opt/ki-ecosystem/ki-reservation-src/` (git deposu, TEK kaynak — `deploy/` prod, `dev/` yerel dev alt dizinleri) |
| **Prod veri/dosya** | `/opt/ki-ecosystem/ki-reservation/{db,files}` (mutlak yol volume, repo dışında kalıcı) |
| **Durum** | 🟢 CANLI — 9 aktif kiracı (bkz. altta) |

## 🌐 Domain Şeması (canlı, doğrulanmış)

| Rol | Domain | Not |
|-----|--------|-----|
| Kiracı uygulaması | `{subdomain}-bookiapp.kibusiness.co` | Her kiracı kendi subdomain'i, örn. `salonflora-bookiapp.kibusiness.co` |
| Süper admin paneli | `admin-bookiapp.kibusiness.co` | Platform seviyesi, tüm kiracıları yönetir — giriş bilgisi bu repoda tutulmaz, operatörün kendi güvenli notlarında |
| Landing / marketplace | `booki.kibusiness.co` | React/Vite/Express, ayrı repo (`ki-reservation-website`) |
| Kiracı özel domain | tenant self-service | DNS TXT/CNAME doğrulama + otomatik sertifika (bkz. Dalga 5, `docs/ROADMAP.md`) |

Eski domain'ler (`reservationapp/reservationadmin/reservation.kibusiness.co`) BooKi rebrand'i
sırasında terk edildi.

## 🏢 Aktif Kiracılar (2026-09-17)

| Subdomain | Plan | Amaç |
|-----------|------|------|
| `salonflora` | Elite | Gerçek canlı müşteri (Salon Flora) |
| `waveaudit` | — | — |
| `qatest` | Elite | E2E/Playwright test ortamı, gerçek müşteri verisi YOK |
| `demo-guzellik` / `demo-masaj` / `demo-restoran` / `demo-otel` / `demo-klinik` / `demo-studyo` | Premium | 6 dikey demo kiracısı (satış/demo amaçlı, `Console::demo_seed`) |

## 🏗️ Mimari

### PHP 8.2 + MySQL/MariaDB + Docker

```
ki-reservation-src/
├── application/
│   ├── controllers/          ← Controller'lar (Marketing, Invoices, Reviews, Agent_api, Console, ...)
│   ├── models/                ← Model'ler
│   ├── views/pages/            ← Sayfa view'ları
│   ├── libraries/
│   │   ├── payment/            ← 6 POS gateway (Iyzico, Stripe, ÖdeAl, Garanti, Enpara, Paytr)
│   │   ├── accounting/         ← ERP connector'ları (Paraşüt, QuickBooks, Zoho Books, Erp_manager)
│   │   ├── Google_marketing_client.php / Meta_marketing_client.php
│   │   ├── Crm_sync.php        ← Zoho CRM outbox senkronizasyonu
│   │   └── ...
│   ├── config/
│   └── helpers/
├── assets/                     ← CSS, JS (her sayfanın hem `.js` hem `.min.js`'i — bu projede
│                                  `.min.js` gerçek minify DEĞİL, aynı içeriğin kopyası; bilinçli konvansiyon)
├── deploy/                     ← Prod Docker Compose, Dockerfile, MCP sunucusu, wa-bridge
├── dev/                        ← Yerel dev ortamı (ayrı DB/port, aynı repo)
├── tests/e2e/                  ← Playwright E2E paketleri (qatest kiracısına karşı çalışır)
├── docs/
│   ├── ROADMAP.md               ← 52 faz / 6 dalga uygulama planı, GÜNCEL durum
│   └── SESSION_NOTES.md         ← Oturum bazlı ayrıntılı geliştirme günlüğü (kronolojik, en yeni ÜSTTE)
├── system/                      ← Framework çekirdeği (CodeIgniter 3)
├── vendor/                      ← Composer bağımlılıkları
└── README.md                    ← Bu dosya
```

### Teknoloji Stack

| Katman | Teknoloji |
|--------|----------|
| Backend | PHP 8.2, CodeIgniter 3 çekirdeği |
| Frontend (uygulama) | Server-rendered PHP view + jQuery/Bootstrap + sayfa bazlı JS |
| Frontend (landing) | React/Vite/Express (ayrı repo) |
| Database | MySQL/MariaDB — 1 master DB (`ki_reservation_master`, tenant katalog) + kiracı-başına ayrı DB |
| Container | Docker Compose (`ki-reservation-app`, `ki-reservation-db`, `ki-wa-bridge`, `kirsv-mcp`) |
| Proxy | Nginx Proxy Manager |
| DNS/SSL | Cloudflare + Let's Encrypt |
| Test | Playwright (E2E, `qatest` kiracısına karşı) + PHPUnit (sadece bootstrap, asıl birim test listesi yazılmadı) |

## 📦 Özellik Durumu

Aşağıdaki liste `docs/ROADMAP.md`'nin özetidir — **tam gerekçe/commit referansları için o dosyaya
bakın**, burada sadece güncel özet var.

### ✅ Canlıda, tam çalışıyor

- Randevu yönetimi: public booking wizard, gerçek zamanlı müsaitlik, istasyon/oda ataması (race-condition-safe, MySQL named lock), çoklu hizmet veren
- Oturum takibi: check-in/check-out, sapma tespiti, manuel düzeltme
- Komisyon & iç faturalama (appointment+package+product birleşik fatura)
- Recurring appointments, Waitlist (+ boşalınca otomatik bildirim), Memberships (abonelik+kullanım hakkı)
- POS (order/order_items abstraction — gateway'lerin gerçeklik durumu aşağıda ayrı)
- MFA/TOTP (admin/provider/secretary), KVKK/GDPR (rıza + veri indirme/silme uçtan uca akışı)
- Background job kuyruğu (SMS/WhatsApp/Telegram/email artık senkron değil)
- Communication Hub + Automation Engine (WHEN/IF/THEN kuralları, hazır şablonlar)
- Marketing: müşteri segmentleri + kampanya broadcast (e-posta/SMS/WhatsApp/Telegram)
- Review Engine: randevu-sonrası otomatik istek + sağlayıcı/istasyon ayrı puanlama + moderasyon
- Çok-kanallı AI Asistan: WhatsApp/Telegram/Instagram — güvenlik ilkesi: müşteri mesajı hiçbir zaman
  doğrudan yazma yapamaz, tek yol `propose_customer_update` → yönetici onay kuyruğu
- Analytics/BI: revenue/utilization/retention raporları
- Command Center Dashboard + tema motoru (4 renk ailesi × 3 ton)
- Custom domain self-service (talep → DNS doğrulama → otomatik sertifika)
- BooKi rebrand (562 dosya), platform admin paneli, `kirsv-mcp` MCP sunucusu, Zoho CRM outbox senkronu (kimlik bekliyor — altta)
- 8 sayfa (Bekleme Listesi/Üyelikler/Veri Talepleri/Faturalar/POS/Raporlar/Pazarlama/Yorumlar) görsel+CRUD denetimi — `tests/e2e/eight_pages_crud.spec.js` (8/8)

### 🟡 Kod hazır, GERÇEK kimlik bilgisi/kararı bekliyor (dormant)

Bu bölüm önemli: aşağıdakiler "yapılmadı" değil — kod ve mimari yazıldı, syntax/entegrasyon testi
geçti, ama **gerçek üçüncü parti kimlik bilgisi olmadan uçtan uca hiç denenmedi**:

| Entegrasyon | Durum | Bekleyen |
|---|---|---|
| Iyzico (POS) | Gerçek IYZWSv2 imza şeması yazıldı (2026-09-17 düzeltmesi) | Gerçek sandbox API key/secret |
| Stripe (POS) | Gerçek PaymentIntents/Refunds API + webhook imza doğrulaması | Gerçek secret/publishable/webhook key |
| ÖdeAl (POS) | Gerçek domain + OAuth2 token akışı | Gerçek client id/secret; init/refund tam şeması |
| Garanti Sanal POS / Enpara (POS) | Bilinçli mock | Banka başvurusu (şema halka açık değil) |
| QuickBooks Online (ERP) | Gerçek OAuth2 refresh_token + fatura API'si (`Quickbooks_connector.php`) | `console erp_config` ile client/secret/refresh_token/realm_id |
| Zoho Books (ERP) | Gerçek OAuth2 refresh_token + fatura API'si (`Zohobooks_connector.php`) | `console erp_config` ile client/secret/refresh_token/organization_id |
| Paraşüt (ERP) | Payload şekli doğru, endpoint path'i teyit edilemedi | Gerçek kimlikle test |
| Logo / Mikro (ERP) | Bilinçli mock | Merkezi API yok — müşteriye özel kurulum gerekir |
| İşbaşı (ERP) | Bilinçli mock | Kullanıcının kendi hesabından API key talep etmesi |
| Google Ads / GA4 (Marketing) | `Google_marketing_client.php` yazıldı, platform OAuth client kayıtlı | GA4 property ID, Ads customer ID/dev token; gerçek OAuth consent akışı (Calendar'dan ayrı scope) henüz yok |
| Meta Ads / Instagram (Marketing + AI Asistan) | `Meta_marketing_client.php` + webhook'lar yazıldı | Meta sandbox/App Review, gerçek Pixel/CAPI/erişim token'ı |
| Zoho CRM (platform + landing) | `Crm_sync.php` dry-run ile doğrulandı | Gerçek client_id/secret/refresh_token |

### ❌ Henüz başlanmadı

Gift Cards, PWA/Service Worker, White-label (branding toggle), gerçek License/Entitlement kontrolü,
Marketplace ranking/revenue/wallet, birim test paketi (PHPUnit bootstrap var, asıl testler yok).
Detaylar: `docs/ROADMAP.md` → Dalga 4/5.

## 🔄 Deployment

```bash
# Kod değişikliği sonrası (canonical → prod, TEK repo):
cd /opt/ki-ecosystem/ki-reservation-src/deploy
docker compose build app
docker compose up -d app
docker exec -u www-data ki-reservation-app php index.php console migrate
```

⚠️ **Kritik kural:** `deploy/docker-compose.yml`'in build context'i repo köküdür (`context: ..`) —
image'a girecek her JS/PHP değişikliği bu şekilde rebuild edilmeden container'a YANSIMAZ (container
recreate PHP oturumlarını da sıfırlar — Playwright ile test ederken önce `tests/e2e/auth.setup.spec.js`
çalıştırılmalı). JS/CSS değiştiyse `deploy/docker-compose.yml`'deki `ASSET_VERSION` bump edilmeli.

### Konfigürasyon komutları (platform seviyesi, `master_settings`)

```bash
docker exec -u www-data ki-reservation-app php index.php console google_config   # Google OAuth client
docker exec -u www-data ki-reservation-app php index.php console crm_config      # Zoho CRM
docker exec -u www-data ki-reservation-app php index.php console erp_config      # QuickBooks / Zoho Books
```

## 📋 Referans Dokümanlar

| Dosya | İçerik |
|-------|--------|
| `docs/ROADMAP.md` | 52 faz / 6 dalga uygulama planı + boşluk analizi — **güncel durumun tek doğru kaynağı** |
| `docs/SESSION_NOTES.md` | Oturum bazlı ayrıntılı geliştirme günlüğü (kök neden analizleri, bulunan buglar, deploy adımları) |
| `SPECS.md` | Özellik spesifikasyonu |
| `CHANGELOG.md` | Değişiklik günlüğü |
| `COMPLIANCE.md` | KVKK/GDPR uyumluluk dokümanı |
| `KEY_MANAGEMENT.md` | Şifreleme anahtarı yönetimi |

**Admin/platform giriş bilgileri bu repoda tutulmaz** (güvenlik) — operatörün kendi güvenli
notlarında saklanır.

---
*Son güncelleme: 2026-09-17. Güncel git HEAD: `911cdd6`.*
