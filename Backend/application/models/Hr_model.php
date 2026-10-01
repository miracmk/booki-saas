<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Enterprise HRMS Model
 * ---------------------------------------------------------------------------- */

class Hr_model extends App_Model
{
    // =========================================================================
    // 1. DEPARTMENTS & DESIGNATIONS
    // =========================================================================

    public function get_departments(bool $active_only = false): array
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('name', 'ASC');
        $depts = $this->db->get('hr_departments')->result_array();

        // Attach employee count
        foreach ($depts as &$dept) {
            $dept['employee_count'] = $this->db
                ->where('id_departments', $dept['id'])
                ->count_all_results('hr_employee_profiles');
        }

        return $depts;
    }

    public function save_department(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'] ?? substr($data['name'], 0, 4))),
            'parent_id' => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'manager_id' => !empty($data['manager_id']) ? (int) $data['manager_id'] : null,
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_departments', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_departments', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_department(int $id): bool
    {
        // Unlink employees before delete
        $this->db->update('hr_employee_profiles', ['id_departments' => null], ['id_departments' => $id]);
        return $this->db->delete('hr_departments', ['id' => $id]);
    }

    public function get_designations(): array
    {
        $this->db->order_by('title', 'ASC');
        $desigs = $this->db->get('hr_designations')->result_array();

        foreach ($desigs as &$d) {
            $d['employee_count'] = $this->db
                ->where('id_designations', $d['id'])
                ->count_all_results('hr_employee_profiles');
        }

        return $desigs;
    }

    public function save_designation(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'title' => trim($data['title']),
            'code' => strtoupper(trim($data['code'] ?? substr($data['title'], 0, 4))),
            'description' => $data['description'] ?? null,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_designations', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_designations', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_designation(int $id): bool
    {
        $this->db->update('hr_employee_profiles', ['id_designations' => null], ['id_designations' => $id]);
        return $this->db->delete('hr_designations', ['id' => $id]);
    }

    // =========================================================================
    // 2. EMPLOYEE PROFILES & ÖZLÜK
    // =========================================================================

    public function get_employees(array $filters = []): array
    {
        $this->db->select("
            u.id, u.first_name, u.last_name, u.email, u.phone_number, u.job_title, u.role_slug,
            u.is_active, u.branch_ids, u.pin_code, u.qr_token,
            p.tckn_passport, p.birth_date, p.gender, p.blood_type, p.marital_status, p.military_status,
            p.emergency_contact_name, p.emergency_contact_phone, p.iban, p.bank_name,
            p.employment_type, p.date_of_joining, p.date_of_leaving, p.probation_end_date,
            p.reports_to_user_id, p.id_departments, p.id_designations,
            d.name as department_name, d.code as department_code,
            des.title as designation_title,
            CONCAT(m.first_name, ' ', m.last_name) as manager_name,
            r.name as role_name, r.slug as system_role_slug
        ");
        $this->db->from('users u');
        $this->db->join('roles r', 'r.id = u.id_roles', 'left');
        $this->db->join('hr_employee_profiles p', 'p.id_users = u.id', 'left');
        $this->db->join('hr_departments d', 'd.id = p.id_departments', 'left');
        $this->db->join('hr_designations des', 'des.id = p.id_designations', 'left');
        $this->db->join('users m', 'm.id = p.reports_to_user_id', 'left');

        // Exclude customers
        $this->db->where('r.slug !=', 'customer');

        if (!empty($filters['department_id'])) {
            $this->db->where('p.id_departments', (int) $filters['department_id']);
        }
        if (!empty($filters['is_active'])) {
            $this->db->where('u.is_active', 1);
        }
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $this->db->group_start();
            $this->db->like('u.first_name', $term);
            $this->db->or_like('u.last_name', $term);
            $this->db->or_like('u.email', $term);
            $this->db->or_like('u.phone_number', $term);
            $this->db->or_like('u.job_title', $term);
            $this->db->group_end();
        }

        $this->db->order_by('u.first_name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_employee(int $user_id): ?array
    {
        $emps = $this->get_employees(['search' => '']);
        foreach ($emps as $emp) {
            if ((int) $emp['id'] === $user_id) {
                // Attach documents
                $emp['documents'] = $this->get_documents($user_id);
                // Attach assets
                $emp['assets'] = $this->get_asset_assignments(['user_id' => $user_id, 'active_only' => true]);
                // Attach salary structure
                $emp['salary_structure'] = $this->get_salary_structure($user_id);
                // Attach leave allocations
                $emp['leave_allocations'] = $this->get_leave_allocations($user_id, (int) date('Y'));
                return $emp;
            }
        }
        return null;
    }

    public function save_employee_profile(int $user_id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');

        // Also update core user table fields if provided
        $user_updates = [];
        if (isset($data['first_name'])) {
            $user_updates['first_name'] = trim($data['first_name']);
        }
        if (isset($data['last_name'])) {
            $user_updates['last_name'] = trim($data['last_name']);
        }
        if (isset($data['email'])) {
            $user_updates['email'] = trim($data['email']);
        }
        if (isset($data['phone_number'])) {
            $user_updates['phone_number'] = trim($data['phone_number']);
        }
        if (isset($data['job_title'])) {
            $user_updates['job_title'] = trim($data['job_title']);
        }
        if (isset($data['role_slug'])) {
            $user_updates['role_slug'] = trim($data['role_slug']);
        }
        if (isset($data['pin_code'])) {
            $user_updates['pin_code'] = trim($data['pin_code']);
        }
        if (!empty($user_updates)) {
            $this->db->update('users', $user_updates, ['id' => $user_id]);
        }

        $existing = $this->db->get_where('hr_employee_profiles', ['id_users' => $user_id])->row_array();

        $profile = [
            'id_users' => $user_id,
            'tckn_passport' => $data['tckn_passport'] ?? ($existing['tckn_passport'] ?? null),
            'birth_date' => !empty($data['birth_date']) ? $data['birth_date'] : ($existing['birth_date'] ?? null),
            'gender' => $data['gender'] ?? ($existing['gender'] ?? null),
            'blood_type' => $data['blood_type'] ?? ($existing['blood_type'] ?? null),
            'marital_status' => $data['marital_status'] ?? ($existing['marital_status'] ?? null),
            'military_status' => $data['military_status'] ?? ($existing['military_status'] ?? null),
            'emergency_contact_name' => $data['emergency_contact_name'] ?? ($existing['emergency_contact_name'] ?? null),
            'emergency_contact_phone' => $data['emergency_contact_phone'] ?? ($existing['emergency_contact_phone'] ?? null),
            'iban' => $data['iban'] ?? ($existing['iban'] ?? null),
            'bank_name' => $data['bank_name'] ?? ($existing['bank_name'] ?? null),
            'employment_type' => $data['employment_type'] ?? ($existing['employment_type'] ?? 'full_time'),
            'date_of_joining' => !empty($data['date_of_joining']) ? $data['date_of_joining'] : ($existing['date_of_joining'] ?? date('Y-m-d')),
            'date_of_leaving' => !empty($data['date_of_leaving']) ? $data['date_of_leaving'] : ($existing['date_of_leaving'] ?? null),
            'probation_end_date' => !empty($data['probation_end_date']) ? $data['probation_end_date'] : ($existing['probation_end_date'] ?? null),
            'reports_to_user_id' => !empty($data['reports_to_user_id']) ? (int) $data['reports_to_user_id'] : null,
            'id_departments' => !empty($data['id_departments']) ? (int) $data['id_departments'] : null,
            'id_designations' => !empty($data['id_designations']) ? (int) $data['id_designations'] : null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->update('hr_employee_profiles', $profile, ['id_users' => $user_id]);
        } else {
            $profile['created_at'] = $now;
            $this->db->insert('hr_employee_profiles', $profile);
        }

        return true;
    }

    // =========================================================================
    // 3. DIGITAL DOCUMENT VAULT (ÖZLÜK DOSYASI)
    // =========================================================================

    public function get_documents(int $user_id = 0): array
    {
        $this->db->select('d.*, CONCAT(u.first_name, " ", u.last_name) as employee_name');
        $this->db->from('hr_documents d');
        $this->db->join('users u', 'u.id = d.id_users', 'left');
        if ($user_id > 0) {
            $this->db->where('d.id_users', $user_id);
        }
        $this->db->order_by('d.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_document(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_users' => (int) $data['id_users'],
            'document_type' => $data['document_type'] ?? 'other',
            'title' => trim($data['title']),
            'file_path' => $data['file_path'],
            'file_name' => $data['file_name'],
            'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
            'status' => $data['status'] ?? 'valid',
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert('hr_documents', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_document(int $id): bool
    {
        return $this->db->delete('hr_documents', ['id' => $id]);
    }

    // =========================================================================
    // 4. SHIFTS & ASSIGNMENTS
    // =========================================================================

    public function get_shifts(bool $active_only = false): array
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('start_time', 'ASC');
        return $this->db->get('hr_shifts')->result_array();
    }

    public function save_shift(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'] ?? substr($data['name'], 0, 4))),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'break_duration_minutes' => (int) ($data['break_duration_minutes'] ?? 60),
            'late_grace_minutes' => (int) ($data['late_grace_minutes'] ?? 15),
            'half_day_threshold_hours' => (float) ($data['half_day_threshold_hours'] ?? 4.00),
            'color' => $data['color'] ?? '#3b82f6',
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_shifts', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_shifts', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_shift(int $id): bool
    {
        $this->db->delete('hr_shift_assignments', ['id_shifts' => $id]);
        return $this->db->delete('hr_shifts', ['id' => $id]);
    }

    public function get_shift_assignments(int $user_id = 0, ?string $date = null): array
    {
        $this->db->select("
            sa.*, s.name as shift_name, s.start_time, s.end_time, s.color,
            CONCAT(u.first_name, ' ', u.last_name) as employee_name
        ");
        $this->db->from('hr_shift_assignments sa');
        $this->db->join('hr_shifts s', 's.id = sa.id_shifts', 'left');
        $this->db->join('users u', 'u.id = sa.id_users', 'left');

        if ($user_id > 0) {
            $this->db->where('sa.id_users', $user_id);
        }
        if ($date !== null) {
            $this->db->where('sa.start_date <=', $date);
            $this->db->group_start();
            $this->db->where('sa.end_date >=', $date);
            $this->db->or_where('sa.end_date IS NULL', null, false);
            $this->db->group_end();
        }

        $this->db->order_by('sa.start_date', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_shift_assignment(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_users' => (int) $data['id_users'],
            'id_shifts' => (int) $data['id_shifts'],
            'start_date' => $data['start_date'],
            'end_date' => !empty($data['end_date']) ? $data['end_date'] : null,
            'day_of_week' => isset($data['day_of_week']) && $data['day_of_week'] !== '' ? (int) $data['day_of_week'] : null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert('hr_shift_assignments', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_shift_assignment(int $id): bool
    {
        return $this->db->delete('hr_shift_assignments', ['id' => $id]);
    }

    // =========================================================================
    // 5. ATTENDANCE & LIVE PDKS PUNCH LOGS
    // =========================================================================

    public function log_attendance(
        int $user_id,
        string $punch_type,
        string $method = 'web',
        ?float $lat = null,
        ?float $lng = null,
        ?string $ip = null,
        ?string $device = null
    ): array {
        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');
        $time_now = date('H:i:s');

        // Record punch log
        $log = [
            'id_users' => $user_id,
            'timestamp' => $now,
            'punch_type' => strtoupper($punch_type),
            'method' => $method,
            'latitude' => $lat,
            'longitude' => $lng,
            'ip_address' => $ip ?: ($_SERVER['REMOTE_ADDR'] ?? null),
            'device_info' => $device ?: substr($_SERVER['HTTP_USER_AGENT'] ?? 'Web', 0, 250),
            'created_at' => $now,
        ];
        $this->db->insert('hr_attendance_logs', $log);

        // Recalculate daily attendance record for today
        $summary = $this->calculate_daily_attendance_record($user_id, $today);

        return [
            'success' => true,
            'punch_type' => $punch_type,
            'timestamp' => $now,
            'daily_summary' => $summary,
        ];
    }

    public function calculate_daily_attendance_record(int $user_id, string $date): array
    {
        $now = date('Y-m-d H:i:s');

        // 1. Get all punches for this user on this day
        $punches = $this->db
            ->where('id_users', $user_id)
            ->where("DATE(timestamp) =", $date)
            ->order_by('timestamp', 'ASC')
            ->get('hr_attendance_logs')
            ->result_array();

        if (empty($punches)) {
            return ['status' => 'absent'];
        }

        $in_time = null;
        $out_time = null;

        foreach ($punches as $p) {
            $t = date('H:i:s', strtotime($p['timestamp']));
            if ($p['punch_type'] === 'IN' && $in_time === null) {
                $in_time = $t;
            }
            if ($p['punch_type'] === 'OUT') {
                $out_time = $t;
            }
        }

        // If no OUT punch yet, use last punch or null
        if ($out_time === null && count($punches) > 1) {
            $last = end($punches);
            if ($last['punch_type'] === 'OUT') {
                $out_time = date('H:i:s', strtotime($last['timestamp']));
            }
        }

        // Determine shift
        $assignment = $this->db
            ->where('id_users', $user_id)
            ->where('start_date <=', $date)
            ->group_start()
            ->where('end_date >=', $date)
            ->or_where('end_date IS NULL', null, false)
            ->group_end()
            ->order_by('id', 'DESC')
            ->get('hr_shift_assignments')
            ->row_array();

        $shift_id = $assignment ? (int) $assignment['id_shifts'] : null;
        $shift = $shift_id ? $this->db->get_where('hr_shifts', ['id' => $shift_id])->row_array() : null;

        $worked_minutes = 0;
        $late_minutes = 0;
        $early_exit_minutes = 0;
        $overtime_minutes = 0;
        $status = 'present';

        if ($in_time && $out_time) {
            $in_sec = strtotime("{$date} {$in_time}");
            $out_sec = strtotime("{$date} {$out_time}");
            if ($out_sec > $in_sec) {
                $worked_minutes = max(0, (int) round(($out_sec - $in_sec) / 60));
                // Deduct break if worked > 4 hours
                if ($worked_minutes > 240 && $shift) {
                    $worked_minutes = max(0, $worked_minutes - (int) $shift['break_duration_minutes']);
                }
            }
        }

        if ($shift && $in_time) {
            $shift_start = strtotime("{$date} {$shift['start_time']}");
            $shift_end = strtotime("{$date} {$shift['end_time']}");
            $actual_in = strtotime("{$date} {$in_time}");

            // Late arrival
            $grace = (int) $shift['late_grace_minutes'] * 60;
            if ($actual_in > ($shift_start + $grace)) {
                $late_minutes = (int) round(($actual_in - $shift_start) / 60);
            }

            // Early exit
            if ($out_time) {
                $actual_out = strtotime("{$date} {$out_time}");
                if ($actual_out < $shift_end) {
                    $early_exit_minutes = (int) round(($shift_end - $actual_out) / 60);
                } elseif ($actual_out > $shift_end) {
                    $overtime_minutes = (int) round(($actual_out - $shift_end) / 60);
                }
            }

            // Half day calculation
            if ($worked_minutes > 0 && ($worked_minutes / 60) < (float) $shift['half_day_threshold_hours']) {
                $status = 'half_day';
            }
        }

        $record = [
            'id_users' => $user_id,
            'date' => $date,
            'id_shifts' => $shift_id,
            'in_time' => $in_time,
            'out_time' => $out_time,
            'total_worked_minutes' => $worked_minutes,
            'late_minutes' => $late_minutes,
            'early_exit_minutes' => $early_exit_minutes,
            'overtime_minutes' => $overtime_minutes,
            'status' => $status,
            'updated_at' => $now,
        ];

        $existing = $this->db->get_where('hr_daily_attendance', ['id_users' => $user_id, 'date' => $date])->row_array();
        if ($existing) {
            $this->db->update('hr_daily_attendance', $record, ['id' => $existing['id']]);
        } else {
            $record['created_at'] = $now;
            $this->db->insert('hr_daily_attendance', $record);
        }

        return $record;
    }

    public function get_daily_attendance(string $date, ?int $user_id = null): array
    {
        $this->db->select("
            da.*, s.name as shift_name, s.start_time as shift_start, s.end_time as shift_end,
            CONCAT(u.first_name, ' ', u.last_name) as employee_name, u.job_title,
            d.name as department_name
        ");
        $this->db->from('hr_daily_attendance da');
        $this->db->join('users u', 'u.id = da.id_users', 'left');
        $this->db->join('hr_employee_profiles p', 'p.id_users = u.id', 'left');
        $this->db->join('hr_departments d', 'd.id = p.id_departments', 'left');
        $this->db->join('hr_shifts s', 's.id = da.id_shifts', 'left');

        $this->db->where('da.date', $date);
        if ($user_id > 0) {
            $this->db->where('da.id_users', $user_id);
        }
        $this->db->order_by('da.in_time', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_monthly_attendance_summary(int $month, int $year, ?int $user_id = null): array
    {
        $this->db->select("
            da.id_users, CONCAT(u.first_name, ' ', u.last_name) as employee_name,
            COUNT(da.id) as total_logged_days,
            SUM(CASE WHEN da.status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN da.status = 'half_day' THEN 1 ELSE 0 END) as half_days,
            SUM(CASE WHEN da.status = 'on_leave' THEN 1 ELSE 0 END) as leave_days,
            SUM(CASE WHEN da.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(da.late_minutes) as total_late_minutes,
            SUM(da.overtime_minutes) as total_overtime_minutes,
            SUM(da.total_worked_minutes) as total_worked_minutes
        ");
        $this->db->from('hr_daily_attendance da');
        $this->db->join('users u', 'u.id = da.id_users', 'left');
        $this->db->where("MONTH(da.date) =", $month);
        $this->db->where("YEAR(da.date) =", $year);

        if ($user_id > 0) {
            $this->db->where('da.id_users', $user_id);
        }

        $this->db->group_by('da.id_users');
        return $this->db->get()->result_array();
    }

    // =========================================================================
    // 6. LEAVES & ABSENCE ENGINE (4857 SK)
    // =========================================================================

    public function get_leave_types(bool $active_only = true): array
    {
        if ($active_only) {
            $this->db->where('is_active', 1);
        }
        $this->db->order_by('id', 'ASC');
        return $this->db->get('hr_leave_types')->result_array();
    }

    public function save_leave_type(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'] ?? substr($data['name'], 0, 4))),
            'is_paid' => isset($data['is_paid']) ? (int) $data['is_paid'] : 1,
            'default_days_per_year' => (int) ($data['default_days_per_year'] ?? 14),
            'max_consecutive_days' => (int) ($data['max_consecutive_days'] ?? 14),
            'allows_carry_forward' => isset($data['allows_carry_forward']) ? (int) $data['allows_carry_forward'] : 1,
            'color' => $data['color'] ?? '#10b981',
            'is_active' => isset($data['is_active']) ? (int) $data['is_active'] : 1,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_leave_types', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_leave_types', $record);
        return (int) $this->db->insert_id();
    }

    public function get_leave_allocations(int $user_id, int $year): array
    {
        // First ensure allocations exist for this user & year
        $this->allocate_leaves_for_user($user_id, $year);

        $this->db->select('la.*, lt.name as leave_type_name, lt.code, lt.color, lt.is_paid');
        $this->db->from('hr_leave_allocations la');
        $this->db->join('hr_leave_types lt', 'lt.id = la.id_leave_types', 'inner');
        $this->db->where('la.id_users', $user_id);
        $this->db->where('la.year', $year);
        $allocs = $this->db->get()->result_array();

        foreach ($allocs as &$a) {
            $a['remaining_days'] = max(0, ((float) $a['allocated_days'] + (float) $a['carry_forward_days']) - (float) $a['used_days']);
        }

        return $allocs;
    }

    /**
     * Allocate leave days according to 4857 Turkish Labor Law:
     * - 1 to 5 years: 14 days
     * - 5 to 15 years: 20 days
     * - 15+ years: 26 days
     * - Age <= 18 or >= 50: min 20 days
     */
    public function allocate_leaves_for_user(int $user_id, int $year): void
    {
        $now = date('Y-m-d H:i:s');
        $profile = $this->db->get_where('hr_employee_profiles', ['id_users' => $user_id])->row_array();
        $joining_date = !empty($profile['date_of_joining']) ? $profile['date_of_joining'] : date('Y-m-d');
        $birth_date = !empty($profile['birth_date']) ? $profile['birth_date'] : null;

        $tenure_years = max(0, (strtotime("{$year}-12-31") - strtotime($joining_date)) / (365.25 * 86400));
        $age = $birth_date ? max(0, (strtotime("{$year}-12-31") - strtotime($birth_date)) / (365.25 * 86400)) : 30;

        $types = $this->get_leave_types(true);

        foreach ($types as $type) {
            $existing = $this->db
                ->get_where('hr_leave_allocations', [
                    'id_users' => $user_id,
                    'id_leave_types' => $type['id'],
                    'year' => $year,
                ])
                ->row_array();

            if ($existing) {
                continue;
            }

            $allocated = (float) $type['default_days_per_year'];

            // 4857 SK Seniority Rule for Annual Leave
            if ($type['code'] === 'ANNUAL') {
                if ($tenure_years >= 15) {
                    $allocated = 26.0;
                } elseif ($tenure_years >= 5) {
                    $allocated = 20.0;
                } else {
                    $allocated = 14.0;
                }

                if ($age <= 18 || $age >= 50) {
                    $allocated = max($allocated, 20.0);
                }
            }

            $this->db->insert('hr_leave_allocations', [
                'id_users' => $user_id,
                'id_leave_types' => $type['id'],
                'year' => $year,
                'allocated_days' => $allocated,
                'used_days' => 0.00,
                'carry_forward_days' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function get_leave_applications(array $filters = []): array
    {
        $this->db->select("
            la.*, lt.name as leave_type_name, lt.color as leave_type_color, lt.is_paid,
            CONCAT(u.first_name, ' ', u.last_name) as employee_name, u.email as employee_email,
            d.name as department_name,
            CONCAT(app.first_name, ' ', app.last_name) as approver_name
        ");
        $this->db->from('hr_leave_applications la');
        $this->db->join('hr_leave_types lt', 'lt.id = la.id_leave_types', 'inner');
        $this->db->join('users u', 'u.id = la.id_users', 'inner');
        $this->db->join('hr_employee_profiles p', 'p.id_users = u.id', 'left');
        $this->db->join('hr_departments d', 'd.id = p.id_departments', 'left');
        $this->db->join('users app', 'app.id = la.approved_by_user_id', 'left');

        if (!empty($filters['user_id'])) {
            $this->db->where('la.id_users', (int) $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('la.status', $filters['status']);
        }
        if (!empty($filters['year'])) {
            $this->db->where("YEAR(la.start_date) =", (int) $filters['year']);
        }

        $this->db->order_by('la.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_leave_application(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $start = $data['start_date'];
        $end = $data['end_date'];

        $days = max(1, (strtotime($end) - strtotime($start)) / 86400 + 1);

        $record = [
            'id_users' => (int) $data['id_users'],
            'id_leave_types' => (int) $data['id_leave_types'],
            'start_date' => $start,
            'end_date' => $end,
            'total_days' => (float) ($data['total_days'] ?? $days),
            'reason' => trim($data['reason'] ?? ''),
            'attachment_path' => $data['attachment_path'] ?? null,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('hr_leave_applications', $record);
        return (int) $this->db->insert_id();
    }

    public function update_leave_status(
        int $application_id,
        string $status,
        ?int $approved_by_user_id = null,
        ?string $rejection_reason = null
    ): bool {
        $now = date('Y-m-d H:i:s');
        $app = $this->db->get_where('hr_leave_applications', ['id' => $application_id])->row_array();
        if (!$app) {
            return false;
        }

        $updates = [
            'status' => $status,
            'approved_by_user_id' => $approved_by_user_id,
            'rejection_reason' => $rejection_reason,
            'updated_at' => $now,
        ];
        $this->db->update('hr_leave_applications', $updates, ['id' => $application_id]);

        // When approved, deduct days from allocation & sync with appointments unavailabilities
        if ($status === 'approved') {
            $year = (int) date('Y', strtotime($app['start_date']));
            $this->db->query("
                UPDATE `" . $this->db->dbprefix('hr_leave_allocations') . "`
                SET `used_days` = `used_days` + " . (float) $app['total_days'] . ", `updated_at` = '{$now}'
                WHERE `id_users` = " . (int) $app['id_users'] . " AND `id_leave_types` = " . (int) $app['id_leave_types'] . " AND `year` = {$year}
            ");

            // Sync with blocked_periods to block appointments on calendar
            if ($this->db->table_exists('blocked_periods')) {
                $this->db->insert('blocked_periods', [
                    'name' => 'İzin: ' . ($app['reason'] ?: 'İzinli'),
                    'start_datetime' => "{$app['start_date']} 00:00:00",
                    'end_datetime' => "{$app['end_date']} 23:59:59",
                    'notes' => 'HR İzin Kaydı (Onaylandı): ' . ($app['reason'] ?: 'İzinli'),
                    'create_datetime' => $now,
                    'update_datetime' => $now,
                ]);
            }
        }

        return true;
    }

    public function get_holidays(?int $year = null): array
    {
        if ($year) {
            $this->db->group_start();
            $this->db->where("YEAR(start_date) =", $year);
            $this->db->or_where('is_recurring', 1);
            $this->db->group_end();
        }
        $this->db->order_by('start_date', 'ASC');
        return $this->db->get('hr_holidays')->result_array();
    }

    public function save_holiday(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'name' => trim($data['name']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'is_recurring' => isset($data['is_recurring']) ? (int) $data['is_recurring'] : 0,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_holidays', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_holidays', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_holiday(int $id): bool
    {
        return $this->db->delete('hr_holidays', ['id' => $id]);
    }

    // =========================================================================
    // 7. PAYROLL, SALARY & ADVANCES
    // =========================================================================

    public function get_salary_components(): array
    {
        return $this->db->order_by('type', 'ASC')->get('hr_salary_components')->result_array();
    }

    public function get_salary_structure(int $user_id): ?array
    {
        $struct = $this->db->get_where('hr_salary_structures', ['id_users' => $user_id])->row_array();
        if ($struct && !empty($struct['components_json'])) {
            $struct['components'] = json_decode($struct['components_json'], true) ?: [];
        } else {
            $struct['components'] = [];
        }
        return $struct;
    }

    public function save_salary_structure(int $user_id, array $data): bool
    {
        $now = date('Y-m-d H:i:s');
        $existing = $this->db->get_where('hr_salary_structures', ['id_users' => $user_id])->row_array();

        $record = [
            'id_users' => $user_id,
            'base_salary' => (float) ($data['base_salary'] ?? 0.00),
            'currency' => $data['currency'] ?? 'TRY',
            'payment_frequency' => $data['payment_frequency'] ?? 'monthly',
            'components_json' => !empty($data['components']) ? json_encode($data['components']) : null,
            'updated_at' => $now,
        ];

        if ($existing) {
            $this->db->update('hr_salary_structures', $record, ['id_users' => $user_id]);
        } else {
            $record['created_at'] = $now;
            $this->db->insert('hr_salary_structures', $record);
        }
        return true;
    }

    public function get_payrolls(array $filters = []): array
    {
        $this->db->order_by('year', 'DESC');
        $this->db->order_by('month', 'DESC');
        if (!empty($filters['year'])) {
            $this->db->where('year', (int) $filters['year']);
        }
        if (!empty($filters['month'])) {
            $this->db->where('month', (int) $filters['month']);
        }
        return $this->db->get('hr_payrolls')->result_array();
    }

    public function generate_payroll(int $month, int $year, ?int $branch_id = null): array
    {
        $now = date('Y-m-d H:i:s');

        // Check or create master payroll row
        $payroll = $this->db
            ->get_where('hr_payrolls', [
                'month' => $month,
                'year' => $year,
                'branch_id' => $branch_id,
            ])
            ->row_array();

        if (!$payroll) {
            $this->db->insert('hr_payrolls', [
                'month' => $month,
                'year' => $year,
                'branch_id' => $branch_id,
                'status' => 'draft',
                'total_gross' => 0.00,
                'total_net' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $payroll_id = (int) $this->db->insert_id();
        } else {
            $payroll_id = (int) $payroll['id'];
        }

        // Get all active employees
        $employees = $this->get_employees(['is_active' => 1]);
        $total_gross = 0.00;
        $total_net = 0.00;

        foreach ($employees as $emp) {
            $user_id = (int) $emp['id'];

            // 1. Base salary
            $struct = $this->get_salary_structure($user_id);
            $base_salary = !empty($struct['base_salary']) ? (float) $struct['base_salary'] : 0.00;

            // 2. Commissions from BooKi (staff_commissions)
            $comm_sum = 0.00;
            if ($this->db->table_exists('staff_commissions')) {
                $comm_row = $this->db
                    ->select('SUM(commission_amount) as total')
                    ->where('id_users_staff', $user_id)
                    ->where("MONTH(created_at) =", $month)
                    ->where("YEAR(created_at) =", $year)
                    ->get('staff_commissions')
                    ->row_array();
                $comm_sum = (float) ($comm_row['total'] ?? 0.00);
            }

            // 3. Overtime from attendance
            $att_row = $this->db
                ->select('SUM(overtime_minutes) as ot_min')
                ->where('id_users', $user_id)
                ->where("MONTH(date) =", $month)
                ->where("YEAR(date) =", $year)
                ->get('hr_daily_attendance')
                ->row_array();
            $ot_hours = round(((int) ($att_row['ot_min'] ?? 0)) / 60, 2);
            $hourly_rate = $base_salary > 0 ? ($base_salary / 225) : 0.00; // 225 hours standard in TR Labor Law
            $overtime_amount = round($ot_hours * $hourly_rate * 1.5, 2); // 1.5x overtime multiplier

            // 4. Approved advances deduction
            $adv_row = $this->db
                ->select('SUM(requested_amount) as adv_total')
                ->where('id_users', $user_id)
                ->where('status', 'approved')
                ->get('hr_advances')
                ->row_array();
            $advance_deduction = (float) ($adv_row['adv_total'] ?? 0.00);

            // Gross pay
            $gross_pay = $base_salary + $comm_sum + $overtime_amount;

            // Standard deductions (SGK worker %14, Unemployment %1, Income tax ~%15, Stamp tax ~%0.759)
            $sgk_deduction = round($gross_pay * 0.14, 2);
            $unemployment_deduction = round($gross_pay * 0.01, 2);
            $tax_deduction = round(($gross_pay - $sgk_deduction - $unemployment_deduction) * 0.15, 2);

            $net_pay = max(0, $gross_pay - $sgk_deduction - $unemployment_deduction - $tax_deduction - $advance_deduction);

            $slip_data = [
                'id_payrolls' => $payroll_id,
                'id_users' => $user_id,
                'base_salary' => $base_salary,
                'commission_amount' => $comm_sum,
                'overtime_amount' => $overtime_amount,
                'bonus_amount' => 0.00,
                'gross_pay' => $gross_pay,
                'sgk_deduction' => $sgk_deduction + $unemployment_deduction,
                'tax_deduction' => $tax_deduction,
                'advance_deduction' => $advance_deduction,
                'other_deductions' => 0.00,
                'net_pay' => $net_pay,
                'details_json' => json_encode([
                    'ot_hours' => $ot_hours,
                    'hourly_rate' => $hourly_rate,
                    'sgk_worker' => $sgk_deduction,
                    'unemployment' => $unemployment_deduction,
                ]),
                'updated_at' => $now,
            ];

            $existing_slip = $this->db
                ->get_where('hr_payroll_slips', [
                    'id_payrolls' => $payroll_id,
                    'id_users' => $user_id,
                ])
                ->row_array();

            if ($existing_slip) {
                $this->db->update('hr_payroll_slips', $slip_data, ['id' => $existing_slip['id']]);
            } else {
                $slip_data['created_at'] = $now;
                $this->db->insert('hr_payroll_slips', $slip_data);
            }

            $total_gross += $gross_pay;
            $total_net += $net_pay;
        }

        // Update master payroll totals
        $this->db->update('hr_payrolls', [
            'total_gross' => $total_gross,
            'total_net' => $total_net,
            'updated_at' => $now,
        ], ['id' => $payroll_id]);

        return [
            'payroll_id' => $payroll_id,
            'month' => $month,
            'year' => $year,
            'total_gross' => $total_gross,
            'total_net' => $total_net,
            'employee_count' => count($employees),
        ];
    }

    public function get_payroll_slips(int $payroll_id): array
    {
        $this->db->select("
            ps.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name,
            u.email, u.phone_number, u.job_title,
            p.iban, p.bank_name, p.tckn_passport,
            d.name as department_name
        ");
        $this->db->from('hr_payroll_slips ps');
        $this->db->join('users u', 'u.id = ps.id_users', 'inner');
        $this->db->join('hr_employee_profiles p', 'p.id_users = u.id', 'left');
        $this->db->join('hr_departments d', 'd.id = p.id_departments', 'left');
        $this->db->where('ps.id_payrolls', $payroll_id);
        $this->db->order_by('u.first_name', 'ASC');
        return $this->db->get()->result_array();
    }

    public function get_user_payroll_slips(int $user_id): array
    {
        $this->db->select('ps.*, p.month, p.year, p.status as payroll_status');
        $this->db->from('hr_payroll_slips ps');
        $this->db->join('hr_payrolls p', 'p.id = ps.id_payrolls', 'inner');
        $this->db->where('ps.id_users', $user_id);
        $this->db->order_by('p.year', 'DESC');
        $this->db->order_by('p.month', 'DESC');
        return $this->db->get()->result_array();
    }

    // =========================================================================
    // 8. ADVANCES & EXPENSE CLAIMS
    // =========================================================================

    public function get_advances(array $filters = []): array
    {
        $this->db->select("
            a.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name,
            CONCAT(app.first_name, ' ', app.last_name) as approver_name
        ");
        $this->db->from('hr_advances a');
        $this->db->join('users u', 'u.id = a.id_users', 'inner');
        $this->db->join('users app', 'app.id = a.approved_by_user_id', 'left');

        if (!empty($filters['user_id'])) {
            $this->db->where('a.id_users', (int) $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('a.status', $filters['status']);
        }
        $this->db->order_by('a.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_advance(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_users' => (int) $data['id_users'],
            'requested_amount' => (float) $data['requested_amount'],
            'currency' => $data['currency'] ?? 'TRY',
            'reason' => trim($data['reason'] ?? ''),
            'installment_months' => (int) ($data['installment_months'] ?? 1),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert('hr_advances', $record);
        return (int) $this->db->insert_id();
    }

    public function update_advance_status(int $id, string $status, ?int $approved_by = null, ?string $reason = null): bool
    {
        return $this->db->update('hr_advances', [
            'status' => $status,
            'approved_by_user_id' => $approved_by,
            'rejection_reason' => $reason,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    public function get_expense_claims(array $filters = []): array
    {
        $this->db->select("
            e.*, CONCAT(u.first_name, ' ', u.last_name) as employee_name,
            CONCAT(app.first_name, ' ', app.last_name) as approver_name
        ");
        $this->db->from('hr_expense_claims e');
        $this->db->join('users u', 'u.id = e.id_users', 'inner');
        $this->db->join('users app', 'app.id = e.approved_by_user_id', 'left');

        if (!empty($filters['user_id'])) {
            $this->db->where('e.id_users', (int) $filters['user_id']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('e.status', $filters['status']);
        }
        $this->db->order_by('e.claim_date', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_expense_claim(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_users' => (int) $data['id_users'],
            'claim_date' => $data['claim_date'] ?? date('Y-m-d'),
            'category' => $data['category'] ?? 'general',
            'amount' => (float) $data['amount'],
            'currency' => $data['currency'] ?? 'TRY',
            'invoice_number' => $data['invoice_number'] ?? null,
            'receipt_file_path' => $data['receipt_file_path'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert('hr_expense_claims', $record);
        return (int) $this->db->insert_id();
    }

    public function update_expense_status(int $id, string $status, ?int $approved_by = null): bool
    {
        return $this->db->update('hr_expense_claims', [
            'status' => $status,
            'approved_by_user_id' => $approved_by,
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);
    }

    // =========================================================================
    // 9. ASSET & CUSTODY (ZİMMET)
    // =========================================================================

    public function get_assets(array $filters = []): array
    {
        $this->db->order_by('asset_name', 'ASC');
        if (!empty($filters['category'])) {
            $this->db->where('category', $filters['category']);
        }
        if (!empty($filters['status'])) {
            $this->db->where('status', $filters['status']);
        }
        $assets = $this->db->get('hr_assets')->result_array();

        // Attach current active assignment
        foreach ($assets as &$asset) {
            $curr = $this->db
                ->select("aa.*, CONCAT(u.first_name, ' ', u.last_name) as assigned_to_name")
                ->from('hr_asset_assignments aa')
                ->join('users u', 'u.id = aa.id_users', 'left')
                ->where('aa.id_assets', $asset['id'])
                ->where('aa.return_date IS NULL', null, false)
                ->get()
                ->row_array();
            $asset['current_assignment'] = $curr;
        }

        return $assets;
    }

    public function save_asset(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'asset_name' => trim($data['asset_name']),
            'asset_code' => strtoupper(trim($data['asset_code'] ?? substr($data['asset_name'], 0, 4))),
            'category' => $data['category'] ?? 'laptop',
            'serial_number' => $data['serial_number'] ?? null,
            'purchase_date' => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
            'purchase_cost' => !empty($data['purchase_cost']) ? (float) $data['purchase_cost'] : null,
            'status' => $data['status'] ?? 'available',
            'notes' => $data['notes'] ?? null,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_assets', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_assets', $record);
        return (int) $this->db->insert_id();
    }

    public function delete_asset(int $id): bool
    {
        $this->db->delete('hr_asset_assignments', ['id_assets' => $id]);
        return $this->db->delete('hr_assets', ['id' => $id]);
    }

    public function get_asset_assignments(array $filters = []): array
    {
        $this->db->select("
            aa.*, a.asset_name, a.asset_code, a.category as asset_category, a.serial_number,
            CONCAT(u.first_name, ' ', u.last_name) as employee_name
        ");
        $this->db->from('hr_asset_assignments aa');
        $this->db->join('hr_assets a', 'a.id = aa.id_assets', 'inner');
        $this->db->join('users u', 'u.id = aa.id_users', 'inner');

        if (!empty($filters['user_id'])) {
            $this->db->where('aa.id_users', (int) $filters['user_id']);
        }
        if (!empty($filters['active_only'])) {
            $this->db->where('aa.return_date IS NULL', null, false);
        }

        $this->db->order_by('aa.assigned_date', 'DESC');
        return $this->db->get()->result_array();
    }

    public function assign_asset(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $asset_id = (int) $data['id_assets'];

        $record = [
            'id_assets' => $asset_id,
            'id_users' => (int) $data['id_users'],
            'assigned_date' => $data['assigned_date'] ?? date('Y-m-d'),
            'condition_on_assignment' => $data['condition_on_assignment'] ?? 'Sorunsuz ve çalışır durumda teslim edildi.',
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $this->db->insert('hr_asset_assignments', $record);
        $insert_id = (int) $this->db->insert_id();

        // Mark asset as assigned
        $this->db->update('hr_assets', ['status' => 'assigned', 'updated_at' => $now], ['id' => $asset_id]);

        return $insert_id;
    }

    public function return_asset(int $assignment_id, string $condition_on_return, ?string $notes = null): bool
    {
        $now = date('Y-m-d H:i:s');
        $assignment = $this->db->get_where('hr_asset_assignments', ['id' => $assignment_id])->row_array();
        if (!$assignment) {
            return false;
        }

        $this->db->update('hr_asset_assignments', [
            'return_date' => date('Y-m-d'),
            'condition_on_return' => $condition_on_return,
            'notes' => $notes ?: $assignment['notes'],
            'updated_at' => $now,
        ], ['id' => $assignment_id]);

        $this->db->update('hr_assets', ['status' => 'available', 'updated_at' => $now], ['id' => $assignment['id_assets']]);

        return true;
    }

    // =========================================================================
    // 10. RECRUITMENT & ATS
    // =========================================================================

    public function get_job_openings(array $filters = []): array
    {
        $this->db->select("
            jo.*, d.name as department_name,
            COUNT(ja.id) as applicants_count
        ");
        $this->db->from('hr_job_openings jo');
        $this->db->join('hr_departments d', 'd.id = jo.id_departments', 'left');
        $this->db->join('hr_job_applicants ja', 'ja.id_job_openings = jo.id', 'left');

        if (!empty($filters['status'])) {
            $this->db->where('jo.status', $filters['status']);
        }

        $this->db->group_by('jo.id');
        $this->db->order_by('jo.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_job_opening(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $id = !empty($data['id']) ? (int) $data['id'] : 0;

        $record = [
            'title' => trim($data['title']),
            'id_departments' => !empty($data['id_departments']) ? (int) $data['id_departments'] : null,
            'branch_id' => !empty($data['branch_id']) ? (int) $data['branch_id'] : null,
            'status' => $data['status'] ?? 'open',
            'job_description' => $data['job_description'] ?? null,
            'requirements' => $data['requirements'] ?? null,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $this->db->update('hr_job_openings', $record, ['id' => $id]);
            return $id;
        }

        $record['created_at'] = $now;
        $this->db->insert('hr_job_openings', $record);
        return (int) $this->db->insert_id();
    }

    public function get_job_applicants(?int $job_id = null): array
    {
        $this->db->select('ja.*, jo.title as job_title');
        $this->db->from('hr_job_applicants ja');
        $this->db->join('hr_job_openings jo', 'jo.id = ja.id_job_openings', 'inner');
        if ($job_id > 0) {
            $this->db->where('ja.id_job_openings', $job_id);
        }
        $this->db->order_by('ja.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function save_job_applicant(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $record = [
            'id_job_openings' => (int) $data['id_job_openings'],
            'full_name' => trim($data['full_name']),
            'email' => trim($data['email']),
            'phone' => trim($data['phone'] ?? ''),
            'cv_path' => $data['cv_path'] ?? null,
            'status' => $data['status'] ?? 'new',
            'notes' => $data['notes'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->insert('hr_job_applicants', $record);
        return (int) $this->db->insert_id();
    }

    public function update_job_applicant_status(int $id, string $status, ?string $notes = null): bool
    {
        $updates = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($notes !== null) {
            $updates['notes'] = $notes;
        }
        return $this->db->update('hr_job_applicants', $updates, ['id' => $id]);
    }
}
