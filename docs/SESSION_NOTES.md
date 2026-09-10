# Ki Reservation — Oturum Notları

Canonical kaynak: `/opt/ki-ecosystem/ki-reservation-src`
Deploy repo: `/opt/ki-ecosystem/ki-reservation` (app kodunun kopyası deploy `src/` dizininde durur)
Son güncelleme: 2026-09-10

---

## 0. KRİTİK DEPLOY KURALLARI (asla çiğneme)

- **rsync hedefi YALNIZCA `/opt/ki-ecosystem/ki-reservation/src/` olur.**
  `rsync -a --delete --exclude='.git' /opt/ki-ecosystem/ki-reservation-src/ /opt/ki-ecosystem/ki-reservation/src/`
- Deploy repo KÖKÜNE rsync ile `--delete` YAPMA (gitignore'lu `db/`, `files/`, `src/`, `.env`'i sildi -> büyük veri felaketi; kurtarma ile 2026-09-08'de atlatıldı).
- **Deploy layout:** Deploy root'ta yalnızca scaffolding var (git tracked: `.gitignore`, `Dockerfile`, `README.md`, `docker-compose.yml`, `docker-entrypoint.sh`, `scripts/`). Uygulama kodu gitignore'lu `src/` altında. Volume'lar: `./db/mysql:/var/lib/mysql`, `./files:/var/www/html/storage`.
- Canonical src'de deploy-only dosyalar (Dockerfile, docker-compose.yml, docker-entrypoint.sh, scripts/) BULUNMAMALI; canonical temiz tutuldu.
- Deploy akışı: canonical src'de düzenle -> rsync (doğru hedefe) -> `docker compose build app` -> `up -d app` -> `docker exec -u www-data ki-reservation-app php index.php console migrate`.

## 1. AKTİF HEDEF (Dalga 3)

Kullanıcı istiyor: (1) custom domain özelliğini eklemek/düzeltmek, (2) Dalga 3'ün tamamı tek turda:
Faz 3.1 Communication Hub (TAMAM, canlıda), Faz 3.2 Automation Engine (TAMAM, canlıda), **Faz 3.3 Marketing (TAMAM, canlıda)**, **Faz 3.4 Review Engine (TAMAM, canlıda)**, Faz 3.5 WhatsApp dual-mode (SIRADAKİ), Faz 3.6 Analytics.

Canlı doğrulama (2026-09-08):
- `salonflora.reservationapp.kibusiness.co`: `/`, `/booking`, `/login`, `/health` -> 200; `/api/v1/services` -> 401 (doğru).
- Bare `reservationapp.kibusiness.co`: portal `/` + `/health` -> 200; `/login`/`/booking` -> 404 (tasarım: tenant'siz booking yok).
- `/backend` route DEĞİL bu fork'ta (admin SPA `/login` altında). 404'ü normaldir.
- `docker compose ps`: app + db "Up", sağlıklı.

## 2. FAZ 3.2 AUTOMATION ENGINE — DURUM: TAMAM (canlı)

- `application/migrations/129_create_automation_rules_table.php`: `automation_rules` + `automation_log` + 6 seed şablon (devre dışı). **Düzeltildi:** `date('Y-m.D')` -> `date('Y-m-d')` format hatası.
- `application/libraries/Automation_engine.php`: WHEN(event)/IF(conditions)/THEN(actions). Conditions ops: `=`, `!=`, `<`, `>`, `<=`, `>=`, `in`, `weekdays_only`; dot-notation (`service.duration_minutes`, `customer.appointment_count` virtual = tamamlanmış status=3 sayısı). Actions: `message` (hub deliver ile), `note` (appointment_notes), `log`.
- `Communication_hub.php` yeniden kullanım: `deliver()` public, `resolve_recipients()` public, `send_channel(channel, recipient, subject, text)` yeni imza, `render_message()`/`render_subject()` public, `build_placeholders()`'a `appointment_count` eklendi (ctx'ten okur, DB sorgusu yok).
- Hook'lar: Booking.php:696 (appointment_created, unconditional), Calendar.php:664 (created, `!$manage_mode`), Calendar.php:971 (completed, check_out try bloğu), Calendar.php:1596 (cancelled, `$notify_users` gate içinde), Appointments.php:329 (cancelled, unconditional).
- Console.php: `automation_rules`, `automation_rule_toggle` komutları; `console_rule_tenants($subdomain)` helper (opsiyonel tek-tenant veya tüm).
- Test: 6 seed listelendi, rule #6 toggle ON->OFF geçti. Kural oluşturmak için 128'deki `communication_rule` benzeri akış / hub deliver kullanılır.

## 2.1 FAZ 3.3 MARKETING — DURUM: TAMAM (canlı)

- Migration 130: `ea_settings` platform ayarı + `google_ads_gateway_token` (Eski Google Ads ↔ yeni kanal eşleştirmesi). Migration 131: `marketing_segments`, `marketing_campaigns`, `campaign_recipients` + `ea_roles.marketing` bitmask (admin=15). DB versiyon 131.
- Segment türleri (`type`): VIP / inactive / birthday / all / custom; `audience_filter` JSON'u custom segmente kaydedilir; `refresh()` ile üyelikler `segment_customers`'ta tutulur.
- Kampanya akışı: `draft → queued → sending → sent`; `Campaigns_model::prepare_broadcast()` idempotent `campaign_recipients` üretir (customer başına tek kayıt, 200'lük chunk), `send_batch()` 50'lik batch'i `status=pending` süzüp gönderir. Merge alanları: `{{customer_name}}`, `{{customer_first_name}}`, `{{company_name}}`. Kanallar: e-posta/SMS/WhatsApp/Telegram.
- Admin panel "Pazarlama" sayfası: `Marketing.php` + `views/pages/marketing.php` (segment + kampanya CRUD, broadcast queue). Nav `backend_header` "Pazarlama". Auth gate `<admin|provider>` enum PRIV_MARKETING, provider görmesin diye role panel'de admin-only (role_setting'de gizli değilse provider da görebilir — doğrulanmadı, admin için doğrulandı).
- Console: `marketing_segments`, `marketing_refresh`, `marketing_campaigns` (campaign listeler, `prepare`/`send`/`reset` ile broadcast yönetimi). `marketing_refresh` segment üyeliklerini (balancing) yeniden hesaplar.
- **CI3 migration adlandırma kuralı** (Migration.php:283): sınıf adı = `Migration_` . ucfirst(strtolower(numarasız-dosya-adı)) — sınıf adına sayı YAZMA, `ea_` önekini dbforge/db çağrılarında KULLANMA (dbprefix otomatik; 131'de `ea_roles`→`roles` düzeltildi).
- **Doğrulama:** 516 müşteri ile smoke test (segment→campaign→prepare→send_batch→cleanup, 0 orphan); tam sayfa render testi (75880 B, nav "Pazarlama" içeriyor); `/marketing` unauth → 307 login. Bilinen: hostta `/usr/sbin/sendmail` yok — e-postalar best-effort, alıcı yine "sent" işaretlenir (mevcut kanal konvansiyonu).
- **Kalan eksik (raporlandı):** tarayıcıda gerçek admin girişi (ALTCHA/captcha + CSRF engellediği için script'li yapılmadı) — paneldeki JS akışları (segment/kampanya CRUD + send butonu) otomatik test kapsamında değil, ilk manuel girişte gözle kontrol edilmeli.

## 2.2 FAZ 3.4 REVIEW ENGINE — DURUM: TAMAM (canlı)

- Migration 132: tenant `reviews` tablosu (id, appointment_id, token UNIQUE 64-hex tek-kullanımlık, customer_phone_hash, customer_name, rating, comment, status ENUM('requested','pending','published','rejected'), submitted_at, moderated_by, moderated_at, created_at) + `settings` `reviews_enabled=1` + `ea_roles.reviews` bitmask (admin=15 set, up-convert with <, idempotent) + seeded kural #4'ü "Değerlendirme isteği" `review_request` aksiyonuna up-convert etme (eski metin eşleşmesi; yeni sürüm JSON-escaped eşleşmesi kullanır — container'daki 6817 byte'lık sürümde de up-convert çalıştı, rule #4 DB'de doğrulandı).
- Akış: Automation Engine `appointment_completed` → `review_request` aksiyonu → `execute_review_request()` (gate'ler: reviews tablosu var, `reviews_enabled != '0'`, gerçek appointment + customer phone, appointment başına tek istek; token = master `source_appointment_hash`; `Communication_hub::build_placeholders`'a `review_link` eklendi) → SMS/WhatsApp ile `{review_link}`.
- Public form: `GET /review/index/{token}` (standalone HTML, CSRF), `POST /review/submit` (JSON; 1-5 yıldız + ≤2000 karakter; dopru token'ı atomik claim → `requested→pending`, `submitted_at`; claimed satır telefon hash'ini çağrı kimliğini doğrulamak için kullanır). İkinci kullanım = "Bağlantı zaten kullanıldı".
- Moderasyon: `Reviews` controller (auth gate PRIV_REVIEWS + admin), sekmeler requested/pending/published/rejected, publish → `mirror_to_master(published)` (master `ea_reviews`'e INSERT id_tenants + source_appointment_hash, legacy tablo), reject → master'dan DELETE (mirror_to_master(rejected)).
- Marketplace: `submit_review` artık tek-kullanımlık tenant token'ı (`source_appointment_hash`) zorunlu kılar, `Review_service::claim_in_tenant()` ile çapraz-tenant doğrulama; anonim/rastgele-hash form kaldırıldı (güvenlik düzeltmesi). `marketplace_business` sadece `status=published` review'ları gösterir (COUNT/AVG).
- Console: `review_issue <appointment_id> [subdomain]`, `reviews list [status] [subdomain]`, `review_status <id> <published|rejected> [subdomain]` (mirror'u tetikler).
- Constants: `PRIV_REVIEWS = 'reviews'`. Nav: "Yorumlar".
- **DB durumu:** tenant `ea_migrations` versiyon 132; `ki_tenant_salonflora.ea_reviews` yeni şema, 2 test isteği (`requested`, appt 84/26). Master `ea_reviews` mirror şeması (id_tenants, source_appointment_hash UNIQUE, status pending/published/rejected) — şu an 0 satır. `ea_roles.reviews`: admin=15, diğerleri 0.
- **Canlı doğrulama (2026-09-09):** `GET /review/index/{token}` → 200 (form render); geçersiz token → hatalı form (200); `/reviews` auth'suz → 307 login; CSRF'li `POST /review/submit` rating=0 → `{"success":false,"message":"Derecelendirme 1-5 arasında olmalıdır."}` JSON hatası ve token TÜKETİLMEDİ; `console reviews list requested salonflora` → 2 kayıt; 8 PHP dosyasında `php -l` temiz; container↔canonical diff: kod dosyaları birebir aynı, yalnızca migration 132 (canonical'de iyileştirilmiş eşleşme) — migration zaten uygulandığı için işlevsel etki yok.
- **Not:** master DB'de legacy `ea_reviews` tümleşik değil — mirror, `Review_service`'in yeni bağlantısı üzerinden çalışır; migration sistemi master'ı kapsamıyor (master `ea_migrations` versiyon 0).

## 3. DEPLOY FELAKETİ + KURTARMA KAYDI (2026-09-08)

Kök neden: `rsync -a --delete` yanlışlıkla deploy ROOT'a yapıldı (src/ yerine). `db/mysql` (MySQL datadir) ve `files/` (storage) ve `src/` silindi.
- mysqld silinmiş inode üzerinden çalışmaya devam ediyordu (13 günlük). SHOW DATABASES çalışıyor, `ls /var/lib/mysql` boş.
- ACİL: o yaşayan mysqld'den her iki DB dump edildi -> `/tmp/opencode/db-recover/ki_reservation_master.sql` (9294 B, 6 tablo) ve `/tmp/opencode/db-recover/ki_tenant_salonflora.sql` (433823 B, 53 tablo). **Bu dump'lar hayati — silme.**
- Kurtarma: `docker compose stop db` -> `rm -f db` -> `up -d db` (taze init, .env'den) -> master import + `CREATE DATABASE ki_tenant_salonflora` + GRANT `ki_reservation_master`@`%` + tenant import. Restore doğrulandı (COUNT 53, ea_tenants'te salonflora active, 8 comm rule).
- `.env` deploy root'ta YOKTU (rsync silmişti); çalışan app container env + db container env'den 13 satır yeniden oluşturuldu (sort -u). `.env` asla commit edilmez.
- `files/` storage volume yeniden oluşturuldu: `mkdir -p files/{backups,cache,logs,sessions,uploads}` + `chown www-data:www-data`. Storage writable değilse migrate "Storage Configuration Error" verir.
- `mod_rewrite` gizli sorunu: base image `alextselegidis/easyappointments:1.6.0` rewrite'ı kapalı geliyor; .htaccess tüm clean URL'leri index.php'ye yönlendiremediği için app 404 dönüyordu. --> Dockerfile'a `RUN a2enmod rewrite` eklendi (deploy root'taki Dockerfile).

Kurtarma sonrası kalan eski container'lar (referans; silinebilir diye duruyor): `ki-reservation-app-run-33b2bfa17efc`, `gallant_carson`, `infallible_ramanujan`. Kullanıcı onayı istenmedi, silinmedi.

## 4. DİĞER ÖZELLİKLER / İÇ BİLGİLER

- Master DB'de `ea_reviews` tablosu VAR ve artık Faz 3.4'ün mirror hedefi olarak kullanılıyor (bkz. 2.2). Tablo öneki `ea_`.
- Multi-tenant deseni: `is_multi_tenant_mode()` -> `connect_tenant($tenant)` -> iş -> `connect_master()`.
- Health endpoint'leri: `/health` (hafif), `/health/deep` (403 token'sız — n_token master setting'inde).
- Etki alanları: `TENANT_APP_DOMAIN=reservationapp.kibusiness.co`, `SUPERADMIN_DOMAIN=reservationadmin.kibusiness.co`, `MARKETPLACE_DOMAIN=reservation.kibusiness.co`. Sunucu IP: `168.231.109.167`.
- Subdomain kalıbı: hem `{subdomain}.TENANT_APP_DOMAIN` hem `{subdomain}-TENANT_APP_DOMAIN` kabul edilir. Tenant `custom_domain`'i varsa o önceliklidir.
- Reverse proxy: host'ta NPM (`npm-app-1`, `/root/npm/npm/data/nginx/proxy_host/reservationapp*.conf`). Docker'dan çıkan port yok; proxy container adı (`ki-reservation-app`) ile eşler.
- SPA'nın JS/CSS cache-busting: `ASSET_VERSION` compose env (deploy'da bump gerekir).
- Seçili kaynak dosyalar: `application/config/routes.php` (85k satır — health routes :227-229, default_controller booking/portal/superadmin_auth :54-70), `docs/ROADMAP.md` (Dalga 3 faz tanımları, satır 61-67).
- Commit kuralı: kullanıcı açıkça istemedikçe commit yok. `.env`, `/tmp/opencode/db-recover/*`, `CREDENTIALS.md` dışarı sızmamalı.

## 5. SONRAKİ ADIMLAR (devam edilecek)

1. **Faz 3.5 WhatsApp dual-mode**: `Whatsapp_client.php` (Meta API) + `messaging_settings` + `whatsapp_messages` üzerine resmi/resmi-olmayan mod + QR.
2. **Faz 3.6 Analytics**: `Reports.php` / `get_daily_revenue` / `export_csv` üzerine dashboard.
3. Custom domain özelliği düzeltmesi (kullanıcının ilk isteği) — `tenants.custom_domain` mevcut, script `scripts/add-custom-domain.sh` deploy root'ta (Faz 0'da düzeltildi: README satır 131-133; script satır 50/100).
4. Onay alındığında eski container'ları (`ki-reservation-app-run-*`, `gallant_carson`, `infallible_ramanujan`) temizle.
5. **Bekleyen kullanıcı doğrulaması:** Pazarlama sayfasına tarayıcıdan admin olarak girip segment/kampanya CRUD + JS akışlarını görsel kontrol et (SESSION_NOTES 2.1).
6. **Bekleyen kullanıcı doğrulaması:** Review akışının uçtan uca görsel testi — gerçek müşteri SMS'iyle (ya da console `review_issue` ile) bir istek tetikle, formu doldur, pentikan moderasyonunda yayınla ve marketplace'te yayını gör (canlıda yapılmadı çünkü gerçek müşteri randevusuna sahte review yazmak istenmedi).