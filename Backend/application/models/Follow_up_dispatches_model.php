<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Follow-Up Dispatches Model.
 *
 * Tracks delayed and scheduled follow-up tasks, their execution state,
 * customer responses, quiet hours postponements, and anti-spam cancellations.
 */
class Follow_up_dispatches_model extends App_Model
{
    /**
     * Find a dispatch by ID.
     */
    public function find(string $id): ?array
    {
        $row = $this->db->get_where('follow_up_dispatches', ['id' => $id])->row_array();
        if ($row) {
            $this->decode_json_fields($row);
        }
        return $row ?: null;
    }

    /**
     * Schedule a new follow-up dispatch task.
     */
    public function schedule(array $data): string
    {
        if (empty($data['id'])) {
            $data['id'] = 'fup_' . bin2hex(random_bytes(16));
        }

        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload']);
        }
        if (isset($data['response_received']) && is_array($data['response_received'])) {
            $data['response_received'] = json_encode($data['response_received']);
        }

        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $now;

        $this->db->insert('follow_up_dispatches', $data);

        return $data['id'];
    }

    /**
     * Fetch scheduled dispatches that are due for execution (scheduled_for <= now).
     */
    public function get_due_dispatches(int $limit = 50): array
    {
        $now = date('Y-m-d H:i:s');

        $rows = $this->db
            ->select('follow_up_dispatches.*, follow_up_rules.rule_type, follow_up_rules.whatsapp_template_name, follow_up_rules.industry_family, follow_up_rules.blueprint_type')
            ->from('follow_up_dispatches')
            ->join('follow_up_rules', 'follow_up_rules.id = follow_up_dispatches.rule_id', 'left')
            ->where('follow_up_dispatches.status', 'SCHEDULED')
            ->where('follow_up_dispatches.scheduled_for <=', $now)
            ->order_by('follow_up_dispatches.scheduled_for', 'asc')
            ->limit($limit)
            ->get()
            ->result_array();

        foreach ($rows as &$row) {
            $this->decode_json_fields($row);
        }

        return $rows;
    }

    /**
     * Mark a dispatch as successfully sent.
     */
    public function mark_as_sent(string $id, ?array $payload = null): bool
    {
        $update = [
            'status' => 'SENT',
            'sent_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($payload !== null) {
            $update['payload'] = json_encode($payload);
        }

        return $this->db->update('follow_up_dispatches', $update, ['id' => $id]);
    }

    /**
     * Mark a dispatch as failed.
     */
    public function mark_as_failed(string $id, string $error): bool
    {
        return $this->db->update('follow_up_dispatches', [
            'status' => 'FAILED',
            'error_message' => $error,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Mark a dispatch as cancelled (e.g. CANCELLED_REBOOKED, CANCELLED_OPT_OUT).
     */
    public function mark_as_cancelled(string $id, string $reason, array $meta = []): bool
    {
        $row = $this->find($id);
        $payload = $row['payload_decoded'] ?? [];
        if (!empty($meta)) {
            $payload['_cancellation_meta'] = $meta;
        }

        return $this->db->update('follow_up_dispatches', [
            'status' => $reason,
            'payload' => json_encode($payload),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Reschedule a dispatch to a future time (e.g. Quiet Hours postponement).
     */
    public function reschedule(string $id, string $new_scheduled_for, ?string $reason = null): bool
    {
        $update = [
            'scheduled_for' => $new_scheduled_for,
            'status' => 'SCHEDULED',
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($reason) {
            $update['error_message'] = $reason;
        }

        return $this->db->update('follow_up_dispatches', $update, ['id' => $id]);
    }

    /**
     * Record a customer response/NPS feedback on a dispatch.
     */
    public function record_response(string $id, array $response_data): bool
    {
        $response_data['received_at'] = date('Y-m-d H:i:s');

        return $this->db->update('follow_up_dispatches', [
            'response_received' => json_encode($response_data),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    /**
     * Retrieve the most recent sent dispatch for a customer, optionally filtered by rule type.
     */
    public function get_latest_for_customer(int|string $customer_id, ?string $rule_type = null, int $within_seconds = 259200): ?array
    {
        $since = date('Y-m-d H:i:s', time() - $within_seconds);

        $this->db
            ->select('follow_up_dispatches.*, follow_up_rules.rule_type, follow_up_rules.whatsapp_template_name')
            ->from('follow_up_dispatches')
            ->join('follow_up_rules', 'follow_up_rules.id = follow_up_dispatches.rule_id', 'left')
            ->where('follow_up_dispatches.customer_id', (string) $customer_id)
            ->where('follow_up_dispatches.status', 'SENT')
            ->where('follow_up_dispatches.sent_at >=', $since);

        if ($rule_type) {
            $this->db->where('follow_up_rules.rule_type', $rule_type);
        }

        $this->db->order_by('follow_up_dispatches.sent_at', 'desc')->limit(1);

        $row = $this->db->get()->row_array();
        if ($row) {
            $this->decode_json_fields($row);
        }
        return $row ?: null;
    }

    /**
     * Cancel pending scheduled dispatches for a customer when they opt out.
     */
    public function cancel_pending_for_customer(int|string $customer_id, bool $keep_medical = true): int
    {
        $this->db
            ->select('follow_up_dispatches.id, follow_up_rules.rule_type')
            ->from('follow_up_dispatches')
            ->join('follow_up_rules', 'follow_up_rules.id = follow_up_dispatches.rule_id', 'left')
            ->where('follow_up_dispatches.customer_id', (string) $customer_id)
            ->where('follow_up_dispatches.status', 'SCHEDULED');

        $rows = $this->db->get()->result_array();
        $cancelled_count = 0;

        foreach ($rows as $row) {
            if ($keep_medical && ($row['rule_type'] ?? '') === 'reaction_check') {
                // Keep medical safety checks active
                continue;
            }

            $this->mark_as_cancelled($row['id'], 'CANCELLED_OPT_OUT', ['reason' => 'Customer requested RED/DUR opt-out']);
            $cancelled_count++;
        }

        return $cancelled_count;
    }

    /**
     * Retrieve paginated dispatches for tenant dashboard.
     */
    public function get_tenant_dispatches(string $tenant_id, array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $this->db
            ->select('follow_up_dispatches.*, follow_up_rules.rule_type, follow_up_rules.whatsapp_template_name, follow_up_rules.industry_family, follow_up_rules.blueprint_type')
            ->from('follow_up_dispatches')
            ->join('follow_up_rules', 'follow_up_rules.id = follow_up_dispatches.rule_id', 'left')
            ->where('follow_up_dispatches.tenant_id', $tenant_id);

        if (!empty($filters['status'])) {
            $this->db->where('follow_up_dispatches.status', $filters['status']);
        }
        if (!empty($filters['channel'])) {
            $this->db->where('follow_up_dispatches.channel', $filters['channel']);
        }
        if (!empty($filters['booking_id'])) {
            $this->db->where('follow_up_dispatches.booking_id', $filters['booking_id']);
        }

        $this->db->order_by('follow_up_dispatches.scheduled_for', 'desc');
        $this->db->limit($limit, $offset);

        $rows = $this->db->get()->result_array();
        foreach ($rows as &$row) {
            $this->decode_json_fields($row);
        }
        return $rows;
    }

    /**
     * Aggregated statistics for tenant overview.
     */
    public function get_stats_for_tenant(string $tenant_id): array
    {
        $counts = $this->db
            ->select('status, COUNT(*) as cnt')
            ->from('follow_up_dispatches')
            ->where('tenant_id', $tenant_id)
            ->group_by('status')
            ->get()
            ->result_array();

        $stats = [
            'total' => 0,
            'scheduled' => 0,
            'sent' => 0,
            'cancelled_rebooked' => 0,
            'cancelled_opt_out' => 0,
            'failed' => 0,
            'responses' => 0,
        ];

        foreach ($counts as $c) {
            $status = strtolower($c['status']);
            $count = (int) $c['cnt'];
            $stats['total'] += $count;

            if ($status === 'scheduled') {
                $stats['scheduled'] += $count;
            } elseif ($status === 'sent') {
                $stats['sent'] += $count;
            } elseif ($status === 'cancelled_rebooked') {
                $stats['cancelled_rebooked'] += $count;
            } elseif ($status === 'cancelled_opt_out') {
                $stats['cancelled_opt_out'] += $count;
            } elseif ($status === 'failed') {
                $stats['failed'] += $count;
            }
        }

        $res_count = $this->db
            ->from('follow_up_dispatches')
            ->where('tenant_id', $tenant_id)
            ->where('response_received IS NOT NULL', null, false)
            ->count_all_results();

        $stats['responses'] = $res_count;

        return $stats;
    }

    /**
     * Decode JSON fields into helper attributes.
     */
    private function decode_json_fields(array &$row): void
    {
        $row['payload_decoded'] = !empty($row['payload']) ? (json_decode($row['payload'], true) ?: []) : [];
        $row['response_decoded'] = !empty($row['response_received']) ? (json_decode($row['response_received'], true) ?: []) : [];
    }
}
