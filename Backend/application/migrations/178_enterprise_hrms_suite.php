<?php defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/App_Migration.php';

/**
 * Migration 178: Enterprise HRMS Suite.
 *
 * Implements benchmark-grade HR & Human Resources Management inspired by:
 * Frappe HRMS, OrangeHRM, Odoo HR, and Turkish Labor Law (4857 SK).
 *
 * 1. Departments & Organizational Tree (ea_hr_departments)
 * 2. Designations / Job Titles (ea_hr_designations)
 * 3. Comprehensive Employee Profiles & Özlük (ea_hr_employee_profiles)
 * 4. Digital Document Vault / Özlük Dosyası (ea_hr_documents)
 * 5. Shifts & Work Schedules (ea_hr_shifts, ea_hr_shift_assignments)
 * 6. Live Punch & PDKS Logs (ea_hr_attendance_logs)
 * 7. Daily & Monthly Attendance / Puantaj (ea_hr_daily_attendance)
 * 8. Leave Types, Allocations & Applications (ea_hr_leave_types, ea_hr_leave_allocations, ea_hr_leave_applications)
 * 9. Company & National Holidays (ea_hr_holidays)
 * 10. Salary Components, Structures & Monthly Payroll (ea_hr_salary_components, ea_hr_salary_structures, ea_hr_payrolls, ea_hr_payroll_slips)
 * 11. Employee Advances & Loans (ea_hr_advances)
 * 12. Employee Expense Claims (ea_hr_expense_claims)
 * 13. Asset & Custody Management / Zimmet (ea_hr_assets, ea_hr_asset_assignments)
 * 14. Lightweight ATS / Recruitment (ea_hr_job_openings, ea_hr_job_applicants)
 * 15. User Kiosk PIN & QR Extensions (ea_users.pin_code, qr_token)
 */
class Migration_Enterprise_hrms_suite extends App_Migration
{
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        // 1. Departments Table
        if (!$this->db->table_exists('hr_departments')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_departments') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `parent_id` INT UNSIGNED NULL DEFAULT NULL,
                    `manager_id` INT UNSIGNED NULL DEFAULT NULL,
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_dept_code` (`code`),
                    KEY `idx_hr_dept_parent` (`parent_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed default departments
            $default_depts = [
                ['name' => 'Yönetim & İdari İşler', 'code' => 'MGMT'],
                ['name' => 'Operasyon & Servis', 'code' => 'OPS'],
                ['name' => 'Mutfak & Üretim', 'code' => 'KITCHEN'],
                ['name' => 'Sağlık & Medikal Kadro', 'code' => 'CLINICAL'],
                ['name' => 'Satış & Pazarlama', 'code' => 'SALES'],
                ['name' => 'Temizlik & Hijyen', 'code' => 'FACILITY'],
                ['name' => 'Muhasebe & Finans', 'code' => 'FINANCE'],
            ];
            foreach ($default_depts as $dept) {
                $this->db->insert('hr_departments', [
                    'name' => $dept['name'],
                    'code' => $dept['code'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Designations Table
        if (!$this->db->table_exists('hr_designations')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_designations') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `description` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_desig_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed default designations
            $default_desigs = [
                ['title' => 'Genel Müdür / Direktör', 'code' => 'GM'],
                ['title' => 'Şube / Operasyon Müdürü', 'code' => 'BM'],
                ['title' => 'Kıdemli Uzman / Baş Hekim / Şef', 'code' => 'SR_SPEC'],
                ['title' => 'Uzman / Hekim / Terapist / Eğitmen', 'code' => 'SPEC'],
                ['title' => 'Servis Elemanı / Garson / Asistan', 'code' => 'STAFF'],
                ['title' => 'Resepsiyonist / Danışma', 'code' => 'FRONT_DESK'],
                ['title' => 'Kasiyer / Ön Muhasebe', 'code' => 'CASHIER'],
            ];
            foreach ($default_desigs as $desig) {
                $this->db->insert('hr_designations', [
                    'title' => $desig['title'],
                    'code' => $desig['code'],
                    'description' => $desig['title'] . ' pozisyonu',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 3. Employee Profiles Table
        if (!$this->db->table_exists('hr_employee_profiles')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_employee_profiles') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `tckn_passport` VARCHAR(32) NULL,
                    `birth_date` DATE NULL,
                    `gender` VARCHAR(16) NULL,
                    `blood_type` VARCHAR(8) NULL,
                    `marital_status` VARCHAR(16) NULL,
                    `military_status` VARCHAR(32) NULL,
                    `emergency_contact_name` VARCHAR(120) NULL,
                    `emergency_contact_phone` VARCHAR(32) NULL,
                    `iban` VARCHAR(64) NULL,
                    `bank_name` VARCHAR(64) NULL,
                    `employment_type` VARCHAR(32) NOT NULL DEFAULT 'full_time',
                    `date_of_joining` DATE NULL,
                    `date_of_leaving` DATE NULL,
                    `probation_end_date` DATE NULL,
                    `reports_to_user_id` INT UNSIGNED NULL,
                    `id_departments` INT UNSIGNED NULL,
                    `id_designations` INT UNSIGNED NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_hr_emp_user` (`id_users`),
                    KEY `idx_hr_emp_dept` (`id_departments`),
                    KEY `idx_hr_emp_desig` (`id_designations`),
                    KEY `idx_hr_emp_reports_to` (`reports_to_user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 4. Employee Documents Table (Özlük Dosyası)
        if (!$this->db->table_exists('hr_documents')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_documents') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `document_type` VARCHAR(64) NOT NULL DEFAULT 'other',
                    `title` VARCHAR(191) NOT NULL,
                    `file_path` VARCHAR(255) NOT NULL,
                    `file_name` VARCHAR(255) NOT NULL,
                    `expiry_date` DATE NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'valid',
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_doc_user` (`id_users`),
                    KEY `idx_hr_doc_type` (`document_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 5. Shifts Table
        if (!$this->db->table_exists('hr_shifts')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_shifts') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `start_time` TIME NOT NULL,
                    `end_time` TIME NOT NULL,
                    `break_duration_minutes` INT NOT NULL DEFAULT 60,
                    `late_grace_minutes` INT NOT NULL DEFAULT 15,
                    `half_day_threshold_hours` DECIMAL(4,2) NOT NULL DEFAULT 4.00,
                    `color` VARCHAR(16) NOT NULL DEFAULT '#3b82f6',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_shift_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed default shifts
            $default_shifts = [
                ['name' => 'Sabah Vardiyası (09:00 - 18:00)', 'code' => 'MORNING', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'color' => '#10b981'],
                ['name' => 'Öğle / Akşam Vardiyası (13:00 - 22:00)', 'code' => 'EVENING', 'start_time' => '13:00:00', 'end_time' => '22:00:00', 'color' => '#f59e0b'],
                ['name' => 'Gece Vardiyası (22:00 - 07:00)', 'code' => 'NIGHT', 'start_time' => '22:00:00', 'end_time' => '07:00:00', 'color' => '#6366f1'],
            ];
            foreach ($default_shifts as $shift) {
                $this->db->insert('hr_shifts', [
                    'name' => $shift['name'],
                    'code' => $shift['code'],
                    'start_time' => $shift['start_time'],
                    'end_time' => $shift['end_time'],
                    'break_duration_minutes' => 60,
                    'late_grace_minutes' => 15,
                    'half_day_threshold_hours' => 4.00,
                    'color' => $shift['color'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 6. Shift Assignments Table
        if (!$this->db->table_exists('hr_shift_assignments')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_shift_assignments') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `id_shifts` INT UNSIGNED NOT NULL,
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NULL,
                    `day_of_week` TINYINT NULL DEFAULT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_sa_user` (`id_users`),
                    KEY `idx_hr_sa_shift` (`id_shifts`),
                    KEY `idx_hr_sa_dates` (`start_date`, `end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 7. Attendance Punch Logs Table (PDKS Canlı Loglar)
        if (!$this->db->table_exists('hr_attendance_logs')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_attendance_logs') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `timestamp` DATETIME NOT NULL,
                    `punch_type` ENUM('IN', 'OUT') NOT NULL,
                    `method` VARCHAR(32) NOT NULL DEFAULT 'web',
                    `latitude` DECIMAL(10,8) NULL,
                    `longitude` DECIMAL(11,8) NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `device_info` VARCHAR(255) NULL,
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_log_user_time` (`id_users`, `timestamp`),
                    KEY `idx_hr_log_punch_type` (`punch_type`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 8. Daily Attendance / Puantaj Table
        if (!$this->db->table_exists('hr_daily_attendance')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_daily_attendance') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `date` DATE NOT NULL,
                    `id_shifts` INT UNSIGNED NULL,
                    `in_time` TIME NULL,
                    `out_time` TIME NULL,
                    `total_worked_minutes` INT NOT NULL DEFAULT 0,
                    `late_minutes` INT NOT NULL DEFAULT 0,
                    `early_exit_minutes` INT NOT NULL DEFAULT 0,
                    `overtime_minutes` INT NOT NULL DEFAULT 0,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'present',
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_hr_user_date` (`id_users`, `date`),
                    KEY `idx_hr_att_date` (`date`),
                    KEY `idx_hr_att_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 9. Leave Types Table
        if (!$this->db->table_exists('hr_leave_types')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_leave_types') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
                    `default_days_per_year` INT NOT NULL DEFAULT 14,
                    `max_consecutive_days` INT NOT NULL DEFAULT 14,
                    `allows_carry_forward` TINYINT(1) NOT NULL DEFAULT 1,
                    `color` VARCHAR(16) NOT NULL DEFAULT '#10b981',
                    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_lt_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed standard leave types according to Turkish Labor Law (4857 SK)
            $default_leaves = [
                ['name' => 'Yıllık Ücretli İzin (4857 SK)', 'code' => 'ANNUAL', 'is_paid' => 1, 'default_days' => 14, 'color' => '#10b981'],
                ['name' => 'Mazeret İzni', 'code' => 'CASUAL', 'is_paid' => 1, 'default_days' => 3, 'color' => '#3b82f6'],
                ['name' => 'Hastalık / İstirahat Raporu', 'code' => 'SICK', 'is_paid' => 1, 'default_days' => 10, 'color' => '#ef4444'],
                ['name' => 'Ücretsiz İzin', 'code' => 'UNPAID', 'is_paid' => 0, 'default_days' => 0, 'color' => '#6b7280'],
                ['name' => 'Evlilik İzni', 'code' => 'MARRIAGE', 'is_paid' => 1, 'default_days' => 3, 'color' => '#ec4899'],
                ['name' => 'Babalık İzni', 'code' => 'PATERNITY', 'is_paid' => 1, 'default_days' => 5, 'color' => '#8b5cf6'],
                ['name' => 'Vefat İzni', 'code' => 'BEREAVEMENT', 'is_paid' => 1, 'default_days' => 3, 'color' => '#1f2937'],
            ];
            foreach ($default_leaves as $dl) {
                $this->db->insert('hr_leave_types', [
                    'name' => $dl['name'],
                    'code' => $dl['code'],
                    'is_paid' => $dl['is_paid'],
                    'default_days_per_year' => $dl['default_days'],
                    'max_consecutive_days' => 30,
                    'allows_carry_forward' => $dl['code'] === 'ANNUAL' ? 1 : 0,
                    'color' => $dl['color'],
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 10. Leave Allocations Table
        if (!$this->db->table_exists('hr_leave_allocations')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_leave_allocations') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `id_leave_types` INT UNSIGNED NOT NULL,
                    `year` INT NOT NULL,
                    `allocated_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `used_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `carry_forward_days` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_hr_la_user_type_year` (`id_users`, `id_leave_types`, `year`),
                    KEY `idx_hr_la_user` (`id_users`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 11. Leave Applications Table
        if (!$this->db->table_exists('hr_leave_applications')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_leave_applications') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `id_leave_types` INT UNSIGNED NOT NULL,
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NOT NULL,
                    `total_days` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
                    `reason` TEXT NULL,
                    `attachment_path` VARCHAR(255) NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `approved_by_user_id` INT UNSIGNED NULL,
                    `rejection_reason` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_leave_app_user` (`id_users`),
                    KEY `idx_hr_leave_app_status` (`status`),
                    KEY `idx_hr_leave_app_dates` (`start_date`, `end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 12. Holidays Table
        if (!$this->db->table_exists('hr_holidays')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_holidays') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `start_date` DATE NOT NULL,
                    `end_date` DATE NOT NULL,
                    `is_recurring` TINYINT(1) NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_hol_dates` (`start_date`, `end_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed standard official holidays
            $year = (int) date('Y');
            $default_holidays = [
                ['name' => 'Yılbaşı Tatili', 'start' => "{$year}-01-01", 'end' => "{$year}-01-01", 'recurring' => 1],
                ['name' => 'Ulusal Egemenlik ve Çocuk Bayramı', 'start' => "{$year}-04-23", 'end' => "{$year}-04-23", 'recurring' => 1],
                ['name' => 'Emek ve Dayanışma Günü', 'start' => "{$year}-05-01", 'end' => "{$year}-05-01", 'recurring' => 1],
                ['name' => 'Atatürk\'ü Anma, Gençlik ve Spor Bayramı', 'start' => "{$year}-05-19", 'end' => "{$year}-05-19", 'recurring' => 1],
                ['name' => 'Demokrasi ve Milli Birlik Günü', 'start' => "{$year}-07-15", 'end' => "{$year}-07-15", 'recurring' => 1],
                ['name' => 'Zafer Bayramı', 'start' => "{$year}-08-30", 'end' => "{$year}-08-30", 'recurring' => 1],
                ['name' => 'Cumhuriyet Bayramı', 'start' => "{$year}-10-29", 'end' => "{$year}-10-29", 'recurring' => 1],
            ];
            foreach ($default_holidays as $h) {
                $this->db->insert('hr_holidays', [
                    'name' => $h['name'],
                    'start_date' => $h['start'],
                    'end_date' => $h['end'],
                    'is_recurring' => $h['recurring'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 13. Salary Components Table
        if (!$this->db->table_exists('hr_salary_components')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_salary_components') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(120) NOT NULL,
                    `code` VARCHAR(32) NOT NULL,
                    `type` VARCHAR(32) NOT NULL DEFAULT 'earning',
                    `is_taxable` TINYINT(1) NOT NULL DEFAULT 1,
                    `is_sgk` TINYINT(1) NOT NULL DEFAULT 1,
                    `is_fixed` TINYINT(1) NOT NULL DEFAULT 1,
                    `default_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_sc_code` (`code`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Seed default components
            $default_comps = [
                ['name' => 'Temel Maaş', 'code' => 'BASIC', 'type' => 'earning'],
                ['name' => 'Yemek Yardımı / Nakdi', 'code' => 'MEAL', 'type' => 'earning'],
                ['name' => 'Yol Yardımı / Ulaşım', 'code' => 'TRANSPORT', 'type' => 'earning'],
                ['name' => 'Satış / Hizmet Primi', 'code' => 'COMMISSION', 'type' => 'earning'],
                ['name' => 'Fazla Mesai Ücreti', 'code' => 'OVERTIME', 'type' => 'earning'],
                ['name' => 'SGK İşçi Primi (%14)', 'code' => 'SGK_WORKER', 'type' => 'deduction'],
                ['name' => 'İşsizlik Sigortası Primi (%1)', 'code' => 'UNEMPLOYMENT', 'type' => 'deduction'],
                ['name' => 'Gelir Vergisi', 'code' => 'INCOME_TAX', 'type' => 'deduction'],
                ['name' => 'Damga Vergisi', 'code' => 'STAMP_TAX', 'type' => 'deduction'],
                ['name' => 'Personel Avans Mahsubu', 'code' => 'ADVANCE_DEDUCT', 'type' => 'deduction'],
            ];
            foreach ($default_comps as $c) {
                $this->db->insert('hr_salary_components', [
                    'name' => $c['name'],
                    'code' => $c['code'],
                    'type' => $c['type'],
                    'is_taxable' => $c['type'] === 'earning' ? 1 : 0,
                    'is_sgk' => $c['type'] === 'earning' ? 1 : 0,
                    'is_fixed' => $c['code'] === 'BASIC' ? 1 : 0,
                    'default_amount' => 0.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 14. Salary Structures Table (Kişi Başı Maaş Yapısı)
        if (!$this->db->table_exists('hr_salary_structures')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_salary_structures') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `base_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `currency` VARCHAR(8) NOT NULL DEFAULT 'TRY',
                    `payment_frequency` VARCHAR(16) NOT NULL DEFAULT 'monthly',
                    `components_json` LONGTEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_hr_ss_user` (`id_users`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 15. Monthly Payrolls Table
        if (!$this->db->table_exists('hr_payrolls')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_payrolls') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `month` INT NOT NULL,
                    `year` INT NOT NULL,
                    `branch_id` INT UNSIGNED NULL DEFAULT NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'draft',
                    `total_gross` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `total_net` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                    `notes` TEXT NULL,
                    `finalized_at` DATETIME NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_pay_period` (`month`, `year`, `branch_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 16. Payroll Slips Table (Bordro Zarfı / Maaş Pusulası)
        if (!$this->db->table_exists('hr_payroll_slips')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_payroll_slips') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_payrolls` INT UNSIGNED NOT NULL,
                    `id_users` INT UNSIGNED NOT NULL,
                    `base_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `overtime_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `bonus_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `gross_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `sgk_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `tax_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `advance_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `other_deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `net_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `details_json` LONGTEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_slip_payroll` (`id_payrolls`),
                    KEY `idx_hr_slip_user` (`id_users`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 17. Advances Table
        if (!$this->db->table_exists('hr_advances')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_advances') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `requested_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `currency` VARCHAR(8) NOT NULL DEFAULT 'TRY',
                    `reason` TEXT NULL,
                    `installment_months` INT NOT NULL DEFAULT 1,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `approved_by_user_id` INT UNSIGNED NULL,
                    `rejection_reason` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_adv_user` (`id_users`),
                    KEY `idx_hr_adv_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 18. Expense Claims Table
        if (!$this->db->table_exists('hr_expense_claims')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_expense_claims') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users` INT UNSIGNED NOT NULL,
                    `claim_date` DATE NOT NULL,
                    `category` VARCHAR(64) NOT NULL DEFAULT 'general',
                    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    `currency` VARCHAR(8) NOT NULL DEFAULT 'TRY',
                    `invoice_number` VARCHAR(64) NULL,
                    `receipt_file_path` VARCHAR(255) NULL,
                    `notes` TEXT NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
                    `approved_by_user_id` INT UNSIGNED NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_exp_user` (`id_users`),
                    KEY `idx_hr_exp_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 19. Assets Table (Varlık / Envanter)
        if (!$this->db->table_exists('hr_assets')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_assets') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `asset_name` VARCHAR(120) NOT NULL,
                    `asset_code` VARCHAR(32) NOT NULL,
                    `category` VARCHAR(32) NOT NULL DEFAULT 'laptop',
                    `serial_number` VARCHAR(64) NULL,
                    `purchase_date` DATE NULL,
                    `purchase_cost` DECIMAL(10,2) NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'available',
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_asset_code` (`asset_code`),
                    KEY `idx_hr_asset_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 20. Asset Assignments Table (Zimmet)
        if (!$this->db->table_exists('hr_asset_assignments')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_asset_assignments') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_assets` INT UNSIGNED NOT NULL,
                    `id_users` INT UNSIGNED NOT NULL,
                    `assigned_date` DATE NOT NULL,
                    `return_date` DATE NULL,
                    `condition_on_assignment` TEXT NULL,
                    `condition_on_return` TEXT NULL,
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_aa_asset` (`id_assets`),
                    KEY `idx_hr_aa_user` (`id_users`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 21. Job Openings Table (ATS)
        if (!$this->db->table_exists('hr_job_openings')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_job_openings') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `title` VARCHAR(120) NOT NULL,
                    `id_departments` INT UNSIGNED NULL,
                    `branch_id` INT UNSIGNED NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'open',
                    `job_description` TEXT NULL,
                    `requirements` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_job_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 22. Job Applicants Table (ATS)
        if (!$this->db->table_exists('hr_job_applicants')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `" . $this->db->dbprefix('hr_job_applicants') . "` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_job_openings` INT UNSIGNED NOT NULL,
                    `full_name` VARCHAR(120) NOT NULL,
                    `email` VARCHAR(120) NOT NULL,
                    `phone` VARCHAR(32) NULL,
                    `cv_path` VARCHAR(255) NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'new',
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hr_app_job` (`id_job_openings`),
                    KEY `idx_hr_app_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 23. Extend Users Table with PIN and QR token for quick Kiosk PDKS
        if ($this->db->table_exists('users')) {
            $user_fields = [];
            if (!$this->db->field_exists('pin_code', 'users')) {
                $user_fields['pin_code'] = ['type' => 'VARCHAR', 'constraint' => 16, 'null' => true, 'default' => null];
            }
            if (!$this->db->field_exists('qr_token', 'users')) {
                $user_fields['qr_token'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }

            if (!empty($user_fields)) {
                $this->dbforge->add_column('users', $user_fields);
            }
        }
    }

    public function down(): void
    {
        // Dropping in reverse order
        $tables = [
            'hr_job_applicants',
            'hr_job_openings',
            'hr_asset_assignments',
            'hr_assets',
            'hr_expense_claims',
            'hr_advances',
            'hr_payroll_slips',
            'hr_payrolls',
            'hr_salary_structures',
            'hr_salary_components',
            'hr_holidays',
            'hr_leave_applications',
            'hr_leave_allocations',
            'hr_leave_types',
            'hr_daily_attendance',
            'hr_attendance_logs',
            'hr_shift_assignments',
            'hr_shifts',
            'hr_documents',
            'hr_employee_profiles',
            'hr_designations',
            'hr_departments',
        ];

        foreach ($tables as $tbl) {
            if ($this->db->table_exists($tbl)) {
                $this->dbforge->drop_table($tbl, true);
            }
        }

        if ($this->db->table_exists('users')) {
            if ($this->db->field_exists('pin_code', 'users')) {
                $this->dbforge->drop_column('users', 'pin_code');
            }
            if ($this->db->field_exists('qr_token', 'users')) {
                $this->dbforge->drop_column('users', 'qr_token');
            }
        }
    }
}
