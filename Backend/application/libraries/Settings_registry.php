<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Settings Registry
 *
 * Centralized schema, metadata, validation, secret masking, and persistence
 * for all configurable tenant settings across the 6 major sections:
 * - Business: Profile, Hours, Localization, Branding, 3x2 Payment Methods & Online Kapora
 * - Booking: Rules, Timeouts, Calendar, Sector Form Customization, Address Normalization, Referral/Campaign
 * - Communication: 3x2 Communication Grid, Custom Domain, AI Assistant Full Persona, Templates Matrix
 * - Integrations: Google, Meta (FB/IG/Threads/WA), ERP/Accounting, AI Providers, CRM, MCP, Webhooks, Video
 * - Legal: Separate KVKK & Privacy, Distance Sales, Terms, Cancellation/Refund, Cookies, Consent Forms
 * - Security: Users & Role Hierarchy, Bot/Honeypot, LDAP, Audit Logs, Data Import/Export
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
     * Resolve localized string via language helper, falling back to default.
     */
    protected function trans(string $key, string $default): string
    {
        $val = lang($key);
        return ($val !== '' && $val !== $key) ? $val : $default;
    }

    /**
     * Get the master schema definitions for all or a specific section.
     */
    public function get_schema(?string $section = null): array
    {
        $schema = [
            self::SECTION_BUSINESS => [
                'title' => $this->trans('settings_section_business_title', 'İşletme & Profil'),
                'icon' => 'fas fa-building',
                'tabs' => [
                    'general' => $this->trans('settings_tab_identity', 'Genel Bilgiler'),
                    'sector_modules' => 'Sektör & Aktif Modüller',
                    'hours' => $this->trans('settings_tab_working_plan', 'Çalışma Saatleri & Plan'),
                    'localization' => $this->trans('settings_tab_localization', 'Bölgesel Ayarlar & Para Birimi'),
                    'branding' => $this->trans('settings_tab_branding', 'Marka & Renkler'),
                    'payment' => $this->trans('settings_tab_payment', 'Ödeme Çeşitleri & Online Kapora'),
                ],
                'settings' => [
                    'company_name' => [
                        'type' => 'string',
                        'tab' => 'general',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_name', 'İşletme Adı'),
                        'description' => $this->trans('settings_field_company_name_desc', 'Randevu ve müşteri bildirimlerinde görüntülenecek işletme unvanı.'),
                        'default' => 'BooKi',
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'company_email' => [
                        'type' => 'email',
                        'tab' => 'general',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_email', 'İşletme E-posta'),
                        'description' => $this->trans('settings_field_company_email_desc', 'Müşterilerin yanıt verebileceği resmi işletme e-posta adresi.'),
                        'default' => '',
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'company_phone' => [
                        'type' => 'string',
                        'tab' => 'general',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_phone', 'İşletme Telefon Numarası'),
                        'description' => 'Müşteri iletişim ve randevu onaylarında gösterilecek resmi telefon.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'company_link' => [
                        'type' => 'url',
                        'tab' => 'general',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_link', 'Web Sitesi'),
                        'description' => $this->trans('settings_field_company_link_desc', 'İşletmenizin web sitesi bağlantısı.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Sektör & Modül Yönetimi (İşletme Profili)
                    'company_sector' => [
                        'type' => 'select',
                        'tab' => 'sector_modules',
                        'col' => 'col-12',
                        'label' => 'İşletme Faaliyet Alanı (Sektör)',
                        'description' => 'İşletmenizin sektörel kimliği. Menüler, formlar ve terminoloji bu seçime göre şekillenir.',
                        'default' => 'beauty_salon',
                        'options' => [
                            'beauty_salon' => '💅 Güzellik Salonu, Kuaför & Spa (Beauty Salon & Spa)',
                            'restaurant' => '🍽️ Restoran, Kafe & Gastronomi (Restaurant & Cafe)',
                            'doctor_clinic' => '🏥 Klinik, Muayenehane & Sağlık (Medical Clinic & Healthcare)',
                            'massage_spa' => '💆 Masaj Salonu & Terapi (Massage & Wellness)',
                            'gym' => '🏋️ Spor Salonu, Fitness & Stüdyo (Fitness & Sports)',
                            'automotive' => '🚗 Oto Servis & Detailing (Automotive Care)',
                            'universal' => '💼 Genel Hizmet & Danışmanlık (General Consulting)',
                        ],
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'module_adisyon_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Adisyon & Termal Fiş Modülü',
                        'description' => '58mm / 80mm fiş yazdırma, masa/koltuk adisyonu ve parçalı tahsilat.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'module_packages_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Seans & Paket Takip Sistemi',
                        'description' => 'Müşterilere çoklu seans paket satışı ve seans haklarından otomatik düşüm.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'module_commissions_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Personel Prim & Hakediş Modülü',
                        'description' => 'Randevu bitirme ekranında personel haklı / müşteri haklı komisyon hesaplama.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'module_consent_forms_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Sektörel Danışan Onam Formları',
                        'description' => 'Cilt tipi, alerji veya sağlık geçmişi onaylarının rezervasyonda zorunlu tutulması.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'module_deposit_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Online Kapora & Güvenli Provizyon',
                        'description' => 'No-show oranını sıfırlamak için randevu esnasında karttan kapora blokesi.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'module_rooms_tables_enabled' => [
                        'type' => 'bool',
                        'tab' => 'sector_modules',
                        'col' => 'col-md-6',
                        'label' => 'Masa / Oda / Koltuk Yönetimi',
                        'description' => 'Klinik odaları, kuaför koltukları veya restoran masalarının rezerve edilmesi.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'company_color' => [
                        'type' => 'color',
                        'tab' => 'branding',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_color', 'Marka Ana Rengi'),
                        'description' => $this->trans('settings_field_company_color_desc', 'Randevu portalında ve butonlarda kullanılacak kurumsal renk (Hex kodu).'),
                        'default' => '#35A768',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'company_logo' => [
                        'type' => 'string',
                        'tab' => 'branding',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_logo', 'Logo URL'),
                        'description' => $this->trans('settings_field_company_logo_desc', 'Müşteri randevu ekranında gösterilecek logo görsel URL.'),
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'white_label_enabled' => [
                        'type' => 'bool',
                        'tab' => 'branding',
                        'col' => 'col-12',
                        'label' => $this->trans('settings_field_white_label_enabled', 'White Label (BooKi İmzası Gizlensin)'),
                        'description' => $this->trans('settings_field_white_label_enabled_desc', 'Rezervasyon ve müşteri sayfalarındaki altyapı imzalarını gizler.'),
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'default_timezone' => [
                        'type' => 'select',
                        'tab' => 'localization',
                        'col' => 'col-md-4',
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
                        'col' => 'col-md-4',
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
                        'col' => 'col-md-4',
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
                        'col' => 'col-md-6',
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
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_company_currency', 'Para Birimi Sembolü / Kodu'),
                        'description' => $this->trans('settings_field_company_currency_desc', 'Örnek: TL, $, €, TRY'),
                        'default' => 'TL',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // 3x2 Payment Methods
                    'payment_method_cash' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '1. Nakit Ödeme',
                        'description' => 'Müşterilerden elden nakit tahsilatı kabul et.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_iban' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '2. IBAN / Havale',
                        'description' => 'Banka hesabına havale/EFT ile ödeme kabul et.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_card' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '3. Kredi Kartı / Fiziki POS',
                        'description' => 'İşletmedeki fiziki POS cihazıyla kart tahsilatı.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_installment' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '4. Elden Taksitli',
                        'description' => 'Paket ve seanslar için elden senetli/taksitli tahsilat.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_own_pos' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '5. Online Ödeme (Kendi POS\'um)',
                        'description' => 'İşletmenizin kendi sanal POS entegrasyonu (İyzico/Paytr vb.).',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_method_booki_pos' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-4',
                        'label' => '6. Online Ödeme (BooKi POS %20 komisyon)',
                        'description' => 'Sanal POS sözleşmesi olmadan BooKi ortak altyapısıyla tahsilat.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Online Kapora & Provizyon Ayarları
                    'payment_deposit_required' => [
                        'type' => 'bool',
                        'tab' => 'payment',
                        'col' => 'col-md-6',
                        'label' => 'Online Kapora / Ön Ödeme Şartı',
                        'description' => 'Randevu onaylanmadan önce müşterinin kartından bloke veya kapora tahsil edilir.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'deposit_commission_rate' => [
                        'type' => 'select',
                        'tab' => 'payment',
                        'col' => 'col-md-6',
                        'label' => 'Uygulanacak Komisyon Modeli',
                        'description' => 'BooKi POS ile birlikte seçilirse %5, sadece kapora blokesi modunda %20 komisyon kesilir.',
                        'default' => 'standalone_20',
                        'options' => [
                            'standalone_20' => 'Yalnızca Kapora Sistemi (%20 BooKi Komisyonu)',
                            'integrated_5' => 'Online Ödeme (BooKi POS) ile Birlikte (%5 Komisyon)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_deposit_type' => [
                        'type' => 'select',
                        'tab' => 'payment',
                        'col' => 'col-md-6',
                        'label' => 'Kapora Tahsilat Şekli',
                        'description' => 'Yüzdelik kapora veya sabit TL tutarı.',
                        'default' => 'percentage',
                        'options' => [
                            'percentage' => 'Yüzde Oranı (%)',
                            'fixed' => 'Sabit Tutar (TL)',
                            'full' => 'Hizmet Bedelinin Tamamı (Ön Provizyon Bloke)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'payment_deposit_amount' => [
                        'type' => 'int',
                        'tab' => 'payment',
                        'col' => 'col-md-6',
                        'label' => 'Kapora Oranı (%) veya Sabit Tutarı (TL)',
                        'description' => 'Örnek: %20 için 20, 250 TL için 250 yazınız.',
                        'default' => 20,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_BOOKING => [
                'title' => $this->trans('settings_section_booking_title', 'Randevu Kuralları'),
                'icon' => 'fas fa-calendar-check',
                'tabs' => [
                    'rules' => $this->trans('settings_tab_rules', 'Rezervasyon Kuralları'),
                    'timeouts' => $this->trans('settings_tab_timeouts', 'Süreler & Kısıtlamalar'),
                    'calendar' => $this->trans('settings_tab_calendar', 'Takvim Davranışı'),
                    'form_customization' => 'Müşteri Formu & Sektörel Alanlar',
                    'address_system' => 'Detaylı Adres Sistemi',
                    'referral_tracking' => 'Referans & Kampanya Takibi',
                ],
                'settings' => [
                    'book_advance_timeout' => [
                        'type' => 'int',
                        'tab' => 'timeouts',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_book_advance_timeout', 'En Erken Randevu Alma Süresi (Dakika)'),
                        'description' => $this->trans('settings_field_book_advance_timeout_desc', 'Müşterilerin randevu saatinden en az kaç dakika önce rezervasyon yapabileceğini belirler.'),
                        'default' => 60,
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'cancellation_timeout' => [
                        'type' => 'int',
                        'tab' => 'timeouts',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_cancellation_timeout', 'Randevu İptal Limiti (Dakika)'),
                        'description' => $this->trans('settings_field_cancellation_timeout_desc', 'Müşterinin randevudan kaç dakika öncesine kadar kendi kendine iptal edebileceği.'),
                        'default' => 120,
                        'required' => true,
                        'is_secret' => false,
                    ],
                    'allow_customer_cancellation' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_allow_customer_cancellation', 'Müşteri Randevusunu İptal Edebilsin'),
                        'description' => 'Müşteri onay bildirimindeki bağlantıdan randevusunu iptal edebilir.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'require_phone_number' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_require_phone_number', 'Telefon Numarası Zorunlu'),
                        'description' => 'Rezervasyon formu doldurulurken telefon alanını zorunlu tut.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_any_provider' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_display_any_provider', '"İlk Müsait Personel" Seçeneği'),
                        'description' => 'Müşterinin belirli bir personel yerine en erken müsait personeli seçebilmesini sağlar.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'multiple_appointments' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_multiple_appointments', 'Sepet Mantığıyla Çoklu Hizmet Seçimi'),
                        'description' => 'Müşterinin tek seferde birden fazla seans / hizmet seçmesine izin verir.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'display_already_booked_times' => [
                        'type' => 'bool',
                        'tab' => 'calendar',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_display_already_booked_times', 'Dolu Saatleri Pasif Olarak Göster'),
                        'description' => 'Müşteri takviminde dolu slotları gri renkli olarak listeler.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'require_captcha' => [
                        'type' => 'bool',
                        'tab' => 'rules',
                        'col' => 'col-md-6',
                        'label' => 'Rezervasyon Formunda Bot Koruması (Captcha)',
                        'description' => 'Spam rezervasyonları engellemek için doğrulama ekler.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Sektörel Form Özelleştirme
                    'booking_sector_preset' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-12',
                        'label' => 'Sektörel Hazır Alan Şablonu',
                        'description' => 'İşletmenizin faaliyet alanına göre rezervasyon formuna sektöre özel sorular ekler.',
                        'default' => 'beauty',
                        'options' => [
                            'beauty' => 'Güzellik, Kuaför & Spa (Cilt Tipi, Alerji, Önceki İşlem Geçmişi)',
                            'clinic' => 'Klinik, Sağlık & Muayenehane (Geçmiş Tetkikler, Muayene Tarihi, İlaç & Alerji)',
                            'restaurant' => 'Restoran & Cafe (Masa Tercihi, Gluten/Alerjen, Bekleme Süresi, Çocuk Sandalyesi)',
                            'sports' => 'Spor Salonu & Halı Saha (Oyuncu/Kişi Sayısı, Seviye, Ekipman Talebi)',
                            'automotive' => 'Oto Servis & Detailing (Araç Marka/Model, Plaka, Şasi No, Kilometre)',
                            'general' => 'Genel / Özel Alanlar',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_field_cilt_tipi' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-6',
                        'label' => 'Sektörel Alan 1 (Cilt Tipi / Tetkik Durumu / Masa Tercihi)',
                        'description' => 'Müşteriden sektöre özgü ana bilgiyi talep eder.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_field_alerji_hassasiyet' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-6',
                        'label' => 'Sektörel Alan 2 (Alerji / Hassasiyet / Diyet)',
                        'description' => 'Özel alerji, hassasiyet veya glütensiz beslenme gibi sağlık notlarını alır.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_field_onceki_islem' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-6',
                        'label' => 'Sektörel Alan 3 (Önceki İşlem / Kronik Rahatsızlık)',
                        'description' => 'Daha önce yaptırılan işlem ve düzenli kullanılan ilaç/tetkik geçmişi.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_field_kisi_sayisi' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-6',
                        'label' => 'Sektörel Alan 4 (Kişi / Katılımcı Sayısı)',
                        'description' => 'Halı saha, grup dersi veya restoran için katılımcı sayısı seçeneği.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // 5 Özel Custom Alan
                    'booking_custom_field_1_name' => [
                        'type' => 'string',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 1 Başlığı',
                        'description' => 'Formda görüntülenecek soru (Boş bırakırsanız gizlenir).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_1_type' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 1 Tipi',
                        'description' => 'Giriş kutusu türü.',
                        'default' => 'text',
                        'options' => ['text' => 'Kısa Metin', 'textarea' => 'Uzun Açıklama', 'number' => 'Sayı', 'select' => 'Seçim Kutusu', 'date' => 'Tarih', 'file' => 'Dosya / Görsel Yükleme'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_1_required' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 1 Zorunlu mu?',
                        'description' => 'Müşteri doldurmadan geçemesin.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_2_name' => [
                        'type' => 'string',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 2 Başlığı',
                        'description' => '2. Özel soru başlığı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_2_type' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 2 Tipi',
                        'description' => 'Giriş kutusu türü.',
                        'default' => 'text',
                        'options' => ['text' => 'Kısa Metin', 'textarea' => 'Uzun Açıklama', 'number' => 'Sayı', 'select' => 'Seçim Kutusu', 'date' => 'Tarih', 'file' => 'Dosya / Görsel Yükleme'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_2_required' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 2 Zorunlu mu?',
                        'description' => 'Müşteri doldurmadan geçemesin.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_3_name' => [
                        'type' => 'string',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 3 Başlığı',
                        'description' => '3. Özel soru başlığı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_3_type' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 3 Tipi',
                        'description' => 'Giriş kutusu türü.',
                        'default' => 'text',
                        'options' => ['text' => 'Kısa Metin', 'textarea' => 'Uzun Açıklama', 'number' => 'Sayı', 'select' => 'Seçim Kutusu', 'date' => 'Tarih', 'file' => 'Dosya / Görsel Yükleme'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_3_required' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 3 Zorunlu mu?',
                        'description' => 'Müşteri doldurmadan geçemesin.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_4_name' => [
                        'type' => 'string',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 4 Başlığı',
                        'description' => '4. Özel soru başlığı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_4_type' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 4 Tipi',
                        'description' => 'Giriş kutusu türü.',
                        'default' => 'text',
                        'options' => ['text' => 'Kısa Metin', 'textarea' => 'Uzun Açıklama', 'number' => 'Sayı', 'select' => 'Seçim Kutusu', 'date' => 'Tarih', 'file' => 'Dosya / Görsel Yükleme'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_4_required' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 4 Zorunlu mu?',
                        'description' => 'Müşteri doldurmadan geçemesin.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_5_name' => [
                        'type' => 'string',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 5 Başlığı',
                        'description' => '5. Özel soru başlığı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_5_type' => [
                        'type' => 'select',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 5 Tipi',
                        'description' => 'Giriş kutusu türü.',
                        'default' => 'text',
                        'options' => ['text' => 'Kısa Metin', 'textarea' => 'Uzun Açıklama', 'number' => 'Sayı', 'select' => 'Seçim Kutusu', 'date' => 'Tarih', 'file' => 'Dosya / Görsel Yükleme'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'booking_custom_field_5_required' => [
                        'type' => 'bool',
                        'tab' => 'form_customization',
                        'col' => 'col-md-4',
                        'label' => 'Özel Alan 5 Zorunlu mu?',
                        'description' => 'Müşteri doldurmadan geçemesin.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Detaylı Adres Sistemi (Normalizasyon)
                    'address_system_enabled' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-12',
                        'label' => 'Normalizasyonlu Detaylı Adres Modülü Aktif',
                        'description' => 'Müşteri kartında ve randevuda adresi Mahalle, Sokak, Dış Kapı No, İç Kapı No, İlçe, İl, Posta Kodu, Ülke olarak ayrı ayrı saklar.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_mahalle' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'Mahalle Alanı Gösterilsin',
                        'description' => 'Müşteri formunda Mahalle alanını etkinleştir.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_sokak' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'Cadde / Sokak Gösterilsin',
                        'description' => 'Cadde ve sokak detayını ayrı tut.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_bina_no' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'Dış Kapı / Bina No',
                        'description' => 'Bina numarasını ayrı al.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_daire_no' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'İç Kapı / Daire No',
                        'description' => 'Kat ve daire numarasını ayrı al.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_ilce' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'İlçe Alanı',
                        'description' => 'İlçe seçimi veya metni.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_il' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-4',
                        'label' => 'İl / Şehir Alanı',
                        'description' => 'Şehir seçimi.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_posta_kodu' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-6',
                        'label' => 'Posta Kodu',
                        'description' => '5 haneli posta kodu alanı.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'address_require_ulke' => [
                        'type' => 'bool',
                        'tab' => 'address_system',
                        'col' => 'col-md-6',
                        'label' => 'Ülke Alanı',
                        'description' => 'Ülke bilgisi (Varsayılan Türkiye).',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Referans & Kampanya Takibi
                    'referral_tracking_enabled' => [
                        'type' => 'bool',
                        'tab' => 'referral_tracking',
                        'col' => 'col-md-6',
                        'label' => 'Referans Olan Kişi Takibi Aktif',
                        'description' => 'Müşteriyi yönlendiren mevcut müşterinin adını ve telefonunu kaydeder.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'referral_code_required' => [
                        'type' => 'bool',
                        'tab' => 'referral_tracking',
                        'col' => 'col-md-6',
                        'label' => 'Referans / Davet Kodu Girişi',
                        'description' => 'Müşterinin davet kodu girmesini sağlar.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'campaign_source_tracking' => [
                        'type' => 'bool',
                        'tab' => 'referral_tracking',
                        'col' => 'col-12',
                        'label' => 'Kampanya & Reklam Kaynağı Takibi (Google / Meta UTM)',
                        'description' => 'Müşterinin hangi reklam kampanyasından geldiğini otomatik tespit edip müşteri profiline işler.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_COMMUNICATION => [
                'title' => $this->trans('settings_section_communication_title', 'İletişim & Bildirim'),
                'icon' => 'fas fa-paper-plane',
                'tabs' => [
                    'templates' => 'İletişim & Bildirim Şablonları Stüdyosu',
                    'custom_domain' => 'Özel Alan Adı (Custom Domain)',
                ],
                'settings' => [
                    // Custom Domain (Özel Alan Adı)
                    'custom_domain_name' => [
                        'type' => 'string',
                        'tab' => 'custom_domain',
                        'col' => 'col-md-8',
                        'label' => 'Özel Alan Adınız (Domain / Subdomain)',
                        'description' => 'Örnek: randevu.isletmeniz.com veya rezervasyon.guzellik.com',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'custom_domain_status' => [
                        'type' => 'select',
                        'tab' => 'custom_domain',
                        'col' => 'col-md-4',
                        'label' => 'Alan Adı Durumu',
                        'description' => 'DNS CNAME yönlendirme ve SSL durumu.',
                        'default' => 'disabled',
                        'options' => [
                            'disabled' => 'Pasif (Alt alan adı aktif)',
                            'pending' => 'DNS Doğrulaması Bekleniyor',
                            'verified' => 'Doğrulandı & SSL Sertifikası Aktif',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'custom_domain_cname_target' => [
                        'type' => 'string',
                        'tab' => 'custom_domain',
                        'col' => 'col-12',
                        'label' => 'DNS CNAME Hedef Sunucusu',
                        'description' => 'Alan adı barındırıcınızda (GoDaddy, Cloudflare, İsimTescil vb.) CNAME kaydını bu adrese yönlendiriniz.',
                        'default' => 'custom.bookiapp.kibusiness.co',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // İletişim Şablonları (4 Rol x 4 Senaryo x 3 Kanal)
                    'template_owner_notification' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'İşletme Sahibi Bildirim Şablonu',
                        'description' => 'Yeni randevu, ciro ve gün sonu özeti {isletme_adi}, {tarih}, {tutar}.',
                        'default' => "Sayın İşletme Yetkilisi, {randevu_tarihi} tarihinde {musteri_adi} tarafından {hizmet_adi} için {tutar} TL tutarında randevu oluşturuldu.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_manager_notification' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'Yönetici Bildirim Şablonu',
                        'description' => 'Şube operasyon bildirimleri.',
                        'default' => "Yönetici Özeti: {musteri_adi} - {hizmet_adi} ({personel_adi}). Durum: {durum}.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_staff_notification' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'Personel Bildirim Şablonu',
                        'description' => 'Personele atanan yeni randevu teyit mesajı.',
                        'default' => "Merhaba {personel_adi}, {randevu_tarihi} saat {randevu_saati} için {musteri_adi} adına {hizmet_adi} randevusu takviminize eklendi.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_confirmation' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Müşteri Randevu Onay (SMS & WhatsApp)',
                        'description' => 'Randevu oluşturulduğunda müşteriye anlık giden teyit mesajı.',
                        'default' => "Sayın {musteri_adi}, {isletme_adi} randevunuz {randevu_tarihi} {randevu_saati} için oluşturulmuştur. Hizmet: {hizmet_adi}. Randevu detayları ve konum için: {randevu_linki}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_confirmation_subject' => [
                        'type' => 'string',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'Müşteri Randevu Onay E-posta Konusu',
                        'description' => 'Giden e-postanın konu başlığı.',
                        'default' => "Randevunuz Onaylandı! - {isletme_adi}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_confirmation_email' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Müşteri Randevu Onay E-posta İçeriği',
                        'description' => 'HTML e-posta gövdesi.',
                        'default' => "Sayın {musteri_adi},\n\n{isletme_adi} bünyesindeki randevunuz başarıyla onaylanmıştır. Sizi salonumuzda ağırlamaktan mutluluk duyacağız.\n\nRandevu Tarihi: {randevu_tarihi} - {randevu_saati}\nHizmet: {hizmet_adi}\nUzman: {personel_adi}\nAdres: {isletme_adresi}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_reminder' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Müşteri Randevu Hatırlatma 24s (SMS & WhatsApp)',
                        'description' => 'Randevudan 24 saat önce otomatik giden erken hatırlatma.',
                        'default' => "Sayın {musteri_adi}, yarın saat {randevu_saati}'deki {hizmet_adi} randevunuzu hatırlatırız. Randevu saatinden 10 dk önce hazır bulunmanızı rica ederiz. Değişiklik için: {randevu_linki}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_reminder_subject' => [
                        'type' => 'string',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'Randevu Hatırlatma (24s) E-posta Konusu',
                        'description' => '24 saat önceki e-postanın konu satırı.',
                        'default' => "Randevu Hatırlatması (Yarın) - {isletme_adi}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_reminder_email' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Randevu Hatırlatma (24s) E-posta İçeriği',
                        'description' => 'HTML hatırlatma e-posta metni.',
                        'default' => "Sayın {musteri_adi},\n\nYarın saat {randevu_saati}'de {personel_adi} ile planlanan {hizmet_adi} randevunuzu hatırlatmak isteriz.\n\nAdresimiz: {isletme_adresi}\nİletişim: {isletme_telefonu}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_reminder_2h' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Müşteri Son Hatırlatma 2s (SMS & WhatsApp)',
                        'description' => 'Randevudan 2 saat önce giden acil hatırlatma ve canlı yol tarifi.',
                        'default' => "Sayın {musteri_adi}, saat {randevu_saati}'deki {hizmet_adi} randevunuza 2 saat kaldı. Adresimiz: {isletme_adresi}. İletişim: {isletme_telefonu}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_cancellation' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Randevu İptal / Değişiklik (SMS & WhatsApp)',
                        'description' => 'İptal veya erteleme durumunda iletilen mesaj.',
                        'default' => "Sayın {musteri_adi}, {randevu_tarihi} tarihli randevunuz isteğiniz üzerine iptal edilmiş / güncellenmiştir. Yeni randevu oluşturmak için: {randevu_linki}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_cancellation_subject' => [
                        'type' => 'string',
                        'tab' => 'templates',
                        'col' => 'col-md-6',
                        'label' => 'Randevu İptal E-posta Konusu',
                        'description' => 'İptal e-postasının konu satırı.',
                        'default' => "Randevunuz İptal Edildi - {isletme_adi}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'template_customer_followup' => [
                        'type' => 'text',
                        'tab' => 'templates',
                        'col' => 'col-12',
                        'label' => 'Müşteri Takip & Değerlendirme (Sonrası)',
                        'description' => 'Seans tamamlandıktan sonra giden teşekkür ve puanlama mesajı.',
                        'default' => "Sayın {musteri_adi}, bugün {isletme_adi}'ni tercih ettiğiniz için teşekkür ederiz! Hizmetimizi 1 dakikada puanlayın: {degerlendirme_linki}",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // E-posta SMTP
                    'email_sender_name' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_email_from_name', 'Gönderici Adı'),
                        'description' => 'Giden e-postalarda görünecek işletme adı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_sender_address' => [
                        'type' => 'email',
                        'tab' => 'email_smtp',
                        'col' => 'col-md-6',
                        'label' => $this->trans('settings_field_email_from_address', 'Gönderici E-posta Adresi'),
                        'description' => 'E-postaların çıkış adresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_host' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'col' => 'col-md-4',
                        'label' => 'SMTP Sunucusu',
                        'description' => 'Ör: smtp.gmail.com veya mail.sirketiniz.com',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_port' => [
                        'type' => 'int',
                        'tab' => 'email_smtp',
                        'col' => 'col-md-4',
                        'label' => 'SMTP Portu',
                        'description' => '587 (TLS) veya 465 (SSL).',
                        'default' => 587,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_username' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'col' => 'col-md-4',
                        'label' => 'SMTP Kullanıcı Adı',
                        'description' => 'SMTP kullanıcı hesabı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'email_smtp_password' => [
                        'type' => 'string',
                        'tab' => 'email_smtp',
                        'col' => 'col-12',
                        'label' => 'SMTP Parolası',
                        'description' => 'SMTP şifresi veya uygulama şifresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // SMS Ayarları
                    'sms_provider' => [
                        'type' => 'select',
                        'tab' => 'sms',
                        'col' => 'col-md-4',
                        'label' => 'SMS Servis Sağlayıcı',
                        'description' => 'Kullanılacak SMS başlığı operatörü.',
                        'default' => 'netgsm',
                        'options' => ['netgsm' => 'NetGSM', 'iletimerkezi' => 'İleti Merkezi', 'twillio' => 'Twilio SMS'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_sender_id' => [
                        'type' => 'string',
                        'tab' => 'sms',
                        'col' => 'col-md-4',
                        'label' => 'SMS Gönderici Başlığı (Originator)',
                        'description' => 'Onaylı kurumsal SMS başlığınız (ör: BOO-KI).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_api_key' => [
                        'type' => 'string',
                        'tab' => 'sms',
                        'col' => 'col-md-4',
                        'label' => 'SMS API Anahtarı',
                        'description' => 'Servis sağlayıcı erişim anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // WhatsApp Ayarları
                    'whatsapp_mode' => [
                        'type' => 'select',
                        'tab' => 'whatsapp',
                        'col' => 'col-md-6',
                        'label' => 'WhatsApp Gönderim Modu',
                        'description' => 'Resmi Meta Cloud API veya QR köprü (Baileys) arasında seçim yapın.',
                        'default' => 'official',
                        'options' => ['official' => 'Resmi (Cloud API)', 'unofficial' => 'QR Köprü (Baileys)'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'whatsapp_phone_number_id' => [
                        'type' => 'string',
                        'tab' => 'whatsapp',
                        'col' => 'col-md-6',
                        'label' => 'WhatsApp Phone Number ID',
                        'description' => 'Meta Developer portalındaki telefon numarası kimliği.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'whatsapp_access_token' => [
                        'type' => 'string',
                        'tab' => 'whatsapp',
                        'col' => 'col-12',
                        'label' => 'WhatsApp Permanent Access Token',
                        'description' => 'Meta Cloud API kalıcı erişim belirteci.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // Telegram Ayarları
                    'telegram_bot_token' => [
                        'type' => 'string',
                        'tab' => 'telegram',
                        'col' => 'col-md-6',
                        'label' => 'Telegram Bot Token',
                        'description' => 'BotFather üzerinden aldığınız bot API anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'telegram_chat_id' => [
                        'type' => 'string',
                        'tab' => 'telegram',
                        'col' => 'col-md-6',
                        'label' => 'Telegram Hedef Grup / Kanal ID',
                        'description' => 'Bildirimlerin düşeceği grup ID (ör: -10012345678).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Instagram DM
                    'instagram_account_id' => [
                        'type' => 'string',
                        'tab' => 'instagram_dm',
                        'col' => 'col-md-6',
                        'label' => 'Instagram Professional Hesap ID',
                        'description' => 'Meta Graph API Instagram İşletme Hesabı Kimliği.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'instagram_access_token' => [
                        'type' => 'string',
                        'tab' => 'instagram_dm',
                        'col' => 'col-md-6',
                        'label' => 'Instagram Graph API Token',
                        'description' => 'Instagram mesajlaşma yetkisine sahip erişim belirteci.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // Sesli Arama Santrali (1.250 TL/60 dk)
                    'voice_assistant_number' => [
                        'type' => 'string',
                        'tab' => 'voice_call',
                        'col' => 'col-md-6',
                        'label' => 'Tahsis Edilen Sanal Numara',
                        'description' => 'İşletmenize özel 0850 veya coğrafi santral numarası.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'voice_assistant_status' => [
                        'type' => 'select',
                        'tab' => 'voice_call',
                        'col' => 'col-md-6',
                        'label' => 'Sesli Santral Hat Durumu',
                        'description' => 'Aylık 1.250 TL / 60 dakika konuşma paketi durumu.',
                        'default' => 'not_requested',
                        'options' => [
                            'not_requested' => 'Talep Edilmedi',
                            'pending' => 'Numara Tahsisi ve Hat Onayı Bekleniyor',
                            'active' => 'Aktif (1.250 TL / 60 Dakika Ses Paketi Tanımlı)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_INTEGRATIONS => [
                'title' => $this->trans('settings_section_integrations_title', 'Entegrasyon & AI'),
                'icon' => 'fas fa-plug',
                'tabs' => [
                    'hub' => 'Tüm Entegrasyonlar (Katalog & Grid)',
                ],
                'settings' => [
                    // Google Workspace
                    'google_connected' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Google Hesabı Bağlantı Durumu',
                        'description' => 'Google Takvim, Meet ve Haritalar senkronizasyonu.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_sync_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Google Takvim İki Yönlü Eşitleme',
                        'description' => 'Personel randevuları Google Calendar ile anlık eşitlenir.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_sync_meet' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Google Meet Otomatik Link Üretimi',
                        'description' => 'Online görüşmeler için Meet linki ekler.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_sync_maps' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Google Haritalar Rezervasyon Butonu',
                        'description' => 'Google Haritalar profilinizde rezervasyon butonunu aktif eder.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_sync_sheets' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Google E-Tablolar & Drive Yedekleme',
                        'description' => 'Gün sonu randevu ve ciro verilerini Google Sheets tablosuna kaydeder.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_analytics_code' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Google Analytics Ölçüm Kimliği (GA4)',
                        'description' => 'Rezervasyon adımlarını izlemek için G-XXXXXXXXXX kodunuz.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Meta Suite
                    'meta_facebook_connected' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Facebook Sayfası Bağlantısı',
                        'description' => 'Facebook Lead Ads ve Sayfa randevu butonu.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'meta_instagram_connected' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Instagram Profili Bağlantısı',
                        'description' => 'Instagram Randevu Butonu ve Direct DM otomasyonu.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'meta_whatsapp_connected' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'WhatsApp Business Bağlantısı',
                        'description' => 'Resmi Meta Cloud API veya QR köprü bağlantısı.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'whatsapp_connection_type' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'WhatsApp Bağlantı Türü',
                        'description' => 'Resmi Cloud API (Tıkla Bağlan) veya QR Köprü.',
                        'default' => 'cloud_api',
                        'options' => [
                            'cloud_api' => 'Resmi Meta Cloud API (Tıkla Bağlan)',
                            'qr_baileys' => 'QR Köprü (Baileys)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'meta_pixel_id' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Meta Pixel Kimliği',
                        'description' => 'Facebook ve Instagram reklam dönüşümlerini izlemek için Pixel ID.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // İletişim Kanalları Bayrakları
                    'channel_email_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'E-posta (SMTP) Kanalı',
                        'description' => 'E-posta üzerinden onay, hatırlatma ve faturalandırma.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'channel_sms_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'SMS Kanalı',
                        'description' => 'SMS sağlayıcı API üzerinden SMS bildirimleri.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'channel_whatsapp_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'WhatsApp Kanalı',
                        'description' => 'Resmi Meta Cloud API veya QR köprü ile bildirim.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'channel_telegram_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Telegram Kanalı',
                        'description' => 'Personel veya müşteri bildirimleri için Telegram botu.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'channel_instagram_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Instagram DM Kanalı',
                        'description' => 'Instagram Direct Message üzerinden randevu ve destek.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'channel_call_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Sesli Arama Kanalı (1.250 TL/60 dk)',
                        'description' => '0850 sanal santral üzerinden sesli asistan aramaları.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // E-posta SMTP
                    'smtp_host' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'SMTP Sunucu Adresi',
                        'description' => 'Örnek: smtp.yandex.com veya mail.isletmeniz.com',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'smtp_port' => [
                        'type' => 'int',
                        'tab' => 'hub',
                        'col' => 'col-md-2',
                        'label' => 'SMTP Portu',
                        'description' => 'Genellikle 587 veya 465.',
                        'default' => 587,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'smtp_crypto' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Güvenlik Protokolü',
                        'description' => 'TLS veya SSL.',
                        'default' => 'tls',
                        'options' => ['tls' => 'TLS', 'ssl' => 'SSL'],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'smtp_user' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'SMTP Kullanıcı Adı / E-posta',
                        'description' => 'Giriş yapılacak kurumsal e-posta adresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'smtp_pass' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'SMTP Şifresi',
                        'description' => 'E-posta veya uygulama parolası.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'smtp_from_email' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Gönderen E-posta Adresi',
                        'description' => 'Müşterinin göreceği kimden (from) adresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'smtp_from_name' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Gönderen Başlığı / İsim',
                        'description' => 'Müşterinin gelen kutusunda göreceği işletme unvanı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Telegram
                    'telegram_bot_token' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Telegram Bot Token',
                        'description' => '@BotFather tarafından verilen API anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'telegram_bot_username' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Telegram Bot Kullanıcı Adı',
                        'description' => 'Örnek: @BookiRezervasyonBot',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // SMS Sağlayıcı
                    'sms_provider' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'SMS Sağlayıcı',
                        'description' => 'Kullanılan SMS geçidi.',
                        'default' => 'netgsm',
                        'options' => [
                            'netgsm' => 'Netgsm',
                            'iletimerkezi' => 'İletiMerkezi',
                            'twilio' => 'Twilio',
                            'mutlucell' => 'Mutlucell',
                            'verimor' => 'Verimor',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_username' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'SMS Kullanıcı Adı / API Key',
                        'description' => 'SMS sağlayıcı hesap kullanıcı adı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'sms_password' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'SMS Şifre / API Secret',
                        'description' => 'SMS sağlayıcı erişim şifresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'sms_title' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'SMS Gönderici Başlığı (Originator)',
                        'description' => 'Müşterilere gidecek SMS başlığı (Örn: BOORIGIN).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // AI Asistan & Santral Detayları
                    'ai_engine_type' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'AI Asistan Altyapı Motoru',
                        'description' => 'BooKi AI Platformu (dahili, kurulumsuz) veya BYOK (kendi anahtarınız).',
                        'default' => 'booki',
                        'options' => [
                            'booki' => 'BooKi AI Platformu (Önerilen - Entegre)',
                            'byok' => 'BYOK (Kendi API Anahtarını Kullan)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_byok_provider' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'BYOK AI Sağlayıcısı',
                        'description' => 'Kendi hesabınızdan bağlanacak model sağlayıcısı.',
                        'default' => 'openai',
                        'options' => [
                            'openai' => 'OpenAI (ChatGPT - GPT-4o / GPT-4o-mini)',
                            'anthropic' => 'Anthropic (Claude 3.5 Sonnet)',
                            'gemini' => 'Google Gemini (Gemini 1.5 Flash / Pro)',
                            'groq' => 'Groq (Llama 3.3 70B - Ultra Hızlı)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_byok_api_key' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'BYOK Özel API Anahtarı',
                        'description' => 'Seçilen sağlayıcıdan aldığınız gizli API anahtarı (sk-...).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'ai_byok_model' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Özel Model Kodu (Opsiyonel)',
                        'description' => 'Varsayılan dışı özel model (örn: gpt-4o, claude-3-5-sonnet-20241022).',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_conversations_used' => [
                        'type' => 'int',
                        'tab' => 'hub',
                        'col' => 'col-md-3',
                        'label' => 'Kullanılan Yazılı AI Görüşme Sayısı',
                        'description' => 'Bu fatura döneminde tüketilen AI oturumu.',
                        'default' => 142,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_conversations_total' => [
                        'type' => 'int',
                        'tab' => 'hub',
                        'col' => 'col-md-3',
                        'label' => 'Toplam Dahil Görüşme Kotası',
                        'description' => 'Paket kapsamındaki aylık görüşme hakkı (Standart: 1.000).',
                        'default' => 1000,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_voice_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Sesli Telefon Santralini Aktif Et (Opsiyonel Eklenti)',
                        'description' => '0850 sanal hat üzerinden gelen çağrıları yanıtlar ve randevu teyit aramaları yapar.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_voice_remaining_minutes' => [
                        'type' => 'int',
                        'tab' => 'hub',
                        'col' => 'col-md-3',
                        'label' => 'Kalan Konuşma Süresi (Dk)',
                        'description' => 'Mevcut paketten kalan görüşme dakikası.',
                        'default' => 48,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_voice_total_minutes' => [
                        'type' => 'int',
                        'tab' => 'hub',
                        'col' => 'col-md-3',
                        'label' => 'Toplam Paket Süresi (Dk)',
                        'description' => 'Aylık tanımlı görüşme kotası.',
                        'default' => 60,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_name' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Asistan Adı',
                        'description' => 'AI asistanın kendisini tanıtırken kullanacağı isim (örn: Leyla, Ece, Aras).',
                        'default' => 'BooKi Asistan',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_persona' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Asistan Karakteri / Persona',
                        'description' => 'Örnek: Güler yüzlü, ilgili ve tecrübeli randevu koordinatörü.',
                        'default' => 'Güler yüzlü, yardımsever ve uzman randevu koordinatörü',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_tone' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Konuşma Tonu & Üslup',
                        'description' => 'Asistanın müşterilerle konuşurken takınacağı ses tonu.',
                        'default' => 'friendly_professional',
                        'options' => [
                            'friendly_professional' => 'Samimi & Profesyonel (Tavsiye Edilen)',
                            'formal' => 'Resmi / Kurumsal',
                            'casual_friendly' => 'Rahat & Arkadaşça',
                            'warm_empathetic' => 'Sıcak & Empatik',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_channel_scope' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Asistanın Çalışacağı Kanallar',
                        'description' => 'Sesli aramalarda mı, yazılı mesajlarda mı yoksa her ikisinde mi aktif olsun?',
                        'default' => 'both',
                        'options' => [
                            'both' => 'Hem Sesli Çağrı Santrali Hem Mesajlaşma',
                            'voice_only' => 'Yalnızca Sesli Telefon Santrali',
                            'messaging_only' => 'Yalnızca Mesajlaşma (WhatsApp, Telegram, IG)',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_permissions' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Asistan Yetkileri',
                        'description' => 'Örnek: Randevu oluşturma, müsait saat sorgulama, iptal talebi alma, fiyat bilgisi verme.',
                        'default' => 'Randevu oluşturma, seans erteleme sorgulama, fiyat ve hizmet detayları aktarma',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_tasks' => [
                        'type' => 'text',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Asistan Görevleri (Her satıra bir görev)',
                        'description' => 'Asistanın yerine getirmesi gereken temel görev listesi.',
                        'default' => "Gelen müşteri çağrılarını ve mesajlarını 7/24 karşılamak.\nMüsait personel ve saatleri sorgulayıp randevu oluşturmak.\nRandevu öncesi hatırlatma ve teyit aramaları yapmak.\nMüşteriden gelen iptal ve erteleme taleplerini sisteme işlemek.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_prohibitions' => [
                        'type' => 'text',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Asistan Yasakları (Kesinlikle Yapılmayacaklar)',
                        'description' => 'Asistanın müşteriye asla söylememesi gereken kurallar.',
                        'default' => "Tıbbi teşhis veya kesin sonuç garantisi vermemek.\nYönetici onayı olmadan fiyatta pazarlık yapmamak veya ekstra indirim tanımlamamak.\nRakip salon ve klinikler hakkında yorum yapmamak.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_sales_rules' => [
                        'type' => 'text',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Asistan Satış & Çapraz Satış Kuralları',
                        'description' => 'Randevu esnasında paket ve ek hizmet önerme senaryoları.',
                        'default' => "Cilt bakımı alan müşteriye tamamlayıcı nem maskesi seansını hatırlat.\nİlk defa randevu alan müşterilere %10 tanışma indirimini belirt.\n5 seans ve üzeri paketlerin birim fiyat avantajını vurgula.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ai_assistant_knowledge_base' => [
                        'type' => 'text',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Asistan Bilgi Bankası (SSS, Adres Tarifi, Otopark vb.)',
                        'description' => 'İşletme hakkında müşterilerin sık sorduğu sorular ve yanıtları.',
                        'default' => "Konum: İşletmemiz merkez meydanda, metro durağının 50 metre ilerisindedir.\nOtopark: Müşterilerimize özel ücretsiz vale hizmetimiz mevcuttur.\nÖdeme: Nakit, kredi kartı, IBAN ve BooKi Güvenli Ödeme kabul edilmektedir.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Muhasebe & ERP
                    'erp_provider' => [
                        'type' => 'select',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Varsayılan Muhasebe / E-Fatura Yazılımı',
                        'description' => 'Adisyon ve randevularda sadece bağlı olan sistemler faturaya dönüştürülecektir.',
                        'default' => 'none',
                        'options' => [
                            'none' => 'Hiçbiri (Bağlı Yazılım Yok)',
                            'parasut' => 'Paraşüt E-Fatura',
                            'bizimhesap' => 'BizimHesap',
                            'kolaybi' => 'KolayBi\'',
                            'logo' => 'Logo İşbaşı',
                            'zirve' => 'Zirve ERP',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'parasut_client_id' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Paraşüt Client ID',
                        'description' => 'Paraşüt API erişim anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'parasut_client_secret' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Paraşüt Client Secret',
                        'description' => 'Paraşüt API gizli anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'bizimhesap_token' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'BizimHesap API Token',
                        'description' => 'BizimHesap entegrasyon anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'kolaybi_api_key' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'KolayBi API Anahtarı',
                        'description' => 'KolayBi API erişim anahtarı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // MCP & API Gateway
                    'agent_api_key' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Ajan / MCP Erişim Anahtarı (Agent API Key)',
                        'description' => 'Claude Desktop, Cursor IDE ve harici sesli AI asistanlarının kimlik doğrulaması.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'mcp_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Model Context Protocol (MCP) Sunucusu Açık',
                        'description' => 'Dış yapay zeka ajanlarının randevu müsaitliği sorgulamasına izin verir.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Webhooks
                    'webhook_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-12',
                        'label' => 'Giden Webhook Bildirimleri Aktif',
                        'description' => 'Yeni randevu, ödeme ve iptal olaylarını harici sunucunuza POST eder.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'tab' => 'hub',
                        'col' => 'col-md-8',
                        'label' => 'Hedef Webhook URL',
                        'description' => 'JSON payload gönderilecek HTTPS adresi.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'webhook_secret' => [
                        'type' => 'string',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Webhook HMAC İmzası (Gizli Anahtar)',
                        'description' => 'Payload güvenliği için X-BooKi-Signature başlığında kullanılır.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    // Video Görüşme
                    'jitsi_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Jitsi Meet Online Görüşme',
                        'description' => 'Ücretsiz, şifresiz görüntülü oda linki üretir.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'google_meet_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Google Meet Entegrasyonu',
                        'description' => 'Google Calendar randevularına otomatik Meet linki ekler.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'zoom_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-4',
                        'label' => 'Zoom Meeting API',
                        'description' => 'Seanslar için Zoom toplantısı oluşturur.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Harici Takvim
                    'ical_export_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'iCal Dışa Aktarım Yayını',
                        'description' => 'Apple Calendar ve harici uygulamalar için canlı iCal beslemesi.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'outlook_sync_enabled' => [
                        'type' => 'bool',
                        'tab' => 'hub',
                        'col' => 'col-md-6',
                        'label' => 'Microsoft Outlook Takvim Eşitleme',
                        'description' => 'Outlook takvimi ile randevuları senkronize eder.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_LEGAL => [
                'title' => $this->trans('settings_section_legal_title', 'Hukuk & KVKK'),
                'icon' => 'fas fa-balance-scale',
                'tabs' => [
                    'kvkk' => 'KVKK & Aydınlatma Metni',
                    'privacy' => 'Gizlilik Politikası',
                    'distance_sales' => 'Mesafeli Satış Sözleşmesi',
                    'terms' => 'Kullanım Koşulları',
                    'cancellation_refund' => 'İptal & İade Politikaları',
                    'cookies' => 'Çerez Politikası & Banner',
                    'consent_forms' => 'Sektörel Dijital Onam Formları',
                ],
                'settings' => [
                    // KVKK
                    'display_kvkk' => [
                        'type' => 'bool',
                        'tab' => 'kvkk',
                        'col' => 'col-12',
                        'label' => 'KVKK Aydınlatma Metnini Randevuda Onaya Sun',
                        'description' => 'Rezervasyon formu tamamlanmadan önce KVKK onay kutusunu zorunlu tut.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'kvkk_content' => [
                        'type' => 'text',
                        'tab' => 'kvkk',
                        'col' => 'col-12',
                        'label' => 'KVKK Aydınlatma Metni İçeriği',
                        'description' => '6698 sayılı Kişisel Verilerin Korunması Kanunu uyarınca veri sorumlusu aydınlatma metni.',
                        'default' => "6698 sayılı Kişisel Verilerin Korunması Kanunu (\"KVKK\") uyarınca, işletmemiz tarafından sunulan randevu ve rezervasyon hizmetleri kapsamında işlenen ad, soyad, telefon, e-posta ve varsa sağlık/sektörel tercihleriniz, yalnızca randevunun planlanması, teyit bildirimlerinin iletilmesi ve yasal yükümlülüklerin ifası amacıyla sınırlı olarak işlenmektedir.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Gizlilik
                    'display_privacy_policy' => [
                        'type' => 'bool',
                        'tab' => 'privacy',
                        'col' => 'col-12',
                        'label' => 'Gizlilik Politikasını Rezervasyonda Göster',
                        'description' => 'Randevu formunun altına gizlilik politikası onay kutusu ekler.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'privacy_policy_content' => [
                        'type' => 'text',
                        'tab' => 'privacy',
                        'col' => 'col-12',
                        'label' => 'Gizlilik Politikası Metni',
                        'description' => 'Müşterilere sunulan gizlilik sözleşmesi içeriği (HTML veya düz metin).',
                        'default' => "İşletmemiz, müşteri mahremiyetine ve kişisel bilgilerin güvenliğine en üst düzeyde önem vermektedir. Tarafınızca paylaşılan iletişim ve ödeme bilgileri hiçbir koşulda üçüncü taraflara ticari amaçlarla devredilemez.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Mesafeli Satış
                    'display_distance_sales' => [
                        'type' => 'bool',
                        'tab' => 'distance_sales',
                        'col' => 'col-12',
                        'label' => 'Mesafeli Satış Sözleşmesini Göster (Ön Ödemeli / Kaporalı Rezervasyonlar)',
                        'description' => 'Online ödeme ve kaporalı hizmetlerde 6502 sayılı Tüketicinin Korunması Kanunu gereği zorunludur.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'distance_sales_content' => [
                        'type' => 'text',
                        'tab' => 'distance_sales',
                        'col' => 'col-12',
                        'label' => 'Mesafeli Satış Sözleşmesi Metni',
                        'description' => 'Hizmet satışı için mesafeli satış koşulları.',
                        'default' => "İşbu sözleşme, ALICI'nın SATICI'ya ait elektronik ortam üzerinden rezervasyonunu yaptığı hizmetin satışı ve ifası ile ilgili olarak 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve Mesafeli Sözleşmeler Yönetmeliği hükümleri gereğince tarafların hak ve yükümlülüklerini düzenler.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Kullanım Koşulları
                    'display_terms_and_conditions' => [
                        'type' => 'bool',
                        'tab' => 'terms',
                        'col' => 'col-12',
                        'label' => 'Kullanım Koşullarını Rezervasyonda Göster',
                        'description' => 'Rezervasyon tamamlanmadan önce genel şartların onayını zorunlu tut.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'terms_and_conditions_content' => [
                        'type' => 'text',
                        'tab' => 'terms',
                        'col' => 'col-12',
                        'label' => 'Kullanım Koşulları Metni',
                        'description' => 'Müşterilere sunulan genel kullanım şartları.',
                        'default' => "Randevu saatine 10 dakika kala hazır bulunulması rica olunur. Gecikme durumunda seans süresi kısalabilir veya sonraki randevuların aksamaması adına randevu iptal edilebilir.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // İptal & İade
                    'display_cancellation_refund' => [
                        'type' => 'bool',
                        'tab' => 'cancellation_refund',
                        'col' => 'col-12',
                        'label' => 'İptal & İade Politikasını Göster',
                        'description' => 'Randevu iptal koşulları ve kapora iadesi şartlarını müşteriye açıkça sunar.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'cancellation_refund_content' => [
                        'type' => 'text',
                        'tab' => 'cancellation_refund',
                        'col' => 'col-12',
                        'label' => 'İptal & İade Politikası Metni',
                        'description' => 'Kapora, seans iptal süresi ve no-show kuralları.',
                        'default' => "Randevunuzu en geç 24 saat öncesine kadar kesintisiz iptal edebilir veya erteleyebilirsiniz. 24 saatten daha kısa sürede yapılan iptallerde veya randevuya gelinmemesi (No-Show) durumunda alınan kapora bedeli iade edilmez.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Çerez Banner
                    'display_cookie_notice' => [
                        'type' => 'bool',
                        'tab' => 'cookies',
                        'col' => 'col-12',
                        'label' => 'Çerez İzin Bildirimi (Cookie Banner)',
                        'description' => 'Randevu sayfasına ilk girdiğinde çerez bilgilendirme çubuğunu açar.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'cookie_notice_content' => [
                        'type' => 'text',
                        'tab' => 'cookies',
                        'col' => 'col-12',
                        'label' => 'Çerez Bildirim Metni',
                        'description' => 'Çerez bildiriminde yer alacak metin.',
                        'default' => 'Sizlere daha iyi bir rezervasyon deneyimi sunabilmek için sitemizde zorunlu ve işlevsel çerezler kullanılmaktadır.',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // Sektörel Dijital Onam Formları
                    'consent_form_template_type' => [
                        'type' => 'select',
                        'tab' => 'consent_forms',
                        'col' => 'col-12',
                        'label' => 'Sektörel Onam Formu Şablonu',
                        'description' => 'İşlem öncesi müşteriden dijital imza / onay alınacak sektörel form.',
                        'default' => 'beauty_laser',
                        'options' => [
                            'beauty_laser' => 'Güzellik: Lazer Epilasyon & Cilt Bakımı Bilgilendirilmiş Onam Formu',
                            'medical_clinic' => 'Sağlık: Klinik Muayene & Girişimsel İşlem Aydınlatılmış Onamı',
                            'sports_fitness' => 'Spor: Egzersiz & Sağlık Beyanı Taahhütnamesi',
                            'general_consent' => 'Genel: Hizmet ve Uygulama Muvafakatnamesi',
                        ],
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'consent_form_content' => [
                        'type' => 'text',
                        'tab' => 'consent_forms',
                        'col' => 'col-12',
                        'label' => 'Dijital Onam Formu Metni',
                        'description' => 'Müşterinin işlem öncesi imzalayacağı veya SMS/Portal üzerinden onaylayacağı metin.',
                        'default' => "İşlem öncesinde tarafıma uygulanacak hizmetin içeriği, olası riskleri ve işlem sonrası dikkat edilmesi gereken hususlar hakkında detaylı bilgilendirme yapılmıştır. Kendi rızamla işleme onay veriyorum.",
                        'required' => false,
                        'is_secret' => false,
                    ],
                ],
            ],

            self::SECTION_SECURITY => [
                'title' => $this->trans('settings_section_security_title', 'Güvenlik & Erişim'),
                'icon' => 'fas fa-shield-alt',
                'tabs' => [
                    'users_roles' => 'Kullanıcılar & Rol Yetkilendirme',
                    'bot_protection' => 'Bot Koruması & Honeypot & Captcha',
                    'ldap' => 'LDAP / Active Directory',
                    'audit_logs' => 'Denetim & Sistem Logları',
                    'data_transfer' => 'Veri İçe / Dışa Aktarım (Import / Export)',
                ],
                'settings' => [
                    // Bot Koruması & Honeypot
                    'security_honeypot_enabled' => [
                        'type' => 'bool',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Honeypot Gizli Tuzak Bot Koruması',
                        'description' => 'Görünmez tuzak alanlar ile otomatik spam form gönderen botları anında engeller.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'security_bruteforce_protection' => [
                        'type' => 'bool',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Brute-force Şifre Deneme Engelleme',
                        'description' => '5 hatalı şifre denemesinde IP adresini 15 dakika askıya alır.',
                        'default' => 1,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'altcha_enabled' => [
                        'type' => 'bool',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Altcha Gizlilik Dostu PoW Captcha',
                        'description' => 'Google reCAPTCHA yerine çerezsiz, KVKK uyumlu Proof-of-Work bot engellemesi.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'altcha_hmac_key' => [
                        'type' => 'string',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Altcha HMAC Gizli Anahtarı',
                        'description' => 'Altcha challenge imzalamak için kullanılan 256-bit gizli anahtar.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => true,
                    ],
                    'security_session_timeout' => [
                        'type' => 'int',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Oturum Zaman Aşımı (Dakika)',
                        'description' => 'Hareketsizlik halinde otomatik güvenli çıkış süresi.',
                        'default' => 120,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'limit_customer_visibility' => [
                        'type' => 'bool',
                        'tab' => 'bot_protection',
                        'col' => 'col-md-6',
                        'label' => 'Personel Müşteri Telefon/E-postasını Göremesin',
                        'description' => 'Yöneticiler dışındaki personelin müşterilerin iletişim bilgilerini maskeler.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    // LDAP
                    'ldap_enabled' => [
                        'type' => 'bool',
                        'tab' => 'ldap',
                        'col' => 'col-12',
                        'label' => 'Kurumsal LDAP Kimlik Doğrulama',
                        'description' => 'Personelin kurumsal Active Directory / OpenLDAP parolası ile giriş yapmasını sağlar.',
                        'default' => 0,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_host' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'col' => 'col-md-6',
                        'label' => 'LDAP Sunucu Adresi',
                        'description' => 'Ör: ldap.sirket.com veya 192.168.1.100',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_port' => [
                        'type' => 'int',
                        'tab' => 'ldap',
                        'col' => 'col-md-6',
                        'label' => 'LDAP Portu',
                        'description' => 'Standart LDAP 389, LDAPS (SSL) 636.',
                        'default' => 389,
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_dn' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'col' => 'col-md-6',
                        'label' => 'Base DN',
                        'description' => 'Ör: ou=users,dc=sirket,dc=com',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_user' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'col' => 'col-md-6',
                        'label' => 'Admin DN / Service Account',
                        'description' => 'Arama yapacak yetkili LDAP kullanıcısı.',
                        'default' => '',
                        'required' => false,
                        'is_secret' => false,
                    ],
                    'ldap_password' => [
                        'type' => 'string',
                        'tab' => 'ldap',
                        'col' => 'col-12',
                        'label' => 'LDAP Parolası',
                        'description' => 'Yetkili servis hesabı parolası.',
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
