<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Platform AI Responder library.
 *
 * Handles Superadmin CRM interactive AI chat and platform-level WhatsApp
 * inbound messages (leads inquiring about BooKi SaaS, pricing, demo, etc.)
 * using the unified Ai_llm_gateway.
 *
 * @package Libraries
 */
class Platform_ai_responder
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('ai_llm_gateway');
        $this->CI->load->helper('setting');
    }

    /**
     * Respond to an inbound WhatsApp message sent to the platform's WhatsApp account.
     *
     * @param string $from Sender phone number
     * @param string $body Inbound message text
     * @return string Assistant reply text (or empty string if unable to reply)
     */
    public function respond_whatsapp(string $from, string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        $system_prompt = $this->build_platform_sales_prompt();

        $messages = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => "Gelen WhatsApp Mesajı (Kimden: {$from}):\n{$body}"],
        ];

        try {
            $response = $this->CI->ai_llm_gateway->chat($messages, [
                'temperature' => 0.4,
                'max_tokens' => 512,
            ]);

            if ($response && !empty($response['reply'])) {
                return trim((string) $response['reply']);
            }
        } catch (Throwable $e) {
            log_message('error', 'Platform_ai_responder::respond_whatsapp - ' . $e->getMessage());
        }

        return 'Merhaba! BooKi Akıllı Randevu & İşletme Yönetim Sistemi ile ilgilendiğiniz için teşekkür ederiz. Uzman ekibimiz en kısa sürede size dönüş yapacaktır. Dilerseniz hemen https://bookiapp.kibusiness.co adresinden 10 günlük ücretsiz demonuzu başlatabilirsiniz.';
    }

    /**
     * Superadmin CRM panel interactive AI chat.
     *
     * @param string $message User question/prompt
     * @param array $history Previous conversation history
     * @param int|null $lead_id Optional lead context ID
     * @return array Response structure
     */
    public function respond_chat(string $message, array $history = [], ?int $lead_id = null): array
    {
        $message = trim($message);
        if ($message === '') {
            return [
                'success' => false,
                'message' => 'Mesaj boş olamaz.',
            ];
        }

        $system_prompt = "Sen BooKi SaaS platformunun yapay zeka saha satış ve CRM asistanısın. Görevin süper yöneticilere işletme analizleri, lead yönetimi, WhatsApp satış stratejileri, soğuk arama tavsiyeleri ve platform operasyonlarında yardımcı olmaktır. Kibar, profesyonel, net ve Türkçe yanıt ver.";

        if ($lead_id !== null && $lead_id > 0) {
            try {
                if (isset($this->CI->leads_model)) {
                    $lead = $this->CI->leads_model->get_lead_by_id($lead_id);
                    if ($lead) {
                        $system_prompt .= "\n\nŞu anda üzerinde çalışılan Lead/İşletme Bilgileri:\n" .
                            "- İşletme Adı: " . ($lead['business_name'] ?? $lead['name'] ?? 'Bilinmiyor') . "\n" .
                            "- Sektör: " . ($lead['sector'] ?? 'Genel') . "\n" .
                            "- İlçe / Adres: " . ($lead['district'] ?? '') . ' ' . ($lead['address'] ?? '') . "\n" .
                            "- Telefon / WhatsApp: " . ($lead['whatsapp_number'] ?? $lead['phone'] ?? 'Yok') . "\n" .
                            "- Aşama / Öncelik: " . ($lead['stage'] ?? 'YENİ') . ' / ' . ($lead['priority'] ?? 'normal') . "\n" .
                            "- Notlar: " . ($lead['notes'] ?? 'Yok');
                    }
                }
            } catch (Throwable $e) {
                log_message('debug', 'Platform_ai_responder::respond_chat lead lookup error: ' . $e->getMessage());
            }
        }

        $messages = [['role' => 'system', 'content' => $system_prompt]];

        foreach ($history as $msg) {
            if (!empty($msg['content']) && !empty($msg['role'])) {
                $role = in_array($msg['role'], ['user', 'assistant'], true) ? $msg['role'] : 'user';
                $messages[] = [
                    'role' => $role,
                    'content' => (string) $msg['content'],
                ];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = $this->CI->ai_llm_gateway->chat($messages, [
                'temperature' => 0.5,
                'max_tokens' => 1024,
            ]);

            if ($response && !empty($response['reply'])) {
                return [
                    'success' => true,
                    'reply' => $response['reply'],
                    'provider' => $response['provider'] ?? 'ai',
                    'model' => $response['model'] ?? 'default',
                ];
            }
        } catch (Throwable $e) {
            log_message('error', 'Platform_ai_responder::respond_chat - ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'AI yanıt oluşturamadı: ' . $e->getMessage(),
            ];
        }

        return [
            'success' => false,
            'message' => 'AI sağlayıcılarından geçerli bir yanıt alınamadı. Lütfen API anahtarlarınızı kontrol edin.',
        ];
    }

    /**
     * Build the sales & pitch prompt for incoming platform WhatsApp messages.
     */
    private function build_platform_sales_prompt(): string
    {
        $custom_pitch = '';
        if (function_exists('master_setting')) {
            $custom_pitch = (string) (master_setting('gemini_sales_pitch_prompt') ?: '');
        }

        $base = "Sen BooKi Akıllı Randevu & Çok Kiracılı İşletme Yönetim Sistemi platformunun kurumsal WhatsApp satış asistanısın. " .
            "Potansiyel işletmeler (kuaförler, güzellik merkezleri, klinikler, spor salonları, danışmanlar vb.) sana mesaj atıyor. " .
            "Hedefin: Onları dinlemek, sorularını nazikçe yanıtlamak ve 10 günlük ücretsiz demo açmaya (https://bookiapp.kibusiness.co) veya demo sunumu planlamaya davet etmektir.\n" .
            "Öne Çıkan Özellikler:\n" .
            "- Otomatik WhatsApp hatırlatmaları ile randevu kayıplarını %80 azaltma\n" .
            "- Personel takvimi, prim hesaplama, kasa ve POS yönetimi\n" .
            "- Kendi alan adlarında (ör. salonflora.com veya salon.bookiapp.kibusiness.co) online randevu alma\n" .
            "- Yapay zeka müşteri asistanı entegrasyonu\n" .
            "Cevapların kısa, samimi, güven veren ve harekete geçirici olmalı.";

        if (!empty($custom_pitch)) {
            $base .= "\n\nYönetici Özel Satış Direktifleri:\n" . $custom_pitch;
        }

        return $base;
    }
}
