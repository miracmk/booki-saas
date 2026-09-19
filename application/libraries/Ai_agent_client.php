<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AI Agent client library.
 *
 * Admin-panel-internal assistant powered by the Universal Multi-Provider AI LLM Gateway
 * (Google AI Studio / Gemini, Groq, OpenRouter, OpenAI, Anthropic).
 *
 * Trust boundary: this class NEVER writes to `users`/`customers`/`appointments` or any other
 * business table directly. Write-shaped tools only insert an `ai_agent_pending_changes` row;
 * an admin must explicitly approve it (Ai_agent::approve()) before
 * any changes or appointments are committed.
 *
 * @package Libraries
 */
class Ai_agent_client
{
    /** Hard cap on tool-call round trips per turn */
    private const MAX_TOOL_ITERATIONS = 6;

    private const TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_customers',
                'description' => 'Müşterileri isim, telefon veya e-posta ile arar. Kelime verilmezse en son kayıtlı 10 müşteriyi döner.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'keyword' => ['type' => 'string', 'description' => 'Arama kelimesi (isim, telefon veya e-posta). Boş bırakılırsa son müşteriler gelir.'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_customer',
                'description' => 'Müşteri ID ile profil detaylarını getirir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_customer_appointments',
                'description' => 'Müşteri ID ile randevu geçmişini (hizmet adı, uzman, başlangıç/bitiş saati, fiyat ve durum detaylarıyla) getirir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                    ],
                    'required' => ['customer_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_appointments_by_date',
                'description' => 'Belirtilen tarihteki (veya bugünkü) tüm randevuları müşteri, hizmet, personel ve saat bilgileriyle listeler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'Tarih (YYYY-MM-DD formatında, örn: 2026-09-19). Boş bırakılırsa bugünü getirir.'],
                        'status' => ['type' => 'string', 'description' => 'Opsiyonel durum filtresi: Reserved, Confirmed, Cancelled vb.'],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_appointment_details',
                'description' => 'Randevu ID ile detaylı randevu bilgilerini getirir (müşteri adı/telefonu, hizmet adı, uzman, saatler, durum, notlar).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'Randevu ID.'],
                    ],
                    'required' => ['appointment_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_services',
                'description' => 'İşletmenin sunduğu tüm aktif hizmetleri (ad, süre dakika, fiyat, para birimi) listeler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_providers',
                'description' => 'İşletmedeki uzmanları / çalışan personeli (ad, soyad, e-posta, telefon) listeler.',
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
                'description' => 'Belirli bir tarih ve hizmet için uygun saat dilimlerini kontrol eder.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'date' => ['type' => 'string', 'description' => 'YYYY-MM-DD formatında kontrol edilecek tarih.'],
                        'service_id' => ['type' => 'integer', 'description' => 'Hizmet ID.'],
                        'provider_id' => ['type' => 'integer', 'description' => 'Opsiyonel uzman personel ID.'],
                    ],
                    'required' => ['date', 'service_id'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_create',
                'description' => 'Yeni randevu oluşturma talebini onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_name' => ['type' => 'string', 'description' => 'Müşteri adı ve soyadı.'],
                        'customer_phone' => ['type' => 'string', 'description' => 'Müşteri telefon numarası.'],
                        'customer_email' => ['type' => 'string', 'description' => 'Müşteri e-posta adresi (varsa).'],
                        'service_name' => ['type' => 'string', 'description' => 'Hizmet adı veya ID.'],
                        'start_datetime' => ['type' => 'string', 'description' => 'Randevu başlangıç zamanı (YYYY-MM-DD HH:MM:SS formatında).'],
                        'notes' => ['type' => 'string', 'description' => 'Randevu notu.'],
                        'reason' => ['type' => 'string', 'description' => 'Gerekçe.'],
                    ],
                    'required' => ['customer_name', 'customer_phone', 'service_name', 'start_datetime'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_cancel',
                'description' => 'Mevcut bir randevuyu iptal etme talebini onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'Randevu ID.'],
                        'reason' => ['type' => 'string', 'description' => 'İptal gerekçesi.'],
                    ],
                    'required' => ['appointment_id', 'reason'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'propose_appointment_reschedule',
                'description' => 'Mevcut bir randevunun saatini değiştirme talebini onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'Randevu ID.'],
                        'new_start_datetime' => ['type' => 'string', 'description' => 'Yeni tarih ve saat (YYYY-MM-DD HH:MM:SS formatında).'],
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
                'description' => 'Müşteri kaydında bilgi güncelleme talebini onay kuyruğuna ekler.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'customer_id' => ['type' => 'integer'],
                        'changes' => [
                            'type' => 'object',
                            'description' => 'first_name, last_name, email, phone_number, address, notes alanları.',
                        ],
                        'reason' => ['type' => 'string', 'description' => 'Gerekçe.'],
                    ],
                    'required' => ['customer_id', 'changes', 'reason'],
                ],
            ],
        ],
    ];

    private const ALLOWED_UPDATE_FIELDS = ['first_name', 'last_name', 'email', 'phone_number', 'address', 'notes'];

    /**
     * @var CI_Controller
     */
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('ai_llm_gateway');
    }

    /**
     * Run one chat turn: send the conversation to the model, execute any tool
     * calls it makes, feed the results back, and repeat until it produces a
     * final plain-text answer (or the iteration cap is hit).
     *
     * @param array $messages Conversation history: [['role' => 'user'|'assistant'|'tool', 'content' => string, ...], ...]
     *
     * @return array ['reply' => string, 'tool_calls' => array]
     */
    public function chat(array $messages): array
    {
        $conversation = array_merge([
            ['role' => 'system', 'content' => $this->build_system_prompt()],
        ], $messages);

        $tool_call_log = [];

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->CI->ai_llm_gateway->chat($conversation, [
                'tools' => self::TOOLS,
                'temperature' => 0.3,
                'max_tokens' => 1024,
            ]);

            if ($response === null || empty($response['success'])) {
                return [
                    'reply' => 'AI Asistan şu anda yanıt veremiyor. Lütfen API anahtarlarınızı kontrol edin.',
                    'tool_calls' => $tool_call_log,
                ];
            }

            $tool_calls = $response['tool_calls'] ?? [];

            if (empty($tool_calls)) {
                return [
                    'reply' => (string) ($response['reply'] ?? ''),
                    'tool_calls' => $tool_call_log,
                ];
            }

            $conversation[] = [
                'role' => 'assistant',
                'content' => $response['reply'] ?? '',
                'tool_calls' => $tool_calls,
            ];

            foreach ($tool_calls as $call) {
                $name = $call['function']['name'] ?? '';
                $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];

                $tool_call_log[] = ['name' => $name, 'args' => $args];

                $result = $this->execute_tool($name, $args);

                $conversation[] = [
                    'role' => 'tool',
                    'name' => $name,
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        return [
            'reply' => (string) ($response['reply'] ?? 'İşlem tamamlandı.'),
            'tool_calls' => $tool_call_log,
            'turn_messages' => array_slice($conversation, count($messages) + 1),
        ];
    }

    /**
     * Dispatch one tool call by name.
     */
    private function execute_tool(string $name, array $args): array
    {
        $CI = &get_instance();
        $CI->load->model('customers_model');
        $CI->load->model('appointments_model');
        $CI->load->model('services_model');
        $CI->load->model('providers_model');
        $CI->load->model('roles_model');

        try {
            switch ($name) {
                case 'search_customers':
                    $keyword = trim((string) ($args['keyword'] ?? ''));

                    if ($keyword === '') {
                        $role_customer_id = (int) ($CI->roles_model->get_role_id_by_slug(DB_SLUG_CUSTOMER) ?: 4);
                        $results = $CI->db
                            ->where('id_roles', $role_customer_id)
                            ->order_by('id', 'DESC')
                            ->limit(10)
                            ->get('users')
                            ->result_array();
                    } else {
                        $results = $CI->customers_model->search($keyword, 10);
                    }

                    return array_map(static fn (array $c) => [
                        'id' => (int) $c['id'],
                        'first_name' => $c['first_name'] ?? null,
                        'last_name' => $c['last_name'] ?? null,
                        'phone_number' => $c['phone_number'] ?? null,
                        'email' => $c['email'] ?? null,
                    ], $results);

                case 'get_customer':
                    $customer_id = (int) ($args['customer_id'] ?? 0);
                    $customer = $CI->customers_model->find($customer_id);
                    if (!$customer) {
                        return ['error' => 'Müşteri bulunamadı'];
                    }

                    return [
                        'id' => (int) $customer['id'],
                        'first_name' => $customer['first_name'] ?? null,
                        'last_name' => $customer['last_name'] ?? null,
                        'phone_number' => $customer['phone_number'] ?? null,
                        'email' => $customer['email'] ?? null,
                        'address' => $customer['address'] ?? null,
                        'notes' => $customer['notes'] ?? null,
                    ];

                case 'get_customer_appointments':
                    $customer_id = (int) ($args['customer_id'] ?? 0);
                    $appointments = $CI->appointments_model->get_for_customer($customer_id);

                    return array_map(static function (array $a) use ($CI) {
                        $service = $CI->services_model->find((int) ($a['id_services'] ?? 0));
                        $provider = $CI->providers_model->find((int) ($a['id_users_provider'] ?? 0));
                        return [
                            'id' => (int) $a['id'],
                            'start_datetime' => $a['start_datetime'] ?? null,
                            'end_datetime' => $a['end_datetime'] ?? null,
                            'status' => $a['status'] ?? null,
                            'service_id' => (int) ($a['id_services'] ?? 0),
                            'service_name' => $service['name'] ?? 'Bilinmeyen Hizmet',
                            'service_duration' => $service['duration'] ?? null,
                            'service_price' => $service['price'] ?? null,
                            'provider_id' => (int) ($a['id_users_provider'] ?? 0),
                            'provider_name' => trim(($provider['first_name'] ?? '') . ' ' . ($provider['last_name'] ?? '')) ?: 'Belirtilmedi',
                            'notes' => $a['notes'] ?? null,
                        ];
                    }, $appointments);

                case 'get_appointments_by_date':
                    $date = trim((string) ($args['date'] ?? date('Y-m-d')));
                    if (empty($date)) {
                        $date = date('Y-m-d');
                    }
                    $status_filter = trim((string) ($args['status'] ?? ''));

                    $query = $CI->db
                        ->select('a.id, a.start_datetime, a.end_datetime, a.status, a.notes,
                                  s.id as service_id, s.name as service_name, s.duration, s.price,
                                  c.id as customer_id, c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone, c.email as customer_email,
                                  p.id as provider_id, p.first_name as provider_first_name, p.last_name as provider_last_name')
                        ->from('appointments a')
                        ->join('services s', 's.id = a.id_services', 'left')
                        ->join('users c', 'c.id = a.id_users_customer', 'left')
                        ->join('users p', 'p.id = a.id_users_provider', 'left')
                        ->where('a.is_unavailability', false)
                        ->where('a.start_datetime >=', $date . ' 00:00:00')
                        ->where('a.start_datetime <=', $date . ' 23:59:59');

                    if ($status_filter !== '') {
                        $query->where('a.status', $status_filter);
                    } else {
                        $query->where_not_in('a.status', ['Cancelled', 'Draft']);
                    }

                    $rows = $query->order_by('a.start_datetime', 'ASC')->limit(50)->get()->result_array();

                    return array_map(static fn (array $r) => [
                        'appointment_id' => (int) $r['id'],
                        'start_datetime' => $r['start_datetime'],
                        'end_datetime' => $r['end_datetime'],
                        'status' => $r['status'],
                        'customer' => [
                            'id' => (int) $r['customer_id'],
                            'name' => trim($r['customer_first_name'] . ' ' . $r['customer_last_name']),
                            'phone' => $r['customer_phone'],
                            'email' => $r['customer_email'],
                        ],
                        'service' => [
                            'id' => (int) $r['service_id'],
                            'name' => $r['service_name'],
                            'duration' => $r['duration'],
                            'price' => $r['price'],
                        ],
                        'provider' => [
                            'id' => (int) $r['provider_id'],
                            'name' => trim($r['provider_first_name'] . ' ' . $r['provider_last_name']),
                        ],
                        'notes' => $r['notes'],
                    ], $rows);

                case 'get_appointment_details':
                    $appointment_id = (int) ($args['appointment_id'] ?? 0);
                    $appt = $CI->db
                        ->select('a.*, s.name as service_name, s.duration, s.price,
                                  c.first_name as customer_first_name, c.last_name as customer_last_name, c.phone_number as customer_phone, c.email as customer_email,
                                  p.first_name as provider_first_name, p.last_name as provider_last_name')
                        ->from('appointments a')
                        ->join('services s', 's.id = a.id_services', 'left')
                        ->join('users c', 'c.id = a.id_users_customer', 'left')
                        ->join('users p', 'p.id = a.id_users_provider', 'left')
                        ->where('a.id', $appointment_id)
                        ->get()
                        ->row_array();

                    if (!$appt) {
                        return ['error' => 'Randevu bulunamadı: #' . $appointment_id];
                    }

                    return [
                        'appointment_id' => (int) $appt['id'],
                        'start_datetime' => $appt['start_datetime'],
                        'end_datetime' => $appt['end_datetime'],
                        'status' => $appt['status'],
                        'customer_name' => trim($appt['customer_first_name'] . ' ' . $appt['customer_last_name']),
                        'customer_phone' => $appt['customer_phone'],
                        'customer_email' => $appt['customer_email'],
                        'service_name' => $appt['service_name'],
                        'duration' => $appt['duration'],
                        'price' => $appt['price'],
                        'provider_name' => trim($appt['provider_first_name'] . ' ' . $appt['provider_last_name']),
                        'notes' => $appt['notes'],
                    ];

                case 'get_services':
                    $services = $CI->db->select('id, name, duration, price, currency, description')->get('services')->result_array() ?: [];
                    return array_map(static fn (array $s) => [
                        'id' => (int) $s['id'],
                        'name' => $s['name'],
                        'duration' => (int) $s['duration'],
                        'price' => (float) $s['price'],
                        'currency' => $s['currency'] ?? 'TL',
                        'description' => $s['description'] ?? '',
                    ], $services);

                case 'get_providers':
                    $providers = $CI->providers_model->get_available_providers();
                    return array_map(static fn (array $p) => [
                        'id' => (int) $p['id'],
                        'name' => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
                        'email' => $p['email'] ?? '',
                        'phone_number' => $p['phone_number'] ?? '',
                    ], $providers);

                case 'check_availability':
                    $date = trim((string) ($args['date'] ?? date('Y-m-d')));
                    $service_id = (int) ($args['service_id'] ?? 0);

                    $existing = $CI->db
                        ->select('start_datetime, end_datetime')
                        ->from('appointments')
                        ->where('start_datetime >=', $date . ' 00:00:00')
                        ->where('start_datetime <=', $date . ' 23:59:59')
                        ->where_not_in('status', ['Cancelled', 'Draft'])
                        ->get()
                        ->result_array();

                    $busy_slots = array_map(static fn ($e) => date('H:i', strtotime($e['start_datetime'])) . ' - ' . date('H:i', strtotime($e['end_datetime'])), $existing);

                    return [
                        'date' => $date,
                        'service_id' => $service_id,
                        'busy_slots' => $busy_slots,
                        'note' => count($busy_slots) > 0 ? 'Bu saatler dolu: ' . implode(', ', $busy_slots) : 'Tüm gün uygun.',
                    ];

                case 'propose_appointment_create':
                    return $this->propose_appointment_create($CI, $args);

                case 'propose_appointment_cancel':
                    return $this->propose_appointment_cancel($CI, $args);

                case 'propose_appointment_reschedule':
                    return $this->propose_appointment_reschedule($CI, $args);

                case 'propose_customer_update':
                    return $this->propose_customer_update($CI, $args);

                default:
                    return ['error' => 'Bilinmeyen araç: ' . $name];
            }
        } catch (Throwable $e) {
            log_message('error', 'Ai_agent_client tool ' . $name . ' failed: ' . $e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    private function propose_appointment_create($CI, array $args): array
    {
        $service_name = (string) ($args['service_name'] ?? '');
        $start_datetime = (string) ($args['start_datetime'] ?? '');
        $customer_name = trim((string) ($args['customer_name'] ?? ''));
        $customer_phone = trim((string) ($args['customer_phone'] ?? ''));
        $customer_email = trim((string) ($args['customer_email'] ?? ''));
        $notes = (string) ($args['notes'] ?? '');
        $reason = (string) ($args['reason'] ?? 'Admin paneli üzerinden randevu talebi');

        if (empty($start_datetime) || empty($customer_name)) {
            return ['error' => 'start_datetime ve customer_name zorunludur'];
        }

        $active_provider = $this->CI->ai_llm_gateway->get_active_provider();

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'appointments',
            'target_id' => 0,
            'changes' => json_encode([
                'action' => 'create',
                'service_name' => $service_name,
                'start_datetime' => $start_datetime,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_email' => $customer_email,
                'notes' => $notes,
            ], JSON_UNESCAPED_UNICODE),
            'reason' => $reason . " ({$customer_name} - {$service_name} @ {$start_datetime})",
            'model_name' => $active_provider,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'queued' => true,
            'pending_id' => $CI->db->insert_id(),
            'note' => 'Yeni randevu talebi yönetici onay kuyruğuna alındı.',
        ];
    }

    private function propose_appointment_cancel($CI, array $args): array
    {
        $appointment_id = (int) ($args['appointment_id'] ?? 0);
        $reason = (string) ($args['reason'] ?? 'Randevu iptal talebi');

        if (empty($appointment_id)) {
            return ['error' => 'appointment_id zorunludur'];
        }

        $active_provider = $this->CI->ai_llm_gateway->get_active_provider();

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'appointments',
            'target_id' => $appointment_id,
            'changes' => json_encode([
                'action' => 'cancel',
                'appointment_id' => $appointment_id,
                'reason' => $reason,
            ], JSON_UNESCAPED_UNICODE),
            'reason' => $reason . " (Randevu #{$appointment_id})",
            'model_name' => $active_provider,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'queued' => true,
            'pending_id' => $CI->db->insert_id(),
            'note' => 'Randevu iptal talebi yönetici onay kuyruğuna alındı.',
        ];
    }

    private function propose_appointment_reschedule($CI, array $args): array
    {
        $appointment_id = (int) ($args['appointment_id'] ?? 0);
        $new_start_datetime = (string) ($args['new_start_datetime'] ?? '');
        $reason = (string) ($args['reason'] ?? 'Randevu saat değişikliği talebi');

        if (empty($appointment_id) || empty($new_start_datetime)) {
            return ['error' => 'appointment_id ve new_start_datetime zorunludur'];
        }

        $active_provider = $this->CI->ai_llm_gateway->get_active_provider();

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'appointments',
            'target_id' => $appointment_id,
            'changes' => json_encode([
                'action' => 'reschedule',
                'appointment_id' => $appointment_id,
                'new_start_datetime' => $new_start_datetime,
                'reason' => $reason,
            ], JSON_UNESCAPED_UNICODE),
            'reason' => $reason . " (Randevu #{$appointment_id} -> {$new_start_datetime})",
            'model_name' => $active_provider,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'queued' => true,
            'pending_id' => $CI->db->insert_id(),
            'note' => 'Randevu değişiklik talebi yönetici onay kuyruğuna alındı.',
        ];
    }

    private function propose_customer_update($CI, array $args): array
    {
        $customer_id = (int) ($args['customer_id'] ?? 0);
        $changes = (array) ($args['changes'] ?? []);
        $reason = (string) ($args['reason'] ?? '');

        if (empty($customer_id) || empty($changes)) {
            return ['error' => 'customer_id ve changes zorunludur'];
        }

        $CI->customers_model->find($customer_id);

        $filtered = array_intersect_key($changes, array_flip(self::ALLOWED_UPDATE_FIELDS));

        if (empty($filtered)) {
            return ['error' => 'Geçerli güncellenebilir alan bulunamadı: ' . implode(', ', self::ALLOWED_UPDATE_FIELDS)];
        }

        $active_provider = $this->CI->ai_llm_gateway->get_active_provider();

        $CI->db->insert('ai_agent_pending_changes', [
            'target_table' => 'users',
            'target_id' => $customer_id,
            'changes' => json_encode($filtered, JSON_UNESCAPED_UNICODE),
            'reason' => $reason,
            'model_name' => $active_provider,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'queued' => true,
            'pending_id' => $CI->db->insert_id(),
            'note' => 'Müşteri güncelleme talebi yönetici onay kuyruğuna alındı.',
        ];
    }

    private function build_system_prompt(): string
    {
        $CI = &get_instance();
        $CI->load->model('settings_model');
        $CI->load->model('services_model');
        $CI->load->model('providers_model');

        $now = date('Y-m-d H:i:s (l)');
        $company_name = setting('company_name') ?: 'BooKi İşletmesi';
        $company_phone = setting('company_phone') ?: '-';
        $company_address = setting('company_address') ?: '-';

        $services = $CI->db->select('id, name, duration, price, currency')->get('services')->result_array() ?: [];
        $service_summary = [];
        foreach (array_slice($services, 0, 20) as $s) {
            $service_summary[] = "- [#{$s['id']}] {$s['name']} ({$s['duration']} dk, {$s['price']} " . ($s['currency'] ?? 'TL') . ")";
        }
        $services_text = implode("\n", $service_summary) ?: 'Kayıtlı hizmet bulunamadı.';

        $providers = $CI->providers_model->get_available_providers();
        $provider_summary = [];
        foreach ($providers as $p) {
            $p_name = trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? ''));
            $provider_summary[] = "- [#{$p['id']}] {$p_name}";
        }
        $providers_text = implode("\n", $provider_summary) ?: 'Kayıtlı personel bulunamadı.';

        return <<<PROMPT
Sen {$company_name} işletmesinin BooKi yapay zeka asistanısın. Personel (yönetici/sekreter) sana randevuları listeleme, bugünkü/yarınki programı sorgulama, müşteri kayıtları, randevu oluşturma, iptal veya saat değişikliği gibi tüm operasyonel konularda talimat verir.

GÜNCEL ZAMAN: {$now}
İŞLETME: {$company_name} | Tel: {$company_phone} | Adres: {$company_address}

MEVCUT HİZMETLER:
{$services_text}

PERSONEL / UZMANLAR:
{$providers_text}

YETKİ VE ARAÇ KULLANIM KURALLARI:
1. Her zaman TÜRKÇE, nazik, net ve çözüm odaklı konuş.
2. Randevuları ve takvimi görmek için:
   - Bugünkü veya belirli bir gündeki randevuları sormuşlarsa: get_appointments_by_date aracını kullan.
   - Belirli bir müşterinin randevularını sormuşlarsa: search_customers ve get_customer_appointments araçlarını kullan.
   - Randevunun tüm detayları için: get_appointment_details aracını kullan.
   - Asla "göremiyorum" deme; ilgili araçları çağırarak verileri incele ve personele sun.
3. Hizmet ve çalışan bilgileri için get_services ve get_providers araçlarını kullan.
4. Müsaitlik sorgulamak için check_availability aracını kullan.
5. Randevu oluşturma, iptal etme, saat değiştirme veya müşteri güncelleme taleplerinde ilgili propose_* aracını çağır.
6. Asla "doğrudan randevuyu oluşturdum/sildim" deme; "Talebi hazırladım ve onay bekleyenler listesine ekledim, sağ taraftaki panelden inceleyip tek tıkla onaylayabilirsiniz" şeklinde bildir.
7. Bilmediğin bilgiyi uydurma.
PROMPT;
    }
}
