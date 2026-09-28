<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi - Multi-Channel AI Assistant Responder Library.
 *
 * Handles inbound customer messages from WhatsApp, Telegram, and Instagram.
 * Provides automated AI responses using read-only customer & business context
 * powered by the Universal Multi-Provider AI LLM Gateway (Google AI Studio, Groq, OpenRouter, OpenAI, Anthropic).
 *
 * RECOGNITION & SECURITY INVARIANTS:
 * 1. Customer Recognition:
 *    - WhatsApp: Matched directly by phone number / wa_id.
 *    - Telegram / Instagram: Matched by telegram_chat_id / instagram_user_id if already linked.
 *      If not linked yet, the AI politely asks for their phone number and links the channel
 *      via `link_customer_channel`, greeting them by name for all future conversations.
 * 2. Mutation Safety:
 *    - Inbound messages NEVER directly alter appointments or core business data.
 *    - New booking, cancellation, rescheduling, or profile update requests are queued as
 *      proposals into `ai_agent_pending_changes` and require human admin approval.
 *
 * @package Libraries
 */
class Ai_channel_responder
{
    private const MAX_TOOL_ITERATIONS = 4;

    private const ALLOWED_UPDATE_FIELDS = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'notes'];

    private const TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'link_customer_channel',
                'description' => 'Müşteri telefon numarasını paylaştığında çağrılır. Veritabanındaki müşteri profilini bularak bu mesajlaşma hesabını (Telegram/Instagram/WhatsApp) müşteriye bağlar.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'phone_number' => ['type' => 'string', 'description' => 'Müşterinin paylaştığı telefon numarası (örn: 05062505562 veya 506 250 55 62).'],
                    ],
                    'required' => ['phone_number'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_providers',
                'description' => 'İşletmedeki uzmanları / terapistleri / çalışan personeli listeler. SADECE müşteri açıkça uzmanları sorduğunda (örn: "kimler var?", "uzmanlarınız kimler?", "masajı kim yapacak?") çağrılmalıdır. Müşteri sormadıkça çağrılmamalıdır.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_services',
                'description' => 'İşletmenin sunduğu tüm aktif hizmetleri (ad, süre dakika, fiyat) listeler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'check_availability',
                'description' => 'İşletmenin veya belirli bir uzmanın/terapistin gerçek müsaitlik durumunu, çalışma saatlerini ve boş randevu slotlarını kontrol eder. Müşteri çalışma gün/saatlerini sorduğunda ("açık mısınız?", "çalışıyor musunuz?"), randevu talep ettiğinde veya bir terapistin müsaitliğini sorduğunda MUTLAKA ilk olarak bu aracı çağır.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'Kontrol edilecek tarih (YYYY-MM-DD formatında, örn: 2026-09-19). Belirtilmezse güncel tarih kullanılır.'],
                        'service_name' => ['type' => 'string', 'description' => 'İstenen hizmetin adı (örn: Klasik Masaj, Cilt Bakımı).'],
                        'service_id' => ['type' => 'integer', 'description' => 'Opsiyonel hizmet ID numarası.'],
                        'provider_name' => ['type' => 'string', 'description' => 'Müşterinin tercih ettiği uzman/terapist adı (örn: Nur Hanım, İlayda).'],
                        'provider_id' => ['type' => 'integer', 'description' => 'Opsiyonel terapist/uzman ID numarası.'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_create',
                'description' => 'Müşterinin yeni randevu alma talebini (hizmet, tarih/saat, müşteri isim ve telefon bilgisi) yönetici onayına gönderir. Müşteri belirli bir uzman talep etmediyse provider_name ve provider_id boş bırakılabilir (sistem müsait olan en uygun uzmanı otomatik atayacaktır). Müşteri özellikle bir uzman talep etmediği sürece cevabınızda KESİNLİKLE uzman adı geçirmeyiniz.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'service_name' => ['type' => 'string', 'description' => 'İstenen hizmetin adı veya türü.'],
                        'service_id' => ['type' => 'integer', 'description' => 'İstenen hizmetin ID numarası (varsa).'],
                        'provider_name' => ['type' => 'string', 'description' => 'Müşterinin tercih ettiği veya önerilen terapist/uzman adı (örn: Nur Hanım, İlayda Hanım).'],
                        'provider_id' => ['type' => 'integer', 'description' => 'Tercih edilen terapist/uzman ID numarası (varsa).'],
                        'start_datetime' => ['type' => 'string', 'description' => 'Randevu başlangıç tarih ve saati (YYYY-AA-GG SS:DD:00 formatında, örn: 2026-09-20 14:00:00).'],
                        'customer_name' => ['type' => 'string', 'description' => 'Müşterinin adı ve soyadı.'],
                        'customer_phone' => ['type' => 'string', 'description' => 'Müşterinin telefon numarası.'],
                        'customer_email' => ['type' => 'string', 'description' => 'Müşterinin e-posta adresi (varsa).'],
                        'notes' => ['type' => 'string', 'description' => 'Müşteri notu veya özel istekleri.'],
                        'reason' => ['type' => 'string', 'description' => 'Talep özeti veya gerekçesi.'],
                    ],
                    'required' => ['service_name', 'start_datetime', 'customer_name', 'customer_phone'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_cancel',
                'description' => 'Müşterinin mevcut bir randevusunu iptal etme/silme talebini yönetici onayına gönderir. Randevuyu doğrudan silmez, yönetici onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'İptal edilmek istenen randevunun ID numarası.'],
                        'reason' => ['type' => 'string', 'description' => 'İptal gerekçesi (müşteri tarafından iletilen).'],
                    ],
                    'required' => ['appointment_id', 'reason'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_reschedule',
                'description' => 'Müşterinin mevcut bir randevusunun gün veya saatini değiştirme talebini yönetici onayına gönderir. Randevuyu doğrudan değiştirmez, yönetici onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'Değiştirilmek istenen randevunun ID numarası.'],
                        'new_start_datetime' => ['type' => 'string', 'description' => 'Yeni randevu başlangıç tarih ve saati (YYYY-AA-GG SS:DD:00 formatında).'],
                        'reason' => ['type' => 'string', 'description' => 'Değişiklik gerekçesi.'],
                    ],
                    'required' => ['appointment_id', 'new_start_datetime', 'reason'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_customer_update',
                'description' => 'Müşterinin kendi iletişim/profil bilgilerinde (telefon, e-posta, adres veya not) değişiklik talebini yönetici onayına gönderir. Veritabanını doğrudan DEĞİŞTİRMEZ, yönetici onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer', 'description' => 'Müşterinin ID numarası.'],
                        'changes' => [
                            'type' => 'object',
                            'description' => 'Güncellenecek alanlar (first_name, last_name, email, phone_number, address, notes).',
                        ],
                        'reason' => ['type' => 'string', 'description' => 'Talep gerekçesi (müşteri tarafından iletilen).'],
                    ],
                    'required' => ['customer_id', 'changes', 'reason'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'request_human_handoff',
                'description' => 'Müşteri canlı destek, yetkili personel, müşteri temsilcisi istediğinde veya yapay zekanın çözemeyeceği özel/karmaşık bir durum oluştuğunda çağrılır. Görüşmeyi insan personele aktarır ve yapay zeka otomatik yanıtlarını durdurur.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'reason' => ['type' => 'string', 'description' => 'Müşterinin aktarma talebi veya gerekçesi.'],
                    ],
                    'required' => ['reason'],
                ],
            ],
        ],
    ];

    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('ai_llm_gateway');
        $this->CI->load->library('channel_templates');
    }

    /**
     * Generate an AI response to an inbound channel message.
     *
     * @param string $channel 'whatsapp' | 'telegram' | 'instagram'
     * @param string $sender_id Unique sender identifier (phone, chat_id, ig user id)
     * @param string $message_text The incoming text message
     * @param array|null $matched_user Customer user record if recognized
     *
     * @return string|null Generated response text or null if cannot reply
     */
    public function respond(string $channel, string $sender_id, string $message_text, ?array $matched_user = null): ?string
    {
        $clean_text = trim($message_text);
        if ($clean_text === '' || $clean_text === '[metin olmayan mesaj]' || $clean_text === '[non-text message]') {
            return null;
        }

        $matched_user = $this->decrypt_user_pii($matched_user);

        $booking_url = site_url('booking');

        // 1. Direct commands to toggle AI
        $lower_text = mb_strtolower($clean_text);
        if ($clean_text === '#ai-on' || $lower_text === 'asistanı aç' || $lower_text === 'asistanı başlat' || $lower_text === 'botu aç') {
            $this->resolve_handoff($channel, $sender_id);
            return "✅ Yapay zeka asistanı yeniden aktif edildi. Size nasıl yardımcı olabilirim? 🤖";
        }

        if ($clean_text === '#ai-off' || $lower_text === 'asistanı kapat' || $lower_text === 'botu kapat') {
            $this->trigger_handoff($channel, $sender_id, $matched_user['id'] ?? null, 'Kullanıcı/Yetkili komutu (#ai-off)');
            return "⏸️ Yapay zeka asistanı durduruldu. Mesajlarınız doğrudan yetkili ekibimize iletilmektedir.";
        }

        // 2. Active human handoff check: If paused, AI remains silent so human staff can chat
        if ($this->is_handoff_active($channel, $sender_id)) {
            log_message('debug', "Ai_channel_responder: conversation {$channel}:{$sender_id} is currently paused for human handoff.");
            return null;
        }

        // 3. Fast regex match for human handoff request
        if (preg_match('/(canlı\s*destek|müşteri\s*temsilci|temsilci(ye|yle)?|yetkili(ye|yle| biri)?|insanla\s*görüş|operatör|insan\s*istiyorum|biriyle\s*görüş)/iu', $clean_text)) {
            return $this->trigger_handoff($channel, $sender_id, $matched_user['id'] ?? null, "Müşteri doğrudan insan temsilci talep etti: {$clean_text}");
        }

        // Auto-match unlinked user by phone if message contains a phone number pattern
        if (empty($matched_user['id'])) {
            $matched_user = $this->try_auto_match_phone($clean_text, $channel, $sender_id);
        }

        $system_prompt = $this->build_system_prompt($channel, $sender_id, $matched_user);

        // Fetch recent conversation history from channel messages table to maintain multi-turn context
        $history = $this->get_channel_history($channel, $sender_id, $clean_text, 5);

        $conversation = array_merge(
            [['role' => 'system', 'content' => $system_prompt]],
            $history
        );

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->CI->ai_llm_gateway->chat($conversation, [
                'tools' => self::TOOLS,
                'temperature' => 0.3,
                'max_tokens' => 800,
            ]);

            if ($response === null || empty($response['success'])) {
                $error_detail = $response === null ? 'LLM gateway returned null (all providers failed)' : 'LLM response success=false';
                log_message('error', "Ai_channel_responder::respond - fallback triggered at iteration {$i}: {$error_detail}. Channel={$channel}, sender={$sender_id}");
                $company_name = setting('company_name') ?: 'İşletmemiz';
                return "Merhaba! Mesajınız ekibimize iletildi. Randevu almak veya müsaitlik durumunu incelemek için bağlantımızı ziyaret edebilirsiniz:\n{$booking_url}";
            }

            $tool_calls = $response['tool_calls'] ?? [];
            if (empty($tool_calls)) {
                $reply = trim((string) ($response['reply'] ?? ''));
                return $reply !== '' ? $reply : null;
            }

            // Append assistant response with tool calls
            $conversation[] = [
                'role' => 'assistant',
                'content' => $response['reply'] ?? '',
                'tool_calls' => $tool_calls,
                'model_parts' => $response['model_parts'] ?? null,
            ];

            foreach ($tool_calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = $this->execute_tool($name, $args, $channel, $sender_id, $matched_user);

                // If customer requested handoff via tool, immediately return confirmation message
                if ($name === 'request_human_handoff' || !empty($result['handoff_triggered'])) {
                    return $result['message'] ?? "Talebinizi yetkili ekibimize aktardım. En kısa sürede bir müşteri temsilcimiz sizinle bu hat üzerinden iletişime geçecektir. 👤";
                }

                // If customer was newly linked during tool execution, refresh matched_user in memory
                if ($name === 'link_customer_channel' && !empty($result['success']) && !empty($result['customer_id'])) {
                    $this->CI->load->model('customers_model');
                    $matched_user = $this->CI->customers_model->find((int) $result['customer_id']);
                }

                $conversation[] = [
                    'role' => 'tool',
                    'name' => $name,
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return "Talebiniz alınarak yetkili ekibimize iletildi. Randevularınızı incelemek veya yeni randevu oluşturmak için:\n{$booking_url}";
    }

    /**
     * Decrypt customer PII fields if encrypted.
     */
    private function decrypt_user_pii(?array $user): ?array
    {
        if (empty($user)) {
            return null;
        }

        if (function_exists('sf_pii_is_encrypted') && function_exists('sf_pii_decrypt')) {
            foreach (['phone_number', 'email', 'address', 'notes', 'city', 'zip_code'] as $field) {
                if (!empty($user[$field]) && sf_pii_is_encrypted($user[$field])) {
                    $user[$field] = sf_pii_decrypt($user[$field]);
                }
            }
        }

        return $user;
    }

    /**
     * Try to auto match customer if a phone number is detected in text.
     */
    private function try_auto_match_phone(string $text, string $channel, string $sender_id): ?array
    {
        $this->CI->load->model('customers_model');

        // Look for 10-11 digit phone number patterns
        if (preg_match('/(\+?90|0)?\s*(5\d{2})[\s.-]*(\d{3})[\s.-]*(\d{2})[\s.-]*(\d{2})/', $text, $matches)) {
            $digits10 = $matches[2] . $matches[3] . $matches[4] . $matches[5];
            $candidates = array_values(array_unique(array_filter([
                $digits10,
                '0' . $digits10,
                '90' . $digits10,
                '+90' . $digits10,
                sprintf('0 (%s) %s %s %s', $matches[2], $matches[3], $matches[4], $matches[5]),
                sprintf('0%s %s %s %s', $matches[2], $matches[3], $matches[4], $matches[5]),
                sprintf('+90 %s %s %s', $matches[2], $matches[3], substr($digits10, 6)),
            ])));

            $customer = null;

            // 1. Direct PII hash search (most reliable for encrypted databases)
            if (function_exists('sf_pii_hash') && $this->CI->db->field_exists('phone_number_hash', 'users')) {
                $hashes = array_values(array_unique(array_filter(array_map('sf_pii_hash', $candidates))));
                if (!empty($hashes)) {
                    $row = $this->CI->db
                        ->where_in('phone_number_hash', $hashes)
                        ->get('users')
                        ->row_array();
                    if ($row) {
                        $customer = $this->CI->customers_model->find((int) $row['id']);
                    }
                }
            }

            // 2. Search fallback with candidates
            if (empty($customer)) {
                foreach ($candidates as $cand) {
                    $found = $this->CI->customers_model->search($cand, 1);
                    if (!empty($found[0])) {
                        $customer = $found[0];
                        break;
                    }
                }
            }

            if (!empty($customer)) {
                $customer = $this->decrypt_user_pii($customer);
                // Auto link channel ID
                $update_field = match ($channel) {
                    'telegram' => 'telegram_chat_id',
                    'instagram' => 'instagram_user_id',
                    'whatsapp' => 'whatsapp_wa_id',
                    default => null,
                };
                if ($update_field) {
                    $this->CI->db->update('users', [$update_field => $sender_id], ['id' => $customer['id']]);
                }
                return $customer;
            }
        }

        return null;
    }

    /**
     * Retrieve recent conversation history for this sender/channel to preserve multi-turn context.
     *
     * @param string $channel 'telegram' | 'whatsapp' | 'instagram'
     * @param string $sender_id Unique chat ID, wa_id, or ig user ID
     * @param string $current_text The current inbound message text
     * @param int $limit Maximum number of recent turns to include
     * @return array List of ['role' => 'user'|'assistant', 'content' => string]
     */
    private function get_channel_history(string $channel, string $sender_id, string $current_text, int $limit = 12): array
    {
        $CI = &get_instance();
        $history = [];

        try {
            $rows = [];
            if ($channel === 'telegram') {
                if ($CI->db->table_exists('telegram_messages')) {
                    $rows = $CI->db
                        ->where('chat_id', $sender_id)
                        ->order_by('id', 'DESC')
                        ->limit($limit)
                        ->get('telegram_messages')
                        ->result_array();
                }
            } elseif ($channel === 'whatsapp') {
                if ($CI->db->table_exists('whatsapp_messages')) {
                    $rows = $CI->db
                        ->where('wa_id', $sender_id)
                        ->order_by('id', 'DESC')
                        ->limit($limit)
                        ->get('whatsapp_messages')
                        ->result_array();
                }
            } elseif ($channel === 'instagram') {
                if ($CI->db->table_exists('instagram_messages')) {
                    $rows = $CI->db
                        ->where('instagram_user_id', $sender_id)
                        ->order_by('id', 'DESC')
                        ->limit($limit)
                        ->get('instagram_messages')
                        ->result_array();
                }
            }

            if (!empty($rows)) {
                $rows = array_reverse($rows);
                foreach ($rows as $r) {
                    $msg = trim((string) ($r['message'] ?? ''));
                    if ($msg === '' || $msg === '[metin olmayan mesaj]' || $msg === '[non-text message]') {
                        continue;
                    }
                    if (mb_strlen($msg) > 250) {
                        $msg = mb_substr($msg, 0, 250) . '...';
                    }
                    $role = (($r['direction'] ?? '') === 'out') ? 'assistant' : 'user';
                    $history[] = [
                        'role' => $role,
                        'content' => $msg,
                    ];
                }
            }
        } catch (Throwable $e) {
            log_message('error', 'Ai_channel_responder::get_channel_history - ' . $e->getMessage());
        }

        // Ensure the current user message is present at the end if history was empty or DB wasn't updated yet
        if (empty($history) || end($history)['role'] !== 'user' || end($history)['content'] !== $current_text) {
            $history[] = ['role' => 'user', 'content' => $current_text];
        }

        return $history;
    }

    /**
     * Build the system prompt with rich read-only business and customer context.
     */
    private function build_system_prompt(string $channel, string $sender_id, ?array $matched_user): string
    {
        $CI = &get_instance();
        $CI->load->model('services_model');
        $CI->load->model('appointments_model');

        $company_name = setting('ai_brand_name') ?: (setting('company_name') ?: 'İşletmemiz');
        $company_phone = setting('company_phone') ?: '';
        $company_address = setting('company_address') ?: 'İşletme Adresi';
        $booking_url = setting('company_link') ?: site_url();
        $tz_string = setting('default_timezone') ?: 'Europe/Istanbul';
        try {
            $tz = new DateTimeZone($tz_string);
        } catch (\Throwable $e) {
            $tz = new DateTimeZone('Europe/Istanbul');
        }
        $now = new DateTime('now', $tz);
        $current_datetime = $now->format('Y-m-d H:i:s');
        $current_day_name = match ($now->format('N')) {
            '1' => 'Pazartesi',
            '2' => 'Salı',
            '3' => 'Çarşamba',
            '4' => 'Perşembe',
            '5' => 'Cuma',
            '6' => 'Cumartesi',
            '7' => 'Pazar',
            default => '',
        };

        // Fetch available services and group them cleanly by base name
        $services = $CI->services_model->get_available_services();
        $grouped_services = [];
        foreach ($services as $s) {
            $base_name = trim(preg_replace('/\s*-\s*\d+\s*(dakika|dk)/i', '', $s['name']));
            if (!isset($grouped_services[$base_name])) {
                $grouped_services[$base_name] = [
                    'id' => $s['id'],
                    'name' => $base_name,
                    'durations' => [],
                    'prices' => [],
                ];
            }
            if (!empty($s['duration'])) {
                $grouped_services[$base_name]['durations'][] = $s['duration'] . ' dk';
            }
            if (!empty($s['price']) && (float)$s['price'] > 0) {
                $grouped_services[$base_name]['prices'][] = (int)$s['price'] . ' TL';
            }
        }

        $services_summary = [];
        foreach (array_slice($grouped_services, 0, 25) as $g) {
            $dur_str = !empty($g['durations']) ? implode('/', array_unique($g['durations'])) : '30 dk';
            $price_str = !empty($g['prices']) ? implode(' - ', array_unique($g['prices'])) : 'Bilgi alınız';
            $services_summary[] = "- [ID: {$g['id']}] {$g['name']} (Süre: {$dur_str}, Fiyat: {$price_str})";
        }
        $services_text = !empty($services_summary) ? implode("\n", $services_summary) : "Hizmet listesi için web sitemizi ziyaret ediniz.";

        // Fetch available providers/therapists
        $CI->load->model('providers_model');
        $providers = $CI->providers_model->get_available_providers();
        $providers_summary = [];
        foreach ($providers as $p) {
            $p_name = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            if ($p_name !== '') {
                $providers_summary[] = "- [ID: {$p['id']}] {$p_name}";
            }
        }
        $providers_text = !empty($providers_summary) ? implode("\n", $providers_summary) : "Tüm uzmanlarımız hizmet vermektedir.";

        // Customer context
        $customer_context = "MÜŞTERİ DURUMU: Henüz sistemle eşleşmemiş misafir. Gönderici Kimliği: {$sender_id}";
        $is_recognized = false;

        $customer_phone_clean = '';
        $customer_name_clean = '';

        if (!empty($matched_user['id'])) {
            $is_recognized = true;
            $customer_name_clean = trim(($matched_user['first_name'] ?? '') . ' ' . ($matched_user['last_name'] ?? ''));
            $raw_phone = $matched_user['phone_number'] ?? $sender_id;
            if (function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($raw_phone)) {
                $raw_phone = sf_pii_decrypt($raw_phone);
            }
            $customer_phone_clean = $raw_phone;
            $email = $matched_user['email'] ?? '';
            if (function_exists('sf_pii_is_encrypted') && sf_pii_is_encrypted($email)) {
                $email = sf_pii_decrypt($email);
            }
            $id = $matched_user['id'];

            // Fetch upcoming appointments
            $upcoming_text = 'Yok';
            $upcoming = $CI->db
                ->select('appointments.id, appointments.start_datetime, appointments.end_datetime, appointments.status, services.name AS service_name, users.first_name AS provider_first_name, users.last_name AS provider_last_name')
                ->from('appointments')
                ->join('services', 'services.id = appointments.id_services', 'left')
                ->join('users', 'users.id = appointments.id_users_provider', 'left')
                ->where('appointments.id_users_customer', $id)
                ->where('appointments.start_datetime >=', date('Y-m-d H:i:s'))
                ->order_by('appointments.start_datetime', 'ASC')
                ->limit(5)
                ->get()
                ->result_array();

            if (!empty($upcoming)) {
                $u_lines = [];
                foreach ($upcoming as $u) {
                    $provider_name = trim(($u['provider_first_name'] ?? '') . ' ' . ($u['provider_last_name'] ?? '')) ?: 'Belirtilmedi';
                    $u_lines[] = "• [Randevu ID: " . $u['id'] . "] " . $u['start_datetime'] . " - " . ($u['service_name'] ?? 'Hizmet') . " (Uzman: " . $provider_name . ", Durum: " . ($u['status'] ?? '') . ")";
                }
                $upcoming_text = implode("\n", $u_lines);
            }

            // Fetch past appointments (last 3)
            $past_text = 'Yok';
            $past = $CI->db
                ->select('appointments.id, appointments.start_datetime, appointments.status, services.name AS service_name, users.first_name AS provider_first_name, users.last_name AS provider_last_name')
                ->from('appointments')
                ->join('services', 'services.id = appointments.id_services', 'left')
                ->join('users', 'users.id = appointments.id_users_provider', 'left')
                ->where('appointments.id_users_customer', $id)
                ->where('appointments.start_datetime <', date('Y-m-d H:i:s'))
                ->order_by('appointments.start_datetime', 'DESC')
                ->limit(3)
                ->get()
                ->result_array();

            if (!empty($past)) {
                $p_lines = [];
                foreach ($past as $p_item) {
                    $p_lines[] = "• [Geçmiş Randevu ID: " . $p_item['id'] . "] " . $p_item['start_datetime'] . " - " . ($p_item['service_name'] ?? 'Hizmet') . " (Durum: " . ($p_item['status'] ?? 'Tamamlandı') . ")";
                }
                $past_text = implode("\n", $p_lines);
            }

            // Active packages / memberships
            $package_text = '(Aktif paket veya seans hakkı bulunmuyor)';
            try {
                $packages = $this->CI->db
                    ->select('cp.*, s.name as service_name')
                    ->from('customer_packages cp')
                    ->join('services s', 's.id = cp.id_services', 'left')
                    ->where('cp.id_users_customer', $id)
                    ->where('cp.status', 'active')
                    ->get()
                    ->result_array();

                if (!empty($packages)) {
                    $pkg_lines = [];
                    foreach ($packages as $pkg) {
                        $remaining = max(0, (int)$pkg['total_sessions'] - (int)$pkg['used_sessions']);
                        $pkg_lines[] = "• " . ($pkg['service_name'] ?? 'Paket') . ": Toplam {$pkg['total_sessions']} seans, Kalan {$remaining} seans (Bitiş: " . ($pkg['expires_at'] ?? 'Süresiz') . ")";
                    }
                    $package_text = implode("\n", $pkg_lines);
                }
            } catch (\Throwable $e) {
                // Table might not exist or error
            }

            $customer_context = <<<CUST
TANINAN MÜŞTERİ BİLGİLERİ (KAYITLI & EŞLEŞMİŞ):
- ID: {$id}
- İsim: {$customer_name_clean}
- Telefon: {$customer_phone_clean}
- E-posta: {$email}
- Yaklaşan Randevuları:
{$upcoming_text}
- Geçmiş / Son Randevuları:
{$past_text}
- Aktif Paketleri / Kalan Seans Hakları:
{$package_text}
CUST;
        } elseif ($channel === 'whatsapp' && preg_match('/^\+?\d{8,15}$/', $sender_id) && strlen(preg_replace('/\D/', '', $sender_id)) <= 12) {
            $customer_phone_clean = $sender_id;
            $customer_context = <<<CUST
WHATSAPP MÜŞTERİ DURUMU:
- Müşterinin WhatsApp Telefon Numarası: {$customer_phone_clean}
- İsim: Henüz sorulmadı (randevu oluştururken adını ve soyadını sor).
CUST;
        }

        $channel_name = match ($channel) {
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'instagram' => 'Instagram Direct',
            default => 'Mesajlaşma Kanalı',
        };

        if ($is_recognized) {
            $recognition_instructions = <<<REC
MÜŞTERİ SİSTEMDE KAYITLI VE TANINMIŞTIR:
- Müşterinin Adı: {$customer_name_clean}
- Müşterinin Telefon Numarası: {$customer_phone_clean}
- Müşteriye ismiyle son derece nazik, saygılı ve samimi şekilde hitap et (Örn: "Miraç Bey", "Miraç Hanım").
- ÇOK KATI KURAL (ASLA TELEFON YA DA İSİM SORMA): Müşterinin adı ve telefon numarası zaten sistemimizde kayıtlıdır! Müşteriden KESİNLİKLE ad, soyad veya telefon numarası İSTEME! "Numaranızı yazabilir misiniz" gibi sorular sorma!
- Müşteri randevu almak istediğinde veya uygun bir saati sorduğunda, saat uygunsa MÜŞTERİDEN BİLGİ SORMA ADIMINI ATLA ve elindeki kayıtlı isim ({$customer_name_clean}) ve telefon ({$customer_phone_clean}) ile DOĞRUDAN `propose_appointment_create` aracını çağır!
- Müşteri geçmiş randevularını sorduğunda, yukarıdaki "Geçmiş / Son Randevuları" alanında yer alan randevuyu doğrudan müşteriye bildir (Örn: "22 Eylül Salı saat 14:00'te Klasik Masaj randevunuz bulunmaktaydı"). Asla "sisteme erişemiyorum" veya "temsilciye bağlayayım" deme.
REC;
        } elseif ($channel === 'whatsapp' && $customer_phone_clean !== '') {
            $recognition_instructions = <<<REC
MÜŞTERİ WHATSAPP ÜZERİNDEN YAZMAKTADIR:
- Müşterinin WhatsApp Telefon Numarası: {$customer_phone_clean}
- ÇOK KATI KURAL (TELEFON NUMARASI SORMA): Müşterinin telefon numarası WhatsApp hattı üzerinden zaten bilinmektedir ({$customer_phone_clean}). Müşteriden telefon numarası İSTEME!
- Randevu oluştururken `customer_phone` parametresine doğrudan "{$customer_phone_clean}" değerini gönder.
- Yalnızca müşterinin adını bilmiyorsan, randevu kaydı için adını ve soyadını rica et (Örn: "Randevunuzu hemen oluşturabilmem için adınızı ve soyadınızı rica edebilir miyim?").
REC;
        } else {
            $recognition_instructions = <<<REC
MÜŞTERİ BU KANALDAN ({$channel_name}) HENÜZ EŞLEŞMEMİŞTİR:
- Müşteri randevularını sorgulamak veya değiştirmek isterse, sistemdeki kaydını bulabilmemiz için kibarca telefon numarasını rica et.
- Müşteri numarasını yazdığında sistem otomatik olarak eşleşecek ve randevu işlemini tamamlayacaktır.
REC;
        }

        $custom_rules = [];
        $ai_tone = setting('ai_tone') ?: 'friendly_professional';
        $tone_text = match($ai_tone) {
            'formal' => 'Resmi, kurumsal ve mesafeli bir dille yanıt ver.',
            'casual_friendly' => 'Rahat, samimi, arkadaşça ve esprili bir dille yanıt ver.',
            'warm_empathetic' => 'Son derece sıcak, anlayışlı ve empatik bir dille yanıt ver.',
            default => 'Saygılı, nazik, samimi ve profesyonel bir dille yanıt ver.',
        };
        $custom_rules[] = "- İletişim Üslubu: " . $tone_text;

        $greeting_style = setting('ai_greeting_style');
        if (!empty($greeting_style)) {
            $custom_rules[] = "- İlk Karşılama Şablonu: " . trim($greeting_style);
        }
        $do_rules = setting('ai_do_rules');
        if (!empty($do_rules)) {
            $custom_rules[] = "- İŞLETME ZORUNLU KURALLARI:\n" . trim($do_rules);
        }
        $dont_rules = setting('ai_dont_rules');
        if (!empty($dont_rules)) {
            $custom_rules[] = "- KESİNLİKLE YAPILMAYACAKLAR (YASAKLAR):\n" . trim($dont_rules);
        }
        $cancellation_policy = setting('ai_cancellation_policy');
        if (!empty($cancellation_policy)) {
            $custom_rules[] = "- İPTAL VE DEĞİŞİKLİK POLİTİKASI:\n" . trim($cancellation_policy);
        }
        $discount_policy = setting('ai_discount_policy');
        if (!empty($discount_policy)) {
            $custom_rules[] = "- İNDİRİM VE KAMPANYA POLİTİKASI:\n" . trim($discount_policy);
        }
        $forbidden_terms = setting('ai_forbidden_terms');
        if (!empty($forbidden_terms)) {
            $custom_rules[] = "- KULLANILMASI YASAK KELİMELER: " . trim($forbidden_terms);
        }
        $ai_rules_block = implode("\n", $custom_rules);

        return <<<PROMPT
Sen "{$company_name}" işletmesinin {$channel_name} üzerindeki resmi, nazik ve akıllı yapay zeka asistanısın.

GÜNCEL ZAMAN:
- Sistem Zamanı: {$current_datetime} ({$current_day_name})

İŞLETME BİLGİLERİ:
- İşletme Adı: {$company_name}
- Telefon: {$company_phone}
- Adres: {$company_address}
- Online Randevu Bağlantısı: {$booking_url}

İŞLETME ÖZEL ASİSTAN POLİTİKASI & KURALLARI:
{$ai_rules_block}

MEVCUT HİZMETLER:
{$services_text}

UZMANLAR / TERAPİSTLER:
{$providers_text}

{$customer_context}

MÜŞTERİ TANIMA VE KANAL EŞLEŞTİRME KURALLARI:
{$recognition_instructions}

TERAPİST / UZMAN ADI KULLANIMI VE MÜSAİTLİK KURALLARI (ÇOK KATI KURAL):
1. **Uzman / Terapist Adını ASLA Kendiliğinden Geçirme:**
   - Müşteri kendisi açıkça bir terapist/uzman adı belirtmediği VEYA uzmanları açıkça sormadığı sürece (örn: "kimler var?", "uzmanlarınız kimler?", "masajı kim yapıyor?"), yanıtlarında KESİNLİKLE hiçbir terapist veya uzman adı GEÇİRME!
   - Müsait saatleri veya günleri sunarken de terapist isimlerini sayma (Örn: "Pazartesi günü saat 11:00, 11:15, 11:30 saatlerimiz uygundur" de, "İlayda Hanım 11:00'de müsait" DEME).
   - Müşteriye durup dururken "tercih ettiğiniz bir uzman var mı?" diye sorma, kendiliğinden uzman tanıtımı yapma.
2. **Müşteri Uzman Sorarsa veya Belirtirse:**
   - YALNIZCA müşteri açıkça sorarsa (örn: "hangi uzmanlar var?", "masajı kimler yapıyor?"), sistemdeki mevcut uzmanların isimlerini sayabilirsin.
   - Müşteri belirli bir uzman talep ederse (örn: "Nur Hanım olsun", "Nur Hanım ile randevu istiyorum"), bunu memnuniyetle onayla ve randevuyu o uzmana yaz (`provider_name` olarak ver).
3. **Randevu Oluştururken Otomatik Uzman Atama:**
   - Randevu talebinde müşteri bir uzman belirtmemişse, `propose_appointment_create` aracını çağırırken sistem en uygun müsait uzmanı arka planda otomatik seçecektir.
   - Müşteriye randevu talebinin alındığını bildirirken de uzman adından ASLA bahsetme! Yalnızca hizmet adı, tarih ve saat bilgisini ilet (Örn: "Klasik Masaj (60 dk) randevu talebiniz 21 Eylül Pazartesi 11:00 için alındı. Talebiniz yönetici onayına iletilmiştir.").

GÖREVLER VE İŞLEM AKIŞI (YÖNETİCİ ONAY PRENSİBİ):
1. **YENİ RANDEVU ALMA TALEBİ:**
   - Müşteri randevu almak istediğinde veya belirli bir saat üzerinde mutabık kalındığında:
     * Eğer müşteri sistemde tanınıyorsa VEYA telefon numarası biliniyorsa: MÜŞTERİYE TEKRAR TELEFON NUMARASI YA DA İSİM ASLA SORMA! Elindeki kayıtlı bilgileri kullanarak DOĞRUDAN `propose_appointment_create` aracını çağır!
     * Yalnızca ilk defa yazan misafirin adı bilinmiyorsa isim sor; telefon numarası WhatsApp üzerinden zaten biliniyorsa telefon asla sorma.
     * Müşteri belirli bir uzman talep etmemişse sisteme otomatik atat ve cevabında ASLA uzman adı geçirme.
     * Müşteriye randevu talebinin (hizmet, tarih ve saat) alındığını, yönetici onayından sonra randevunun kesinleşeceğini bildir.

2. **GEÇMİŞ / YAKLAŞAN RANDEVU VE PAKET SORGULARI:**
   - Müşteri geçmiş veya yaklaşan randevularını sorduğunda:
     * Yukarıdaki "TANINAN MÜŞTERİ BİLGİLERİ" alanındaki "Yaklaşan Randevuları" ve "Geçmiş / Son Randevuları" verilerini kullanarak doğrudan yanıt ver (Örn: "25 Eylül Cuma günü saat 15:45'te Klasik Masaj randevunuz bulunmaktaydı").
     * Asla "sisteme erişemiyorum", "yetkiliye bağlayayım" gibi gereksiz yanıtlar verme; elindeki kayıtlı randevu bilgilerini müşteriye şeffafça sun.
   - Müşteri paketlerini, seans haklarını veya üyeliğini sorduğunda ("Paketim var mı?", "Kaç seansım kaldı?"):
     * Yukarıdaki "Aktif Paketleri / Kalan Seans Hakları" alanındaki bilgiyi doğrudan aktar.
     * Eğer paket yoksa veya "(Aktif paket veya seans hakkı bulunmuyor)" ise: "Sistemimizde adınıza kayıtlı aktif bir paket veya seans hakkı bulunmamaktadır." şeklinde doğrudan, kısa ve net bilgi ver. Cevabında asla İngilizce metin, iç düşünce veya 'Draft:' gibi kelimeler kullanma.

3. **RANDEVU DEĞİŞTİRME / SAAT GÜNCELLEME:**
   - Tanınan müşterinin yaklaşan randevularındaki [Randevu ID] ve istenen yeni tarih/saat ile `propose_appointment_reschedule` aracını çağır.
   - Değişiklik talebinin yönetici onayına iletildiğini bildir.

4. **RANDEVU İPTALİ / SİLME:**
   - İlgili [Randevu ID] ile `propose_appointment_cancel` aracını çağır.
   - İptal talebinin yönetici onayına iletildiğini bildir.

5. **BİLGİ GÜNCELLEME:**
   - Profil bilgisi (telefon, e-posta, not) değişikliğinde `propose_customer_update` aracını çağır.

6. **ONLİNE RANDEVU SEÇENEĞİ:**
   - Müsait saatleri canlı görüp anında randevu almak isteyenlere linki sun: {$booking_url}

7. **ÇALIŞMA SAATLERİ, AÇIKLIK VE MÜSAİTLİK SORULARI (ÖNEMLİ KURAL):**
   - Müşteri "bugün açık mısınız?", "çalışıyor musunuz?", "saat kaçta açılıyorsunuz?", "hafta sonu açık mısınız?" veya belirli bir terapistin (örn: Nur Hanım) müsaitliğini sorduğunda KENDİ KAFANDAN TAHMİN YAPMA VE "asistan olarak her zaman buradayım" GİBİ GEÇİŞTİRİCİ CEVAP VERME.
   - MUTLAKA ilk adım olarak `check_availability` aracını çağır!
   - Bu araç işletmenin ve terapistlerin gerçek çalışma takvimini, kapalı günlerini ve boş randevu saatlerini hesaplar.
   - Araç "kapalıdır" veya "müsait randevu saati bulunmamaktadır" döndürürse: Müşteriye bugün kapalı olduğumuzu veya o tarihte randevu bulunmadığını nazikçe açıkla ve aracın önerdiği en yakın açık iş gününü (örn: Pazartesi) ve saatleri teklif et.
   - Müşteri doğrudan randevu almak istediğinde de önce veya randevu teklifi sırasında saatin uygunluğunu `check_availability` ile doğrula.

8. **CANLI DESTEK VE İNSAN TEMSİLCİYE AKTARMA (HANDOFF PROTOKOLÜ):**
   - Müşteri insan müşteri temsilcisi, canlı destek, yetkili personel istediğinde veya sistemin çözemeyeceği özel bir istek/şikayet belirttiğinde MUTLAKA `request_human_handoff` aracını çağır!
   - Bu araç çağrıldığında sistem otomatik olarak yapay zekayı durduracak ve görüşmeyi mağaza/işletme yetkililerine devredecektir.

GENEL KURALLAR:
- Her zaman Türkçe, saygılı, samimi ve mobil mesaja uygun formatta (kısa, paragraflı) yanıt ver.
- Mesajlaşma kanalını karıştırma: Şu an {$channel_name} üzerindesin. 'SMS ile bildireceğiz' veya 'e-posta attık' gibi uydurma bildirim kanalları söyleme; talebin yönetici tarafından onaylandığında doğrudan buradan ({$channel_name}) bildirim alacağını belirt.
- Elinde olmayan hizmet veya fiyatı uydurma.
- Teknik terimlerden (JSON, araç, prompt, database) asla bahsetme.
PROMPT;
    }

    /**
     * Execute tool calls triggered by the LLM.
     */
    private function execute_tool(string $name, array $args, string $channel, string $sender_id, ?array $matched_user): array
    {
        try {
            $active_provider = $this->CI->ai_llm_gateway->get_active_provider();
            $this->CI->load->model('customers_model');

            switch ($name) {
                case 'get_providers':
                    $this->CI->load->model('providers_model');
                    $providers = $this->CI->providers_model->get_available_providers();
                    return array_map(static fn (array $p) => [
                        'id' => (int) $p['id'],
                        'name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
                    ], $providers);

                case 'get_services':
                    $this->CI->load->model('services_model');
                    $services = $this->CI->services_model->get_available_services();
                    return array_map(static fn (array $s) => [
                        'id' => (int) $s['id'],
                        'name' => $s['name'],
                        'duration' => (int) ($s['duration'] ?? 30),
                        'price' => (float) ($s['price'] ?? 0),
                        'currency' => $s['currency'] ?? 'TL',
                    ], $services);

                case 'check_availability':
                    $this->CI->load->library('availability');
                    $this->CI->load->model('services_model');
                    $this->CI->load->model('providers_model');

                    $date = trim((string) ($args['date'] ?? date('Y-m-d')));
                    $ts = $date !== '' ? strtotime($date) : false;
                    $date = $ts !== false ? date('Y-m-d', $ts) : date('Y-m-d');
                    $service_id = (int) ($args['service_id'] ?? 0);
                    $service_name = trim((string) ($args['service_name'] ?? ''));
                    $provider_id = (int) ($args['provider_id'] ?? 0);
                    $provider_name = trim((string) ($args['provider_name'] ?? ''));

                    $day_names_tr = [
                        1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe',
                        5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'
                    ];
                    $day_tr = $day_names_tr[(int) date('N', strtotime($date))] ?? '';

                    // 1. Resolve Service
                    $service = null;
                    if ($service_id > 0) {
                        $service = $this->CI->services_model->find($service_id);
                    }
                    $available_services = $this->CI->services_model->get_available_services();
                    if (!$service && $service_name !== '') {
                        foreach ($available_services as $s) {
                            if (stripos($s['name'], $service_name) !== false || stripos($service_name, $s['name']) !== false) {
                                $service = $s;
                                break;
                            }
                        }
                    }
                    if (!$service) {
                        $service = !empty($available_services) ? $available_services[0] : [
                            'id' => 1,
                            'duration' => 60,
                            'slot_interval' => 15,
                            'attendants_number' => 1,
                            'is_private' => false,
                        ];
                    }

                    // 2. Resolve Providers
                    $all_providers = $this->CI->providers_model->get_available_providers();
                    $candidate_providers = [];

                    if ($provider_id > 0) {
                        foreach ($all_providers as $p) {
                            if ((int) $p['id'] === $provider_id) {
                                $candidate_providers[] = $p;
                                break;
                            }
                        }
                    } elseif ($provider_name !== '') {
                        foreach ($all_providers as $p) {
                            $full = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                            if (stripos($full, $provider_name) !== false || stripos($provider_name, ($p['first_name'] ?? '')) !== false) {
                                $candidate_providers[] = $p;
                                break;
                            }
                        }
                    }

                    // If no specific provider selected or found, filter providers assigned to this service
                    if (empty($candidate_providers)) {
                        foreach ($all_providers as $p) {
                            if (empty($service['id']) || in_array((int) $service['id'], array_map('intval', $p['services'] ?? []), true)) {
                                $candidate_providers[] = $p;
                            }
                        }
                    }
                    if (empty($candidate_providers)) {
                        $candidate_providers = $all_providers;
                    }

                    // 3. Check availability using BooKi Availability library
                    $availability_by_provider = [];
                    $total_available_hours = [];

                    foreach ($candidate_providers as $provider) {
                        if (empty($provider['settings']['working_plan'])) {
                            $provider['settings']['working_plan'] = setting('company_working_plan');
                        }

                        $hours = $this->CI->availability->get_available_hours($date, $service, $provider);
                        $p_name = trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? ''));

                        if (!empty($hours)) {
                            $availability_by_provider[$p_name] = $hours;
                            $total_available_hours = array_unique(array_merge($total_available_hours, $hours));
                        }
                    }
                    sort($total_available_hours);

                    // 4. If slots exist on requested date
                    if (!empty($total_available_hours)) {
                        $is_provider_requested = ($provider_id > 0 || $provider_name !== '');
                        $sample_hours = count($total_available_hours) > 8 ? array_slice($total_available_hours, 0, 8) : $total_available_hours;
                        $hours_str = implode(', ', $sample_hours) . (count($total_available_hours) > 8 ? ' ...' : '');

                        if ($is_provider_requested) {
                            $note = "{$date} ({$day_tr}) günü randevu için uygundur. {$provider_name} için müsait saatler: {$hours_str}";
                        } else {
                            $note = "{$date} ({$day_tr}) günü randevu için uygundur. Müsait saatler: {$hours_str}\n(KATI KURAL: Müşteri özellikle uzman sormadığı sürece yanıtınızda KESİNLİKLE hiçbir uzman/terapist ismi belirtmeyiniz, yalnızca uygun saatleri söyleyiniz.)";
                        }

                        return [
                            'date' => $date,
                            'day' => $day_tr,
                            'is_available' => true,
                            'is_closed' => false,
                            'available_slots' => $total_available_hours,
                            'providers' => $availability_by_provider,
                            'note' => $note,
                        ];
                    }

                    // 5. If NO slots available on requested date (e.g. Saturday/Sunday closed, holiday, or booked)
                    $next_slots = $this->CI->availability->find_first_available_slots($service, $candidate_providers, 4, 14, false);

                    $is_provider_requested = ($provider_id > 0 || $provider_name !== '');
                    $suggestions = [];
                    foreach ($next_slots as $slot) {
                        $slot_date = $slot['date'];
                        $slot_day = $day_names_tr[(int) date('N', strtotime($slot_date))] ?? '';
                        if ($is_provider_requested) {
                            $suggestions[] = "• {$slot_date} ({$slot_day}) {$slot['hour']} - {$slot['provider_name']}";
                        } else {
                            $suggestions[] = "• {$slot_date} ({$slot_day}) {$slot['hour']}";
                        }
                    }
                    $unique_suggestions = array_values(array_unique($suggestions));

                    $next_open_day = !empty($next_slots[0])
                        ? $next_slots[0]['date'] . ' (' . ($day_names_tr[(int) date('N', strtotime($next_slots[0]['date']))] ?? '') . ')'
                        : 'Pazartesi';

                    $note = "İşletme veya seçilen uzman {$date} ({$day_tr}) tarihinde kapalıdır / müsait randevu saati bulunmamaktadır.";
                    if (!empty($unique_suggestions)) {
                        $note .= " En yakın müsait alternatifler:\n" . implode("\n", $unique_suggestions);
                    } else {
                        $note .= " En yakın açık iş günü: {$next_open_day}.";
                    }
                    if (!$is_provider_requested) {
                        $note .= "\n(KATI KURAL: Müşteri özellikle uzman sormadıkça yanıtınızda KESİNLİKLE uzman ismi geçirmeyiniz, yalnızca gün ve saat öneriniz.)";
                    }

                    return [
                        'date' => $date,
                        'day' => $day_tr,
                        'is_available' => false,
                        'is_closed' => true,
                        'note' => $note,
                        'next_open_day' => $next_open_day,
                        'suggested_slots' => $next_slots,
                    ];

                case 'link_customer_channel':
                    $phone = trim((string) ($args['phone_number'] ?? ''));
                    if (empty($phone)) {
                        return ['error' => 'Telefon numarası zorunludur.'];
                    }

                    $results = $this->CI->customers_model->search($phone, 1);
                    if (empty($results)) {
                        return [
                            'success' => false,
                            'message' => 'Bu telefon numarasıyla kayıtlı müşteri bulunamadı. Dilerseniz yeni müşteri olarak bilgilerinizi alarak randevu oluşturabilirim.',
                        ];
                    }

                    $customer = $results[0];
                    $customer_id = (int) $customer['id'];

                    // Permanently link channel ID to customer user record
                    $update_field = match ($channel) {
                        'telegram' => 'telegram_chat_id',
                        'instagram' => 'instagram_user_id',
                        'whatsapp' => 'whatsapp_wa_id',
                        default => null,
                    };

                    if ($update_field) {
                        $this->CI->db->update('users', [$update_field => $sender_id], ['id' => $customer_id]);
                    }

                    // Fetch customer's upcoming appointments
                    $upcoming = $this->CI->db
                        ->select('appointments.id, appointments.start_datetime, appointments.end_datetime, appointments.status, services.name AS service_name')
                        ->from('appointments')
                        ->join('services', 'services.id = appointments.id_services', 'left')
                        ->where('appointments.id_users_customer', $customer_id)
                        ->where('appointments.start_datetime >=', date('Y-m-d H:i:s'))
                        ->order_by('appointments.start_datetime', 'ASC')
                        ->limit(3)
                        ->get()
                        ->result_array();

                    return [
                        'success' => true,
                        'customer_id' => $customer_id,
                        'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                        'phone_number' => $customer['phone_number'] ?? $phone,
                        'email' => $customer['email'] ?? '',
                        'upcoming_appointments' => $upcoming,
                        'note' => 'Hesap başarıyla eşleştirildi. Müşteriye artık ismiyle hitap edin.',
                    ];

                case 'propose_appointment_create':
                    $service_name = (string) ($args['service_name'] ?? '');
                    $service_id = (int) ($args['service_id'] ?? 0);
                    $provider_name = trim((string) ($args['provider_name'] ?? ''));
                    $provider_id = (int) ($args['provider_id'] ?? 0);
                    $start_datetime = (string) ($args['start_datetime'] ?? '');
                    $customer_name = trim((string) ($args['customer_name'] ?? ($matched_user ? (($matched_user['first_name'] ?? '') . ' ' . ($matched_user['last_name'] ?? '')) : 'Misafir')));
                    $customer_phone = trim((string) ($args['customer_phone'] ?? ($matched_user['phone_number'] ?? $sender_id)));
                    $customer_email = trim((string) ($args['customer_email'] ?? ($matched_user['email'] ?? '')));
                    $notes = (string) ($args['notes'] ?? '');
                    $reason = (string) ($args['reason'] ?? "{$channel} üzerinden randevu talebi");

                    if (empty($start_datetime)) {
                        return ['error' => 'start_datetime zorunludur.'];
                    }

                    // Try to match provider by name if provider_id wasn't passed directly
                    if (!$provider_id && !empty($provider_name)) {
                        $this->CI->load->model('providers_model');
                        $all_p = $this->CI->providers_model->get_available_providers();
                        foreach ($all_p as $p) {
                            $full = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
                            if (stripos($full, $provider_name) !== false || stripos($provider_name, ($p['first_name'] ?? '')) !== false) {
                                $provider_id = (int) $p['id'];
                                $provider_name = $full;
                                break;
                            }
                        }
                    }

                    // Try to match service by name if service_id wasn't passed directly
                    if (!$service_id && !empty($service_name)) {
                        $this->CI->load->model('services_model');
                        $all_s = $this->CI->services_model->get_available_services();
                        foreach ($all_s as $s) {
                            if (stripos($s['name'], $service_name) !== false || stripos($service_name, $s['name']) !== false) {
                                $service_id = (int) $s['id'];
                                $service_name = $s['name'];
                                break;
                            }
                        }
                    }

                    // --- Validate appointment availability via BooKi native Availability library ---
                    $this->CI->load->library('availability');
                    $req_ts = strtotime($start_datetime);
                    if ($req_ts !== false) {
                        $req_date = date('Y-m-d', $req_ts);
                        $req_hour = date('H:i', $req_ts);

                        // Load service
                        $service_data = null;
                        if ($service_id > 0) {
                            $service_data = $this->CI->services_model->find($service_id);
                        }
                        if (!$service_data) {
                            $avail_s = $this->CI->services_model->get_available_services();
                            $service_data = !empty($avail_s) ? $avail_s[0] : null;
                        }

                        // Load candidate provider(s)
                        $candidate_providers = [];
                        if ($provider_id > 0) {
                            $candidate_providers = [$this->CI->providers_model->find($provider_id)];
                        } else {
                            $candidate_providers = $this->CI->providers_model->get_available_providers();
                        }

                        if ($service_data && !empty($candidate_providers)) {
                            $slot_found = false;
                            $open_hours_any = [];
                            foreach ($candidate_providers as $cp) {
                                if (empty($cp['settings']['working_plan'])) {
                                    $cp['settings']['working_plan'] = setting('company_working_plan');
                                }
                                $hours = $this->CI->availability->get_available_hours($req_date, $service_data, $cp);
                                if (!empty($hours)) {
                                    $open_hours_any = array_merge($open_hours_any, $hours);
                                    if (in_array($req_hour, $hours, true)) {
                                        $slot_found = true;
                                        if (!$provider_id) {
                                            $provider_id = (int) $cp['id'];
                                            $provider_name = trim(($cp['first_name'] ?? '') . ' ' . ($cp['last_name'] ?? ''));
                                        }
                                        break;
                                    }
                                }
                            }

                            if (!$slot_found) {
                                $day_names_tr = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
                                $next_slots = $this->CI->availability->find_first_available_slots($service_data, $candidate_providers, 4, 14, false);

                                $is_provider_requested = !empty($provider_name) || !empty($provider_id);
                                $suggestions = [];
                                foreach ($next_slots as $ns) {
                                    $dname = $day_names_tr[(int) date('N', strtotime($ns['date']))] ?? '';
                                    if ($is_provider_requested) {
                                        $suggestions[] = "• {$ns['date']} ({$dname}) {$ns['hour']} - {$ns['provider_name']}";
                                    } else {
                                        $suggestions[] = "• {$ns['date']} ({$dname}) {$ns['hour']}";
                                    }
                                }
                                $unique_sugg = array_values(array_unique($suggestions));

                                if (empty($open_hours_any)) {
                                    $dname_req = $day_names_tr[(int) date('N', $req_ts)] ?? '';
                                    $err = "İşletme {$req_date} ({$dname_req}) tarihinde kapalıdır / randevu kabul etmemektedir. Randevu ONAY KUYRUĞUNA ALINMADI. Müşteriye o gün kapalı olduğumuzu nazikçe açıkla ve şu en yakın müsait gün/saatleri öner:\n" . implode("\n", $unique_sugg);
                                    if (!$is_provider_requested) {
                                        $err .= "\n(KATI KURAL: Müşteri özellikle uzman adı belirtmediği için yanıtta KESİNLİKLE hiçbir uzman/terapist ismi geçirmeyiniz.)";
                                    }
                                    return [
                                        'queued' => false,
                                        'error' => $err,
                                        'suggested_slots' => $next_slots,
                                    ];
                                } else {
                                    $unique_hours = array_values(array_unique($open_hours_any));
                                    sort($unique_hours);
                                    $sample_hours = array_slice($unique_hours, 0, 6);
                                    $err = "Talep edilen saat ({$req_hour}) {$req_date} tarihinde müsait değildir veya mesai saatleri dışındadır. Randevu ONAY KUYRUĞUNA ALINMADI. O günkü müsait saatler: " . implode(', ', $sample_hours) . ". Lütfen müşteriye bu saatleri öner.";
                                    if (!$is_provider_requested) {
                                        $err .= " (KATI KURAL: Uzman ismi belirtmeyiniz.)";
                                    }
                                    return [
                                        'queued' => false,
                                        'error' => $err,
                                        'available_hours' => $unique_hours,
                                    ];
                                }
                            }
                        }
                    }

                    $is_provider_requested = !empty($args['provider_name']) || !empty($args['provider_id']);
                    $notes_with_provider = $notes;
                    if ($is_provider_requested && !empty($provider_name) && stripos($notes_with_provider, $provider_name) === false) {
                        $notes_with_provider = trim($notes_with_provider . " [Müşteri Tercihi Uzman: {$provider_name}]");
                    }

                    $changes_payload = [
                        'action' => 'create',
                        'channel' => $channel,
                        'sender_id' => $sender_id,
                        'service_id' => $service_id,
                        'service_name' => $service_name,
                        'provider_id' => $provider_id ?: null,
                        'provider_name' => $provider_name ?: null,
                        'start_datetime' => $start_datetime,
                        'customer_name' => $customer_name,
                        'customer_phone' => $customer_phone,
                        'customer_email' => $customer_email,
                        'customer_id' => $matched_user['id'] ?? null,
                        'notes' => $notes_with_provider,
                    ];

                    $this->CI->db->insert('ai_agent_pending_changes', [
                        'target_table' => 'appointments',
                        'target_id' => 0,
                        'changes' => json_encode($changes_payload, JSON_UNESCAPED_UNICODE),
                        'reason' => "[{$channel}] " . $reason . " ({$customer_name} - {$service_name}" . ($is_provider_requested ? " / {$provider_name}" : '') . " @ {$start_datetime})",
                        'model_name' => $active_provider,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    $return_note = 'Yeni randevu talebi yönetici onay kuyruğuna alındı.';
                    if (!$is_provider_requested) {
                        $return_note .= ' (KATI KURAL: Müşteri özellikle uzman talep etmediği için cevabınızda KESİNLİKLE uzman/terapist ismi belirtmeyiniz! Yalnızca hizmet adı, tarih ve saati bildiriniz.)';
                    } else {
                        $return_note .= " (Talep edilen uzman: {$provider_name})";
                    }

                    return [
                        'queued' => true,
                        'pending_id' => $this->CI->db->insert_id(),
                        'note' => $return_note,
                    ];

                case 'propose_appointment_cancel':
                    $appointment_id = (int) ($args['appointment_id'] ?? 0);
                    $reason = (string) ($args['reason'] ?? "{$channel} üzerinden iptal talebi");

                    if (empty($appointment_id)) {
                        return ['error' => 'appointment_id zorunludur.'];
                    }

                    $changes_payload = [
                        'action' => 'cancel',
                        'channel' => $channel,
                        'sender_id' => $sender_id,
                        'appointment_id' => $appointment_id,
                        'customer_id' => $matched_user['id'] ?? null,
                        'reason' => $reason,
                    ];

                    $this->CI->db->insert('ai_agent_pending_changes', [
                        'target_table' => 'appointments',
                        'target_id' => $appointment_id,
                        'changes' => json_encode($changes_payload, JSON_UNESCAPED_UNICODE),
                        'reason' => "[{$channel}] " . $reason . " (Randevu #{$appointment_id})",
                        'model_name' => $active_provider,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    return [
                        'queued' => true,
                        'pending_id' => $this->CI->db->insert_id(),
                        'note' => 'Randevu iptal talebi yönetici onay kuyruğuna alındı.',
                    ];

                case 'propose_appointment_reschedule':
                    $appointment_id = (int) ($args['appointment_id'] ?? 0);
                    $new_start_datetime = (string) ($args['new_start_datetime'] ?? '');
                    $reason = (string) ($args['reason'] ?? "{$channel} üzerinden saat değişikliği talebi");

                    if (empty($appointment_id) || empty($new_start_datetime)) {
                        return ['error' => 'appointment_id ve new_start_datetime zorunludur.'];
                    }

                    $changes_payload = [
                        'action' => 'reschedule',
                        'channel' => $channel,
                        'sender_id' => $sender_id,
                        'appointment_id' => $appointment_id,
                        'new_start_datetime' => $new_start_datetime,
                        'customer_id' => $matched_user['id'] ?? null,
                        'reason' => $reason,
                    ];

                    $this->CI->db->insert('ai_agent_pending_changes', [
                        'target_table' => 'appointments',
                        'target_id' => $appointment_id,
                        'changes' => json_encode($changes_payload, JSON_UNESCAPED_UNICODE),
                        'reason' => "[{$channel}] " . $reason . " (Randevu #{$appointment_id} -> {$new_start_datetime})",
                        'model_name' => $active_provider,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    return [
                        'queued' => true,
                        'pending_id' => $this->CI->db->insert_id(),
                        'note' => 'Randevu değişiklik talebi yönetici onay kuyruğuna alındı.',
                    ];

                case 'propose_customer_update':
                    $customer_id = (int) ($args['customer_id'] ?? ($matched_user['id'] ?? 0));
                    $changes = (array) ($args['changes'] ?? []);
                    $reason = (string) ($args['reason'] ?? "{$channel} üzerinden müşteri bilgi güncelleme");

                    if (empty($customer_id) || empty($changes)) {
                        return ['error' => 'customer_id ve changes zorunludur.'];
                    }

                    if (!empty($matched_user['id']) && (int) $matched_user['id'] !== $customer_id) {
                        return ['error' => 'Yetkisiz müşteri ID güncelleme talebi engellendi.'];
                    }

                    $filtered = array_intersect_key($changes, array_flip(self::ALLOWED_UPDATE_FIELDS));
                    if (empty($filtered)) {
                        return ['error' => 'Güncellenebilir geçerli alan bulunamadı.'];
                    }

                    $this->CI->db->insert('ai_agent_pending_changes', [
                        'target_table' => 'users',
                        'target_id' => $customer_id,
                        'changes' => json_encode($filtered, JSON_UNESCAPED_UNICODE),
                        'reason' => "[{$channel}] " . $reason,
                        'model_name' => $active_provider,
                        'status' => 'pending',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);

                    return [
                        'queued' => true,
                        'pending_id' => $this->CI->db->insert_id(),
                        'note' => 'Müşteri bilgisi güncelleme talebi yönetici onay kuyruğuna alındı.',
                    ];

                case 'request_human_handoff':
                    $reason = trim((string) ($args['reason'] ?? 'Müşteri canlı destek / temsilci talep etti'));
                    $msg = $this->trigger_handoff($channel, $sender_id, $matched_user['id'] ?? null, $reason);
                    return [
                        'success' => true,
                        'handoff_triggered' => true,
                        'message' => $msg,
                    ];

                default:
                    return ['error' => 'Geçersiz araç: ' . $name];
            }
        } catch (Throwable $e) {
            log_message('error', 'Ai_channel_responder execute_tool failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Ensure the handoff tracking table exists in the current tenant database.
     */
    private function ensure_handoff_table_exists(): void
    {
        static $checked = false;
        if ($checked) {
            return;
        }

        try {
            if (!$this->CI->db->table_exists('ai_channel_handoffs')) {
                $this->CI->db->query("
                    CREATE TABLE IF NOT EXISTS `ea_ai_channel_handoffs` (
                      `id` int unsigned NOT NULL AUTO_INCREMENT,
                      `channel` varchar(32) NOT NULL,
                      `sender_id` varchar(64) NOT NULL,
                      `id_users` int unsigned DEFAULT NULL,
                      `status` enum('active','resolved') NOT NULL DEFAULT 'active',
                      `reason` text DEFAULT NULL,
                      `paused_until` datetime NOT NULL,
                      `created_at` datetime NOT NULL,
                      `resolved_at` datetime DEFAULT NULL,
                      `resolved_by` int unsigned DEFAULT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `uniq_channel_sender` (`channel`, `sender_id`),
                      KEY `idx_status_paused` (`status`, `paused_until`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            }
        } catch (Throwable $e) {
            log_message('error', 'ensure_handoff_table_exists failed: ' . $e->getMessage());
        }

        $checked = true;
    }

    /**
     * Check if a human handoff session is currently active for this channel + sender.
     */
    public function is_handoff_active(string $channel, string $sender_id): bool
    {
        $this->ensure_handoff_table_exists();
        try {
            $now = date('Y-m-d H:i:s');
            $row = $this->CI->db
                ->where('channel', $channel)
                ->where('sender_id', $sender_id)
                ->where('status', 'active')
                ->where('paused_until >', $now)
                ->get('ai_channel_handoffs')
                ->row_array();

            return !empty($row);
        } catch (Throwable $e) {
            log_message('error', 'is_handoff_active failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trigger a human handoff: pauses AI auto-replies for this customer and notifies staff.
     */
    public function trigger_handoff(string $channel, string $sender_id, ?int $user_id, string $reason, int $hours = 24): string
    {
        $this->ensure_handoff_table_exists();
        $now = date('Y-m-d H:i:s');
        $paused_until = date('Y-m-d H:i:s', strtotime("+{$hours} hours"));

        try {
            $existing = $this->CI->db
                ->where('channel', $channel)
                ->where('sender_id', $sender_id)
                ->get('ai_channel_handoffs')
                ->row_array();

            if ($existing) {
                $this->CI->db->update('ai_channel_handoffs', [
                    'id_users' => $user_id ?: ($existing['id_users'] ?? null),
                    'status' => 'active',
                    'reason' => $reason,
                    'paused_until' => $paused_until,
                    'resolved_at' => null,
                    'resolved_by' => null,
                ], ['id' => $existing['id']]);
            } else {
                $this->CI->db->insert('ai_channel_handoffs', [
                    'channel' => $channel,
                    'sender_id' => $sender_id,
                    'id_users' => $user_id,
                    'status' => 'active',
                    'reason' => $reason,
                    'paused_until' => $paused_until,
                    'created_at' => $now,
                ]);
            }

            // Record a pending item in ai_agent_pending_changes so staff dashboard shows it
            if ($this->CI->db->table_exists('ai_agent_pending_changes')) {
                $channel_label = ucfirst($channel);
                $this->CI->db->insert('ai_agent_pending_changes', [
                    'target_table' => 'ai_channel_handoffs',
                    'target_id' => $user_id ?: 0,
                    'changes' => json_encode([
                        'channel' => $channel,
                        'sender_id' => $sender_id,
                        'reason' => $reason,
                        'action' => 'human_handoff',
                        'paused_until' => $paused_until,
                    ], JSON_UNESCAPED_UNICODE),
                    'reason' => "[{$channel_label} Canlı Destek Talebi] {$reason} (Gönderici: {$sender_id})",
                    'model_name' => 'handoff_protocol',
                    'status' => 'pending',
                    'created_at' => $now,
                ]);
            }
        } catch (Throwable $e) {
            log_message('error', 'trigger_handoff failed: ' . $e->getMessage());
        }

        return "Talebinizi yetkili ekibimize aktardım. En kısa sürede bir müşteri temsilcimiz sizinle bu hat üzerinden iletişime geçecektir. 👤";
    }

    /**
     * Resolve handoff and re-enable AI for this customer.
     */
    public function resolve_handoff(string $channel, string $sender_id, ?int $resolved_by = null): bool
    {
        $this->ensure_handoff_table_exists();
        try {
            $this->CI->db->update('ai_channel_handoffs', [
                'status' => 'resolved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'resolved_by' => $resolved_by,
            ], [
                'channel' => $channel,
                'sender_id' => $sender_id,
            ]);
            return true;
        } catch (Throwable $e) {
            log_message('error', 'resolve_handoff failed: ' . $e->getMessage());
            return false;
        }
    }
}
