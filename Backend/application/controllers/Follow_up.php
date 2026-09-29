<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Post-Service Follow-Up Controller.
 *
 * Allows tenant administrators to view, customize, and configure
 * automated post-service follow-up rules, view execution logs,
 * inspect customer NPS scores and response feedback.
 *
 * @package Controllers
 */
class Follow_up extends App_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('follow_up_rules_model');
        $this->load->model('follow_up_dispatches_model');
        $this->load->model('messaging_settings_model');
        $this->load->library('follow_up_engine');
        $this->load->helper('industry');
    }

    /**
     * Dashboard: List rules, dispatches, and performance statistics.
     */
    public function index(): void
    {
        method('get');

        if (cannot('view', PRIV_SYSTEM_SETTINGS)) {
            abort(403, 'Forbidden');
        }

        $tenant_id = $this->get_current_tenant_id();
        $blueprint_type = current_industry_code();
        $industry_family = $this->follow_up_engine->resolve_industry_family($blueprint_type);

        $rules = $this->follow_up_rules_model->get_rules_by_tenant($tenant_id, $blueprint_type);
        if (empty($rules)) {
            $rules = $this->follow_up_rules_model->get_rules_by_tenant($tenant_id);
        }

        $stats = $this->follow_up_dispatches_model->get_stats_for_tenant($tenant_id);
        $recent_dispatches = $this->follow_up_dispatches_model->get_tenant_dispatches($tenant_id, [], 30);

        // Compute service-level follow-up statistics
        $service_follow_up_stats = $this->compute_service_follow_up_stats();

        html_vars([
            'page_title' => 'Hizmet Sonrası Takip Motoru',
            'active_menu' => PRIV_SYSTEM_SETTINGS,
            'rules' => $rules,
            'stats' => $stats,
            'recent_dispatches' => $recent_dispatches,
            'blueprint_type' => $blueprint_type,
            'industry_family' => $industry_family,
            'service_follow_up_stats' => $service_follow_up_stats,
        ]);

        script_vars([
            'routes' => [
                'toggle_rule' => site_url('follow_up/toggle_rule'),
                'save_rule' => site_url('follow_up/save_rule'),
                'create_rule' => site_url('follow_up/create_rule'),
                'delete_rule' => site_url('follow_up/delete_rule'),
                'trigger_test' => site_url('follow_up/trigger_test'),
                'status_trigger' => site_url('follow_up/status_trigger'),
            ],
        ]);

        $this->load->view('pages/follow_up');
    }

    /**
     * Toggle rule active/inactive state.
     */
    public function toggle_rule(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('rule_id', 'string');
            check('is_active', 'numeric');

            $rule_id = (string) request('rule_id');
            $is_active = (bool) request('is_active');

            $rule = $this->follow_up_rules_model->find($rule_id);
            if (!$rule) {
                throw new InvalidArgumentException('Kural bulunamadı.');
            }

            $tenant_id = $this->get_current_tenant_id();

            // If the rule belongs to 'default', clone as tenant-specific rule
            if ($rule['tenant_id'] === 'default' && $tenant_id !== 'default') {
                $new_id = 'rule_' . bin2hex(random_bytes(16));
                $new_rule = $rule;
                $new_rule['id'] = $new_id;
                $new_rule['tenant_id'] = $tenant_id;
                $new_rule['is_active'] = $is_active ? 1 : 0;
                $this->follow_up_rules_model->create($new_rule);
            } else {
                $this->follow_up_rules_model->toggle_active($rule_id, $is_active);
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Save/update rule delay interval and template.
     */
    public function save_rule(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('rule_id', 'string');
            check('trigger_delay_interval', 'string');
            check('whatsapp_template_name', 'string');

            $rule_id = (string) request('rule_id');
            $interval = trim((string) request('trigger_delay_interval'));
            $template_name = trim((string) request('whatsapp_template_name'));
            $message_copy = trim((string) request('message_copy', ''));

            $rule = $this->follow_up_rules_model->find($rule_id);
            if (!$rule) {
                throw new InvalidArgumentException('Kural bulunamadı.');
            }

            $tenant_id = $this->get_current_tenant_id();

            $schema = $rule['dynamic_payload_schema_decoded'] ?? [];
            if ($message_copy !== '') {
                $schema['mesaj'] = $message_copy;
            }

            $update_data = [
                'trigger_delay_interval' => $interval,
                'whatsapp_template_name' => $template_name,
                'dynamic_payload_schema' => $schema,
            ];

            if ($rule['tenant_id'] === 'default' && $tenant_id !== 'default') {
                $new_id = 'rule_' . bin2hex(random_bytes(16));
                $new_rule = array_merge($rule, $update_data);
                $new_rule['id'] = $new_id;
                $new_rule['tenant_id'] = $tenant_id;
                $this->follow_up_rules_model->create($new_rule);
            } else {
                $this->follow_up_rules_model->update($rule_id, $update_data);
            }

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Create custom rule for tenant.
     */
    public function create_rule(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('rule_type', 'string');
            check('trigger_delay_interval', 'string');
            check('whatsapp_template_name', 'string');

            $tenant_id = $this->get_current_tenant_id();
            $blueprint_type = current_industry_code();
            $industry_family = $this->follow_up_engine->resolve_industry_family($blueprint_type);

            $rule_type = (string) request('rule_type');
            $interval = trim((string) request('trigger_delay_interval'));
            $template_name = trim((string) request('whatsapp_template_name'));
            $message_copy = trim((string) request('message_copy', ''));

            $schema = [];
            if ($message_copy !== '') {
                $schema['mesaj'] = $message_copy;
            }

            $id = $this->follow_up_rules_model->create([
                'tenant_id' => $tenant_id,
                'industry_family' => $industry_family,
                'blueprint_type' => $blueprint_type,
                'rule_type' => $rule_type,
                'trigger_delay_interval' => $interval,
                'whatsapp_template_name' => $template_name,
                'dynamic_payload_schema' => $schema,
                'is_active' => 1,
            ]);

            json_response(['success' => true, 'id' => $id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Delete custom rule.
     */
    public function delete_rule(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('rule_id', 'string');
            $rule_id = (string) request('rule_id');

            $rule = $this->follow_up_rules_model->find($rule_id);
            if (!$rule) {
                throw new InvalidArgumentException('Kural bulunamadı.');
            }

            $tenant_id = $this->get_current_tenant_id();
            if ($rule['tenant_id'] !== $tenant_id && $tenant_id !== 'default') {
                throw new RuntimeException('Varsayılan sistem şablonları silinemez, devre dışı bırakılabilir.');
            }

            $this->follow_up_rules_model->delete($rule_id);

            json_response(['success' => true]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Trigger status-driven follow-up (e.g. READY_FOR_PICKUP).
     */
    public function status_trigger(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_APPOINTMENTS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('booking_id', 'string');
            check('status', 'string');

            $booking_id = (string) request('booking_id');
            $status = (string) request('status');
            $artwork_name = (string) request('artwork_name', '');

            $dispatch_id = $this->follow_up_engine->on_status_trigger($booking_id, $status, [
                'artwork_name' => $artwork_name,
            ]);

            json_response(['success' => true, 'dispatch_id' => $dispatch_id]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Trigger immediate test dispatch for debugging.
     */
    public function trigger_test(): void
    {
        try {
            method('post');

            if (cannot('edit', PRIV_SYSTEM_SETTINGS)) {
                throw new RuntimeException('Yetkiniz bulunmamaktadır.');
            }

            check('booking_id', 'string');
            $booking_id = (string) request('booking_id');

            $dispatch_ids = $this->follow_up_engine->on_booking_completed($booking_id);

            json_response(['success' => true, 'dispatch_ids' => $dispatch_ids]);
        } catch (Throwable $e) {
            json_exception($e);
        }
    }

    /**
     * Helper to resolve current tenant ID.
     */
    private function get_current_tenant_id(): string
    {
        if (function_exists('tenant_context')) {
            $tc = tenant_context();
            if (is_array($tc) && !empty($tc['id'])) {
                return (string) $tc['id'];
            }
        }
        return 'default';
    }

    /**
     * Compute service-level follow-up statistics for the current tenant.
     *
     * Returns counts by priority level (critical/standard/optional),
     * total services requiring follow-up, and category breakdown.
     *
     * @return array Statistics array
     */
    private function compute_service_follow_up_stats(): array
    {
        $stats = [
            'critical' => 0,
            'standard' => 0,
            'optional' => 0,
            'required_total' => 0,
            'categories' => [],
        ];

        // Check if follow_up_required column exists
        if (!$this->db->field_exists('follow_up_required', 'services')) {
            return $stats;
        }

        // Count by priority
        $priority_counts = $this->db
            ->select('follow_up_priority, COUNT(*) as cnt')
            ->from('services')
            ->where('follow_up_required', 1)
            ->group_by('follow_up_priority')
            ->get()
            ->result_array();

        foreach ($priority_counts as $row) {
            $p = strtolower($row['follow_up_priority'] ?? 'optional');
            $cnt = (int) $row['cnt'];
            if (isset($stats[$p])) {
                $stats[$p] = $cnt;
            }
            $stats['required_total'] += $cnt;
        }

        // Count by category
        $category_counts = $this->db
            ->select('follow_up_category, COUNT(*) as cnt')
            ->from('services')
            ->where('follow_up_required', 1)
            ->where('follow_up_category IS NOT NULL', null, false)
            ->group_by('follow_up_category')
            ->get()
            ->result_array();

        $category_labels = [
            'medical_reaction' => 'Tıbbi Reaksiyon',
            'medical_protocol' => 'Tedavi Protokolü',
            'aftercare_safety' => 'Aftercare Güvenliği',
            'asset_delivery' => 'Varlık Teslimi',
            'compliance_check' => 'Uyum Kontrolü',
            'veterinary_postop' => 'Veteriner Postop',
            'retention_marketing' => 'Randevu Yenileme',
            'review_nps' => 'NPS & Değerlendirme',
        ];

        foreach ($category_counts as $row) {
            $cat = $row['follow_up_category'] ?? '';
            $label = $category_labels[$cat] ?? $cat;
            $stats['categories'][$label] = (int) $row['cnt'];
        }

        return $stats;
    }
}
