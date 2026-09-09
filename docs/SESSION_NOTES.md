# Ki Reservation — Oturum Notları

Canonical kaynak: `/opt/ki-ecosystem/ki-reservation-src`
Deploy repo: `/opt/ki-ecosystem/ki-reservation` (app kodunun kopyası deploy `src/` dizininde durur)
Son güncelleme: 2026-09-09

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
Faz 3.1 Communication Hub (TAMAM, canlıda), Faz 3.2 Automation Engine (TAMAM, canlıda), **Faz 3.3 Marketing (TAMAM, canlıda)**, Faz 3.4 Review Engine (SIRADAKİ), Faz 3.5 WhatsApp dual-mode, Faz 3.6 Analytics.

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

- Legacy: master DB'de `ea_reviews` tablosu VAR (Faz 3.4 Review Engine tenant değil master tarafına kurulabilir). Tablo öneki `ea_`.
- Multi-tenant deseni: `is_multi_tenant_mode()` -> `connect_tenant($tenant)` -> iş -> `connect_master()`.
- Health endpoint'leri: `/health` (hafif), `/health/deep` (403 token'sız — n_token master setting'inde).
- Etki alanları: `TENANT_APP_DOMAIN=reservationapp.kibusiness.co`, `SUPERADMIN_DOMAIN=reservationadmin.kibusiness.co`, `MARKETPLACE_DOMAIN=reservation.kibusiness.co`. Sunucu IP: `168.231.109.167`.
- Subdomain kalıbı: hem `{subdomain}.TENANT_APP_DOMAIN` hem `{subdomain}-TENANT_APP_DOMAIN` kabul edilir. Tenant `custom_domain`'i varsa o önceliklidir.
- Reverse proxy: host'ta NPM (`npm-app-1`, `/root/npm/npm/data/nginx/proxy_host/reservationapp*.conf`). Docker'dan çıkan port yok; proxy container adı (`ki-reservation-app`) ile eşler.
- SPA'nın JS/CSS cache-busting: `ASSET_VERSION` compose env (deploy'da bump gerekir).
- Seçili kaynak dosyalar: `application/config/routes.php` (85k satır — health routes :227-229, default_controller booking/portal/superadmin_auth :54-70), `docs/ROADMAP.md` (Dalga 3 faz tanımları, satır 61-67).
- Commit kuralı: kullanıcı açıkça istemedikçe commit yok. `.env`, `/tmp/opencode/db-recover/*`, `CREDENTIALS.md` dışarı sızmamalı.

## 5. SONRAKİ ADIMLAR (devam edilecek)

1. **Faz 3.4 Review Engine**: master `ea_reviews` yapısını incele; tenant'ta reviews + randevu-sonrası istek akışı (otomatik tetikleme appointment_completed hook'una bağlanabilir; Automation rule şablonu #4 zaten "değerlendirme isteği").
2. **Faz 3.5 WhatsApp dual-mode**: `Whatsapp_client.php` (Meta API) + `messaging_settings` + `whatsapp_messages` üzerine resmi/resmi-olmayan mod + QR.
3. **Faz 3.6 Analytics**: `Reports.php` / `get_daily_revenue` / `export_csv` üzerine dashboard.
4. Custom domain özelliği düzeltmesi (kullanıcının ilk isteği) — `tenants.custom_domain` mevcut, script `scripts/add-custom-domain.sh` deploy root'ta (Faz 0'da düzeltildi: README satır 131-133; script satır 50/100).
5. Onay alındığında eski container'ları (`ki-reservation-app-run-*`, `gallant_carson`, `infallible_ramanujan`) temizle.
6. **Bekleyen kullanıcı doğrulaması:** Pazarlama sayfasına tarayıcıdan admin olarak girip segment/kampanya CRUD + JS akışlarını görsel kontrol et (SESSION_NOTES 2.1).