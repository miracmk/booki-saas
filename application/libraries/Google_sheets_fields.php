<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Ki Reservation - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Salon Flora customization (2026-08-25) - the exportable field catalog for each Google Sheets sync
 * module. Single source of truth used by BOTH the field-mapping UI (Google_integrations.php) and the row
 * builder (Google_sheets_writer.php) - and, for the 'appointments' module, IS the same catalog
 * Reports::export_csv() uses (Reports::field_catalog() delegates here), so CSV export and Sheets sync
 * can never drift apart on what a field means.
 *
 * @package Libraries
 */
class Google_sheets_fields
{
    public const MODULES = [
        'appointments' => 'Seanslar (Randevular)',
        'customers' => 'Müşteriler',
        'providers' => 'Hizmet Sağlayanlar',
    ];

    /**
     * @param string $module One of MODULES' keys.
     *
     * @return array<string, array{label: string, group: string, pii: bool}>
     */
    public static function catalog(string $module): array
    {
        return match ($module) {
            'appointments' => [
                'appointment_id' => ['label' => 'Randevu ID', 'group' => 'Randevu', 'pii' => false],
                'created_at' => ['label' => 'Oluşturulma Tarihi', 'group' => 'Randevu', 'pii' => false],
                'date' => ['label' => 'Tarih', 'group' => 'Randevu', 'pii' => false],
                'time' => ['label' => 'Başlangıç Saati', 'group' => 'Randevu', 'pii' => false],
                'end_time' => ['label' => 'Bitiş Saati', 'group' => 'Randevu', 'pii' => false],
                'actual_start' => ['label' => 'Gerçek Giriş', 'group' => 'Randevu', 'pii' => false],
                'actual_end' => ['label' => 'Gerçek Çıkış', 'group' => 'Randevu', 'pii' => false],
                'status' => ['label' => 'Durum', 'group' => 'Randevu', 'pii' => false],
                'notes' => ['label' => 'Notlar', 'group' => 'Randevu', 'pii' => false],
                'location' => ['label' => 'Konum', 'group' => 'Randevu', 'pii' => false],
                'customer_name' => ['label' => 'Müşteri Adı', 'group' => 'Müşteri', 'pii' => false],
                'customer_phone' => ['label' => 'Müşteri Telefonu', 'group' => 'Müşteri', 'pii' => true],
                'customer_email' => ['label' => 'Müşteri E-postası', 'group' => 'Müşteri', 'pii' => true],
                'customer_address' => ['label' => 'Müşteri Adresi', 'group' => 'Müşteri', 'pii' => true],
                'provider_name' => ['label' => 'Terapist Adı', 'group' => 'Terapist', 'pii' => false],
                'provider_email' => ['label' => 'Terapist E-postası', 'group' => 'Terapist', 'pii' => false],
                'service_name' => ['label' => 'Hizmet', 'group' => 'Hizmet', 'pii' => false],
                'service_list_price' => ['label' => 'Liste Fiyatı', 'group' => 'Hizmet', 'pii' => false],
                'service_planned_minutes' => ['label' => 'Planlanan Süre (dk)', 'group' => 'Hizmet', 'pii' => false],
                'effective_minutes' => ['label' => 'Hesaplanan Süre (dk)', 'group' => 'Hesaplanan', 'pii' => false],
                'effective_price' => ['label' => 'Hesaplanan Fiyat', 'group' => 'Hesaplanan', 'pii' => false],
                'hourly_rate' => ['label' => 'Saatlik Ücret', 'group' => 'Hesaplanan', 'pii' => false],
                'payout' => ['label' => 'Terapist Hakedişi', 'group' => 'Hesaplanan', 'pii' => false],
                'station_name' => ['label' => 'İstasyon', 'group' => 'İstasyon', 'pii' => false],
                'payment_status' => ['label' => 'Ödeme Durumu', 'group' => 'Ödeme', 'pii' => false],
                'payment_method' => ['label' => 'Ödeme Yöntemi', 'group' => 'Ödeme', 'pii' => false],
                'payment_amount' => ['label' => 'Ödenen Tutar', 'group' => 'Ödeme', 'pii' => false],
                'payment_balance' => ['label' => 'Kalan Bakiye', 'group' => 'Ödeme', 'pii' => false],
                'is_invoiced' => ['label' => 'Faturalandı mı', 'group' => 'Ödeme', 'pii' => false],
                'deviation_type' => ['label' => 'Sapma Tipi', 'group' => 'Sapma', 'pii' => false],
                'deviation_minutes' => ['label' => 'Sapma (dk)', 'group' => 'Sapma', 'pii' => false],
                'early_exit_justification' => ['label' => 'Erken Çıkış Haklı mı', 'group' => 'Sapma', 'pii' => false],
                'early_exit_reason' => ['label' => 'Erken Çıkış Sebebi', 'group' => 'Sapma', 'pii' => false],
                'conflict_override' => ['label' => 'Çakışma Onayı', 'group' => 'Çakışma', 'pii' => false],
            ],
            'customers' => [
                'id' => ['label' => 'Müşteri ID', 'group' => 'Kimlik', 'pii' => false],
                'first_name' => ['label' => 'Ad', 'group' => 'Kimlik', 'pii' => false],
                'last_name' => ['label' => 'Soyad', 'group' => 'Kimlik', 'pii' => false],
                'phone_number' => ['label' => 'Telefon', 'group' => 'İletişim', 'pii' => true],
                'email' => ['label' => 'E-posta', 'group' => 'İletişim', 'pii' => true],
                'address' => ['label' => 'Adres', 'group' => 'İletişim', 'pii' => true],
                'city' => ['label' => 'İlçe', 'group' => 'İletişim', 'pii' => false],
                'state' => ['label' => 'Semt/Mahalle', 'group' => 'İletişim', 'pii' => true],
                'whatsapp' => ['label' => 'WhatsApp', 'group' => 'Sosyal Kanallar', 'pii' => true],
                'telegram' => ['label' => 'Telegram', 'group' => 'Sosyal Kanallar', 'pii' => true],
                'instagram' => ['label' => 'Instagram', 'group' => 'Sosyal Kanallar', 'pii' => true],
                'last_contact_channel' => ['label' => 'Son İletişim Kanalı', 'group' => 'Sosyal Kanallar', 'pii' => false],
                'notes' => ['label' => 'Notlar', 'group' => 'Diğer', 'pii' => false],
                'created_at' => ['label' => 'Kayıt Tarihi', 'group' => 'Diğer', 'pii' => false],
            ],
            'providers' => [
                'id' => ['label' => 'Terapist ID', 'group' => 'Kimlik', 'pii' => false],
                'first_name' => ['label' => 'Ad', 'group' => 'Kimlik', 'pii' => false],
                'last_name' => ['label' => 'Soyad', 'group' => 'Kimlik', 'pii' => false],
                'phone_number' => ['label' => 'Telefon', 'group' => 'İletişim', 'pii' => true],
                'email' => ['label' => 'E-posta', 'group' => 'İletişim', 'pii' => false],
                'commission_type' => ['label' => 'Komisyon Tipi', 'group' => 'Komisyon', 'pii' => false],
                'commission_value' => ['label' => 'Komisyon Değeri', 'group' => 'Komisyon', 'pii' => false],
                'commission_overtime_bonus' => ['label' => 'Mesai Bonusu', 'group' => 'Komisyon', 'pii' => false],
                'telegram_linked' => ['label' => 'Telegram Bağlı mı', 'group' => 'Diğer', 'pii' => false],
                'created_at' => ['label' => 'Kayıt Tarihi', 'group' => 'Diğer', 'pii' => false],
            ],
            default => [],
        };
    }
}
