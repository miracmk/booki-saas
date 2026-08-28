# Ki Reservation — 52 Faz Yol Haritası

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

### ✅ Dalga 1 — Gelir ve Operasyon Temeli (Faz 7·8·9·12·13) — TAMAMLANDI (2026-08-28, izole Docker'da doğrulandı, canlıya deploy edilmedi)
- [x] Recurring Appointments — `id_recurrence_group` nullable kolon, seri randevu mantığı (commit `1d8e930`, izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Waitlist — dolu slota katılma, boşalınca SMS/WhatsApp bildirimi (commit `b2542c9`, izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Memberships — abonelik planı, kullanım hakları (commit `40ab0b5`; otomatik ÇEVRİMİÇİ yenileme yok - gateway'de kayıtlı-kart/off-session tahsilat yeteneği olmadığından staff explicit `renew()` ile kaydediyor, lazy past_due/expired geçişi var; izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] Invoicing (iç) — invoice/invoice_items, appointment+package+product birleşik fatura (commit `fe6cbc3`, salt-okunur agregasyon, payment_transactions'a dokunmuyor; izole Docker'da doğrulandı, henüz canlıya deploy edilmedi)
- [x] POS — order/order_items abstraction, mevcut payment gateway'lere bağlı (commit `e1a0588`; tek riskli migration burada uygulandı - `payment_transactions` ENUM genişletmesi, mevcut deposit akışı izole Docker'da regresyonsuz doğrulandı; henüz canlıya deploy edilmedi)

**Risk:** Invoice+POS mevcut `Payment_transactions` ile çakışmamalı — additive, feature-flagged (Faz 1 deposit akışı gibi).

### 🔲 Dalga 2 — Güvenlik ve Güvenilirlik (Faz 29·30·31·32·33)
- [x] MFA/TOTP — admin+provider+secretary girişleri için opsiyonel TOTP + backup kodları TAMAMLANDI (commit `75b1d34`); gerçek Docker çalıştırmasında bulunup düzeltilen kritik hata: robthree/twofactorauth kütüphanesinin gerçek constructor imzası (IQRCodeProvider zorunlu, issuer string değil) - bacon/bacon-qr-code + SVG render ile çözüldü; superadmin/müşteri MFA'sı bilinçli kapsam dışı; izole Docker'da gerçek çalıştırmayla doğrulandı, henüz canlıya deploy edilmedi
- [x] KVKK/GDPR tamamlama — veri indirme + hesap silme uçtan uca akışı TAMAMLANDI (commit `1edba98`, `5579c89`, `af107dd`): data_requests tablosu + export/erasure model katmanı; Data_export kütüphanesi (JSON+HTML+BENIOKU.txt paketleme, zip/loose-file fallback, Job_dispatcher'a `data_requests.export` handler'ı); Customer_portal KVKK kartı (self-service export/erasure talebi, token tabanlı indirme) + admin Data_requests paneli (silme talebi onay/red, PRIV_CUSTOMERS yeniden kullanıldı); Console::process_data_requests() + Cleanup::cleanup_data_exports(). Gerçek Docker + gerçek HTTP/çerez oturumuyla uçtan uca doğrulandı; 5 gerçek hata bulunup düzeltildi (GROUP BY eksikliği, yanlış sütun adı, export dizini izin uyumsuzluğu 0750→0755, iki sayfada eksik layout wrapping + `asset()` yerine `asset_url()`); izole Docker'da doğrulandı, henüz canlıya deploy edilmedi
- [~] Otomatik test paketi — PHPUnit bootstrap TAMAMLANDI (commit `b294424`, gerçek Docker'da çalıştırıldı: 17 test/27 assertion, exit 0); asıl test listesi (booking conflict, station allocation, commission, encryption, tenant isolation) henüz yazılmadı
- [x] Background job sistemi — DB-tabanlı birleşik `jobs` kuyruğu + Console::process_jobs() + 6 senkron gönderim noktasının (SMS/WhatsApp/Telegram/appointment-saved email x4) kuyruğa bağlanması TAMAMLANDI (commit `e05f50d`, `2279227`); appointment-DELETED email ve password-reset e-postası bilinçli olarak senkron kaldı; izole Docker'da gerçek çalıştırmayla doğrulandı (3 kritik hata bulunup düzeltildi - detay commit mesajında), henüz canlıya deploy edilmedi
- [x] Observability — yapılandırılmış JSON log (EA_Log, CI3'ün kırık büyük/küçük harf hatası düzeltildi), health endpoint (/health, /health/deep), jobs izleme sayfası (commit `f3eaec3`); izole Docker'da gerçek çalıştırmayla doğrulandı, henüz canlıya deploy edilmedi

### 🔲 Dalga 3 — Otomasyon ve Büyüme (Faz 18·21·22·23·24·25)
- [ ] Communication Hub — event sistemi (appointment.created/completed/cancelled → kanal seçimi)
- [ ] Automation Engine — WHEN/IF/THEN kuralları, 5-6 hazır şablon
- [ ] Marketing — segment (VIP/inaktif/doğum günü) + kampanya gönderimi
- [ ] Review Engine genişletme — randevu-sonrası otomatik review isteği
- [ ] Analytics/BI — revenue/utilization/retention dashboard'ları

### 🔲 Dalga 4 — Marketplace Olgunlaştırma (Faz 41·42·43·44·45)
- [ ] Marketplace Ranking — mesafe + puan + müsaitlik skoru
- [ ] Review güvenliği — sadece gerçekleşmiş randevusu olan müşteri review bırakabilsin
- [ ] Marketplace Revenue — basit sabit komisyon oranı
- [ ] Wallet — muhasebe/takip katmanı (gerçek para transferi değil — lisanslı ödeme kuruluşu gerektirir, mevzuat incelemesi ayrı)

### 🔲 Dalga 5 — Kurumsal / Ölçek (Faz 34·48·49·50·51·52)
- [x] ~~Organization katmanı~~ — karar verildi, ek iş gerekmiyor (yukarı bkz.)
- [ ] PWA — manifest.json + service worker, önce sağlayıcı (staff) günlük görünümü
- [ ] White-label — "Powered by Ki" kapatma, custom domain
- [ ] SaaS Admin genişletme — abonelik/fatura/kullanım metrikleri
- [ ] License/Entitlement sistemi — plan alanını gerçek feature-flag kontrolüne bağla
- [ ] Dokümantasyon — /docs, en son (önceki dalgalar API/özellik ekledikçe güncellenecek)

## Kapsam Dışı / Ayrı Konu

| Faz | Neden bu turda değil |
|-----|----------------------|
| Faz 36 — AI Voice Receptionist | Telefon/SIP altyapısı gerektirir, mevcut Whisper akışından ayrı proje |
| Faz 26 (API genişletme) | API zaten var — yeni kaynaklar eklendikçe (membership, gift-card vb.) endpoint eklenecek, ayrı faz değil |
| Çoklu-organizasyon (tek çatı altında N tenant) | Kullanıcı kararı: her organizasyon ayrı tenant satın alımı olarak kalacak |

---
*Son güncelleme: Dalga 1 başlıyor.*
