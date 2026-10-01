<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Model: Bk_marketplace_customer_model
 * 
 * Manages standalone customer accounts on RandevuBurada:
 * Single login/profile across all platform businesses.
 */
class Bk_marketplace_customer_model extends CI_Model
{
    private string $table = 'bk_marketplace_customers';

    public function __construct()
    {
        $this->load->database();
    }

    public function get_by_id(int $id): ?array
    {
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `id` = ? LIMIT 1", [$id])->row_array();
        return $res ?: null;
    }

    public function get_by_phone(string $phone): ?array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `phone` = ? LIMIT 1", [$cleanPhone])->row_array();
        return $res ?: null;
    }

    public function get_by_email(string $email): ?array
    {
        $res = $this->db->query("SELECT * FROM `{$this->table}` WHERE `email` = ? LIMIT 1", [trim(strtolower($email))])->row_array();
        return $res ?: null;
    }

    /**
     * Register or find existing customer by phone.
     */
    public function find_or_create(string $fullName, string $phone, ?string $email = null): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $existing = $this->get_by_phone($cleanPhone);

        if ($existing) {
            if (!empty($fullName) && $existing['full_name'] !== $fullName) {
                $this->db->query("UPDATE `{$this->table}` SET `full_name` = ?, `updated_at` = NOW() WHERE `id` = ?", [$fullName, $existing['id']]);
                $existing['full_name'] = $fullName;
            }
            return $existing;
        }

        $now = date('Y-m-d H:i:s');
        $this->db->query("
            INSERT INTO `{$this->table}` (`full_name`, `phone`, `email`, `is_verified`, `status`, `created_at`, `updated_at`)
            VALUES (?, ?, ?, 1, 'active', ?, ?)
        ", [$fullName, $cleanPhone, $email, $now, $now]);

        $id = (int) $this->db->insert_id();
        return $this->get_by_id($id);
    }

    /**
     * Get appointments for customer across all tenants.
     */
    public function get_customer_appointments(int $customerId, string $phone): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        return $this->db->query("
            SELECT s.*, t.company_name, t.subdomain, t.city, t.district
            FROM `bk_escrow_settlements` s
            LEFT JOIN `bk_tenants` t ON t.id = s.id_tenants
            WHERE s.customer_id = ? OR s.customer_phone = ?
            ORDER BY s.created_at DESC
            LIMIT 50
        ", [$customerId, $cleanPhone])->result_array();
    }
}
