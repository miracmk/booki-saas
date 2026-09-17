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
| 17 | Otomatik Test Paketi | ❌ Yok | ✅ PHPUnit birim testleri (28/28) + Playwright E2E (47/47) %100 yeşil | Dalga 2 + Dalga 5 tamamlandı |
| 18 | Background Job / Queue | ❌ Yok | ✅ Var, canlıda | Dalga 2 |
| 19 | PWA / Service Worker | ❌ Yok | ✅ Var, canlıda | Dalga 5 (/manifest.json, /sw.js, start_url: /calendar) |
| 20 | License Sistemi | 🟡 Kısmi | ✅ Var, canlıda | Dalga 5 (Free/Basic/Premium/Elite entitlement matrix + kilitli menü/modal UX) |
| 21 | SaaS Admin Paneli | 🟡 Kısmi | ✅ Var, canlıda | Dalga 0.6 + Dalga 5 (KPI kartları, MRR/gelir metrikleri, provisioning sihirbazı) |
| 22 | Localization | 🟡 Kısmi | 🟡 Kısmi (değişmedi) | 40+ dil dosyası hazır, aktif değil; currency config yok |

## Uygulama Planı — Öncelik Dalgaları

### 🔄 Dalga 0 — BooKi Markalaşması, Pazarlama & Büyüme Altyapısı (2026-09-16 başladı) — CANLIYA ALINDI (2026-09-17)

### ✅ Dalga 1 — Gelir ve Operasyon Temeli (Faz 7·8·9·12·13) — TAMAMLANDI + CANLIYA ALINDI (2026-08-28)

### ✅ Dalga 2 — Güvenlik ve Güvenilirlik (Faz 29·30·31·32·33) — TAMAMLANDI (2026-08-28, 2026-09-17 test paketi tamamlandı)
- [x] MFA/TOTP — TAMAMLANDI (commit `75b1d34`)
- [x] KVKK/GDPR tamamlama — TAMAMLANDI (commit `1edba98`)
- [x] Otomatik test paketi — TAMAMLANDI: PHPUnit 28/28 birim test (BookingConflict, CommissionCalculation, TenantIsolation, PlanEntitlement, CryptoHelper) + Playwright E2E 47/47 senaryo (%100 yeşil)
- [x] Background job sistemi — TAMAMLANDI (commit `e05f50d`)
- [x] Observability — TAMAMLANDI (commit `f3eaec3`)
- [x] Analytics Command Center — Reports sayfası modernize edildi (KPI kartları, doluluk/kapasite barları, ciro analizi)

### ✅ Dalga 3 — Otomasyon ve Büyüme (Faz 18·21·22·23·24·25) — TAMAMLANDI

### ✅ Dalga 4 — Marketplace Olgunlaştırma (Faz 41·42·43·44·45) — TAMAMLANDI (2026-09-17, commit `12b68d8`)
- [x] Marketplace Ranking — Bayesian ortalama puan, yorum hacmi log skoru, profil doluluk bonusu, GPS konum / Haversine mesafe hesabı ve şehir/ilçe filtreleri (`Marketplace::index()`)
- [x] Review güvenliği — yalnızca gerçekleşmiş (`end_datetime < NOW()`) ve iptal edilmemiş randevusu olan müşteriler değerlendirme bırakabilir (`Review_service::claim_in_tenant()`)
- [x] Marketplace Revenue — pazar yerinden gelen randevular için `?ref=marketplace` attribution etiketi ve 30 günlük first-party çerez takibi (`Booking.php`), %5 platform komisyon oranı (`marketplace_commission_rate = 5.00`)
- [x] Wallet — mevzuat uyumlu dahili cari/muhasebe defteri (`ea_tenant_wallets` ve `ea_wallet_ledger` tabloları, randevu `closed` olduğunda otomatik hakediş ve komisyon tahakkuku)

### ✅ Dalga 5 — Kurumsal / Ölçek (Faz 34·48·49·50·51·52) — TAMAMLANDI (2026-09-17)
- [x] ~~Organization katmanı~~ — karar verildi, ek iş gerekmiyor (yukarı bkz.)
- [x] PWA — manifest.json (start_url: /calendar, standalone, icons) + service worker (sw.js, cache-first + offline fallback)
- [x] Custom domain — TAMAMLANDI (commit `368201c`)
- [x] White-label — "Powered by Ki" kapatma (Elite plan için Genel Ayarlar'da branding toggle)
- [x] SaaS Admin genişletme — abonelik/fatura/kullanım metrikleri, MRR ve gelir kartları, tenant provisioning sihirbazı
- [x] License/Entitlement sistemi — Free/Basic/Premium/Elite plan matrisi (`plan_helper.php`), kilitli menü öğeleri için 🔒 rozeti ve yükseltme modalı (`#upgrade-plan-modal`)
- [x] Dokümantasyon — `/docs` etkileşimli API dokümantasyonu (REST API v1, Webhooks, MCP & AI sözleşmeleri)

### 🟢 Roadmap-dışı ek — Command Center Dashboard + Tema Motoru (2026-09-10, canlıda — commit `368201c`)
- [x] Yeni gerçek Dashboard landing sayfası
- [x] Görsel tema katmanı: 4 renk ailesi × 3 ton + özel 3'lü palet
- [x] Analytics Command Center: Reports sayfası görsel dille modernize edildi (2026-09-17)

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
