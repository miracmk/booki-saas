<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi AI Governance Service.
 *
 * Implements:
 * 1. AI Policy Resolution: Effective Policy = Business AI Policy ∩ Vertical Policy ∩ User Permission
 * 2. AI Authority Gating: read, suggest, propose, execute, approve
 * 3. Controlled Behavioral Learning Pipeline: Observed -> Suggested -> Owner Approval -> Business Rule -> Active
 * 4. Multi-Domain Escalation & Human Handoff: medical, legal, payment, angry_customer, uncertainty
 */
class Ai_governance_service
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('vertical_service');
        $this->CI->load->library('permission_service');
    }

    /**
     * Resolve effective AI policy for the current user and tenant.
     * Effective policy = Business AI Policy ∩ Vertical Policy ∩ User Permission
     *
     * @param int|null $user_id
     * @return array
     */
    public function get_effective_policy(?int $user_id = null): array
    {
        $user_id = $user_id ?: (int) session('user_id');
        $business_type = $this->CI->vertical_service->current_business_type();
        $bp = $this->CI->vertical_service->get_blueprint($business_type);

        // 1. Blueprint Vertical Policy
        $vertical_policy = $bp['ai_policy'] ?? [
            'tone' => 'friendly_professional',
            'language' => 'tr',
            'greeting_style' => 'Merhaba, size nasıl yardımcı olabilirim?',
            'allowed_terms' => [],
            'forbidden_terms' => [],
            'do_rules' => [],
            'dont_rules' => [],
            'business_rules' => [],
            'cancellation_policy' => '',
            'refund_policy' => '',
            'discount_policy' => '',
            'escalation_rules' => [],
            'allowed_actions' => ['search_customers', 'get_customer', 'list_appointments', 'get_services'],
            'approval_required_actions' => ['propose_appointment_create', 'propose_appointment_update', 'propose_appointment_cancel'],
            'forbidden_actions' => ['direct_delete_customer', 'direct_refund_payment'],
        ];

        // 2. Tenant Business AI Policy
        $tenant_policy = [];
        if ($this->CI->db->table_exists('tenant_ai_policies')) {
            $row = $this->CI->db->get('tenant_ai_policies')->row_array();
            if ($row) {
                $tenant_policy = [
                    'brand_name' => $row['brand_name'] ?? null,
                    'tone' => $row['tone'] ?? null,
                    'language' => $row['language'] ?? null,
                    'greeting_style' => $row['greeting_style'] ?? null,
                    'allowed_terms' => !empty($row['allowed_terms']) ? explode(',', $row['allowed_terms']) : null,
                    'forbidden_terms' => !empty($row['forbidden_terms']) ? explode(',', $row['forbidden_terms']) : null,
                    'do_rules' => !empty($row['do_rules']) ? explode("\n", $row['do_rules']) : null,
                    'dont_rules' => !empty($row['dont_rules']) ? explode("\n", $row['dont_rules']) : null,
                    'business_rules' => !empty($row['business_rules']) ? json_decode($row['business_rules'], true) : null,
                    'cancellation_policy' => $row['cancellation_policy'] ?? null,
                    'refund_policy' => $row['refund_policy'] ?? null,
                    'discount_policy' => $row['discount_policy'] ?? null,
                    'escalation_rules' => !empty($row['escalation_rules']) ? json_decode($row['escalation_rules'], true) : null,
                    'allowed_actions' => !empty($row['allowed_actions']) ? explode(',', $row['allowed_actions']) : null,
                    'approval_required_actions' => !empty($row['approval_required_actions']) ? explode(',', $row['approval_required_actions']) : null,
                    'forbidden_actions' => !empty($row['forbidden_actions']) ? explode(',', $row['forbidden_actions']) : null,
                ];
            }
        }

        // Merge Tenant overrides into Vertical policy
        $effective = $vertical_policy;
        foreach ($tenant_policy as $k => $v) {
            if ($v !== null && $v !== '') {
                if (is_array($v) && is_array($effective[$k] ?? null)) {
                    $effective[$k] = array_unique(array_merge($effective[$k], $v));
                } else {
                    $effective[$k] = $v;
                }
            }
        }

        // 3. Intersect with User Permissions (AI cannot exceed user's permissions)
        $effective['allowed_actions'] = array_filter(
            $effective['allowed_actions'] ?? [],
            fn (string $act) => $this->is_user_permitted_for_tool($act, $user_id)
        );

        $effective['approval_required_actions'] = array_filter(
            $effective['approval_required_actions'] ?? [],
            fn (string $act) => $this->is_user_permitted_for_tool($act, $user_id)
        );

        // Fetch dynamic approved business rules from memory pipeline
        $effective['active_learned_rules'] = $this->get_active_learned_rules();

        return $effective;
    }

    /**
     * Check tool execution clearance: 'execute' | 'propose' | 'suggest' | 'forbidden'.
     */
    public function authorize_tool(string $tool_name, ?int $user_id = null): string
    {
        $user_id = $user_id ?: (int) session('user_id');
        $policy = $this->get_effective_policy($user_id);

        // If tool operates on a resource the user cannot touch -> forbidden
        if (!$this->is_user_permitted_for_tool($tool_name, $user_id)) {
            return 'forbidden';
        }

        // Forbidden actions explicitly defined
        if (in_array($tool_name, $policy['forbidden_actions'] ?? [], true)) {
            return 'forbidden';
        }

        // Mutation actions on sensitive, financial, or appointment entities
        if (in_array($tool_name, $policy['approval_required_actions'] ?? [], true) ||
            str_starts_with($tool_name, 'propose_') ||
            str_starts_with($tool_name, 'create_') ||
            str_starts_with($tool_name, 'update_') ||
            str_starts_with($tool_name, 'delete_')) {
            return 'propose';
        }

        // Read actions
        if (in_array($tool_name, $policy['allowed_actions'] ?? [], true)) {
            return 'execute';
        }

        return 'suggest';
    }

    /**
     * Check if user permission allows this tool's underlying entity.
     */
    protected function is_user_permitted_for_tool(string $tool_name, int $user_id): bool
    {
        $map = [
            'search_customers' => ['view', 'customers'],
            'get_customer' => ['view', 'customers'],
            'get_customer_appointments' => ['view', 'appointments'],
            'get_appointments_by_date' => ['view', 'appointments'],
            'list_appointments' => ['view', 'appointments'],
            'get_appointment_details' => ['view', 'appointments'],
            'get_services' => ['view', 'services'],
            'get_providers' => ['view', 'users'],
            'check_availability' => ['view', 'appointments'],
            'propose_appointment_create' => ['add', 'appointments'],
            'propose_appointment_update' => ['edit', 'appointments'],
            'propose_appointment_cancel' => ['delete', 'appointments'],
            'propose_customer_update' => ['edit', 'customers'],
        ];

        if (!isset($map[$tool_name])) {
            return true;
        }

        [$action, $resource] = $map[$tool_name];
        return $this->CI->permission_service->can($action, $resource, $user_id);
    }

    /* -------------------------------------------------------------------------
     * AI CONTROLLED LEARNING PIPELINE
     * Observed -> Suggested -> Owner Approval -> Business Rule -> Active
     * ------------------------------------------------------------------------- */

    /**
     * Record an observation from user interactions.
     */
    public function record_observation(string $rule_type, string|array $rule_content, ?string $context = null): int
    {
        if (!$this->CI->db->table_exists('ai_learned_rules')) {
            return 0;
        }

        if (is_array($rule_content)) {
            $rule_content = json_encode($rule_content, JSON_UNESCAPED_UNICODE);
        }

        $this->CI->db->insert('ai_learned_rules', [
            'stage' => 'observed',
            'status' => 'observed',
            'rule_type' => $rule_type,
            'rule_content' => $rule_content,
            'context' => $context,
            'suggested_by' => 'ai',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->CI->db->insert_id();
    }

    /**
     * Promote observation to 'suggested' stage for owner review, or insert a newly suggested rule.
     */
    public function suggest_rule(mixed $typeOrId, ?string $content = null, mixed $context = null, ?int $observation_id = null): int|bool
    {
        if (is_int($typeOrId) && $content === null) {
            return (bool) $this->CI->db->update('ai_learned_rules', [
                'stage' => 'suggested',
                'status' => 'suggested',
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['id' => $typeOrId]);
        }

        $ctx = is_array($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : (string) $context;
        $payload = [
            'stage' => 'suggested',
            'status' => 'suggested',
            'rule_type' => (string) $typeOrId,
            'rule_content' => (string) $content,
            'context' => $ctx,
            'suggested_by' => 'ai',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->CI->db->insert('ai_learned_rules', $payload);
        return (int) $this->CI->db->insert_id();
    }

    /**
     * Owner approves suggested rule -> becomes 'active' business rule.
     */
    public function approve_rule(int $rule_id, int $user_id): bool
    {
        $updated = $this->CI->db->update('ai_learned_rules', [
            'stage' => 'active',
            'status' => 'active',
            'approved_by_user_id' => $user_id,
            'approved_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $rule_id]);

        if ($updated && function_exists('audit_log')) {
            audit_log('ai.rule_approve', 'ai_rule', $rule_id, ['approved_by' => $user_id]);
        }

        return (bool) $updated;
    }

    /**
     * Get list of currently active learned rules to inject into system prompt.
     */
    public function get_active_learned_rules(): array
    {
        if (!$this->CI->db->table_exists('ai_learned_rules')) {
            return [];
        }

        return $this->CI->db
            ->select('id, rule_type, rule_content')
            ->where('stage', 'active')
            ->get('ai_learned_rules')
            ->result_array();
    }

    /* -------------------------------------------------------------------------
     * AI MULTI-DOMAIN ESCALATION & HUMAN HANDOFF
     * ------------------------------------------------------------------------- */

    /**
     * Evaluate incoming text for escalation triggers.
     *
     * @param string $message
     * @return array|null Returns escalation details or null if no escalation needed
    /**
     * Alias for check_escalation.
     */
    public function detect_escalation(string $message): ?array
    {
        return $this->check_escalation($message);
    }

    public function check_escalation(string $message): ?array
    {
        $msg = mb_strtolower($message, 'UTF-8');

        $rules = [
            'medical' => [
                'keywords' => ['ilaç', 'yan etki', 'alerji', 'kanama', 'enfeksiyon', 'ağrı', 'ameliyat', 'reçete', 'doktor acil'],
                'role' => 'doctor',
                'reason' => 'Tıbbi şikayet veya reçete/yan etki konusu tespit edildi.',
            ],
            'legal' => [
                'keywords' => ['dava', 'ihtarname', 'avukat', 'tazminat', 'mahkeme', 'savcılık', 'savcı', 'kvkk ihlali'],
                'role' => 'lawyer',
                'reason' => 'Hukuki uyuşmazlık veya dava/ihtar riski tespit edildi.',
            ],
            'payment' => [
                'keywords' => ['fazla çekim', 'dolandırıcılık', 'itiraz', 'chargeback', 'kartımdan izinsiz', 'iade yapmadınız'],
                'role' => 'manager',
                'reason' => 'Finansal itiraz veya ödeme ihtilafı tespit edildi.',
            ],
            'angry_customer' => [
                'keywords' => ['şikayetçiyim', 'berbat', 'dava edeceğim', 'rezalet', 'tüketici hakem heyeti', 'şikayet var', 'yetkili biriyle görüşmek'],
                'role' => 'owner',
                'reason' => 'Yüksek müşteri memnuniyetsizliği ve şikayet tespiti.',
            ],
        ];

        foreach ($rules as $type => $data) {
            foreach ($data['keywords'] as $kw) {
                if (str_contains($msg, $kw)) {
                    return [
                        'domain' => $type,
                        'type' => $type,
                        'keyword_match' => $kw,
                        'assigned_role' => $data['role'],
                        'reason' => $data['reason'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Create an escalation handoff record (wrapper for create_handoff).
     */
    public function create_escalation_handoff(
        int|string|null $customerIdOrContext,
        string $domain,
        string $message,
        string $trigger,
        array $extra = []
    ): int {
        return $this->create_handoff($domain, $trigger, array_merge($extra, [
            'customer_id' => is_numeric($customerIdOrContext) ? (int) $customerIdOrContext : null,
            'summary' => $message,
        ]));
    }

    /**
     * Create an escalation handoff record.
     */
    public function create_handoff(string $type, string $reason, array $context = []): int
    {
        if (!$this->CI->db->table_exists('ai_escalation_handoffs')) {
            return 0;
        }

        $payload = [
            'escalation_type' => $type,
            'customer_id' => $context['customer_id'] ?? null,
            'conversation_id' => $context['conversation_id'] ?? null,
            'reason' => $reason,
            'context_summary' => !empty($context['summary']) ? (string) $context['summary'] : null,
            'assigned_role' => $context['assigned_role'] ?? 'owner',
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->CI->db->insert('ai_escalation_handoffs', $payload);
        $handoff_id = (int) $this->CI->db->insert_id();

        if (function_exists('audit_log')) {
            audit_log('ai.escalation_handoff', 'handoff', $handoff_id, [
                'type' => $type,
                'role' => $payload['assigned_role'],
            ]);
        }

        return $handoff_id;
    }
}
