<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Multi-Channel AI Assistant Responder Library.
 *
 * Handles inbound customer messages from WhatsApp, Telegram, and Instagram.
 * Provides automated AI responses using read-only customer & business context.
 *
 * SECURITY INVARIANT:
 * Customer inbound messages NEVER trigger direct DB mutations or write tools.
 * The only action tool permitted is `propose_customer_update`, which queues
 * changes to `ai_agent_pending_changes` requiring human admin approval.
 *
 * @package Libraries
 * ---------------------------------------------------------------------------- */

class Ai_channel_responder
{
    private const MAX_TOOL_ITERATIONS = 4;

    private const ALLOWED_UPDATE_FIELDS = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'notes'];

    private const TOOLS = [
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
    ];

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

        $api_key = getenv('OPENROUTER_API_KEY');
        $booking_url = site_url('booking');

        // If no API key configured, provide a safe friendly fallback
        if (empty($api_key)) {
            $company_name = setting('company_name') ?: 'İşletmemiz';
            return "Merhaba! {$company_name} AI asistanına ulaştınız. Mesajınız ekibimize iletildi, en kısa sürede size dönüş yapılacaktır.\n\nRandevu almak veya hizmetlerimizi incelemek için: {$booking_url}";
        }

        $model = getenv('AI_AGENT_MODEL') ?: 'openrouter/free';
        $system_prompt = $this->build_system_prompt($channel, $matched_user);

        $conversation = [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $clean_text],
        ];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->call_openrouter($conversation, $model, $api_key);

            if ($response === null) {
                $company_name = setting('company_name') ?: 'İşletmemiz';
                return "Merhaba! Mesajınız ekibimize iletildi. Randevu almak veya müsaitlik durumunu görmek için bağlantımızı ziyaret edebilirsiniz:\n{$booking_url}";
            }

            $msg = $response['choices'][0]['message'] ?? null;
            if ($msg === null) {
                break;
            }

            $tool_calls = $msg['tool_calls'] ?? [];
            if (empty($tool_calls)) {
                $reply = trim((string) ($msg['content'] ?? ''));
                return $reply !== '' ? $reply : null;
            }

            // Execute safe tool call (propose_customer_update only)
            $conversation[] = $msg;

            foreach ($tool_calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = $this->execute_tool($name, $args, $channel, $matched_user);

                $conversation[] = [
                    'role' => 'tool',
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return "Mesajınız yetkili ekibimize iletildi. Randevu veya bilgi için bağlantımızı ziyaret edebilirsiniz:\n{$booking_url}";
    }

    /**
     * Build the system prompt with rich read-only business and customer context.
     */
    private function build_system_prompt(string $channel, ?array $matched_user): string
    {
        $CI = &get_instance();
        $CI->load->model('services_model');
        $CI->load->model('appointments_model');

        $company_name = setting('company_name') ?: 'İşletmemiz';
        $company_phone = setting('company_phone') ?: '';
        $company_link = setting('company_link') ?: site_url();
        $booking_url = site_url('booking');

        // Fetch available services (read-only)
        $services = $CI->services_model->get_available_services();
        $services_summary = [];
        foreach (array_slice($services, 0, 15) as $s) {
            $price = !empty($s['price']) ? $s['price'] . ' TL' : 'Ücretsiz / Bilgi alınız';
            $duration = !empty($s['duration']) ? $s['duration'] . ' dk' : '';
            $services_summary[] = "- {$s['name']} ({$duration}, {$price})";
        }
        $services_text = !empty($services_summary) ? implode("\n", $services_summary) : "Hizmet listesi için web sitemizi ziyaret ediniz.";

        // Customer context if recognized
        $customer_context = "Müşteri durumu: Sistemde henüz kayıtlı/eşleşmiş değil (yeni misafir).";
        if (!empty($matched_user['id'])) {
            $name = trim(($matched_user['first_name'] ?? '') . ' ' . ($matched_user['last_name'] ?? ''));
            $phone = $matched_user['phone_number'] ?? '';
            $email = $matched_user['email'] ?? '';
            $id = $matched_user['id'];

            // Fetch upcoming appointments
            $upcoming_text = 'Yok';
            $upcoming = $CI->db
                ->select('appointments.*, services.name AS service_name, CONCAT(users.first_name, " ", users.last_name) AS provider_name')
                ->from('appointments')
                ->join('services', 'services.id = appointments.id_services', 'left')
                ->join('users', 'users.id = appointments.id_users_provider', 'left')
                ->where('appointments.id_users_customer', $id)
                ->where('appointments.start_datetime >=', date('Y-m-d H:i:s'))
                ->order_by('appointments.start_datetime', 'ASC')
                ->limit(3)
                ->get()
                ->result_array();

            if (!empty($upcoming)) {
                $up_lines = [];
                foreach ($upcoming as $app) {
                    $up_lines[] = "- {$app['start_datetime']}: {$app['service_name']} (Uzman: {$app['provider_name']})";
                }
                $upcoming_text = implode("\n", $up_lines);
            }

            $customer_context = <<<CUST
Tanınan Müşteri Bilgileri:
- ID: {$id}
- Ad Soyad: {$name}
- Telefon: {$phone}
- E-posta: {$email}
- Yaklaşan Randevular:
{$upcoming_text}
CUST;
        }

        $channel_name = ucfirst($channel);

        return <<<PROMPT
Sen "{$company_name}" işletmesinin sanal AI Asistanısın. Müşteriler sana {$channel_name} kanalı üzerinden mesaj gönderiyor.

GÖREVLERİN:
1. Müşteriyi kibar, yardımsever ve profesyonel bir dille Türkçe karşıla.
2. İşletme ve hizmetler hakkındaki soruları aşağıdaki bilgilere göre yanıtla.
3. Randevu almak veya saat seçmek isteyen müşterilere doğrudan online randevu bağlantısını ver: {$booking_url}
4. Tanınan müşterinin yaklaşan randevusu varsa ve randevusunu soruyorsa randevu tarih/saat/hizmet bilgisini bildir.
5. GÜVENLİK KURALI (KESİNLİKLE UYULMASI GEREKEN KURAL):
   - Sen veritabanında ASLA doğrudan randevu oluşturamaz, değiştiremez veya silemezsin.
   - Müşteri telefon numarası, e-posta veya iletişim bilgisini güncellemek isterse, `propose_customer_update` aracını çağır. Müşteriye "Bilgi güncelleme talebiniz alındı, yetkili personelimiz onayladıktan sonra sistemde güncellenecektir" şeklinde bilgi ver.
   - Randevu iptali veya ertelemesi isteyen müşteriye: "Randevu değişiklik veya iptal talebinizi aldım, onay ve işlem için yetkili ekibimize ilettim. Acil durumlar için işletmemizle iletişime geçebilirsiniz." şeklinde yanıt ver.
6. Yanıtlarında gereksiz uzun açıklamalardan kaçın, mesajlaşma uygulamasına (WhatsApp/Telegram/Instagram) uygun net ve okunaklı paragraflar kullan.

İŞLETME BİLGİLERİ:
- İşletme Adı: {$company_name}
- Telefon: {$company_phone}
- Web / Randevu Linki: {$booking_url}

SUNULAN HİZMETLER:
{$services_text}

{$customer_context}
PROMPT;
    }

    /**
     * Safely execute tools (propose_customer_update only).
     */
    private function execute_tool(string $name, array $args, string $channel, ?array $matched_user): array
    {
        if ($name !== 'propose_customer_update') {
            return ['error' => 'Bu işlem için yetkiniz bulunmamaktadır.'];
        }

        $CI = &get_instance();
        $CI->load->model('customers_model');

        $customer_id = (int) ($args['customer_id'] ?? ($matched_user['id'] ?? 0));
        $changes = (array) ($args['changes'] ?? []);
        $reason = (string) ($args['reason'] ?? "Müşteri {$channel} üzerinden talep etti.");

        if (empty($customer_id)) {
            return ['error' => 'Kayıtlı müşteri bulunamadı.'];
        }

        $filtered = array_intersect_key($changes, array_flip(self::ALLOWED_UPDATE_FIELDS));
        if (empty($filtered)) {
            return ['error' => 'Güncellenebilir geçerli alan bulunamadı.'];
        }

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'users',
            'target_id' => $customer_id,
            'changes' => json_encode($filtered, JSON_UNESCAPED_UNICODE),
            'reason' => "{$channel} kanalından otomatik asistan aracılığıyla talep edildi: {$reason}",
            'model_name' => getenv('AI_AGENT_MODEL') ?: 'openrouter/free',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'status' => 'queued_for_approval',
            'customer_id' => $customer_id,
            'message' => 'Değişiklik önerisi yönetici onayı için kuyruğa eklendi.',
        ];
    }

    /**
     * Call OpenRouter completion endpoint.
     */
    private function call_openrouter(array $messages, string $model, string $api_key): ?array
    {
        try {
            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://openrouter.ai/api/v1/chat/completions',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode([
                    'model' => $model,
                    'messages' => $messages,
                    'tools' => self::TOOLS,
                    'temperature' => 0.4,
                    'max_tokens' => 800,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $api_key,
                    'Content-Type: application/json',
                    'HTTP-Referer: https://' . (getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co'),
                    'X-Title: BooKi Multi-Channel AI Assistant',
                ],
            ]);

            $response = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $error = curl_error($curl);

            curl_close($curl);

            if ($error) {
                log_message('error', 'Ai_channel_responder cURL error: ' . $error);
                return null;
            }

            if ($http_code !== 200) {
                log_message('error', 'Ai_channel_responder OpenRouter error (HTTP ' . $http_code . '): ' . $response);
                return null;
            }

            $data = json_decode($response, true);

            return is_array($data) ? $data : null;
        } catch (Throwable $e) {
            log_message('error', 'Ai_channel_responder OpenRouter call failed: ' . $e->getMessage());

            return null;
        }
    }
}
