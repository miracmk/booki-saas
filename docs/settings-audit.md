# 🔍 BooKi SaaS — Ayarlar Merkezi Denetim ve Envanter Raporu (Faz 1)

> **Doküman Türü:** Mimari Denetim, Özellik Envanteri & Kök Neden Analizi  
> **Konum:** `docs/settings-audit.md`  
> **Tarih:** 28 Eylül 2026  
> **Hedef:** Mevcut ayar sayfalarının sıfırdan "Ayarlar Merkezi (Settings Center)" olarak yeniden yazımı için eksiksiz analiz.

---

## 📌 0. Yönetici Özeti & Denetim Bulguları

BooKi SaaS genelinde ayar, yapılandırma ve entegrasyon yönetimi; 35 farklı controller, 20+ dağınık view şablonu, 142 veritabanı ayar anahtarı ve `ea_messaging_settings`, `ea_tenant_ai_policies`, `ea_branches`, `ea_consents`, `ea_data_requests` gibi izole tablolara parçalanmıştır.

### Kritik Güvenlik ve İşlevsellik Bulguları:
1. **Hata A (Yetkisiz Rolde 403 Kilitlenmesi):** `Demo::switch_role()` istisnasız `redirect('dashboard')` yapmakta; `enforce_route_permissions()` ise `dashboard` için `view dashboard` izni aramakta, olmayan personele (örn. Mutfak, Garson) layout'suz ve çıkış düğmesiz yalın `error_general.php` göstermektedir. Ayrıca 41 controller'da `session(['dest_url' => ...])` yetki kontrolünden **önce** yazılmakta, yasak sayfaya kilitlenen kullanıcı döngüye girmektedir.
2. **Hata B (MCP & API Bilgilerine Erişilememesi):** `Integrations` ve `Api_settings` sayfaları `cannot('view', PRIV_SYSTEM_SETTINGS)` kontrolüyle kapatılmıştır; ancak sidebar ve kartlar bu yetkiden bağımsız görüntülenmektedir. `Email_template_settings` ve `Channel_template_settings` içinde `session('role_slug') === 'admin'` kontrolü yapılmakta, bu sebeple `role_slug = 'owner'` olan demo işletme sahibi admin olmasına rağmen şablonlara erişememektedir. `agent_api_key` düz metin olarak JS `script_vars` içine basılmakta ve `mcp_url` boş subdomain durumunda geçersiz URL üretmektedir.
3. **Bağlantısız Özellikler (Orphan Features):** Şubeler, Denetim Kayıtları (Audit Log), Özel Alan Adı (Custom Domain), CalDAV, Zadarma (PBX/VoIP), Ödeme Sağlayıcıları (iyzico, PayTR, Stripe), KVKK Veri Talepleri, Rıza Kayıtları ve AI Yönetişimi sistemde tam kodlanmış olmasına rağmen Ayarlar menüsünden ulaşılamamaktadır.
4. **Ölü ve Yinelenen Ayarlar:** 22 ayar anahtarı hiçbir iş mantığı tarafından tüketilmemekte (örn. `display_custom_field_1..5`, `api_token`), bazı entegrasyonlar (Google Calendar/Analytics) hem `Integrations` hem de `Google_integrations` altında çift kart olarak yinelenmektedir.

---

## 🛠️ 1. Mevcut Özellik Envanteri (35 Controller)

| # | Controller | Route | View | Kullanılan Ayar Anahtarları & Tablolar | Yazdığı Uç Noktalar | Tüketen Kod / Servis | Yetki Kuralı | Modül Bağımlılığı |
|---|---|---|---|---|---|---|---|---|
| 1 | `General_settings` | `/general_settings` | `pages/general_settings` | `company_name`, `company_email`, `company_link`, `company_color`, `company_logo`, `date_format`, `time_format`, `first_weekday`, `default_language`, `default_timezone`, `currency_symbol`, `currency_code`, `marketplace_*` (`ea_settings`) | `POST /general_settings/save` | `App_Controller`, `Booking`, `Calendar`, `Marketplace` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok (Çekirdek) |
| 2 | `Industry_settings` | `/industry_settings` | `pages/industry_settings` | `industry_code`, `industry_custom_terminology`, `features_enabled_json`, `slot_interval`, `future_booking_limit` | `POST /industry_settings/apply_blueprint`, `/save` | `Vertical_service`, `Navigation_service`, `industry_helper` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 3 | `Booking_settings` | `/booking_settings` | `pages/booking_settings` | `display_*`, `require_*` (9 alan), `display/require/label_custom_field_1..5`, `display_any_provider`, `display_login_button`, `disable_booking`, `disable_booking_message` | `POST /booking_settings/save` | `Booking.php` (Public Wizard), `/api/v1/appointments` | `PRIV_SYSTEM_SETTINGS` (view/edit) | `appointments` |
| 4 | `Business_settings` | `/business_settings` | `pages/business_settings` | `company_working_plan`, `book_advance_timeout`, `future_booking_limit`, `session_deviation_tolerance_minutes`, `session_duration_baseline`, `ai_assistant_enabled`, `appointment_status_options` | `POST /business_settings/save`, `/save_working_plan` | `Booking::get_available_hours()`, `Calendar`, `Checkin`, `compute_effective_billing()` | `PRIV_SYSTEM_SETTINGS` (view/edit) | `calendar` |
| 5 | `Legal_settings` | `/legal_settings` | `pages/legal_settings` | `display_cookie_notice`, `cookie_notice_content`, `display_terms_and_conditions`, `terms_and_conditions_content`, `display_privacy_policy`, `privacy_policy_content`, `legal_notice_url`, `imprint_url` | `POST /legal_settings/save` | `Booking.php` (Wizard altı modal ve onaylar), `Legal.php` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 6 | `Messaging_settings` | `/messaging_settings` | `pages/messaging_settings` | Tablo: `ea_messaging_settings` (SMS Netgsm, WhatsApp Cloud/Bridge, Telegram, Instagram, SMTP, Zadarma Çağrı, Hatırlatma saatleri) | `POST /messaging_settings/save_settings`, `/send_test_sms` | `notifications.php`, `Jobs::cron_send_appointment_reminders()`, `Telegram.php`, `Whatsapp.php` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 7 | `Integrations` | `/integrations` | `pages/integrations` | Dağınık kartlar listesi (Webhooks, Google, Matomo, API, MCP, LDAP, Jitsi, ALTCHA, Custom Domain, CalDAV, Instagram, WhatsApp, SMTP) | GET Only (Hub Sayfası) | Ayarlar alt sayfalarına yönlendirici | `PRIV_SYSTEM_SETTINGS` (view) | Yok |
| 8 | `Api_settings` | `/api_settings` | `pages/api_settings` | `agent_api_key`, `api_token` (`ea_settings`), MCP yapılandırması | `POST /api_settings/rotate_agent_key` | `Agent_api.php`, `booki-mcp` (Port 8765), `/api/v1/*` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 9 | `Data_transfer` | `/data_transfer` | `pages/data_transfer` | Müşteri, Hizmet, Randevu CSV içe/dışa aktarma | `POST /data_transfer/export_*`, `/import_*` | Model katmanları (`customers`, `services`, `appointments`) | `PRIV_SYSTEM_SETTINGS` (view/add) | Yok |
| 10 | `Webhooks` | `/webhooks` | `pages/webhooks` | Tablo: `ea_webhooks` | `POST /webhooks/save`, `/delete`, `/test` | `Webhooks_client.php` (randevu olaylarında dış tetikleyici) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 11 | `Google_integrations` | `/google_integrations` | `pages/google_integrations` | `google_sync_feature`, `google_client_id`, `google_client_secret`, Google Analytics & Ads | `POST /google_integrations/save` | `Google_sync.php`, `Google.php` (OAuth) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 12 | `Google_calendar_settings`| `/google_calendar_settings`| `pages/google_calendar_settings` | `google_client_id`, `google_client_secret` (Eski kopya) | `POST /google_calendar_settings/save` | `Google.php` (Yinelenen controller) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 13 | `Google_analytics_settings`| `/google_analytics_settings`| `pages/google_analytics_settings` | `google_analytics_code` (Eski kopya) | `POST /google_analytics_settings/save` | `Booking.php` (Yinelenen controller) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 14 | `Matomo_analytics_settings`| `/matomo_analytics_settings`| `pages/matomo_analytics_settings` | `matomo_analytics_url`, `matomo_analytics_site_id` | `POST /matomo_analytics_settings/save` | `Booking.php` (Takip kodu enjeksiyonu) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 15 | `Ldap_settings` | `/ldap_settings` | `pages/ldap_settings` | `ldap_host`, `ldap_port`, `ldap_user_dn`, `ldap_password`, `ldap_base_dn`, `ldap_filter`, `ldap_field_mapping`, `ldap_is_active` | `POST /ldap_settings/save`, `/test_connection` | `Ldap_provider.php` (Kurumsal kullanıcı senkronu) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 16 | `Jitsi_settings` | `/jitsi_settings` | `pages/jitsi_settings` | `jitsi_domain` | `POST /jitsi_settings/save` | `Appointments::index()` (Online görüşme linki) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 17 | `Altcha_settings` | `/altcha_settings` | `pages/altcha_settings` | `altcha_hmac_key`, `altcha_max_number`, `altcha_expires`, `altcha_enabled` | `POST /altcha_settings/save` | `Captcha.php`, `Booking.php` (Spam engelleme) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 18 | `Payment_settings` | `/payment_settings` | `pages/payment_settings` | `payment_provider` (iyzico, PayTR, Stripe anahtarları) | `POST /payment_settings/save` | `Payment_webhooks.php`, `Booking.php`, `Adisyons.php` | `PRIV_SYSTEM_SETTINGS` (view/edit) | `pos` / `finance` |
| 19 | `Custom_domain` | `/custom_domain` | `pages/custom_domain` | Tablo: `ea_tenants.custom_domain` (Master DB) | `POST /custom_domain/save`, `/verify` | `App_Controller::resolve_tenant()` | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 20 | `Caldav` | `/caldav` | `pages/caldav` | Tablo: `ea_users.caldav_token`, Apple/Outlook sync | `POST /caldav/generate_token` | `Caldav.php` (RFC 4791 Takvim akışı) | `PRIV_USERS` (kendi profili) | Yok |
| 21 | `Whatsapp` | `/whatsapp` | `pages/whatsapp` | Tablo: `ea_messaging_settings` (QR bridge oturumu) | `POST /whatsapp/start_session`, `/logout` | `booki-wa:3000` (Baileys sidecar) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 22 | `Telegram` | `/telegram` | `pages/telegram` | `telegram_bot_token`, `telegram_bot_username`, webhook secret | `POST /telegram/save`, `/set_webhook` | `Telegram.php` (Bot bildirimleri ve randevu akışı) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 23 | `Instagram` | `/instagram` | `pages/instagram` | `instagram_access_token`, `instagram_account_id` | `POST /instagram/save`, `/verify` | `Instagram.php` (Direct Message entegrasyonu) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 24 | `Zadarma` | `/zadarma` | (View yok / API) | `call_provider`, `call_api_key`, `call_from_number` | `POST /zadarma/webhook` | `Zadarma.php` (Sesli teyit aramaları) | Public Webhook | Yok |
| 25 | `Email_template_settings`| `/email_template_settings`| `pages/email_template_settings` | `email_template_*` (10 şablon) | `POST /email_template_settings/save`, `/preview` | `Email_messages.php` | `role_slug === 'admin'` (**Hatalı!**) | Yok |
| 26 | `Channel_template_settings`| `/channel_template_settings`| `pages/channel_template_settings`| `channel_template_*` (6 şablon) | `POST /channel_template_settings/save`, `/preview` | `Channel_templates.php` | `role_slug === 'admin'` (**Hatalı!**) | Yok |
| 27 | `Blocked_periods` | `/blocked_periods` | `pages/blocked_periods` | Tablo: `ea_secretaries_providers` unavailabilities | `POST /blocked_periods/save`, `/delete` | `Calendar`, `Booking::get_available_hours()` | `PRIV_BLOCKED_PERIODS` | `calendar` |
| 28 | `Working_plan_exceptions`| `/working_plan_exceptions`| Modal bileşeni | Tablo: `ea_working_plan_exceptions` | `POST /working_plan_exceptions/save` | `Calendar`, `Booking` | `PRIV_SYSTEM_SETTINGS` / `PRIV_USERS` | `calendar` |
| 29 | `Localization` | `/localization` | (AJAX API) | Dil dosyaları (`application/language/`) | `GET /localization/get_translations` | Tüm frontend JS i18n (`lang.min.js`) | Public / Session | Yok |
| 30 | `Branches` | `/branches` | `pages/branches` | Tablo: `ea_branches`, `ea_user_branches` | `POST /branches/save`, `/delete` | `Permission_service::check_branch_access` | `branches` (view/edit) | Yok |
| 31 | `Audit_log` | `/audit_log` | `pages/audit_log` | Tablo: `ea_audit_log` | `GET /audit_log`, `/filter` | `salonflora_audit_helper.php`, SOC 2 & ISO Denetimi | `PRIV_SYSTEM_SETTINGS` (view) | Yok |
| 32 | `Data_requests` | `/data_requests` | `pages/data_requests` | Tablo: `ea_data_requests` (KVKK silme/erişim) | `POST /data_requests/process` | `Customers.php` (Kişisel veri silme) | `PRIV_SYSTEM_SETTINGS` (view/edit) | Yok |
| 33 | `Consents` | `/consents` | `pages/consents` | Tablo: `ea_consents` (Müşteri onay kütüğü) | `GET /consents/list` | `Booking.php` (Aydınlatma metni onayı) | `PRIV_SYSTEM_SETTINGS` (view) | Yok |
| 34 | `Ai_agent` | `/ai_agent` | `pages/ai_agent` | Tablo: `ea_tenant_ai_policies`, `ea_ai_learned_rules`, `ea_ai_escalation_handoffs` | `POST /ai_agent/save_policy`, `/approve_rule` | `Ai_governance_service.php`, `booki-mcp` | `ai_agent` (view/edit) | `ai_agent` |
| 35 | `Superadmin_settings` | `/superadmin_settings` | `pages/superadmin_settings` | Master DB `settings` tablosu | `POST /superadmin_settings/save` | SaaS genel altyapı ayarları | `superadmin` oturumu | Yok |

---

## 🗑️ 2. Ölü ve Yinelenen Ayar Listesi

### A. Ölü (Dead) Ayar Anahtarları (Kodda Okunmayan / Tüketilmeyen)
1. `display_custom_field_1` .. `5`: `booking_settings` görünümünde switch var, DB'ye yazılıyor; fakat `Booking.php` randevu formunda veya randevu modelinde işlenmiyor.
2. `require_custom_field_1` .. `5`: Doğrulama kuralı işletilmiyor.
3. `label_custom_field_1` .. `5`: Müşteriye gösterilmiyor.
4. `api_token`: Eski stok Easy!Appointments kalıntısı. Modern sistemde JWT (`Authorization: Bearer`) ve `agent_api_key` kullanılıyor.
5. `cash_register_auto_open`: POS için açılmış ancak donanım/yazılım tarafında karşılığı bağlanmamış.
6. `e_invoice_auto_issue`, `e_invoice_provider`: Fatura otomatikleştirme bayrağı kodda okunmuyor (`Invoices.php` manuel kesim yapıyor).
7. `no_show_threshold_for_deposit`: Randevuya gelmeyen müşteri için depozito kuralı mantıksal olarak bağlanmamış.
8. `ai_assistant_enabled` (`business_settings` içindeki): "Voice-to-text coming soon" şeklinde bırakılmış statik bayrak; yeni `Ai_governance_service` ile entegre değil.

### B. Yinelenen (Duplicate) Controller ve Görünümler
1. **Google Takvim & Analitik:**
   - `Google_calendar_settings.php` ve `Google_analytics_settings.php` bağımsız controller olarak durmakta; ancak `Google_integrations.php` zaten her ikisini de kapsayan merkezi bir hub sunmaktadır. İkisi gereksiz kod kopyasıdır.
2. **Bildirim Şablonları:**
   - `Email_template_settings.php` ve `Channel_template_settings.php` neredeyse birebir aynı CRUD ve preview mantığını farklı controller'larda tekrarlamaktadır. Tek bir "Şablonlar" (Templates) servisinde birleştirilmelidir.
3. **Pazaryeri Profil Bilgileri:**
   - `marketplace_category`, `marketplace_city`, `marketplace_district` vb. hem `General_settings` hem de `Randevuburada.php` profili altında çift yerden yönetilmeye çalışılmaktadır.

---

## 🔗 3. Bağlantısız Özellikler Listesi (Orphan Features)

Aşağıdaki özellikler backend'de tam fonksiyonel olarak yazılmış olmasına rağmen, Ayarlar sol menüsünde (`settings_nav.php`) yer almamakta ve kullanıcılar tarafından keşfedilememektedir:
1. **Şubeler (`/branches`):** Çok şubeli işletmeler için şube ekleme/düzenleme ve personel atama ekranı var, menüde bağlantısı yok.
2. **Denetim Kayıtları (`/audit_log`):** ISO 27001 ve SOC 2 uyumlu tam denetim kütüğü var, menüde bağlantısı yok.
3. **Roller & İzinler:** Migration 170 ile 22 rol ve 10 aksiyonluk JSON permission altyapısı kuruldu; ancak kiracının rollerin izinlerini özelleştirebileceği bir arayüz Ayarlar altında mevcut değil.
4. **Özel Alan Adı (`/custom_domain`):** Kiracının kendi domainini (örn. `randevu.salonflora.com`) bağlamasını sağlayan CNAME doğrulama ve SSL sihirbazı var, menüde bağlantısı yok.
5. **CalDAV Senkronizasyonu (`/caldav`):** iPhone Takvim ve Outlook için doğrudan CalDAV feed ve parola üretim ekranı var, menüde bağlantısı yok.
6. **Zadarma Sesli Çağrı (`/zadarma`):** Otomatik telefonla randevu teyit robotu altyapısı var, Ayarlar'da yapılandırma kartı yok.
7. **Ödeme Sağlayıcıları (`/payment_settings`):** iyzico, PayTR ve Stripe API anahtar girişleri var, `settings_nav` içinde listelenmiyor.
8. **KVKK Veri Talepleri (`/data_requests`) & Rıza Kütüğü (`/consents`):** Müşteri kişisel verilerini anonimleştirme/silme onay takip sistemi var, menüde bağlantısı yok.
9. **AI Yönetişimi (`/ai_agent`):** İşletme AI politikası, onay bekleyen öğrenilmiş kurallar ve insan eskalasyon devirleri var, Ayarlar içinde bağlı değil.
10. **RandevuBurada Pazaryeri Profili:** PSEO vitrin görseli, fiyat aralığı ve tanıtım metni Ayarlar içinde derli toplu bir vitrin sekmesinde değil.

---

## ⚠️ 4. Bildirilen Hataların Kök Neden Analizi (Deep-Dive)

### 🔴 Hata A: Yetkisiz Demo Rolüne Geçince 403 Ekranında Kilitli Kalınması

#### Kök Neden Mekanizması:
1. `Demo::switch_role()` çağrıldığında, oturum verileri (`role_slug`, `job_title`, `user_id`) başarıyla güncellenir ve hemen ardından `redirect('dashboard')` çalıştırılır.
2. `App_Controller::enforce_route_permissions()` satır 642'de `dashboard` rotasını inceler:
   ```php
   if (!$this->permission_service->can('view', 'dashboard', $user_id)) {
       if ($controller === 'dashboard' && $this->permission_service->can('view', PRIV_APPOINTMENTS, $user_id)) {
           return;
       }
       abort(403, 'Bu sayfaya erişim yetkiniz bulunmamaktadır.');
   }
   ```
3. Restoran `kitchen` (Mutfak KDS) veya `waiter` (Garson) rollerinin `permissions_json` tanımlarında `dashboard:view` YASAKTIR. `kitchen` sadece `verticals_kds` ve `adisyons` görebilir. `appointments:view` de mutfakta kapalıdır.
4. Bu durumda `abort(403)` tetiklenir. `abort()` fonksiyonu CodeIgniter'ın varsayılan `show_error()` metodunu çağırır.
5. Sistemde özel `error_403.php` bulunmadığından `application/views/errors/html/error_general.php` yüklenir.
6. Bu şablon; **menüsüz, layout'suz, sidebar'sız, CSS'siz ve oturum/çıkış düğmesiz** düz beyaz bir HTML kutusudur.
7. Kullanıcı bu ekranda kalır; "Geri" tuşuna bassa dahi `dest_url` döngüsüne yakalanır çünkü controller'lar `dest_url` değerini yetki kontrolünden **önce** yazmaktadır!

#### Kapsamlı Çözüm Planı:
1. `Permission_service::first_accessible_route(int $user_id): string` metodu eklenecek. `Navigation_service->forCurrentUser()` çıktısını tarayarak modülü açık ve yetkisi geçerli olan ilk aktif menü rotasını (örn. Mutfak için `restaurant/kitchen_screen`, Garson için `restaurant/waitress_screen`) döndürecek. Hiçbir rota yoksa `account` (profil/çıkış) sayfasına düşecek.
2. `Demo::switch_role()` ve login sonrası yönlendirmeler sabit `redirect('dashboard')` yerine bu `first_accessible_route` rotasını kullanacak. Rol değişiminde `dest_url` temizlenecek.
3. `enforce_route_permissions()` GET isteklerinde 403 yerine kullanıcıyı `first_accessible_route` sayfasına flash mesajla yönlendirecek; AJAX isteklerinde ise JSON 403 yanıtı verecek.
4. Tam layout'lu `views/errors/html/error_403.php` şablonu oluşturulacak (Net hata mesajı, "Bana Uygun Sayfaya Git" butonu, canlı Demo Rol Seçici ve "Çıkış Yap" aksiyonu).

---

### 🔴 Hata B: MCP Sunucu ve API Bilgileri Ekranına Girilememesi

#### Kök Neden Mekanizması:
1. `Integrations::index()` ve `Api_settings::index()` kontrollerinde:
   ```php
   if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
       if ($user_id) { abort(403, 'Forbidden'); }
   }
   ```
   Ancak `settings_nav.php` ve `views/pages/integrations.php` bu linkleri `can('view', PRIV_SYSTEM_SETTINGS)` kontrolü yapmadan render etmektedir (Görünürlük ile yetki uyumsuzluğu).
2. `Email_template_settings` ve `Channel_template_settings` kontrollerinde:
   ```php
   if (session('role_slug') !== DB_SLUG_ADMIN) { ... abort(403); }
   ```
   Burada `DB_SLUG_ADMIN = 'admin'` sabitiyle katı eşitlik kontrol edilmektedir. Oysa demo işletme sahibi rolünde `role_slug = 'owner'` ve `is_admin = 1`dir. Rol slug ile yetki kavramı birbirine karıştırıldığı için demo sahibi şablonları açamamaktadır.
3. `Api_settings.php` satır 59-63:
   ```php
   $agent_api_key = setting('agent_api_key');
   if (empty($agent_api_key) && can('edit', PRIV_SYSTEM_SETTINGS)) {
       $agent_api_key = bin2hex(random_bytes(32));
       $this->settings_model->set_setting('agent_api_key', $agent_api_key);
   }
   ```
   Salt-okunur (`view`) yetkisine sahip kullanıcı sayfaya girdiğinde ve anahtar henüz DB'de yoksa, anahtar üretilmemekte ve boş kalmaktadır.
4. `Api_settings.php` satır 65-66:
   ```php
   $mcp_url = 'https://' . $app_domain . '/mcp?tenant=' . urlencode($subdomain);
   ```
   Tek kiracılı veya demo ortamında `tenant_context()` null ise `$subdomain` boş kalmakta ve `?tenant=` geçersiz bağlantı adresi oluşmaktadır.
5. Güvenlik Açığı: `Api_settings.php` satır 73'te `script_vars(['agent_api_key' => $agent_api_key])` ile kritik sır tarayıcıya düz metin olarak JS penceresine basılmaktadır!
6. MCP Butonunun Soluk Görünmesi: `views/pages/integrations.php` içindeki buton `.btn-primary` sınıfına sahiptir. `company_color_style.php` işletme rengi beyaz (`#ffffff`) olduğunda Bootstrap `--bs-primary` değişkenini beyaza çevirerek butonun kontrastını sıfırlamakta ve pasif/görünmez kılmaktadır.

#### Kapsamlı Çözüm Planı:
1. `DB_SLUG_ADMIN` kontrolleri (17 yerin tamamı) `is_admin` veya `can('edit', 'system_settings')` tabanlı kontrole dönüştürülecek.
2. Entegrasyonlar altında yeni **"Geliştirici" (Developer)** sekmesi açılacak (API & MCP tek merkezde toplanacak).
3. `agent_api_key` veritabanında tek yönlü hash olarak saklanacak, arayüzde varsayılan olarak maskelenecek (`••••••a1b2`). Yalnızca ilk üretim anında tam gösterilecek; "Yenile" butonunda onay modalı çalışacak.
4. `mcp_url` için `tenant_context()` boş olduğunda varsayılan domain fallback'i uygulanacak. Hazır istemci konfigürasyonları (Claude Desktop, Cursor, ElevenLabs) JSON formatında tek tıkla kopyalanabilir kılınacak ve 17 aracın listesi canlı sunucu sorgusuyla gösterilecek.

---

## 🎨 5. Mevcut Ekranlardaki UX/UI Sorunları Analizi

1. **Dil Bütünlüğü Eksikliği:** Menüde "Settings", "Booking Settings", "Business Logic", "Legal Contents", "Integrations", "Save", "Configure", "Cookie Notice" İngilizce; diğer alanlar Türkçe. Çözüm: Bütün metinler `application/language/{turkish,english}/translations_lang.php` dil anahtarlarına taşınacak.
2. **Dağınık Sayfa Yüklemeleri:** 8+ ayrı bağımsız controller her ayar için tam sayfa yenilemesi yapıyor. Çözüm: Sol menülü, derin bağlantılı (`/settings#booking/rules`), modern tek sayfa deneyimli **Ayarlar Merkezi**.
3. **Bildirim Ayarları Yorgunluğu:** `messaging_settings.php` ~580 satırlık tek sayfada 7 kartı üst üste yığmış durumda. Kanal durumu (Bağlı/Bağlı Değil) belirsiz. Çözüm: Sekmeli veya kart bazlı kurulum sihirbazları + canlı test butonları.
4. **Yer Tutucu (Placeholder) Karmaşası:** SMTP port `0`, `smtp.example.com`, `sifre`, `EAABs...` gibi örnekler sanki kaydedilmiş gerçek veriymiş gibi algılanıyor. Çözüm: Placeholder ile değer kesin ayrılacak; şifreler maskelenecek; port (1-65535) ve e-posta kontrolleri satır içi (inline) doğrulanacak.
5. **Business Logic Tablo Tekrarları:** 7 gün × 3 alanlık ham tablolar tekrarlı ve yorucu. Çözüm: Görsel haftalık zaman çizelgesi, "Hafta içine kopyala" düğmesi, tek tıkla tüm personele yayma seçeneği.
6. **Booking Settings Canlı Önizleme Yokluğu:** 9 alan ve display/require anahtarlarının müşteriye nasıl yansıdığı görülemiyor. Çözüm: Sağ tarafta gerçek zamanlı güncellenen mini randevu formu önizleme simülatörü.
7. **Legal Ham Textarea:** Sözleşmeler düz `<textarea>` içinde ham metin olarak düzenleniyor. Çözüm: Markdown/Zengin metin editörü + Türkiye KVKK, Açık Rıza ve Çerez Politikası hazır yasal şablon kütüphanesi.
8. **Veriler Bölümü Yetersizliği:** `data_transfer.php` yalnızca iki küçük dosya yükleme kartından ibaret; yükleme öncesi veri sütun eşleme ve doğrulama adımı yok. Çözüm: Önizlemeli içe aktarma sihirbazı.
9. **Sektör Değişikliğinde Yıkıcı Risk:** Sektör değişikliği tüm randevuları etkileyebilecek bir işlemdir. Çözüm: Yazarak onay kutusu ("ONAYLIYORUM") + işlem öncesi otomatik tek tıkla tam yedek indirme önerisi.
10. **Kaydet Butonu Geri Bildirimi:** Sayfada neyin değiştiği (dirty state) belli olmuyor. Çözüm: Altta beliren yapışkan kaydet çubuğu ("Kaydedilmemiş 3 değişiklik var") + Kaydet/Vazgeç butonları.

---

## 🏛️ 6. Hedef Bilgi Mimarisi & URL Yönlendirme (301) Planı

Yeni **Ayarlar Merkezi** (`/settings`), 6 ana kategori ve altında organize edilmiş derin sekmelerden (deep links) oluşacaktır:

```text
/settings
├── #business (İşletme)
│   ├── general               # Şirket adı, logo, renk, yerelleştirme (Eski: general_settings)
│   ├── industry              # Sektör blueprint, aktif modüller (Eski: industry_settings)
│   ├── branches              # Çoklu şube yönetimi (Eski: branches)
│   ├── marketplace           # RandevuBurada pazar yeri vitrin profili (Eski: randevuburada/profile)
│   └── mobile_apps           # Mobil uygulama bağlantıları ve QR kodu
│
├── #booking (Rezervasyon)
│   ├── fields                # Form alanları, özel alanlar, canlı önizleme (Eski: booking_settings)
│   ├── schedule              # Çalışma planı, molalar, tatiller, istisnalar (Eski: business_settings/working_plan)
│   ├── rules                 # İptal/erteleme limiti, tolerans, süre hesabı (Eski: business_settings/rules)
│   └── statuses              # Randevu durum seçenekleri ve renkleri (Eski: appointment_status_options)
│
├── #communication (İletişim)
│   ├── channels              # WhatsApp (Bridge/Cloud), Telegram, Instagram, SMS, Zadarma (Eski: messaging_settings)
│   ├── email_smtp            # SMTP sunucu ayarları ve bağlantı testi
│   ├── reminders             # Otomatik randevu hatırlatma kuralları (saat/offset)
│   ├── templates             # E-posta ve kanal mesaj şablonları (Eski: email/channel_template_settings)
│   └── ai_copilot            # AI Asistan ayarları, kuralları ve öğrenme onayları (Eski: ai_agent)
│
├── #integrations (Entegrasyonlar)
│   ├── payments              # iyzico, PayTR, Stripe sanal POS (Eski: payment_settings)
│   ├── google                # Google Takvim, Analytics, Meet (Eski: google_integrations)
│   ├── developer             # API & MCP Ajan Bağlantısı (Eski: api_settings)
│   ├── webhooks              # Dış webhook bildirimleri (Eski: webhooks)
│   └── tools                 # Matomo, LDAP, Jitsi, ALTCHA, Custom Domain, CalDAV
│
├── #legal (Yasal & Gizlilik)
│   ├── policies              # Çerez, Şartlar, Gizlilik, KVKK metinleri & hazır şablonlar (Eski: legal_settings)
│   ├── imprint               # Yasal bildirim & şirket künyesi
│   ├── consents              # Açık rıza denetim kayıtları (Eski: consents)
│   └── data_requests         # KVKK kişisel veri erişim ve silme talepleri (Eski: data_requests)
│
└── #security (Güvenlik & Veri)
    ├── roles_permissions     # 22 Rol × 10 Aksiyon yetki düzenleyici
    ├── audit_log             # Sistem denetim kayıtları (Eski: audit_log)
    └── data_transfer         # CSV İçe / Dışa aktarma ve yedekleme (Eski: data_transfer)
```

### URL Eşleme & Geriye Dönük Uyumluluk (301 Redirects):
| Eski URL | Yeni Hedef Rota | Yönlendirme Türü |
|---|---|---|
| `/general_settings` | `/settings#business/general` | 301 Kalıcı Yönlendirme |
| `/industry_settings` | `/settings#business/industry` | 301 Kalıcı Yönlendirme |
| `/booking_settings` | `/settings#booking/fields` | 301 Kalıcı Yönlendirme |
| `/business_settings` | `/settings#booking/schedule` | 301 Kalıcı Yönlendirme |
| `/messaging_settings`| `/settings#communication/channels` | 301 Kalıcı Yönlendirme |
| `/email_template_settings` | `/settings#communication/templates` | 301 Kalıcı Yönlendirme |
| `/channel_template_settings` | `/settings#communication/templates` | 301 Kalıcı Yönlendirme |
| `/integrations` | `/settings#integrations` | 301 Kalıcı Yönlendirme |
| `/api_settings` | `/settings#integrations/developer` | 301 Kalıcı Yönlendirme |
| `/payment_settings` | `/settings#integrations/payments` | 301 Kalıcı Yönlendirme |
| `/legal_settings` | `/settings#legal/policies` | 301 Kalıcı Yönlendirme |
| `/data_transfer` | `/settings#security/data_transfer` | 301 Kalıcı Yönlendirme |
| `/google_integrations` | `/settings#integrations/google` | 301 Kalıcı Yönlendirme |
| `/google_calendar_settings` | `/settings#integrations/google` | 301 Kalıcı Yönlendirme |
| `/google_analytics_settings`| `/settings#integrations/google` | 301 Kalıcı Yönlendirme |

---

## ⚙️ 7. Teknik Uygulama Mimarisi (Faz 2 & 3 İçin Temel)

1. **Merkezi Ayar Şeması (`Settings_registry.php`):**
   - Her ayar anahtarı tek bir kaynakta tanımlanacak:
     `[key, type, default, validation_rules, permission, module, is_secret, consumer]`
   - Form alanları, backend doğrulamaları ve API yanıtları bu şemadan dinamik türetilecek.
2. **Birleşik REST API Uç Noktaları:**
   - `GET /settings/api/{section}`: İlgili bölümün geçerli değerlerini (sırlar maskelenmiş olarak) döner.
   - `PUT /settings/api/{section}`: Kısmi güncelleme (partial update) uygular, sürüm/zaman damgası ile eşzamanlılık (concurrency) kontrolü yapar ve `audit_log()` kaydeder.
3. **Sır Yönetimi & Maskeleme:**
   - Şifre, gizli anahtar ve token'lar veritabanında `salonflora_crypto_helper` (`SFENC1:`) ile saklanacak; arayüze hiçbir zaman açık metin verilmeyecek (`••••••a1b2`).
4. **Teknoloji Uyumu:**
   - Ekstra karmaşık JS framework bağımlılığı getirilmeden, mevcut Bootstrap 5 + modern vanilla JS / jQuery bileşen yapısı korunacak; hafif bir sekme/state yöneticisi ile SPA akıcılığı sağlanacaktır.

---

> **Faz 1 Sonu:** Denetim ve envanter raporu tamamlanmıştır. Kod geliştirmelerine (Faz 2 & 3) geçmek için kullanıcı onayı beklenmektedir.
