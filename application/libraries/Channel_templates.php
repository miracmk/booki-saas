<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi - Multi-Channel Notification & Message Template Service.
 *
 * Provides standardized, high-quality, beautifully formatted message templates
 * for Telegram, WhatsApp, and Instagram notifications with dynamic placeholders:
 *
 * Supported Placeholders:
 * - {company_name}     : İşletme / Marka Adı (örn: Salon Flora)
 * - {customer_name}    : Müşteri Adı Soyadı (örn: Selin Yılmaz)
 * - {service_name}     : Hizmet Adı (örn: Aromaterapi Masajı)
 * - {start_datetime}   : Başlangıç Tarih ve Saati (örn: 20 Eylül 2026, 14:00)
 * - {end_datetime}     : Bitiş Tarih ve Saati (örn: 20 Eylül 2026, 15:00)
 * - {provider_name}    : Hizmet Veren Uzman / Personel Adı
 * - {company_address}  : İşletme / Salon Adresi
 * - {company_phone}    : İşletme Telefon Numarası
 * - {booking_url}      : Randevu Yönetim ve İnceleme Bağlantısı
 * - {notes}            : Randevu Notu veya Açıklama
 *
 * @package Libraries
 */
class Channel_templates
{
    /**
     * Default message template definitions.
     */
    public const TEMPLATES = [
        'appointment_pending' => <<<TPL
🌸 *{company_name}* — Randevu Talebiniz Alındı

Sayın *{customer_name}*,
Randevu talebiniz başarıyla alınmış olup yetkili onayına iletilmiştir:

📌 *Hizmet:* {service_name}
🗓 *Tarih & Saat:* {start_datetime}
👤 *Uzman:* {provider_name}
📍 *Adres:* {company_address}

Randevunuz yönetici onayından sonra kesinleşecek ve size bilgi verilecektir.
İletişim: {company_phone}
Online İşlemler: {booking_url}
TPL,

        'appointment_approved' => <<<TPL
✅ *{company_name}* — Randevunuz Onaylandı!

Sayın *{customer_name}*,
Randevunuz onaylanarak takvimimize kaydedilmiştir. Sizi ağırlamaktan mutluluk duyacağız:

📌 *Hizmet:* {service_name}
🗓 *Tarih & Saat:* {start_datetime} (Bitiş: {end_datetime})
👤 *Uzman:* {provider_name}
📍 *Adres:* {company_address}
📞 *Telefon:* {company_phone}

Randevu detaylarınızı incelemek veya değişiklik yapmak için:
🔗 {booking_url}

Keyifli ve sağlıklı günler dileriz! ✨
TPL,

        'appointment_rescheduled' => <<<TPL
🗓 *{company_name}* — Randevu Saatiniz Güncellendi

Sayın *{customer_name}*,
Randevunuzun yeni tarih ve saat bilgisi onaylanmıştır:

📌 *Hizmet:* {service_name}
✨ *Yeni Tarih & Saat:* {start_datetime} (Bitiş: {end_datetime})
👤 *Uzman:* {provider_name}
📍 *Adres:* {company_address}

Detaylar için: {booking_url}
Herhangi bir sorunuz olursa bize {company_phone} numarasından ulaşabilirsiniz.
TPL,

        'appointment_cancelled' => <<<TPL
❌ *{company_name}* — Randevu İptali

Sayın *{customer_name}*,
*{start_datetime}* tarihindeki *{service_name}* randevunuz talebiniz üzerine iptal edilmiştir.

Yeni bir randevu oluşturmak isterseniz bize dilediğiniz an buradan yazabilir veya online bağlantımızı kullanabilirsiniz:
🔗 {booking_url}

Teşekkür eder, iyi günler dileriz.
TPL,

        'appointment_reminder' => <<<TPL
🔔 *{company_name}* — Randevu Hatırlatması

Sayın *{customer_name}*,
Yaklaşan randevunuzu hatırlatmak isteriz:

📌 *Hizmet:* {service_name}
🗓 *Tarih & Saat:* {start_datetime}
👤 *Uzman:* {provider_name}
📍 *Adres:* {company_address}
📞 *Telefon:* {company_phone}

Randevunuza vaktinde gelmenizi rica eder, keyifli bir deneyim dileriz! 🌸
TPL,

        'customer_channel_linked' => <<<TPL
🤝 *{company_name}* — Hesabınız Tanındı!

Merhaba *{customer_name}*,
İletişim bilgileriniz doğrulandı ve bu mesajlaşma kanalınız BooKi profilinizle başarıyla eşleştirildi. 

Artık buradan doğrudan:
• Yaklaşan randevularınızı sorgulayabilir,
• Yeni randevu talebinde bulunabilir,
• Randevularınızı güncelleyebilir veya iptal edebilirsiniz.

Size nasıl yardımcı olabilirim? 😊
TPL,
    ];

    /**
     * Render template with provided data dictionary.
     *
     * @param string $template_key
     * @param array $data
     * @param string|null $custom_template_override
     * @return string
     */
    public function render(string $template_key, array $data, ?string $custom_template_override = null): string
    {
        $template = $custom_template_override ?: (self::TEMPLATES[$template_key] ?? '');
        if ($template === '') {
            return '';
        }

        $replacements = [
            '{company_name}' => $data['company_name'] ?? (setting('company_name') ?: 'BooKi İşletmesi'),
            '{customer_name}' => $data['customer_name'] ?? 'Değerli Misafirimiz',
            '{service_name}' => $data['service_name'] ?? 'Hizmet',
            '{start_datetime}' => $this->format_datetime($data['start_datetime'] ?? null),
            '{end_datetime}' => $this->format_datetime($data['end_datetime'] ?? null, false),
            '{provider_name}' => $data['provider_name'] ?? 'Belirtilmedi',
            '{company_address}' => $data['company_address'] ?? (setting('company_address') ?: 'İşletme Adresi'),
            '{company_phone}' => $data['company_phone'] ?? (setting('company_phone') ?: ''),
            '{booking_url}' => $data['booking_url'] ?? site_url('booking'),
            '{notes}' => $data['notes'] ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Format SQL datetime into readable Turkish string.
     */
    private function format_datetime(?string $datetime, bool $include_date = true): string
    {
        if (empty($datetime)) {
            return '-';
        }

        $timestamp = strtotime($datetime);
        if (!$timestamp) {
            return (string) $datetime;
        }

        $months = [
            1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
            5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
            9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
        ];

        $day = date('j', $timestamp);
        $month = $months[(int) date('n', $timestamp)] ?? date('m', $timestamp);
        $year = date('Y', $timestamp);
        $time = date('H:i', $timestamp);

        if (!$include_date) {
            return $time;
        }

        return "{$day} {$month} {$year}, {$time}";
    }
}

