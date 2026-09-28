<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Settings Registry
 *
 * Centralized schema, metadata, validation, secret masking, and persistence
 * for all configurable tenant settings across the 6 major sections.
 * ---------------------------------------------------------------------------- */

class Settings_registry
{
    protected CI_Controller $CI;

    public const SECTION_BUSINESS = 'business';
    public const SECTION_BOOKING = 'booking';
    public const SECTION_COMMUNICATION = 'communication';
    public const SECTION_INTEGRATIONS = 'integrations';
    public const SECTION_LEGAL = 'legal';
    public const SECTION_SECURITY = 'security';

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('settings_model');
    }

    /**
     * Get the master schema definitions for all or a specific section.
     */
    /**
     * Resolve localized string via language helper, falling back to default.
     */
    protected function trans(string $key, string $default): string
    {
        $val = lang($key);
        return ($val !== '' && $val !== $key) ? $val : $default;
    }

    public function get_schema(?string $section = null): array
    {
        $schema = [
            self::SECTION_BUSINESS => [
                'title' => $this->trans('settings_section_business_title', 'İşletme Profili & Genel Ayarlar'),
                'icon' => 'fas fa-building',
                'tabs' => [
                    'general' => $this->trans('settings_tab_identity', 'Genel Bilgiler'),
                    'hours' => $this->trans('settings_tab_working_plan', 'Çalışma Saatleri & Plan'),
                    'localization' => $this->trans('settings_tab_localization', 'Bölgesel Ayarlar & Para Birimi'),
                    'branding' => $this->trans('settings_tab_branding', 'Marka & Renkler'),
                    'payment' => $this->trans('settings_tab_payment', 'Ödeme & Finans Ayarları'),
                ],
                'settings' => [
                    'company_name' => [
                        'type' => 'string',
                        'tab' => 'general',
                        'label' => $this->trans('settings_field_company_name', 'İşletme Adı'),
                        'description' => $this->trans('settings_field_company_name_desc', 'Randevu ve müşteri bildirimlerinde görüntülenecek işletme unvanı.'),
                        'default' => 'BooKi',
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'company_email' => [
                        'type' => 'email',
                        'tab' => 'general',
                        'label' => $this->trans('settings_field_company_email', 'İşletme E-posta'),
                        'description' => $this->trans('settings_field_company_email_desc', 'Müşterilerin yanıt verebileceği resmi işletme e-posta adresi.'),
                        'default' => '',
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'company_link' => [
                        'type' => 'url',
                        'tab' => 'general',
                        'label' => $this->trans('settings_field_company_link', 'Web Sitesi'),
                        'description' => $this->trans('settings_field_company_link_desc', 'İşletmenizin web sitesi bağlantısı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'company_color' => [
                        'type' => 'color',
                        'tab' => 'branding',
                        'label' => $this->trans('settings_field_company_color', 'Marka Ana Rengi'),
                        'description' => $this->trans('settings_field_company_color_desc', 'Randevu portalında ve butonlarda kullanılacak kurumsal renk (Hex kodu).'),
                        'default' => '#35A768',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'company_logo' => [
                        'type' => 'string',
                        'tab' => 'branding',
                        'label' => $this->trans('settings_field_company_logo', 'Logo URL'),
                        'description' => $this->trans('settings_field_company_logo_desc', 'Müşteri randevu ekranında gösterilecek logo görsel URL.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'white_label_enabled' => [
                        'type' => 'bool',
                        'tab' => 'branding',
                        'label' => $this->trans('settings_field_white_label_enabled', 'White Label (BooKi İmzası Gizlensin)'),
                        'description' => $this->trans('settings_field_white_label_enabled_desc', 'Rezervasyon ve müşteri sayfalarındaki altyapı imzalarını gizler.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'default_timezone' => [
                        'type' => 'select',
                        'tab' => 'localization',
                        'label' => $this->trans('settings_field_default_timezone', 'Zaman Dilimi'),
                        'description' => $this->trans('settings_field_default_timezone_desc', 'İşletmenizin faaliyet gösterdiği saat dilimi.'),
                        'default' => 'Europe/Istanbul',
                        'options' => ['Europe/Istanbul', 'UTC', 'Europe/London', 'Europe/Berlin', 'America/New_York', 'Asia/Dubai'],
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'date_format' => [
                        'type' => 'select',
                        'tab' => 'localization',
                        'label' => $this->trans('settings_field_date_format', 'Tarih Formatı'),
                        'description' => $this->trans('settings_field_date_format_desc', 'Sistem genelinde tarih gösterim standardı.'),
                        'default' => 'DMY',
                        'options' => ['DMY' => 'GG/AA/YYYY (28/09/2026)', 'MDY' => 'AA/GG/YYYY (09/28/2026)', 'YMD' => 'YYYY-AA-GG (2026-09-28)'],
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'time_format' => [
                        'type' => 'select',
                        'tab' => 'localization',
                        'label' => $this->trans('settings_field_time_format', 'Saat Formatı'),
                        'description' => $this->trans('settings_field_time_format_desc', '12 saat veya 24 saat gösterimi.'),
                        'default' => 'regular',
                        'options' => ['regular' => '24 Saat (14:30)', 'military' => '12 Saat AM/PM (02:30 PM)'],
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'first_weekday' => [
                        'type' => 'select',
                        'tab' => 'localization',
                        'label' => $this->trans('settings_field_first_weekday', 'Haftanın İlk Günü'),
                        'description' => $this->trans('settings_field_first_weekday_desc', 'Takvimde haftanın başlangıç günü.'),
                        'default' => 'Monday',
                        'options' => ['Monday' => 'Pazartesi', 'Sunday' => 'Pazar', 'Saturday' => 'Cumartesi'],
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'company_currency' => [
                        'type' => 'string',
                        'tab' => 'localization',
                        'label' => $this->trans('settings_field_company_currency', 'Para Birimi Sembolü / Kodu'),
                        'description' => $this->trans('settings_field_company_currency_desc', 'Örnek: TL, $, €, TRY'),
                        'default' => 'TL',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_cash' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'label' => $this->trans('settings_field_payment_method_cash', 'Nakit Ödeme'),
                        'description' => $this->trans('settings_field_payment_method_cash_desc', 'Müşterilerden nakit tahsilatı kabul et.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_card' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'label' => $this->trans('settings_field_payment_method_card', 'Kredi Kartı / POS'),
                        'description' => $this->trans('settings_field_payment_method_card_desc', 'Kartla ödeme seçeneğini etkinleştir.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_deposit_required' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'label' => $this->trans('settings_field_payment_deposit_required', 'Online Kapora / Ön Ödeme Şartı'),
                        'description' => $this->trans('settings_field_payment_deposit_required_desc', 'Rezervasyon onaylanmadan önce kapora veya tam ödeme alınmasını zorunlu tutar.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_BOOKING => [
                'title' => $this->trans('settings_section_booking_title', 'Randevu & Rezervasyon Kuralları'),
                'icon' => 'fas fa-calendar-check',
                'tabs' => [
                    'rules' => $this->trans('settings_tab_rules', 'Rezervasyon Kuralları'),
                    'timeouts' => $this->trans('settings_tab_timeouts', 'Süreler & Kısıtlamalar'),
                    'calendar' => $this->trans('settings_tab_calendar', 'Takvim Davranışı'),
                ],
                'settings' => [
                    'book_advance_timeout' => [
                        'type' => 'int',
                        'tab' => 'timeouts',
                        'label' => $this->trans('settings_field_book_advance_timeout', 'En Erken Randevu Alma Süresi (Dakika)'),
                        'description' => $this->trans('settings_field_book_advance_timeout_desc', 'Müşterilerin randevu saatinden en az kaç dakika önce rezervasyon yapabileceğini belirler (ör: 60 = 1 saat).'),
                        'default' => 60,
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'cancellation_timeout' => [
                        'type' => 'int',
                        'tab' => 'timeouts',
                        'label' => $this->trans('settings_field_cancellation_timeout', 'Randevu İptal Limiti (Dakika)'),
                        'description' => $this->trans('settings_field_cancellation_timeout_desc', 'Müşterinin randevudan kaç dakika öncesine kadar kendi kendine iptal edebileceği.'),
                        'default' => 120,
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'allow_customer_cancellation' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'label' => $this->trans('settings_field_allow_customer_cancellation', 'Müşteri Randevusunu İptal Edebilsin'),
                        'description' => $this->trans('settings_field_allow_customer_cancellation_desc', 'Açık olduğunda müşteri onay e-postasındaki veya profilindeki bağlantıdan randevusunu iptal edebilir.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'require_phone_number' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'label' => $this->trans('settings_field_require_phone_number', 'Telefon Numarası Zorunlu'),
                        'description' => $this->trans('settings_field_require_phone_number_desc', 'Rezervasyon formu doldurulurken telefon alanını zorunlu tut.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_any_provider' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'label' => $this->trans('settings_field_display_any_provider', '"Fark etmez / İlk Müsait Personel" Seçeneği'),
                        'description' => $this->trans('settings_field_display_any_provider_desc', 'Müşterinin belirli bir personel yerine en erken müsait personeli seçebilmesini sağlar.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'multiple_appointments' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'label' => $this->trans('settings_field_multiple_appointments', 'Aynı Anda Birden Fazla Hizmet / Randevu'),
                        'description' => $this->trans('settings_field_multiple_appointments_desc', 'Müşterinin tek seferde sepet mantığıyla birden fazla seans rezerve etmesine izin verir.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_already_booked_times' => [
                        'type' => 'bool',
                        'tab' => 'calendar',
                        'label' => $this->trans('settings_field_display_already_booked_times', 'Dolu Saatleri Göster (Pasif Olarak)'),
                        'description' => $this->trans('settings_field_display_already_booked_times_desc', 'Müşteri randevu ekranında dolu saatleri gri renkli olarak listeler.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'require_captcha' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'label' => $this->trans('settings_field_require_captcha', 'Rezervasyon Formunda Bot Koruması (Captcha)'),
                        'description' => $this->trans('settings_field_require_captcha_desc', 'Bot saldırılarını ve spam rezervasyonları engellemek için doğrulama kutusu ekler.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_COMMUNICATION => [
                'title' => $this->trans('settings_section_communication_title', 'İletişim & Bildirimler'),
                'icon' => 'fas fa-paper-plane',
                'tabs' => [
                    'notifications' => $this->trans('settings_tab_notifications', 'Bildirim Tercihleri'),
                    'email_smtp' => $this->trans('settings_tab_email_smtp', 'E-posta & SMTP'),
                    'whatsapp' => $this->trans('settings_tab_whatsapp', 'WhatsApp Cloud API'),
                    'telegram' => $this->trans('settings_tab_telegram', 'Telegram Bot'),
                    'sms' => $this->trans('settings_tab_sms', 'SMS Entegrasyonu'),
                ],
                'settings' => [
                    'customer_notifications' => [
                        'type' => 'bool',
                        'tab' => 'notifications',
                        'label' => $this->trans('settings_field_customer_notifications', 'Müşteri E-posta Bildirimleri'),
                        'description' => $this->trans('settings_field_customer_notifications_desc', 'Rezervasyon onay, değişiklik ve iptallerinde müşteriye otomatik e-posta gönderilsin.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_sender_name' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_from_name', 'Gönderici Adı'),
                        'description' => $this->trans('settings_field_email_from_name_desc', 'Giden e-postaların Kimden (From) kısmında görünecek isim.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_sender_address' => [
                        'type' => 'email',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_from_address', 'Gönderici E-posta Adresi'),
                        'description' => $this->trans('settings_field_email_from_address_desc', 'Giden e-postaların çıkış adresi.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_host' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_smtp_host', 'SMTP Sunucusu'),
                        'description' => $this->trans('settings_field_email_smtp_host_desc', 'Ör: smtp.gmail.com veya mail.sirketiniz.com'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_port' => [
                        'type' => 'int',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_smtp_port', 'SMTP Portu'),
                        'description' => $this->trans('settings_field_email_smtp_port_desc', 'Genellikle 587 (TLS) veya 465 (SSL).'),
                        'default' => 587,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_username' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_smtp_user', 'SMTP Kullanıcı Adı'),
                        'description' => $this->trans('settings_field_email_smtp_user_desc', 'SMTP posta kutusu kullanıcı adı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_password' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'label' => $this->trans('settings_field_email_smtp_password', 'SMTP Parolası'),
                        'description' => $this->trans('settings_field_email_smtp_password_desc', 'SMTP hesap şifresi veya uygulama anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'whatsapp_enabled' => [
                        'type' => 'bool',
                        'tab' => 'whatsapp',
                        'label' => $this->trans('settings_field_whatsapp_enabled', 'WhatsApp Bildirimleri Aktif'),
                        'description' => $this->trans('settings_field_whatsapp_enabled_desc', 'Rezervasyon onay ve hatırlatmalarını WhatsApp üzerinden iletin.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'whatsapp_phone_number_id' => [
                        'type' => 'string',
                        'tab' => 'whatsapp',
                        'label' => $this->trans('settings_field_whatsapp_phone_number', 'WhatsApp Phone Number ID'),
                        'description' => $this->trans('settings_field_whatsapp_phone_number_desc', 'Meta for Developers portalındaki telefon numarası kimliği.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'whatsapp_access_token' => [
                        'type' => 'string',
                        'tab' => 'whatsapp',
                        'label' => $this->trans('settings_field_whatsapp_access_token', 'WhatsApp Permanent Access Token'),
                        'description' => $this->trans('settings_field_whatsapp_access_token_desc', 'Cloud API kalıcı erişim belirteci.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'telegram_enabled' => [
                        'type' => 'bool',
                        'tab' => 'telegram',
                        'label' => $this->trans('settings_field_telegram_enabled', 'Telegram Bildirimleri Aktif'),
                        'description' => $this->trans('settings_field_telegram_enabled_desc', 'Yeni rezervasyonları personelinize anında Telegram botuyla gönderin.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'telegram_bot_token' => [
                        'type' => 'string',
                        'tab' => 'telegram',
                        'label' => $this->trans('settings_field_telegram_bot_token', 'Telegram Bot Token'),
                        'description' => $this->trans('settings_field_telegram_bot_token_desc', 'BotFather üzerinden aldığınız bot API anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'telegram_chat_id' => [
                        'type' => 'string',
                        'tab' => 'telegram',
                        'label' => $this->trans('settings_field_telegram_chat_id', 'Telegram Hedef Grup / Kanal ID'),
                        'description' => $this->trans('settings_field_telegram_chat_id_desc', 'Bildirimlerin düşeceği grup ID (ör: -10012345678).'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sms',
                        'label' => $this->trans('settings_field_sms_enabled', 'SMS Bildirimleri Aktif'),
                        'description' => $this->trans('settings_field_sms_enabled_desc', 'Müşterilere SMS hatırlatma ve onay kodu gönderimi.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_provider' => [
                        'type' => 'select',
                        'tab' => 'sms',
                        'label' => $this->trans('settings_field_sms_provider', 'SMS Servis Sağlayıcı'),
                        'description' => $this->trans('settings_field_sms_provider_desc', 'Kullanılacak SMS başlığı operatörü.'),
                        'default' => 'netgsm',
                        'options' => ['netgsm' => 'NetGSM', 'iletimerkezi' => 'İleti Merkezi', 'twillio' => 'Twilio SMS'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_api_key' => [
                        'type' => 'string',
                        'tab' => 'sms',
                        'label' => $this->trans('settings_field_sms_api_key', 'SMS API Anahtarı / Kullanıcı Adı'),
                        'description' => $this->trans('settings_field_sms_api_key_desc', 'SMS servis sağlayıcı erişim anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'sms_sender_id' => [
                        'type' => 'string',
                        'tab' => 'sms',
                        'label' => $this->trans('settings_field_sms_sender_id', 'SMS Gönderici Başlığı (Originator)'),
                        'description' => $this->trans('settings_field_sms_sender_id_desc', 'Onaylı kurumsal SMS başlığınız (ör: BOO-KI).'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_INTEGRATIONS => [
                'title' => $this->trans('settings_section_integrations_title', 'Entegrasyonlar & AI Geliştirici Merkezi'),
                'icon' => 'fas fa-plug',
                'tabs' => [
                    'api' => $this->trans('settings_tab_api', 'AI Geliştirici & MCP Bağlantı Ucu'),
                    'google' => $this->trans('settings_tab_google', 'Google Takvim & Haritalar'),
                    'analytics' => $this->trans('settings_tab_analytics', 'Web Analitik (Google & Matomo)'),
                    'accounting' => $this->trans('settings_tab_accounting', 'Muhasebe (Paraşüt)'),
                    'video' => $this->trans('settings_tab_video', 'Online Görüşme (Jitsi Meet)'),
                ],
                'settings' => [
                    'agent_api_key' => [
                        'type' => 'string',
                        'tab' => 'api',
                        'label' => $this->trans('settings_field_agent_api_key', 'Ajan / MCP Erişim Anahtarı (Agent API Key)'),
                        'description' => $this->trans('settings_field_agent_api_key_desc', 'Claude Desktop, Cursor IDE ve harici sesli AI asistanlarının kimlik doğrulaması.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'mcp_enabled' => [
                        'type' => 'bool',
                        'tab' => 'api',
                        'label' => $this->trans('settings_field_mcp_enabled', 'Model Context Protocol (MCP) Sunucusu Açık'),
                        'description' => $this->trans('settings_field_mcp_enabled_desc', 'Dış yapay zeka ajanlarının randevu müsaitliği sorgulamasına izin verir.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_sync_enabled' => [
                        'type' => 'bool',
                        'tab' => 'google',
                        'label' => $this->trans('settings_field_google_sync_enabled', 'Google Takvim İki Yönlü Senkronizasyon'),
                        'description' => $this->trans('settings_field_google_sync_enabled_desc', 'Personel takvimlerini Google Calendar ile anlık eşitler.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_client_id' => [
                        'type' => 'string',
                        'tab' => 'google',
                        'label' => $this->trans('settings_field_google_client_id', 'Google Client ID'),
                        'description' => $this->trans('settings_field_google_client_id_desc', 'Google Cloud Console OAuth 2.0 İstemci Kimliği.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_client_secret' => [
                        'type' => 'string',
                        'tab' => 'google',
                        'label' => $this->trans('settings_field_google_client_secret', 'Google Client Secret'),
                        'description' => $this->trans('settings_field_google_client_secret_desc', 'Google Cloud Console OAuth 2.0 Gizli Anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'google_analytics_code' => [
                        'type' => 'string',
                        'tab' => 'analytics',
                        'label' => $this->trans('settings_field_google_analytics_code', 'Google Analytics Ölçüm Kimliği (GA4)'),
                        'description' => $this->trans('settings_field_google_analytics_code_desc', 'Rezervasyon adımlarını izlemek için G-XXXXXXXXXX kodunuz.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'matomo_analytics_url' => [
                        'type' => 'url',
                        'tab' => 'analytics',
                        'label' => $this->trans('settings_field_matomo_analytics_url', 'Matomo Sunucu URL'),
                        'description' => $this->trans('settings_field_matomo_analytics_url_desc', 'Kendi sunucunuzdaki Matomo Analytics adresi.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'matomo_site_id' => [
                        'type' => 'string',
                        'tab' => 'analytics',
                        'label' => $this->trans('settings_field_matomo_site_id', 'Matomo Site ID'),
                        'description' => $this->trans('settings_field_matomo_site_id_desc', 'Matomo üzerindeki site kimlik numarası.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'parasut_enabled' => [
                        'type' => 'bool',
                        'tab' => 'accounting',
                        'label' => $this->trans('settings_field_parasut_enabled', 'Paraşüt E-Fatura Entegrasyonu'),
                        'description' => $this->trans('settings_field_parasut_enabled_desc', 'Tamamlanan randevu ve adisyonlar için otomatik fatura oluşturma.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'parasut_client_id' => [
                        'type' => 'string',
                        'tab' => 'accounting',
                        'label' => $this->trans('settings_field_parasut_client_id', 'Paraşüt Client ID'),
                        'description' => $this->trans('settings_field_parasut_client_id_desc', 'Paraşüt API erişim anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'parasut_client_secret' => [
                        'type' => 'string',
                        'tab' => 'accounting',
                        'label' => $this->trans('settings_field_parasut_client_secret', 'Paraşüt Client Secret'),
                        'description' => $this->trans('settings_field_parasut_client_secret_desc', 'Paraşüt API gizli anahtarı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'jitsi_enabled' => [
                        'type' => 'bool',
                        'tab' => 'video',
                        'label' => $this->trans('settings_field_jitsi_enabled', 'Jitsi Meet Online Görüşme'),
                        'description' => $this->trans('settings_field_jitsi_enabled_desc', 'Online seans ve danışmanlıklar için otomatik görüntülü görüşme odası linki üretir.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_LEGAL => [
                'title' => $this->trans('settings_section_legal_title', 'Hukuki Metinler & KVKK / GDPR'),
                'icon' => 'fas fa-balance-scale',
                'tabs' => [
                    'kvkk' => $this->trans('settings_tab_kvkk', 'KVKK & Aydınlatma Metni'),
                    'privacy' => $this->trans('settings_tab_privacy', 'Gizlilik Politikası'),
                    'terms' => $this->trans('settings_tab_terms', 'Kullanım Koşulları'),
                    'cookies' => $this->trans('settings_tab_cookies', 'Çerez Politikası & Banner'),
                ],
                'settings' => [
                    'display_privacy_policy' => [
                        'type' => 'bool',
                        'tab' => 'privacy',
                        'label' => $this->trans('settings_field_display_privacy_policy', 'Gizlilik Politikasını Rezervasyonda Göster'),
                        'description' => $this->trans('settings_field_display_privacy_policy_desc', 'Randevu formunun altına gizlilik politikası onay kutusu ekler.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'privacy_policy_content' => [
                        'type' => 'text',
                        'tab' => 'privacy',
                        'label' => $this->trans('settings_field_privacy_content', 'Gizlilik Politikası Metni'),
                        'description' => $this->trans('settings_field_privacy_content_desc', 'Müşterilere sunulan gizlilik sözleşmesi içeriği (HTML veya düz metin).'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_terms_and_conditions' => [
                        'type' => 'bool',
                        'tab' => 'terms',
                        'label' => $this->trans('settings_field_display_terms_and_conditions', 'Kullanım Koşullarını Rezervasyonda Göster'),
                        'description' => $this->trans('settings_field_display_terms_and_conditions_desc', 'Rezervasyon tamamlanmadan önce kullanım koşulları onayını zorunlu tut.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'terms_and_conditions_content' => [
                        'type' => 'text',
                        'tab' => 'terms',
                        'label' => $this->trans('settings_field_terms_content', 'Kullanım Koşulları Metni'),
                        'description' => $this->trans('settings_field_terms_content_desc', 'Müşterilere sunulan genel kullanım şartları.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_cookie_notice' => [
                        'type' => 'bool',
                        'tab' => 'cookies',
                        'label' => $this->trans('settings_field_display_cookie_notice', 'Çerez İzin Bildirimi (Cookie Banner)'),
                        'description' => $this->trans('settings_field_display_cookie_notice_desc', 'Müşteri randevu sayfasına ilk girdiğinde çerez bilgilendirme çubuğunu açar.'),
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'cookie_notice_content' => [
                        'type' => 'text',
                        'tab' => 'cookies',
                        'label' => $this->trans('settings_field_cookie_notice_content', 'Çerez Bildirim Metni'),
                        'description' => $this->trans('settings_field_cookie_notice_content_desc', 'Çerez bildiriminde yer alacak metin.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_SECURITY => [
                'title' => $this->trans('settings_section_security_title', 'Güvenlik, Kimlik & Yedekleme'),
                'icon' => 'fas fa-shield-alt',
                'tabs' => [
                    'access' => $this->trans('settings_tab_access', 'Oturum & Erişim'),
                    'captcha' => $this->trans('settings_tab_captcha', 'Bot Koruması (Altcha)'),
                    'ldap' => $this->trans('settings_tab_ldap', 'LDAP / Active Directory'),
                    'backup' => $this->trans('settings_tab_backup', 'Veri Yedekleme & Aktarım'),
                ],
                'settings' => [
                    'limit_customer_access' => [
                        'type' => 'bool',
                        'tab' => 'access',
                        'label' => $this->trans('settings_field_limit_customer_access', 'Müşteri Erişimini Kısıtla'),
                        'description' => $this->trans('settings_field_limit_customer_access_desc', 'Müşterilerin panel üzerinden randevu geçmişlerini görmesini sınırlar.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'limit_customer_visibility' => [
                        'type' => 'bool',
                        'tab' => 'access',
                        'label' => $this->trans('settings_field_limit_customer_visibility', 'Personel Müşteri İletişim Bilgilerini Gizle'),
                        'description' => $this->trans('settings_field_limit_customer_visibility_desc', 'Yöneticiler dışındaki personelin müşterilerin telefon ve e-postasını görmesini engeller.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'altcha_enabled' => [
                        'type' => 'bool',
                        'tab' => 'captcha',
                        'label' => $this->trans('settings_field_altcha_enabled', 'Altcha Gizlilik Dostu Captcha'),
                        'description' => $this->trans('settings_field_altcha_enabled_desc', 'Google reCAPTCHA yerine çerezsiz, KVKK uyumlu Proof-of-Work bot engellemesi.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'altcha_hmac_key' => [
                        'type' => 'string',
                        'tab' => 'captcha',
                        'label' => $this->trans('settings_field_altcha_hmac_key', 'Altcha HMAC Gizli Anahtarı'),
                        'description' => $this->trans('settings_field_altcha_hmac_key_desc', 'Altcha challenge imzalamak için kullanılan 256-bit gizli anahtar.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'ldap_enabled' => [
                        'type' => 'bool',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_enabled', 'Kurumsal LDAP Kimlik Doğrulama'),
                        'description' => $this->trans('settings_field_ldap_enabled_desc', 'Personelin kurumsal Active Directory / OpenLDAP parolası ile giriş yapmasını sağlar.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_host' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_host', 'LDAP Sunucu Adresi'),
                        'description' => $this->trans('settings_field_ldap_host_desc', 'Ör: ldap.sirket.com veya 192.168.1.100'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_port' => [
                        'type' => 'int',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_port', 'LDAP Portu'),
                        'description' => $this->trans('settings_field_ldap_port_desc', 'Standart LDAP 389, LDAPS (SSL) 636.'),
                        'default' => 389,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_dn' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_dn', 'Base DN'),
                        'description' => $this->trans('settings_field_ldap_dn_desc', 'Ör: ou=users,dc=sirket,dc=com'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_user' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_user', 'Admin DN / Service Account'),
                        'description' => $this->trans('settings_field_ldap_user_desc', 'Arama yapacak yetkili LDAP kullanıcısı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_password' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'label' => $this->trans('settings_field_ldap_password', 'LDAP Parolası'),
                        'description' => $this->trans('settings_field_ldap_password_desc', 'Yetkili servis hesabı parolası.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                ],
            ],
        ];

        if ($section !== null) {
            return $schema[$section] ?? [];
        }

        return $schema;
    }

    /**
     * Get loaded values for a section, automatically applying secret masking.
     */
    public function get_section_values(string $section, bool $mask_secrets = true): array
    {
        $schema = $this->get_schema($section);
        if (empty($schema) || empty($schema['settings'])) {
            return [];
        }

        $values = [];
        foreach ($schema['settings'] as $key => $meta) {
            $raw = setting($key);
            if ($raw === null) {
                $raw = $meta['default'] ?? null;
            }

            // Type casting
            if ($meta['type'] === 'bool') {
                $val = ($raw === '1' || $raw === 1 || $raw === true);
            } elseif ($meta['type'] === 'int') {
                $val = (int) $raw;
            } else {
                $val = (string) ($raw ?? '');
            }

            // Secret masking
            if ($mask_secrets && !empty($meta['is_secret'])) {
                $val = $this->mask_secret((string) $val);
            }

            $values[$key] = $val;
        }

        return $values;
    }

    /**
     * Check if a specific setting key is marked as secret.
     */
    public function is_secret(string $key): bool
    {
        $all = $this->get_schema();
        foreach ($all as $sec) {
            if (isset($sec['settings'][$key])) {
                return !empty($sec['settings'][$key]['is_secret']);
            }
        }
        return false;
    }

    /**
     * Mask a sensitive secret string.
     */
    public function mask_secret(?string $val): string
    {
        if (empty($val)) {
            return '';
        }
        $len = strlen($val);
        if ($len <= 8) {
            return '••••••••';
        }
        return substr($val, 0, 4) . '••••••••••••' . substr($val, -4);
    }

    /**
     * Check if an entered value is the masked placeholder.
     */
    public function is_masked_placeholder(?string $val): bool
    {
        if (empty($val)) {
            return false;
        }
        return (strpos($val, '••••••••') !== false);
    }

    /**
     * Validate and sanitize payload for a section.
     */
    public function validate_and_sanitize(string $section, array $payload): array
    {
        $schema = $this->get_schema($section);
        if (empty($schema) || empty($schema['settings'])) {
            throw new InvalidArgumentException("Bilinmeyen ayar kategorisi: {$section}");
        }

        $sanitized = [];

        foreach ($schema['settings'] as $key => $meta) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $input = $payload[$key];

            // If it's a secret and user sent the masked placeholder, ignore it (keep existing value)
            if (!empty($meta['is_secret']) && $this->is_masked_placeholder(is_string($input) ? $input : '')) {
                continue;
            }

            if ($meta['type'] === 'bool') {
                $sanitized[$key] = ($input === '1' || $input === 1 || $input === true || $input === 'true') ? '1' : '0';
            } elseif ($meta['type'] === 'int') {
                $sanitized[$key] = (string) max(0, (int) $input);
            } elseif ($meta['type'] === 'email') {
                $val = trim((string) $input);
                if (!empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Geçersiz e-posta formatı ({$meta['label']}): {$val}");
                }
                $sanitized[$key] = $val;
            } elseif ($meta['type'] === 'color') {
                $val = trim((string) $input);
                if (!empty($val) && !preg_match('/^#[a-fA-F0-9]{3,6}$/', $val)) {
                    throw new InvalidArgumentException("Geçersiz renk hex formatı: {$val}");
                }
                $sanitized[$key] = $val;
            } elseif ($meta['type'] === 'select') {
                $allowed = array_keys($meta['options']) !== range(0, count($meta['options']) - 1)
                    ? array_keys($meta['options'])
                    : $meta['options'];
                if (!in_array($input, $allowed, false)) {
                    throw new InvalidArgumentException("Geçersiz seçim ({$meta['label']}): {$input}");
                }
                $sanitized[$key] = (string) $input;
            } else {
                $sanitized[$key] = trim((string) $input);
            }
        }

        return $sanitized;
    }

    /**
     * Persist settings to the database and return audit diff.
     */
    public function save_section_values(string $section, array $sanitized, int $user_id): array
    {
        $schema = $this->get_schema($section);
        $diff = [];

        foreach ($sanitized as $key => $new_value) {
            $existing_record = $this->CI->settings_model->query()->where('name', $key)->get()->row_array();
            $old_value = $existing_record['value'] ?? null;

            if ($old_value !== $new_value) {
                $save_data = [
                    'name' => $key,
                    'value' => $new_value,
                ];
                if (!empty($existing_record)) {
                    $save_data['id'] = $existing_record['id'];
                }
                $this->CI->settings_model->save($save_data);

                // Add to diff (masking secrets in audit trails)
                $is_sec = !empty($schema['settings'][$key]['is_secret']);
                $diff[$key] = [
                    'old' => $is_sec ? '[MASKED]' : $old_value,
                    'new' => $is_sec ? '[MASKED]' : $new_value,
                ];
            }
        }

        // If company_name changed and multi-tenant master exists, sync
        if (isset($sanitized['company_name'])) {
            $tenant = tenant_context();
            if ($tenant && !empty($tenant['id'])) {
                $master = $this->CI->load->database('default', true);
                if ($master && ($master->table_exists($master->dbprefix('tenants')) || $master->table_exists('tenants'))) {
                    if ($master->field_exists('company_name', 'tenants')) {
                        $master->where('id', $tenant['id'])->update('tenants', [
                            'company_name' => $sanitized['company_name'],
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        return $diff;
    }
}
