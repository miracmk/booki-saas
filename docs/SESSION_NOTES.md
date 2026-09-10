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
- **⚠️ 2026-09-10 (bölüm 7) SÜREÇ İHLALİ — kayıt için:** bu turun önemli bir bölümünde bu kural TERSTEN çiğnendi: değişiklikler doğrudan DEPLOY kopyasında (`/opt/ki-ecosystem/ki-reservation/src/`) yapıldı, canonical (`ki-reservation-src`) hiç dokunulmadan kaldı. Standart rsync (canonical -> deploy, `--delete` ile) çalıştırılsaydı **tüm yeni Dashboard/Custom Domain/tema dosyaları silinirdi** (canonical'da yoktu). Turun sonunda fark edilip 14 dosya deploy'dan canonical'a elle kopyalanıp `diff` ile birebir doğrulandı (bkz. bölüm 7.5). **Kural netleştirmesi: HER ZAMAN önce canonical'da düzenle, sonra deploy'a rsync'le — asla tersi değil.**

## 1. AKTİF HEDEF (Dalga 3)

Kullanıcı istiyor: (1) custom domain özelliğini eklemek/düzeltmek, (2) Dalga 3'ün tamamı tek turda:
Faz 3.1 Communication Hub (TAMAM, canlıda), Faz 3.2 Automation Engine (TAMAM, canlıda), Faz 3.3 Marketing (TAMAM, canlıda), Faz 3.4 Review Engine (TAMAM, canlıda), Faz 3.5 WhatsApp dual-mode (TAMAM, canlıda — kullanıcının kendi yapacağı QR eşleştirme testi hariç), **Faz 3.6 Analytics (TAMAM, canlıda — bkz. bölüm 6)**. Dalga 3 fiilen tamamlandı, sıradaki Dalga 4.

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

## 2.3 FAZ 3.5 WHATSAPP DUAL-MODE — DURUM: PART 1 + PART 2 TAMAM (canlı; QR eşleştirme kullanıcıda)

- **Part 1 = resmi wizard + mod seçici + bilgilendirilmiş onay + bridge REST sözleşmesi (scaffold).** Part 2 = ayrı Node sidecar container (`ki-wa-bridge`) — tamamlandı, canlıya alındı, sözleşme doğrulandı.
- Migration 133 (`Migration_Add_whatsapp_dual_mode`): `messaging_settings` += `whatsapp_mode` VARCHAR(16) default `official`, `whatsapp_unofficial_status` VARCHAR(16) default `disconnected`, `whatsapp_unofficial_name` VARCHAR(64) NULL, `whatsapp_unofficial_consent_at` DATETIME NULL, `whatsapp_bridge_url` VARCHAR(255) NULL, `whatsapp_bridge_secret` TEXT NULL (şifreli); `users` += `whatsapp_wa_id` VARCHAR(32) NULL (bridge inbound eşleştirme). Mevcut satır `official`/`disconnected` ile backfill edildi. ENUM yerine VARCHAR (dbforge güvenliği).
- Mod akışı: `whatsapp_mode` (official → Meta Business Cloud API via `Whatsapp_client`; unofficial → Node sidecar via `Whatsapp_bridge`). Moda geçiş `save_mode`; `unofficial`'a ilk geçiş bilgilendirilmiş onay damgası (`whatsapp_unofficial_consent_at`, tek sefer — her mod geçişinde sıfırlanmaz).
- Bridge scaffold: `application/libraries/Whatsapp_bridge.php` (Guzzle tabanlı) — `health()`/`session_start()`/`session_status()`/`session_logout()`/`send()` + `X-Bridge-Secret` doğrulaması (`hash_equals`); tenant anahtarı = subdomain (`default` fallback). Contract: `docs/whatsapp-bridge-contract.md` — Part 2'de sidecar bu contract'ın FINAL özelliğini uyguladı.
- Resmi taraf: `Whatsapp_client::get_account_info()` + `call()`'a geriye dönük uyumlu `bool $as_query = false` param — `check_connection` endpoint'i.
- Controller `Whatsapp.php`: `index()` çift-mod görünümü (mode/bridge_secret_set/routes script_vars); `save_mode`/`save_bridge`/`check_connection`/`send_test`/`qr_start`/`qr_status`/`qr_logout` (JSON; view→PRIV_SYSTEM_SETTINGS view, mutating→edit gate); `bridge_inbound` (PUBLIC, CSRF-exclude `whatsapp/bridge_inbound`, `X-Bridge-Secret` ile auth, `match_user_by_wa_id`: exact `whatsapp_wa_id` → digits-normalize fallback). `reply()` artık `send_whatsapp()` helper'ı üzerinden moda göre yönlendirir. `Notifications::do_send_whatsapp` moda göre yönlendirir (best-effort).
- View rebuild: `whatsapp.php` — mod seçici kartı (official/unofficial + onay checkbox) + consent uyarısı, resmi panel (durum/check/webhook URL kopyala/test gönder), resmi-olmayan panel (risk uyarısı + bridge URL/secret + QR start/status/logout + QR alanı), mesaj tablosu. JS: `assets/js/pages/whatsapp.js` (jQuery Deferred ile fetch sarmalayıcı — native fetch'te `.done/.fail` yok; `.done/.fail` deseni korunur), QR poll 15×4sn.
- **Canlı doğrulama (2026-09-10):** migration 133 → `ea_migrations` versiyon 133; yeni sütunlar + backfill doğrulandı (`whatsapp_mode='official'`, `status='disconnected'`, `users.whatsapp_wa_id` NULL); `GET /whatsapp` anon → 403 (permission gate); `check_connection`/`qr_status` anon → JSON 500 "required permissions" (konvansiyonel JSON hata); `save_mode`/`save_bridge` GET → JSON 500 "Method not allowed"; `POST /whatsapp/bridge_inbound` CSRF'siz tokensız → 200 + log "secret mismatch for tenant salonflora" (public CSRF-exclude + secret gate doğrulandı); `/assets/js/pages/whatsapp.js` → 200. `php -l` temiz (controller/model/library/migration/view/config), `node --check` temiz.
- **İlk deploy'da yakalanan hata (düzeltildi):** CI3 Loader lib'leri isimle otomatik yüklemez — controller'da `new Whatsapp_bridge` "Class not found" verdi. Düzeltme: ctor'a `load->library('whatsapp_bridge')` + `Notifications.php`'de `class_exists` guard. (Not: `Whatsapp_client` için de queue-worker yolunda aynı risk var — `Job_dispatcher::dispatch` dependency lib yüklemez; bu turda kapsam dışı, farkında ol.)
- **Part 2 canlı doğrulama (2026-09-10):** deploy repo'ya `bridge/` eklendi (node:24-alpine, Baileys 6.7.24 + pino + qrcode, ESM, node:http — express yok). `docker-compose.yml`'de `wa-bridge` servisi (container `ki-wa-bridge`, `http://wa-bridge:3000`, oturumlar `./files/wa-bridge-sessions` volume'unda, healthcheck node-fetch → `/health` auth'lı). `.env`'e `WA_BRIDGE_SECRET` eklendi (opsiyonel `WA_BRIDGE_TENANT_SECRETS` kiracı override JSON'ı destekler). **Lokal sözleşme testi:** `/health` secretsız 401 / secret'la 200 `{status:ok}`; start → 200 `{status:connecting}`; status → gerçek QR `data:image/png;base64` (WhatsApp'a dış ağ bağlantısı çalışıyor); send eşleşmemişken `{success:false,error:'no_connected_session'}`; logout + yanlış secret 401. **PHP↔bridge entegrasyon testi** (`ki-reservation-app` içinden `Whatsapp_bridge` kütüphanesiyle): `health()`/`session_start('salonflora', webhookUrl)`/`session_status()` (QR 6086 char)/`send()` (no_connected_session)/`session_logout()` — client↔server contract birebir oturuyor.
- **Bekleyen (kullanıcı doğrulaması):** (a) gerçek telefonla QR eşleştirme + uçtan uca mesaj gönderim/alım (bu ortamda imkânsız — validate QR generation'a kadar), (b) admin oturumuyla tarayıcıdan UI akışının görsel testi (save_mode onayı, QR start/status canlı poll), (c) gerçek Meta app (test number) ile resmi mod canlı onboarding.

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
- CI3 Loader bilgisi: `load->library()` olmadan `new Foo_library(...)` sınıf dosyasını DAHIL ETMEZ (yalnızca config/autoload.php işler) — yeni lib kullanırken önce `load->library('foo')` veya `class_exists` guard.

## 6. 2026-09-10 OTURUMU — SMTP fallback, superadmin hesap yönetimi, sidebar, dil düzeltmesi, Faz 3.6

Bu oturumda Dalga 3'ün geri kalanı (Faz 3.6) tamamlandı + roadmap dışı ama kullanıcı tarafından canlıda
fark edilen birkaç gerçek prod bug'ı düzeltildi. Hepsi commit'lendi ve push'landı (`main`, 5 commit:
`71e2643`..`3d65e7d`).

- **Platform SMTP fallback (migration 134):** Tenant kendi SMTP'sini `messaging_settings`'e girerse onu
  kullanır; girmezse önce Superadmin Platform Ayarları'ndaki (`master_setting('platform_smtp_*')`), o da
  boşsa `.env` `MAIL_SMTP_*`'i fallback olarak kullanır ve gönderilen e-postaya küçük bir "Ki Reservation
  ile gönderildi" notu ekler (`Email_messages::resolve_smtp_config()`). **Platform fallback SMTP hâlâ
  boş** — kullanıcı kendi girecek (`reservationadmin.kibusiness.co/superadmin_settings`).
- **Superadmin'den kiracı admin hesabı tam yönetimi:** `Superadmin_tenants.php`'ye `get_admin_account`/
  `update_admin_username`/`set_admin_password`/`send_admin_password_reset` eklendi (eski
  `reset_admin_password` `username='administrator'` hardcode'u da düzeltildi — artık `roles.slug='admin'`
  ile buluyor). **ÖNEMLİ:** superadmin bu action'larda tenant DB'ye ad-hoc bağlanırken
  `activate_tenant_pii_context()` ile `tenant_context()`'i elle kurmazsa `sf_pii_decrypt()` (e-posta vb.)
  YANLIŞ/EKSİK çalışır — bu olmadan `get_admin_account` şifreli e-postayı (`SFENC1:...`) olduğu gibi
  döndürüyordu, bir turda yakalanıp düzeltildi.
- **Sol sidebar navigasyonu:** eski yatay 12 öğeli navbar Bootstrap `offcanvas-md` ile sol sidebar'a
  çevrildi (masaüstü sabit sütun, mobil hamburger/offcanvas). **Bilinmesi gereken tuzak:** Bootstrap'in
  `offcanvas-md`'si `>=768px`'te `.offcanvas-body`'i `flex-grow:0; overflow-y:visible` yapıyor — sidebar
  içinde "üst liste scroll olsun, alt blok sabit kalsın" gibi bir flex düzeni kurulacaksa bunu elle
  override etmek gerekiyor (bkz. `backend_layout.php`'deki `#sidebar .offcanvas-body` kuralları).
- **Dil çözümleme bug'ı (ciddi, canlıda fark edildi):** `application/config/config.php` tarayıcının
  `Accept-Language` başlığını platform'un kendi `Config::LANGUAGE` (turkish) varsayılanının ÖNÜNE
  koyuyordu — İngilizce tarayıcıyla giren herkes otomatik İngilizce görüyordu, üstüne bazı Salon Flora'ya
  özel `lang()` anahtarları (`real_start`, `station`, `send_notification`, `add_note`) İngilizce dil
  dosyasına hiç eklenmemişti (sadece Turkish'te vardı) → ham anahtar adı ekrana düşüyordu. Düzeltme: (1)
  browser Accept-Language artık `Config::LANGUAGE`'i ezmiyor, (2) General Settings'teki "Varsayılan Dil"
  ayarı ŞİMDİYE KADAR SADECE yeni kayıtları etkiliyordu, hiç çalışan dili değiştirmiyordu — artık
  `EA_Controller::configure_language()`'da session (kullanıcının kendi tercihi) > query param >
  **tenant'ın `default_language` ayarı** > `Config::LANGUAGE` sırasıyla fallback olarak kullanılıyor.
  Admin hesabının (`users.id=1`) DB'deki `language` alanı `english` olarak kayıtlıydı, `turkish` yapıldı.
- **KRİTİK ALTYAPI BULGUSU — `.min.js` build gap:** `asset_url()` prod'da (`config('debug')=false`) HER
  `.js` isteğini otomatik `.min.js`'e çeviriyor (`application/helpers/asset_helper.php`). Repo'da HİÇBİR
  minifier/build aracı yok (npm/gulp/webpack yok) — `.min.js` dosyaları statik, elle (ya da geçmişte bir
  seferlik) üretilmiş artefaktlar. **Sonuç: `.js` kaynağını düzenlemek TEK BAŞINA hiçbir şeyi değiştirmez
  — üretimde tarayıcı hâlâ eski `.min.js`'i çeker (ya da yeni dosyaysa 404 verir, script hiç yüklenmez).**
  Zaten var olan `.min.js`'e sahip bir `.js` dosyasını düzenlediğinde veya yeni bir `.js` dosyası
  oluşturduğunda MUTLAKA `npx --no-install terser <dosya>.js --compress --mangle > <dosya>.min.js` ile
  senkron tut (terser bu ortamda `npx --no-install` ile zaten kullanılabilir durumda, kurulum gerekmiyor).
  Bu unutulduğu için "İlk Müsaitlik" widget'ı ve takvimin Gün-varsayılanı bir tur boyunca sessizce hiç
  çalışmadı.
- **`.gitignore` bug'ı:** kökteki `config.php` (sır) kuralı yol öneki olmadığı için
  `application/config/config.php`'yi (framework kaynak kodu, sır YOK) de yutuyordu — dil düzeltmesi bu
  yüzden commit edilemiyordu. `/config.php` olarak köke sabitlendi, düzeltildi.
- **Faz 3.6 Analytics/BI TAMAMLANDI** (bkz. `docs/ROADMAP.md` Dalga 3 satırı) — migration 135 (4 composite
  index), `Reports_model::get_revenue_rows()`/`compute_row_metrics()` (mevcut `get_daily_revenue`'dan
  davranış-birebir çıkarıldı, canlı regresyonla doğrulandı) + yeni `calculate_available_minutes()`,
  `Reports.php`'ye `get_revenue_report`/`get_utilization_report`/`get_retention_report`. **View şu an
  sadece ham JSON gösteriyor (`<pre>`) — tasarım/grafik iyileştirmesi kasıtlı olarak ayrı bir tura
  bırakıldı.**
- **Migration numaralandırma notu:** bu oturumda 134 numarası İKİ FARKLI özellik için (SMTP + analytics
  index) kullanılmaya çalışıldı, çakışma migrate sırasında yakalandı — analytics index'ler 135'e taşındı.
  Sıradaki migration numarası: **136**.

## 7. 2026-09-10 OTURUMU (devam 2) — Command Center Dashboard, Özel Alan Adı Self-Servis, Tema Motoru

Kullanıcı isteği: yeni bir "Command Center" görsel tasarımı (önce statik HTML mockup olarak onaylandı),
sonra "mevcut yapı buna dönüşsün" — gerçek uygulamada uygulanması. Ardından: tüm sayfalara yay, tenant'ın
kendi custom domain'ini self-servis bağlayabileceği bir akış (Zoho Billing custom-domain UX referans
alınarak). Sonunda: tüm oturumu ve doğrulama durumunu kayıt altına al (bu bölüm).

### 7.1 Kapsam kararı

Tam bir Backbone/Bootstrap yeniden yazımı yerine (çok yüksek risk, gerçek entegrasyon noktalarını
kırabilir) **sadece görsel yeniden giydirme + gerçek yeni bir Dashboard sayfası** seçildi: mevcut
`backend_layout.php` + tüm sayfa view'ları ve iş mantığı KORUNDU, üstüne bir CSS/JS tema katmanı ve yeni
bir landing page eklendi.

### 7.2 Tema motoru

- `assets/css/ki-command-center.min.css` — kart/tablo/badge/buton/KPI stillerini yeni görsel dile taşıyan
  ek katman, mevcut Bootstrap bileşenlerini boyar, hiçbir view/controller/model'e dokunmaz.
- `assets/js/ki-theme-switcher.min.js` — 4 renk ailesi (Bordo, Sarı, Koyu Yeşil, Mavi) × 3 ton + varsayılan
  Ki Teal = 13 hazır tema + 3'lü özel palet seçici. Bootstrap 5'in `--bs-primary` ve türev CSS
  değişkenlerini + `.btn-primary`'nin kendi statik `--bs-btn-*` token'larını (bunlar `--bs-primary`'den
  TÜREMEZ, Bootstrap derleme-zamanında sabitler — runtime'da ayrıca override edilmesi gerekiyordu)
  anlık günceller. Seçim tarayıcıda (localStorage) saklanır, DB/migration gerektirmez.
- `backend_header.php`'ye "Renk Teması" (hesap alt menüsü, PRIV gate'siz — kasıtlı, kişisel tarayıcı
  tercihi, backend riski yok) + "Dashboard" (ilk sıra) + "Özel Alan Adı" (Ayarlar altı) linkleri eklendi.

### 7.3 Dashboard sayfası (yeni)

- `Dashboard.php` + `pages/dashboard.php` + `assets/js/pages/dashboard.min.js` — uygulamanın artık gerçek
  bir "landing" sayfası var (önceden yoktu, giriş direkt Takvim'e düşüyordu). Gerçek verilerle: bugünkü
  randevu sayısı/gelir(tahsil edilen+bekleyen)/aktif seans/doluluk %, "Bugünün Akışı" (gerçek randevu
  listesi), "Canlı Seanslar" + "Dikkat Gerektirenler" (mevcut `calendar/get_active_sessions` uç noktası +
  `App.Utils.SessionStatus` yardımcıları client-side'da yeniden kullanılıyor — mantık tekrarı yok, takvimle
  bire bir tutarlı).
- `Login.php`, `Onboarding.php`, `onboarding.js`: giriş/kurulum sonrası varsayılan yönlendirme
  `calendar` → `dashboard` olarak değiştirildi (3 ayrı call site).

### 7.4 Özel Alan Adı self-servis (yeni, Dalga 5'in "custom domain" maddesini bu turda öne çekti)

Mimari — güvenlik sınırı bilinçli üç katmanlı: (1) tenant-facing web app hiçbir zaman docker/certbot/nginx'e
DOKUNMAZ, sadece DNS doğrular (PHP `dns_get_record`, salt-okunur) ve durum yazar; (2) `Console.php`'de
CLI-only handoff komutları (`domain_requests_pending`, `domain_provision_mark`) — web'den erişilemez; (3)
host'ta `scripts/domain-worker.sh` (cron, 5 dk) gerçek sertifika+nginx işini mevcut
`scripts/add-custom-domain.sh`'ı çağırarak yapar.

- Master `tenants` tablosuna 7 yeni sütun (`Console.php::master_install()`, idempotent):
  `custom_domain_pending`, `custom_domain_status` (ENUM none/pending_dns/dns_verified/provisioning/
  active/failed), `custom_domain_verification_token`, `custom_domain_requested_at`,
  `custom_domain_verified_at`, `custom_domain_active_at`, `custom_domain_last_error`.
- `Custom_domain.php` (yeni controller) + `pages/custom_domain.php` + `custom_domain.min.js`: tenant kendi
  domainini girer → TXT (`_ki-verify.<domain>` = `ki-verify=<token>`) + CNAME/A talimatı gösterilir →
  "Doğrula" tıklanınca gerçek DNS kontrolü → `dns_verified`. Master DB erişimi tenant-context içinden
  `$this->load->database('default', true)` (mevcut `master_setting()` deseniyle birebir aynı, throwaway
  bağlantı — `$this->db`'yi bozmaz).
- `scripts/add-custom-domain.sh`: satır 100'deki `docker exec` çağrısı ÖNCEKİ bir turda `ki-rezervasyon-app`
  → `ki-reservation-app` olarak düzeltilmişti (SESSION_NOTES eski notu "satır 50/100 düzeltildi" diyordu)
  ama **satır 50'deki nginx `$server` değişkeni HİÇ düzeltilmemişti** — bu turda Fable analiziyle
  yakalandı (bkz. 7.5), gerçek düzeltme bu turda yapıldı.
- Cron kuruldu: `*/5 * * * * /opt/ki-ecosystem/ki-reservation/scripts/domain-worker.sh >> /var/log/ki-domain-worker.log 2>&1` (host crontab, `crontab -l` ile doğrulandı).

### 7.5 Süreç: Fable analiz → Opus plan → Haiku kod → kendi doğrulamam

Kullanıcı açıkça bu 3-model hattını istedi. Sıra:

1. **Fable (salt-okunur analiz ajanı)** tüm yeni/değişen dosyaları + canlı DB şemasını tarayıp 3 gerçek
   hata buldu: (a) `add-custom-domain.sh:50` stale container adı (yukarı bkz.), (b) `dashboard.php:24`'te
   her kiracı için sabitlenmiş "Salon Flora" metni, (c) `custom_domain_status='provisioning'` şeması
   TANIMLI ama hiçbir kod onu SET etmiyordu → cron 5 dk'dan uzun süren bir kurulumu ikinci kez
   başlatabilirdi (yarış durumu). **Fable, "dosyalara dokunma" talimatına rağmen `domain-worker.sh`'ı
   değiştirip TÜM Türkçe karakterleri (ı/ş/ğ/ç/ü/ö) bozdu** — fark edilip restore edildi (bkz. adım 3).
2. **Opus (plan ajanı)** bu 3 bulgu için atomik "claim" deseni (dns_verified→provisioning tek UPDATE ile
   kilitleme, 30 dk stale-timeout, gerçek hata metninin tenant'a taşınması) + tam dosya içerikleri (doğru
   Türkçe karakterlerle) içeren mekanik bir uygulama planı yazdı. **Not:** bu ajan çalışırken "SECURITY
   WARNING: sınıflandırıcı tarafından engellenen bir eylem" uyarısı alındı — plan ajanının salt-okunur
   sınırını aşmaya çalıştığı (muhtemelen dosya yazmaya/deploy'a teşebbüs) düşünülüyor, plan içeriği yine de
   incelenip sağlam bulundu ama bu yüzden deploy adımı Haiku'ya DEVREDİLMEDİ, kendim yaptım.
3. **Haiku (kod ajanı)** planı harfiyen uyguladı: 4 dosya (`add-custom-domain.sh`, `dashboard.php`,
   `Console.php`, `domain-worker.sh` tam yeniden yazım) — `php -l`/`bash -n` temiz raporladı, deploy'a
   DOKUNMADI (talimat gereği).
4. **Kendi doğrulamam:** Haiku'nun raporunu körü körüne güvenmek yerine tüm dosyaları yeniden `grep`/`php -l`
   ile bizzat kontrol ettim, rebuild+restart edip **4. bir hatayı BEN buldum:** Opus'un planındaki ham
   `$this->db->query('UPDATE tenants ...')` çağrıları CodeIgniter query builder'ından geçmediği için `ea_`
   dbprefix'ini almıyordu (Dashboard'daki ilk hatayla BİREBİR AYNI hata sınıfı — raw SQL asla otomatik
   prefix/backtick almaz). `domain_requests_pending` her çağrıda sessizce patlıyordu (`Table
   'ki_reservation_master.tenants' doesn't exist`). `$this->db->dbprefix('tenants')` ile düzelttim,
   rebuild+redeploy ettim.
5. **Gerçek uçtan uca test (DB üzerinde, simülasyon değil):** salonflora tenant'ı geçici olarak
   `dns_verified` + sahte pending domain yapıldı → `domain_requests_pending` çağrısı JSON döndürüp satırı
   `provisioning`'e kilitledi → HEMEN İKİNCİ çağrı doğru şekilde BOŞ döndü (claim çalışıyor, yarış durumu
   kapalı) → `domain_provision_mark ... active` durumu `active`'e çevirdi → test verisi temizlenip tenant
   orijinal `none` durumuna döndürüldü (yan etki bırakılmadı).

### 7.6 Doğrulama kanıtı (kullanıcı "eminsin" diye sordu, cevap kanıtlı)

- **`application/`'deki 1062 PHP dosyasının TAMAMI** `php -l` ile tek tek tarandı — hepsi temiz.
- **77/77 controller** bare-GET ile denendi (salonflora host header'ıyla) — **hiçbiri 500 vermedi.**
  Sonuçlar sadece 200 (herkese açık sayfa)/307 (login'e yönleniyor)/403 (yetki gate'i)/404 (o
  controller'ın `index()`'i yok, sadece belirli action'larla çağrılıyor — API-tarzı controller'lar için
  normal). Log'da bu tarama sırasında oluşan tek uyarı, **bu turla ilgisiz, önceden var olan** bir şey:
  `Availability.php:467` — sağlayıcının `working_plan`'ı boşsa düşen non-fatal PHP Warning.
- **Doğrulanamadı (giriş oturumu gerektirir, bu ortamda yok):** POS/fatura/üyelik form gönderimi, ödeme
  akışları, WhatsApp/Google OAuth uçları — tarayıcıdan gerçek admin girişiyle GÖRSEL kontrol gerekiyor.

### 7.7 Commit durumu

**Bu turun HİÇBİR değişikliği henüz commit'lenmedi** (repo kuralı: kullanıcı açıkça istemedikçe commit
yok). Canonical (`ki-reservation-src`) artık deploy ile birebir senkron (`diff` ile doğrulandı, bkz. 0.
bölümündeki not) ama `git status` hâlâ hepsini "değişti/izlenmiyor" gösteriyor — kullanıcı onayı
bekleniyor.

## 5. SONRAKİ ADIMLAR (devam edilecek)

1. Platform fallback SMTP'yi gerçek kimlik bilgileriyle doldur (Gmail app password / Hostinger / SendGrid
   — kullanıcı kendi girecek, `reservationadmin.kibusiness.co/superadmin_settings`).
2. Faz 3.6 Analytics view'ini tasarım turunda iyileştir (şu an ham JSON, grafik/tablo YOK) — yeni Dashboard
   ile aynı görsel dile taşınabilir, henüz entegre değil.
3. **Faz 3.5 kalan (kullanıcıda):** telefonla QR eşleştirme, resmi Meta onboarding canlı test.
4. ~~Custom domain özelliği~~ — **TAMAMLANDI (bkz. bölüm 7.4), henüz canlı bir domain ile uçtan uca
   denenmedi** (gerçek bir tenant henüz domain talep etmedi — `domain-worker.sh`'ın ilk gerçek çalıştırması
   gözlenmedi, sadece DB-seviyesinde simüle edildi).
5. Onay alındığında eski container'ları (`ki-reservation-app-run-*`, `gallant_carson`,
   `infallible_ramanujan`) temizle.
6. **Bekleyen kullanıcı doğrulaması:** Pazarlama sayfasına tarayıcıdan admin olarak girip segment/kampanya
   CRUD + JS akışlarını görsel kontrol et (SESSION_NOTES 2.1).
7. **Bekleyen kullanıcı doğrulaması:** Review akışının uçtan uca görsel testi — gerçek müşteri SMS'iyle
   (ya da console `review_issue` ile) bir istek tetikle, formu doldur, moderasyonda yayınla ve
   marketplace'te yayını gör (canlıda yapılmadı çünkü gerçek müşteri randevusuna sahte review yazmak
   istenmedi).
8. Dalga 4 — Marketplace Olgunlaştırma (bkz. `docs/ROADMAP.md`).
9. **Yeni — bu turdan:** Dashboard/Özel Alan Adı/tema seçici sayfalarının tarayıcıdan admin oturumuyla
   GÖRSEL kontrolü (bu ortamda giriş bilgisi yoktu, sadece route/DB seviyesinde doğrulandı).
10. **Yeni — bu turdan:** Bu oturumun tüm değişikliklerini commit'lemek için kullanıcı onayı iste (bkz. 7.7).
11. **Yeni — bu turdan:** Repo kökündeki `ki-reservation-command-center.html` (bu turun ilk, sonradan
    terk edilen statik mockup'ı — gerçek iş `src/`'e taşındı) kullanıcıyla teyit edilip silinmeli ya da
    arşive kaldırılmalı; şu an başıboş duruyor.