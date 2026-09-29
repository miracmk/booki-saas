<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 174: Education & Workshop Attendance and Student Evaluations Suite
 */
class Migration_education_attendance_and_grades_suite extends CI_Migration
{
    public function up(): void
    {
        // 1. Course Attendance Table
        if (!$this->db->table_exists('course_attendance')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `ea_course_attendance` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_appointments` INT UNSIGNED NOT NULL,
                    `id_users_customer` INT UNSIGNED NOT NULL,
                    `status` VARCHAR(32) NOT NULL DEFAULT 'present',
                    `notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_attendance_session` (`id_appointments`),
                    KEY `idx_attendance_student` (`id_users_customer`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }

        // 2. Student Evaluations Table
        if (!$this->db->table_exists('student_evaluations')) {
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `ea_student_evaluations` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `id_users_customer` INT UNSIGNED NOT NULL,
                    `id_appointments` INT UNSIGNED NULL,
                    `subject` VARCHAR(255) NOT NULL,
                    `grade_score` DECIMAL(5,2) NULL,
                    `feedback_notes` TEXT NULL,
                    `created_at` DATETIME NOT NULL,
                    `updated_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_evaluation_student` (`id_users_customer`),
                    KEY `idx_evaluation_session` (`id_appointments`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
    }

    public function down(): void
    {
        if ($this->db->table_exists('student_evaluations')) {
            $this->db->query("DROP TABLE IF EXISTS `ea_student_evaluations`;");
        }
        if ($this->db->table_exists('course_attendance')) {
            $this->db->query("DROP TABLE IF EXISTS `ea_course_attendance`;");
        }
    }
}
