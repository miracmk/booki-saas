<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 169: Enterprise Multi-Vertical Master Suite.
 *
 * Implements benchmark-level capabilities across 5 core verticals:
 * 1. Legal Practice Management (Clio / Legal Guru / Suitor Law Firm):
 *    - legal_matters (Dava ve dosya takibi, esas/karar no, mahkeme, avans bakiye)
 *    - legal_hearings (Duruşma ajandası, yasal hak düşürücü süreler)
 *    - legal_time_entries (Kronometreli saatlik faturalama, timer)
 *    - legal_expenses (Dava masraf ve harçları, gider avansı mahsuplaşma)
 *
 * 2. Consulting & Strategic Project Management (Consultant Management / Leantime):
 *    - consulting_projects (Proje kodları, kapsam, bütçe)
 *    - consulting_milestones (Kilometre taşları, teslimatlar, müşteri onayı)
 *    - consulting_timesheets (Faturalandırılabilir / faturalandırılamaz efor takibi)
 *
 * 3. Clinical EHR, Vitals & Prescriptions (OpenMRS / Bahmni / Open Hospital / RemoteClinic):
 *    - patient_vitals (Tansiyon, nabız, ateş, boy/kilo/BMI, SpO2, kan şekeri)
 *    - patient_prescriptions (Reçete, ilaç, dozaj, kullanım talimatı)
 *    - patient_allergies (İlaç ve besin alerjileri, şiddet derecesi)
 *    - clinical_lab_orders (Tahlil/tetkik istemleri, referans aralıkları, anormal değer uyarısı)
 *
 * 4. Beauty Salon Processing Gaps, Tips & Formulas (OpenSalon / Salon Booking System):
 *    - services table extensions (lead_in_duration, processing_duration, lead_out_duration)
 *    - customer_beauty_profiles (Boya formülü arşivi, cilt/tırnak tipi, önce/sonra fotoğrafı)
 *    - appointment_tips (Kasa bahşişi ve personel hakediş dağıtımı)
 *
 * 5. Car Wash & Detailing Live TV & 360 Inspection (car-wash-management / OpenWashing):
 *    - customer_vehicles vehicle_segment (hatchback, sedan, suv, van, truck)
 *    - carwash_queue_logs (Canlı peron TV bekleme ekranı, geri sayım ve bildirim takibi)
 *    - vehicle_inspections scratch_coordinates_json (360° hasar/çizik SVG koordinatları)
 */
class Migration_Enterprise_multi_vertical_master_suite extends CI_Migration
{
    public function up(): void
    {
        $p = $this->db->dbprefix;

        // =====================================================================
        // 1. HUKUK BÜROSU & AVUKATLIK SUITE (LEGAL MATTERS, HEARINGS, BILLABLE)
        // =====================================================================
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}legal_matters` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `matter_number` VARCHAR(50) NOT NULL,
                `id_users_client` INT NOT NULL,
                `id_users_attorney` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `court_name` VARCHAR(191) NULL,
                `case_number` VARCHAR(100) NULL,
                `decision_number` VARCHAR(100) NULL,
                `case_type` VARCHAR(50) NOT NULL DEFAULT 'civil',
                `case_status` VARCHAR(50) NOT NULL DEFAULT 'open',
                `claim_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `retainer_balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `opposing_party` VARCHAR(191) NULL,
                `opposing_counsel` VARCHAR(191) NULL,
                `opened_at` DATE NULL,
                `closed_at` DATE NULL,
                `description` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_matter_number` (`matter_number`),
                KEY `idx_client` (`id_users_client`),
                KEY `idx_attorney` (`id_users_attorney`),
                KEY `idx_case_status` (`case_status`),
                KEY `idx_case_number` (`case_number`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}legal_hearings` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_legal_matters` INT UNSIGNED NOT NULL,
                `hearing_datetime` DATETIME NOT NULL,
                `court_room` VARCHAR(100) NULL,
                `hearing_summary` TEXT NULL,
                `next_deadline_date` DATE NULL,
                `deadline_description` VARCHAR(255) NULL,
                `status` ENUM('scheduled','attended','postponed','completed') NOT NULL DEFAULT 'scheduled',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_matter` (`id_legal_matters`),
                KEY `idx_hearing_time` (`hearing_datetime`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}legal_time_entries` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_legal_matters` INT UNSIGNED NOT NULL,
                `id_users_attorney` INT NOT NULL,
                `work_description` TEXT NOT NULL,
                `duration_minutes` INT NOT NULL DEFAULT 0,
                `hourly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
                `is_invoiced` TINYINT(1) NOT NULL DEFAULT 0,
                `timer_started_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_matter` (`id_legal_matters`),
                KEY `idx_attorney` (`id_users_attorney`),
                KEY `idx_is_billable` (`is_billable`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}legal_expenses` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_legal_matters` INT UNSIGNED NOT NULL,
                `expense_type` VARCHAR(100) NOT NULL DEFAULT 'court_fee',
                `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `description` VARCHAR(255) NULL,
                `receipt_file` VARCHAR(255) NULL,
                `is_reimbursable` TINYINT(1) NOT NULL DEFAULT 1,
                `paid_by` VARCHAR(50) NOT NULL DEFAULT 'firm',
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_matter` (`id_legal_matters`),
                KEY `idx_type` (`expense_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // =====================================================================
        // 2. DANIŞMANLIK & STRATEJİK PROJE YÖNETİMİ SUITE (PROJECTS, MILESTONES)
        // =====================================================================
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}consulting_projects` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `project_code` VARCHAR(50) NOT NULL,
                `id_users_client` INT NOT NULL,
                `id_users_lead_consultant` INT NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `scope` TEXT NULL,
                `total_budget` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `start_date` DATE NULL,
                `target_end_date` DATE NULL,
                `status` ENUM('planning','active','review','completed','paused') NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_project_code` (`project_code`),
                KEY `idx_client` (`id_users_client`),
                KEY `idx_consultant` (`id_users_lead_consultant`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}consulting_milestones` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_projects` INT UNSIGNED NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `deliverable_description` TEXT NULL,
                `due_date` DATE NOT NULL,
                `signoff_status` ENUM('pending','client_approved','revision_requested') NOT NULL DEFAULT 'pending',
                `signoff_at` DATETIME NULL,
                `client_feedback` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_project` (`id_projects`),
                KEY `idx_due_date` (`due_date`),
                KEY `idx_signoff` (`signoff_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}consulting_timesheets` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_projects` INT UNSIGNED NOT NULL,
                `id_milestones` INT UNSIGNED NULL,
                `id_users_consultant` INT NOT NULL,
                `log_date` DATE NOT NULL,
                `hours_spent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
                `work_summary` TEXT NOT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_project` (`id_projects`),
                KEY `idx_milestone` (`id_milestones`),
                KEY `idx_consultant` (`id_users_consultant`),
                KEY `idx_log_date` (`log_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // =====================================================================
        // 3. SAĞLIK, KLİNİK EMR, VİTALS & REÇETE SUITE (PATIENT VITALS, LABS)
        // =====================================================================
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}patient_vitals` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_users_patient` INT NOT NULL,
                `id_appointments` INT NULL,
                `systolic_bp` INT NULL,
                `diastolic_bp` INT NULL,
                `pulse_rate` INT NULL,
                `temperature_c` DECIMAL(4,1) NULL,
                `respiratory_rate` INT NULL,
                `weight_kg` DECIMAL(5,2) NULL,
                `height_cm` DECIMAL(5,2) NULL,
                `bmi` DECIMAL(4,1) NULL,
                `spo2_percent` INT NULL,
                `blood_glucose` DECIMAL(5,1) NULL,
                `recorded_by_user_id` INT NULL,
                `notes` TEXT NULL,
                `recorded_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_patient` (`id_users_patient`),
                KEY `idx_appointment` (`id_appointments`),
                KEY `idx_recorded_at` (`recorded_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}patient_prescriptions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_users_patient` INT NOT NULL,
                `id_appointments` INT NULL,
                `id_users_doctor` INT NOT NULL,
                `medication_name` VARCHAR(191) NOT NULL,
                `dosage` VARCHAR(100) NOT NULL,
                `frequency` VARCHAR(100) NOT NULL,
                `duration_days` INT NOT NULL DEFAULT 7,
                `instructions` TEXT NULL,
                `prescribed_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_patient` (`id_users_patient`),
                KEY `idx_doctor` (`id_users_doctor`),
                KEY `idx_prescribed_at` (`prescribed_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}patient_allergies` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_users_patient` INT NOT NULL,
                `allergen` VARCHAR(191) NOT NULL,
                `severity` ENUM('mild','moderate','severe') NOT NULL DEFAULT 'moderate',
                `reaction_notes` TEXT NULL,
                `identified_at` DATE NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_patient` (`id_users_patient`),
                KEY `idx_allergen` (`allergen`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}clinical_lab_orders` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_users_patient` INT NOT NULL,
                `id_appointments` INT NULL,
                `test_name` VARCHAR(191) NOT NULL,
                `category` VARCHAR(100) NOT NULL DEFAULT 'biochemistry',
                `status` ENUM('requested','sample_collected','in_progress','completed') NOT NULL DEFAULT 'requested',
                `result_summary` TEXT NULL,
                `normal_range` VARCHAR(100) NULL,
                `is_abnormal` TINYINT(1) NOT NULL DEFAULT 0,
                `result_attachment` VARCHAR(255) NULL,
                `completed_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_patient` (`id_users_patient`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // =====================================================================
        // 4. GÜZELLİK & KUAFÖR: BEKLEME SÜRESİ, BAHŞİŞ & GÜZELLİK PROFİLİ
        // =====================================================================
        if ($this->db->table_exists('services')) {
            if (!$this->db->field_exists('lead_in_duration', 'services')) {
                $this->db->query("ALTER TABLE `{$p}services` ADD `lead_in_duration` INT NOT NULL DEFAULT 0 AFTER `duration`");
            }
            if (!$this->db->field_exists('processing_duration', 'services')) {
                $this->db->query("ALTER TABLE `{$p}services` ADD `processing_duration` INT NOT NULL DEFAULT 0 AFTER `lead_in_duration`");
            }
            if (!$this->db->field_exists('lead_out_duration', 'services')) {
                $this->db->query("ALTER TABLE `{$p}services` ADD `lead_out_duration` INT NOT NULL DEFAULT 0 AFTER `processing_duration`");
            }
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}customer_beauty_profiles` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_users_customer` INT NOT NULL,
                `color_formula` TEXT NULL,
                `hair_type` VARCHAR(100) NULL,
                `skin_type` VARCHAR(100) NULL,
                `patch_test_date` DATE NULL,
                `patch_test_result` VARCHAR(50) NULL,
                `nail_notes` TEXT NULL,
                `before_photo_url` VARCHAR(255) NULL,
                `after_photo_url` VARCHAR(255) NULL,
                `private_notes` TEXT NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_customer` (`id_users_customer`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}appointment_tips` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_appointments` INT NULL,
                `id_adisyons` INT NULL,
                `tip_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `payment_method` ENUM('cash','credit_card') NOT NULL DEFAULT 'credit_card',
                `distributed_to_user_id` INT NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_appointment` (`id_appointments`),
                KEY `idx_adisyon` (`id_adisyons`),
                KEY `idx_staff` (`distributed_to_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // =====================================================================
        // 5. OTO YIKAMA & DETAILING: PERON TV KUYRUK & 360 HASAR İŞARETLEME
        // =====================================================================
        if ($this->db->table_exists('customer_vehicles')) {
            if (!$this->db->field_exists('vehicle_segment', 'customer_vehicles')) {
                $this->db->query("ALTER TABLE `{$p}customer_vehicles` ADD `vehicle_segment` VARCHAR(30) NOT NULL DEFAULT 'sedan' AFTER `color`");
            }
        }

        if ($this->db->table_exists('vehicle_inspections')) {
            if (!$this->db->field_exists('scratch_coordinates_json', 'vehicle_inspections')) {
                $this->db->query("ALTER TABLE `{$p}vehicle_inspections` ADD `scratch_coordinates_json` MEDIUMTEXT NULL AFTER `items_json`");
            }
        }

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `{$p}carwash_queue_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_appointments` INT NULL,
                `id_vehicles` INT UNSIGNED NOT NULL,
                `bay_name` VARCHAR(100) NOT NULL DEFAULT 'Peron 1',
                `queue_status` ENUM('waiting','washing','detailing','ready','delivered') NOT NULL DEFAULT 'waiting',
                `notes` VARCHAR(255) NULL,
                `started_at` DATETIME NULL,
                `estimated_ready_at` DATETIME NULL,
                `completed_at` DATETIME NULL,
                `notified_customer_at` DATETIME NULL,
                `created_at` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_vehicle` (`id_vehicles`),
                KEY `idx_appointment` (`id_appointments`),
                KEY `idx_queue_status` (`queue_status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down(): void
    {
        $p = $this->db->dbprefix;
        $this->db->query("DROP TABLE IF EXISTS `{$p}carwash_queue_logs`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}appointment_tips`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}customer_beauty_profiles`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}clinical_lab_orders`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}patient_allergies`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}patient_prescriptions`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}patient_vitals`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}consulting_timesheets`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}consulting_milestones`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}consulting_projects`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}legal_expenses`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}legal_time_entries`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}legal_hearings`");
        $this->db->query("DROP TABLE IF EXISTS `{$p}legal_matters`");
    }
}
