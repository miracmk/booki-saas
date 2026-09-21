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

        $booking_url = site_url('booking');

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
            ];

            foreach ($tool_calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $result = $this->execute_tool($name, $args, $channel, $sender_id, $matched_user);

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
     * Try to auto match customer if a phone number is detected in text.
     */
    private function try_auto_match_phone(string $text, string $channel, string $sender_id): ?array
    {
        $this->CI->load->model('customers_model');

        // Look for 10-11 digit phone number patterns
        if (preg_match('/(\+?90|0)?\s*(5\d{2})[\s.-]*(\d{3})[\s.-]*(\d{2})[\s.-]*(\d{2})/', $text, $matches)) {
            $clean_phone = '0' . $matches[2] . $matches[3] . $matches[4] . $matches[5];
            $found = $this->CI->customers_model->search($clean_phone, 1);
            if (!empty($found[0])) {
                $customer = $found[0];
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

        $company_name = setting('company_name') ?: 'İşletmemiz';
        $company_phone = setting('company_phone') ?: '';
        $company_address = setting('company_address') ?: 'İşletme Adresi';
        $booking_url = site_url('booking');
        $current_datetime = date('Y-m-d H:i:s');
        $current_day_name = match (date('N')) {
            '1' => 'Pazartesi',
            '2' => 'Salı',
            '3' => 'Çarşamba',
            '4' => 'Perşembe',
            '5' => 'Cuma',
            '6' => 'Cumartesi',
            '7' => 'Pazar',
            default => '',
        };

        // Fetch available services
        $services = $CI->services_model->get_available_services();
        $services_summary = [];
        foreach (array_slice($services, 0, 8) as $s) {
            $price = !empty($s['price']) ? $s['price'] . ' TL' : 'Ücretsiz / Bilgi alınız';
            $duration = !empty($s['duration']) ? $s['duration'] . ' dk' : '30 dk';
            $services_summary[] = "- [ID: {$s['id']}] {$s['name']} (Süre: {$duration}, Fiyat: {$price})";
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

        if (!empty($matched_user['id'])) {
            $is_recognized = true;
            $name = trim(($matched_user['first_name'] ?? '') . ' ' . ($matched_user['last_name'] ?? ''));
            $phone = $matched_user['phone_number'] ?? $sender_id;
            $email = $matched_user['email'] ?? '';
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

            $customer_context = <<<CUST
TANINAN MÜŞTERİ BİLGİLERİ (KAYITLI & EŞLEŞMİŞ):
- ID: {$id}
- İsim: {$name}
- Telefon: {$phone}
- E-posta: {$email}
- Yaklaşan Randevuları:
{$upcoming_text}
CUST;
        }

        $channel_name = match ($channel) {
            'whatsapp' => 'WhatsApp',
            'telegram' => 'Telegram',
            'instagram' => 'Instagram Direct',
            default => 'Mesajlaşma Kanalı',
        };

        $recognition_instructions = $is_recognized
            ? "Müşteri sistemimizde kayıtlıdır ({$matched_user['first_name']} {$matched_user['last_name']}). Eğer konuşma sıfırdan yeni başlıyorsa nazikçe ismiyle hitap et. Ancak devam eden bir konuşmanın ortasındaysanız (örneğin müşteri telefon numarasını ya da randevu detaylarını az önce iletmişse) tekrar 'hoş geldiniz / size nasıl yardımcı olabilirim' gibi sıfırlama ifadeleri kullanma; müşterinin önceki mesajlarında talep ettiği randevu/hizmet akışını (propose_appointment_create vb.) kesintisiz sürdür."
            : "Müşteri bu kanaldan ({$channel_name}) henüz eşleşmemiştir. Eğer müşteri randevularını sorgulamak, değiştirmek veya yeni randevu almak isterse, sistemdeki kaydını bulabilmemiz için kibarca telefon numarasını iste (Örn: \"Size daha iyi yardımcı olabilmem ve randevularınızı görüntüleyebilmem için kayıtlı telefon numaranızı paylaşabilir misiniz?\"). Müşteri numarasını yazdığında hemen `link_customer_channel` aracını çağır ve talep ettiği randevu işlemini tamamla.";

        return <<<PROMPT
Sen "{$company_name}" işletmesinin {$channel_name} üzerindeki resmi, nazik ve akıllı yapay zeka asistanısın.

GÜNCEL ZAMAN:
- Sistem Zamanı: {$current_datetime} ({$current_day_name})

İŞLETME BİLGİLERİ:
- İşletme Adı: {$company_name}
- Telefon: {$company_phone}
- Adres: {$company_address}
- Online Randevu Bağlantısı: {$booking_url}

MEVCUT HİZMETLER:
{$services_text}

UZMANLAR / TERAPİSTLER:
{$providers_text}

{$customer_context}

MÜŞTERİ TANIMA VE KANAL EŞLEŞTİRME:
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
   - Müşteri hizmet, tarih/saat, isim ve telefon belirttiğinde (uzman belirtilmişse uzmanıyla birlikte) `propose_appointment_create` aracını çağırarak talebi yönetici onay kuyruğuna ilet.
   - Müşteri uzman belirtmemişse, sisteme otomatik atat ve müşteriye cevabında ASLA uzman adı geçirme.
   - Müşteriye yanıtında: Randevu talebinin (hizmet, tarih ve saat; yalnızca müşteri özellikle talep ettiyse uzmanıyla) alındığını, yönetici onayından sonra randevunun kesinleşeceğini bildir.

2. **RANDEVU DEĞİŞTİRME / SAAT GÜNCELLEME:**
   - Tanınan müşterinin yaklaşan randevularındaki [Randevu ID] ve istenen yeni tarih/saat ile `propose_appointment_reschedule` aracını çağır.
   - Değişiklik talebinin yönetici onayına iletildiğini bildir.

3. **RANDEVU İPTALİ / SİLME:**
   - İlgili [Randevu ID] ile `propose_appointment_cancel` aracını çağır.
   - İptal talebinin yönetici onayına iletildiğini bildir.

4. **BİLGİ GÜNCELLEME:**
   - Profil bilgisi (telefon, e-posta, not) değişikliğinde `propose_customer_update` aracını çağır.

5. **ONLİNE RANDEVU SEÇENEĞİ:**
   - Müsait saatleri canlı görüp anında randevu almak isteyenlere linki sun: {$booking_url}

6. **ÇALIŞMA SAATLERİ, AÇIKLIK VE MÜSAİTLİK SORULARI (ÖNEMLİ KURAL):**
   - Müşteri "bugün açık mısınız?", "çalışıyor musunuz?", "saat kaçta açılıyorsunuz?", "hafta sonu açık mısınız?" veya belirli bir terapistin (örn: Nur Hanım) müsaitliğini sorduğunda KENDİ KAFANDAN TAHMİN YAPMA VE "asistan olarak her zaman buradayım" GİBİ GEÇİŞTİRİCİ CEVAP VERME.
   - MUTLAKA ilk adım olarak `check_availability` aracını çağır!
   - Bu araç işletmenin ve terapistlerin gerçek çalışma takvimini, kapalı günlerini ve boş randevu saatlerini hesaplar.
   - Araç "kapalıdır" veya "müsait randevu saati bulunmamaktadır" döndürürse: Müşteriye bugün kapalı olduğumuzu veya o tarihte randevu bulunmadığını nazikçe açıkla ve aracın önerdiği en yakın açık iş gününü (örn: Pazartesi) ve saatleri teklif et.
   - Müşteri doğrudan randevu almak istediğinde de önce veya randevu teklifi sırasında saatin uygunluğunu `check_availability` ile doğrula.

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

                default:
                    return ['error' => 'Geçersiz araç: ' . $name];
            }
        } catch (Throwable $e) {
            log_message('error', 'Ai_channel_responder execute_tool failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
