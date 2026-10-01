<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Enterprise HRMS Service
 * ---------------------------------------------------------------------------- */

class Hr_service
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('hr_model');
        $this->CI->load->model('users_model');
    }

    /**
     * Build hierarchical tree data for Organizational Chart rendering.
     */
    public function get_organization_tree(): array
    {
        $employees = $this->CI->hr_model->get_employees(['is_active' => 1]);
        $tree = [];
        $by_id = [];

        foreach ($employees as $emp) {
            $by_id[$emp['id']] = [
                'id' => (int) $emp['id'],
                'name' => $emp['first_name'] . ' ' . $emp['last_name'],
                'title' => $emp['job_title'] ?: ($emp['designation_title'] ?: $emp['role_name']),
                'department' => $emp['department_name'] ?: 'Genel',
                'email' => $emp['email'],
                'phone' => $emp['phone_number'],
                'reports_to' => (int) $emp['reports_to_user_id'],
                'children' => [],
            ];
        }

        foreach ($by_id as $id => &$node) {
            if (!empty($node['reports_to']) && isset($by_id[$node['reports_to']])) {
                $by_id[$node['reports_to']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }

    /**
     * Get upcoming HR milestones: birthdays, anniversaries, expiring documents, ending probations.
     */
    public function get_hr_alerts(): array
    {
        $alerts = [
            'expiring_documents' => [],
            'probations_ending' => [],
            'birthdays' => [],
            'anniversaries' => [],
        ];

        $today = date('Y-m-d');
        $in_30_days = date('Y-m-d', strtotime('+30 days'));

        // Expiring documents
        $docs = $this->CI->hr_model->get_documents();
        foreach ($docs as $d) {
            if (!empty($d['expiry_date']) && $d['expiry_date'] <= $in_30_days && $d['expiry_date'] >= $today) {
                $alerts['expiring_documents'][] = $d;
            }
        }

        // Ending probations & birthdays
        $employees = $this->CI->hr_model->get_employees(['is_active' => 1]);
        $curr_month_day = date('m-d');

        foreach ($employees as $emp) {
            if (!empty($emp['probation_end_date']) && $emp['probation_end_date'] <= $in_30_days && $emp['probation_end_date'] >= $today) {
                $alerts['probations_ending'][] = $emp;
            }

            if (!empty($emp['birth_date'])) {
                $b_md = date('m-d', strtotime($emp['birth_date']));
                if ($b_md >= $curr_month_day && $b_md <= date('m-d', strtotime('+15 days'))) {
                    $alerts['birthdays'][] = $emp;
                }
            }

            if (!empty($emp['date_of_joining'])) {
                $j_md = date('m-d', strtotime($emp['date_of_joining']));
                if ($j_md >= $curr_month_day && $j_md <= date('m-d', strtotime('+15 days'))) {
                    $alerts['anniversaries'][] = $emp;
                }
            }
        }

        return $alerts;
    }

    /**
     * High level summary KPI cards for HR dashboard.
     */
    public function get_dashboard_kpis(): array
    {
        $today = date('Y-m-d');
        $month = (int) date('m');
        $year = (int) date('Y');

        $total_employees = $this->CI->db->where('is_active', 1)->count_all_results('users');
        $departments_count = $this->CI->db->where('is_active', 1)->count_all_results('hr_departments');
        
        // Today attendance
        $today_present = $this->CI->db
            ->where('date', $today)
            ->where('status', 'present')
            ->count_all_results('hr_daily_attendance');

        // Pending leaves
        $pending_leaves = $this->CI->db
            ->where('status', 'pending')
            ->count_all_results('hr_leave_applications');

        // Pending advances
        $pending_advances = $this->CI->db
            ->where('status', 'pending')
            ->count_all_results('hr_advances');

        // Pending expense claims
        $pending_expenses = $this->CI->db
            ->where('status', 'pending')
            ->count_all_results('hr_expense_claims');

        // Active job openings
        $open_jobs = $this->CI->db
            ->where('status', 'open')
            ->count_all_results('hr_job_openings');

        return [
            'total_employees' => $total_employees,
            'departments_count' => $departments_count,
            'today_present' => $today_present,
            'pending_leaves' => $pending_leaves,
            'pending_advances' => $pending_advances,
            'pending_expenses' => $pending_expenses,
            'open_jobs' => $open_jobs,
        ];
    }
}
