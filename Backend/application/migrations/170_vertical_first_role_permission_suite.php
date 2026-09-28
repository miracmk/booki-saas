<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration 170: Vertical-First, Role-Aware and Permission-Aware Enterprise Suite.
 *
 * Implements:
 * 1. Blueprint extensions: family, business_type, navigation, roles, dashboard, ai_policy, demo_roles, demo_users
 * 2. Granular Permissions & Scopes: own, assigned, branch, all
 * 3. Multi-branch user assignments (user_branches)
 * 4. Job title vs role separation
 * 5. Tenant AI Governance Policies & Controlled Learning Pipeline
 * 6. AI Escalation Handoff Queue
 * 7. Default vertical role templates
 */
class Migration_Vertical_first_role_permission_suite extends App_Migration
{
    public function up(): void
    {
        // 1. Extend industry_blueprints table
        if ($this->db->table_exists('industry_blueprints')) {
            $fields = [];
            if (!$this->db->field_exists('family', 'industry_blueprints')) {
                $fields['family'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
            }
            if (!$this->db->field_exists('business_type', 'industry_blueprints')) {
                $fields['business_type'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
            }
            if (!$this->db->field_exists('navigation', 'industry_blueprints')) {
                $fields['navigation'] = ['type' => 'JSON', 'null' => true];
            }
            if (!$this->db->field_exists('roles', 'industry_blueprints')) {
                $fields['roles'] = ['type' => 'JSON', 'null' => true];
            }
            if (!$this->db->field_exists('dashboard', 'industry_blueprints')) {
                $fields['dashboard'] = ['type' => 'JSON', 'null' => true];
            }
            if (!$this->db->field_exists('ai_policy', 'industry_blueprints')) {
                $fields['ai_policy'] = ['type' => 'JSON', 'null' => true];
            }
            if (!$this->db->field_exists('demo_roles', 'industry_blueprints')) {
                $fields['demo_roles'] = ['type' => 'JSON', 'null' => true];
            }
            if (!$this->db->field_exists('demo_users', 'industry_blueprints')) {
                $fields['demo_users'] = ['type' => 'JSON', 'null' => true];
            }

            if (!empty($fields)) {
                $this->dbforge->add_column('industry_blueprints', $fields);
            }
        }

        // 2. Extend users table with job_title, role_slug, and branch_ids
        if ($this->db->table_exists('users')) {
            $user_fields = [];
            if (!$this->db->field_exists('job_title', 'users')) {
                $user_fields['job_title'] = ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true];
            }
            if (!$this->db->field_exists('role_slug', 'users')) {
                $user_fields['role_slug'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
            }
            if (!$this->db->field_exists('branch_ids', 'users')) {
                $user_fields['branch_ids'] = ['type' => 'TEXT', 'null' => true];
            }

            if (!empty($user_fields)) {
                $this->dbforge->add_column('users', $user_fields);
            }
        }

        // 3. Extend roles table with permissions_json, vertical_family, business_type
        if ($this->db->table_exists('roles')) {
            $role_fields = [];
            if (!$this->db->field_exists('permissions_json', 'roles')) {
                $role_fields['permissions_json'] = ['type' => 'LONGTEXT', 'null' => true];
            }
            if (!$this->db->field_exists('vertical_family', 'roles')) {
                $role_fields['vertical_family'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
            }
            if (!$this->db->field_exists('business_type', 'roles')) {
                $role_fields['business_type'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true];
            }

            if (!empty($role_fields)) {
                $this->dbforge->add_column('roles', $role_fields);
            }
        }

        // 4. Create user_branches table
        if (!$this->db->table_exists('user_branches')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'user_id' => ['type' => 'INT', 'null' => false],
                'branch_id' => ['type' => 'INT', 'null' => false],
                'is_primary' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['user_id', 'branch_id']);
            $this->dbforge->create_table('user_branches');
        }

        // 5. Create tenant_ai_policies table
        if (!$this->db->table_exists('tenant_ai_policies')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'brand_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'tone' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'professional'],
                'language' => ['type' => 'VARCHAR', 'constraint' => 32, 'default' => 'tr'],
                'greeting_style' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'allowed_terms' => ['type' => 'TEXT', 'null' => true],
                'forbidden_terms' => ['type' => 'TEXT', 'null' => true],
                'do_rules' => ['type' => 'TEXT', 'null' => true],
                'dont_rules' => ['type' => 'TEXT', 'null' => true],
                'business_rules' => ['type' => 'TEXT', 'null' => true],
                'cancellation_policy' => ['type' => 'TEXT', 'null' => true],
                'refund_policy' => ['type' => 'TEXT', 'null' => true],
                'discount_policy' => ['type' => 'TEXT', 'null' => true],
                'escalation_rules' => ['type' => 'TEXT', 'null' => true],
                'allowed_actions' => ['type' => 'TEXT', 'null' => true],
                'approval_required_actions' => ['type' => 'TEXT', 'null' => true],
                'forbidden_actions' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->create_table('tenant_ai_policies');
        }

        // 6. Create ai_learned_rules table (Observed -> Suggested -> Owner Approval -> Business Rule -> Active)
        if (!$this->db->table_exists('ai_learned_rules')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'stage' => [
                    'type' => 'ENUM("observed","suggested","approved","active","rejected")',
                    'default' => 'observed',
                ],
                'status' => [
                    'type' => 'VARCHAR',
                    'constraint' => 32,
                    'default' => 'observed',
                ],
                'rule_type' => ['type' => 'VARCHAR', 'constraint' => 64],
                'rule_content' => ['type' => 'TEXT'],
                'context' => ['type' => 'TEXT', 'null' => true],
                'suggested_by' => ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'ai'],
                'approved_by_user_id' => ['type' => 'INT', 'null' => true],
                'approved_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
                'updated_at' => ['type' => 'DATETIME'],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('stage');
            $this->dbforge->create_table('ai_learned_rules');
        }

        // 7. Create ai_escalation_handoffs table
        if (!$this->db->table_exists('ai_escalation_handoffs')) {
            $this->dbforge->add_field([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'escalation_type' => [
                    'type' => 'ENUM("medical","legal","payment","angry_customer","uncertainty")',
                    'default' => 'uncertainty',
                ],
                'customer_id' => ['type' => 'INT', 'null' => true],
                'conversation_id' => ['type' => 'INT', 'null' => true],
                'reason' => ['type' => 'TEXT'],
                'context_summary' => ['type' => 'TEXT', 'null' => true],
                'assigned_role' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'status' => [
                    'type' => 'ENUM("pending","in_progress","resolved")',
                    'default' => 'pending',
                ],
                'resolved_by_user_id' => ['type' => 'INT', 'null' => true],
                'created_at' => ['type' => 'DATETIME'],
                'resolved_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('status');
            $this->dbforge->add_key('escalation_type');
            $this->dbforge->create_table('ai_escalation_handoffs');
        }

        // 8. Seed default roles into roles table
        $this->seed_default_roles();
    }

    private function seed_default_roles(): void
    {
        $existing_slugs = [];
        $query = $this->db->select('id, slug')->get('roles');
        if ($query) {
            foreach ($query->result_array() as $row) {
                $existing_slugs[$row['slug']] = (int) $row['id'];
            }
        }

        // Define default role templates across verticals
        $roles_to_seed = [
            'owner' => [
                'name' => 'İşletme Sahibi (Owner)',
                'is_admin' => 1,
                'vertical_family' => 'universal',
                'business_type' => 'universal',
                'permissions' => [
                    '*' => ['*' => 'all'],
                ],
            ],
            'manager' => [
                'name' => 'İşletme Müdürü (Manager)',
                'is_admin' => 1,
                'vertical_family' => 'universal',
                'business_type' => 'universal',
                'permissions' => [
                    'appointments' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch', 'delete' => 'branch', 'approve' => 'branch', 'override' => 'branch'],
                    'customers' => ['view' => 'all', 'add' => 'all', 'edit' => 'all', 'export' => 'all'],
                    'services' => ['view' => 'all', 'add' => 'all', 'edit' => 'all'],
                    'users' => ['view' => 'branch'],
                    'finance' => ['view' => 'branch', 'manage' => 'branch'],
                    'reports' => ['view' => 'branch', 'export' => 'branch'],
                    'inventory' => ['view' => 'branch', 'manage' => 'branch'],
                    'ai_agent' => ['view' => 'branch', 'execute' => 'branch', 'propose' => 'branch'],
                ],
            ],
            // Restaurant roles
            'general_manager' => [
                'name' => 'Genel Müdür',
                'is_admin' => 1,
                'vertical_family' => 'restaurant_food',
                'business_type' => 'restaurant',
                'permissions' => [
                    '*' => ['*' => 'all'],
                ],
            ],
            'floor_manager' => [
                'name' => 'Salon Şefi (Floor Manager)',
                'is_admin' => 0,
                'vertical_family' => 'restaurant_food',
                'business_type' => 'restaurant',
                'permissions' => [
                    'restaurant_floor_plan' => ['view' => 'branch', 'manage' => 'branch'],
                    'restaurant_reservations' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                    'adisyons' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch', 'override' => 'branch'],
                    'customers' => ['view' => 'branch', 'add' => 'branch'],
                    'ai_agent' => ['view' => 'branch', 'propose' => 'branch'],
                ],
            ],
            'waiter' => [
                'name' => 'Garson / Servis Personeli',
                'is_admin' => 0,
                'vertical_family' => 'restaurant_food',
                'business_type' => 'restaurant',
                'permissions' => [
                    'restaurant_floor_plan' => ['view' => 'branch'],
                    'restaurant_reservations' => ['view' => 'branch'],
                    'adisyons' => ['view' => 'own', 'add' => 'assigned', 'edit' => 'assigned'],
                    'pos' => ['view' => 'branch', 'add' => 'assigned'],
                ],
            ],
            'cashier' => [
                'name' => 'Kasiyer',
                'is_admin' => 0,
                'vertical_family' => 'universal',
                'business_type' => 'universal',
                'permissions' => [
                    'adisyons' => ['view' => 'branch', 'edit' => 'branch'],
                    'pos' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                    'invoices' => ['view' => 'branch', 'add' => 'branch'],
                    'finance' => ['view' => 'branch'],
                    'refunds' => ['execute' => 'branch'],
                ],
            ],
            'kitchen' => [
                'name' => 'Mutfak / KDS',
                'is_admin' => 0,
                'vertical_family' => 'restaurant_food',
                'business_type' => 'restaurant',
                'permissions' => [
                    'verticals_kds' => ['view' => 'branch', 'edit' => 'branch'],
                    'inventory' => ['view' => 'branch'],
                ],
            ],
            'bar' => [
                'name' => 'Bar Personeli',
                'is_admin' => 0,
                'vertical_family' => 'restaurant_food',
                'business_type' => 'restaurant',
                'permissions' => [
                    'verticals_kds' => ['view' => 'branch', 'edit' => 'branch'],
                    'inventory' => ['view' => 'branch'],
                ],
            ],
            // Clinic roles
            'clinic_manager' => [
                'name' => 'Klinik Müdürü',
                'is_admin' => 1,
                'vertical_family' => 'health_clinical',
                'business_type' => 'doctor_clinic',
                'permissions' => [
                    '*' => ['*' => 'branch'],
                ],
            ],
            'doctor' => [
                'name' => 'Hekim / Doktor',
                'is_admin' => 0,
                'vertical_family' => 'health_clinical',
                'business_type' => 'doctor_clinic',
                'permissions' => [
                    'appointments' => ['view' => 'assigned', 'edit' => 'assigned'],
                    'customers' => ['view' => 'assigned', 'edit' => 'assigned'],
                    'verticals_clinic' => ['view' => 'assigned', 'add' => 'assigned', 'edit' => 'assigned'],
                    'services' => ['view' => 'all'],
                    'stations' => ['view' => 'branch'],
                    'ai_agent' => ['view' => 'assigned', 'propose' => 'assigned'],
                ],
            ],
            'nurse' => [
                'name' => 'Hemşire / Sağlık Personeli',
                'is_admin' => 0,
                'vertical_family' => 'health_clinical',
                'business_type' => 'doctor_clinic',
                'permissions' => [
                    'appointments' => ['view' => 'branch'],
                    'customers' => ['view' => 'branch'],
                    'verticals_clinic' => ['view' => 'branch', 'add' => 'branch'],
                    'stations' => ['view' => 'branch'],
                ],
            ],
            'reception' => [
                'name' => 'Resepsiyon / Danışma',
                'is_admin' => 0,
                'vertical_family' => 'universal',
                'business_type' => 'universal',
                'permissions' => [
                    'appointments' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                    'customers' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                    'waitlist' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                    'checkin' => ['view' => 'branch', 'add' => 'branch'],
                    'pos' => ['view' => 'branch', 'add' => 'branch'],
                ],
            ],
            // Beauty & Spa roles
            'professional' => [
                'name' => 'Uzman Estetisyen / Kuaför',
                'is_admin' => 0,
                'vertical_family' => 'beauty_wellness',
                'business_type' => 'beauty_salon',
                'permissions' => [
                    'appointments' => ['view' => 'assigned', 'edit' => 'assigned'],
                    'customers' => ['view' => 'assigned'],
                    'services' => ['view' => 'all'],
                    'stations' => ['view' => 'branch'],
                    'ai_agent' => ['view' => 'assigned', 'propose' => 'assigned'],
                ],
            ],
            'therapist' => [
                'name' => 'Masaj Terapisti',
                'is_admin' => 0,
                'vertical_family' => 'beauty_wellness',
                'business_type' => 'massage_spa',
                'permissions' => [
                    'appointments' => ['view' => 'assigned', 'edit' => 'assigned'],
                    'customers' => ['view' => 'assigned'],
                    'services' => ['view' => 'all'],
                    'stations' => ['view' => 'branch'],
                ],
            ],
            'hamam_staff' => [
                'name' => 'Tellak / Hamam Görevlisi',
                'is_admin' => 0,
                'vertical_family' => 'beauty_wellness',
                'business_type' => 'massage_spa',
                'permissions' => [
                    'appointments' => ['view' => 'assigned'],
                    'stations' => ['view' => 'branch'],
                ],
            ],
            // Sports & Fitness roles
            'trainer' => [
                'name' => 'Personal Trainer (PT)',
                'is_admin' => 0,
                'vertical_family' => 'sports_fitness',
                'business_type' => 'gym',
                'permissions' => [
                    'appointments' => ['view' => 'assigned', 'edit' => 'assigned'],
                    'customers' => ['view' => 'assigned'],
                    'packages' => ['view' => 'assigned'],
                    'memberships' => ['view' => 'assigned'],
                    'checkin' => ['view' => 'branch'],
                ],
            ],
            'group_instructor' => [
                'name' => 'Grup Dersi Eğitmeni',
                'is_admin' => 0,
                'vertical_family' => 'sports_fitness',
                'business_type' => 'gym',
                'permissions' => [
                    'appointments' => ['view' => 'assigned'],
                    'customers' => ['view' => 'assigned'],
                    'stations' => ['view' => 'branch'],
                ],
            ],
            'inventory' => [
                'name' => 'Depo / Envanter Sorumlusu',
                'is_admin' => 0,
                'vertical_family' => 'universal',
                'business_type' => 'universal',
                'permissions' => [
                    'inventory' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch', 'manage' => 'branch'],
                    'products' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                ],
            ],
        ];

        foreach ($roles_to_seed as $slug => $data) {
            $role_payload = [
                'name' => $data['name'],
                'slug' => $slug,
                'is_admin' => $data['is_admin'],
                'vertical_family' => $data['vertical_family'],
                'business_type' => $data['business_type'],
                'permissions_json' => json_encode($data['permissions']),
                'update_datetime' => date('Y-m-d H:i:s'),
            ];

            if (isset($existing_slugs[$slug])) {
                $this->db->update('roles', $role_payload, ['id' => $existing_slugs[$slug]]);
            } else {
                $role_payload['create_datetime'] = date('Y-m-d H:i:s');
                // Give basic legacy bitmask numbers so old can() doesn't fail
                $role_payload['appointments'] = $data['is_admin'] ? 15 : 7;
                $role_payload['customers'] = $data['is_admin'] ? 15 : 7;
                $role_payload['services'] = $data['is_admin'] ? 15 : 1;
                $role_payload['users'] = $data['is_admin'] ? 15 : 0;
                $role_payload['system_settings'] = $data['is_admin'] ? 15 : 0;
                $role_payload['user_settings'] = 15;
                $this->db->insert('roles', $role_payload);
                $existing_slugs[$slug] = $this->db->insert_id();
            }
        }

        // Also update standard roles (admin, provider, customer, secretary) with permissions_json
        $std_updates = [
            'admin' => ['*' => ['*' => 'all']],
            'provider' => [
                'appointments' => ['view' => 'assigned', 'add' => 'assigned', 'edit' => 'assigned'],
                'customers' => ['view' => 'assigned'],
                'services' => ['view' => 'all'],
                'stations' => ['view' => 'branch'],
                'ai_agent' => ['view' => 'assigned', 'propose' => 'assigned'],
            ],
            'secretary' => [
                'appointments' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch', 'delete' => 'branch'],
                'customers' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                'services' => ['view' => 'all'],
                'stations' => ['view' => 'branch'],
                'waitlist' => ['view' => 'branch', 'add' => 'branch', 'edit' => 'branch'],
                'checkin' => ['view' => 'branch', 'add' => 'branch'],
                'pos' => ['view' => 'branch', 'add' => 'branch'],
            ],
            'customer' => [
                'appointments' => ['view' => 'own', 'add' => 'own'],
                'customers' => ['view' => 'own', 'edit' => 'own'],
            ],
        ];

        foreach ($std_updates as $slug => $perms) {
            if (isset($existing_slugs[$slug])) {
                $this->db->update('roles', [
                    'permissions_json' => json_encode($perms),
                    'update_datetime' => date('Y-m-d H:i:s'),
                ], ['slug' => $slug]);
            }
        }
    }

    public function down(): void
    {
        // Safe rollback without dropping business data
    }
}
