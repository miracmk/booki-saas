<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * BooKi - Customer Onboarding Sessions & Lifecycle Model
 *
 * Manages secure customer onboarding sessions, 10-step progress tracking,
 * customer data provisioning into tenant databases, and final activation.
 * -------------------------------------------------------------------------- */

class Onboarding_sessions_model extends CI_Model
{
    public const TOTAL_STEPS = 10;

    public function __construct()
    {
        $this->load->helper('tenant');
    }

    /**
     * Create or retrieve an active onboarding session for a tenant.
     */
    public function create_session(int $tenant_id, ?int $lead_id = null, int $expiry_days = 30): array
    {
        $tenant = $this->db->get_where('tenants', ['id' => $tenant_id])->row_array();
        if (!$tenant) {
            throw new InvalidArgumentException("Tenant #{$tenant_id} bulunamadı.");
        }

        // Check if an existing session exists
        $existing = $this->db
            ->where('id_tenants', $tenant_id)
            ->where('status !=', 'completed')
            ->order_by('created_at', 'desc')
            ->limit(1)
            ->get('onboarding_sessions')
            ->row_array();

        if ($existing && strtotime($existing['expires_at']) > time()) {
            $existing['link'] = $this->generate_onboarding_url($existing['token']);
            return $existing;
        }

        $token = bin2hex(random_bytes(24));
        $now = date('Y-m-d H:i:s');
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));

        $session_data = [
            'id_tenants' => $tenant_id,
            'id_leads' => $lead_id ?: ($tenant['id_leads'] ?? null),
            'token' => $token,
            'status' => 'not_opened',
            'current_step' => 1,
            'total_steps' => self::TOTAL_STEPS,
            'progress_percent' => 0,
            'session_data_json' => json_encode([
                'business_name' => $tenant['company_name'] ?: $tenant['subdomain'],
                'phone' => $tenant['phone_number'] ?? '',
                'address' => $tenant['address'] ?? '',
                'sector' => $tenant['business_type'] ?? '',
            ], JSON_UNESCAPED_UNICODE),
            'expires_at' => $expires_at,
            'created_at' => $now,
        ];

        $this->db->insert('onboarding_sessions', $session_data);
        $session_id = $this->db->insert_id();

        // Update tenant
        $this->db->where('id', $tenant_id)->update('tenants', [
            'onboarding_token' => $token,
            'onboarding_status' => 'pending',
            'updated_at' => $now,
        ]);

        $session_data['id'] = $session_id;
        $session_data['link'] = $this->generate_onboarding_url($token);

        return $session_data;
    }

    /**
     * Get session by secure token, marking opened on first access.
     */
    public function get_session_by_token(string $token): ?array
    {
        $session = $this->db->get_where('onboarding_sessions', ['token' => $token])->row_array();
        if (!$session) {
            return null;
        }

        // Check expiration
        if (strtotime($session['expires_at']) < time() && $session['status'] !== 'completed') {
            $session['is_expired'] = true;
            return $session;
        }
        $session['is_expired'] = false;

        // If not opened yet, mark opened
        if ($session['status'] === 'not_opened') {
            $now = date('Y-m-d H:i:s');
            $this->db->where('id', $session['id'])->update('onboarding_sessions', [
                'status' => 'opened',
                'first_opened_at' => $now,
                'last_activity_at' => $now,
            ]);
            $session['status'] = 'opened';
            $session['first_opened_at'] = $now;

            // Log activity to lead if linked
            if (!empty($session['id_leads'])) {
                $this->db->insert('lead_activities', [
                    'id_leads' => $session['id_leads'],
                    'activity_type' => 'onboarding',
                    'title' => 'Onboarding Linki Müşteri Tarafından Açıldı',
                    'description' => 'Müşteri onboarding kurulum sihirbazı linkine giriş yaptı.',
                    'performed_by' => 'Müşteri',
                    'created_at' => $now,
                ]);
            }
        }

        $session['tenant'] = $this->db->get_where('tenants', ['id' => $session['id_tenants']])->row_array();
        $session['lead'] = !empty($session['id_leads']) ? $this->db->get_where('leads', ['id' => $session['id_leads']])->row_array() : null;
        $session['data'] = !empty($session['session_data_json']) ? json_decode($session['session_data_json'], true) : [];
        $session['link'] = $this->generate_onboarding_url($token);

        return $session;
    }

    /**
     * Save draft data for a given onboarding step.
     */
    public function save_step(string $token, int $step, array $step_data): array
    {
        $session = $this->get_session_by_token($token);
        if (!$session || $session['is_expired']) {
            throw new InvalidArgumentException('Geçersiz veya süresi dolmuş onboarding oturumu.');
        }

        $current_data = $session['data'] ?: [];
        $current_data["step_{$step}"] = $step_data;

        $next_step = min(self::TOTAL_STEPS, max($session['current_step'], $step + 1));
        $progress = (int) round(($step / self::TOTAL_STEPS) * 100);

        $now = date('Y-m-d H:i:s');
        $updates = [
            'status' => ($step >= self::TOTAL_STEPS) ? $session['status'] : 'in_progress',
            'current_step' => $next_step,
            'progress_percent' => max($session['progress_percent'], $progress),
            'session_data_json' => json_encode($current_data, JSON_UNESCAPED_UNICODE),
            'last_activity_at' => $now,
        ];

        $this->db->where('id', $session['id'])->update('onboarding_sessions', $updates);

        return [
            'success' => true,
            'current_step' => $next_step,
            'progress_percent' => $updates['progress_percent'],
        ];
    }

    /**
     * Complete the onboarding session and provision data into the tenant database.
     */
    public function complete_onboarding(string $token): array
    {
        $session = $this->get_session_by_token($token);
        if (!$session || $session['is_expired']) {
            throw new InvalidArgumentException('Geçersiz veya süresi dolmuş onboarding oturumu.');
        }

        $now = date('Y-m-d H:i:s');
        $tenant = $session['tenant'];
        $data = $session['data'];

        // 1. Connect to tenant database
        $tenant_db_config = [
            'hostname' => $tenant['db_host'],
            'username' => $tenant['db_username'],
            'password' => tenant_master_decrypt($tenant['db_password']),
            'database' => $tenant['db_name'],
            'dbdriver' => 'mysqli',
            'dbprefix' => 'ea_',
            'pconnect' => false,
            'db_debug' => true,
            'cache_on' => false,
            'char_set' => 'utf8mb4',
            'dbcollat' => 'utf8mb4_unicode_ci',
        ];

        $tenant_db = $this->load->database($tenant_db_config, true);

        // 2. Provision collected data into tenant DB
        $this->apply_onboarding_data_to_tenant_db($tenant_db, $tenant, $data);

        // 3. Update master DB onboarding session
        $this->db->where('id', $session['id'])->update('onboarding_sessions', [
            'status' => 'completed',
            'current_step' => self::TOTAL_STEPS,
            'progress_percent' => 100,
            'completed_at' => $now,
            'last_activity_at' => $now,
        ]);

        // 4. Update master DB tenant status
        $this->db->where('id', $tenant['id'])->update('tenants', [
            'status' => 'active',
            'onboarding_status' => 'completed',
            'onboarding_completed_at' => $now,
            'updated_at' => $now,
        ]);

        // 5. Update lead if connected
        if (!empty($session['id_leads'])) {
            $this->db->where('id', $session['id_leads'])->update('leads', [
                'stage' => 'Won',
                'trial_status' => 'converted',
                'converted_tenant_id' => $tenant['id'],
                'conversion_date' => $now,
                'updated_at' => $now,
            ]);

            $this->db->insert('lead_activities', [
                'id_leads' => $session['id_leads'],
                'activity_type' => 'onboarding',
                'title' => '🎉 Müşteri Onboarding Kurulumu Tamamlandı',
                'description' => "İşletme tüm adımları tamamladı ve hesabı aktif edildi. Kiracı ID: #{$tenant['id']}",
                'performed_by' => 'Müşteri',
                'created_at' => $now,
            ]);
        }

        // 6. Master Audit Log
        $this->db->insert('master_audit_logs', [
            'actor_username' => 'Müşteri Onboarding',
            'action' => 'onboarding_completed',
            'entity_type' => 'tenant',
            'entity_id' => (string) $tenant['id'],
            'description' => "Kiracı {$tenant['subdomain']} kurulum adımlarını tamamladı ve aktif hale getirildi.",
            'ip_address' => $this->input->ip_address(),
            'metadata_json' => json_encode(['token' => $token, 'tenant_id' => $tenant['id']]),
            'created_at' => $now,
        ]);

        return [
            'success' => true,
            'message' => 'Onboarding başarıyla tamamlandı! Hesabınız aktif edildi.',
            'subdomain' => $tenant['subdomain'],
            'portal_url' => "https://{$tenant['subdomain']}.bookiapp.kibusiness.co/backend",
        ];
    }

    /**
     * Write onboarding wizard data into tenant database.
     */
    private function apply_onboarding_data_to_tenant_db($tenant_db, array $tenant, array $data): void
    {
        $now = date('Y-m-d H:i:s');

        // Step 1: Business Information
        $step1 = $data['step_1'] ?? [];
        if (!empty($step1['company_name'])) {
            $this->upsert_tenant_setting($tenant_db, 'company_name', $step1['company_name']);
        }
        if (!empty($step1['company_email'])) {
            $this->upsert_tenant_setting($tenant_db, 'company_email', $step1['company_email']);
        }
        if (!empty($step1['company_link'])) {
            $this->upsert_tenant_setting($tenant_db, 'company_link', $step1['company_link']);
        }

        // Step 2: Business Hours (Working Plan)
        $step2 = $data['step_2'] ?? [];
        if (!empty($step2['business_hours'])) {
            $this->upsert_tenant_setting($tenant_db, 'company_working_plan', json_encode($step2['business_hours']));
        }

        // Step 3: Services
        $step3 = $data['step_3'] ?? [];
        if (!empty($step3['services']) && is_array($step3['services'])) {
            foreach ($step3['services'] as $srv) {
                if (empty($srv['name'])) continue;
                $tenant_db->insert('services', [
                    'name' => trim($srv['name']),
                    'duration' => (int) ($srv['duration'] ?? 30),
                    'price' => (float) ($srv['price'] ?? 0.00),
                    'currency' => 'TRY',
                    'description' => trim($srv['description'] ?? ''),
                    'availabilities_type' => 'flexible',
                    'attendants_number' => 1,
                ]);
            }
        }

        // Step 4: Employees / Providers
        $step4 = $data['step_4'] ?? [];
        if (!empty($step4['employees']) && is_array($step4['employees'])) {
            $provider_role = $tenant_db->get_where('roles', ['slug' => DB_SLUG_PROVIDER])->row_array();
            $provider_role_id = $provider_role['id'] ?? 2;

            foreach ($step4['employees'] as $emp) {
                if (empty($emp['first_name'])) continue;
                $tenant_db->insert('users', [
                    'first_name' => trim($emp['first_name']),
                    'last_name' => trim($emp['last_name'] ?? ''),
                    'email' => trim($emp['email'] ?? 'calisan' . random_int(100, 999) . '@booki.internal'),
                    'mobile_number' => trim($emp['phone'] ?? ''),
                    'phone_number' => trim($emp['phone'] ?? ''),
                    'id_roles' => $provider_role_id,
                ]);
                $user_id = $tenant_db->insert_id();

                $tenant_db->insert('user_settings', [
                    'id_users' => $user_id,
                    'username' => 'user_' . $user_id . '_' . random_int(100, 999),
                    'password' => hash_password(generate_salt(), bin2hex(random_bytes(6))),
                    'salt' => generate_salt(),
                    'working_plan' => $step2['business_hours'] ? json_encode($step2['business_hours']) : null,
                    'notifications' => 1,
                ]);
            }
        }

        // Step 5: Rooms / Stations / Cabins / Resources
        $step5 = $data['step_5'] ?? [];
        if (!empty($step5['resources']) && is_array($step5['resources'])) {
            foreach ($step5['resources'] as $res) {
                if (empty($res['name'])) continue;
                if ($tenant_db->table_exists('stations')) {
                    $tenant_db->insert('stations', [
                        'name' => trim($res['name']),
                        'description' => trim($res['type'] ?? 'Genel Kaynak'),
                        'capacity' => (int) ($res['capacity'] ?? 1),
                        'status' => 'active',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // Step 7: Customers Import
        $step7 = $data['step_7'] ?? [];
        if (!empty($step7['customers']) && is_array($step7['customers'])) {
            $customer_role = $tenant_db->get_where('roles', ['slug' => DB_SLUG_CUSTOMER])->row_array();
            $customer_role_id = $customer_role['id'] ?? 3;

            foreach ($step7['customers'] as $cust) {
                if (empty($cust['first_name'])) continue;
                $tenant_db->insert('users', [
                    'first_name' => trim($cust['first_name']),
                    'last_name' => trim($cust['last_name'] ?? ''),
                    'email' => trim($cust['email'] ?? 'musteri' . random_int(1000, 9999) . '@booki.internal'),
                    'phone_number' => trim($cust['phone'] ?? ''),
                    'mobile_number' => trim($cust['phone'] ?? ''),
                    'notes' => trim($cust['notes'] ?? ''),
                    'id_roles' => $customer_role_id,
                ]);
            }
        }

        // Mark onboarding completed in tenant settings
        $this->upsert_tenant_setting($tenant_db, 'onboarding_completed', '1');
        $this->upsert_tenant_setting($tenant_db, 'onboarding_completed_at', $now);
    }

    private function upsert_tenant_setting($tenant_db, string $name, string $value): void
    {
        if ($tenant_db->get_where('settings', ['name' => $name])->num_rows() > 0) {
            $tenant_db->where('name', $name)->update('settings', ['value' => $value]);
        } else {
            $tenant_db->insert('settings', ['name' => $name, 'value' => $value]);
        }
    }

    /**
     * Get all onboarding sessions with tenant details.
     */
    public function get_all_sessions(array $filters = [], int $limit = 25, int $offset = 0): array
    {
        $builder = $this->db
            ->select('onboarding_sessions.*, tenants.subdomain, tenants.company_name, tenants.phone_number, leads.name as lead_name, leads.contact_person as lead_contact')
            ->from('onboarding_sessions')
            ->join('tenants', 'tenants.id = onboarding_sessions.id_tenants', 'inner')
            ->join('leads', 'leads.id = onboarding_sessions.id_leads', 'left');

        if (!empty($filters['status'])) {
            $builder->where('onboarding_sessions.status', $filters['status']);
        }
        if (!empty($filters['q'])) {
            $q = trim($filters['q']);
            $builder->group_start()
                ->like('tenants.subdomain', $q)
                ->or_like('tenants.company_name', $q)
                ->or_like('leads.name', $q)
                ->group_end();
        }

        $total = $builder->count_all_results('', false);

        $sessions = $builder
            ->order_by('onboarding_sessions.created_at', 'desc')
            ->limit($limit, $offset)
            ->get()
            ->result_array();

        foreach ($sessions as &$s) {
            $s['link'] = $this->generate_onboarding_url($s['token']);
            $s['is_expired'] = (strtotime($s['expires_at']) < time() && $s['status'] !== 'completed');
        }
        unset($s);

        return [
            'sessions' => $sessions,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'total_pages' => max(1, (int) ceil($total / max(1, $limit))),
        ];
    }

    /**
     * Reset and regenerate onboarding session for a tenant.
     */
    public function regenerate_token(int $tenant_id): array
    {
        // Cancel existing sessions
        $this->db->where('id_tenants', $tenant_id)->update('onboarding_sessions', [
            'status' => 'not_opened',
            'token' => bin2hex(random_bytes(24)),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'current_step' => 1,
            'progress_percent' => 0,
            'last_activity_at' => date('Y-m-d H:i:s'),
        ]);

        $session = $this->db
            ->where('id_tenants', $tenant_id)
            ->order_by('created_at', 'desc')
            ->limit(1)
            ->get('onboarding_sessions')
            ->row_array();

        if ($session) {
            $this->db->where('id', $tenant_id)->update('tenants', [
                'onboarding_token' => $session['token'],
                'onboarding_status' => 'pending',
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $session['link'] = $this->generate_onboarding_url($session['token']);
            return $session;
        }

        return $this->create_session($tenant_id);
    }

    /**
     * Build customer onboarding URL.
     */
    public function generate_onboarding_url(string $token): string
    {
        $domain = getenv('MARKETPLACE_DOMAIN') ?: 'booki.kibusiness.co';
        return "https://{$domain}/onboarding/{$token}";
    }
}
