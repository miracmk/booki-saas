<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Online Appointment Scheduler & SaaS Operating System
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * @link        https://kisoftware.com
 * ---------------------------------------------------------------------------- */

/**
 * Platform AI Responder & Sales Intelligence Consultant.
 *
 * Dedicated exclusively to the platform-level AI operations:
 * - Inbound Platform WhatsApp messages (Leads, prospects, pricing, competitors, demo requests)
 * - Superadmin CRM panel interactive sales assistant (Lead coaching, objection handling, conversion strategy)
 *
 * Backed by:
 * - Platform_knowledge_base (9 vertical competitor playbooks, 21 BooKi advantages, pricing, Ki Business sales matrix)
 * - Dynamic Codebase Feature Scanner (auto-updated with Git commits)
 * - Model Context Protocol (MCP) server & Live DB inspection tools
 *
 * @package Libraries
 * @subpackage Platform
 */
class Platform_ai_responder
{
    protected CI_Controller $CI;

    public const MAX_TOOL_ITERATIONS = 4;

    public const PLATFORM_TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name' => 'search_knowledge_base',
                'description' => 'BooKi Knowledge Base içinde arama yapar: 9 sektörel dikey rakip analizleri, çürütme argümanları, BooKi üstünlükleri, şeffaf fiyatlandırma ve Ki Business satış metodolojisi kararları.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Arama terimi, konu veya soru (örn: Menajer.im, Adisyo, fiyatlar, paket üyelik farkı, no-show, seans takibi).',
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'Kategori filtresi: competitors, pricing, advantages, methodology, codebase (boş bırakılabilir).',
                        ],
                    ],
                    'required' => ['query'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_competitor_analysis',
                'description' => 'Belirli bir rakip yazılım (örn: Menajer.im, SalonJet, Fresha, Booksy, SalonRandevu.app, Adisyo, Simpra, RRobotpos, SepetTakip, RestoranTakibi, GymTekno, Gymsoft, BulutGym, GymPro, Megin, MedikalCRM, Doktor365, Dr.DENTES, DentSoft, Macrodental, PADOK Bulut, Tila TSP, Livo, OtoServiso, Onarmatik, Biletix, Passo, Biletinial, Bubilet, Biletino, Elektraweb, HotelRunner, RoomRaccoon, ArtechSchool, Delta Kurs, Eğitim360, OkulConnect, Birebil, Zoho CRM, HubSpot, Pipedrive, Logo CRM, Workcube) hakkında güçlü taraflarını, BooKi\'nin çürütme argümanlarını ve itiraz karşılama scriptini getirir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'competitor_name' => [
                            'type' => 'string',
                            'description' => 'Rakip firmanın / yazılımın adı.',
                        ],
                    ],
                    'required' => ['competitor_name'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_sales_strategy',
                'description' => 'Ki Business Satış Metodolojisi Karar Matrisinden (SA-01..HO-03) en uygun satış tekniğini, uygulama adımlarını, scriptini, itiraz yönetimini ve CRM etiketini getirir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'technique_id' => [
                            'type' => 'string',
                            'description' => 'Teknik ID (örn: SA-01, TI-01, TI-02, OC-01, OC-03, HO-01, HO-02, HO-03).',
                        ],
                        'channel' => [
                            'type' => 'string',
                            'description' => 'Kanal: WhatsApp, Soğuk Arama, Telesatış, Web Chat, Sosyal Medya.',
                        ],
                        'customer_psychology' => [
                            'type' => 'string',
                            'description' => 'Müşterinin psikolojik durumu: fiyat itirazı, rakip karşılaştırma, savunmacı, zaman baskısı, şüpheci, keşif modunda.',
                        ],
                    ],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'check_subdomain_availability',
                'description' => 'İşletmenin istediği alan adının / subdomainin (örn: salonflora, estetikmerkezi, fitlife) BooKi üzerinde boşta olup olmadığını canlı kontrol eder.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'subdomain' => [
                            'type' => 'string',
                            'description' => 'Kontrol edilecek subdomain (Türkçe karaktersiz, küçük harf ve tire, örn: "flora-kuafor").',
                        ],
                    ],
                    'required' => ['subdomain'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'create_demo_lead',
                'description' => 'İlgilenen potansiyel işletmeyi (lead) Superadmin CRM sistemine kaydeder, böylece satış ekibi veya otomatik demo akışı işletmeyle hemen iletişime geçebilir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'business_name' => [
                            'type' => 'string',
                            'description' => 'İşletmenin veya salonun adı.',
                        ],
                        'contact_name' => [
                            'type' => 'string',
                            'description' => 'İlgili kişinin / işletme yetkilisinin adı soyadı.',
                        ],
                        'phone' => [
                            'type' => 'string',
                            'description' => 'Telefon veya WhatsApp numarası.',
                        ],
                        'sector' => [
                            'type' => 'string',
                            'description' => 'Sektör (Güzellik, Restoran, Gym, Klinik, Oto Servis, Otel, Eğitim vb.).',
                        ],
                        'notes' => [
                            'type' => 'string',
                            'description' => 'Müşterinin talepleri, ilgilendiği paket veya konuştuğu rakip.',
                        ],
                        'crm_tag' => [
                            'type' => 'string',
                            'description' => 'Metodoloji CRM etiketi (örn: BANT_COMPETITIVE_INBOUND, VALUE_ANCHOR_FOMO_B2C, URGENCY_PRICING_CLOSE, SPECIAL_OFFER_REQUEST, CUSTOM_ENTERPRISE_OFFER, FREE_TIER_INQUIRY).',
                        ],
                    ],
                    'required' => ['business_name', 'phone'],
                ],
            ],
        ],
        [
            'type' => 'function',
            'function' => [
                'name' => 'get_codebase_features',
                'description' => 'BooKi kod tabanından git commit bazlı taranmış canlı modül ve mimari yetenek kataloğunu getirir.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'module_key' => [
                            'type' => 'string',
                            'description' => 'Belirli bir modül anahtarı (örn: multi_tenant_core, appointment_engine, packages_and_memberships, pos_adisyon_finance, omnichannel_ai_assistant, model_context_protocol_mcp, kvkk_gdpr_pii_security). Boş bırakılırsa tüm modüller listelenir.',
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('ai_llm_gateway');
        $this->CI->load->library('platform_knowledge_base');
        $this->CI->load->helper('setting');
    }

    /**
     * Respond to an inbound WhatsApp message sent to the platform's WhatsApp account.
     *
     * @param string $from Sender phone number / JID
     * @param string $body Inbound message text
     * @return string Assistant reply text (or fallback message if unable to reply)
     */
    public function respond_whatsapp(string $from, string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            return '';
        }

        $clean_from = preg_replace('/@.*$/', '', $from);

        // 1. Log inbound message in master DB ea_whatsapp_messages
        $this->log_whatsapp_message($clean_from, 'in', $body);

        // 2. Load recent conversation history for this sender (last 6 messages)
        $history = $this->load_whatsapp_history($clean_from, 6);

        // 3. Build master platform sales prompt with full Knowledge Base
        $system_prompt = $this->CI->platform_knowledge_base->build_platform_master_prompt();

        $conversation = [
            ['role' => 'system', 'content' => $system_prompt],
        ];

        foreach ($history as $h) {
            $conversation[] = [
                'role' => $h['direction'] === 'in' ? 'user' : 'assistant',
                'content' => $h['message'],
            ];
        }

        $conversation[] = [
            'role' => 'user',
            'content' => "Gelen WhatsApp Mesajı (Kimden: {$clean_from}):\n{$body}",
        ];

        // 4. Multi-turn Tool Calling Execution Loop
        $reply = null;
        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->CI->ai_llm_gateway->chat($conversation, [
                'tools' => self::PLATFORM_TOOLS,
                'temperature' => 0.3,
                'max_tokens' => 800,
            ]);

            if ($response === null || empty($response['success'])) {
                log_message('error', "Platform_ai_responder::respond_whatsapp - LLM failed at iteration {$i}");
                break;
            }

            $tool_calls = $response['tool_calls'] ?? [];
            if (empty($tool_calls)) {
                $reply = trim((string) ($response['reply'] ?? ''));
                break;
            }

            // Append assistant response with tool calls and model parts
            $conversation[] = [
                'role' => 'assistant',
                'content' => $response['reply'] ?? '',
                'tool_calls' => $tool_calls,
                'model_parts' => $response['model_parts'] ?? null,
            ];

            foreach ($tool_calls as $call) {
                $tool_name = $call['function']['name'] ?? '';
                $raw_args = $call['function']['arguments'] ?? '{}';
                $args = is_string($raw_args) ? (json_decode($raw_args, true) ?: []) : (array) $raw_args;

                $tool_result = $this->execute_platform_tool($tool_name, $args, $clean_from);

                $conversation[] = [
                    'role' => 'tool',
                    'name' => $tool_name,
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($tool_result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if (empty($reply)) {
            $reply = "Merhaba! BooKi Akıllı Randevu & İşletme İşletim Sistemi platformuna hoş geldiniz. 🌸\n\n" .
                "İşletmeniz için 7/24 WhatsApp yapay zeka asistanı, randevu takvimi, paket/seans takibi ve adisyon altyapısı sunuyoruz.\n\n" .
                "Dilerseniz hemen https://bookiapp.kibusiness.co adresinden 10 günlük ücretsiz demonuzu başlatabilir veya merak ettiğiniz özellikleri sorabilirsiniz.";
        }

        // 5. Log outbound reply in master DB ea_whatsapp_messages
        $this->log_whatsapp_message($clean_from, 'out', $reply);

        return $reply;
    }

    /**
     * Superadmin CRM panel interactive AI chat endpoint.
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

        $system_prompt = $this->CI->platform_knowledge_base->build_platform_master_prompt();
        $system_prompt .= "\n\nNOT: Şu anda Superadmin CRM panelinde yetkili satış ve operasyon yöneticisi ile konuşuyorsun. Saha satış tavsiyeleri, lead analizleri, rakip çürütme argümanları ve platform özelliklerinde yöneticiye rehberlik et.";

        if ($lead_id !== null && $lead_id > 0) {
            try {
                $lead = $this->CI->db->get_where('leads', ['id' => $lead_id])->row_array();
                if ($lead) {
                    $system_prompt .= "\n\nŞu Anda Üzerinde Çalışılan Lead Bilgileri:\n" .
                        "- İşletme Adı: " . ($lead['name'] ?? 'Bilinmiyor') . "\n" .
                        "- Sektör: " . ($lead['sector'] ?? 'Genel') . "\n" .
                        "- İlçe / Adres: " . ($lead['district'] ?? '') . ' ' . ($lead['address'] ?? '') . "\n" .
                        "- Telefon: " . ($lead['phone'] ?? $lead['whatsapp'] ?? 'Yok') . "\n" .
                        "- Aşama / Öncelik: " . ($lead['stage'] ?? 'YENİ') . ' / ' . ($lead['priority'] ?? 'medium') . "\n" .
                        "- Notlar: " . ($lead['notes'] ?? 'Yok') . "\n" .
                        "- Etiketler: " . ($lead['tags'] ?? 'Yok');
                }
            } catch (Throwable $e) {
                log_message('debug', 'Platform_ai_responder::respond_chat lead lookup error: ' . $e->getMessage());
            }
        }

        $conversation = [
            ['role' => 'system', 'content' => $system_prompt],
        ];

        foreach ($history as $msg) {
            if (!empty($msg['content']) && !empty($msg['role'])) {
                $role = in_array($msg['role'], ['user', 'assistant'], true) ? $msg['role'] : 'user';
                $conversation[] = [
                    'role' => $role,
                    'content' => (string) $msg['content'],
                ];
            }
        }

        $conversation[] = ['role' => 'user', 'content' => $message];

        $reply = null;
        $used_provider = 'ai';
        $used_model = 'default';

        for ($i = 0; $i < self::MAX_TOOL_ITERATIONS; $i++) {
            $response = $this->CI->ai_llm_gateway->chat($conversation, [
                'tools' => self::PLATFORM_TOOLS,
                'temperature' => 0.4,
                'max_tokens' => 1024,
            ]);

            if ($response === null || empty($response['success'])) {
                break;
            }

            $used_provider = $response['provider'] ?? $used_provider;
            $used_model = $response['model'] ?? $used_model;

            $tool_calls = $response['tool_calls'] ?? [];
            if (empty($tool_calls)) {
                $reply = trim((string) ($response['reply'] ?? ''));
                break;
            }

            $conversation[] = [
                'role' => 'assistant',
                'content' => $response['reply'] ?? '',
                'tool_calls' => $tool_calls,
                'model_parts' => $response['model_parts'] ?? null,
            ];

            foreach ($tool_calls as $call) {
                $tool_name = $call['function']['name'] ?? '';
                $raw_args = $call['function']['arguments'] ?? '{}';
                $args = is_string($raw_args) ? (json_decode($raw_args, true) ?: []) : (array) $raw_args;

                $tool_result = $this->execute_platform_tool($tool_name, $args, '');

                $conversation[] = [
                    'role' => 'tool',
                    'name' => $tool_name,
                    'tool_call_id' => $call['id'] ?? '',
                    'content' => json_encode($tool_result, JSON_UNESCAPED_UNICODE),
                ];
            }
        }

        if (!empty($reply)) {
            return [
                'success' => true,
                'reply' => $reply,
                'provider' => $used_provider,
                'model' => $used_model,
            ];
        }

        return [
            'success' => false,
            'message' => 'AI sağlayıcısından geçerli bir yanıt alınamadı.',
        ];
    }

    /**
     * Tool Execution Router for Platform Sales Consultant.
     */
    protected function execute_platform_tool(string $name, array $args, string $sender_phone): array
    {
        try {
            switch ($name) {
                case 'search_knowledge_base':
                    $query = $args['query'] ?? '';
                    $category = $args['category'] ?? null;
                    return $this->tool_search_knowledge_base($query, $category);

                case 'get_competitor_analysis':
                    $competitor = $args['competitor_name'] ?? '';
                    return $this->tool_get_competitor_analysis($competitor);

                case 'get_sales_strategy':
                    $technique_id = $args['technique_id'] ?? null;
                    $channel = $args['channel'] ?? null;
                    $psychology = $args['customer_psychology'] ?? null;
                    return $this->tool_get_sales_strategy($technique_id, $channel, $psychology);

                case 'check_subdomain_availability':
                    $subdomain = strtolower(trim((string) ($args['subdomain'] ?? '')));
                    return $this->tool_check_subdomain_availability($subdomain);

                case 'create_demo_lead':
                    return $this->tool_create_demo_lead($args, $sender_phone);

                case 'get_codebase_features':
                    $module_key = $args['module_key'] ?? null;
                    return $this->tool_get_codebase_features($module_key);

                default:
                    return [
                        'success' => false,
                        'error' => "Bilinmeyen araç: {$name}",
                    ];
            }
        } catch (Throwable $e) {
            log_message('error', "Platform_ai_responder::execute_platform_tool [{$name}] error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function tool_search_knowledge_base(string $query, ?string $category): array
    {
        $query_lower = mb_strtolower(trim($query), 'UTF-8');
        $kb = $this->CI->platform_knowledge_base;

        $results = [];

        // 1. Competitor Search
        if ($category === null || $category === 'competitors') {
            $comp = $kb->find_competitor($query);
            if ($comp) {
                $results['competitor_match'] = $comp;
            }
        }

        // 2. Pricing Search
        if ($category === null || $category === 'pricing' || mb_stripos($query_lower, 'fiyat') !== false || mb_stripos($query_lower, 'ücret') !== false || mb_stripos($query_lower, 'paket') !== false) {
            $results['pricing'] = $kb->get_pricing();
        }

        // 3. Advantages Search
        if ($category === null || $category === 'advantages' || mb_stripos($query_lower, 'üstünlük') !== false || mb_stripos($query_lower, 'fark') !== false || mb_stripos($query_lower, 'neden') !== false) {
            $results['general_advantages'] = array_slice($kb->get_general_advantages(), 0, 8);
        }

        // 4. Methodology Search
        if ($category === null || $category === 'methodology' || mb_stripos($query_lower, 'itiraz') !== false || mb_stripos($query_lower, 'script') !== false) {
            $matrix = $kb->get_sales_methodology_matrix();
            $matched_methods = [];
            foreach ($matrix as $id => $item) {
                if (mb_stripos($item['technique_name'], $query_lower) !== false || mb_stripos($item['psychology'], $query_lower) !== false || mb_stripos($item['crm_tag'], $query_lower) !== false) {
                    $matched_methods[$id] = $item;
                }
            }
            if (!empty($matched_methods)) {
                $results['sales_methodology'] = array_slice($matched_methods, 0, 3);
            }
        }

        // 5. Codebase features
        if ($category === 'codebase' || mb_stripos($query_lower, 'özellik') !== false || mb_stripos($query_lower, 'modül') !== false) {
            $features = $kb->get_codebase_features();
            $results['codebase_summary'] = [
                'git_commit' => $features['git_commit'] ?? 'latest',
                'modules_count' => count($features['modules'] ?? []),
            ];
        }

        if (empty($results)) {
            $results['pricing'] = $kb->get_pricing();
            $results['general_advantages'] = array_slice($kb->get_general_advantages(), 0, 5);
        }

        return [
            'success' => true,
            'query' => $query,
            'results' => $results,
        ];
    }

    private function tool_get_competitor_analysis(string $competitor): array
    {
        $comp = $this->CI->platform_knowledge_base->find_competitor($competitor);
        if (!$comp) {
            return [
                'success' => false,
                'message' => "'{$competitor}' adlı rakip spesifik listede bulunamadı, ancak BooKi'nin genel 21 üstünlüğü ve dikey işletim sistemi mimarisi tüm rakiplere karşı geçerlidir.",
                'general_positioning' => 'Sadece randevu tutan rakiplere karşı: BooKi CRM + paket/seans + adisyon/POS + WhatsApp AI + pazaryeri tek sistemdedir.',
            ];
        }

        return [
            'success' => true,
            'competitor' => $comp,
        ];
    }

    private function tool_get_sales_strategy(?string $technique_id, ?string $channel, ?string $psychology): array
    {
        $matrix = $this->CI->platform_knowledge_base->get_sales_methodology_matrix();

        if (!empty($technique_id) && isset($matrix[$technique_id])) {
            return [
                'success' => true,
                'technique' => $matrix[$technique_id],
            ];
        }

        if (!empty($psychology)) {
            $psy_lower = mb_strtolower($psychology, 'UTF-8');
            foreach ($matrix as $id => $item) {
                if (mb_stripos($item['psychology'], $psy_lower) !== false || mb_stripos($item['technique_name'], $psy_lower) !== false) {
                    return [
                        'success' => true,
                        'match_id' => $id,
                        'technique' => $item,
                    ];
                }
            }
        }

        // Default to HO-02 (Value Ladder / ROI Proof) for price questions or TI-01 for speed
        return [
            'success' => true,
            'recommendation' => $matrix['HO-02'],
        ];
    }

    private function tool_check_subdomain_availability(string $subdomain): array
    {
        $clean = preg_replace('/[^a-z0-9\-]/', '', strtolower($subdomain));
        if (strlen($clean) < 3) {
            return [
                'success' => false,
                'message' => 'Subdomain en az 3 karakter olmalıdır.',
            ];
        }

        $reserved = ['admin', 'api', 'app', 'master', 'platform', 'booki', 'randevu', 'demo', 'superadmin'];
        if (in_array($clean, $reserved, true)) {
            return [
                'success' => true,
                'subdomain' => $clean,
                'available' => false,
                'reason' => 'Bu alan adı sistem tarafından rezerve edilmiştir.',
            ];
        }

        $exists = $this->CI->db->get_where('tenants', ['subdomain' => $clean])->row_array();
        if ($exists) {
            return [
                'success' => true,
                'subdomain' => $clean,
                'available' => false,
                'reason' => 'Bu subdomain başka bir işletme tarafından kullanılmaktadır.',
            ];
        }

        $app_domain = getenv('TENANT_APP_DOMAIN') ?: 'bookiapp.kibusiness.co';

        return [
            'success' => true,
            'subdomain' => $clean,
            'available' => true,
            'full_domain' => "https://{$clean}-{$app_domain}",
            'message' => "Harika haber! '{$clean}' alan adı boşta ve hemen adınıza tanımlanabilir.",
        ];
    }

    private function tool_create_demo_lead(array $args, string $sender_phone): array
    {
        $business_name = trim((string) ($args['business_name'] ?? 'İsimsiz İşletme'));
        $contact_name = trim((string) ($args['contact_name'] ?? ''));
        $phone = trim((string) ($args['phone'] ?? $sender_phone));
        $sector = trim((string) ($args['sector'] ?? 'Genel Hizmet'));
        $notes = trim((string) ($args['notes'] ?? ''));
        $crm_tag = trim((string) ($args['crm_tag'] ?? 'INBOUND_AI_SALES'));

        if ($phone === '' && $sender_phone !== '') {
            $phone = $sender_phone;
        }

        $lead_data = [
            'name' => $business_name,
            'contact_person' => $contact_name,
            'phone' => $phone,
            'whatsapp' => $phone,
            'whatsapp_number' => $phone,
            'sector' => $sector,
            'district' => 'Online Talep',
            'stage' => 'YENİ',
            'priority' => 'high',
            'lead_source' => 'WhatsApp AI Sales',
            'tags' => $crm_tag,
            'notes' => $notes . " [AI Satış Danışmanı tarafından WhatsApp üzerinden kaydedildi: " . date('Y-m-d H:i') . "]",
            'demo_start_date' => date('Y-m-d'),
            'demo_end_date' => date('Y-m-d', strtotime('+10 days')),
            'trial_status' => 'pending_setup',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->CI->db->insert('leads', $lead_data);
        $lead_id = $this->CI->db->insert_id();

        return [
            'success' => true,
            'lead_id' => $lead_id,
            'business_name' => $business_name,
            'phone' => $phone,
            'trial_info' => '10 günlük ücretsiz demo kaydı oluşturuldu.',
            'message' => "İşletme kaydınız başarıyla oluşturuldu. Satış ekibimiz ve teknik ekibimiz demo kurulumunuz için sizinle bu WhatsApp hattı üzerinden hemen irtibatta olacaktır.",
        ];
    }

    private function tool_get_codebase_features(?string $module_key): array
    {
        $catalog = $this->CI->platform_knowledge_base->get_codebase_features();

        if (!empty($module_key) && isset($catalog['modules'][$module_key])) {
            return [
                'success' => true,
                'git_commit' => $catalog['git_commit'] ?? 'unknown',
                'module' => $catalog['modules'][$module_key],
            ];
        }

        return [
            'success' => true,
            'system_name' => $catalog['system_name'] ?? 'BooKi',
            'git_commit' => $catalog['git_commit'] ?? 'unknown',
            'scanned_at' => $catalog['scanned_at'] ?? date('Y-m-d H:i:s'),
            'total_controllers' => $catalog['total_controllers'] ?? 0,
            'total_models' => $catalog['total_models'] ?? 0,
            'modules_summary' => array_keys($catalog['modules'] ?? []),
        ];
    }

    private function log_whatsapp_message(string $wa_id, string $direction, string $message): void
    {
        try {
            $this->CI->db->insert('whatsapp_messages', [
                'wa_id' => $wa_id,
                'direction' => $direction,
                'message' => $message,
                'status' => 'delivered',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'Platform_ai_responder::log_whatsapp_message error: ' . $e->getMessage());
        }
    }

    private function load_whatsapp_history(string $wa_id, int $limit = 6): array
    {
        try {
            $rows = $this->CI->db
                ->where('wa_id', $wa_id)
                ->order_by('id', 'DESC')
                ->limit($limit)
                ->get('whatsapp_messages')
                ->result_array();

            return array_reverse($rows);
        } catch (Throwable $e) {
            return [];
        }
    }
}
