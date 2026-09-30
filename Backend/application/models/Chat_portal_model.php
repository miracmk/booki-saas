<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Unified Omnichannel Chat Portal Model
 *
 * Aggregates conversations across:
 * - BooKi AI Copilot / Asistan (Pinned at top, persistent memory)
 * - WhatsApp (Cloud API & QR Baileys bridge)
 * - Instagram Direct Messages
 * - Telegram Bot
 * - Web Chat Widget
 *
 * Supports Human-AI Handoff protocol for every channel.
 * ---------------------------------------------------------------------------- */

class Chat_portal_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Fetch all threads aggregated and sorted by latest message timestamp.
     * The BooKi AI Assistant is always pinned at the top.
     */
    public function get_threads(?string $channel_filter = null, ?string $search = null, int $user_id = 1): array
    {
        $threads = [];

        // 1. PINNED: BooKi AI Copilot / Asistan
        if (empty($channel_filter) || $channel_filter === 'ai') {
            $ai_thread = $this->get_ai_assistant_thread($user_id);
            if (!empty($ai_thread)) {
                if (empty($search) || stripos($ai_thread['name'], $search) !== false || stripos($ai_thread['last_message'], $search) !== false) {
                    $threads[] = $ai_thread;
                }
            }
        }

        // 2. WhatsApp Threads
        if ((empty($channel_filter) || $channel_filter === 'whatsapp') && $this->db->table_exists('whatsapp_messages')) {
            $wa_threads = $this->get_whatsapp_threads($search);
            $threads = array_merge($threads, $wa_threads);
        }

        // 3. Instagram Threads
        if ((empty($channel_filter) || $channel_filter === 'instagram') && $this->db->table_exists('instagram_messages')) {
            $ig_threads = $this->get_instagram_threads($search);
            $threads = array_merge($threads, $ig_threads);
        }

        // 4. Telegram Threads
        if ((empty($channel_filter) || $channel_filter === 'telegram') && $this->db->table_exists('telegram_messages')) {
            $tg_threads = $this->get_telegram_threads($search);
            $threads = array_merge($threads, $tg_threads);
        }

        // 5. Web Chat Widget Threads
        if ((empty($channel_filter) || $channel_filter === 'widget') && $this->db->table_exists('webchat_messages')) {
            $widget_threads = $this->get_widget_threads($search);
            $threads = array_merge($threads, $widget_threads);
        }

        // Separate pinned thread (AI Assistant) and sort remaining by last_time DESC
        $pinned = [];
        $unpinned = [];
        foreach ($threads as $t) {
            if (!empty($t['pinned'])) {
                $pinned[] = $t;
            } else {
                $unpinned[] = $t;
            }
        }

        usort($unpinned, static function ($a, $b) {
            return strcmp($b['last_time_raw'] ?? '', $a['last_time_raw'] ?? '');
        });

        return array_merge($pinned, $unpinned);
    }

    /**
     * BooKi AI Copilot pinned thread.
     */
    private function get_ai_assistant_thread(int $user_id): array
    {
        $conv = null;
        if ($this->db->table_exists('ai_agent_conversations')) {
            $conv = $this->db->where('title', 'BooKi AI Copilot Portalı')->order_by('id', 'DESC')->get('ai_agent_conversations')->row_array();
            if (!$conv) {
                $this->db->insert('ai_agent_conversations', [
                    'id_users' => $user_id,
                    'title' => 'BooKi AI Copilot Portalı',
                    'summary' => 'Birleşik Chat Portalı Üzerinden AI Copilot İletişimi',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $conv_id = $this->db->insert_id();
                $conv = $this->db->where('id', $conv_id)->get('ai_agent_conversations')->row_array();
            }
        }

        $last_msg = 'Nasıl yardımcı olabilirim?';
        $last_time = date('Y-m-d H:i:s');
        if (!empty($conv['id']) && $this->db->table_exists('ai_agent_messages')) {
            $m = $this->db->where('id_conversation', $conv['id'])->order_by('id', 'DESC')->limit(1)->get('ai_agent_messages')->row_array();
            if ($m) {
                $last_msg = $m['content'];
                $last_time = $m['created_at'];
            }
        }

        return [
            'id' => 'ai_assistant',
            'thread_id' => 'ai_copilot',
            'channel' => 'ai',
            'pinned' => true,
            'name' => 'BooKi AI Asistan',
            'subtitle' => '7/24 Akıllı Yönetim & Handoff Yöneticisi',
            'avatar_icon' => 'fas fa-robot',
            'avatar_color' => 'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)',
            'channel_icon' => 'fas fa-robot text-purple',
            'channel_name' => 'AI Copilot',
            'badge_bg' => 'bg-purple text-white',
            'last_message' => mb_strimwidth($last_msg, 0, 70, '...'),
            'last_time' => $this->format_relative_time($last_time),
            'last_time_raw' => $last_time,
            'unread_count' => 0,
            'handoff_status' => 'ai_active', // always AI
            'phone' => '',
            'customer_id' => null,
        ];
    }

    /**
     * WhatsApp threads grouped by wa_id.
     */
    private function get_whatsapp_threads(?string $search = null): array
    {
        if (!$this->db->table_exists('whatsapp_messages')) {
            return [];
        }

        $sql = "
            SELECT m1.*, u.first_name, u.last_name, u.email as user_email, u.id as matched_user_id
            SELECT m1.*
            FROM ea_whatsapp_messages m1
            INNER JOIN (
                SELECT wa_id, MAX(id) as max_id
                FROM ea_whatsapp_messages
                GROUP BY wa_id
            ) m2 ON m1.id = m2.max_id
            LEFT JOIN ea_users u ON (m1.id_users = u.id OR u.phone_number LIKE CONCAT('%', SUBSTRING(m1.wa_id, -10)))
            ORDER BY m1.created_at DESC
            LIMIT 100
        ";

        $rows = $this->db->query($sql)->result_array();
        $threads = [];
        $seen_norm_phones = [];

        foreach ($rows as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            $raw_phone = preg_replace('/\D+/', '', (string) $r['wa_id']);
            $norm_phone = (strlen($raw_phone) > 10) ? substr($raw_phone, -10) : $raw_phone;

            if (!empty($norm_phone) && isset($seen_norm_phones[$norm_phone])) {
                continue; // Prevent duplicate threads for different format variations (0543... vs 90543...)
            }
            if (!empty($norm_phone)) {
                $seen_norm_phones[$norm_phone] = true;
            }

            // Find matching user (prefer customer role id_roles = 3, strictly 1 row)
            $matched_user = null;
            if (!empty($r['id_users'])) {
                $matched_user = $this->db->where('id', (int) $r['id_users'])->limit(1)->get('users')->row_array();
            }
            if (!$matched_user && !empty($norm_phone)) {
                $matched_user = $this->db->select('id, first_name, last_name, email, phone_number')
                    ->from('users')
                    ->like('phone_number', $norm_phone)
                    ->order_by('CASE WHEN id_roles = 3 THEN 0 ELSE 1 END', 'ASC')
                    ->order_by('id', 'ASC')
                    ->limit(1)
                    ->get()
                    ->row_array();
            }

            $name = trim(($matched_user['first_name'] ?? '') . ' ' . ($matched_user['last_name'] ?? ''));
            if (empty($name)) {
                $name = '+' . ltrim($r['wa_id'], '+');
            }

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($r['wa_id'], $search) === false && stripos($r['message'], $search) === false) {
                    continue;
                }
            }

            $handoff_active = $this->is_handoff_active('whatsapp', $r['wa_id']);

            $threads[] = [
                'id' => 'whatsapp_' . $r['wa_id'],
                'thread_id' => $r['wa_id'],
                'channel' => 'whatsapp',
                'pinned' => false,
                'name' => $name,
                'subtitle' => '+' . ltrim($r['wa_id'], '+'),
                'avatar_icon' => 'fab fa-whatsapp',
                'avatar_color' => '#25D366',
                'channel_icon' => 'fab fa-whatsapp text-success',
                'channel_name' => 'WhatsApp',
                'badge_bg' => 'bg-success text-white',
                'last_message' => mb_strimwidth($r['message'], 0, 70, '...'),
                'last_time' => $this->format_relative_time($r['created_at']),
                'last_time_raw' => $r['created_at'],
                'unread_count' => ($r['direction'] === 'in') ? 1 : 0,
                'handoff_status' => $handoff_active ? 'human_handoff' : 'ai_active',
                'phone' => '+' . ltrim($r['wa_id'], '+'),
                'customer_id' => $r['matched_user_id'] ?? null,
                'customer_id' => $matched_user['id'] ?? null,
            ];
        }

        return $threads;
    }

    /**
     * Instagram threads grouped by instagram_user_id.
     */
    private function get_instagram_threads(?string $search = null): array
    {
        $sql = "
            SELECT m1.*, u.first_name, u.last_name, u.id as matched_user_id
            FROM ea_instagram_messages m1
            INNER JOIN (
                SELECT instagram_user_id, MAX(id) as max_id
                FROM ea_instagram_messages
                GROUP BY instagram_user_id
            ) m2 ON m1.id = m2.max_id
            LEFT JOIN ea_users u ON m1.id_users = u.id
            ORDER BY m1.created_at DESC
            LIMIT 50
        ";

        $rows = $this->db->query($sql)->result_array();
        $threads = [];

        foreach ($rows as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if (empty($name)) {
                $name = '@' . $r['instagram_user_id'];
            }

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($r['message'], $search) === false) {
                    continue;
                }
            }

            $handoff_active = $this->is_handoff_active('instagram', $r['instagram_user_id']);

            $threads[] = [
                'id' => 'instagram_' . $r['instagram_user_id'],
                'thread_id' => $r['instagram_user_id'],
                'channel' => 'instagram',
                'pinned' => false,
                'name' => $name,
                'subtitle' => 'Instagram Direct',
                'avatar_icon' => 'fab fa-instagram',
                'avatar_color' => '#E1306C',
                'channel_icon' => 'fab fa-instagram text-danger',
                'channel_name' => 'Instagram',
                'badge_bg' => 'bg-danger text-white',
                'last_message' => mb_strimwidth($r['message'], 0, 70, '...'),
                'last_time' => $this->format_relative_time($r['created_at']),
                'last_time_raw' => $r['created_at'],
                'unread_count' => ($r['direction'] === 'in') ? 1 : 0,
                'handoff_status' => $handoff_active ? 'human_handoff' : 'ai_active',
                'phone' => '',
                'customer_id' => $r['matched_user_id'] ?? null,
            ];
        }

        return $threads;
    }

    /**
     * Telegram threads grouped by chat_id.
     */
    private function get_telegram_threads(?string $search = null): array
    {
        $sql = "
            SELECT m1.*, u.first_name, u.last_name, u.id as matched_user_id
            FROM ea_telegram_messages m1
            INNER JOIN (
                SELECT chat_id, MAX(id) as max_id
                FROM ea_telegram_messages
                GROUP BY chat_id
            ) m2 ON m1.id = m2.max_id
            LEFT JOIN ea_users u ON m1.id_users = u.id
            ORDER BY m1.created_at DESC
            LIMIT 50
        ";

        $rows = $this->db->query($sql)->result_array();
        $threads = [];

        foreach ($rows as $r) {
            $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            if (empty($name)) {
                $name = 'Telegram #' . $r['chat_id'];
            }

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($r['message'], $search) === false) {
                    continue;
                }
            }

            $handoff_active = $this->is_handoff_active('telegram', $r['chat_id']);

            $threads[] = [
                'id' => 'telegram_' . $r['chat_id'],
                'thread_id' => $r['chat_id'],
                'channel' => 'telegram',
                'pinned' => false,
                'name' => $name,
                'subtitle' => 'Telegram Bot',
                'avatar_icon' => 'fab fa-telegram-plane',
                'avatar_color' => '#0088cc',
                'channel_icon' => 'fab fa-telegram-plane text-info',
                'channel_name' => 'Telegram',
                'badge_bg' => 'bg-info text-white',
                'last_message' => mb_strimwidth($r['message'], 0, 70, '...'),
                'last_time' => $this->format_relative_time($r['created_at']),
                'last_time_raw' => $r['created_at'],
                'unread_count' => ($r['direction'] === 'in') ? 1 : 0,
                'handoff_status' => $handoff_active ? 'human_handoff' : 'ai_active',
                'phone' => '',
                'customer_id' => $r['matched_user_id'] ?? null,
            ];
        }

        return $threads;
    }

    /**
     * Web Chat Widget threads.
     */
    private function get_widget_threads(?string $search = null): array
    {
        $sql = "
            SELECT m1.*, u.first_name, u.last_name, u.id as matched_user_id
            FROM ea_webchat_messages m1
            INNER JOIN (
                SELECT session_id, MAX(id) as max_id
                FROM ea_webchat_messages
                GROUP BY session_id
            ) m2 ON m1.id = m2.max_id
            LEFT JOIN ea_users u ON m1.id_users = u.id
            ORDER BY m1.created_at DESC
            LIMIT 50
        ";

        $rows = $this->db->query($sql)->result_array();
        $threads = [];

        foreach ($rows as $r) {
            $name = trim(($r['customer_name'] ?? '') ?: (($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')));
            if (empty($name)) {
                $name = 'Web Ziyaretçi #' . substr($r['session_id'], 0, 6);
            }

            if (!empty($search)) {
                if (stripos($name, $search) === false && stripos($r['message'], $search) === false) {
                    continue;
                }
            }

            $handoff_active = $this->is_handoff_active('widget', $r['session_id']);

            $threads[] = [
                'id' => 'widget_' . $r['session_id'],
                'thread_id' => $r['session_id'],
                'channel' => 'widget',
                'pinned' => false,
                'name' => $name,
                'subtitle' => 'Web Chat Widget',
                'avatar_icon' => 'fas fa-globe',
                'avatar_color' => '#0d6efd',
                'channel_icon' => 'fas fa-globe text-primary',
                'channel_name' => 'Web Widget',
                'badge_bg' => 'bg-primary text-white',
                'last_message' => mb_strimwidth($r['message'], 0, 70, '...'),
                'last_time' => $this->format_relative_time($r['created_at']),
                'last_time_raw' => $r['created_at'],
                'unread_count' => ($r['direction'] === 'in') ? 1 : 0,
                'handoff_status' => $handoff_active ? 'human_handoff' : 'ai_active',
                'phone' => $r['customer_phone'] ?? '',
                'customer_id' => $r['matched_user_id'] ?? null,
            ];
        }

        return $threads;
    }

    /**
     * Get conversation message history for a specific thread.
     */
    public function get_messages(string $channel, string $thread_id, int $user_id = 1): array
    {
        $messages = [];

        if ($channel === 'ai' || $thread_id === 'ai_copilot') {
            $conv = $this->db->where('title', 'BooKi AI Copilot Portalı')->order_by('id', 'DESC')->get('ai_agent_conversations')->row_array();
            if ($conv) {
                $rows = $this->db->where('id_conversation', $conv['id'])->order_by('id', 'ASC')->get('ai_agent_messages')->result_array();
                foreach ($rows as $r) {
                    $messages[] = [
                        'id' => $r['id'],
                        'sender' => ($r['role'] === 'user') ? 'agent' : 'ai',
                        'sender_name' => ($r['role'] === 'user') ? 'Siz (Yönetici)' : 'BooKi AI Copilot',
                        'direction' => ($r['role'] === 'user') ? 'out' : 'in',
                        'content' => $r['content'],
                        'time' => date('H:i', strtotime($r['created_at'])),
                        'date' => date('d.m.Y', strtotime($r['created_at'])),
                        'status' => 'delivered',
                    ];
                }
            }
            return $messages;
        }

        if ($channel === 'whatsapp') {
            $rows = $this->db->where('wa_id', $thread_id)->order_by('id', 'ASC')->get('whatsapp_messages')->result_array();
            foreach ($rows as $r) {
                $is_out = ($r['direction'] === 'out');
                $messages[] = [
                    'id' => $r['id'],
                    'sender' => $is_out ? 'agent' : 'customer',
                    'sender_name' => $is_out ? 'İşletme / AI' : 'Müşteri',
                    'direction' => $r['direction'],
                    'content' => $r['message'],
                    'time' => date('H:i', strtotime($r['created_at'])),
                    'date' => date('d.m.Y', strtotime($r['created_at'])),
                    'status' => $r['status'] ?? 'delivered',
                ];
            }
            return $messages;
        }

        if ($channel === 'instagram') {
            $rows = $this->db->where('instagram_user_id', $thread_id)->order_by('id', 'ASC')->get('instagram_messages')->result_array();
            foreach ($rows as $r) {
                $is_out = ($r['direction'] === 'out');
                $messages[] = [
                    'id' => $r['id'],
                    'sender' => $is_out ? 'agent' : 'customer',
                    'sender_name' => $is_out ? 'İşletme / AI' : 'Instagram Müşterisi',
                    'direction' => $r['direction'],
                    'content' => $r['message'],
                    'time' => date('H:i', strtotime($r['created_at'])),
                    'date' => date('d.m.Y', strtotime($r['created_at'])),
                    'status' => $r['status'] ?? 'delivered',
                ];
            }
            return $messages;
        }

        if ($channel === 'telegram') {
            $rows = $this->db->where('chat_id', $thread_id)->order_by('id', 'ASC')->get('telegram_messages')->result_array();
            foreach ($rows as $r) {
                $is_out = ($r['direction'] === 'out');
                $messages[] = [
                    'id' => $r['id'],
                    'sender' => $is_out ? 'agent' : 'customer',
                    'sender_name' => $is_out ? 'İşletme / AI' : 'Telegram Müşterisi',
                    'direction' => $r['direction'],
                    'content' => $r['message'],
                    'time' => date('H:i', strtotime($r['created_at'])),
                    'date' => date('d.m.Y', strtotime($r['created_at'])),
                    'status' => 'delivered',
                ];
            }
            return $messages;
        }

        if ($channel === 'widget') {
            $rows = $this->db->where('session_id', $thread_id)->order_by('id', 'ASC')->get('webchat_messages')->result_array();
            foreach ($rows as $r) {
                $is_out = ($r['direction'] === 'out');
                $messages[] = [
                    'id' => $r['id'],
                    'sender' => $is_out ? 'agent' : 'customer',
                    'sender_name' => $is_out ? 'İşletme / AI' : ($r['customer_name'] ?: 'Web Ziyaretçisi'),
                    'direction' => $r['direction'],
                    'content' => $r['message'],
                    'time' => date('H:i', strtotime($r['created_at'])),
                    'date' => date('d.m.Y', strtotime($r['created_at'])),
                    'status' => $r['status'] ?? 'delivered',
                ];
            }
            return $messages;
        }

        return [];
    }

    /**
     * Send outbound message or dispatch to AI Copilot.
     */
    public function send_message(string $channel, string $thread_id, string $text, int $user_id = 1): array
    {
        $text = trim($text);
        if ($text === '') {
            throw new InvalidArgumentException('Mesaj boş olamaz.');
        }

        if ($channel === 'ai' || $thread_id === 'ai_copilot') {
            return $this->send_to_ai_assistant($text, $user_id);
        }

        if ($channel === 'whatsapp') {
            $this->load->model('messaging_settings_model');
            $this->load->model('whatsapp_messages_model');
            $settings = $this->messaging_settings_model->get_settings();
            $mode = $settings['whatsapp_mode'] ?? 'official';
            $send_status = 'sent';
            $error_message = null;

            if ($mode === 'unofficial') {
                if (!class_exists('Whatsapp_bridge', false)) {
                    $this->load->library('whatsapp_bridge');
                }
                $bridge_url = !empty($settings['whatsapp_bridge_url']) ? $settings['whatsapp_bridge_url'] : (getenv('WA_BRIDGE_URL') ?: 'http://booki-wa:3000');
                $bridge_secret = !empty($settings['whatsapp_bridge_secret']) ? $settings['whatsapp_bridge_secret'] : (getenv('WA_BRIDGE_SECRET') ?: '');
                $bridge = new Whatsapp_bridge($bridge_url, $bridge_secret);

                $context = tenant_context();
                $tenant_key = Whatsapp_bridge::resolve_tenant_key($context['subdomain'] ?? 'salonflora');
                $result = $bridge->send($tenant_key, $thread_id, $text);
                if (empty($result['success'])) {
                    $send_status = 'failed';
                    $error_message = $result['error'] ?? 'Bridge gönderim hatası';
                }
            } else {
                $client = new Whatsapp_client(
                    $settings['whatsapp_phone_number_id'] ?? null,
                    $settings['whatsapp_access_token'] ?? null
                );
                if ($client->is_configured()) {
                    $result = $client->send_text($thread_id, $text);
                    if (empty($result['success'])) {
                        $send_status = 'failed';
                        $error_message = $result['error'] ?? 'Meta API hatası';
                    }
                } else {
                    $send_status = 'failed';
                    $error_message = 'Meta API bilgileri yapılandırılmamış';
                }
            }

            $this->whatsapp_messages_model->save([
                'wa_id' => $thread_id,
                'direction' => 'out',
                'message' => $text,
                'status' => $send_status,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if ($send_status === 'failed') {
                return [
                    'status' => 'warning',
                    'message' => 'Mesaj kaydedildi ancak WhatsApp iletimi başarısız oldu: ' . ($error_message ?: 'Bilinmeyen hata'),
                    'delivery_status' => 'failed',
                    'mode' => $mode,
                ];
            }

            return [
                'status' => 'success',
                'message' => 'Mesaj WhatsApp (' . ($mode === 'unofficial' ? 'QR Web Köprüsü' : 'Resmi Cloud API') . ') üzerinden başarıyla gönderildi.',
                'delivery_status' => 'sent',
                'mode' => $mode,
            ];
        }

        if ($channel === 'instagram') {
            $this->load->model('messaging_settings_model');
            $settings = $this->messaging_settings_model->get_settings();
            $api_sent = false;
            $error_message = null;

            if (!empty($settings['instagram_access_token'])) {
                try {
                    $page_id = $settings['instagram_account_id'] ?? 'me';
                    $url = "https://graph.facebook.com/v20.0/{$page_id}/messages";
                    $payload = [
                        'recipient' => ['id' => $thread_id],
                        'message' => ['text' => $text],
                    ];
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $url,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $settings['instagram_access_token'],
                            'Content-Type: application/json',
                        ],
                    ]);
                    $res = curl_exec($ch);
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    if ($http_code === 200) {
                        $api_sent = true;
                    } else {
                        $error_message = "HTTP {$http_code}: " . $res;
                    }
                } catch (Throwable $e) {
                    $error_message = $e->getMessage();
                }
            }

            $this->db->insert('instagram_messages', [
                'instagram_user_id' => $thread_id,
                'direction' => 'out',
                'message' => $text,
                'status' => 'sent',
                'status' => $api_sent ? 'delivered' : 'sent',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$api_sent && !empty($settings['instagram_access_token'])) {
                return [
                    'status' => 'warning',
                    'message' => 'Mesaj kaydedildi ancak Instagram DM iletimi tamamlanamadı: ' . ($error_message ?: 'Bilinmeyen hata'),
                ];
            }

            return ['status' => 'success', 'message' => 'Mesaj Instagram DM ile gönderildi.'];
        }

        if ($channel === 'telegram') {
            if (!class_exists('Telegram_client', false)) {
                $this->load->library('telegram_client');
            }
            $api_sent = false;
            try {
                $api_sent = $this->telegram_client->send_message($thread_id, $text);
            } catch (Throwable $e) {
                log_message('error', 'Chat_portal Telegram send error: ' . $e->getMessage());
            }

            $this->db->insert('telegram_messages', [
                'chat_id' => $thread_id,
                'direction' => 'out',
                'message' => $text,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['status' => 'success', 'message' => 'Mesaj Telegram ile gönderildi.'];
            if (!$api_sent) {
                return [
                    'status' => 'warning',
                    'message' => 'Mesaj veritabanına kaydedildi ancak Telegram API iletimi başarısız oldu (bot ayarlarını kontrol ediniz).'
                ];
            }

            return ['status' => 'success', 'message' => 'Mesaj Telegram botu üzerinden başarıyla iletildi.'];
        }

        if ($channel === 'widget') {
            $this->db->insert('webchat_messages', [
                'session_id' => $thread_id,
                'direction' => 'out',
                'message' => $text,
                'status' => 'sent',
                'status' => 'delivered',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            return ['status' => 'success', 'message' => 'Mesaj Web Chat ile gönderildi.'];
            return ['status' => 'success', 'message' => 'Mesaj Web Chat Widget ziyaretçisine iletildi.'];
        }

        throw new InvalidArgumentException('Bilinmeyen kanal: ' . $channel);
    }

    /**
     * Send direct message to BooKi AI Copilot, save history, execute tools, and return reply.
     */
    private function send_to_ai_assistant(string $user_text, int $user_id): array
    {
        $conv = $this->db->where('title', 'BooKi AI Copilot Portalı')->order_by('id', 'DESC')->get('ai_agent_conversations')->row_array();
        if (!$conv) {
            $this->db->insert('ai_agent_conversations', [
                'id_users' => $user_id,
                'title' => 'BooKi AI Copilot Portalı',
                'summary' => 'Birleşik Chat Portalı Üzerinden AI Copilot İletişimi',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $conv_id = $this->db->insert_id();
        } else {
            $conv_id = (int)$conv['id'];
        }

        // 1. Save user turn in DB
        $this->db->insert('ai_agent_messages', [
            'id_conversation' => $conv_id,
            'role' => 'user',
            'content' => $user_text,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 2. Load recent conversation messages to feed into AI
        $recent_msgs = $this->db->where('id_conversation', $conv_id)->order_by('id', 'DESC')->limit(12)->get('ai_agent_messages')->result_array();
        $history = [];
        foreach (array_reverse($recent_msgs) as $rm) {
            $history[] = [
                'role' => $rm['role'],
                'content' => $rm['content'],
            ];
        }

        // 3. Call AI Agent Client
        $this->load->library('ai_agent_client');
        $reply_text = 'Üzgünüm, şu anda yapay zeka servisine erişilemiyor.';
        try {
            $result = $this->ai_agent_client->chat($history);
            $reply_text = $result['reply'] ?? 'Anladım, işlemi kontrol ediyorum.';
        } catch (Throwable $e) {
            log_message('error', 'Chat_portal AI assistant error: ' . $e->getMessage());
            $reply_text = 'İşleminizi aldım ancak AI servisi yanıt verirken bir gecikme yaşandı: ' . $e->getMessage();
        }

        // 4. Save assistant reply in DB
        $this->db->insert('ai_agent_messages', [
            'id_conversation' => $conv_id,
            'role' => 'assistant',
            'content' => $reply_text,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'status' => 'success',
            'reply' => $reply_text,
            'time' => date('H:i'),
        ];
    }

    /**
     * Check if human handoff is currently active for a thread.
     */
    public function is_handoff_active(string $channel, string $sender_id): bool
    {
        if (!$this->db->table_exists('ai_channel_handoffs')) {
            return false;
        }

        $row = $this->db->where('channel', $channel)
            ->where('sender_id', $sender_id)
            ->where('status', 'active')
            ->where('paused_until >', date('Y-m-d H:i:s'))
            ->get('ai_channel_handoffs')
            ->row_array();

        return !empty($row);
    }

    /**
     * Switch between AI mode and Human Handoff mode.
     */
    public function toggle_handoff(string $channel, string $sender_id, string $mode, int $user_id = 1): array
    {
        $this->load->library('ai_channel_responder');

        if ($mode === 'human') {
            $this->ai_channel_responder->trigger_handoff($channel, $sender_id, $user_id, 'Kullanıcı chat portalından manuel devraldı.', 48);
            return [
                'status' => 'success',
                'mode' => 'human_handoff',
                'message' => 'Konuşma manuel devralındı. AI otomatik yanıtları bu konuşma için duraklatıldı.',
            ];
        } else {
            $this->ai_channel_responder->resolve_handoff($channel, $sender_id, $user_id);
            return [
                'status' => 'success',
                'mode' => 'ai_active',
                'message' => 'Konuşma AI Asistana geri devredildi. Müşteri mesajlarına AI otomatik yanıt verecektir.',
            ];
        }
    }

    /**
     * AI Draft Suggestion for human agent.
     */
    public function get_ai_draft_suggestion(string $channel, string $thread_id): array
    {
        $messages = $this->get_messages($channel, $thread_id);
        if (empty($messages)) {
            return ['status' => 'error', 'suggestion' => 'Henüz mesaj geçmişi bulunmuyor.'];
        }

        $last_customer_msg = '';
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if ($messages[$i]['direction'] === 'in') {
                $last_customer_msg = $messages[$i]['content'];
                break;
            }
        }

        if (empty($last_customer_msg)) {
            $last_customer_msg = end($messages)['content'] ?? '';
        }

        $prompt = "Müşterinin son mesajı: \"{$last_customer_msg}\". Bir işletme temsilcisi olarak müşteriye nazik, profesyonel ve rezervasyon odaklı kısa bir Türkçe yanıt önerisi hazırla. Sadece önerilen mesaj metnini yaz.";

        $this->load->library('ai_llm_gateway');
        try {
            $res = $this->ai_llm_gateway->complete([
                ['role' => 'system', 'content' => 'Sen profesyonel bir müşteri temsilcisi asistanısın. Müşteri mesajına verilecek en uygun yanıtı kısa, net ve rezervasyona yönlendirici şekilde üret.'],
                ['role' => 'user', 'content' => $prompt],
            ], [
                'temperature' => 0.4,
                'max_tokens' => 250,
            ]);

            $suggestion = trim($res['content'] ?? '');
            if (empty($suggestion)) {
                $suggestion = 'Merhaba, mesajınız için teşekkür ederiz. Talebinizle ilgili size yardımcı olmaktan memnuniyet duyarız. Müsaitlik durumunu kontrol edip size hemen bilgi veriyorum.';
            }

            return ['status' => 'success', 'suggestion' => $suggestion];
        } catch (Throwable $e) {
            return [
                'status' => 'success',
                'suggestion' => 'Merhaba! Mesajınızı aldım, size yardımcı olmaktan memnuniyet duyarım. Rezervasyon veya hizmetlerimizle ilgili detayları hemen aktarıyorum.',
            ];
        }
    }

    /**
     * Format time relative/human readable.
     */
    private function format_relative_time(string $datetime): string
    {
        $ts = strtotime($datetime);
        $diff = time() - $ts;

        if ($diff < 60) {
            return 'Az önce';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . ' dk';
        }
        if ($diff < 86400) {
            return date('H:i', $ts);
        }
        if ($diff < 172800) {
            return 'Dün';
        }
        return date('d.m', $ts);
    }
}
