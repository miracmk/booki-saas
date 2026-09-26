<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Master Audit Logging Model
 *
 * Logs and tracks every administrative action in the Super Admin operations center
 * (Tenant creation, plan updates, status changes, lead conversions, impersonation).
 * -------------------------------------------------------------------------- */

class Master_audit_model extends CI_Model
{
    public function __construct()
    {
    }

    /**
     * Log a master administrative action.
     */
    public function log(string $action, string $entity_type = 'general', string|int|null $entity_id = null, ?string $description = null, array $metadata = []): int
    {
        $actor = session('superadmin_username') ?: 'SuperAdmin';
        $ip = $this->input->ip_address();

        // Handle case where caller passed ($action, $description)
        if ($description === null && $entity_id === null && $entity_type !== 'general' && !in_array($entity_type, ['lead', 'tenant', 'task', 'visit', 'user', 'platform_settings', 'import_job'], true)) {
            $description = $entity_type;
            $entity_type = 'general';
        }

        $this->db->insert('master_audit_logs', [
            'actor_username' => $actor,
            'action' => $action,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id ? (string) $entity_id : null,
            'description' => $description,
            'ip_address' => $ip,
            'metadata_json' => !empty($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id();
    }

    /**
     * Get paginated audit logs.
     */
    public function get_logs(int $limit = 50, int $offset = 0, ?string $entity_type = null, ?string $action = null): array
    {
        $builder = $this->db;
        if ($entity_type) {
            $builder->where('entity_type', $entity_type);
        }
        if ($action) {
            $builder->where('action', $action);
        }

        $total = $builder->count_all_results('master_audit_logs');

        if ($entity_type) {
            $builder->where('entity_type', $entity_type);
        }
        if ($action) {
            $builder->where('action', $action);
        }

        $logs = $builder
            ->order_by('created_at', 'desc')
            ->limit($limit, $offset)
            ->get('master_audit_logs')
            ->result_array();

        return [
            'logs' => $logs,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
