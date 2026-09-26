<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Audit Logs Model (Security & Financial Traceability)
 * ---------------------------------------------------------------------------- */

class Audit_logs_model extends App_Model
{
    /**
     * Record an audit trail entry.
     */
    public function log(string $action_type, string $entity_type, ?int $entity_id = null, $before = null, $after = null, ?string $notes = null, ?int $user_id = null): int
    {
        $ci = &get_instance();
        $uid = $user_id ?: ($ci->session->userdata('user_id') ?? null);
        $ip = $ci->input->ip_address();
        $ua = substr((string) $ci->input->user_agent(), 0, 250);

        $data = [
            'id_users' => $uid,
            'action_type' => $action_type,
            'entity_type' => $entity_type,
            'entity_id' => $entity_id,
            'before_payload' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            'after_payload' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            'ip_address' => $ip,
            'user_agent' => $ua,
            'notes' => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->insert('audit_logs', $data);
        return $this->db->insert_id();
    }

    /**
     * Get audit logs with filtering.
     */
    public function get_logs(?string $entity_type = null, ?int $entity_id = null, int $limit = 100): array
    {
        $this->db
            ->select('al.*, u.first_name, u.last_name, u.email')
            ->from('audit_logs al')
            ->join('users u', 'u.id = al.id_users', 'left');

        if ($entity_type) {
            $this->db->where('al.entity_type', $entity_type);
        }
        if ($entity_id) {
            $this->db->where('al.entity_id', $entity_id);
        }

        return $this->db
            ->order_by('al.created_at DESC, al.id DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }
}
