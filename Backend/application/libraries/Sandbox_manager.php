<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi SaaS - Sandbox Manager for Live Demo Isolation
 *
 * @package     KiReservation
 * @author      Ki Software
 * @copyright   Copyright (c) Ki Software
 * @license     Proprietary - see LICENSE file
 * ---------------------------------------------------------------------------- */

class Sandbox_manager
{
    protected CI_Controller $CI;
    protected ?mysqli $master_mysqli = null;

    public const DEMO_SECTORS = [
        'guzellik-bookiapp' => 'guzellik',
        'demo-guzellik'     => 'guzellik',
        'guzellik'          => 'guzellik',

        'masaj-bookiapp'    => 'masaj',
        'demo-masaj'        => 'masaj',
        'masaj'             => 'masaj',

        'restorant-bookiapp'=> 'restoran',
        'restoran-bookiapp' => 'restoran',
        'demo-restoran'     => 'restoran',
        'demo-restorant'    => 'restoran',
        'restorant'         => 'restoran',
        'restoran'          => 'restoran',

        'otel-bookiapp'     => 'otel',
        'demo-otel'         => 'otel',
        'otel'              => 'otel',

        'klinik-bookiapp'   => 'klinik',
        'demo-klinik'       => 'klinik',
        'klinik'            => 'klinik',

        'studyo-bookiapp'   => 'studyo',
        'demo-studyo'       => 'studyo',
        'studyo'            => 'studyo',
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Check if a given subdomain is a designated demo tenant.
     */
    public function is_demo_tenant(string $subdomain): bool
    {
        return array_key_exists(strtolower(trim($subdomain)), self::DEMO_SECTORS);
    }

    /**
     * Get sector key for a demo subdomain.
     */
    public function get_sector(string $subdomain): ?string
    {
        $subdomain = strtolower(trim($subdomain));
        return self::DEMO_SECTORS[$subdomain] ?? null;
    }

    /**
     * Get direct MySQLi connection to master database server.
     */
    protected function get_mysqli(): mysqli
    {
        if ($this->master_mysqli === null || !$this->master_mysqli->ping()) {
            $db_host = getenv('DB_HOST') ?: 'db';
            $db_user = getenv('DB_USERNAME') ?: 'ki_reservation_master';
            $db_pass = getenv('DB_PASSWORD') ?: '';
            $db_name = getenv('DB_NAME') ?: 'ki_reservation_master';

            $this->master_mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if ($this->master_mysqli->connect_error) {
                log_message('error', 'Sandbox_manager mysqli connection failed: ' . $this->master_mysqli->connect_error);
                throw new RuntimeException('Master DB connection failed in Sandbox Manager');
            }
            $this->master_mysqli->set_charset('utf8mb4');
        }
        return $this->master_mysqli;
    }

    /**
     * Retrieve active sandbox database for current request/session if valid for today.
     */
    public function get_active_sandbox(string $subdomain): ?string
    {
        if (!$this->is_demo_tenant($subdomain)) {
            return null;
        }

        $session_sandbox = $this->CI->session->userdata('sandbox_db');
        $session_tenant = $this->CI->session->userdata('sandbox_tenant');
        $session_expires = $this->CI->session->userdata('sandbox_expires');

        if (!empty($session_sandbox) && $session_tenant === $subdomain) {
            if (empty($session_expires) || strtotime($session_expires) >= time()) {
                return $session_sandbox;
            }
        }

        // Check persistent cookie if session expired or fresh browser session
        $cookie_token = $this->CI->input->cookie('booki_sandbox_token');
        if (!empty($cookie_token)) {
            $mysqli = $this->get_mysqli();
            $stmt = $mysqli->prepare(
                "SELECT db_name, expires_at FROM ea_sandbox_slots 
                 WHERE tenant_subdomain = ? AND claimed_by_session = ? AND status = 'active' AND expires_at >= NOW() LIMIT 1"
            );
            $stmt->bind_param('ss', $subdomain, $cookie_token);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $this->CI->session->set_userdata([
                    'sandbox_db' => $row['db_name'],
                    'sandbox_tenant' => $subdomain,
                    'sandbox_expires' => $row['expires_at'],
                ]);
                return $row['db_name'];
            }
        }

        return null;
    }

    /**
     * Acquire or allocate an isolated sandbox slot for the current user login.
     * Guaranteed to return a valid sandbox database name for the tenant.
     */
    public function acquire_sandbox(string $subdomain, ?string $session_id = null): string
    {
        if (!$this->is_demo_tenant($subdomain)) {
            return 'ki_tenant_' . $subdomain;
        }

        $mysqli = $this->get_mysqli();

        if (empty($session_id)) {
            $session_id = $this->CI->session->session_id ?? md5(uniqid((string) mt_rand(), true));
        }

        // Cookie token identifier for same-day persistence
        $cookie_token = $this->CI->input->cookie('booki_sandbox_token');
        if (empty($cookie_token)) {
            $cookie_token = 'sb_' . substr(hash('sha256', $session_id . '_' . ($_SERVER['REMOTE_ADDR'] ?? '') . '_' . date('Y-m-d')), 0, 32);
            $expires_timestamp = strtotime('tomorrow 03:00:00'); // Valid until 03:00 tomorrow
            $this->CI->input->set_cookie([
                'name'   => 'booki_sandbox_token',
                'value'  => $cookie_token,
                'expire' => $expires_timestamp - time(),
                'path'   => '/',
                'secure' => false,
                'httponly' => true,
            ]);
        }

        // 1. Check if this session already has an active slot today
        $stmt = $mysqli->prepare(
            "SELECT id, db_name, expires_at FROM ea_sandbox_slots 
             WHERE tenant_subdomain = ? AND (claimed_by_session = ? OR claimed_by_session = ?) AND status = 'active' AND expires_at >= NOW() LIMIT 1"
        );
        $stmt->bind_param('sss', $subdomain, $session_id, $cookie_token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($existing = $res->fetch_assoc()) {
            $this->CI->session->set_userdata([
                'sandbox_db' => $existing['db_name'],
                'sandbox_tenant' => $subdomain,
                'sandbox_expires' => $existing['expires_at'],
                'sandbox_slot_id' => $existing['id'],
            ]);
            return $existing['db_name'];
        }

        $today_end = date('Y-m-d 23:59:59');

        // 2. Look for an available pre-created slot
        $stmt = $mysqli->prepare(
            "SELECT id, slot_number, db_name FROM ea_sandbox_slots 
             WHERE tenant_subdomain = ? AND status = 'available' 
             ORDER BY slot_number ASC LIMIT 1 FOR UPDATE"
        );
        $stmt->bind_param('s', $subdomain);
        $stmt->execute();
        $res = $stmt->get_result();
        $slot = $res->fetch_assoc();

        // 3. If no available slot, check for expired slots that can be reset & claimed
        if (!$slot) {
            $stmt = $mysqli->prepare(
                "SELECT id, slot_number, db_name FROM ea_sandbox_slots 
                 WHERE tenant_subdomain = ? AND (status = 'expired' OR expires_at < NOW()) 
                 ORDER BY expires_at ASC LIMIT 1 FOR UPDATE"
            );
            $stmt->bind_param('s', $subdomain);
            $stmt->execute();
            $res = $stmt->get_result();
            $slot = $res->fetch_assoc();

            if ($slot) {
                // Reset dirty/expired DB from template
                $this->reset_slot_db($subdomain, $slot['db_name']);
            }
        }

        // 4. If still no slot (all 20+ currently in use), dynamically allocate new slot
        if (!$slot) {
            $stmt = $mysqli->prepare("SELECT MAX(slot_number) as max_slot FROM ea_sandbox_slots WHERE tenant_subdomain = ?");
            $stmt->bind_param('s', $subdomain);
            $stmt->execute();
            $max_row = $stmt->get_result()->fetch_assoc();
            $new_slot_num = ((int) ($max_row['max_slot'] ?? 0)) + 1;
            $new_db_name = 'ki_tenant_' . $subdomain . '_sb' . $new_slot_num;

            // Provision database
            $this->create_slot_db($subdomain, $new_db_name);

            $stmt_ins = $mysqli->prepare(
                "INSERT INTO ea_sandbox_slots (tenant_subdomain, slot_number, db_name, claimed_by_session, claimed_at, expires_at, status) 
                 VALUES (?, ?, ?, ?, NOW(), ?, 'active')"
            );
            $stmt_ins->bind_param('sisss', $subdomain, $new_slot_num, $new_db_name, $cookie_token, $today_end);
            $stmt_ins->execute();
            $new_id = $mysqli->insert_id;

            $slot = [
                'id' => $new_id,
                'slot_number' => $new_slot_num,
                'db_name' => $new_db_name,
            ];
        } else {
            // Update existing slot to active
            $stmt_upd = $mysqli->prepare(
                "UPDATE ea_sandbox_slots 
                 SET claimed_by_session = ?, claimed_at = NOW(), expires_at = ?, status = 'active' 
                 WHERE id = ?"
            );
            $stmt_upd->bind_param('ssi', $cookie_token, $today_end, $slot['id']);
            $stmt_upd->execute();
        }

        $this->CI->session->set_userdata([
            'sandbox_db' => $slot['db_name'],
            'sandbox_tenant' => $subdomain,
            'sandbox_expires' => $today_end,
            'sandbox_slot_id' => $slot['id'],
        ]);

        return $slot['db_name'];
    }

    /**
     * Create and populate a new sandbox slot database from template.
     */
    public function create_slot_db(string $subdomain, string $target_db): bool
    {
        $mysqli = $this->get_mysqli();
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$target_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        return $this->reset_slot_db($subdomain, $target_db);
    }

    /**
     * Reset a slot database from template .sql dump using native PHP mysqli.
     */
    public function reset_slot_db(string $subdomain, string $target_db): bool
    {
        $sector = $this->get_sector($subdomain) ?? 'guzellik';
        $candidates = [
            $subdomain,
            'demo-' . $subdomain,
            'demo-' . $sector,
            $sector . '-bookiapp',
            $sector,
        ];
        $template_file = null;
        foreach ($candidates as $cand) {
            $p1 = '/var/www/html/storage/sandbox_templates/' . $cand . '.sql';
            $p2 = '/opt/ki-ecosystem/booki/files/sandbox_templates/' . $cand . '.sql';
            if (file_exists($p1) && filesize($p1) > 0) {
                $template_file = $p1;
                break;
            }
            if (file_exists($p2) && filesize($p2) > 0) {
                $template_file = $p2;
                break;
            }
        }

        if (!$template_file || !file_exists($template_file)) {
            log_message('error', "Sandbox template file not found for subdomain: {$subdomain}");
            return false;
        }

        $sql = file_get_contents($template_file);
        if (empty($sql)) {
            log_message('error', "Sandbox template file is empty: {$template_file}");
            return false;
        }

        $db_host = getenv('DB_HOST') ?: 'db';
        $db_user = getenv('DB_USERNAME') ?: 'ki_reservation_master';
        $db_pass = getenv('DB_PASSWORD') ?: '';

        $conn = new mysqli($db_host, $db_user, $db_pass);
        if ($conn->connect_error) {
            log_message('error', "reset_slot_db connection error: {$conn->connect_error}");
            return false;
        }

        $conn->query("CREATE DATABASE IF NOT EXISTS `{$target_db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        if (!$conn->select_db($target_db)) {
            log_message('error', "Failed selecting target sandbox db: {$target_db}");
            $conn->close();
            return false;
        }

        // Disable foreign keys and auto-commit during mass import for maximum speed
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("SET UNIQUE_CHECKS = 0");

        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
        } else {
            log_message('error', "multi_query error on {$target_db}: " . $conn->error);
            $conn->close();
            return false;
        }

        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->query("SET UNIQUE_CHECKS = 1");
        $conn->close();

        return true;
    }

    /**
     * Clean up expired sandboxes and return count of recycled slots.
     */
    public function cleanup_expired(): int
    {
        $mysqli = $this->get_mysqli();
        $res = $mysqli->query("SELECT id, tenant_subdomain, db_name FROM ea_sandbox_slots WHERE expires_at < NOW() AND status != 'available'");
        $count = 0;

        while ($row = $res->fetch_assoc()) {
            $this->reset_slot_db($row['tenant_subdomain'], $row['db_name']);
            $mysqli->query("UPDATE ea_sandbox_slots SET status = 'available', claimed_by_session = NULL, claimed_at = NULL, expires_at = NULL WHERE id = " . (int) $row['id']);
            $count++;
        }

        return $count;
    }

    /**
     * Reset ALL sandbox slots across all demo tenants (Cache purge / Admin reset).
     */
    public function reset_all(): int
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $mysqli = $this->get_mysqli();
        $res = $mysqli->query("SELECT id, tenant_subdomain, db_name FROM ea_sandbox_slots");
        $count = 0;

        while ($row = $res->fetch_assoc()) {
            $this->reset_slot_db($row['tenant_subdomain'], $row['db_name']);
            $mysqli->query("UPDATE ea_sandbox_slots SET status = 'available', claimed_by_session = NULL, claimed_at = NULL, expires_at = NULL WHERE id = " . (int) $row['id']);
            $count++;
        }

        return $count;
    }

    /**
     * Pre-provision slots for a tenant (e.g. 5 or 20 slots).
     */
    public function pre_provision_slots(string $subdomain, int $count = 5): int
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        if (!$this->is_demo_tenant($subdomain)) {
            return 0;
        }

        $mysqli = $this->get_mysqli();
        $created = 0;

        for ($i = 1; $i <= $count; $i++) {
            $db_name = 'ki_tenant_' . $subdomain . '_sb' . $i;

            // Check if slot record exists
            $check = $mysqli->query("SELECT id FROM ea_sandbox_slots WHERE tenant_subdomain = '{$subdomain}' AND slot_number = {$i}");
            if ($check->num_rows === 0) {
                $this->create_slot_db($subdomain, $db_name);
                $mysqli->query("INSERT INTO ea_sandbox_slots (tenant_subdomain, slot_number, db_name, status) VALUES ('{$subdomain}', {$i}, '{$db_name}', 'available')");
                $created++;
            }
        }

        return $created;
    }
}
