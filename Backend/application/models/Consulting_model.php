<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Consulting & Project Management Model
 *
 * Implements consulting projects, deliverables/milestones, client sign-offs,
 * and billable timesheet tracking (Leantime / Consultant Management System standard).
 */
class Consulting_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get consulting projects.
     */
    public function get_projects(?int $client_id = null, ?int $consultant_id = null, ?string $status = null, int $limit = 100): array
    {
        $this->db->select('p.*, 
            c.first_name as client_first_name, c.last_name as client_last_name, c.phone_number as client_phone,
            u.first_name as consultant_first_name, u.last_name as consultant_last_name')
            ->from('consulting_projects p')
            ->join('users c', 'c.id = p.id_users_client', 'left')
            ->join('users u', 'u.id = p.id_users_lead_consultant', 'left');

        if ($client_id) {
            $this->db->where('p.id_users_client', $client_id);
        }
        if ($consultant_id) {
            $this->db->where('p.id_users_lead_consultant', $consultant_id);
        }
        if ($status && $status !== 'all') {
            $this->db->where('p.status', $status);
        }

        return $this->db->order_by('p.created_at DESC')->limit($limit)->get()->result_array();
    }

    /**
     * Get single project with milestones & timesheet summaries.
     */
    public function get_project(int $id): ?array
    {
        $project = $this->db->select('p.*, 
            c.first_name as client_first_name, c.last_name as client_last_name, c.phone_number as client_phone, c.email as client_email,
            u.first_name as consultant_first_name, u.last_name as consultant_last_name')
            ->from('consulting_projects p')
            ->join('users c', 'c.id = p.id_users_client', 'left')
            ->join('users u', 'u.id = p.id_users_lead_consultant', 'left')
            ->where('p.id', $id)
            ->get()
            ->row_array();

        if (!$project) {
            return null;
        }

        $project['milestones'] = $this->get_milestones($id);

        $timesheet_stats = $this->db->select('
                SUM(hours_spent) as total_hours, 
                SUM(CASE WHEN is_billable = 1 THEN hours_spent ELSE 0 END) as billable_hours
            ')
            ->from('consulting_timesheets')
            ->where('id_projects', $id)
            ->get()
            ->row_array();

        $project['total_hours'] = (float) ($timesheet_stats['total_hours'] ?? 0.0);
        $project['billable_hours'] = (float) ($timesheet_stats['billable_hours'] ?? 0.0);

        return $project;
    }

    /**
     * Save project.
     */
    public function save_project(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        if (empty($data['project_code'])) {
            $data['project_code'] = 'PRJ-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        }

        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            $this->db->where('id', $id)->update('consulting_projects', $data);
            return $id;
        }

        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $this->db->insert('consulting_projects', $data);
        return (int) $this->db->insert_id();
    }

    public function delete_project(int $id): bool
    {
        $this->db->where('id_projects', $id)->delete('consulting_milestones');
        $this->db->where('id_projects', $id)->delete('consulting_timesheets');
        return $this->db->where('id', $id)->delete('consulting_projects');
    }

    /**
     * Get milestones for project.
     */
    public function get_milestones(int $project_id): array
    {
        return $this->db->where('id_projects', $project_id)
            ->order_by('due_date ASC')
            ->get('consulting_milestones')
            ->result_array();
    }

    public function save_milestone(array $data): int
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('consulting_milestones', $data);
            return $id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('consulting_milestones', $data);
        return (int) $this->db->insert_id();
    }

    public function update_milestone_signoff(int $id, string $status, ?string $feedback = null): bool
    {
        return $this->db->where('id', $id)->update('consulting_milestones', [
            'signoff_status' => $status,
            'client_feedback' => $feedback,
            'signoff_at' => ($status === 'client_approved') ? date('Y-m-d H:i:s') : null,
        ]);
    }

    /**
     * Timesheets.
     */
    public function get_timesheets(?int $project_id = null, ?int $consultant_id = null): array
    {
        $this->db->select('t.*, p.title as project_title, p.project_code, m.title as milestone_title,
            u.first_name as consultant_first_name, u.last_name as consultant_last_name')
            ->from('consulting_timesheets t')
            ->join('consulting_projects p', 'p.id = t.id_projects', 'inner')
            ->join('consulting_milestones m', 'm.id = t.id_milestones', 'left')
            ->join('users u', 'u.id = t.id_users_consultant', 'left');

        if ($project_id) {
            $this->db->where('t.id_projects', $project_id);
        }
        if ($consultant_id) {
            $this->db->where('t.id_users_consultant', $consultant_id);
        }

        return $this->db->order_by('t.log_date DESC, t.id DESC')->get()->result_array();
    }

    public function save_timesheet(array $data): int
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('consulting_timesheets', $data);
            return $id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('consulting_timesheets', $data);
        return (int) $this->db->insert_id();
    }
}
