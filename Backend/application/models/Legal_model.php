<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi SaaS - Legal Practice & Law Firm Management Model
 *
 * Implements matter/case tracking, court hearings calendar, billable hours,
 * trust accounting (expenses/retainers), and conflict of interest checks (Clio standard).
 */
class Legal_model extends App_Model
{
    public function __construct()
    {
        //
    }

    /**
     * Get legal matters / cases.
     */
    public function get_matters(?int $client_id = null, ?int $attorney_id = null, ?string $status = null, int $limit = 100): array
    {
        $this->db->select('m.*, 
            c.first_name as client_first_name, c.last_name as client_last_name, c.phone_number as client_phone, c.email as client_email,
            a.first_name as attorney_first_name, a.last_name as attorney_last_name')
            ->from('legal_matters m')
            ->join('users c', 'c.id = m.id_users_client', 'left')
            ->join('users a', 'a.id = m.id_users_attorney', 'left');

        if ($client_id) {
            $this->db->where('m.id_users_client', $client_id);
        }
        if ($attorney_id) {
            $this->db->where('m.id_users_attorney', $attorney_id);
        }
        if ($status && $status !== 'all') {
            $this->db->where('m.case_status', $status);
        }

        return $this->db->order_by('m.created_at DESC')->limit($limit)->get()->result_array();
    }

    /**
     * Get single matter with summary statistics.
     */
    public function get_matter(int $id): ?array
    {
        $matter = $this->db->select('m.*, 
            c.first_name as client_first_name, c.last_name as client_last_name, c.phone_number as client_phone, c.email as client_email,
            a.first_name as attorney_first_name, a.last_name as attorney_last_name')
            ->from('legal_matters m')
            ->join('users c', 'c.id = m.id_users_client', 'left')
            ->join('users a', 'a.id = m.id_users_attorney', 'left')
            ->where('m.id', $id)
            ->get()
            ->row_array();

        if (!$matter) {
            return null;
        }

        // Aggregate billable time & expenses
        $time_stats = $this->db->select('SUM(duration_minutes) as total_minutes, SUM(total_amount) as total_billed')
            ->from('legal_time_entries')
            ->where('id_legal_matters', $id)
            ->get()
            ->row_array();

        $expense_stats = $this->db->select('SUM(amount) as total_expenses')
            ->from('legal_expenses')
            ->where('id_legal_matters', $id)
            ->get()
            ->row_array();

        $matter['total_billable_minutes'] = (int) ($time_stats['total_minutes'] ?? 0);
        $matter['total_billed_amount'] = (float) ($time_stats['total_billed'] ?? 0.00);
        $matter['total_expenses'] = (float) ($expense_stats['total_expenses'] ?? 0.00);

        return $matter;
    }

    /**
     * Save or update legal matter.
     */
    public function save_matter(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        // Aliases
        if (isset($data['docket_number']) && !isset($data['case_number'])) {
            $data['case_number'] = $data['docket_number'];
            unset($data['docket_number']);
        }
        if (isset($data['status']) && !isset($data['case_status'])) {
            $data['case_status'] = $data['status'];
            unset($data['status']);
        }
        if (isset($data['retainer_amount']) && !isset($data['retainer_balance'])) {
            $data['retainer_balance'] = $data['retainer_amount'];
            unset($data['retainer_amount']);
        }
        if (isset($data['billing_type'])) {
            unset($data['billing_type']);
        }

        if (empty($data['matter_number'])) {
            $data['matter_number'] = 'DOS-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));
        }

        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $data['updated_at'] = $now;
            $this->db->where('id', $id)->update('legal_matters', $data);
            return $id;
        }

        $data['created_at'] = $now;
        $data['updated_at'] = $now;
        $this->db->insert('legal_matters', $data);
        return (int) $this->db->insert_id();
    }

    /**
     * Delete matter.
     */
    public function delete_matter(int $id): bool
    {
        $this->db->where('id_legal_matters', $id)->delete('legal_hearings');
        $this->db->where('id_legal_matters', $id)->delete('legal_time_entries');
        $this->db->where('id_legal_matters', $id)->delete('legal_expenses');
        return $this->db->where('id', $id)->delete('legal_matters');
    }

    /**
     * Get hearings with matter info.
     */
    public function get_hearings(?int $matter_id = null, bool $upcoming_only = false): array
    {
        $this->db->select('h.*, m.matter_number, m.title as matter_title, m.case_number, m.court_name,
            c.first_name as client_first_name, c.last_name as client_last_name')
            ->from('legal_hearings h')
            ->join('legal_matters m', 'm.id = h.id_legal_matters', 'inner')
            ->join('users c', 'c.id = m.id_users_client', 'left');

        if ($matter_id) {
            $this->db->where('h.id_legal_matters', $matter_id);
        }
        if ($upcoming_only) {
            $this->db->where('h.hearing_datetime >=', date('Y-m-d H:i:s'));
            $this->db->order_by('h.hearing_datetime ASC');
        } else {
            $this->db->order_by('h.hearing_datetime DESC');
        }

        return $this->db->get()->result_array();
    }

    public function save_hearing(array $data): int
    {
        if (isset($data['hearing_date']) && !isset($data['hearing_datetime'])) {
            $data['hearing_datetime'] = $data['hearing_date'];
            unset($data['hearing_date']);
        }
        if (isset($data['courtroom']) && !isset($data['court_room'])) {
            $data['court_room'] = $data['courtroom'];
            unset($data['courtroom']);
        }
        if (isset($data['agenda_notes']) && !isset($data['hearing_summary'])) {
            $data['hearing_summary'] = $data['agenda_notes'];
            unset($data['agenda_notes']);
        }
        if (isset($data['judge_name'])) {
            unset($data['judge_name']);
        }

        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('legal_hearings', $data);
            return $id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('legal_hearings', $data);
        return (int) $this->db->insert_id();
    }

    public function delete_hearing(int $id): bool
    {
        return $this->db->where('id', $id)->delete('legal_hearings');
    }

    /**
     * Get billable time entries.
     */
    public function get_time_entries(?int $matter_id = null): array
    {
        $this->db->select('t.*, m.matter_number, m.title as matter_title, a.first_name as attorney_first_name, a.last_name as attorney_last_name')
            ->from('legal_time_entries t')
            ->join('legal_matters m', 'm.id = t.id_legal_matters', 'left')
            ->join('users a', 'a.id = t.id_users_attorney', 'left');

        if ($matter_id) {
            $this->db->where('t.id_legal_matters', $matter_id);
        }

        return $this->db->order_by('t.created_at DESC')->get()->result_array();
    }

    public function save_time_entry(array $data): int
    {
        if (isset($data['narrative']) && !isset($data['work_description'])) {
            $data['work_description'] = $data['narrative'];
            unset($data['narrative']);
        }
        if (isset($data['entry_date'])) {
            unset($data['entry_date']);
        }

        if (!isset($data['total_amount']) || $data['total_amount'] <= 0) {
            $duration_hours = ($data['duration_minutes'] ?? 0) / 60.0;
            $rate = (float) ($data['hourly_rate'] ?? 0.00);
            $data['total_amount'] = round($duration_hours * $rate, 2);
        }

        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('legal_time_entries', $data);
            return $id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('legal_time_entries', $data);
        return (int) $this->db->insert_id();
    }

    public function delete_time_entry(int $id): bool
    {
        return $this->db->where('id', $id)->delete('legal_time_entries');
    }

    /**
     * Get expenses / retainers for a matter.
     */
    public function get_expenses(?int $matter_id = null): array
    {
        $this->db->select('e.*, m.matter_number, m.title as matter_title')
            ->from('legal_expenses e')
            ->join('legal_matters m', 'm.id = e.id_legal_matters', 'left');

        if ($matter_id) {
            $this->db->where('e.id_legal_matters', $matter_id);
        }

        return $this->db->order_by('e.created_at DESC')->get()->result_array();
    }

    public function save_expense(array $data): int
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $id = (int) $data['id'];
            unset($data['id']);
            $this->db->where('id', $id)->update('legal_expenses', $data);
            return $id;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('legal_expenses', $data);
        return (int) $this->db->insert_id();
    }

    /**
     * Conflict of Interest Check (Clio / Ethics Benchmark).
     * Searches clients, adverse parties, opposing counsel and matter descriptions.
     */
    public function check_conflict(string $keyword): array
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 3) {
            return [];
        }

        // 1. Search in existing matters
        $matter_matches = $this->db->select('m.id, m.matter_number, m.title, m.case_number, m.case_status, 
                m.opposing_party, m.opposing_counsel, c.first_name, c.last_name, "matter" as match_type')
            ->from('legal_matters m')
            ->join('users c', 'c.id = m.id_users_client', 'left')
            ->group_start()
                ->like('m.opposing_party', $keyword)
                ->or_like('m.opposing_counsel', $keyword)
                ->or_like('m.title', $keyword)
                ->or_like('c.first_name', $keyword)
                ->or_like('c.last_name', $keyword)
            ->group_end()
            ->get()
            ->result_array();

        // 2. Search in all customers
        $client_matches = $this->db->select('id, first_name, last_name, phone_number, email, "existing_client" as match_type')
            ->from('users')
            ->where('id_roles', 3) // Customer
            ->group_start()
                ->like('first_name', $keyword)
                ->or_like('last_name', $keyword)
                ->or_like('email', $keyword)
            ->group_end()
            ->limit(10)
            ->get()
            ->result_array();

        $total = count($matter_matches) + count($client_matches);
        return [
            'matters' => $matter_matches,
            'clients' => $client_matches,
            'conflicts' => array_merge($matter_matches, $client_matches),
            'total_conflicts' => $total,
            'has_conflict' => $total > 0,
        ];
    }
}
