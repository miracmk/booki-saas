<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - SaaS Sales CRM & Lead Operations Model
 *
 * Runs strictly against the master database (`ki_reservation_master`).
 * Provides high-performance CRM operations, Kanban pipeline state machine,
 * KPI aggregation, field visit interviews, and global search.
 * -------------------------------------------------------------------------- */

class Leads_model extends CI_Model
{
    public const STAGES = [
        'New Lead' => 'Yeni Lead',
        'Qualified' => 'Nitelikli Lead',
        'Visit Planned' => 'Ziyaret Planlandı',
        'Visited' => 'Ziyaret Edildi',
        'Meeting' => 'Görüşme Yapıldı',
        'Demo Presented' => 'Demo Sunuldu',
        'Trial Started' => '10-Gün Demo Başladı',
        'Follow-up' => 'Takip / Yeniden Ziyaret',
        'Won' => 'Kazanıldı (Satış Yapıldı)',
        'Lost' => 'Kaybedildi',
    ];

    public function __construct()
    {
    }

    /**
     * Get paginated and filtered leads list.
     */
    public function get_leads(array $filters = [], int $limit = 25, int $offset = 0, string $sort = 'id', string $order = 'asc'): array
    {
        $this->apply_lead_filters($filters);
        $total = $this->db->count_all_results('leads');

        $this->apply_lead_filters($filters);

        $allowed_sorts = ['id', 'name', 'sector', 'district', 'stage', 'created_at', 'potential_mrr', 'demo_end_date', 'next_action_date'];
        $sort_col = in_array($sort, $allowed_sorts, true) ? $sort : 'id';
        $sort_order = strtolower($order) === 'desc' ? 'desc' : 'asc';

        $leads = $this->db
            ->order_by($sort_col, $sort_order)
            ->limit($limit, $offset)
            ->get('leads')
            ->result_array();

        foreach ($leads as &$lead) {
            $lead = $this->format_lead_metadata($lead);
        }
        unset($lead);

        return [
            'success' => true,
            'leads' => $leads,
            'total' => (int) $total,
            'limit' => (int) $limit,
            'offset' => (int) $offset,
            'page' => (int) floor($offset / max(1, $limit)) + 1,
            'total_pages' => max(1, (int) ceil($total / max(1, $limit))),
            'current_page' => (int) floor($offset / max(1, $limit)) + 1,
        ];
    }

    /**
     * Get all leads grouped by Kanban stage.
     */
    public function get_pipeline_kanban(array $filters = []): array
    {
        $pipeline = [];
        foreach (self::STAGES as $stage_key => $stage_label) {
            $pipeline[$stage_key] = [
                'stage_key' => $stage_key,
                'stage_label' => $stage_label,
                'count' => 0,
                'total_mrr' => 0.00,
                'leads' => [],
            ];
        }

        $this->apply_lead_filters($filters);
        $leads = $this->db
            ->order_by('priority', 'desc')
            ->order_by('next_action_date', 'asc')
            ->order_by('updated_at', 'desc')
            ->get('leads')
            ->result_array();

        foreach ($leads as $lead) {
            $lead = $this->format_lead_metadata($lead);
            $stage = $lead['stage'];
            if (!isset($pipeline[$stage])) {
                $stage = 'Visit Planned';
            }

            $pipeline[$stage]['leads'][] = $lead;
            $pipeline[$stage]['count']++;
            $pipeline[$stage]['total_mrr'] += (float) ($lead['potential_mrr'] ?? 0);
        }

        return $pipeline;
    }

    /**
     * Get single lead by ID.
     */
    public function get_lead_by_id(int $id): ?array
    {
        $lead = $this->db->get_where('leads', ['id' => $id])->row_array();
        return $lead ? $this->format_lead_metadata($lead) : null;
    }

    /**
     * Get full lead details including timeline, activities, tasks, visits, and trials.
     */
    public function get_lead_detail(int $id): ?array
    {
        $lead = $this->get_lead_by_id($id);
        if (!$lead) {
            return null;
        }

        $activities = $this->db
            ->where('id_leads', $id)
            ->order_by('created_at', 'desc')
            ->get('lead_activities')
            ->result_array();

        $stage_history = $this->db
            ->where('id_leads', $id)
            ->order_by('created_at', 'desc')
            ->get('lead_stage_history')
            ->result_array();

        $tasks = $this->db
            ->where('id_leads', $id)
            ->order_by('due_date', 'asc')
            ->order_by('due_time', 'asc')
            ->get('lead_tasks')
            ->result_array();

        $visits = $this->db
            ->where('id_leads', $id)
            ->order_by('visit_date', 'desc')
            ->get('lead_visits')
            ->result_array();

        $converted_tenant = null;
        if (!empty($lead['converted_tenant_id'])) {
            $converted_tenant = $this->db
                ->get_where('tenants', ['id' => $lead['converted_tenant_id']])
                ->row_array();
        }

        $onboarding_session = null;
        if ($converted_tenant) {
            $onboarding_session = $this->db
                ->get_where('onboarding_sessions', ['id_tenants' => $converted_tenant['id']])
                ->row_array();
        }

        return [
            'lead' => $lead,
            'activities' => $activities,
            'stage_history' => $stage_history,
            'tasks' => $tasks,
            'visits' => $visits,
            'converted_tenant' => $converted_tenant,
            'onboarding_session' => $onboarding_session,
        ];
    }

    /**
     * Create a new lead and log initial activity.
     */
    public function create_lead(array $data, string $actor = 'Sistem'): int
    {
        $now = date('Y-m-d H:i:s');
        $stage = $data['stage'] ?? 'New Lead';

        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        if (!isset($data['potential_mrr']) || (float) $data['potential_mrr'] <= 0) {
            $prices = ['Starter' => 1999.00, 'Professional' => 2199.00, 'Enterprise' => 4499.00];
            $data['potential_mrr'] = $prices[$data['package'] ?? ''] ?? 2199.00;
        }

        $this->db->insert('leads', $data);
        $lead_id = $this->db->insert_id();

        $this->add_activity($lead_id, 'note', 'Yeni Lead Oluşturuldu', 'İşletme sisteme kaydedildi. Aşama: ' . $stage, $actor);
        $this->record_stage_history($lead_id, null, $stage, $actor, 'Lead oluşturma');

        return $lead_id;
    }

    /**
     * Update an existing lead.
     */
    public function update_lead(int $id, array $data, string $actor = 'Admin'): bool
    {
        $current = $this->get_lead_by_id($id);
        if (!$current) {
            return false;
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        // Check if stage is changing
        if (isset($data['stage']) && $data['stage'] !== $current['stage']) {
            $old_stage = $current['stage'];
            $new_stage = $data['stage'];
            $this->handle_stage_side_effects($id, $data, $old_stage, $new_stage, $actor);
        }

        $this->db->where('id', $id)->update('leads', $data);
        return true;
    }

    /**
     * Drag & Drop Kanban stage transition.
     */
    public function update_stage(int $id, string $new_stage, string $actor = 'Admin', string $reason = ''): array
    {
        $lead = $this->get_lead_by_id($id);
        if (!$lead) {
            throw new InvalidArgumentException("Lead #{$id} bulunamadı.");
        }

        $old_stage = $lead['stage'];
        if ($old_stage === $new_stage) {
            return ['success' => true, 'changed' => false, 'lead' => $lead];
        }

        $updates = [
            'stage' => $new_stage,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->handle_stage_side_effects($id, $updates, $old_stage, $new_stage, $actor, $reason);

        $this->db->where('id', $id)->update('leads', $updates);
        $updated_lead = $this->get_lead_by_id($id);

        return [
            'success' => true,
            'changed' => true,
            'old_stage' => $old_stage,
            'new_stage' => $new_stage,
            'lead' => $updated_lead,
        ];
    }

    /**
     * Handle stage side effects (trial dates, activities, tasks, tenant flow trigger).
     */
    private function handle_stage_side_effects(int $lead_id, array &$updates, string $old_stage, string $new_stage, string $actor, string $reason = ''): void
    {
        $now_date = date('Y-m-d');
        $now_datetime = date('Y-m-d H:i:s');

        // If stage is Trial Started -> start 10-day counter
        if ($new_stage === 'Trial Started') {
            $updates['trial_status'] = 'active';
            if (empty($updates['demo_start_date'])) {
                $updates['demo_start_date'] = $now_date;
            }
            if (empty($updates['demo_end_date'])) {
                $updates['demo_end_date'] = date('Y-m-d', strtotime('+10 days'));
            }
            $updates['priority'] = 'high';
            $updates['next_action'] = '10-Günlük Demo Bitiş Kontrolü';
            $updates['next_action_date'] = $updates['demo_end_date'];
        } elseif ($new_stage === 'Follow-up') {
            $updates['trial_status'] = 'ending_soon';
            $updates['priority'] = 'urgent';
            if (empty($updates['next_action_date'])) {
                $updates['next_action_date'] = date('Y-m-d', strtotime('+2 days'));
            }
            $updates['next_action'] = 'Yeniden Ziyaret & Tahsilat';
        } elseif ($new_stage === 'Won') {
            $updates['trial_status'] = 'converted';
            $updates['priority'] = 'high';
            $updates['next_action'] = 'Tenant Kurulumu & Onboarding Gönderimi';
            $updates['next_action_date'] = $now_date;
        } elseif ($new_stage === 'Lost') {
            $updates['trial_status'] = 'cancelled';
        }

        // Add activity
        $title = "Aşama Değiştirildi: {$old_stage} → {$new_stage}";
        $desc = $reason !== '' ? "Gerekçe: {$reason}" : "Satış aşaması güncellendi.";
        $this->add_activity($lead_id, 'stage_change', $title, $desc, $actor);

        // Record stage history
        $this->record_stage_history($lead_id, $old_stage, $new_stage, $actor, $reason);
    }

    /**
     * Record an activity in lead timeline.
     */
    public function add_activity(int $lead_id, string $type, string $title, string $description, string $performed_by, array $metadata = []): int
    {
        $this->db->insert('lead_activities', [
            'id_leads' => $lead_id,
            'activity_type' => $type,
            'title' => $title,
            'description' => $description,
            'performed_by' => $performed_by,
            'metadata_json' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $activity_id = (int) $this->db->insert_id();

        $this->db->where('id', $lead_id)->update('leads', ['updated_at' => date('Y-m-d H:i:s')]);
        return $activity_id;
    }

    /**
     * Record stage change history.
     */
    public function record_stage_history(int $lead_id, ?string $old_stage, string $new_stage, string $changed_by, string $reason = ''): int
    {
        $this->db->insert('lead_stage_history', [
            'id_leads' => $lead_id,
            'old_stage' => $old_stage,
            'new_stage' => $new_stage,
            'changed_by' => $changed_by,
            'reason_notes' => $reason !== '' ? $reason : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }

    /**
     * Get recent activities across all leads for dashboard feed.
     */
    public function get_recent_activities(int $limit = 20): array
    {
        return $this->db
            ->select('lead_activities.*, leads.name as lead_name, leads.phone as lead_phone, leads.sector as lead_sector, leads.district as lead_district')
            ->from('lead_activities')
            ->join('leads', 'leads.id = lead_activities.id_leads', 'left')
            ->order_by('lead_activities.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Record a field visit interview.
     */
    public function add_visit(int $lead_id, array $visit_data, string $created_by): int
    {
        $now = date('Y-m-d H:i:s');
        $visit_data['id_leads'] = $lead_id;
        $visit_data['created_by'] = $created_by;
        $visit_data['created_at'] = $now;

        if (isset($visit_data['contact_title']) && !isset($visit_data['position'])) {
            $visit_data['position'] = $visit_data['contact_title'];
        }
        if (isset($visit_data['current_method']) && !isset($visit_data['current_booking_method'])) {
            $visit_data['current_booking_method'] = $visit_data['current_method'];
        }
        if (isset($visit_data['new_stage']) && !isset($visit_data['suggested_stage'])) {
            $visit_data['suggested_stage'] = $visit_data['new_stage'];
        }

        $allowed_visit_fields = [
            'id_leads', 'visit_date', 'contact_person', 'position', 'current_booking_method',
            'current_system', 'staff_count', 'resource_count', 'monthly_appointments',
            'biggest_problem', 'most_needed_feature', 'uses_whatsapp', 'uses_online_booking',
            'competitor_system', 'budget_approach', 'decision_maker', 'purchase_timeframe',
            'objections', 'quick_tags', 'suggested_stage', 'notes', 'created_by', 'created_at'
        ];

        $insert_data = array_intersect_key($visit_data, array_flip($allowed_visit_fields));
        $this->db->insert('lead_visits', $insert_data);
        $visit_id = $this->db->insert_id();

        // Create timeline activity
        $contact = $visit_data['contact_person'] ?? 'Yetkili';
        $summary = "Saha Ziyareti Tamamlandı ({$contact})";
        $notes = $visit_data['notes'] ?? '';
        if (!empty($visit_data['quick_tags'])) {
            $notes = "[Etiketler: {$visit_data['quick_tags']}] " . $notes;
        }

        $this->add_activity($lead_id, 'visit', $summary, $notes, $created_by, $visit_data);

        // Auto-update stage if suggested and appropriate
        if (!empty($visit_data['suggested_stage'])) {
            $this->update_stage($lead_id, $visit_data['suggested_stage'], $created_by, 'Saha görüşmesi sonucuna göre otomatik önerildi.');
        } else {
            $lead = $this->get_lead_by_id($lead_id);
            if ($lead && in_array($lead['stage'], ['New Lead', 'Qualified', 'Visit Planned'], true)) {
                $this->update_stage($lead_id, 'Visited', $created_by, 'Ziyaret formu dolduruldu.');
            }
        }

        return $visit_id;
    }

    /**
     * Add a task / follow-up for a lead.
     */
    public function add_task(array $task_data, string $created_by = 'Admin'): int
    {
        $task_data['created_at'] = date('Y-m-d H:i:s');
        $task_data['task_type'] = $task_data['task_type'] ?? 'follow_up';
        if (($task_data['priority'] ?? '') === 'normal') {
            $task_data['priority'] = 'medium';
        }
        if (isset($task_data['description']) && !isset($task_data['notes'])) {
            $task_data['notes'] = $task_data['description'];
        }

        $allowed_task_fields = [
            'id_leads', 'task_type', 'title', 'due_date', 'due_time',
            'priority', 'assigned_to', 'status', 'notes', 'completed_at', 'created_at'
        ];
        $insert_data = array_intersect_key($task_data, array_flip($allowed_task_fields));

        $this->db->insert('lead_tasks', $insert_data);
        $task_id = $this->db->insert_id();

        if (!empty($task_data['id_leads'])) {
            $lead_id = (int) $task_data['id_leads'];
            $due = $task_data['due_date'] . (!empty($task_data['due_time']) ? ' ' . $task_data['due_time'] : '');
            $this->add_activity(
                $lead_id,
                'task',
                "Yeni Görev: {$task_data['title']}",
                "Görev Tipi: {$task_data['task_type']} • Vade: {$due}",
                $created_by
            );

            // Update lead next action
            $this->db->where('id', $lead_id)->update('leads', [
                'next_action' => $task_data['title'],
                'next_action_date' => $task_data['due_date'],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return $task_id;
    }

    /**
     * Get tasks partitioned by status and urgency.
     */
    public function get_tasks_summary(array $filters = []): array
    {
        $today = date('Y-m-d');

        $base = $this->db
            ->select('lead_tasks.*, leads.name as lead_name, leads.phone as lead_phone, leads.district as lead_district, leads.sector as lead_sector')
            ->from('lead_tasks')
            ->join('leads', 'leads.id = lead_tasks.id_leads', 'left');

        if (!empty($filters['assigned_to'])) {
            $base->where('lead_tasks.assigned_to', $filters['assigned_to']);
        }
        if (!empty($filters['id_leads'])) {
            $base->where('lead_tasks.id_leads', $filters['id_leads']);
        }

        $all_tasks = $base->order_by('due_date', 'asc')->order_by('due_time', 'asc')->get()->result_array();

        $summary = [
            'today' => [],
            'overdue' => [],
            'upcoming' => [],
            'completed' => [],
            'total_pending' => 0,
            'overdue_count' => 0,
        ];

        foreach ($all_tasks as $t) {
            if ($t['status'] === 'completed') {
                $summary['completed'][] = $t;
                continue;
            }

            $summary['total_pending']++;
            if ($t['due_date'] < $today) {
                $t['is_overdue'] = true;
                $summary['overdue'][] = $t;
                $summary['overdue_count']++;
            } elseif ($t['due_date'] === $today) {
                $t['is_today'] = true;
                $summary['today'][] = $t;
            } else {
                $t['is_upcoming'] = true;
                $summary['upcoming'][] = $t;
            }
        }

        return $summary;
    }

    /**
     * Mark task as completed or toggle status.
     */
    public function update_task_status(int $task_id, string $status, string $actor = 'Admin'): bool
    {
        $task = $this->db->get_where('lead_tasks', ['id' => $task_id])->row_array();
        if (!$task) {
            return false;
        }

        $updates = [
            'status' => $status,
            'completed_at' => ($status === 'completed') ? date('Y-m-d H:i:s') : null,
        ];

        $this->db->where('id', $task_id)->update('lead_tasks', $updates);

        if (!empty($task['id_leads'])) {
            $this->add_activity(
                (int) $task['id_leads'],
                'task',
                "Görev Durumu: {$status}",
                "Görev: {$task['title']}",
                $actor
            );
        }

        return true;
    }

    /**
     * Get real-time comprehensive KPIs for dashboard.
     */
    public function get_kpis(): array
    {
        $today = date('Y-m-d');
        $this_month_start = date('Y-m-01 00:00:00');

        $total_leads = $this->db->count_all_results('leads');
        $new_leads = $this->db->where('stage', 'New Lead')->count_all_results('leads');
        $qualified_leads = $this->db->where('stage', 'Qualified')->count_all_results('leads');

        $today_visits = $this->db
            ->like('visit_date', $today, 'after')
            ->count_all_results('lead_visits');

        $upcoming_followups = $this->db
            ->where('stage', 'Follow-up')
            ->count_all_results('leads');

        $active_trials = $this->db
            ->where('stage', 'Trial Started')
            ->count_all_results('leads');

        // Trials ending soon (<= 3 days)
        $three_days_ahead = date('Y-m-d', strtotime('+3 days'));
        $trials_ending_soon = $this->db
            ->where('stage', 'Trial Started')
            ->where('demo_end_date <=', $three_days_ahead)
            ->where('demo_end_date >=', $today)
            ->count_all_results('leads');

        $won_this_month = $this->db
            ->where('stage', 'Won')
            ->where('updated_at >=', $this_month_start)
            ->count_all_results('leads');

        $lost_this_month = $this->db
            ->where('stage', 'Lost')
            ->where('updated_at >=', $this_month_start)
            ->count_all_results('leads');

        $total_won = $this->db->where('stage', 'Won')->count_all_results('leads');
        $total_lost = $this->db->where('stage', 'Lost')->count_all_results('leads');

        // Total MRR calculation
        $won_leads = $this->db
            ->select_sum('potential_mrr')
            ->where('stage', 'Won')
            ->get('leads')
            ->row_array();
        $total_won_mrr = (float) ($won_leads['potential_mrr'] ?? 0.00);

        $pipeline_leads = $this->db
            ->select_sum('potential_mrr')
            ->where_not_in('stage', ['Won', 'Lost'])
            ->get('leads')
            ->row_array();
        $pipeline_potential_mrr = (float) ($pipeline_leads['potential_mrr'] ?? 0.00);

        // Tenants metrics from master DB
        $active_tenants = $this->db->where('status', 'active')->count_all_results('tenants');
        $total_tenants = $this->db->count_all_results('tenants');

        $onboarding_pending = $this->db->where('status', 'not_opened')->count_all_results('onboarding_sessions');
        $onboarding_in_progress = $this->db->where_in('status', ['opened', 'started', 'in_progress'])->count_all_results('onboarding_sessions');
        $onboarding_completed = $this->db->where('status', 'completed')->count_all_results('onboarding_sessions');

        // Overdue tasks
        $overdue_tasks = $this->db
            ->where('status !=', 'completed')
            ->where('due_date <', $today)
            ->count_all_results('lead_tasks');

        // Conversion Rate
        $total_closed = $total_won + $total_lost;
        $conversion_rate = $total_closed > 0 ? round(($total_won / $total_closed) * 100, 1) : 0.0;

        return [
            'total_leads' => $total_leads,
            'new_leads' => $new_leads,
            'qualified_leads' => $qualified_leads,
            'today_visits' => $today_visits,
            'upcoming_followups' => $upcoming_followups,
            'active_trials' => $active_trials,
            'trials_ending_soon' => $trials_ending_soon,
            'won_this_month' => $won_this_month,
            'lost_this_month' => $lost_this_month,
            'total_won' => $total_won,
            'total_lost' => $total_lost,
            'active_tenants' => $active_tenants,
            'total_tenants' => $total_tenants,
            'onboarding_pending' => $onboarding_pending,
            'onboarding_in_progress' => $onboarding_in_progress,
            'onboarding_completed' => $onboarding_completed,
            'overdue_tasks' => $overdue_tasks,
            'total_won_mrr' => $total_won_mrr,
            'pipeline_potential_mrr' => $pipeline_potential_mrr,
            'conversion_rate' => $conversion_rate,
        ];
    }

    /**
     * Duplicate check by phone, email, or name + address.
     */
    public function check_duplicate(string $name, string $phone = '', string $email = '', string $address = '', ?int $exclude_id = null): ?array
    {
        $has_condition = false;
        $this->db->group_start();

        if ($phone !== '') {
            $clean_phone = preg_replace('/[^0-9]/', '', $phone);
            if (strlen($clean_phone) >= 7) {
                $last7 = substr($clean_phone, -7);
                $this->db->or_like('phone', $last7);
                $this->db->or_like('whatsapp', $last7);
                $has_condition = true;
            }
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->db->or_where('email', $email);
            $has_condition = true;
        }

        if ($name !== '') {
            if ($address !== '') {
                $this->db->or_group_start()
                    ->where('name', $name)
                    ->where('address', $address)
                    ->group_end();
            } else {
                $this->db->or_where('name', $name);
            }
            $has_condition = true;
        }

        $this->db->group_end();

        if (!$has_condition) {
            return null;
        }

        if ($exclude_id !== null) {
            $this->db->where('id !=', $exclude_id);
        }

        return $this->db->limit(1)->get('leads')->row_array();
    }

    /**
     * Cross-entity global search (Lead, Tenant, Phone, Email, IDs).
     */
    public function global_search(string $query, int $limit = 15): array
    {
        $q = trim($query);
        if ($q === '') {
            return ['results' => []];
        }

        $results = [];

        // 1. Search Leads
        $lead_builder = $this->db
            ->group_start()
            ->like('name', $q)
            ->or_like('phone', $q)
            ->or_like('whatsapp', $q)
            ->or_like('email', $q)
            ->or_like('contact_person', $q)
            ->or_like('district', $q)
            ->or_like('sector', $q);

        $digits = preg_replace('/[^0-9]/', '', $q);
        if (strlen($digits) >= 3) {
            $lead_builder->or_where("REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '(', ''), ')', ''), '-', '') LIKE '%{$digits}%'", null, false);
            $lead_builder->or_where("REPLACE(REPLACE(REPLACE(REPLACE(whatsapp, ' ', ''), '(', ''), ')', ''), '-', '') LIKE '%{$digits}%'", null, false);
        }

        $lead_rows = $lead_builder->group_end()
            ->limit($limit)
            ->get('leads')
            ->result_array();

        foreach ($lead_rows as $lr) {
            $results[] = [
                'type' => 'lead',
                'id' => (int) $lr['id'],
                'title' => $lr['name'],
                'subtitle' => "{$lr['sector']} • {$lr['district']} • Aşama: {$lr['stage']}",
                'phone' => $lr['phone'] ?: $lr['whatsapp'],
                'badge' => $lr['stage'],
                'url' => '#leads?lead_id=' . $lr['id'],
            ];
        }

        // 2. Search Tenants
        $tenant_rows = $this->db
            ->group_start()
            ->like('subdomain', $q)
            ->or_like('company_name', $q)
            ->or_like('custom_domain', $q)
            ->or_like('phone_number', $q)
            ->group_end()
            ->limit($limit)
            ->get('tenants')
            ->result_array();

        foreach ($tenant_rows as $tr) {
            $name = $tr['company_name'] ?: $tr['subdomain'];
            $results[] = [
                'type' => 'tenant',
                'id' => (int) $tr['id'],
                'title' => $name . " ({$tr['subdomain']}.bookiapp.kibusiness.co)",
                'subtitle' => "Plan: {$tr['plan']} • Durum: {$tr['status']}",
                'phone' => $tr['phone_number'] ?? '',
                'badge' => $tr['status'],
                'url' => '#tenants?tenant_id=' . $tr['id'],
            ];
        }

        return ['results' => $results, 'query' => $q, 'total' => count($results)];
    }

    /**
     * Distinct sectors list for dropdown filters.
     */
    public function get_distinct_sectors(): array
    {
        $rows = $this->db
            ->select('sector, count(*) as count')
            ->group_by('sector')
            ->order_by('count', 'desc')
            ->get('leads')
            ->result_array();
        return $rows;
    }

    /**
     * Distinct districts list for dropdown filters.
     */
    public function get_distinct_districts(): array
    {
        $rows = $this->db
            ->select('district, count(*) as count')
            ->group_by('district')
            ->order_by('count', 'desc')
            ->get('leads')
            ->result_array();
        return $rows;
    }

    /**
     * Format lead row with helper metrics (days left in trial, etc.)
     */
    public function format_lead_metadata(array $lead): array
    {
        $today = new DateTime('today');

        // Trial calculation
        $lead['demo_days_left'] = null;
        $lead['demo_is_expired'] = false;
        if (!empty($lead['demo_end_date'])) {
            $end_date = new DateTime($lead['demo_end_date']);
            $diff = (int) $today->diff($end_date)->format('%r%a');
            $lead['demo_days_left'] = $diff;
            $lead['demo_is_expired'] = $diff < 0;
        }

        // Clean WhatsApp link
        $phone_raw = (!empty($lead['whatsapp']) ? $lead['whatsapp'] : ($lead['phone'] ?? ''));
        $clean_digits = preg_replace('/[^0-9]/', '', (string) $phone_raw);
        if ($clean_digits !== '') {
            if (!str_starts_with($clean_digits, '90')) {
                $clean_digits = '90' . ltrim($clean_digits, '0');
            }
        }
        $lead['clean_whatsapp'] = $clean_digits;
        $lead['clean_phone'] = $clean_digits;

        // Clean Instagram link
        $insta_raw = trim((string) ($lead['instagram'] ?? ''));
        $insta_handle = ltrim($insta_raw, '@');
        if (strpos($insta_handle, 'instagram.com/') !== false) {
            $parts = explode('instagram.com/', $insta_handle);
            $insta_handle = trim(end($parts), '/');
        }
        $lead['clean_instagram'] = $insta_handle;
        $lead['instagram_url'] = $insta_handle ? 'https://www.instagram.com/' . urlencode($insta_handle) : '';

        // Clean Email
        $lead['clean_email'] = trim((string) ($lead['email'] ?? ''));

        // Stage styling helper
        $stage_key = $lead['stage'] ?? 'New Lead';
        $lead['stage_label'] = self::STAGES[$stage_key] ?? $stage_key;

        return $lead;
    }

    /**
     * Add structured call activity with provider, transcript, audio duration, and outcome.
     */
    public function add_call_activity(int $lead_id, array $data): int
    {
        $duration = (int) ($data['duration'] ?? ($data['duration_seconds'] ?? 0));
        $provider = $data['provider'] ?? 'zadarma';
        $payload = [
            'id_leads' => $lead_id,
            'activity_type' => 'call',
            'title' => $data['title'] ?? ('Sesli Görüşme (' . strtoupper($provider) . ')'),
            'description' => $data['notes'] ?? ($data['description'] ?? ''),
            'performed_by' => $data['performed_by'] ?? (session('superadmin_username') ?: 'Super Admin'),
            'metadata_json' => json_encode([
                'provider' => $provider,
                'duration' => $duration,
                'duration_seconds' => $duration,
                'outcome' => $data['outcome'] ?? 'completed',
                'transcript' => $data['transcript'] ?? '',
                'called_number' => $data['called_number'] ?? '',
                'ai_model' => $data['ai_model'] ?? '',
            ], JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('lead_activities', $payload);
        $insert_id = (int) $this->db->insert_id();

        // Update lead's updated_at & optionally stage
        $update = ['updated_at' => date('Y-m-d H:i:s')];
        $stage_key = null;
        if (!empty($data['new_stage'])) {
            $requested_stage = trim((string) $data['new_stage']);
            if (isset(self::STAGES[$requested_stage])) {
                $stage_key = $requested_stage;
            } else {
                $flipped = array_flip(self::STAGES);
                if (isset($flipped[$requested_stage])) {
                    $stage_key = $flipped[$requested_stage];
                }
            }
        }

        if ($stage_key !== null) {
            $outcome_label = match ($data['outcome'] ?? '') {
                'demo_started' => 'Demo Başlatıldı',
                'interested_follow_up' => 'İlgilendi (Takip Aranacak)',
                'visit_requested' => 'Saha Ziyareti İstendi',
                'busy_no_answer' => 'Cevap Vermedi / Meşgul',
                'objection_retry' => 'İtiraz Etti',
                'not_interested' => 'İlgilenmiyor / Olumsuz',
                default => ($data['outcome'] ?? 'Tamamlandı'),
            };
            $actor = $data['performed_by'] ?? 'Admin';
            $this->update_stage($lead_id, $stage_key, $actor, 'Sesli görüşme sonucu: ' . $outcome_label);
        } else {
            $this->db->where('id', $lead_id)->update('leads', $update);
        }

        return $insert_id;
    }

    /**
     * Public wrapper for apply_lead_filters — used by controller for map queries.
     */
    public function apply_lead_filters_public(array $filters): void
    {
        $this->apply_lead_filters($filters);
    }

    /**
     * Apply query filters on leads query builder.
     */
    private function apply_lead_filters(array $filters): void
    {
        if (!empty($filters['q'])) {
            $q = trim((string) $filters['q']);
            $this->db->group_start()
                ->like('name', $q)
                ->or_like('phone', $q)
                ->or_like('whatsapp', $q)
                ->or_like('email', $q)
                ->or_like('contact_person', $q)
                ->or_like('address', $q)
                ->or_like('notes', $q)
                ->group_end();
        }

        if (!empty($filters['sector'])) {
            $this->db->like('sector', $filters['sector']);
        }

        if (!empty($filters['district'])) {
            $this->db->where('district', $filters['district']);
        }

        if (!empty($filters['stage'])) {
            if ($filters['stage'] === 'DEMO_TRIAL') {
                $this->db->where('stage', 'Trial Started');
            } elseif ($filters['stage'] === 'REVISIT_URGENT') {
                $this->db->where('stage', 'Follow-up');
            } elseif ($filters['stage'] === 'WON') {
                $this->db->where('stage', 'Won');
            } elseif ($filters['stage'] !== 'ALL') {
                $this->db->where('stage', $filters['stage']);
            }
        }

        if (!empty($filters['priority'])) {
            $this->db->where('priority', $filters['priority']);
        }

        if (!empty($filters['owner_name'])) {
            $this->db->where('owner_name', $filters['owner_name']);
        }

        if (!empty($filters['package']) && $filters['package'] !== 'ALL') {
            $this->db->where('package', $filters['package']);
        }

        if (!empty($filters['is_places'])) {
            $this->db->where('place_id IS NOT NULL', null, false);
        }

        if (!empty($filters['enrich_filter'])) {
            if ($filters['enrich_filter'] === 'enriched') {
                $this->db->where('enriched_at IS NOT NULL', null, false);
            } elseif ($filters['enrich_filter'] === 'not_enriched') {
                $this->db->where('enriched_at IS NULL', null, false);
            }
        }

        if (!empty($filters['business_status']) && $filters['business_status'] !== 'ALL') {
            $this->db->where('business_status', $filters['business_status']);
        }
    }

    /**
     * Get a single lead by its SEO slug.
     */
    public function get_by_slug(string $slug): ?array
    {
        $lead = $this->db->get_where('leads', ['slug' => $slug])->row_array();
        return $lead ? $this->format_lead_metadata($lead) : null;
    }

    /**
     * Get leads for marketplace storefront (homepage).
     * Only enriched or claimed leads are shown on the homepage for quality.
     */
    public function get_for_storefront(int $limit = 12, int $offset = 0, array $filters = []): array
    {
        $this->db->group_start();
        $this->db->where('enrichment_status', 'enriched_lead');
        $this->db->or_where('membership_status', 'claimed_member');
        $this->db->group_end();
        $this->db->where('business_status', 'OPERATIONAL');

        $this->apply_marketplace_filters($filters);
        $total = $this->db->count_all_results('leads');

        $this->db->group_start();
        $this->db->where('enrichment_status', 'enriched_lead');
        $this->db->or_where('membership_status', 'claimed_member');
        $this->db->group_end();
        $this->db->where('business_status', 'OPERATIONAL');
        $this->apply_marketplace_filters($filters);

        $this->db->order_by('rating', 'desc');
        $this->db->order_by('user_rating_count', 'desc');
        $leads = $this->db->limit($limit, $offset)->get('leads')->result_array();

        foreach ($leads as &$l) {
            $l = $this->format_lead_metadata($l);
        }
        unset($l);

        return ['leads' => $leads, 'total' => (int) $total];
    }

    /**
     * Get leads for pSEO category/city/district pages.
     * Shows all operational leads (prioritizing enriched) to ensure full pSEO coverage.
     */
    public function get_for_directory(int $limit = 24, int $offset = 0, array $filters = []): array
    {
        $this->db->where('business_status', 'OPERATIONAL');
        $this->apply_marketplace_filters($filters);
        $total = $this->db->count_all_results('leads');

        $this->db->where('business_status', 'OPERATIONAL');
        $this->apply_marketplace_filters($filters);

        $this->db->order_by("FIELD(enrichment_status, 'enriched_lead', 'raw_lead')", '', false);
        $this->db->order_by('rating', 'desc');
        $leads = $this->db->limit($limit, $offset)->get('leads')->result_array();

        foreach ($leads as &$l) {
            $l = $this->format_lead_metadata($l);
        }
        unset($l);

        return ['leads' => $leads, 'total' => (int) $total];
    }

    /**
     * Get all slugs for sitemap generation.
     */
    public function get_all_slugs(int $limit = 5000, int $offset = 0): array
    {
        return $this->db
            ->select('slug, name, city, district, sector, enrichment_status, updated_at')
            ->where('slug IS NOT NULL', null, false)
            ->where('slug !=', '')
            ->where('business_status', 'OPERATIONAL')
            ->where('name NOT LIKE', '%Test%')
            ->where('name NOT LIKE', '%Demo%')
            ->order_by('id', 'asc')
            ->limit($limit, $offset)
            ->get('leads')
            ->result_array();
    }

    /**
     * Count all leads with slugs for sitemap pagination.
     */
    public function count_slugs(): int
    {
        return (int) $this->db
            ->where('slug IS NOT NULL', null, false)
            ->where('slug !=', '')
            ->where('business_status', 'OPERATIONAL')
            ->where('name NOT LIKE', '%Test%')
            ->where('name NOT LIKE', '%Demo%')
            ->count_all_results('leads');
    }

    /**
     * Get distinct cities for marketplace filters.
     */
    public function get_distinct_cities(): array
    {
        return $this->db
            ->select('city, COUNT(*) as count', false)
            ->where('city IS NOT NULL', null, false)
            ->where('city !=', '')
            ->where('business_status', 'OPERATIONAL')
            ->where('name NOT LIKE', '%Test%')
            ->where('name NOT LIKE', '%Demo%')
            ->group_by('city')
            ->order_by('count', 'desc')
            ->get('leads')
            ->result_array();
    }

    /**
     * Get distinct neighborhoods for a city + district.
     */
    public function get_neighborhoods(string $city = '', string $district = ''): array
    {
        $this->db->select('neighborhood, COUNT(*) as count', false)
            ->where('neighborhood IS NOT NULL', null, false)
            ->where('neighborhood !=', '')
            ->where('business_status', 'OPERATIONAL')
            ->where('name NOT LIKE', '%Test%')
            ->where('name NOT LIKE', '%Demo%');

        if ($city !== '') {
            $this->db->where('city', $city);
        }
        if ($district !== '') {
            $this->db->where('district', $district);
        }

        return $this->db
            ->group_by('neighborhood')
            ->order_by('count', 'desc')
            ->get('leads')
            ->result_array();
    }

    /**
     * Get lead by claim token.
     */
    public function get_by_claim_token(string $token): ?array
    {
        $lead = $this->db->get_where('leads', ['claim_token' => $token])->row_array();
        return $lead ? $this->format_lead_metadata($lead) : null;
    }

    /**
     * Apply marketplace-specific search filters.
     */
    public function apply_marketplace_filters(array $filters): void
    {
        // Exclude dummy test/demo leads from marketplace
        $this->db->where('name NOT LIKE', '%Test%');
        $this->db->where('name NOT LIKE', '%Demo%');
        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $this->db->group_start()
                ->like('name', $q)
                ->or_like('sector', $q)
                ->or_like('district', $q)
                ->or_like('city', $q)
                ->or_like('neighborhood', $q)
                ->or_like('address', $q)
                ->group_end();
        }
        if (!empty($filters['sector'])) {
            $this->db->like('sector', $filters['sector']);
        }
        if (!empty($filters['city'])) {
            $this->db->where('city', $filters['city']);
        }
        if (!empty($filters['district'])) {
            $this->db->where('district', $filters['district']);
        }
        if (!empty($filters['neighborhood'])) {
            $this->db->where('neighborhood', $filters['neighborhood']);
        }
        if (!empty($filters['category'])) {
            $this->db->group_start()
                ->like('sector', $filters['category'])
                ->or_like('primary_type', $filters['category'])
                ->or_like('matched_categories', $filters['category'])
                ->group_end();
        }
    }
}

