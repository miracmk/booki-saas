# BooKi — 52 Faz Yol Haritası

> Rakip analizine dayalı büyüme roadmap'inin devamı. Bu belge, kod tabanının doğrudan taranmasıyla (grep + dosya okuma, tahmin yok) hazırlanan boşluk analizini ve 5 dalgalık uygulama planını takip eder. Her dalga: izole Docker testi → diff doğrulama → `/code-review` → deploy disipliniyle ayrı ayrı tamamlanır ve burada işaretlenir.
>
> Görsel/interaktif versiyon: (Claude Artifact olarak yayınlandı, bu dosya kalıcı takip kaynağıdır.)

## Mimari Karar: Organization Katmanı

**Karar (onaylandı):** Ayrı bir "organization" tablosu/katmanı eklenmeyecek. **Her tenant = 1 organization.** Mevcut `Branches_model` (migration 108) zaten bir tenant'ın N branch'e sahip olabilmesini sağlıyor — bu, "organization → N location" hiyerarşisini zaten karşılıyor. Çoklu-organizasyon (bir şirketin birden fazla tenant/organizasyonu tek çatı altında yönetmesi) senaryosu **kapsam dışı**: her ayrı organizasyon ayrı bir tenant satın alımı olarak ele alınacak (mevcut SaaS lisans/tenant modeliyle uyumlu, ek mimari değişiklik gerekmiyor).

Bu karar Faz 1 ve Dalga 5'teki "Organization katmanı" maddesini kapsam dışı bırakır.

## Boşluk Analizi — 22 Kritik Alan

| # | Alan | Durum | Not |
|---|------|-------|-----|
| 1 | Organization / Multi-Location | ✅ Karar verildi | Tenant = organization, Branches = location. Ek iş yok. |
| 2 | Customer Portal | 🟡 Kısmi | Profil/şifre/kalan seans var; randevu değiştirme/iptal, fatura, membership/loyalty görünümü yok |
| 3 | Waitlist | ❌ Yok | — |
| 4 | Recurring Appointments | ❌ Yok | — |
| 5 | Memberships | ❌ Yok | Packages (seans paketi) var, abonelik modeli yok |
| 6 | Gift Cards | ❌ Yok | — |
| 7 | Invoicing (iç) | 🟡 Kısmi | Sadece dış ERP (Paraşüt) OAuth bağlantısı var, kendi fatura üretimi yok |
| 8 | POS | ❌ Yok | order/order_items abstraction yok |
| 9 | Staff / HR | 🟡 Kısmi | Komisyon motoru güçlü (provider_service_commissions); izin/bordro/devam takibi yok |
| 10 | Communication Hub | 🟡 Kısmi | SMS/WhatsApp/Email ayrı ayrı var, genel event→kanal sistemi yok |
| 11 | Automation Engine | ❌ Yok | — |
| 12 | Marketing / Segments | ❌ Yok | — |
| 13 | Public REST API | ✅ Var | `/api/v1/` zaten kapsamlı (appointments, customers, providers, services, vb.) |
| 14 | Outgoing Webhooks | ✅ Var | `Webhooks_client.php` + `Webhooks.php` zaten üretimde |
| 15 | MFA / TOTP | ❌ Yok | En kritik güvenlik boşluğu |
| 16 | KVKK / GDPR | 🟡 Kısmi | Migration 091 rıza/politika var; export/erasure akışı yok |
| 17 | Otomatik Test Paketi | ❌ Yok | PHPUnit hiç kurulu değil |
| 18 | Background Job / Queue | ❌ Yok | SMS/WhatsApp/email hâlâ senkron |
| 19 | PWA / Service Worker | ❌ Yok | — |
| 20 | License Sistemi | 🟡 Kısmi | Sadece bilgi amaçlı plan/tarih alanları, gerçek entitlement kontrolü yok |
| 21 | SaaS Admin Paneli | 🟡 Kısmi | Tenant CRUD var; abonelik/fatura/kullanım metrikleri yok |
| 22 | Localization | 🟡 Kısmi | 40+ dil dosyası hazır, sadece aktif değil; currency config yok |

## Uygulama Planı — Öncelik Dalgaları

### 🔄 Dalga 0 — BooKi Markalaşması, Pazarlama & Büyüme Altyapısı (2026-09-16, kullanıcı talebiyle yeni dalga) — dev ortamda ilerliyor, canlıya dokunulmadı
- [x] **0.1 Kod tabanı yeniden markalaşması — TAMAMLANDI (2026-09-16, dev'de doğrulandı)** — 562 dosyada "Ki Reservation" / "KI RESERVATION" → "BooKi" bulk sed (415 PHP dahil, php -l sıfır hata): views, email şablonları, error sayfaları, JS, CSS, composer.json (`BooKi - Online Appointment Scheduler`), `config-sample.php`, `index.php`, `installation.php`, backend_header fallback `'BooKi'`, backend_footer "Powered by BooKi (Ki Software License)", kullanıcıya görünen tüm başlıklar. DB tablo adları (`ki_reservation`, `ea_` prefix) BİLİNÇLİ değiştirilmedi (tombstone, Faz 0.11 opsiyonel). Alt marka "Ki Software" (kisoftware.com) firma adı olarak korundu.
- [x] **0.1b İki-aşamalı dev/prod ortamı — TAMAMLANDI (2026-09-16)** — kanonik repo `miracmk/booki-saas`, dev deploy `/opt/ki-ecosystem/ki-booki-dev/` (containers `ki-booki-dev-app` 8080+8081, `ki-booki-dev-db` 3307; DB `ki_booki_dev_master` + dev superadmin `admin/BookiAdmin#2026` + dev tenant `devsalon`), `scripts/dev-sync.sh` kanonik→dev rsync, `/etc/hosts` `booki-app.dev booki-admin.dev booki.dev devsalon-booki-app.dev`. Dev'de canlı test edildi: /health OK, portal 200, tenant 200, superadmin 200. Prod deploy dizini (`ki-reservation/`) VE canlı ortam HİÇBİR değişiklik almadı.
- [ ] **0.2 Landing sayfası — booki.kibusiness.co** — clean server-rendered PHP (SPA değil), design reference manus.space kulesi (tokens: `--primary #1b5e64`, `--navy #0a1724`, Manrope + DM Serif Display + DM Sans); SEO meta, canlı arama motoru, lead formu buraya bağlanacak
- [ ] **0.3 Domain & DNS & SSL** — Cloudflare CNAMEs: `booki.kibusiness.co` (landing), `bookie-app.kibusiness.co` (tenant app), `booki-admin.kibusiness.co` (superadmin); eski domain'ler (`reservationapp/reservationadmin/reservation.kibusiness.co`) 301 ile yeni url'lere yönlendirilecek; NPM Let's Encrypt sertifikaları; prod switchover SONRA
- [ ] **0.4 Analytics (GA4 + GTM + Google Ads + Meta Pixel + GSC)** — `google_analytics_settings` / `master_settings` üzerinden cloud_id & measurement_id; placement tracking kullanıcıdan gelecek (GA4 ID, GTM ID, Ads ID, Pixel ID, GSC verification bekleniyor)
- [ ] **0.5 Lead formu & CRM (Zoho)** — landing üzerinde lead form → `ea_leads` (master DB) → e-posta bildirimi → Zoho CRM; Zoho credentials kullanıcıdan bekleniyor
- [ ] **0.6 Kiracı metrikleri (SaaS admin)** — başına randevu/gelir/müşteri/aktif aylık metrikler superadmin panelinde
- [ ] **0.7 Paket & fiyatlandırma katmanı** — `ea_packages` fiyatlandırma şeması + plan-alan feature-flag
- [ ] **0.8 Ödeme takibi** — gelir/komisyon raporlaması (önceki POS/invoice katmanının üstüne SaaS). **Mevzuat:** mevcut para akışı yok; gerçek para/provizyon gerektiğinde lisanslı ödeme kuruluşu + tüketici koruma incelemesi AYRI konu.
- [ ] **0.9 Harici integrasyonlar** — Google Business Profile randevu linki standartları, Meta OAuth durumu, Zoho CRM pipeline; OAuth/API anahtarları kullanıcıdan bekleniyor
- [ ] **0.10 Superadmin genişletmeleri** — tenant provisioning UX, saklı anahtarlar, plan yönetimi
- [ ] **0.11 (opsiyonel) Tombstone** — `ki_reservation` adlı tablo/sütun/değişken/fil adı kalıntıları temizliği (işlevsel etki yok)
- [ ] **0.12 Test & validasyon** — dev'de uçtan uca senaryolar (tenant oluştur → randevu → lead → analitik), sonra canlıya sunum

### ✅ Dalga 1 — Gelir ve Operasyon Temeli (Faz 7·8·9·12·13) — TAMAMLANDI (2026-08-28, izole Docker'da doğrulandı, canlıya deploy edilmedi)
- [x] Recurring Appointments — `id_recurrence_group` nullable kolon, seri randevu mantığı (commit `1d8e930`, izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Waitlist — dolu slota katılma, boşalınca SMS/WhatsApp bildirimi (commit `b2542c9`, izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Memberships — abonelik planı, kullanım hakları (commit `40ab0b5`; otomatik ÇEVRİMİÇİ yenileme yok - gateway'de kayıtlı-kart/off-session tahsilat yeteneği olmadığından staff explicit `renew()` ile kaydediyor, lazy past_due/expired geçişi var; izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Invoicing (iç) — invoice/invoice_items, appointment+package+product birleşik fatura (commit `fe6cbc3`, salt-okunur agregasyon, payment_transactions'a dokunmuyor; izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] POS — order/order_items abstraction, mevcut payment gateway'lere bağlı (commit `e1a0588`; tek riskli migration burada uygulandı - `payment_transactions` ENUM genişletmesi, mevcut deposit akışı izole Docker'da regresyonsuz doğrulandı; henüz canlıya deploy edilmedi)

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
- [x] **Custom domain — sırasından ÖNE ÇEKİLİP TAMAMLANDI (2026-09-10, kullanıcı talebiyle, henüz commit
      edilmedi)** — tenant self-service akış (domain talep → DNS TXT/CNAME doğrulama → host cron ile
      otomatik sertifika+nginx). Mimari detay: `docs/SESSION_NOTES.md` bölüm 7.4. "Powered by Ki" kapatma
      (branding toggle) kısmı bu kapsamda DEĞİL, hâlâ yapılmadı.
- [ ] White-label — "Powered by Ki" kapatma (branding toggle — custom domain'den ayrı, yukarı bkz.)
- [ ] SaaS Admin genişletme — abonelik/fatura/kullanım metrikleri
- [ ] License/Entitlement sistemi — plan alanını gerçek feature-flag kontrolüne bağla
- [ ] Dokümantasyon — /docs, en son (önceki dalgalar API/özellik ekledikçe güncellenecek)

### 🟢 Roadmap-dışı ek — Command Center Dashboard + Tema Motoru (2026-09-10, kullanıcı talebiyle, henüz commit edilmedi)
- [x] Yeni gerçek Dashboard landing sayfası (önceden yoktu — giriş direkt Takvim'e düşüyordu). Gerçek
      KPI'lar (bugünkü randevu/gelir/aktif seans/doluluk), takvimin mevcut canlı-seans altyapısını yeniden
      kullanıyor. Detay: `docs/SESSION_NOTES.md` bölüm 7.3.
- [x] Görsel tema katmanı: 4 renk ailesi × 3 ton + özel 3'lü palet, mevcut Bootstrap temasının üstüne
      (view/controller/model'e dokunmadan). Detay: bölüm 7.2.
- [ ] Faz 3.6 Analytics view'i (şu an ham JSON) yeni Dashboard'un görsel diline henüz TAŞINMADI — ayrı iş.

## Kapsam Dışı / Ayrı Konu

| Faz | Neden bu turda değil |
|-----|----------------------|
| Faz 36 — AI Voice Receptionist | Telefon/SIP altyapısı gerektirir, mevcut Whisper akışından ayrı proje |
| Faz 26 (API genişletme) | API zaten var — yeni kaynaklar eklendikçe (membership, gift-card vb.) endpoint eklenecek, ayrı faz değil |
| Çoklu-organizasyon (tek çatı altında N tenant) | Kullanıcı kararı: her organizasyon ayrı tenant satın alımı olarak kalacak |

---
*Son güncelleme (2026-09-16, oturum 9): Dalga 0 başladı — repo `booki-saas` oldu, iki-aşamalı dev/prod ortamı kuruldu (dev: `ki-booki-dev`, prod dokunulmadı), kod tabanı %100 "BooKi" markasına geçildi (562 dosya, 415 PHP clean lint, dev'de tüm ekranlar doğrulandı). Dev superadmin + ilk dev tenant'ı (`devsalon`) çalışıyor. Sıradaki: Dalga 0.2 landing sayfası — detay `docs/SESSION_NOTES.md` bölüm 9.*
