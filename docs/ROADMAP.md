# BooKi — 52 Faz Yol Haritası

> Rakip analizine dayalı büyüme roadmap'inin devamı. Bu belge, kod tabanının doğrudan taranmasıyla (grep + dosya okuma, tahmin yok) hazırlanan boşluk analizini ve 5 dalgalık uygulama planını takip eder. Her dalga: izole Docker testi → diff doğrulama → `/code-review` → deploy disipliniyle ayrı ayrı tamamlanır ve burada işaretlenir.
>
> Görsel/interaktif versiyon: (Claude Artifact olarak yayınlandı, bu dosya kalıcı takip kaynağıdır.)

## Mimari Karar: Organization Katmanı

**Karar (onaylandı):** Ayrı bir "organization" tablosu/katmanı eklenmeyecek. **Her tenant = 1 organization.** Mevcut `Branches_model` (migration 108) zaten bir tenant'ın N branch'e sahip olabilmesini sağlıyor — bu, "organization → N location" hiyerarşisini zaten karşılıyor. Çoklu-organizasyon (bir şirketin birden fazla tenant/organizasyonu tek çatı altında yönetmesi) senaryosu **kapsam dışı**: her ayrı organizasyon ayrı bir tenant satın alımı olarak ele alınacak (mevcut SaaS lisans/tenant modeliyle uyumlu, ek mimari değişiklik gerekmiyor).

Bu karar Faz 1 ve Dalga 5'teki "Organization katmanı" maddesini kapsam dışı bırakır.

## Boşluk Analizi — 22 Kritik Alan

> Bu tablo roadmap'in BAŞLANGIÇ anlık görüntüsüdür (2026-08 öncesi durum). "Güncel (2026-09-17)"
> sütunu her alanın Dalgalar tamamlandıkça ulaştığı GERÇEK durumu gösterir - detay için ilgili
> Dalga bölümüne bakın.

| # | Alan | Başlangıç Durumu | Güncel (2026-09-17) | Not |
|---|------|-------------------|----------------------|-----|
| 1 | Organization / Multi-Location | ✅ Karar verildi | ✅ Değişmedi | Tenant = organization, Branches = location. |
| 2 | Customer Portal | 🟡 Kısmi | 🟡 Kısmi (değişmedi) | Profil/şifre/kalan seans var; randevu değiştirme/iptal, fatura, membership/loyalty görünümü hâlâ yok |
| 3 | Waitlist | ❌ Yok | ✅ Var, canlıda | Dalga 1 + Dalga 6 (ön-bilgilendirme genişletmesi) |
| 4 | Recurring Appointments | ❌ Yok | ✅ Var, canlıda | Dalga 1 |
| 5 | Memberships | ❌ Yok | ✅ Var, canlıda | Dalga 1 (otomatik online yenileme hâlâ yok, staff explicit `renew()`) |
| 6 | Gift Cards | ❌ Yok | ❌ Hâlâ yok | Roadmap'te yok, talep edilmedi |
| 7 | Invoicing (iç) | 🟡 Kısmi | ✅ İç fatura var; dış ERP kod hazır/kimlik bekliyor | Dalga 1 (iç) + Dalga 6 (Paraşüt/QuickBooks/Zoho Books gerçek API kodu, Logo/Mikro/İşbaşı bilinçli mock) |
| 8 | POS | ❌ Yok | ✅ Var; gerçek gateway'ler kısmen | Dalga 1 (abstraction) + Dalga 6 (Iyzico/Stripe/ÖdeAl gerçek kod, Garanti/Enpara banka onayı bekliyor) |
| 9 | Staff / HR | 🟡 Kısmi | 🟡 Kısmi (değişmedi) | Komisyon motoru güçlü; izin/bordro/devam takibi hâlâ yok |
| 10 | Communication Hub | 🟡 Kısmi | ✅ Var, canlıda | Dalga 3 Faz 3.1 + Dalga 6 çok-kanallı AI Asistan (WhatsApp/Telegram/Instagram) |
| 11 | Automation Engine | ❌ Yok | ✅ Var, canlıda | Dalga 3 Faz 3.2 |
| 12 | Marketing / Segments | ❌ Yok | ✅ Var, canlıda + Google/Meta Ads client'ları | Dalga 3 Faz 3.3 + Dalga 6 (gerçek GA4/Ads/Meta API kodu, veri kimliği bekliyor) |
| 13 | Public REST API | ✅ Var | ✅ Değişmedi | `/api/v1/` zaten kapsamlı |
| 14 | Outgoing Webhooks | ✅ Var | ✅ Değişmedi | `Webhooks_client.php` + `Webhooks.php` |
| 15 | MFA / TOTP | ❌ Yok | ✅ Var, canlıda | Dalga 2 |
| 16 | KVKK / GDPR | 🟡 Kısmi | ✅ Export/erasure akışı da tamam, canlıda | Dalga 2 |
| 17 | Otomatik Test Paketi | ❌ Yok | 🟡 PHPUnit bootstrap + Playwright E2E (30+8 senaryo) var; birim test listesi hâlâ yazılmadı | Dalga 2 (bootstrap) - asıl birim testler (booking conflict, encryption, tenant isolation) bilinçli ertelendi |
| 18 | Background Job / Queue | ❌ Yok | ✅ Var, canlıda | Dalga 2 |
| 19 | PWA / Service Worker | ❌ Yok | ❌ Hâlâ yok | Dalga 5, başlanmadı |
| 20 | License Sistemi | 🟡 Kısmi | 🟡 Kısmi (değişmedi) | Gerçek entitlement kontrolü hâlâ yok, Dalga 5 |
| 21 | SaaS Admin Paneli | 🟡 Kısmi | 🟡 Platform admin girişi + kirsv-mcp eklendi; metrikler hâlâ yok | Dalga 0.6/Dalga 5 açık |
| 22 | Localization | 🟡 Kısmi | 🟡 Kısmi (değişmedi) | 40+ dil dosyası hazır, aktif değil; currency config yok |

## Uygulama Planı — Öncelik Dalgaları

### 🔄 Dalga 0 — BooKi Markalaşması, Pazarlama & Büyüme Altyapısı (2026-09-16 başladı) — canlıya alındı, birkaç alt madde açık
- [x] **0.1 Kod tabanı yeniden markalaşması — TAMAMLANDI (2026-09-16)** — 562 dosyada "Ki Reservation" / "KI RESERVATION" → "BooKi" bulk sed (415 PHP dahil, php -l sıfır hata): views, email şablonları, error sayfaları, JS, CSS, composer.json (`BooKi - Online Appointment Scheduler`), `config-sample.php`, `index.php`, `installation.php`, backend_header fallback `'BooKi'`, backend_footer "Powered by BooKi (Ki Software License)", kullanıcıya görünen tüm başlıklar. DB tablo adları (`ki_reservation`, `ea_` prefix) BİLİNÇLİ değiştirilmedi (tombstone, Faz 0.11 opsiyonel). Alt marka "Ki Software" (kisoftware.com) firma adı olarak korundu. **Canlıya alındı (2026-09-16/17), commit `a2fe073` ve sonrası.**
- [x] **0.1b Repo konsolidasyonu — TAMAMLANDI (2026-09-16/17, commit `4127c2c`)** — geçici dev/prod-ayrı-repo modeli terk edildi, TEK kanonik repo (`/opt/ki-ecosystem/ki-reservation-src`, GitHub `miracmk/booki-saas`) `deploy/` (prod Docker/compose) ve `dev/` (yerel dev) alt dizinleriyle. Prod build context repo kökü (`deploy/docker-compose.yml` → `context: ..`). Gerçek DB/dosya verisi eski konumlarında (mutlak yol volume referanslarıyla). Dev ortamı: `ki-booki-dev-app`/`ki-booki-dev-db` container'ları, dev superadmin `admin/BookiAdmin#2026`, dev tenant `devsalon`.
- [x] **0.2 Landing sayfası — booki.kibusiness.co — CANLIDA** (commit `24929b5` "Production go-live: Landing") — React/Vite/Express (`ki-reservation-website` reposu, ayrı repo, `/opt/ki-ecosystem/websites/booki/`), Zoho CRM entegrasyonu (demo/deneme formları Lead oluşturuyor, OAuth onayı henüz kullanıcı tarafından yapılmadı).
- [x] **0.3 Domain & DNS & SSL — CANLIDA** — gerçek şema: `bookiapp.kibusiness.co` (kiracı girişi, `{subdomain}-bookiapp.kibusiness.co`), `admin-bookiapp.kibusiness.co` (superadmin, doğrulandı: `donkimonki` girişi 2026-09-17'de test edildi, çalışıyor), `booki.kibusiness.co` (landing/marketplace). **Not:** roadmap'in önceki taslağındaki `bookie-app.kibusiness.co`/`booki-admin.kibusiness.co` şeması KULLANILMADI, gerçek canlı şema yukarıdaki gibi kesinleşti (`deploy/docker-compose.yml`'deki `TENANT_APP_DOMAIN`/`SUPERADMIN_DOMAIN`/`MARKETPLACE_DOMAIN` ile teyitli). Eski domain'lerden 301 yönlendirmesi ayrıca doğrulanmalı.
- [x] **0.4a Google OAuth platform client — TAMAMLANDI (2026-09-17)** — `Console::google_config` ile master_settings'e client_id/secret yazıldı (Calendar sync + Marketing GA4/Ads ortak client). **0.4b Analytics veri bağlantısı — 🟡 AÇIK**: GA4 property ID, Google Ads customer ID/developer token, GSC verification, Meta Pixel/CAPI kimlikleri hâlâ kullanıcıdan bekleniyor - `Google_marketing_client`/`Meta_marketing_client` kod tarafı hazır (bkz. Dalga 6) ama gerçek veri akışı yok.
- [x] **0.5a Zoho CRM (platform/admin seviyesi) — kod TAMAMLANDI, gerçek kimlik BEKLİYOR** — `Crm_sync.php` (OAuth refresh-token + REST v8, outbox+id_map mimarisi, migration 138) uçtan uca dry-run ile doğrulandı; gerçek `zoho_client_id`/`secret`/`refresh_token` girilmeden kuyruk boşalmıyor. **0.5b Zoho CRM (landing/lead formu)** — ayrı, admin/platform CRM'inden bağımsız bir Zoho entegrasyonu landing sitesinde var, OAuth onayı (`/api/zoho/oauth/start`) kullanıcı tarafından henüz yapılmadı.
- [ ] **0.6 Kiracı metrikleri (SaaS admin)** — başına randevu/gelir/müşteri/aktif aylık metrikler superadmin panelinde
- [ ] **0.7 Paket & fiyatlandırma katmanı** — `ea_packages` fiyatlandırma şeması + plan-alan feature-flag
- [ ] **0.8 Ödeme takibi** — gelir/komisyon raporlaması (önceki POS/invoice katmanının üstüne SaaS). **Mevzuat:** mevcut para akışı yok; gerçek para/provizyon gerektiğinde lisanslı ödeme kuruluşu + tüketici koruma incelemesi AYRI konu.
- [ ] **0.9 Harici integrasyonlar** — Google Business Profile randevu linki standartları, Meta OAuth durumu, Zoho CRM pipeline; OAuth/API anahtarları kullanıcıdan bekleniyor
- [ ] **0.10 Superadmin genişletmeleri** — tenant provisioning UX, saklı anahtarlar, plan yönetimi
- [ ] **0.11 (opsiyonel) Tombstone** — `ki_reservation` adlı tablo/sütun/değişken/fil adı kalıntıları temizliği (işlevsel etki yok)
- [ ] **0.12 Test & validasyon** — dev'de uçtan uca senaryolar (tenant oluştur → randevu → lead → analitik), sonra canlıya sunum

### ✅ Dalga 1 — Gelir ve Operasyon Temeli (Faz 7·8·9·12·13) — TAMAMLANDI + CANLIYA ALINDI (2026-08-28)
- [x] Recurring Appointments — `id_recurrence_group` nullable kolon, seri randevu mantığı (commit `1d8e930`, canlıya deploy edildi)
- [x] Waitlist — dolu slota katılma, boşalınca SMS/WhatsApp bildirimi (commit `b2542c9`, canlıya deploy edildi; 2026-09-17'de ön-bilgilendirme genişletmesi — bkz. Dalga 6)
- [x] Memberships — abonelik planı, kullanım hakları (commit `40ab0b5`; otomatik ÇEVRİMİÇİ yenileme yok - gateway'de kayıtlı-kart/off-session tahsilat yeteneği olmadığından staff explicit `renew()` ile kaydediyor, lazy past_due/expired geçişi var; canlıya deploy edildi)
- [x] Invoicing (iç) — invoice/invoice_items, appointment+package+product birleşik fatura (commit `fe6cbc3`, salt-okunur agregasyon, payment_transactions'a dokunmuyor; canlıya deploy edildi; ERP dış senkron 2026-09-17'de genişletildi — bkz. Dalga 6)
- [x] POS — order/order_items abstraction, mevcut payment gateway'lere bağlı (commit `e1a0588`; `payment_transactions` ENUM genişletmesi, mevcut deposit akışı regresyonsuz; canlıya deploy edildi; gerçek gateway entegrasyonları 2026-09-17'de genişletildi — bkz. Dalga 6)

**Not (2026-09-17):** `docs/SESSION_NOTES.md` §Dalga 1 kaydı bu 6 fazın 2026-08-28'de canlıya (`/opt/ki-ecosystem/ki-reservation`) deploy edildiğini doğruluyor (474 müşteri/58 randevu veri kaybı yok, booking 200) — bu roadmap'in önceki "henüz canlıya deploy edilmedi" notu YANLIŞTI, düzeltildi.

**Risk:** Invoice+POS mevcut `Payment_transactions` ile çakışmamalı — additive, feature-flagged (Faz 1 deposit akışı gibi).

### ✅ Dalga 2 — Güvenlik ve Güvenilirlik (Faz 29·30·31·32·33) — TAMAMLANDI (2026-08-28, canlıya deploy edildi)
- [x] MFA/TOTP — admin+provider+secretary girişleri için opsiyonel TOTP + backup kodları TAMAMLANDI (commit `75b1d34`); gerçek Docker çalıştırmasında bulunup düzeltilen kritik hata: robthree/twofactorauth kütüphanesinin gerçek constructor imzası (IQRCodeProvider zorunlu, issuer string değil) - bacon/bacon-qr-code + SVG render ile çözüldü; superadmin/müşteri MFA'sı bilinçli kapsam dışı. **Deploy sırasında ek kritik hata bulundu:** composer bağımlılıkları (robthree/bacon) hiçbir gerçek Docker build'ine hiç girmemişti (base image'ın vendor/'ı stok EasyAppointments'tan geliyordu, Dockerfile'da composer install/update adımı yoktu) - production Dockerfile'a composer:2 multi-stage build eklendi, gerçek TOTP akışı (kurulum→etkinleştirme→giriş) ilk kez uçtan uca doğrulandı; canlıya deploy edildi.
- [x] KVKK/GDPR tamamlama — veri indirme + hesap silme uçtan uca akışı TAMAMLANDI (commit `1edba98`, `5579c89`, `af107dd`): data_requests tablosu + export/erasure model katmanı; Data_export kütüphanesi (JSON+HTML+BENIOKU.txt paketleme, zip/loose-file fallback, Job_dispatcher'a `data_requests.export` handler'ı); Customer_portal KVKK kartı (self-service export/erasure talebi, token tabanlı indirme) + admin Data_requests paneli (silme talebi onay/red, PRIV_CUSTOMERS yeniden kullanıldı); Console::process_data_requests() + Cleanup::cleanup_data_exports(). Gerçek Docker + gerçek HTTP/çerez oturumuyla uçtan uca doğrulandı; 5 gerçek hata bulunup düzeltildi (GROUP BY eksikliği, yanlış sütun adı, export dizini izin uyumsuzluğu 0750→0755, iki sayfada eksik layout wrapping + `asset()` yerine `asset_url()`); canlıya deploy edildi.
- [~] Otomatik test paketi — PHPUnit bootstrap TAMAMLANDI (commit `b294424`, gerçek Docker'da çalıştırıldı: 17 test/27 assertion, exit 0); asıl test listesi (booking conflict, station allocation, commission, encryption, tenant isolation) bilinçli olarak ERTELENDİ, ayrı bir tur gerektiriyor
- [x] Background job sistemi — DB-tabanlı birleşik `jobs` kuyruğu + Console::process_jobs() + 6 senkron gönderim noktasının (SMS/WhatsApp/Telegram/appointment-saved email x4) kuyruğa bağlanması TAMAMLANDI (commit `e05f50d`, `2279227`); appointment-DELETED email ve password-reset e-postası bilinçli olarak senkron kaldı; canlıya deploy edildi.
- [x] Observability — yapılandırılmış JSON log (EA_Log, CI3'ün kırık büyük/küçük harf hatası düzeltildi), health endpoint (/health, /health/deep), jobs izleme sayfası (commit `f3eaec3`); canlıya deploy edildi.
- [x] **Ek düzeltme (kapsam dışı ama aynı turda bulunup düzeltildi):** waitlist.php/memberships.php/pos.php/products.php/invoices.php/packages.php/jobs.php sayfalarının hiçbiri `extend()`/`section()` şablon mekanizmasını çağırmıyordu (backend header/nav olmadan çıplak render ediliyorlardı) ve var olmayan `asset()` fonksiyonunu çağırıyorlardı - 7 sayfa da düzeltildi (commit `af107dd`, `7f6816f`).

**Not:** products.php/packages.php sayfalarında AYRI ve önceden var olan bir sorun kaldı - `ea_roles` tablosunda bu iki özellik için izin sütunu hiç yok, administrator rolü bile 403 alıyor. Bu bir permission-şema düzeltmesi gerektiriyor, ayrı bir tur.

### 🟡 Dalga 3 — Otomasyon ve Büyüme (Faz 18·21·22·23·24·25) — WhatsApp gerçek cihaz testi hariç TAMAMLANDI
- [ ] **WhatsApp dual-mode** (dalganın ilk maddesi, kullanıcı talebiyle 2026-08-28'de eklendi) — her iki bağlantı yöntemi de kurulacak: (1) resmi Meta Business API kurulum sihirbazı (mevcut altyapı üzerine, kiracı bazlı onboarding), (2) QR kod ile bağlanan resmi-olmayan alternatif. QR seçeneği tenant admin panelinde AÇIKÇA risk uyarısıyla sunulacak (ToS ihlali riski, gerçek işletme numarasının yasaklanma riski) — kullanıcı bilgilendirilmiş onayla seçiyor. **Faz 3.5 Part 1 + Part 2 TAMAM, canlıda** (2026-09-10): migration 133 (dual-mode ayarları + `users.whatsapp_wa_id`), mod seçici + bilgilendirilmiş onay, resmi taraf `check_connection`/test send, resmi-olmayan taraf için `Whatsapp_bridge` REST client + controller endpoint'leri (save_mode/save_bridge/qr_start/qr_status/qr_logout/bridge_inbound) + Node sidecar REST sözleşmesi (`docs/whatsapp-bridge-contract.md`); **Part 2**: `ki-wa-bridge` Node/Baileys container inşası (bridge/ dizini, node:24-alpine, deploy compose servisi, WA_BRIDGE_SECRET) + sözleşme doğrulaması (health/start/status+gerçek QR/send-not-paired/logout; PHP kütüphanesiyle uçtan uca). **Kalan (kullanıcıda, kullanıcı kendi yapacak):** telefonla canlı QR eşleştirme + mesaj yuvarlak testi, resmi Meta onboarding canlı test.
- [x] Communication Hub — event sistemi (appointment.created/completed/cancelled → kanal seçimi) — **Faz 3.1, TAMAMLANDI, canlıda** (2026-09-04)
- [x] Automation Engine — WHEN/IF/THEN kuralları, 5-6 hazır şablon — **Faz 3.2, TAMAMLANDI, canlıda** (2026-09-08)
- [x] Marketing — segment (VIP/inaktif/doğum günü) + kampanya gönderimi — **Faz 3.3, TAMAMLANDI, canlıda** (2026-09-09): migration 130 (platform/google_ads_gateway_token ayarları) + migration 131 (marketing_segments/marketing_campaigns/campaign_recipients + ea_roles.marketing admin=15); Pazarlama paneli (segment CRUD + kampanya CRUD), `Campaigns_model::prepare_broadcast()` (idempotent) + `send_batch()` (50'lik batch, draft→queued→sending→sent), merge alanları `{{customer_name}}`/`{{customer_first_name}}`/`{{company_name}}`, kanallar e-posta/SMS/WhatsApp/Telegram; console `marketing_segments`/`marketing_refresh`/`marketing_campaigns`; 516 müşterili smoke test + tam sayfa render + auth gate doğrulandı
- [x] Review Engine genişletme — randevu-sonrası otomatik review isteği — **Faz 3.4, TAMAMLANDI, canlıda** (2026-09-09): migration 132 (tenant `reviews` tablosu + UNIQUE token, `reviews_enabled` ayarı, `ea_roles.reviews` admin=15, seeded "Değerlendirme isteği" kuralını `review_request` aksiyonuna up-convert etme); `Review_service` (atomik token claim + master mirror + marketplace için çapraz-tenant doğrulama), `Reviews_model` (requested→pending→published/rejected yaşam döngüsü), public tek-kullanımlık form `GET /review/index/{token}` + `POST /review/submit` (CSRF + 1-5 yıldız + 2000 karakter), tenant admin moderasyon sayfası (pending/published/rejected sekmeleri), `PRIV_REVIEWS`, console `review_issue`/`reviews list`/`review_status`; marketplace güvenlik düzeltmesi: anonim form kaldırıldı, `submit_review` artık tek-kullanımlık tenant token'ı zorunlu kılıyor; 2 canlı test kaydı + migration başarıyla uygulandı, form/gate/CSRF/validation canlı doğrulandı, code+DB+syntax checkleri temiz
- [x] Analytics/BI — revenue/utilization/retention dashboard'ları — **Faz 3.6, TAMAMLANDI, canlıda** (2026-09-10): migration 135 (appointments tablosuna 4 composite index — provider/service/customer/status bazlı); `Reports_model` (revenue hesaplama mantığı `Reports::get_daily_revenue()`'dan çıkarılıp `get_revenue_rows()`/`compute_row_metrics()` olarak yeniden kullanılabilir hale getirildi — davranış birebir korunarak, canlı regresyon testiyle doğrulandı; yeni `calculate_available_minutes()` provider'ın working_plan + working_plan_exceptions'ını okuyarak kapasite hesaplıyor); `Reports.php`'ye 3 yeni endpoint: `get_revenue_report` (gün/hafta/ay trend + provider/service kırılımı), `get_utilization_report` (booked/available dakika oranı), `get_retention_report` (yeni/dönen müşteri, churn % — provider rolüne kapalı); basit JSON-görünümlü view (tasarım henüz ilkel, ayrı bir turda iyileştirilecek); tüm 3 endpoint canlı gerçek veriyle test edildi.

### 🔲 Dalga 4 — Marketplace Olgunlaştırma (Faz 41·42·43·44·45)
- [ ] Marketplace Ranking — mesafe + puan + müsaitlik skoru
- [ ] Review güvenliği — sadece gerçekleşmiş randevusu olan müşteri review bırakabilsin
- [ ] Marketplace Revenue — basit sabit komisyon oranı
- [ ] Wallet — muhasebe/takip katmanı (gerçek para transferi değil — lisanslı ödeme kuruluşu gerektirir, mevzuat incelemesi ayrı)

### 🔲 Dalga 5 — Kurumsal / Ölçek (Faz 34·48·49·50·51·52)
- [x] ~~Organization katmanı~~ — karar verildi, ek iş gerekmiyor (yukarı bkz.)
- [ ] PWA — manifest.json + service worker, önce sağlayıcı (staff) günlük görünümü
- [x] **Custom domain — sırasından ÖNE ÇEKİLİP TAMAMLANDI, canlıda (2026-09-10, commit `368201c`,
      `64255a1`, `4329bd5`)** — tenant self-service akış (domain talep → DNS TXT/CNAME doğrulama → host
      cron ile otomatik sertifika+nginx). Mimari detay: `docs/SESSION_NOTES.md` bölüm 7.4. "Powered by Ki"
      kapatma (branding toggle) kısmı bu kapsamda DEĞİL, hâlâ yapılmadı.
- [ ] White-label — "Powered by Ki" kapatma (branding toggle — custom domain'den ayrı, yukarı bkz.)
- [ ] SaaS Admin genişletme — abonelik/fatura/kullanım metrikleri
- [ ] License/Entitlement sistemi — plan alanını gerçek feature-flag kontrolüne bağla
- [ ] Dokümantasyon — /docs, en son (önceki dalgalar API/özellik ekledikçe güncellenecek)

### 🟢 Roadmap-dışı ek — Command Center Dashboard + Tema Motoru (2026-09-10, canlıda — commit `368201c`)
- [x] Yeni gerçek Dashboard landing sayfası (önceden yoktu — giriş direkt Takvim'e düşüyordu). Gerçek
      KPI'lar (bugünkü randevu/gelir/aktif seans/doluluk), takvimin mevcut canlı-seans altyapısını yeniden
      kullanıyor. Detay: `docs/SESSION_NOTES.md` bölüm 7.3.
- [x] Görsel tema katmanı: 4 renk ailesi × 3 ton + özel 3'lü palet, mevcut Bootstrap temasının üstüne
      (view/controller/model'e dokunmadan). Detay: bölüm 7.2.
- [ ] Faz 3.6 Analytics view'i (şu an ham JSON) yeni Dashboard'un görsel diline henüz TAŞINMADI — ayrı iş.

### 🟡 Dalga 6 — 8 Sayfa Denetimi, Çok-Kanallı AI Asistanı, Kurumsal Genişletmeler (2026-09-17)

- [x] **8 sayfa görsel/CRUD denetimi — TAMAMLANDI, canlıda (commit `07d49ba`)** — Bekleme Listesi, Üyelikler, Veri Talepleri, Faturalar, POS, Raporlar, Pazarlama, Yorumlar sayfaları Customers/Services/Providers ile görsel tutarlılığa getirildi; `tests/e2e/eight_pages_crud.spec.js` (8/8 doğrulandı, birden çok turda tekrar edildi).
- [x] **Çok-kanallı AI Asistan (WhatsApp/Telegram/Instagram) — TAMAMLANDI, canlıda (commit `07d49ba`, migration 149)** — 9 kiracıya uygulandı. Güvenlik ilkesi: müşteri mesajları hiçbir zaman doğrudan yazma yapamaz, tek mutation yolu `propose_customer_update` → `ea_ai_agent_pending_changes` kuyruğu → yönetici onayı. Instagram Direct + Meta Graph API webhook'ları + panel ayarları hazır; **gerçek Meta/Instagram kimlik bilgisi ile canlı test edilmedi** (kullanıcı Meta sandbox'ı kendi kuracak).
- [x] **Kurumsal Genişletmeler: Marketing Suite, Çoklu POS, ERP, Reviews, Waitlist — TAMAMLANDI (commit `d418929`, migration 150)** — `reviews` tablosuna provider/istasyon puanı, `payment_settings`'e ÖdeAl/Garanti/Enpara alanları, `landing_pages`/`traffic_attributions` tabloları; kampanya duraklat/sürdür + landing page yönetimi + web telemetrisi/attribution; `Payment_gateway_factory` + 5 gateway; `Erp_manager` + ERP payload'ları; sağlayıcı/istasyon ayrı puanlama + moderasyon; bekleme listesi ön-bilgilendirme.
- [x] **Marketing: gerçek Google/Meta reklam client'ları + MCP — TAMAMLANDI (commit `fa833e0`)** — `Google_marketing_client.php` (GA4 realtime/report, Google Ads GAQL search+mutate), `Meta_marketing_client.php` (Marketing API campaigns+insights), Agent_api.php'de 5 MCP-tüketimli endpoint, `deploy/mcp/reservation-mcp/server.js`'de 5 MCP tool'u.
- [x] **Google OAuth platform client — TAMAMLANDI (commit `1f44860`)** — `Console::google_config`, kullanıcının verdiği client_id/secret master_settings'e maskeli yazıldı.
- [x] **POS gateway + ERP API dokümantasyon araştırması ve gerçek entegrasyon düzeltmeleri — TAMAMLANDI (commit `f5f6de9`)**:
  - **Iyzico:** imza şeması YANLIŞTI (401 ile reddedilirdi) → gerçek IYZWSv2 HMAC şeması + doğru endpoint path'leri.
  - **Stripe:** tamamen mock'tan gerçek `PaymentIntents`/`Refunds` API'sine geçirildi; webhook imza doğrulaması güvenlik açığı (her zaman `true` dönüyordu) düzeltildi.
  - **ÖdeAl:** uydurma domain (`paym.com.tr`) → gerçek domain + OAuth2 token akışı.
  - **Garanti/Enpara:** gerçek şema halka açık değil (banka başvurusu gerekiyor) — bilinçli mock, nedeni kodda belgeli.
  - **Erp_manager::PROVIDERS düzeltildi** (yanlışlıkla `bizimhesap` içeriyordu, kullanıcının istediği `İşbaşı`/`QuickBooks`/`Zoho Books` yoktu) → `Quickbooks_connector.php`, `Zohobooks_connector.php` (yeni, gerçek OAuth2+fatura API'si) eklendi; Logo/Mikro/İşbaşı merkezi API'leri olmadığı için bilinçli mock kaldı.
  - `Console::erp_config` eklendi; üç `*_config` komutundaki maskeleme bug'ı (`null !== ''` TRUE sorunu) düzeltildi.
- [x] **Platform admin + kirsv-mcp + Zoho CRM outbox — TAMAMLANDI (2026-09-16, `docs/SESSION_NOTES.md` §8.1)** — `donkimonki` master admin hesabı (doğrulandı, 2026-09-17), `kirsv-mcp` REST MCP sunucusu canlıda, `Crm_sync.php` (migration 138, outbox+id_map) uçtan uca dry-run ile doğrulandı.

**Bu dalgadan sonra AÇIK kalanlar (kullanıcıdan gerçek kimlik bilgisi/karar bekleyen):**
1. Stripe/Iyzico/ÖdeAl gerçek sandbox API anahtarları (kod hazır, hiç gerçek çağrı denenmedi).
2. Garanti/Enpara: banka başvurusu olmadan ilerlenemez.
3. QuickBooks/Zoho Books: OAuth app oluşturup `console erp_config` ile refresh_token girilmeli.
4. İşbaşı: kullanıcı önce kendi hesabından API key talep etmeli.
5. Paraşüt: gerçek endpoint path'i (`apidocs.parasut.com` bot korumalı) gerçek kimlikle test edilmeden production'a güvenilmemeli.
6. Google Marketing (GA4/Ads): client_id/secret var ama gerçek OAuth consent akışı (analytics.readonly+adwords scope, mevcut Calendar-only akıştan AYRI) henüz yazılmadı — `get_access_token()` hâlâ ham/manuel yapıştırılan bir token bekliyor.
7. Meta/Instagram gerçek sandbox kimlik bilgisiyle çok-kanallı AI Asistan canlı testi.
8. Zoho CRM (hem platform hem landing/lead formu) gerçek kimlik bilgisi.

## Kapsam Dışı / Ayrı Konu

| Faz | Neden bu turda değil |
|-----|----------------------|
| Faz 36 — AI Voice Receptionist | Telefon/SIP altyapısı gerektirir, mevcut Whisper akışından ayrı proje |
| Faz 26 (API genişletme) | API zaten var — yeni kaynaklar eklendikçe (membership, gift-card vb.) endpoint eklenecek, ayrı faz değil |
| Çoklu-organizasyon (tek çatı altında N tenant) | Kullanıcı kararı: her organizasyon ayrı tenant satın alımı olarak kalacak |

---
*Son güncelleme (2026-09-17): Dalga 0 canlıya alındı (BooKi rebrand, landing, domain şeması, Google OAuth client), Dalga 1 canlı deploy notu düzeltildi (yanlışlıkla "deploy edilmedi" yazıyordu), Dalga 6 eklendi (8 sayfa denetimi, çok-kanallı AI Asistan, Marketing/POS/ERP kurumsal genişletmeleri + gerçek API doküman araştırması ve düzeltmeleri). Güncel git HEAD: `f5f6de9`. Detay: `docs/SESSION_NOTES.md`.*
