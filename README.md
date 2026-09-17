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

## 🧭 Geliştirme Metodolojisi & Dikkat Edilmesi Gerekenler

> Bu bölüm, bu projede çalışan her ajanın (Claude, Gemini, insan geliştirici) uyması gereken
> disiplini özetler. Bunlar teorik tercih değil — hepsi bu projede GERÇEKTEN yaşanmış, tekrarlanmış
> hataların çıkardığı derslerdir (kaynak: `docs/SESSION_NOTES.md`, kronolojik geliştirme günlüğü).

### 1. Deploy sırası — ATLARSAN yanıltıcı "bug" raporu üretirsin

```
kod değişikliği → docker compose build app → docker compose up -d app →
tests/e2e/auth.setup.spec.js (session'ı tazele) → asıl test paketi
```

- Build context repo köküdür (`context: ..`) — rebuild etmeden hiçbir PHP/JS değişikliği container'a
  girmez. 2026-09-17'de Gemini CLI tam bu adımı atladığı için gerçekte var olmayan 2 test hatası
  "buldu" (kota tükenmesiyle oturum kesildi, devamı Claude tarafından teşhis edilip düzeltildi).
- `docker compose up -d` bir **recreate** yaptığında PHP oturumları (session dosyaları) sıfırlanır —
  Playwright'ın önceden kaydettiği `tests/e2e/.auth/admin.json` çerezi geçersiz kalır, TÜM sayfalar
  `/login`'e 307 döner. Bu "her şey kırık" gibi görünür ama aslında sadece oturum tazelenmemiştir.
- JS/CSS dosyası değiştiyse `deploy/docker-compose.yml`'deki `ASSET_VERSION` bump edilmeli (yoksa
  tarayıcı eski cache'i kullanabilir).
- Migration eklediysen `console migrate` TÜM aktif kiracılarda çalışır (tek bir `--tenant` bayrağı
  yok, hepsi otomatik döner) — sonucun 9/9 "OK" olduğunu doğrula.

### 2. Dış API entegrasyonu — şema uydurmak yasak

- Bir ödeme/ERP/pazarlama API'sine kod yazmadan önce GERÇEK dokümantasyonu bul ve oku (WebFetch/
  WebSearch). Endpoint path'i, auth şeması, imza formülü TEYİT EDİLMEDEN yazılan kod, gerçek
  kimlikle denendiğinde ya sessizce yanlış sonuç üretir ya da (Iyzico örneğinde olduğu gibi) her
  isteği 401 ile reddettirir — ikisi de kod incelemesinde YAKALANAMAZ, sadece gerçek API çağrısında
  ortaya çıkar.
- Dokümantasyona ulaşılamıyorsa (bot koruması, giriş gerekiyor, banka başvurusu şart vb.) kodu
  **bilinçli mock** bırak ve docblock'a NEDEN'i yaz (bkz. `Garanti_gateway.php`, `Enpara_gateway.php`,
  `Erp_manager.php`'deki Logo/Mikro/İşbaşı metodları). Sahte bir şema uydurup "tamamlandı" deme.
- Kimlik bilgisi henüz yoksa entegrasyon "dormant" kalır — bu normaldir, kod hazır olabilir ama asla
  gerçek kimlik olmadan "uçtan uca doğrulandı" denemez. Bu projede `configured=false` veya açık
  `RuntimeException` dönmek, sessizce sahte bir "success" dönmekten HER ZAMAN daha iyidir.

### 3. Kimlik bilgisi yönetimi

- Platform seviyesi (master, tüm kiracılar için ortak) kimlik bilgileri `master_setting()` ile DB'de
  tutulur, `Console::google_config` / `crm_config` / `erp_config` gibi whitelist'li, maskeli-gösterimli
  komutlarla girilir/okunur. Yeni bir entegrasyon eklerken bu deseni tekrar kullan, yeni bir env var
  ya da düz-metin config dosyası icat etme.
- Maskeleme kontrolünde `$display !== ''` YAZMA — `master_setting()` ayarlanmamış anahtar için
  `null` döner ve PHP'de `null !== ''` TRUE'dur, bu da boş alanları yanlışlıkla "dolu/maskeli"
  gösterir (bu bug üç komutta da vardı, 2026-09-17'de düzeltildi). `!empty($display)` kullan.
- Gerçek secret/token/şifre hiçbir zaman README/ROADMAP/SESSION_NOTES.md'ye veya commit mesajına
  yazılmaz. Süper admin gibi giriş bilgileri bu repoda tutulmaz.

### 4. Test disiplini

- `tests/e2e/*.spec.js` (Playwright) canlı `qatest` kiracısına karşı çalışır — gerçek müşteri verisi
  YOK, serbestçe yazıp silinebilir. `playwright.config.js`: `workers: 1`, `fullyParallel: false`
  (paylaşılan tenant durumu, sıralı çalışmalı).
- Bir test tam suit'te başarısız oluyor ama tek başına (`-g "..."` ile izole) geçiyorsa, önce bunun
  gerçek bir regresyon mu yoksa zamanlama/veri çarpışması kaynaklı bir flake mi olduğunu ayır —
  hepsini "bug" sayma.
- Her PHP değişikliğinde `php -l`, her JS değişikliğinde `node --check` çalıştır — bu ucuz ve hızlı,
  atlama.
- `docker exec ... storage/logs/log-YYYY-MM-DD.php` (JSON log) değişiklik sonrası kısaca taranmalı —
  beklenen gürültüyü (örn. WhatsApp bridge bağlı değilken `no_connected_session`) gerçek yeni
  hatalardan ayırt et.

### 5. Dokümantasyon disiplini

- **`docs/SESSION_NOTES.md`**: her oturumun sonunda tarihli bir bölüm eklenir, EN YENİ EN ÜSTTE
  (dosyanın başında). Kök neden analizi + bulunan gerçek buglar + deploy adımları + "sıradaki" net
  yazılır — bu dosya gelecekteki bir ajanın "nerede kaldık" sorusuna cevabı.
- **`docs/ROADMAP.md`**: bu dosya HER ZAMAN güncel durumun tek doğru kaynağı olmalı. Bir faz/dalga
  "canlıya deploy edilmedi" diye işaretliyse ve sonradan deploy edildiyse, bunu güncelleme —
  bayatlamış roadmap notları yanlış varsayımlara yol açar (2026-09-17'de birkaç böyle stale not
  bulunup düzeltildi).
- **`README.md`** (bu dosya): proje kimliği + mimari + ÖZELLİK DURUMU özeti — "canlıda" / "kod hazır
  kimlik bekliyor" / "başlanmadı" ayrımı net tutulmalı, aksi halde bir sonraki ajan yarım bir
  entegrasyonu "tamamlanmış" sanabilir.
- Kod tabanında büyük bir değişiklik (rebrand, domain geçişi, provider listesi değişikliği vb.)
  yaptıysan, hem repo dokümantasyonunu HEM de varsa harici bir bilgi kaynağını (bu projede: Hermes
  vault `03_Projects/Ki-Reservation/`) aynı oturumda güncelle — ikisi arasında tutarsızlık, "hangisi
  doğru" sorusuna yol açar.

### 6. Paralel oturum koordinasyonu

Aynı repo üzerinde başka bir ajan/oturum aynı anda çalışıyor olabilir (bu projede birden fazla kez
oldu). Deploy build context'i TÜM working directory'yi alır — başka bir oturumun commit edilmemiş
değişiklikleri araya karışabilir. Prod'a deploy etmeden önce `git status` ile beklenmedik değişiklik
var mı kontrol et; şüpheliyse `git worktree add --detach <tmp> <kendi-commit-sha>` ile SADECE kendi
commit'ini içeren temiz bir checkout'tan build al. Paylaşılan `git stash` stack'ini asla bare
`git stash`/`git stash pop` ile kullanma.

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
