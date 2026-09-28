<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * BooKi Role-Based Demo Service.
 *
 * Allows instant role switching in demo / evaluation mode:
 * Restaurant: Owner / Manager / Waiter / Cashier / Kitchen
 * Clinic: Owner / Doctor / Nurse / Reception / Cashier
 * Beauty: Owner / Reception / Professional
 * Spa: Owner / Reception / Therapist
 * Fitness: Owner / Reception / Trainer
 *
 * Truly alters session role, permissions, data visibility, sidebar, and dashboard.
 */
class Demo_service
{
    protected CI_Controller $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->library('vertical_service');
        $this->CI->load->model('users_model');
        $this->CI->load->model('roles_model');
    }

    /**
     * Get list of available demo roles for the active or given industry.
     */
    public function get_available_demo_roles(?string $code = null): array
    {
        $bp = $this->CI->vertical_service->get_blueprint($code);
        if ($bp && !empty($bp['demo_roles'])) {
            return $bp['demo_roles'];
        }

        return [
            ['slug' => 'owner', 'name' => 'İşletme Sahibi (Owner)', 'icon' => 'crown', 'description' => 'Tam yetkili'],
            ['slug' => 'manager', 'name' => 'Müdür', 'icon' => 'user-tie', 'description' => 'İşletme yöneticisi'],
            ['slug' => 'reception', 'name' => 'Resepsiyon', 'icon' => 'concierge-bell', 'description' => 'Danışma'],
        ];
    }

    /**
     * Switch current session to a specific demo role.
     *
     * @param string $role_slug Role slug (e.g. owner, waiter, kitchen, doctor, etc.)
     * @return array Result with user info and redirect destination
     */
    public function switch_role(string $role_slug): array
    {
        $business_type = $this->CI->vertical_service->current_business_type();
        $bp = $this->CI->vertical_service->get_blueprint($business_type);

        $demo_users = $bp['demo_users'] ?? [];
        $profile = $demo_users[$role_slug] ?? [
            'first_name' => ucfirst($role_slug),
            'last_name' => 'Demo',
            'email' => "demo-{$business_type}-{$role_slug}@kibusiness.co",
            'role_slug' => $role_slug,
            'job_title' => ucfirst($role_slug),
        ];

        // Locate or create the role in roles table
        $role_row = $this->CI->db->get_where('roles', ['slug' => $role_slug])->row_array();
        if (!$role_row) {
            // Fallback to provider or secretary role ID
            $role_id = match ($role_slug) {
                'owner', 'general_manager', 'clinic_manager' => 1,
                'reception', 'cashier' => 4,
                default => 2,
            };
            $is_admin = ($role_id === 1) ? 1 : 0;
        } else {
            $role_id = (int) $role_row['id'];
            $is_admin = (int) $role_row['is_admin'];
        }

        // Locate or create user in users table
        $email = $profile['email'];
        $email_hash = function_exists('sf_pii_hash') ? sf_pii_hash($email) : hash('sha256', $email);

        $user = $this->CI->db->where('email_hash', $email_hash)->get('users')->row_array();
        if (!$user) {
            $user_payload = [
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'email' => $email,
                'email_hash' => $email_hash,
                'phone_number' => '05432137000',
                'phone_number_hash' => function_exists('sf_pii_hash') ? sf_pii_hash('05432137000') : hash('sha256', '05432137000'),
                'id_roles' => $role_id,
                'role_slug' => $role_slug,
                'job_title' => $profile['job_title'] ?? $profile['first_name'],
                'is_active' => 1,
                'create_datetime' => date('Y-m-d H:i:s'),
                'update_datetime' => date('Y-m-d H:i:s'),
            ];
            $this->CI->db->insert('users', $user_payload);
            $user_id = $this->CI->db->insert_id();
        } else {
            $user_id = (int) $user['id'];
            $this->CI->db->update('users', [
                'role_slug' => $role_slug,
                'job_title' => $profile['job_title'] ?? $profile['first_name'],
                'id_roles' => $role_id,
                'is_active' => 1,
                'update_datetime' => date('Y-m-d H:i:s'),
            ], ['id' => $user_id]);
        }

        // Ensure user credentials in user_settings match the {demoismi}-{rol} and {demoismi}.BooKi standard
        $demoismi = match ($business_type) {
            'restaurant' => 'restorant',
            'beauty_salon' => 'guzellik',
            'massage_spa' => 'masaj',
            'doctor_clinic' => 'klinik',
            'hotel' => 'otel',
            'pilates_studio' => 'studyo',
            default => $business_type,
        };
        $demo_username = "{$demoismi}-{$role_slug}";
        $demo_pwd_hash = password_hash("{$demoismi}.BooKi", PASSWORD_BCRYPT, ['cost' => 12]);

        $us_row = $this->CI->db->get_where('user_settings', ['id_users' => $user_id])->row_array();
        if ($us_row) {
            $this->CI->db->update('user_settings', [
                'username' => $demo_username,
                'password' => $demo_pwd_hash,
            ], ['id_users' => $user_id]);
        } else {
            $this->CI->db->insert('user_settings', [
                'id_users' => $user_id,
                'username' => $demo_username,
                'password' => $demo_pwd_hash,
            ]);
        }

        // Establish full session state
        $display_name = trim($profile['first_name'] . ' ' . $profile['last_name']) . ' (' . ($profile['job_title'] ?? $role_slug) . ')';
        session([
            'user_id' => $user_id,
            'user_email' => $email,
            'user_display_name' => $display_name,
            'role_slug' => $role_slug,
            'job_title' => $profile['job_title'] ?? $role_slug,
            'is_admin' => $is_admin,
            'dest_url' => site_url('dashboard'),
        ]);

        if (function_exists('audit_log')) {
            audit_log('demo.switch_role', 'user', $user_id, [
                'role' => $role_slug,
                'job_title' => $profile['job_title'] ?? $role_slug,
            ]);
        }

        return [
            'success' => true,
            'user_id' => $user_id,
            'role_slug' => $role_slug,
            'job_title' => $profile['job_title'] ?? $role_slug,
            'display_name' => $display_name,
            'redirect' => site_url('dashboard'),
            'user' => [
                'id' => $user_id,
                'role_slug' => $role_slug,
                'job_title' => $profile['job_title'] ?? $role_slug,
            ],
        ];
    }
}
