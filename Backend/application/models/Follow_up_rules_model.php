<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Follow-Up Rules Model.
 *
 * Manages sector-based follow-up rules for tenants. Supports 9 industry families
 * and customizable trigger delays, templates, and dynamic schemas.
 */
class Follow_up_rules_model extends App_Model
{
    /**
     * Find a single rule by ID.
     */
    public function find(string $id): ?array
    {
        $row = $this->db->get_where('follow_up_rules', ['id' => $id])->row_array();
        if ($row && !empty($row['dynamic_payload_schema']) && is_string($row['dynamic_payload_schema'])) {
            $row['dynamic_payload_schema_decoded'] = json_decode($row['dynamic_payload_schema'], true) ?: [];
        }
        return $row ?: null;
    }

    /**
     * Retrieve all rules for a tenant, optionally filtered by blueprint.
     */
    public function get_rules_by_tenant(string $tenant_id, ?string $blueprint_type = null): array
    {
        $this->db->group_start()
            ->where('tenant_id', $tenant_id)
            ->or_where('tenant_id', 'default')
            ->group_end();

        if ($blueprint_type) {
            $this->db->where('blueprint_type', $blueprint_type);
        }

        $this->db->order_by('industry_family', 'asc');
        $this->db->order_by('blueprint_type', 'asc');
        $this->db->order_by('id', 'asc');

        $rows = $this->db->get('follow_up_rules')->result_array();
        foreach ($rows as &$row) {
            if (!empty($row['dynamic_payload_schema']) && is_string($row['dynamic_payload_schema'])) {
                $row['dynamic_payload_schema_decoded'] = json_decode($row['dynamic_payload_schema'], true) ?: [];
            }
        }
        return $rows;
    }

    /**
     * Retrieve active rules for scheduling.
     */
    public function get_active_rules(string $tenant_id, ?string $blueprint_type = null, ?string $rule_type = null): array
    {
        $this->db->where('is_active', 1);

        // Fetch tenant-specific override first, otherwise fall back to 'default'
        $this->db->group_start()
            ->where('tenant_id', $tenant_id)
            ->or_where('tenant_id', 'default')
            ->group_end();

        if ($blueprint_type) {
            $this->db->group_start()
                ->where('blueprint_type', $blueprint_type)
                ->or_where('blueprint_type', 'all')
                ->group_end();
        }

        if ($rule_type) {
            $this->db->where('rule_type', $rule_type);
        }

        $this->db->order_by('id', 'asc');
        $rows = $this->db->get('follow_up_rules')->result_array();

        // De-duplicate if tenant has a specific override for a rule
        $rules_by_type = [];
        foreach ($rows as $row) {
            $key = ($row['blueprint_type'] ?? '') . ':' . ($row['rule_type'] ?? '') . ':' . ($row['whatsapp_template_name'] ?? '');
            if ($row['tenant_id'] === $tenant_id) {
                $rules_by_type[$key] = $row;
            } elseif (!isset($rules_by_type[$key])) {
                $rules_by_type[$key] = $row;
            }
        }

        return array_values($rules_by_type);
    }

    /**
     * Create or insert a new rule.
     */
    public function create(array $data): string
    {
        if (empty($data['id'])) {
            $data['id'] = 'rule_' . bin2hex(random_bytes(16));
        }

        if (isset($data['dynamic_payload_schema']) && is_array($data['dynamic_payload_schema'])) {
            $data['dynamic_payload_schema'] = json_encode($data['dynamic_payload_schema']);
        }

        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $now;

        $this->db->insert('follow_up_rules', $data);

        return $data['id'];
    }

    /**
     * Update an existing rule.
     */
    public function update(string $id, array $data): bool
    {
        if (isset($data['dynamic_payload_schema']) && is_array($data['dynamic_payload_schema'])) {
            $data['dynamic_payload_schema'] = json_encode($data['dynamic_payload_schema']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('follow_up_rules', $data, ['id' => $id]);
    }

    /**
     * Delete a rule.
     */
    public function delete(string $id): bool
    {
        return $this->db->delete('follow_up_rules', ['id' => $id]);
    }

    /**
     * Toggle active state.
     */
    public function toggle_active(string $id, bool $is_active): bool
    {
        return $this->update($id, ['is_active' => $is_active ? 1 : 0]);
    }

    /**
     * Seed default rules for a specific tenant based on their blueprint.
     */
    public function seed_tenant_defaults(string $tenant_id, string $blueprint_type, string $industry_family): int
    {
        $default_rules = $this->db
            ->where('tenant_id', 'default')
            ->where('blueprint_type', $blueprint_type)
            ->get('follow_up_rules')
            ->result_array();

        $seeded = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($default_rules as $default_rule) {
            // Check if tenant already has this rule
            $existing = $this->db
                ->where('tenant_id', $tenant_id)
                ->where('rule_type', $default_rule['rule_type'])
                ->where('blueprint_type', $blueprint_type)
                ->get('follow_up_rules')
                ->row_array();

            if (!$existing) {
                $new_rule = $default_rule;
                $new_rule['id'] = 'rule_' . bin2hex(random_bytes(16));
                $new_rule['tenant_id'] = $tenant_id;
                $new_rule['industry_family'] = $industry_family;
                $new_rule['created_at'] = $now;
                $new_rule['updated_at'] = $now;

                $this->db->insert('follow_up_rules', $new_rule);
                $seeded++;
            }
        }

        return $seeded;
    }
}
